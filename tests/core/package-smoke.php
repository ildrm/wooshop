<?php
// WP-CLI eval-file prepends statements, so strict_types cannot be declared in this harness.
// Executes installed packages only; no /workspace/packages autoloader or source dependency.
use WooShop\Commerce\Kernel\Plugin;
use WooShop\Commerce\Marketplace\OrderSnapshots;
use WooShop\Commerce\Marketplace\Records;
use WooShop\Commerce\Finance\Money;

if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES) { throw new RuntimeException('Refusing assurance fixtures outside authorized local isolation.'); }
$siteLabels = [18880 => 'outdoor', 18881 => 'smart-home', 18882 => 'package-check'];
$port = (int) wp_parse_url(home_url('/'), PHP_URL_PORT);
if (!isset($siteLabels[$port]) || !in_array(wp_parse_url(home_url('/'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)) { throw new RuntimeException('Unknown local assurance target.'); }
$site = $siteLabels[$port];
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$kernel = Plugin::instance();
$assert($kernel !== null, 'Installed companion plugin must boot successfully.');
require_once ABSPATH.'wp-admin/includes/plugin.php';
$pluginFile = WP_PLUGIN_DIR.'/wooshop-commerce/wooshop-commerce.php';
$pluginHeaders = get_plugin_data($pluginFile, false, false);
$theme = wp_get_theme();
$assert(is_plugin_active('wooshop-commerce/wooshop-commerce.php'), 'Companion plugin must be active.');
$assert(is_plugin_active('woocommerce/woocommerce.php'), 'Pinned WooCommerce must be active.');
$assert(get_bloginfo('version') === '7.1.3', 'WordPress version differs from this declared verification matrix.');
$assert(defined('WC_VERSION') && WC_VERSION === '11.2.1', 'WooCommerce version differs from this declared verification matrix.');
$assert($pluginHeaders['Version'] === '0.1.0', 'Unexpected installed companion version.');
$assert(get_stylesheet() === 'wooshop' && $theme->get('Version') === '0.1.0', 'Expected active theme/version is unavailable.');
$assert(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 4, 'Run this assurance matrix on the pinned PHP8.4 container.');

global $wpdb;
$db = $kernel->database(); $entities = $kernel->entities();
$suffixes = ['entities', 'jobs', 'events', 'operations', 'rate_limits', 'sellers', 'memberships', 'offers', 'inventory_pools', 'reservations', 'inventory_movements', 'journals', 'journal_entries', 'allocations', 'marketplace_commands'];
$engines = [];
foreach ($suffixes as $suffix) {
    $engine = $wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $db->table($suffix)));
    $assert(strtoupper((string) $engine) === 'INNODB', 'Required transactional table missing or non-InnoDB: '.$suffix);
    $engines[$suffix] = strtoupper((string) $engine);
}
$assert((int) get_option('wooshop_commerce_schema_version', 0) === WooShop\Commerce\Infrastructure\Schema::VERSION, 'Recorded schema version must match installed implementation.');

// Storage is an explicit test matrix argument, never an automatic fallback after failure.
$expectedStorage = $args[0] ?? 'hpos';
$assert(in_array($expectedStorage, ['hpos', 'legacy'], true), 'Expected storage must be explicitly hpos or legacy.');
$actualHpos = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
$assert($actualHpos === ($expectedStorage === 'hpos'), 'Expected '.$expectedStorage.' storage is not enabled for the declared matrix.');
$priorOrderProof = null;
if (isset($args[1]) || isset($args[2])) {
    $assert(isset($args[1], $args[2]) && preg_match('/^[1-9][0-9]*$/D', $args[1]) && preg_match('/^[a-f0-9]{64}$/D', $args[2]), 'Migration continuity requires an explicit prior synthetic order ID and snapshot digest.');
    $prior = wc_get_order((int) $args[1]);
    $assert($prior && $prior->get_meta('_wooshop_test_origin', true) === 'core.package-smoke' && $prior->get_meta('_wooshop_test_site', true) === $site, 'Prior order is not this site\'s assurance evidence.');
    $priorItems = $prior->get_items('line_item');
    $assert(count($priorItems) === 1, 'Prior assurance order line identity changed.');
    $priorItem = reset($priorItems); $priorSnapshot = $priorItem->get_meta('_wooshop_purchase_snapshot', true);
    $assert(is_array($priorSnapshot) && $priorSnapshot['order_id'] === (string) $prior->get_id() && hash_equals($args[2], (string) $priorItem->get_meta('_wooshop_purchase_snapshot_digest', true)) && hash_equals($args[2], hash('sha256', Records::canonical($priorSnapshot))), 'Prior native order identity or immutable snapshot did not survive storage migration.');
    $priorOrderProof = ['order_id' => (string) $prior->get_id(), 'snapshot_digest' => $args[2], 'result' => 'unchanged'];
}

