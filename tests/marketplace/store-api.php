<?php
declare(strict_types=1);
if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Refusing Store API fixtures outside local isolation.'); }
require_once WP_PLUGIN_DIR.'/wooshop-commerce/autoload.php';
require_once __DIR__.'/FixtureProducts.php';
use WooShop\Commerce\Marketplace\NativeGuard;
$guard = new NativeGuard(); $guard->register();
wp_set_current_user(0);
if (!WC()->session) { WC()->initialize_session(); }
if (!WC()->cart) { WC()->initialize_cart(); }
WC()->cart->empty_cart();
$product = new WC_Product_Simple(); MarketplaceFixtureProducts::track($product, 'marketplace.store-api', bin2hex(random_bytes(8))); $product->set_name('Synthetic Store API stock authority fixture'); $product->set_status('publish'); $product->set_regular_price('10.00'); $product->set_manage_stock(true); $product->set_stock_quantity(3); $product->save();
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void { ++$checks; if (!$condition) { throw new RuntimeException($message); } };
$request = static function (array $body): WP_REST_Request {
    $q = new WP_REST_Request('POST', '/wc/store/v1/cart/add-item'); $q->set_header('Content-Type', 'application/json'); $q->set_header('Nonce', wp_create_nonce('wc_store_api')); $q->set_body(wp_json_encode($body)); return $q;
};
$response = rest_do_request($request(['id' => $product->get_id(), 'quantity' => 1]));
$check($response->get_status() < 300, 'Ordinary native Store API add-to-cart remains functional: '.wp_json_encode($response->get_data()));
$wire = json_decode(wp_json_encode($response->get_data()), true, 512, JSON_THROW_ON_ERROR);
$items = $wire['items'] ?? [];
$check(count($items) === 1, 'Store API cart contains one native fixture line');
$extension = $items[0]['extensions']['commerce'] ?? null;
$check(is_array($extension) && $extension['marketplace_available'] === false && $extension['offer_id'] === null, 'Verified CartItemSchema mechanism exposes safe namespaced commerce extension');
$spoof = rest_do_request($request(['id' => $product->get_id(), 'quantity' => 1, 'extensions' => ['commerce' => ['offer_id' => '00000000-0000-4000-8000-000000000001', 'price' => '0.01']]]));
$check($spoof->get_status() === 503, 'Client-supplied marketplace extension cannot manufacture an accepted seller quote');
$check(count(WC()->cart->get_cart()) === 1 && array_values(WC()->cart->get_cart())[0]['quantity'] === 1, 'Rejected extension leaves native cart unchanged');
$product->update_meta_data('_wooshop_authority_mode', 'marketplace_plugin'); $product->set_stock_quantity(0); $product->set_stock_status('outofstock'); $product->set_backorders('no'); $product->save();
$blocked = rest_do_request($request(['id' => $product->get_id(), 'quantity' => 1]));
$check($blocked->get_status() >= 400, 'Direct mapped shell Store API add-to-cart is rejected');
$cart = new WP_REST_Request('GET', '/wc/store/v1/cart'); $cart->set_header('Nonce', wp_create_nonce('wc_store_api'));
$current = rest_do_request($cart);
$errors = $current->get_data()['errors'] ?? [];
$check($current->get_status() >= 400 || $errors !== [], 'Existing native cart line becomes invalid after authority changes to a blocked shell');
WC()->cart->empty_cart();
MarketplaceFixtureProducts::cleanup();
$cleanedProduct = wc_get_product($product->get_id());
$check($cleanedProduct->get_status() === 'draft' && $cleanedProduct->get_meta('_wooshop_test_origin', true) === 'marketplace.store-api', 'Store API probe has explicit provenance and is drafted after testing');
echo 'PASS '.$checks." WooCommerce 11.2.1 Store API assertions; no checkout/payment provider proof.\n";
