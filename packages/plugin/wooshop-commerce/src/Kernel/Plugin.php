<?php
declare(strict_types=1);
namespace WooShop\Commerce\Kernel;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Infrastructure\Schema;
use WooShop\Commerce\Infrastructure\Idempotency;
use WooShop\Commerce\Jobs\Queue;
use WooShop\Commerce\Security\Authorization;
use WooShop\Commerce\Settings\SettingsService;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;
use WooShop\Commerce\Support\Rest;
use WooShop\Commerce\Support\SchemaValidator;

final class Plugin
{
    private static ?self $instance = null;
    private Database $db;
    private EntityStore $store;
    private SettingsService $settings;
    private ModuleRegistry $modules;
    private Authorization $auth;
    private Queue $queue;
    private array $serviceCapabilities = [];
    private array $contract;

    private function __construct()
    {
        global $wpdb;
        $this->db = new Database($wpdb);
        $this->store = new EntityStore($this->db);
        $this->settings = new SettingsService($this->store, $this->db);
        $this->modules = new ModuleRegistry($this->store, $this->db);
        $this->auth = new Authorization($this->db);
        $this->queue = new Queue($this->db);
        $this->contract = json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/openapi.json'), true, 128, JSON_THROW_ON_ERROR);
    }
    public static function instance(): ?self { return self::$instance; }
    public function settings(): SettingsService { return $this->settings; }
    public function modules(): ModuleRegistry { return $this->modules; }
    public function queue(): Queue { return $this->queue; }
    public function database(): Database { return $this->db; }
    public function entities(): EntityStore { return $this->store; }

