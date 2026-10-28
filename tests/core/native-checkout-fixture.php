<?php
/**
 * Local-only native checkout assurance. Never shipped in either release package.
 * WP-CLI: eval-file /workspace/tests/core/native-checkout-fixture.php OP RUN [ORDER_ID]
 * setup changes only snapshotted local fixture options; restore is mandatory.
 */
(static function (array $arguments): void {
    if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'local'
        || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true
        || untrailingslashit(home_url()) !== 'http://localhost:18880' || !function_exists('WC')) {
        throw new RuntimeException('Refused: guarded loopback Outdoor WordPress fixture required.');
    }
    $operation = $arguments[0] ?? '';
    $run = $arguments[1] ?? '';
    if (!in_array($operation, ['preflight', 'setup', 'verify', 'restore'], true)
        || !preg_match('/^native-[a-z0-9-]{12,70}$/D', $run)) {
        throw new RuntimeException('Invalid bounded fixture operation or run identifier.');
    }
    $lockKey = 'wooshop_native_checkout_fixture_lock';
    $evidenceKey = 'wooshop_native_checkout_evidence_' . $run;
    $emit = static function (array $result): void {
        echo 'WOOSHOP_NATIVE_CHECKOUT ' . wp_json_encode($result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    };
    $snapshot = static function (array $keys): array {
        $absent = new stdClass();
        $values = [];
        foreach ($keys as $key) {
            $value = get_option($key, $absent);
            $values[$key] = ['exists' => $value !== $absent, 'value' => $value === $absent ? null : $value];
        }
        return $values;
    };
    $restore = static function (array $lock) use ($lockKey, $evidenceKey, $snapshot, $run): array {
        if (($lock['run'] ?? '') !== $run || empty($lock['options'])) {
            throw new RuntimeException('Option restoration refused: another run owns the fixture lock.');
        }
        foreach ($lock['options'] as $key => $record) {
            if ($record['exists']) {
                update_option($key, $record['value']); // Existing option autoload behavior is retained.
            } else {
                delete_option($key);
            }
        }
        $same = $snapshot(array_keys($lock['options'])) === $lock['options'];
        if (!$same) {
            throw new RuntimeException('Fixture options did not restore exactly; lock retained for recovery.');
        }
        $proof = ['result' => 'RESTORED', 'run' => $run, 'option_values_and_existence_match' => true,
            'restored_option_count' => count($lock['options']),
            'original_snapshot_sha256' => hash('sha256', serialize($lock['options']))];
        $evidence = get_option($evidenceKey, []);
        $evidence['restoration'] = $proof;
        update_option($evidenceKey, $evidence, false);
        delete_option($lockKey);
        return $proof;
    };

    // Restore must remain available even if a later mail-fence preflight fails.
    if ($operation === 'restore') {
        $lock = get_option($lockKey, []);
        if (!$lock) {
            $previous = get_option($evidenceKey, []);
            if (empty($previous['restoration']['option_values_and_existence_match'])) {
                throw new RuntimeException('No matching setup or completed restoration found.');
            }
            $emit($previous['restoration'] + ['already_restored' => true]);
            return;
        }
        $emit($restore($lock));
        return;
    }

    // Read the actual container mount, not its empty host-side bind target placeholder.
    $mountedGuard = WPMU_PLUGIN_DIR . '/wooshop-local-guard.php';
    $expectedGuard = '/workspace/tools/dev-guard.php';
    if (!is_readable($mountedGuard) || !is_readable($expectedGuard)
        || hash_file('sha256', $mountedGuard) !== hash_file('sha256', $expectedGuard)
        || has_filter('pre_wp_mail') === false || has_filter('pre_http_request') === false
        || apply_filters('pre_wp_mail', null, ['to' => 'probe@example.invalid', 'subject' => 'Local fixture transport probe', 'message' => 'No outbound delivery permitted.', 'headers' => [], 'attachments' => []]) !== false) {
        throw new RuntimeException('Actual runtime outbound transport fence required.');
    }
    if (wp_mail('probe@example.invalid', 'Synthetic local transport fence probe', 'This must be blocked locally.') !== false) {
        throw new RuntimeException('Local mail transport did not report blocked delivery.');
    }
    $profile = get_option('wooshop_fixture_profile', []);
    $productId = (int) ($profile['native_product_ids']['download'] ?? 0);
    $product = wc_get_product($productId);
    if (($profile['profile_key'] ?? '') !== 'outdoor' || !$product
        || !$product->get_meta('_wooshop_synthetic_fixture') || !$product->is_virtual()
        || !$product->is_downloadable() || !$product->is_purchasable() || !$product->is_in_stock()
        || count($product->get_downloads()) !== 1 || (float) $product->get_price() <= 0 || wc_tax_enabled()) {
        throw new RuntimeException('Unmodified positive-price virtual downloadable local fixture without tax required.');
    }
    $expected = ['product_id' => $productId, 'product_name' => $product->get_name(),
        'product_url' => $product->get_permalink(), 'checkout_url' => wc_get_checkout_url(),
        'currency' => get_woocommerce_currency(), 'decimals' => wc_get_price_decimals(),
        'total' => wc_format_decimal($product->get_price(), wc_get_price_decimals()),
        'email' => $run . '@example.invalid'];
    if ($operation === 'preflight') {
        $emit(['result' => 'PREFLIGHT', 'runtime_mail_blocked' => true, 'fixture' => $expected]);
        return;
    }
    if ($operation === 'setup') {
        $gateways = WC()->payment_gateways()->payment_gateways();
        if (!isset($gateways['bacs']) || !($gateways['bacs'] instanceof WC_Gateway_BACS)) {
            throw new RuntimeException('Installed native BACS gateway required.');
        }
        $keys = ['woocommerce_bacs_accounts', 'woocommerce_gateway_order', 'woocommerce_enable_guest_checkout'];
        foreach ($gateways as $gateway) {
            // Do not reconfigure an unrelated provider, even in this isolated test.
            if (!in_array($gateway->id, ['bacs', 'cod', 'cheque'], true)) {
                if ($gateway->enabled === 'yes') {
                    throw new RuntimeException('An additional enabled gateway requires explicit fixture review.');
                }
                continue;
            }
            $keys[] = $gateway->get_option_key();
        }
        $lock = ['run' => $run, 'started_at' => time(), 'fixture' => $expected, 'options' => $snapshot(array_values(array_unique($keys)))];
        if (!add_option($lockKey, $lock, '', false)) {
            throw new RuntimeException('Another checkout fixture owns the option snapshot; restore it first.');
        }
        try {
            foreach ($gateways as $gateway) {
                if (!in_array($gateway->id, ['bacs', 'cod', 'cheque'], true)) { continue; }
                $settings = get_option($gateway->get_option_key(), []);
                if (!is_array($settings)) { throw new RuntimeException('Native gateway settings shape changed.'); }
                $settings['enabled'] = 'no';
                update_option($gateway->get_option_key(), $settings);
            }
            update_option('woocommerce_bacs_settings', ['enabled' => 'yes',
                'title' => 'Synthetic local BACS — no funds transferred',
                'description' => 'SYNTHETIC LOCAL TEST ONLY. No bank transfer or real purchase is authorized.',
                'instructions' => 'Synthetic unpaid order retained for native checkout assurance. Do not transfer funds.']);
            update_option('woocommerce_bacs_accounts', []);
            update_option('woocommerce_enable_guest_checkout', 'yes');
            $emit(['result' => 'SETUP', 'run' => $run, 'runtime_mail_blocked' => true, 'fixture' => $expected,
                'snapshotted_option_count' => count($lock['options'])]);
        } catch (Throwable $failure) {
            $restore($lock);
            throw $failure;
        }
        return;
    }

    $lock = get_option($lockKey, []);
    if (($lock['run'] ?? '') !== $run || ($lock['fixture'] ?? []) !== $expected) {
        throw new RuntimeException('Matching unchanged fixture setup required for order verification.');
    }
    $orderId = filter_var($arguments[2] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $order = $orderId ? wc_get_order($orderId) : false;
    if (!$order || $order->get_type() !== 'shop_order' || $order->get_billing_email() !== $expected['email']
        || $order->get_customer_id() !== 0 || !$order->get_date_created()
        || $order->get_date_created()->getTimestamp() < $lock['started_at'] - 5
        || $order->get_payment_method() !== 'bacs') {
        throw new RuntimeException('Order does not match this synthetic native browser checkout.');
    }
    $items = array_values($order->get_items('line_item'));
    if (count($items) !== 1 || $items[0]->get_product_id() !== $productId || $items[0]->get_quantity() !== 1) {
        throw new RuntimeException('Unexpected native order items; no annotation or financial mutation performed.');
    }
    $origin = ['fixture' => 'native-checkout', 'run_id' => $run, 'profile' => 'outdoor',
        'environment' => 'local', 'origin' => 'browser-native-store-api-checkout',
        'payment' => 'native-bacs-unpaid', 'annotation' => 'verified-after-browser-order-creation'];
    $previousOrigin = $order->get_meta('_wooshop_native_checkout_fixture', true);
    if ($previousOrigin !== '' && $previousOrigin !== $origin) {
        throw new RuntimeException('Conflicting fixture origin; native order retained unchanged.');
    }
    if ($previousOrigin === '') {
        // Native CRUD annotation after browser creation; never creates/pays an order.
        $order->update_meta_data('_wooshop_native_checkout_fixture', $origin);
        $order->update_meta_data('_wooshop_synthetic_fixture', true);
        $order->save();
        $order->add_order_note('SYNTHETIC LOCAL CHECKOUT EVIDENCE. Native BACS unpaid order. No real funds, fulfillment or product entitlement authorized.');
    }
    $order = wc_get_order($orderId);
    $permissions = WC_Data_Store::load('customer-download')->get_downloads(['order_id' => $orderId, 'return' => 'ids']);
    $checks = [
        'native_store_api_origin' => $order->get_created_via() === 'store-api',
        'on_hold' => $order->get_status() === 'on-hold',
        'unpaid' => !$order->is_paid() && $order->get_date_paid() === null && $order->get_transaction_id() === '',
        'currency' => $order->get_currency() === $expected['currency'],
        'total' => wc_format_decimal($order->get_total(), $expected['decimals']) === $expected['total'],
        'line_total' => wc_format_decimal($items[0]->get_total(), $expected['decimals']) === $expected['total'],
        'no_shipping_tax_discount_or_fee' => (float) $order->get_shipping_total() === 0.0 && (float) $order->get_total_tax() === 0.0
            && (float) $order->get_discount_total() === 0.0 && count($order->get_items('fee')) === 0 && count($order->get_items('shipping')) === 0,
        'download_not_permitted' => !$order->is_download_permitted(),
        'download_permissions_not_granted' => !$order->get_download_permissions_granted() && count($permissions) === 0,
        'no_available_download_items' => $order->get_downloadable_items() === [],
        'exact_fixture_metadata' => $order->get_meta('_wooshop_native_checkout_fixture', true) === $origin && (bool) $order->get_meta('_wooshop_synthetic_fixture', true),
    ];
    $proof = ['result' => in_array(false, $checks, true) ? 'FAIL' : 'VERIFIED', 'run' => $run,
        'order_id' => $orderId, 'status' => $order->get_status(), 'gateway' => $order->get_payment_method(),
        'created_via' => $order->get_created_via(), 'currency' => $order->get_currency(),
        'total' => wc_format_decimal($order->get_total(), $expected['decimals']),
        'items' => [['product_id' => $productId, 'quantity' => $items[0]->get_quantity(), 'total' => $items[0]->get_total()]],
        'download_permission_count' => count($permissions), 'fixture_origin' => $origin, 'checks' => $checks];
    update_option($evidenceKey, ['verification' => $proof], false);
    $emit($proof); // Safe summary omits keys, billing address, and download tokens.
    if (in_array(false, $checks, true)) {
        throw new RuntimeException('Native checkout assurance failed; synthetic order and safe evidence retained.');
    }
})(isset($args) && is_array($args) ? $args : []);