// Same IDs/option names intentionally exist independently on every site. Never print salt values.
$markerId = 'e3ca0846-9ad0-4d35-a80a-0470a1cf9609';
$marker = ['origin' => 'tests/core/package-smoke.php', 'site' => $site, 'home_digest' => hash('sha256', home_url('/'))];
$existing = $entities->get('assurance.package-smoke', $markerId);
$createdMarker = false; $createdOption = false; $assuranceComplete = false;
if (!$existing) { $existing = $entities->create('assurance.package-smoke', $marker, 'assurance:'.$site, $markerId); $createdMarker = true; }
$cleanupSetup = static function () use ($entities, $markerId, $marker, &$createdMarker, &$createdOption, &$assuranceComplete): void {
    if ($assuranceComplete) { return; }
    if ($createdMarker) {
        $record = $entities->get('assurance.package-smoke', $markerId);
        if ($record && $record['data'] === $marker) { $entities->delete('assurance.package-smoke', $markerId, $record['version']); }
        $createdMarker = false;
    }
    if ($createdOption && get_option('wooshop_assurance_site_marker') === $marker) { delete_option('wooshop_assurance_site_marker'); $createdOption = false; }
};
register_shutdown_function($cleanupSetup);
$assert($existing['data'] === $marker && $existing['owner_id'] === 'assurance:'.$site, 'Fixed entity ID resolves to foreign site data.');
$optionKey = 'wooshop_assurance_site_marker';
$option = get_option($optionKey, null);
if ($option === null) { $createdOption = add_option($optionKey, $marker, '', false); }
$assert(get_option($optionKey) === $marker, 'Identical native option key resolves to foreign site settings.');
$signature = hash_hmac('sha256', 'wooshop-package-assurance-nonsecret-challenge-v1', wp_salt('auth'));
$assert(strlen($signature) === 64, 'Could not derive a non-secret salt-isolation probe.');

// Prove typed plugin settings are local and that the shared transaction wrapper preserves rollback.
$settings = $kernel->settings(); $beforeSettings = $settings->snapshot();
$beforeValue = $settings->get('wishlist.max_items');
$localSetting = ['outdoor' => 91, 'smart-home' => 92, 'package-check' => 93][$site];
$previousActor = get_current_user_id();
$admin = get_user_by('login', 'fixture-admin');
$assert($admin !== false, 'Synthetic local administrator is missing.');
wp_set_current_user($admin->ID);
$assert($wpdb->query('START TRANSACTION') !== false, 'Unable to begin the settings rollback probe.');
try {
    $db->transaction(function () use ($settings, $localSetting, $assert): void {
        $preview = $settings->preview([['key' => 'wishlist.max_items', 'value' => $localSetting, 'scope' => 'site']]);
        $saved = $settings->commit($preview['preview_id'], $settings->snapshot()['version']);
        $assert($saved['values']['wishlist.max_items'] === $localSetting && $settings->get('wishlist.max_items') === $localSetting, 'Typed settings write/read does not reflect this site sentinel.');
    });
} finally {
    $wpdb->query('ROLLBACK');
    wp_set_current_user($previousActor);
}
$assert($settings->snapshot() === $beforeSettings && $settings->get('wishlist.max_items') === $beforeValue, 'Nested settings transaction escaped the caller rollback.');