    public static function boot(): void
    {
        if (self::$instance) { return; }
        self::$instance = new self();
        self::$instance->register();
    }
    private function register(): void
    {
        if ((int) get_option('wooshop_commerce_schema_version', 0) < Schema::VERSION) { Schema::install($this->db); }
        add_action('init', static fn () => load_plugin_textdomain('wooshop-commerce', false, 'wooshop-commerce/languages'));
        add_action('rest_api_init', [$this, 'routes']);
        add_filter('rest_pre_dispatch', [$this, 'requestBoundary'], 8, 3);
        add_filter('rest_post_dispatch', static function ($response, $server, $request) {
            if (str_starts_with($request->get_route(), '/commerce/v1/')) {
                $response = rest_ensure_response($response);
                $response->header('Cache-Control', 'private, no-store');
                $response->header('X-Content-Type-Options', 'nosniff');
                if ($response->get_status() === 429) { $response->header('Retry-After', (string) (60 - (time() % 60))); }
            }
            return $response;
        }, 20, 3);
        add_filter('cron_schedules', static function (array $schedules): array {
            $schedules['wooshop_minute'] = ['interval' => 60, 'display' => 'Wooshop queue']; return $schedules;
        });
        add_action('init', static function (): void {
            if (!wp_next_scheduled('wooshop_commerce_run_jobs')) { wp_schedule_event(time() + 60, 'wooshop_minute', 'wooshop_commerce_run_jobs'); }
        });
        add_action('wooshop_commerce_run_jobs', fn () => $this->queue->run());
        add_action('wooshop_commerce_enqueue', fn (string $handler, array $payload, array $options = []) => $this->queue->enqueue($handler, $payload, $options), 10, 3);
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_wooshop_save_settings', [$this, 'saveSettings']);
        add_action('admin_post_wooshop_export_configuration', [$this, 'exportConfiguration']);
        add_action('admin_post_wooshop_reset_settings', [$this, 'resetSettings']);
        add_action('admin_post_wooshop_transition_module', [$this, 'transitionModule']);
        add_action('admin_post_wooshop_commit_settings_revision', [$this, 'commitSettingsRevision']);
        add_action('admin_notices', [$this, 'notices']);
        foreach ([\WooShop\Commerce\Catalog\CatalogModule::class, \WooShop\Commerce\Marketplace\MarketplaceModule::class] as $class) {
            if (class_exists($class) && class_exists('WooCommerce')) {
                $module = new $class($this->db, $this->store);
                $module->register();
                $this->serviceCapabilities[$class] = method_exists($module, 'capabilities') ? $module->capabilities() : [];
            }
        }
        // Register real handlers through trusted code; no method name supplied by a client is dispatched.
        do_action('wooshop_commerce_register_job_handlers', $this->queue);
    }
    public function notices(): void
    {
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) { return; }
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Wooshop Commerce needs WooCommerce. Existing records are retained; dependent operations are unavailable.', 'wooshop-commerce') . '</p></div>';
        }
    }
    public function requestBoundary(mixed $result, object $server, \WP_REST_Request $request): mixed
    {
        if (!str_starts_with($request->get_route(), '/commerce/v1/')) { return $result; }
        return Rest::respond(function () use ($result, $request): mixed {
            $bodyLimit = str_contains($request->get_route(), '/settings/') ? 262144 : 1048576;
            if (strlen((string) $request->get_body()) > $bodyLimit) { throw new DomainError('body_too_large', 'The request exceeds its supported size.', 413); }
            $read = in_array($request->get_method(), ['GET', 'HEAD', 'OPTIONS'], true);
            $finance = str_ends_with($request->get_route(), '/approval');
            $limit = $finance ? 5 : ($read ? 60 : 30);
            $principal = get_current_user_id() ? 'user:' . get_current_user_id() : 'edge:' . hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), wp_salt('nonce'));
            $bucket = hash('sha256', $principal . ':' . ($finance ? 'finance' : ($read ? 'read' : 'write')) . ':' . intdiv(time(), 60));
            $table = $this->db->table('rate_limits'); $wpdb = $this->db->wpdb();
            $ok = $wpdb->query($wpdb->prepare("INSERT INTO {$table} (bucket,hits,expires_at) VALUES (%s,1,%d) ON DUPLICATE KEY UPDATE hits=hits+1", $bucket, time() + 120));
            if ($ok === false) { throw new DomainError('rate_storage_unavailable', 'Request quota storage is unavailable.', 503); }
            $hits = (int) $wpdb->get_var($wpdb->prepare("SELECT hits FROM {$table} WHERE bucket=%s", $bucket));
            if ($hits > $limit) { throw new DomainError('rate_limited', 'Too many requests. Retry after the current minute.', 429, ['retry_after_seconds' => 60 - (time() % 60)]); }
            if (!class_exists('WooCommerce') && !str_contains($request->get_route(), '/diagnostics') && !str_contains($request->get_route(), '/operations/')) { throw new DomainError('dependency_unavailable', 'WooCommerce is unavailable.', 503); }
            return $result;
        });
    }
    private function route(string $path, string $methods, string $capability, callable $callback): void
    {
        register_rest_route('commerce/v1', $path, [
            'methods' => $methods,
            'permission_callback' => fn () => $this->auth->can($capability) ? true : new \WP_Error('forbidden', __('This action is unavailable in your scope.', 'wooshop-commerce'), ['status' => is_user_logged_in() ? 403 : 401]),
            'callback' => fn (\WP_REST_Request $request) => Rest::respond(fn () => in_array($request->get_method(), ['GET', 'HEAD', 'OPTIONS'], true) ? $callback($request) : (new Idempotency($this->db))->run($request, fn () => $callback($request))),
        ]);
    }
    private function body(\WP_REST_Request $request, string $schema): array
    {
        try { $value = json_decode((string) $request->get_body(), false, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new DomainError('malformed_json', 'The JSON request is malformed.', 400); }
        (new SchemaValidator($this->contract['components']['schemas']))->validate($value, $this->contract['components']['schemas'][$schema]);
        return json_decode($request->get_body(), true, 64, JSON_THROW_ON_ERROR);
    }
    public function routes(): void
    {
        $this->route('/diagnostics', 'GET', 'commerce.diagnostics.read', fn () => $this->diagnostics());
        register_rest_route('commerce/v1', '/operations/(?P<operation_id>[a-fA-F0-9-]{36})', [
            'methods' => 'GET', 'permission_callback' => static fn () => is_user_logged_in(),
            'callback' => fn ($q) => Rest::respond(fn () => $this->operation((string) $q['operation_id'])),
        ]);
        $this->route('/modules/(?P<module_key>[A-Za-z0-9_-]{1,80})/lifecycle', 'POST', 'commerce.modules.manage', function ($q) {
            $body = $this->body($q, 'ModuleTransition'); Rest::idempotency($q);
            $result = $this->db->transaction(fn () => $this->modules->transition($q['module_key'], $body['target_state'], Rest::version($q), $body['reason_code']));
            return $this->completedOperation('module.lifecycle', $result['id']);
        });
        $this->route('/settings/preview', 'POST', 'commerce.settings.manage', function ($q) {
            $body = $this->body($q, 'SettingsPreview'); Rest::idempotency($q);
            if ($body['schema_version'] !== 1) { throw new DomainError('unsupported_schema', 'Settings schema version is unsupported.', 422); }
            return $this->settings->preview($body['settings']);
        });
        $this->route('/settings/export', 'POST', 'commerce.settings.manage', function ($q) {
            $body = $this->body($q, 'ExportRequest'); Rest::idempotency($q);
            if ($body['kind'] !== 'configuration' || $body['format'] !== 'json') { throw new DomainError('export_unavailable', 'This release exports configuration JSON only. Catalog/operational/financial exports need their scoped implementation.', 501); }
            $record = $this->store->create('configuration_export', ['configuration' => $this->settings->export(), 'expires_at' => time() + 86400], 'user:' . get_current_user_id());
            return new \WP_REST_Response($this->completedOperation('settings.export', $record['id']), 202);
        });
        $this->route('/imports', 'POST', 'commerce.import.manage', function ($q) {
            $this->body($q, 'ImportRequest'); Rest::idempotency($q);
            throw new DomainError('import_verification_unavailable', 'Approved upload scanning and bounded catalog import are not implemented. Use the guarded local fixture seeder only in development.', 503);
        });
    }
    public function diagnostics(): array
    {
        return ['checks' => [
            ['code' => 'schema', 'state' => (int) get_option('wooshop_commerce_schema_version', 0) === Schema::VERSION ? 'pass' : 'fail', 'message' => 'Plugin-owned schema version ' . (int) get_option('wooshop_commerce_schema_version', 0)],
            ['code' => 'woocommerce', 'state' => class_exists('WooCommerce') ? 'pass' : 'fail', 'message' => class_exists('WooCommerce') ? 'WooCommerce ' . WC_VERSION . '; native commerce owns customer orders.' : 'WooCommerce unavailable.'],
            ['code' => 'marketplace_activation', 'state' => 'warn', 'message' => 'Marketplace checkout, funds, provider and independent maintenance approvals remain blocked.'],
            ['code' => 'job_queue', 'state' => isset($this->queue->counts()['poison']) ? 'warn' : 'pass', 'message' => 'Only registered handlers execute; unavailable providers never report success.'],
            ['code' => 'implementation', 'state' => 'warn', 'message' => 'Development implementation is incomplete. See docs/implementation for per-requirement evidence.'],
        ], 'generated_at' => gmdate('Y-m-d\TH:i:s\Z')];
    }
    private function completedOperation(string $kind, string $resource): array
    {
        $record = $this->store->create('operation', ['state' => 'succeeded', 'resource_id' => $resource, 'correlation_id' => Ids::uuid(), 'kind' => $kind], 'user:' . get_current_user_id());
        return $this->operation($record['id']);
    }
    private function operation(string $id): array
    {
        $record = $this->store->get('operation', $id);
        if (!$record || ($record['owner_id'] !== 'user:' . get_current_user_id() && !$this->auth->can('commerce.diagnostics.read'))) { throw new DomainError('not_found', 'Operation not found.', 404); }
        return ['operation_id' => $record['id'], 'state' => $record['data']['state'], 'resource_id' => $record['data']['resource_id'] ?? null, 'correlation_id' => $record['data']['correlation_id'], 'retry_after_seconds' => $record['data']['retry_after_seconds'] ?? 0];
    }
    public function menu(): void
    {
        add_submenu_page('woocommerce', __('Commerce modules', 'wooshop-commerce'), __('Commerce modules', 'wooshop-commerce'), 'manage_woocommerce', 'wooshop-commerce', [$this, 'admin']);
    }
    public function admin(): void
    {
        $this->auth->require('commerce.settings.manage');
        $snapshot = $this->settings->snapshot();
        echo '<div class="wrap"><h1>' . esc_html__('Wooshop Commerce', 'wooshop-commerce') . '</h1><p>' . esc_html__('Development controls. Provider-dependent activation remains gated.', 'wooshop-commerce') . '</p>';
        echo '<h2>' . esc_html__('Typed settings preview and save', 'wooshop-commerce') . '</h2><p>' . esc_html__('Enter key/value/scope entries as JSON. Native visual settings remain in Appearance → Editor. Saving validates the entire preview before commit.', 'wooshop-commerce') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="wooshop_save_settings"><input type="hidden" name="version" value="' . esc_attr((string) $snapshot['version']) . '">';
        wp_nonce_field('wooshop_save_settings');
        echo '<label for="wooshop-settings">' . esc_html__('Setting entries', 'wooshop-commerce') . '</label><p><textarea id="wooshop-settings" name="entries" rows="8" cols="100" class="large-text code">' . esc_textarea('[{"key":"wishlist.max_items","value":100,"scope":"site"}]') . '</textarea></p>';
        submit_button(__('Validate and save', 'wooshop-commerce')); echo '</form>';
        $this->restorationPreview($snapshot);
        $revisions = $this->settings->revisions();
        if ($revisions) {
            echo '<h2>' . esc_html__('Restore plugin configuration', 'wooshop-commerce') . '</h2><p>' . esc_html__('Review a previous compatible plugin configuration before applying it as a new revision. This does not restore native styles, orders or financial history. The latest twenty revisions are listed.', 'wooshop-commerce') . '</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="wooshop_reset_settings">';
            wp_nonce_field('wooshop_reset_settings');
            echo '<label for="wooshop-revision">' . esc_html__('Saved revision', 'wooshop-commerce') . '</label> <select name="revision_id" id="wooshop-revision">';
            foreach ($revisions as $revision) { echo '<option value="' . esc_attr($revision['id']) . '">' . esc_html(sprintf(__('Revision %1$d · %2$s UTC · %3$d overrides', 'wooshop-commerce'), $revision['revision'], $revision['created_at'], $revision['key_count'])) . '</option>'; }
            echo '</select> '; submit_button(__('Preview restoration', 'wooshop-commerce'), 'secondary', 'submit', false); echo '</form>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="wooshop_export_configuration">'; wp_nonce_field('wooshop_export_configuration'); submit_button(__('Export configuration JSON', 'wooshop-commerce'), 'secondary'); echo '</form>';
        echo '<h2>' . esc_html__('Module availability', 'wooshop-commerce') . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__('Module', 'wooshop-commerce') . '</th><th>' . esc_html__('State', 'wooshop-commerce') . '</th><th>' . esc_html__('Implementation', 'wooshop-commerce') . '</th></tr></thead><tbody>';
        foreach ($this->modules->all() as $module) {
            echo '<tr><th scope="row">' . esc_html($module['id'] . ' ' . $module['name']) . '</th><td>' . esc_html($module['state']);
            if ($module['lifecycle_supported']) {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="wooshop_transition_module"><input type="hidden" name="module_id" value="' . esc_attr($module['id']) . '"><input type="hidden" name="version" value="' . esc_attr((string) $module['version']) . '">';
                wp_nonce_field('wooshop_transition_module_' . $module['id']);
                echo '<label for="state-' . esc_attr($module['id']) . '">' . esc_html__('Module state', 'wooshop-commerce') . '</label> <select id="state-' . esc_attr($module['id']) . '" name="target_state">';
                foreach (['enabled', 'disabled_new_operations', 'draining', 'disabled'] as $state) { echo '<option value="' . esc_attr($state) . '" ' . selected($module['state'], $state, false) . '>' . esc_html($state) . '</option>'; }
                echo '</select> '; submit_button(__('Apply module state', 'wooshop-commerce'), 'secondary small', 'submit', false); echo '</form>';
            }
            echo '</td><td>' . esc_html($module['availability']) . '</td></tr>';
        }
        echo '</tbody></table><h2>' . esc_html__('Settings inventory', 'wooshop-commerce') . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__('Key', 'wooshop-commerce') . '</th><th>' . esc_html__('Owner / type', 'wooshop-commerce') . '</th><th>' . esc_html__('Allowed values', 'wooshop-commerce') . '</th></tr></thead><tbody>';
        foreach ($this->settings->validator()->inventory() as $key => $row) { echo '<tr><th scope="row">' . esc_html($key) . '</th><td>' . esc_html($row['owning_system'] . ' / ' . $row['type']) . '</td><td>' . esc_html($row['valid_values_units_range']) . '</td></tr>'; }
        echo '</tbody></table></div>';
    }
    public function saveSettings(): void
    {
        $result = Rest::respond(function (): array {
            $this->auth->require('commerce.settings.manage'); check_admin_referer('wooshop_save_settings');
            $raw = wp_unslash((string) ($_POST['entries'] ?? ''));
            if (strlen($raw) > 262144) { throw new DomainError('settings_too_large', 'Settings exceed 256 KiB.', 413); }
            try { $entries = json_decode($raw, true, 64, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new DomainError('malformed_json', 'Setting entries must be valid JSON.', 400); }
            if (!is_array($entries) || !array_is_list($entries)) { throw new DomainError('invalid_settings', 'A JSON list of setting entries is required.'); }
            return $this->db->transaction(function () use ($entries): array {
                $preview = $this->settings->preview($entries);
                return $this->settings->commit($preview['preview_id'], absint($_POST['version'] ?? 0));
            });
        });
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), esc_html__('Settings were not saved', 'wooshop-commerce'), ['response' => $result->get_error_data()['status'] ?? 400]); }
        wp_safe_redirect(admin_url('admin.php?page=wooshop-commerce&saved=1')); exit;
    }
    public function exportConfiguration(): void
    {
        $this->auth->require('commerce.settings.manage'); check_admin_referer('wooshop_export_configuration');
        nocache_headers(); header('Content-Type: application/json; charset=utf-8'); header('Content-Disposition: attachment; filename="wooshop-configuration.json"');
        echo wp_json_encode($this->settings->export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); exit;
    }
    public function resetSettings(): void
    {
        $result = Rest::respond(function (): array {
            $this->auth->require('commerce.settings.manage'); check_admin_referer('wooshop_reset_settings');
            return $this->settings->previewRevision(sanitize_text_field(wp_unslash((string) ($_POST['revision_id'] ?? ''))));
        });
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), esc_html__('Restoration preview unavailable', 'wooshop-commerce'), ['response' => $result->get_error_data()['status'] ?? 400]); }
        wp_safe_redirect(add_query_arg('restore_preview', $result['preview_id'], admin_url('admin.php?page=wooshop-commerce'))); exit;
    }
    private function restorationPreview(array $snapshot): void
    {
        $id = sanitize_text_field(wp_unslash((string) ($_GET['restore_preview'] ?? '')));
        if ($id === '') { return; }
        $preview = Ids::valid($id) ? $this->store->get('settings_preview', $id) : null;
        if (!$preview || $preview['owner_id'] !== 'user:' . get_current_user_id() || !isset($preview['data']['restore_revision']) || ($preview['data']['expires_at'] ?? 0) < time()) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Restoration preview is unavailable or expired.', 'wooshop-commerce') . '</p></div>'; return;
        }
        $restored = array_column($preview['data']['entries'], 'value', 'key');
        $keys = array_unique([...array_keys($snapshot['values']), ...array_keys($restored)]);
        echo '<h2>' . esc_html__('Review configuration restoration', 'wooshop-commerce') . '</h2><p>' . esc_html__('Missing overrides return to their current validated defaults. Applying advances the configuration revision.', 'wooshop-commerce') . '</p><table class="widefat"><thead><tr><th>' . esc_html__('Setting', 'wooshop-commerce') . '</th><th>' . esc_html__('Current override', 'wooshop-commerce') . '</th><th>' . esc_html__('Restored override', 'wooshop-commerce') . '</th></tr></thead><tbody>';
        foreach ($keys as $key) { echo '<tr><th scope="row">' . esc_html($key) . '</th><td>' . esc_html(array_key_exists($key, $snapshot['values']) ? wp_json_encode($snapshot['values'][$key]) : __('Inherit default', 'wooshop-commerce')) . '</td><td>' . esc_html(array_key_exists($key, $restored) ? wp_json_encode($restored[$key]) : __('Inherit default', 'wooshop-commerce')) . '</td></tr>'; }
        echo '</tbody></table><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="wooshop_commit_settings_revision"><input type="hidden" name="preview_id" value="' . esc_attr($id) . '"><input type="hidden" name="version" value="' . esc_attr((string) $snapshot['version']) . '">';
        wp_nonce_field('wooshop_commit_settings_revision_' . $id); submit_button(__('Apply reviewed configuration', 'wooshop-commerce')); echo '</form>';
    }
    public function commitSettingsRevision(): void
    {
        $result = Rest::respond(function (): array {
            $this->auth->require('commerce.settings.manage');
            $id = sanitize_text_field(wp_unslash((string) ($_POST['preview_id'] ?? '')));
            check_admin_referer('wooshop_commit_settings_revision_' . $id);
            $preview = $this->store->get('settings_preview', $id);
            if (!$preview || !isset($preview['data']['restore_revision'])) { throw new DomainError('not_found', 'Restoration preview not found.', 404); }
            return $this->settings->commit($id, absint($_POST['version'] ?? 0));
        });
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), esc_html__('Configuration was not restored', 'wooshop-commerce'), ['response' => $result->get_error_data()['status'] ?? 400]); }
        wp_safe_redirect(admin_url('admin.php?page=wooshop-commerce&restored=1')); exit;
    }
    public function transitionModule(): void
    {
        $result = Rest::respond(function (): array {
            $this->auth->require('commerce.modules.manage');
            $id = sanitize_text_field(wp_unslash((string) ($_POST['module_id'] ?? '')));
            check_admin_referer('wooshop_transition_module_' . $id);
            return $this->modules->transition($id, sanitize_text_field(wp_unslash((string) ($_POST['target_state'] ?? ''))), absint($_POST['version'] ?? 0), 'administrator_request');
        });
        if (is_wp_error($result)) { wp_die(esc_html($result->get_error_message()), esc_html__('Module state was not changed', 'wooshop-commerce'), ['response' => $result->get_error_data()['status'] ?? 400]); }
        wp_safe_redirect(admin_url('admin.php?page=wooshop-commerce&module_changed=1')); exit;
    }
}
