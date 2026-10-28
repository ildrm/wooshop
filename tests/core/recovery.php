<?php
/** Guarded third-site recovery observations. Never packaged; no order/ledger mutation. */
// Direct reader only: capture the restored database before plugins/theme/cron can
// refresh even one transient. These constants affect this PHP process, never HTTP.
if (PHP_SAPI === 'cli' && (($argv[1] ?? '') === 'raw')) {
    try {
        if (defined('ABSPATH') || PHP_INT_SIZE < 8 || !preg_match('/^[a-f0-9]{32}$/D', $argv[2] ?? '')
            || getenv('WORDPRESS_DB_NAME') !== 'wooshop_package_check' || getenv('WORDPRESS_DB_USER') !== 'wooshop_package'
            || getenv('WORDPRESS_DB_HOST') !== 'db') { throw new RuntimeException('Exact direct local raw-reader process required.'); }
        define('SHORTINIT', true);
        define('WP_INSTALLING', true); // Native process-local maintenance bypass for this reader only.
        require '/var/www/html/wp-load.php';
        if (!defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true || wp_get_environment_type() !== 'local'
            || !defined('DB_NAME') || DB_NAME !== 'wooshop_package_check' || !defined('DB_USER') || DB_USER !== 'wooshop_package'
            || !defined('DB_HOST') || DB_HOST !== 'db' || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL !== true
            || !defined('SHORTINIT') || SHORTINIT !== true || function_exists('WC') || did_action('plugins_loaded')) {
            throw new RuntimeException('Raw reader runtime scope or minimal-bootstrap guard failed.');
        }
        global $wpdb;
        $wpdb->suppress_errors(true);
        if ($wpdb->get_var('SELECT DATABASE()') !== 'wooshop_package_check' || !preg_match('/^[A-Za-z0-9_]+$/D', $wpdb->prefix)) { throw new RuntimeException('Raw reader database scope mismatch.'); }
        $rawRows = static function (string $sql) use ($wpdb): array {
            $value = $wpdb->get_results($sql, ARRAY_A);
            if (!is_array($value) || $wpdb->last_error !== '') { throw new RuntimeException('Guarded raw recovery read failed.'); }
            return $value;
        };
        $locations = $rawRows($wpdb->prepare("SELECT option_name,option_value FROM `{$wpdb->options}` WHERE option_name IN (%s,%s)", 'home', 'siteurl'));
        $locations = array_column($locations, 'option_value', 'option_name');
        if (count($locations) !== 2 || rtrim($locations['home'] ?? '', '/') !== 'http://localhost:18882' || rtrim($locations['siteurl'] ?? '', '/') !== 'http://localhost:18882') { throw new RuntimeException('Raw home/siteurl scope mismatch.'); }
        $maintenance = ABSPATH.'.maintenance';
        if (!is_file($maintenance) || is_link($maintenance) || !is_readable($maintenance)
            || !preg_match('/\$upgrading\s*=\s*(\d+)\s*;/D', (string) file_get_contents($maintenance), $held)
            || time() - (int) $held[1] >= 600) { throw new RuntimeException('Unchanged native maintenance hold is required for raw observation.'); }
        $tableInfo = $rawRows($wpdb->prepare('SELECT TABLE_NAME,TABLE_TYPE,ENGINE,DATA_LENGTH,INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=%s ORDER BY TABLE_NAME', 'wooshop_package_check'));
        if (!$tableInfo || count($tableInfo) > 200) { throw new RuntimeException('Raw table count exceeds bounded local scope.'); }
        $estimatedBytes = 0;
        foreach ($tableInfo as $table) {
            if ($table['TABLE_TYPE'] !== 'BASE TABLE' || strtoupper((string) $table['ENGINE']) !== 'INNODB' || !preg_match('/^[A-Za-z0-9_]+$/D', $table['TABLE_NAME']) || !str_starts_with($table['TABLE_NAME'], $wpdb->prefix)) { throw new RuntimeException('Raw reader requires same-prefix InnoDB base tables.'); }
            $estimatedBytes += (int) $table['DATA_LENGTH'] + (int) $table['INDEX_LENGTH'];
        }
        if ($estimatedBytes > 134217728) { throw new RuntimeException('Raw recovery dataset exceeds 128 MiB.'); }
        foreach (['TRIGGERS' => 'TRIGGER_SCHEMA', 'ROUTINES' => 'ROUTINE_SCHEMA', 'EVENTS' => 'EVENT_SCHEMA'] as $table => $column) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.{$table} WHERE {$column}=%s", 'wooshop_package_check'));
            if ($count === null || (int) $count !== 0) { throw new RuntimeException('Raw reader refuses triggers, routines or events.'); }
        }
        $active = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB=DATABASE() AND ID<>CONNECTION_ID() AND COMMAND<>'Sleep'");
        if ($active === null || (int) $active !== 0) { throw new RuntimeException('Another database reader/writer is active during raw observation.'); }
        $jobs = $wpdb->prefix.'wooshop_jobs';
        if (!in_array($jobs, array_column($tableInfo, 'TABLE_NAME'), true)) { throw new RuntimeException('Installed plugin tables required; raw reader never repairs schema.'); }
        $running = $wpdb->get_var("SELECT COUNT(*) FROM `{$jobs}` WHERE state='running'");
        if ($running === null || (int) $running !== 0) { throw new RuntimeException('A running plugin job prevents raw observation.'); }
        // SHOW CREATE and the aggregate contain arrays/scalars only. Match the
        // full helper's canonical JSON ordering and flags byte-for-byte.
        $rawNormalize = static function ($value) use (&$rawNormalize) {
            if (is_object($value)) { throw new RuntimeException('Unexpected object in raw recovery snapshot.'); }
            if (is_array($value)) { if (!array_is_list($value)) { ksort($value, SORT_STRING); } foreach ($value as &$entry) { $entry = $rawNormalize($entry); } unset($entry); }
            return $value;
        };
        $rawDigest = static fn ($value): string => hash('sha256', json_encode($rawNormalize($value), JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
        $tables = []; $totalRows = 0;
        foreach ($tableInfo as $table) {
            $name = $table['TABLE_NAME']; $hashes = [];
            $indexes = $rawRows("SHOW INDEX FROM `{$name}` WHERE Key_name='PRIMARY'");
            usort($indexes, static fn (array $a, array $b): int => (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index']);
            if (!$indexes) { throw new RuntimeException('Raw reader requires a stable primary key on every table.'); }
            $primary = [];
            foreach ($indexes as $index) { if (!preg_match('/^[A-Za-z0-9_]+$/D', $index['Column_name'])) { throw new RuntimeException('Unsupported raw primary key identifier.'); } $primary[] = '`'.$index['Column_name'].'`'; }
            $orderBy = implode(',', $primary);
            for ($offset = 0; ; $offset += 500) {
                $batch = $rawRows("SELECT * FROM `{$name}` ORDER BY {$orderBy} LIMIT 500 OFFSET {$offset}");
                foreach ($batch as $row) { ksort($row, SORT_STRING); $hashes[] = hash('sha256', serialize($row)); if (++$totalRows > 200000) { throw new RuntimeException('Raw row count exceeds this drill limit.'); } }
                if (count($batch) < 500) { break; }
            }
            sort($hashes, SORT_STRING);
            $schema = $rawRows("SHOW CREATE TABLE `{$name}`");
            $tables[$name] = ['rows' => count($hashes), 'rows_sha256' => hash('sha256', implode('', $hashes)), 'schema_sha256' => $rawDigest($schema[0])];
        }
        $markerCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->options}` WHERE option_name=%s", 'wooshop_recovery_probe_'.$argv[2]));
        if ($markerCount === null || $wpdb->last_error !== '') { throw new RuntimeException('Raw marker absence could not be read.'); }
        echo 'WOOSHOP_RECOVERY_RAW '.json_encode(['database' => 'wooshop_package_check', 'table_count' => count($tables), 'total_rows' => $totalRows, 'estimated_database_bytes' => $estimatedBytes,
            'tables' => $tables, 'database_rows_and_schema_sha256' => $rawDigest($tables), 'marker_absent' => (int) $markerCount === 0], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        exit(0);
    } catch (Throwable $failure) {
        fwrite(STDERR, 'Guarded raw recovery observation failed ('.get_class($failure).').'.PHP_EOL);
        exit(1);
    }
}
(static function (array $arguments): void {
    if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true
        || wp_get_environment_type() !== 'local' || untrailingslashit(home_url()) !== 'http://localhost:18882'
        || untrailingslashit(site_url()) !== 'http://localhost:18882' || !defined('DB_NAME') || DB_NAME !== 'wooshop_package_check'
        || !defined('DB_USER') || DB_USER !== 'wooshop_package' || !defined('DB_HOST') || DB_HOST !== 'db'
        || !function_exists('WC') || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL !== true) {
        throw new RuntimeException('Recovery refused: exact isolated package-check runtime required.');
    }
    global $wpdb;
    $wpdb->suppress_errors(true);
    if ($wpdb->get_var('SELECT DATABASE()') !== 'wooshop_package_check' || !preg_match('/^[A-Za-z0-9_]+$/D', $wpdb->prefix)) { throw new RuntimeException('Recovery database scope mismatch.'); }
    $mode = $arguments[0] ?? ''; $run = $arguments[1] ?? '';
    if (!in_array($mode, ['preflight', 'snapshot', 'marker', 'marker-absent'], true) || !preg_match('/^[a-f0-9]{32}$/D', $run)) { throw new RuntimeException('Invalid bounded recovery operation.'); }
    if (!in_array('wooshop-commerce/wooshop-commerce.php', (array) get_option('active_plugins', []), true)
        || !class_exists(\WooShop\Commerce\Kernel\Plugin::class, false) || !\WooShop\Commerce\Kernel\Plugin::instance()) { throw new RuntimeException('Active installed commerce kernel required.'); }
    $guard = WPMU_PLUGIN_DIR . '/wooshop-local-guard.php';
    if (!is_readable($guard) || !is_readable('/workspace/tools/dev-guard.php') || hash_file('sha256', $guard) !== hash_file('sha256', '/workspace/tools/dev-guard.php')
        || has_filter('pre_wp_mail') === false || has_filter('pre_http_request') === false) { throw new RuntimeException('Installed local outbound fence required.'); }
    $emit = static function (array $result): void { echo 'WOOSHOP_RECOVERY ' . wp_json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL; };
    $rows = static function (string $sql) use ($wpdb): array {
        $result = $wpdb->get_results($sql, ARRAY_A);
        if (!is_array($result) || $wpdb->last_error !== '') { throw new RuntimeException('A guarded recovery read failed.'); }
        return $result;
    };
    $tableInfo = $rows($wpdb->prepare('SELECT TABLE_NAME,TABLE_TYPE,ENGINE,DATA_LENGTH,INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=%s ORDER BY TABLE_NAME', 'wooshop_package_check'));
    if (!$tableInfo || count($tableInfo) > 200) { throw new RuntimeException('Recovery table count exceeds bounded local scope.'); }
    $estimatedBytes = 0;
    foreach ($tableInfo as $table) {
        if ($table['TABLE_TYPE'] !== 'BASE TABLE' || strtoupper((string) $table['ENGINE']) !== 'INNODB' || !preg_match('/^[A-Za-z0-9_]+$/D', $table['TABLE_NAME']) || !str_starts_with($table['TABLE_NAME'], $wpdb->prefix)) { throw new RuntimeException('Only same-prefix InnoDB base tables are supported by this drill.'); }
        $estimatedBytes += (int) $table['DATA_LENGTH'] + (int) $table['INDEX_LENGTH'];
    }
    if ($estimatedBytes > 134217728) { throw new RuntimeException('Recovery dataset exceeds the 128 MiB local bound.'); }
    foreach (['TRIGGERS' => 'TRIGGER_SCHEMA', 'ROUTINES' => 'ROUTINE_SCHEMA', 'EVENTS' => 'EVENT_SCHEMA'] as $table => $column) {
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM information_schema.{$table} WHERE {$column}=%s", 'wooshop_package_check'));
        if ($count === null || (int) $count !== 0) { throw new RuntimeException('This drill refuses database triggers, routines or events.'); }
    }
    $active = $wpdb->get_var("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB=DATABASE() AND ID<>CONNECTION_ID() AND COMMAND<>'Sleep'");
    if ($active === null || (int) $active !== 0) { throw new RuntimeException('Another database writer/reader is active; coordinated idle hold required.'); }
    $jobs = $wpdb->prefix . 'wooshop_jobs';
    if (!in_array($jobs, array_column($tableInfo, 'TABLE_NAME'), true)) { throw new RuntimeException('Installed plugin tables required; helper never repairs schema.'); }
    if ((int) $wpdb->get_var("SELECT COUNT(*) FROM `{$jobs}` WHERE state='running'") !== 0) { throw new RuntimeException('A running plugin job prevents this recovery drill.'); }
    if ($mode === 'preflight') { $emit(['scope' => 'package-check-only', 'database' => 'wooshop_package_check', 'url' => 'http://localhost:18882', 'local_fixture' => true, 'outbound_fence_present' => true, 'table_count' => count($tableInfo), 'estimated_database_bytes' => $estimatedBytes]); return; }
    $upgrading = 0;
    if (!is_file(ABSPATH . '.maintenance') || is_link(ABSPATH . '.maintenance')) { throw new RuntimeException('Native maintenance hold required.'); }
    include ABSPATH . '.maintenance';
    if (!is_numeric($upgrading) || time() - (int) $upgrading >= 600) { throw new RuntimeException('Native maintenance hold is inactive.'); }
    $marker = 'wooshop_recovery_probe_' . $run; $markerValue = hash('sha256', 'wooshop-package-recovery:' . $run);
    if ($mode === 'marker') {
        if (!add_option($marker, $markerValue, '', false) || get_option($marker) !== $markerValue) { throw new RuntimeException('Unique post-backup marker could not be verified.'); }
        $emit(['post_backup_marker_present' => true]); return;
    }
    if ($mode === 'marker-absent') {
        $absent = new stdClass();
        if (get_option($marker, $absent) !== $absent) { throw new RuntimeException('Restore did not remove the unique post-backup marker.'); }
        $emit(['post_backup_marker_absent' => true]); return;
    }
    $normalize = static function ($value) use (&$normalize) {
        if ($value instanceof DateTimeInterface) { return $value->format('Y-m-d\TH:i:s.uP'); }
        if ($value instanceof WC_Meta_Data) { return $normalize($value->get_data()); }
        if ($value instanceof WC_Order_Item) { return $normalize($value->get_data()); }
        if (is_object($value)) { throw new RuntimeException('Unsupported native object in bounded recovery snapshot.'); }
        if (is_array($value)) { if (!array_is_list($value)) { ksort($value, SORT_STRING); } foreach ($value as &$entry) { $entry = $normalize($entry); } unset($entry); }
        return $value;
    };
    $digest = static fn ($value): string => hash('sha256', wp_json_encode($normalize($value), JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
    $orderIds = wc_get_orders(['type' => ['shop_order', 'shop_order_refund'], 'status' => array_keys(wc_get_order_statuses()), 'limit' => 501, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC']);
    if (count($orderIds) > 500) { throw new RuntimeException('Native order count exceeds this drill limit.'); }
    sort($orderIds, SORT_NUMERIC); $orders = []; $totals = []; $items = [];
    foreach ($orderIds as $id) {
        $order = wc_get_order($id); if (!$order) { throw new RuntimeException('Native order identity unavailable.'); }
        $orders[(string) $id] = $order->get_data();
        $totals[(string) $id] = ['type' => $order->get_type(), 'status' => $order->get_status(), 'currency' => $order->get_currency(), 'total' => $order->get_total(), 'parent_id' => $order->get_parent_id()];
        $items[(string) $id] = [];
        foreach ($order->get_items(['line_item', 'fee', 'shipping', 'tax', 'coupon']) as $itemId => $item) { $items[(string) $id][(string) $itemId] = $item->get_data(); }
        ksort($items[(string) $id], SORT_NUMERIC);
    }
    $tables = []; $totalRows = 0; $markerOptionHashes = []; $markerRows = 0; $optionsSchema = null;
    foreach ($tableInfo as $table) {
        $name = $table['TABLE_NAME']; $hashes = [];
        $indexes = $rows("SHOW INDEX FROM `{$name}` WHERE Key_name='PRIMARY'");
        usort($indexes, static fn (array $a, array $b): int => (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index']);
        if (!$indexes) { throw new RuntimeException('This bounded drill requires a stable primary key on every table.'); }
        $primary = [];
        foreach ($indexes as $index) { if (!preg_match('/^[A-Za-z0-9_]+$/D', $index['Column_name'])) { throw new RuntimeException('Unsupported primary key identifier.'); } $primary[] = '`' . $index['Column_name'] . '`'; }
        $orderBy = implode(',', $primary);
        for ($offset = 0; ; $offset += 500) {
            $batch = $rows("SELECT * FROM `{$name}` ORDER BY {$orderBy} LIMIT 500 OFFSET {$offset}");
            foreach ($batch as $row) {
                ksort($row, SORT_STRING); $rowHash = hash('sha256', serialize($row)); $hashes[] = $rowHash;
                if ($name === $wpdb->options) {
                    if ($row['option_name'] === $marker) { if ($row['option_value'] !== $markerValue) { throw new RuntimeException('The diagnostic marker value is not owned by this run.'); } $markerRows++; }
                    else { $markerOptionHashes[] = $rowHash; }
                }
                if (++$totalRows > 200000) { throw new RuntimeException('Recovery row count exceeds this drill limit.'); }
            }
            if (count($batch) < 500) { break; }
        }
        sort($hashes, SORT_STRING);
        $schema = $rows("SHOW CREATE TABLE `{$name}`");
        if ($name === $wpdb->options) { $optionsSchema = $schema[0]['Create Table'] ?? null; }
        $tables[$name] = ['rows' => count($hashes), 'rows_sha256' => hash('sha256', implode('', $hashes)), 'schema_sha256' => $digest($schema[0])];
    }
    $settings = $rows($wpdb->prepare("SELECT option_name,option_value,autoload FROM `{$wpdb->options}` WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name IN ('home','siteurl','active_plugins','template','stylesheet','blog_public','permalink_structure') ORDER BY option_name", $wpdb->esc_like('wooshop_') . '%', $wpdb->esc_like('woocommerce_') . '%', $wpdb->esc_like('theme_mods_') . '%'));
    $ledger = [];
    foreach (['journals', 'journal_entries', 'inventory_movements', 'allocations', 'entities'] as $suffix) { $name = $wpdb->prefix . 'wooshop_' . $suffix; if (!isset($tables[$name])) { throw new RuntimeException('A required extension invariant table is absent.'); } $ledger[$name] = $tables[$name]; }
    sort($markerOptionHashes, SORT_STRING);
    $settingsWithoutMarker = array_values(array_filter($settings, static fn (array $row): bool => $row['option_name'] !== $marker));
    $emit(['database' => 'wooshop_package_check', 'table_count' => count($tables), 'total_rows' => $totalRows, 'estimated_database_bytes' => $estimatedBytes,
        'tables' => $tables, 'database_rows_and_schema_sha256' => $digest($tables),
        'native_orders' => ['count' => count($orderIds), 'ids' => array_map('strval', $orderIds), 'data_sha256' => $digest($orders), 'totals_sha256' => $digest($totals), 'item_snapshots_sha256' => $digest($items)],
        'ledger_and_extension_sha256' => $digest($ledger), 'settings_sha256' => hash('sha256', serialize($settings)), 'settings_option_count' => count($settings),
        // Diagnostic projection only: full rows/schema/settings above remain the final acceptance invariant.
        'own_marker_diagnostic' => ['option_rows' => $markerRows, 'other_option_rows' => count($markerOptionHashes), 'other_option_rows_sha256' => hash('sha256', implode('', $markerOptionHashes)), 'settings_without_marker_sha256' => hash('sha256', serialize($settingsWithoutMarker)), 'options_schema' => $optionsSchema]]);
})($args ?? []);