// Native HPOS CRUD only. No payment completion, stock decrement, provider or email assertion.
$run = bin2hex(random_bytes(8));
$product = new WC_Product_Simple();
$product->set_name('Synthetic package assurance probe '.$run);
$product->set_status('draft'); $product->set_catalog_visibility('hidden');
$product->set_regular_price('12.34'); $product->set_manage_stock(true); $product->set_stock_quantity(7);
$product->update_meta_data('_wooshop_test_origin', 'core.package-smoke');
$product->update_meta_data('_wooshop_test_run', $run);
$product->update_meta_data('_wooshop_test_site', $site);
$product->save();
$productId = $product->get_id(); $cleanupDone = false;
$cleanup = static function () use ($productId, $run, &$cleanupDone): void {
    if ($cleanupDone) { return; }
    $probe = wc_get_product($productId);
    if (!$probe || $probe->get_meta('_wooshop_fixture_key', true) || $probe->get_meta('_wooshop_test_origin', true) !== 'core.package-smoke' || $probe->get_meta('_wooshop_test_run', true) !== $run) { throw new RuntimeException('Refusing cleanup of an unverified package probe ID.'); }
    $probe->set_status('draft'); $probe->set_catalog_visibility('hidden');
    $probe->update_meta_data('_wooshop_test_cleanup', gmdate('Y-m-d\TH:i:s\Z')); $probe->save();
    $cleanupDone = true;
};
register_shutdown_function($cleanup);
$orderId = null; $itemId = null; $snapshotDigest = null;
try {
    $order = wc_create_order(['customer_id' => 0, 'created_via' => 'wooshop-local-package-assurance']);
    $order->set_currency(get_woocommerce_currency());
    $order->update_meta_data('_wooshop_test_origin', 'core.package-smoke');
    $order->update_meta_data('_wooshop_test_run', $run);
    $order->update_meta_data('_wooshop_test_site', $site);
    $itemId = $order->add_product($product, 2);
    $order->calculate_totals(); $order->save(); $orderId = $order->get_id();
    $assert($orderId > 0 && $itemId > 0, 'Native order/item persistence failed.');
    $storeClass = $order->get_data_store()->get_current_class_name();
    $assert($expectedStorage === 'hpos' ? str_contains($storeClass, 'OrdersTableDataStore') : $storeClass === 'WC_Order_Data_Store_CPT', 'Native order is not using the explicitly expected datastore.');
    (new OrderSnapshots())->capture($order);
    $roundtrip = wc_get_order($orderId); $items = $roundtrip->get_items('line_item');
    $assert(isset($items[$itemId]), 'Native HPOS item cannot be read back.');
    $item = $items[$itemId];
    $snapshot = $item->get_meta('_wooshop_purchase_snapshot', true);
    $snapshotDigest = $item->get_meta('_wooshop_purchase_snapshot_digest', true);
    $assert(is_array($snapshot) && $snapshot['order_id'] === (string) $orderId && $snapshot['order_item_id'] === (string) $itemId && $snapshot['product_id'] === (string) $productId && $snapshot['quantity'] === 2, 'Snapshot lost native order/item/product identity or quantity.');
    $expected = Money::decimal((string) $item->get_total(), $order->get_currency(), (int) wc_get_price_decimals())->jsonSerialize();
    $assert($snapshot['amounts']['net'] === $expected, 'Snapshot amount differs from WooCommerce native order authority.');
    $assert(hash_equals($snapshotDigest, hash('sha256', Records::canonical($snapshot))), 'Purchased snapshot digest failed roundtrip integrity.');
    $product->set_name('Changed synthetic assurance probe '.$run); $product->set_regular_price('99.99'); $product->save();
    (new OrderSnapshots())->capture(wc_get_order($orderId));
    $again = wc_get_order($orderId)->get_items('line_item')[$itemId];
    $assert($again->get_meta('_wooshop_purchase_snapshot', true) === $snapshot && $again->get_meta('_wooshop_purchase_snapshot_digest', true) === $snapshotDigest, 'Catalog edits rewrote the purchased snapshot.');
    $assert(wc_get_order($orderId)->get_meta('_wooshop_test_run', true) === $run, 'Native HPOS order metadata did not persist.');
} finally { $cleanup(); }
$probeAfter = wc_get_product($productId);
$assert($probeAfter->get_status() === 'draft' && $probeAfter->get_catalog_visibility() === 'hidden' && !$probeAfter->get_meta('_wooshop_fixture_key', true), 'Package probe cleanup affected the wrong product or left it public.');

