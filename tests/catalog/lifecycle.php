<?php
/** Local no-order integration: actual registry transitions, services, internal REST and native shortcodes. */
if (!defined('WP_CLI') || !WP_CLI || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || !str_contains(home_url('/'), 'localhost:1888')) { throw new RuntimeException('Isolated local fixture store required.'); }
if (!defined('WP_PLUGIN_DIR') || !in_array('wooshop-commerce/wooshop-commerce.php', (array) get_option('active_plugins', []), true) || !is_readable(WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php') || !class_exists(\WooShop\Commerce\Kernel\Plugin::class, false) || !\WooShop\Commerce\Kernel\Plugin::instance()) { throw new RuntimeException('The installed WooShop Commerce plugin must already be active and bootstrapped.'); }
require_once WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php';
if (!apply_filters('wooshop_commerce_catalog_services', [])) { throw new RuntimeException('The active installed plugin did not register its catalog services.'); }

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Kernel\Plugin;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Catalog\Input;

final class CatalogLifecycleRollback extends RuntimeException {}
$lifecycleDb = new Database($GLOBALS['wpdb']); $lifecycleStore = new EntityStore($lifecycleDb);
$services = apply_filters('wooshop_commerce_catalog_services', []); $registry = Plugin::instance()->modules();
$checks = 0;
$same = static function ($actual, $expected, string $case) use (&$checks): void { $checks++; if ($actual !== $expected) { throw new RuntimeException($case . ': ' . wp_json_encode(['actual' => $actual, 'expected' => $expected])); } };
$denied = static function (callable $call, string $code = 'module_unavailable') use ($same): void { try { $call(); } catch (DomainError $error) { $same($error->errorCode, $code, 'expected service denial'); return; } throw new RuntimeException('Missing denial ' . $code); };
$transition = static function (string $id, string $target) use ($registry): array { $current = $registry->all()[$id]; return $registry->transition($id, $target, $current['version'], 'local.catalog.lifecycle'); };
$rest = static function (string $method, string $path, ?array $body = null, ?int $version = null) use ($services): WP_REST_Response {
    $request = new WP_REST_Request($method, '/commerce/v1' . $path);
    $request->set_header(get_current_user_id() ? 'X-WP-Nonce' : 'X-Commerce-CSRF', get_current_user_id() ? wp_create_nonce('wp_rest') : $services['session']->csrf());
    $request->set_header('Idempotency-Key', wp_generate_uuid4());
    if ($version !== null) { $request->set_header('If-Match', '"' . $version . '"'); }
    if ($body !== null) { $request->set_header('Content-Type', 'application/json'); $request->set_body(wp_json_encode($body)); }
    return rest_get_server()->dispatch($request);
};
$unavailableUi = static function (string $shortcode) use ($same): void {
    wp_dequeue_style('wooshop-catalog'); wp_dequeue_script('wooshop-catalog');
    $html = do_shortcode('[' . $shortcode . ']');
    $same(str_contains($html, esc_html__('This feature is currently unavailable. Existing saved data is retained.', 'wooshop-commerce')), true, 'disabled UI translated status');
    $same(str_contains($html, '<form'), false, 'disabled UI no creation form');
    $same(wp_style_is('wooshop-catalog', 'enqueued'), false, 'disabled UI no style enqueue');
    $same(wp_script_is('wooshop-catalog', 'enqueued'), $shortcode === 'commerce_history', 'only disabled history enqueues necessary local cleanup');
    if ($shortcode === 'commerce_history') { $same(str_contains($html, 'data-module-read-enabled="0"'), true, 'disabled history necessary cleanup marker'); }
};
try {
    $lifecycleDb->transaction(function () use ($services, $registry, $lifecycleStore, $same, $denied, $transition, $rest, $unavailableUi): void {
        $products = wc_get_products(['status' => 'publish', 'limit' => 2, 'return' => 'ids', 'type' => 'simple']);
        if (count($products) < 2) { throw new RuntimeException('Two seeded public native products required.'); }
        [$a, $b] = array_map('strval', $products);
        $tag = bin2hex(random_bytes(6));
        $customerId = wp_insert_user(['user_login' => 'lifecycle_' . $tag, 'user_email' => 'lifecycle-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        $foreignId = wp_insert_user(['user_login' => 'lifecycle_foreign_' . $tag, 'user_email' => 'lifecycle-foreign-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        if (is_wp_error($customerId) || is_wp_error($foreignId)) { throw new RuntimeException('Synthetic users failed.'); }
        wp_set_current_user($customerId); $services['session']->start();
        foreach (['MOD-U003', 'MOD-U004', 'MOD-U005'] as $id) { $transition($id, 'enabled'); }
        $list = $services['customer']->wishlist();
        $saveKey = wp_generate_uuid4(); $saveVersion = $list['version'];
        $list = $services['customer']->saveItem(['product_id' => $a], $saveVersion, $saveKey);
        $list = $services['customer']->saveItem(['product_id' => $b], $list['version'], wp_generate_uuid4());
        foreach (['disabled_new_operations', 'draining', 'disabled'] as $state) {
            $before = $services['customer']->wishlist(); $transition('MOD-U003', $state);
            $denied(fn () => $services['customer']->saveItem(['product_id' => $a], $before['version'], wp_generate_uuid4()));
            $denied(fn () => $services['customer']->saveItem(['product_id' => $a], $saveVersion, $saveKey));
            $denied(fn () => $services['customer']->mergeWishlist($before['version'], wp_generate_uuid4()));
            $same($rest('POST', '/wishlists/me/items', ['product_id' => $a], $before['version'])->get_status(), 503, 'wishlist API new denied');
            if ($state === 'disabled') {
                $denied(fn () => $services['customer']->wishlist());
                $same($rest('GET', '/wishlists/me')->get_status(), 503, 'disabled own API read denied');
                $unavailableUi('commerce_wishlist');
                wp_set_current_user($foreignId);
                $denied(fn () => $services['customer']->removeItem($a, $before['version'], wp_generate_uuid4()), 'stale_revision');
                wp_set_current_user($customerId);
                $same($rest('DELETE', '/wishlists/me/items/' . $a, null, $before['version'])->get_status(), 200, 'disabled own cleanup API allowed');
            } else {
                $same($services['customer']->wishlist(), $before, 'draining list and version retained');
                $same($rest('GET', '/wishlists/me')->get_status(), 200, 'draining own API read allowed');
                $html = do_shortcode('[commerce_wishlist]');
                $same(str_contains($html, 'value="wishlist_add"'), false, 'draining wishlist no add form');
                $same(str_contains($html, 'value="wishlist_merge"'), false, 'draining wishlist no merge form');
                $same(str_contains($html, 'value="wishlist_remove"'), true, 'draining wishlist cleanup form');
            }
            $transition('MOD-U003', 'enabled'); $after = $services['customer']->wishlist();
            $same($after['id'], $before['id'], 'reenable list identity retained');
            $same($after['version'], $before['version'] + ($state === 'disabled' ? 1 : 0), 'transition does not mutate saved list revision');
            $same(array_column($after['items'], 'product_id'), $state === 'disabled' ? [$b] : [$a, $b], 'reenable retained selections');
        }
        $compareKey = wp_generate_uuid4(); $services['customer']->compare(['product_ids' => [$a, $b]], $compareKey);
        foreach (['disabled_new_operations', 'draining', 'disabled'] as $state) {
            $before = $services['customer']->comparisonSelection(); $transition('MOD-U004', $state);
            $denied(fn () => $services['customer']->compare(['product_ids' => [$b, $a]], wp_generate_uuid4()));
            $denied(fn () => $services['customer']->compare(['product_ids' => [$a, $b]], $compareKey));
            $same($rest('POST', '/comparisons', ['product_ids' => [$b, $a]])->get_status(), 503, 'comparison API creation denied');
            if ($state === 'disabled') {
                $denied(fn () => $services['customer']->savedComparison());
                $denied(fn () => $services['customer']->comparison([$a, $b]));
                $unavailableUi('commerce_compare');
            } else {
                $same($services['customer']->comparisonSelection(), $before, 'draining comparison and revision retained');
                $same(count($services['customer']->savedComparison()['products']), 2, 'draining comparison readable');
                $html = do_shortcode('[commerce_compare]');
                $same(str_contains($html, 'value="comparison"'), false, 'draining comparison no create form');
                $same(str_contains($html, 'value="comparison_clear"'), true, 'draining comparison clear form');
            }
            $transition('MOD-U004', 'enabled');
            $same($services['customer']->comparisonSelection(), $before, 'reenable comparison data and revision retained');
        }
        $selection = $services['customer']->comparisonSelection(); $transition('MOD-U004', 'disabled');
        wp_set_current_user($foreignId); $denied(fn () => $services['customer']->clearComparison($selection['version'], wp_generate_uuid4()), 'not_found');
        wp_set_current_user($customerId); $services['customer']->clearComparison($selection['version'], wp_generate_uuid4());
        $transition('MOD-U004', 'enabled'); $same($services['customer']->savedComparison(), null, 'disabled comparison cleanup retained');
        wp_set_current_user(0);
        $preferences = $services['customer']->preferences();
        $preferences = $services['customer']->updatePreferences(['history_enabled' => true, 'consent_notice_version' => (int) get_option('wooshop_commerce_consent_notice_version', 1)], $preferences['version'], wp_generate_uuid4());
        foreach (['disabled_new_operations', 'draining', 'disabled'] as $state) {
            $history = $services['customer']->history('read', null, 0, '');
            $history = $services['customer']->history('record', $a, $history['deletion_revision'], wp_generate_uuid4());
            $transition('MOD-U005', $state);
            $denied(fn () => $services['customer']->history('record', $b, $history['deletion_revision'], wp_generate_uuid4()));
            if ($state === 'disabled') { $denied(fn () => $services['customer']->history('read', null, 0, '')); $unavailableUi('commerce_history'); }
            else {
                $same($services['customer']->history('read', null, 0, ''), $history, 'draining history retained');
                $html = do_shortcode('[commerce_history product_id="' . $b . '"]');
                $same(str_contains($html, 'data-record-enabled="0"'), true, 'draining local history no recording');
                $same(str_contains($html, 'data-history-clear'), true, 'draining local history clear retained');
            }
            $cleared = $services['customer']->history('clear', null, $history['deletion_revision'], wp_generate_uuid4());
            $same($cleared['items'], [], 'history cleanup while disabled');
            $transition('MOD-U005', 'enabled');
            $same($services['customer']->history('read', null, 0, ''), $cleared, 'history deletion preserved on reenable');
            $denied(fn () => $services['customer']->history('record', $a, $history['deletion_revision'], wp_generate_uuid4()), 'history_cleared');
        }
        $transition('MOD-U005', 'disabled');
        $preferences = $services['customer']->preferences();
        $withdrawn = $services['customer']->updatePreferences(['history_enabled' => false, 'consent_notice_version' => (int) get_option('wooshop_commerce_consent_notice_version', 1)], $preferences['version'], wp_generate_uuid4());
        $same($withdrawn['history_enabled'], false, 'withdrawal allowed while disabled');
        $same($rest('GET', '/preferences/me')->get_status(), 200, 'privacy API remains available');
        $same(str_contains(do_shortcode('[commerce_preferences]'), 'data-enabled="0"'), true, 'disabled privacy UI still clears own device');
        $denied(fn () => $services['customer']->updatePreferences(['history_enabled' => true, 'consent_notice_version' => (int) get_option('wooshop_commerce_consent_notice_version', 1)], $withdrawn['version'], wp_generate_uuid4()));
        $ownHistory = $lifecycleStore->get('recent_history', Input::stableId('recent_history:' . $services['session']->owner()));
        $same($ownHistory['data']['items'], [], 'withdrawal cleared persisted history');
        $transition('MOD-U005', 'enabled');
        $same($services['customer']->preferences()['history_enabled'], false, 'reenable cannot restore withdrawn consent');
        wp_set_current_user($customerId);
        foreach (['MOD-U003', 'MOD-U004', 'MOD-U005'] as $id) { $transition($id, 'disabled'); }
        $privacy = new \WooShop\Commerce\Customer\Privacy(new Database($GLOBALS['wpdb']), $lifecycleStore);
        $export = $privacy->export('lifecycle-' . $tag . '@example.invalid');
        $same(count($export['data']) > 0, true, 'native privacy export independent of module disable');
        $erasure = $privacy->erase('lifecycle-' . $tag . '@example.invalid');
        $same($erasure['items_removed'], true, 'native erasure independent of module disable');
        $same($lifecycleStore->get('wishlist', Input::stableId('wishlist:user:' . $customerId)), null, 'disabled owner wishlist erased');
        throw new CatalogLifecycleRollback('No-order lifecycle fixture rolled back.');
    });
} catch (CatalogLifecycleRollback $rollback) { echo 'Catalog lifecycle checks passed: ' . $checks . "; no orders; synthetic transaction rolled back.\n"; }
