<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Customer\CustomerService;
use WooShop\Commerce\Customer\Notifications;
use WooShop\Commerce\Customer\Session;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Merchandising\Loyalty;
use WooShop\Commerce\Merchandising\Incentives;

final class Views
{
    private Session $session; private Products $products; private Specifications $specs; private CustomerService $customer; private Community $community; private Notifications $notifications; private Search $search; private RateLimit $rate; private Loyalty $loyalty; private Incentives $incentives;
    public function __construct(Session $session, Products $products, Specifications $specs, CustomerService $customer, Community $community, Notifications $notifications, Search $search, RateLimit $rate, Loyalty $loyalty, Incentives $incentives)
    { $this->session = $session; $this->products = $products; $this->specs = $specs; $this->customer = $customer; $this->community = $community; $this->notifications = $notifications; $this->search = $search; $this->rate = $rate; $this->loyalty = $loyalty; $this->incentives = $incentives; }

    public function register(): void
    {
        foreach (['specs', 'wishlist', 'compare', 'questions', 'preferences', 'alerts', 'search', 'unsubscribe', 'history', 'rewards', 'incentives'] as $name) {
            add_shortcode('commerce_' . $name, function ($attributes = []) use ($name): string {
                $bufferLevel = ob_get_level();
                try {
                    if ($name === 'incentives' && !$this->incentives->hasRules()) { return ''; }
                    $module = ['wishlist' => 'MOD-U003', 'compare' => 'MOD-U004', 'history' => 'MOD-U005'][$name] ?? null;
                    if ($name === 'history' && !ModuleAccess::accepts('MOD-U005', 'read')) {
                        $this->session->start(); nocache_headers();
                        ob_start(); $this->historyRail([], $this->customer->preferences(), true); $cleanup = (string) ob_get_clean();
                        return '<p class="commerce-error" role="status">' . esc_html__('This feature is currently unavailable. Existing saved data is retained.', 'wooshop-commerce') . '</p>' . $cleanup;
                    }
                    if ($module !== null) { ModuleAccess::require($module, 'read'); }
                    if (in_array($name, ['wishlist', 'compare', 'preferences', 'alerts', 'questions', 'history', 'rewards', 'incentives'], true)) { $this->session->start(); nocache_headers(); }
                    wp_enqueue_style('wooshop-catalog', plugins_url('assets/catalog.css', dirname(__DIR__, 2) . '/wooshop-commerce.php'), [], '0.1.0');
                    ob_start(); echo '<section class="commerce-panel" aria-label="' . esc_attr(ucfirst($name)) . '">'; $this->notice();
                    $this->$name(is_array($attributes) ? $attributes : []);
                    echo '</section>'; return (string) ob_get_clean();
                } catch (DomainError $error) { while (ob_get_level() > $bufferLevel) { ob_end_clean(); } return '<p class="commerce-error" role="status">' . esc_html($error->getMessage()) . '</p>' . ($name === 'search' ? '<p><a href="' . esc_url(remove_query_arg(['cf', 'cp'])) . '">' . esc_html__('Clear filters and keep the search', 'wooshop-commerce') . '</a></p>' : ''); }
            });
        }
        add_action('wp', function (): void {
            global $post;
            if (!$post || !is_string($post->post_content)) { return; }
            foreach (['commerce_wishlist', 'commerce_compare', 'commerce_preferences', 'commerce_alerts', 'commerce_questions', 'commerce_history', 'commerce_rewards', 'commerce_incentives'] as $shortcode) {
                if (has_shortcode($post->post_content, $shortcode)) { $this->session->start(); nocache_headers(); break; }
            }
            if (has_shortcode($post->post_content, 'commerce_unsubscribe')) { nocache_headers(); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, follow'); }
        });
        add_action('admin_post_wooshop_customer', [$this, 'handle']);
        add_action('admin_post_nopriv_wooshop_customer', [$this, 'handle']);
        add_action('woocommerce_single_product_summary', function (): void { $this->productActions(); }, 35);
        add_action('woocommerce_after_single_product_summary', function (): void { echo do_shortcode('[commerce_history]'); }, 30);
        add_action('woocommerce_before_cart_totals', function (): void { echo do_shortcode('[commerce_incentives]'); });
        add_filter('woocommerce_product_tabs', function (array $tabs): array {
            $tabs['wooshop_specs'] = ['title' => __('Technical specifications', 'wooshop-commerce'), 'priority' => 25, 'callback' => function (): void { echo do_shortcode('[commerce_specs]'); }];
            $tabs['wooshop_questions'] = ['title' => __('Questions and answers', 'wooshop-commerce'), 'priority' => 35, 'callback' => function (): void { echo do_shortcode('[commerce_questions]'); }];
            return $tabs;
        });
        // Product action controls need an essential private shopping session before output starts.
        add_action('wp', function (): void { if ((function_exists('is_product') && is_product()) || ($this->incentives->hasRules() && function_exists('is_cart') && is_cart())) { $this->session->start(); nocache_headers(); } });
    }

    private function notice(): void
    {
        $code = isset($_GET['commerce_notice']) && is_string($_GET['commerce_notice']) ? sanitize_key(wp_unslash($_GET['commerce_notice'])) : '';
        if ($code === 'saved') { echo '<p class="commerce-success" role="status">' . esc_html__('Your change was saved.', 'wooshop-commerce') . '</p>'; }
        elseif ($code !== '') { echo '<p class="commerce-error" role="alert">' . esc_html__('The change could not be saved. Reload, check your selection and try again.', 'wooshop-commerce') . ' <code>' . esc_html(substr($code, 0, 64)) . '</code></p>'; }
    }

    private function begin(string $command, ?int $revision = null): void
    {
        echo '<form class="commerce-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        foreach (['action' => 'wooshop_customer', 'command' => $command, 'commerce_csrf' => $this->session->csrf(), 'idempotency' => wp_generate_uuid4()] + ($revision !== null ? ['revision' => (string) $revision] : []) as $name => $value) { echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">'; }
    }
    private function end(string $label): void { echo '<button type="submit">' . esc_html($label) . '</button></form>'; }
    private function hidden(string $key, string $value): void { echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">'; }
    private function productId(array $attributes): ?string { $id = $attributes['product_id'] ?? (function_exists('is_product') && is_product() ? (string) get_the_ID() : null); return $id === null ? null : Input::id((string) $id); }

    public function specs(array $attributes): void
    {
        $id = $this->productId($attributes);
        if ($id === null) { echo '<p>' . esc_html__('Select a product to view technical specifications.', 'wooshop-commerce') . '</p>'; return; }
        try { $spec = $this->specs->read($id); }
        catch (DomainError $error) { if ($error->status !== 404) { throw $error; } echo '<p>' . esc_html__('Technical specifications have not been supplied.', 'wooshop-commerce') . '</p>'; return; }
        $fields = $this->specs->fields($spec['schema_id']); $values = array_column($spec['values'], null, 'key');
        echo '<table class="commerce-table"><caption>' . esc_html__('Approved technical specifications', 'wooshop-commerce') . '</caption><tbody>';
        foreach ($fields as $field) {
            $value = $values[$field['key']] ?? null;
            $display = $value === null || $value['value'] === null ? __('Not supplied', 'wooshop-commerce') : (is_bool($value['value']) ? ($value['value'] ? __('Yes', 'wooshop-commerce') : __('No', 'wooshop-commerce')) : (string) $value['value']) . ($value['unit'] ? ' ' . $value['unit'] : '');
            echo '<tr><th scope="row">' . esc_html($field['label']) . '</th><td><bdi>' . esc_html($display) . '</bdi></td></tr>';
        }
        echo '</tbody></table>';
    }

    public function wishlist(array $attributes): void
    {
        $list = $this->customer->wishlist();
        echo '<h2>' . esc_html__('Saved products', 'wooshop-commerce') . '</h2><p>' . esc_html__('Saved products do not reserve stock or lock a price. Guest lists are private to this browser for up to 30 days.', 'wooshop-commerce') . '</p>';
        if (!$list['items']) { echo '<p>' . esc_html__('Your saved list is empty.', 'wooshop-commerce') . '</p>'; }
        echo '<ul class="commerce-list">';
        foreach ($list['items'] as $item) {
            echo '<li>';
            try { $product = $this->products->get($item['variation_id'] ?? $item['product_id']); echo '<a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a> ' . wp_kses_post($product->get_price_html()); }
            catch (DomainError $error) { if ($error->status !== 404) { throw $error; } echo esc_html__('This product is no longer available.', 'wooshop-commerce'); }
            $this->begin('wishlist_remove', $list['version']); $this->hidden('product_id', $item['product_id']); $this->end(__('Remove', 'wooshop-commerce')); echo '</li>';
        }
        echo '</ul>';
        if (ModuleAccess::accepts('MOD-U003', 'new')) {
            $this->begin('wishlist_add', $list['version']); echo '<label>' . esc_html__('Product ID', 'wooshop-commerce') . ' <input name="product_id" inputmode="numeric" pattern="[1-9][0-9]*" required></label>'; $this->end(__('Save product', 'wooshop-commerce'));
            if (get_current_user_id() > 0) { $this->begin('wishlist_merge', $list['version']); $this->end(__('Merge this browser’s guest list', 'wooshop-commerce')); }
        } else { $this->newActivityUnavailable(); }
    }

    private function productActions(): void
    {
        global $product;
        if (!$product || $product->get_status() !== 'publish') { return; }
        try {
            ModuleAccess::require('MOD-U003', 'new');
            $list = $this->customer->wishlist(); $this->begin('wishlist_add', $list['version']);
            $this->hidden('product_id', (string) $product->get_id());
            $saved = false; foreach ($list['items'] as $item) { if ($item['product_id'] === (string) $product->get_id()) { $saved = true; break; } }
            echo '<button type="submit" aria-pressed="' . ($saved ? 'true' : 'false') . '">' . esc_html($saved ? __('Saved', 'wooshop-commerce') : __('Save product', 'wooshop-commerce')) . '</button></form>';
        } catch (DomainError $error) { echo '<p>' . esc_html($error->getMessage()) . '</p>'; }
    }

    public function compare(array $attributes): void
    {
        ModuleAccess::require('MOD-U004', 'read');
        echo '<h2>' . esc_html__('Compare products', 'wooshop-commerce') . '</h2>';
        if (ModuleAccess::accepts('MOD-U004', 'new')) { $this->begin('comparison'); echo '<label>' . esc_html__('Two to four product IDs, separated by commas', 'wooshop-commerce') . ' <input name="product_ids" required inputmode="numeric" pattern="[0-9, ]+"></label>'; $this->end(__('Compare', 'wooshop-commerce')); }
        else { $this->newActivityUnavailable(); }
        $comparison = $this->customer->savedComparison(); if (!$comparison) { return; }
        $selection = $this->customer->comparisonSelection();
        $this->begin('comparison_clear', $selection['version']); $this->end(__('Clear saved comparison', 'wooshop-commerce'));
        echo '<div class="commerce-table-scroll" tabindex="0" role="region" aria-label="' . esc_attr__('Product comparison', 'wooshop-commerce') . '"><table class="commerce-table"><caption>' . esc_html__('Technical facts; missing values remain unknown', 'wooshop-commerce') . '</caption><thead><tr><th scope="col">' . esc_html__('Specification', 'wooshop-commerce') . '</th>';
        foreach ($comparison['products'] as $column) { $product = $this->products->get($column['product_id']); echo '<th scope="col"><a href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->get_name()) . '</a></th>'; }
        echo '</tr></thead><tbody>';
        foreach ($comparison['products'][0]['values'] as $index => $row) { echo '<tr><th scope="row">' . esc_html($row['field_key']) . '</th>'; foreach ($comparison['products'] as $column) { echo '<td><bdi>' . esc_html($column['values'][$index]['display_value']) . '</bdi></td>'; } echo '</tr>'; }
        echo '</tbody></table></div>';
    }

    public function questions(array $attributes): void
    {
        $product = $this->productId($attributes);
        echo '<h2>' . esc_html__('Product questions', 'wooshop-commerce') . '</h2>';
        $questions = $this->community->questions(20, null, $product);
        foreach ($questions['items'] as $question) { echo '<article class="commerce-question"><h3>' . esc_html($question['text']) . '</h3>'; $answer = $this->community->publishedAnswer($question['id']); echo '<p>' . esc_html($answer ?? __('Awaiting an approved answer.', 'wooshop-commerce')) . '</p></article>'; }
        if (!$questions['items']) { echo '<p>' . esc_html__('No published questions yet.', 'wooshop-commerce') . '</p>'; }
        if (get_current_user_id() < 1) { echo '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Sign in to ask a question', 'wooshop-commerce') . '</a></p>'; return; }
        $this->begin('question');
        if ($product !== null) { $this->hidden('product_id', $product); }
        else { echo '<label>' . esc_html__('Product ID', 'wooshop-commerce') . ' <input name="product_id" inputmode="numeric" required></label>'; }
        echo '<label>' . esc_html__('Question (10–2000 characters)', 'wooshop-commerce') . ' <textarea name="text" minlength="10" maxlength="2000" required></textarea></label><p>' . esc_html__('Questions are reviewed before publication. Do not include personal or order information.', 'wooshop-commerce') . '</p>';
        $this->end(__('Submit for review', 'wooshop-commerce'));
    }

    public function preferences(array $attributes): void
    {
        $preferences = $this->customer->preferences();
        echo '<h2>' . esc_html__('Privacy and message preferences', 'wooshop-commerce') . '</h2><p>' . esc_html__('Optional preferences are off until you choose them. Browsing history stays on this device unless account synchronization is explicitly enabled by the store.', 'wooshop-commerce') . '</p>';
        $this->begin('preferences', $preferences['version']); $this->hidden('consent_notice_version', (string) get_option('wooshop_commerce_consent_notice_version', 1));
        foreach (['marketing_email' => __('Optional email alerts and marketing', 'wooshop-commerce'), 'marketing_sms' => __('Optional SMS alerts and marketing', 'wooshop-commerce'), 'history_enabled' => __('Remember products viewed on this device', 'wooshop-commerce')] as $key => $label) { echo '<label class="commerce-check"><input type="checkbox" name="' . esc_attr($key) . '" value="1"' . checked($preferences[$key], true, false) . '> ' . esc_html($label) . '</label>'; }
        $this->end(__('Save preferences', 'wooshop-commerce'));
        $privacy = get_privacy_policy_url(); if ($privacy !== '') { echo '<p><a href="' . esc_url($privacy) . '">' . esc_html__('Read the privacy notice', 'wooshop-commerce') . '</a></p>'; }
        if (ModuleAccess::accepts('MOD-U005', 'read')) { $this->history([]); }
        elseif (!$preferences['history_enabled']) {
            // Consent withdrawal must still clear this owner's local device storage while the module is disabled.
            $this->historyRail([], $preferences, true);
        }
        else { echo '<p role="status">' . esc_html__('Browsing history is currently unavailable. You can still withdraw your consent above.', 'wooshop-commerce') . '</p>'; }
    }

    public function history(array $attributes): void
    {
        ModuleAccess::require('MOD-U005', 'read');
        $this->historyRail($attributes, $this->customer->preferences());
    }

    private function newActivityUnavailable(): void
    {
        echo '<p role="status">' . esc_html__('New activity for this feature is currently unavailable. Existing saved data is retained.', 'wooshop-commerce') . '</p>';
    }

    private function historyRail(array $attributes, array $preference, bool $unavailable = false): void
    {
        wp_enqueue_script('wooshop-catalog', plugins_url('assets/catalog.js', dirname(__DIR__, 2) . '/wooshop-commerce.php'), [], '0.1.0', true);
        $productId = $unavailable ? null : $this->productId($attributes); $product = null;
        if ($productId !== null) { $product = $this->products->get($productId); }
        $storageKey = hash_hmac('sha256', home_url('/') . ':' . $this->session->owner(), wp_salt('nonce'));
        echo '<div data-commerce-history' . ($unavailable ? ' hidden' : '') . ' data-key="' . esc_attr($storageKey) . '" data-enabled="' . ($preference['history_enabled'] ? '1' : '0') . '" data-consent-version="' . (int) $preference['consent_version'] . '" data-module-version="' . ModuleAccess::revision('MOD-U005') . '" data-module-read-enabled="' . ($unavailable ? '0' : '1') . '" data-record-enabled="' . (!$unavailable && ModuleAccess::accepts('MOD-U005', 'new') ? '1' : '0') . '" data-product="' . esc_attr($product ? wp_json_encode(['product_id' => $productId, 'name' => $product->get_name(), 'url' => $product->get_permalink()]) : '') . '" data-limit="' . Options::integer('recent.max_items', 20, 1, 50) . '" data-days="' . Options::integer('recent.expiry_days', 30, 1, 90) . '" data-cleared-message="' . esc_attr__('History cleared on this device.', 'wooshop-commerce') . '"><h2>' . esc_html__('Recently viewed on this device', 'wooshop-commerce') . '</h2><p data-history-empty>' . esc_html($preference['history_enabled'] ? __('No products have been remembered on this device.', 'wooshop-commerce') : __('Browsing history is off. Enable it in privacy preferences to remember products on this device.', 'wooshop-commerce')) . '</p><ol data-history-items></ol><button type="button" data-history-clear' . ($preference['history_enabled'] ? '' : ' hidden') . '>' . esc_html__('Clear history on this device', 'wooshop-commerce') . '</button><p data-history-status role="status"></p></div>';
    }

    public function alerts(array $attributes): void
    {
        echo '<h2>' . esc_html__('Product alerts', 'wooshop-commerce') . '</h2><p>' . esc_html__('Alert delivery is unavailable until a verified message provider is configured. Saved requests remain pending and do not reserve a product or price.', 'wooshop-commerce') . '</p>';
        if (get_current_user_id() < 1) { echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Sign in and verify your contact', 'wooshop-commerce') . '</a>'; return; }
        foreach ($this->notifications->ownAlerts() as $alert) {
            echo '<article><p>' . esc_html($alert['data']['product_id'] . ' · ' . $alert['data']['kind'] . ' · ' . $alert['data']['channel'] . ' · ' . $alert['data']['state']) . '</p>';
            $this->begin('alert_cancel', $alert['version']); $this->hidden('alert_id', $alert['id']); $this->end(__('Cancel alert', 'wooshop-commerce')); echo '</article>';
        }
        $this->begin('alert'); echo '<label>' . esc_html__('Product ID', 'wooshop-commerce') . ' <input name="product_id" inputmode="numeric" required></label><label>' . esc_html__('Channel', 'wooshop-commerce') . ' <select name="channel"><option value="email">' . esc_html__('Email', 'wooshop-commerce') . '</option><option value="sms">' . esc_html__('SMS', 'wooshop-commerce') . '</option></select></label>'; $this->hidden('kind', 'back_in_stock'); $this->end(__('Request stock alert', 'wooshop-commerce'));
    }

    public function unsubscribe(array $attributes): void
    {
        wp_enqueue_script('wooshop-catalog', plugins_url('assets/catalog.js', dirname(__DIR__, 2) . '/wooshop-commerce.php'), [], '0.1.0', true);
        echo '<h2>' . esc_html__('Stop optional messages', 'wooshop-commerce') . '</h2><p>' . esc_html__('Confirm to stop the optional purpose and channel in your link. Order and service notices are unaffected. Expired links can be renewed through store support.', 'wooshop-commerce') . '</p><form data-commerce-unsubscribe method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        $this->hidden('action', 'wooshop_customer'); $this->hidden('command', 'unsubscribe');
        echo '<label>' . esc_html__('Opt-out token', 'wooshop-commerce') . ' <input name="token" required minlength="32" maxlength="2048" autocomplete="off" spellcheck="false"></label><button type="submit">' . esc_html__('Confirm opt-out', 'wooshop-commerce') . '</button></form>';
    }

    public function rewards(array $attributes): void
    {
        echo '<h2>' . esc_html__('Rewards and points', 'wooshop-commerce') . '</h2>';
        if (get_current_user_id() < 1) { echo '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '">' . esc_html__('Sign in to view your own rewards', 'wooshop-commerce') . '</a></p>'; return; }
        $availability = $this->loyalty->availability(); $balance = $this->loyalty->balance();
        echo '<dl>'; foreach (['available' => __('Available points', 'wooshop-commerce'), 'pending' => __('Pending points', 'wooshop-commerce'), 'held' => __('Reserved points', 'wooshop-commerce'), 'redeemed' => __('Redeemed points', 'wooshop-commerce'), 'expired' => __('Expired points', 'wooshop-commerce')] as $key => $label) { echo '<dt>' . esc_html($label) . '</dt><dd><bdi>' . esc_html((string) $balance[$key]) . '</bdi></dd>'; } echo '</dl>';
        if ($balance['restricted']) { echo '<p role="status">' . esc_html__('Redemption is restricted while refunded earned points exceed your remaining balance. Points are not a cash debt.', 'wooshop-commerce') . '</p>'; }
        if ($availability['accepting_new']) { $this->begin('reward_reserve'); echo '<label>' . esc_html__('Points to reserve for a native coupon', 'wooshop-commerce') . ' <input name="points" type="number" min="1" max="1000000000" required></label>'; $this->end(__('Reserve points', 'wooshop-commerce')); }
        else { echo '<p>' . esc_html__('New earning and redemption are disabled pending the store’s approved program policy. Existing reward obligations remain visible.', 'wooshop-commerce') . '</p>'; }
        if ($availability['terms_uri']) { echo '<p><a href="' . esc_url($availability['terms_uri']) . '">' . esc_html__('Program terms', 'wooshop-commerce') . '</a></p>'; }
        foreach ($this->loyalty->redemptions() as $record) {
            echo '<article><p>' . esc_html($record['data']['points'] . ' · ' . $record['data']['state']) . '</p>';
            if ($record['data']['state'] === 'held') {
                if ($record['data']['coupon_id']) { $coupon = new \WC_Coupon((int) $record['data']['coupon_id']); if ($coupon->get_id()) { echo '<p>' . esc_html__('Enter this code in the native cart coupon field:', 'wooshop-commerce') . ' <code>' . esc_html($coupon->get_code()) . '</code></p>'; } }
                else { $this->begin('reward_issue'); $this->hidden('reservation_id', $record['id']); $this->end(__('Issue reserved coupon', 'wooshop-commerce')); }
                if ($record['data']['order_id'] === null) { $this->begin('reward_release'); $this->hidden('reservation_id', $record['id']); $this->end(__('Release reserved points', 'wooshop-commerce')); }
            }
            echo '</article>';
        }
    }

    public function incentives(array $attributes): void
    {
        if (!function_exists('WC') || !WC()->cart) { echo '<p>' . esc_html__('Your cart is unavailable. Open the native cart and retry.', 'wooshop-commerce') . '</p>'; return; }
        WC()->cart->calculate_totals(); $result = $this->incentives->evaluate();
        if (!$result['benefits']) { return; }
        echo '<h2>' . esc_html__('Available cart benefits', 'wooshop-commerce') . '</h2>';
        foreach ($result['benefits'] as $benefit) {
            echo '<article>';
            if ($benefit['eligible']) {
                echo '<p>' . esc_html($benefit['applied'] ? __('This benefit is applied through WooCommerce.', 'wooshop-commerce') : __('Your current cart qualifies for this reviewed benefit.', 'wooshop-commerce')) . '</p>';
                if (!$benefit['applied']) { $this->begin('incentive_apply', $benefit['version']); $this->hidden('rule_id', $benefit['rule_id']); $this->end(__('Apply native coupon benefit', 'wooshop-commerce')); }
            } elseif ($benefit['reason'] === 'threshold_not_met') {
                echo '<p>' . esc_html__('Add eligible items worth', 'wooshop-commerce') . ' ' . wp_kses_post(wc_price(Loyalty::major($benefit['remaining_threshold_minor'], $benefit['exponent']), ['currency' => $benefit['currency']])) . ' ' . esc_html__('to meet the threshold after other discounts, excluding tax and shipping.', 'wooshop-commerce') . '</p>';
            } else { echo '<p>' . esc_html__('This benefit is unavailable for the current cart or promotion period.', 'wooshop-commerce') . '</p>'; }
            echo '</article>';
        }
    }

    public function search(array $attributes): void
    {
        $query = isset($_GET['cq']) && is_string($_GET['cq']) ? wp_unslash($_GET['cq']) : '';
        $filters = isset($_GET['cf']) && is_array($_GET['cf']) ? wp_unslash($_GET['cf']) : [];
        $filters = array_filter($filters, static fn ($value): bool => $value !== '');
        $page = isset($_GET['cp']) && is_scalar($_GET['cp']) && ctype_digit((string) $_GET['cp']) ? (int) $_GET['cp'] : 1;
        $category = isset($attributes['category_id']) ? Input::id((string) $attributes['category_id']) : null;
        $result = $this->search->find($query, $filters, 20, $page, $category);
        echo '<form method="get" class="commerce-form"><label>' . esc_html__('Search products', 'wooshop-commerce') . ' <input type="search" name="cq" maxlength="120" value="' . esc_attr($query) . '"></label>';
        foreach ($result['facets'] as $key => $facet) { echo '<label>' . esc_html($facet['label'] . ($facet['unit'] ? ' (' . $facet['unit'] . ')' : '')) . '<input name="cf[' . esc_attr($key) . ']" value="' . esc_attr($filters[$key] ?? '') . '"></label>'; }
        echo '<button>' . esc_html__('Apply filters', 'wooshop-commerce') . '</button></form><p role="status">' . esc_html(sprintf(__('%d products. Native search is active; advanced index filters are reduced.', 'wooshop-commerce'), $result['total'])) . '</p><ul class="commerce-products">';
        foreach ($result['items'] as $item) { echo '<li><a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a><div>' . wp_kses_post($item['price_html']) . '</div><p>' . esc_html(str_replace('_', ' ', $item['capabilities']['purchase_flow'])) . '</p>'; if (!$item['capabilities']['supported']) { echo '<p>' . esc_html($item['capabilities']['reason']) . '</p>'; } echo '</li>'; }
        echo '</ul><nav aria-label="' . esc_attr__('Search result pages', 'wooshop-commerce') . '">';
        if ($page > 1) { echo '<a href="' . esc_url(add_query_arg('cp', $page - 1)) . '">' . esc_html__('Previous', 'wooshop-commerce') . '</a> '; }
        if ($page < $result['pages']) { echo '<a href="' . esc_url(add_query_arg('cp', $page + 1)) . '">' . esc_html__('Next', 'wooshop-commerce') . '</a>'; } echo '</nav>';
    }

    public function handle(): void
    {
        $code = 'saved';
        try {
            $this->rate->check('catalog.form.write', 20);
            $post = wp_unslash($_POST);
            Input::keys($post, ['action', 'command', 'commerce_csrf', 'idempotency', 'revision', 'product_id', 'variation_id', 'product_ids', 'text', 'marketing_email', 'marketing_sms', 'history_enabled', 'consent_notice_version', 'channel', 'kind', 'alert_id', 'token', 'points', 'reservation_id', 'rule_id'], ['action', 'command']);
            if ($post['command'] !== 'unsubscribe') { $this->session->authorize(isset($post['commerce_csrf']) && is_string($post['commerce_csrf']) ? $post['commerce_csrf'] : null, true); }
            $revision = isset($post['revision']) && is_string($post['revision']) && ctype_digit($post['revision']) ? (int) $post['revision'] : 0;
            $key = $post['command'] === 'unsubscribe' ? '' : Input::text($post['idempotency'] ?? null, 16, 128);
            switch ($post['command']) {
                case 'wishlist_add': $this->customer->saveItem(array_intersect_key($post, array_flip(['product_id', 'variation_id'])), $revision, $key); break;
                case 'wishlist_remove': $this->customer->removeItem(Input::id($post['product_id'] ?? null), $revision, $key); break;
                case 'wishlist_merge': $this->customer->mergeWishlist($revision, $key); break;
                case 'comparison': $ids = explode(',', Input::text($post['product_ids'] ?? null, 3, 100)); $this->customer->compare(['product_ids' => array_map('trim', $ids)], $key); break;
                case 'comparison_clear': $this->customer->clearComparison($revision, $key); break;
                case 'question': $this->community->question(['product_id' => $post['product_id'] ?? null, 'text' => $post['text'] ?? null], $key); break;
                case 'preferences':
                    $input = ['consent_notice_version' => isset($post['consent_notice_version']) && ctype_digit((string) $post['consent_notice_version']) ? (int) $post['consent_notice_version'] : 0];
                    foreach (['marketing_email', 'marketing_sms', 'history_enabled'] as $flag) { $input[$flag] = isset($post[$flag]) && $post[$flag] === '1'; }
                    $this->customer->updatePreferences($input, $revision, $key); break;
                case 'alert': $this->notifications->subscribe(['product_id' => $post['product_id'] ?? null, 'channel' => $post['channel'] ?? null, 'kind' => $post['kind'] ?? null], $key); break;
                case 'alert_cancel': $this->notifications->cancel(Input::uuid($post['alert_id'] ?? null), $revision); break;
                case 'unsubscribe': $this->notifications->unsubscribe(['token' => $post['token'] ?? null]); break;
                case 'reward_reserve': if (!isset($post['points']) || !is_string($post['points']) || !ctype_digit($post['points']) || strlen($post['points']) > 10) { throw new DomainError('invalid_points', 'Choose a valid positive number of points.', 422); } $reservation = $this->loyalty->reserve((int) $post['points'], $key); $this->loyalty->issueCoupon($reservation['id']); break;
                case 'reward_issue': $this->loyalty->issueCoupon(Input::uuid($post['reservation_id'] ?? null)); break;
                case 'reward_release': $this->loyalty->release(Input::uuid($post['reservation_id'] ?? null)); break;
                case 'incentive_apply': $this->incentives->apply(Input::uuid($post['rule_id'] ?? null), $revision); break;
                default: throw new DomainError('invalid_action', 'Unsupported shopping action.', 400);
            }
        } catch (DomainError $error) { $code = $error->errorCode; }
        catch (\Throwable $error) { $code = 'storage_unavailable'; }
        $url = wp_validate_redirect(wp_get_referer() ?: home_url('/'), home_url('/'));
        wp_safe_redirect(add_query_arg('commerce_notice', $code, $url), 303); exit;
    }
}