$hashFiles = [
    'plugin/bootstrap' => $pluginFile,
    'plugin/Database' => WP_PLUGIN_DIR.'/wooshop-commerce/src/Infrastructure/Database.php',
    'plugin/Authorization' => WP_PLUGIN_DIR.'/wooshop-commerce/src/Security/Authorization.php',
    'plugin/ModuleRegistry' => WP_PLUGIN_DIR.'/wooshop-commerce/src/Kernel/ModuleRegistry.php',
    'plugin/OrderSnapshots' => WP_PLUGIN_DIR.'/wooshop-commerce/src/Marketplace/OrderSnapshots.php',
    'theme/style' => get_stylesheet_directory().'/style.css',
    'theme/functions' => get_stylesheet_directory().'/functions.php',
];
$codeHashes = [];
foreach ($hashFiles as $name => $file) { $assert(is_file($file), 'Installed runtime file missing: '.$name); $codeHashes[$name] = hash_file('sha256', $file); }
$runtimeTrees = [];
foreach (['plugin' => dirname($pluginFile), 'theme' => get_stylesheet_directory()] as $kind => $base) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile()) { continue; }
        if ($file->isLink()) { throw new RuntimeException('Installed package contains an unexpected file symlink.'); }
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base) + 1));
        $files[$relative] = hash_file('sha256', $file->getPathname());
    }
    ksort($files, SORT_STRING);
    $assert(count($files) > 0, 'Installed package tree is empty.');
    $runtimeTrees[$kind] = ['file_count' => count($files), 'sha256' => hash('sha256', json_encode($files, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))];
}
$output = ['suite' => 'installed package/source assurance', 'result' => 'PASS', 'assertions' => $checks, 'site' => $site, 'home' => home_url('/'), 'versions' => ['wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION, 'plugin' => $pluginHeaders['Version'], 'theme' => $theme->get('Version'), 'php' => PHP_VERSION], 'transactional_tables' => $engines, 'isolation' => ['database_scope_digest' => hash('sha256', DB_NAME.'|'.$wpdb->prefix), 'salt_probe_hmac' => $signature, 'fixed_entity_id' => $markerId, 'entity_site' => $existing['data']['site'], 'native_option_site' => get_option($optionKey)['site'], 'plugin_settings_rollback' => 'passed'], 'native_order_crud' => ['expected_storage' => $expectedStorage, 'actual_hpos' => $actualHpos, 'datastore_class' => $storeClass, 'order_id' => (string) $orderId, 'order_item_id' => (string) $itemId, 'snapshot_digest' => $snapshotDigest, 'product_id' => (string) $productId, 'product_cleanup' => 'draft/hidden; historical order retained', 'payment_provider_scope' => 'not exercised'], 'migration_previous_order' => $priorOrderProof, 'runtime_code_sha256' => $codeHashes, 'installed_tree_sha256' => $runtimeTrees];
$assuranceComplete = true;
echo 'WOOSHOP_PACKAGE_SMOKE='.wp_json_encode($output, JSON_UNESCAPED_SLASHES).PHP_EOL;
