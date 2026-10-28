<?php
/** Native cart/coupon integration using synthetic reviewed terms, not a funding approval claim. */
if (!defined('WP_CLI') || !WP_CLI || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Requires isolated fixture store.'); }
if (!defined('WP_PLUGIN_DIR') || !in_array('wooshop-commerce/wooshop-commerce.php', (array) get_option('active_plugins', []), true) || !is_readable(WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php') || !class_exists(\WooShop\Commerce\Kernel\Plugin::class, false) || !\WooShop\Commerce\Kernel\Plugin::instance()) { throw new RuntimeException('The installed WooShop Commerce plugin must already be active and bootstrapped.'); }
require_once WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php';
if (!apply_filters('wooshop_commerce_catalog_services', [])) { throw new RuntimeException('The active installed plugin did not register its catalog services.'); }
require_once '/workspace/tests/catalog/fixture-cleanup.php';
use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Merchandising\IncentiveRule;
use WooShop\Commerce\Merchandising\Incentives;
use WooShop\Commerce\Merchandising\PriceHistory;
use WooShop\Commerce\Support\DomainError;
final class IncentiveFixtureRollback extends RuntimeException {}
$db = new Database($GLOBALS['wpdb']); $store = new EntityStore($db); $incentiveChecks = 0;
$same = static function ($actual, $expected, string $case) use (&$incentiveChecks): void { $incentiveChecks++; if ($actual !== $expected) { throw new RuntimeException($case . ': ' . wp_json_encode(['actual' => $actual, 'expected' => $expected])); } };
try {
    $db->transaction(function () use ($store, $same): void {
        $tag = bin2hex(random_bytes(6)); $admin = get_users(['role' => 'administrator', 'number' => 1])[0];
        $customer = wp_insert_user(['user_login' => 'incentive_' . $tag, 'user_email' => 'incentive-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']); if (is_wp_error($customer)) { throw new RuntimeException('Cannot create fixture customer.'); }
        wp_set_current_user($customer); WC()->customer = new WC_Customer($customer);
        if (!WC()->session) { WC()->session = new WC_Session_Handler(); WC()->session->init(); }
        WC()->cart = new WC_Cart();
        $product = new WC_Product_Simple(); $product->set_name('Synthetic incentive probe ' . $tag); $product->set_status('publish'); $product->set_regular_price('50'); $product->set_virtual(true); $product->set_tax_status('none'); $productId = catalog_save_product_fixture($product, 'incentive-suite:' . $tag);
        $lineKey = WC()->cart->add_to_cart((int) $productId, 2); if (!$lineKey) { throw new RuntimeException('Native cart rejected synthetic simple product.'); }
        $nativeCoupon = static function (string $code, string $amount) { $coupon = new WC_Coupon(); $coupon->set_code($code); $coupon->set_discount_type('fixed_cart'); $coupon->set_amount($amount); $coupon->set_individual_use(false); $coupon->save(); return $coupon; };
        $shared = $nativeCoupon('shared-' . $tag, '20'); $benefit = $nativeCoupon('benefit-' . $tag, '10');
        $same(WC()->cart->apply_coupon($shared->get_code()), true, 'native shared coupon applied'); WC()->cart->calculate_totals();
        $exponent = wc_get_price_decimals(); $minor = static fn (string $amount): string => PriceHistory::minor($amount, $exponent);
        $base = ['id' => Input::stableId('incentive:' . $tag), 'version' => 1, 'approval_reference' => Input::stableId('review:' . $tag), 'prepared_by' => (string) $admin->ID, 'reviewed_by' => (string) $customer, 'currency' => get_woocommerce_currency(), 'exponent' => $exponent, 'threshold_minor' => $minor('75'), 'maximum_benefit_minor' => $minor('10'), 'starts_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600), 'ends_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 3600), 'stacking_group' => 'fixture', 'priority' => 10, 'native_coupon_id' => (string) $benefit->get_id(), 'basis' => 'after_discounts_excluding_tax_shipping', 'action' => 'coupon', 'mode' => 'single_seller', 'funding_party' => 'merchant', 'eligible_product_ids' => []];
        $service = new Incentives($store, [new IncentiveRule($base)]); $service->register();
        $evaluation = $service->evaluate(); $same($evaluation['benefits'][0]['basis_minor'], $minor('80'), 'threshold basis is after shared coupon'); $same($evaluation['benefits'][0]['eligible'], true, 'native threshold qualifies');
        $service->apply($base['id'], 1); $same(PriceHistory::minor((string) WC()->cart->get_cart_contents_total(), $exponent), $minor('70'), 'native WooCommerce computes discount');
        $applied = $service->evaluate()['benefits'][0]; $same($applied['basis_minor'], $minor('80'), 'own benefit does not invalidate its qualifying basis'); $same($applied['funding_allocation']['merchant'], $minor('10'), 'single merchant funding equals actual discount');
        $service->apply($base['id'], 1); $same(count(WC()->cart->get_applied_coupons()), 2, 'duplicate apply adds no second benefit');
        WC()->cart->set_quantity($lineKey, 1, true); $same(WC()->cart->has_discount($benefit->get_code()), false, 'removing eligibility removes incentive on native recalc');
        $same($service->evaluate()['benefits'][0]['remaining_threshold_minor'], $minor('45'), 'remaining threshold recomputed after shared coupon');
        $errors = new WP_Error(); do_action('woocommerce_store_api_cart_errors', $errors, WC()->cart); $same($errors->has_errors(), false, 'Store API accepts reconciled cart');
        $expired = $base; $expired['id'] = Input::stableId('expired-incentive:' . $tag); $expired['ends_at'] = gmdate('Y-m-d\TH:i:s\Z', time() - 10); $expired['starts_at'] = gmdate('Y-m-d\TH:i:s\Z', time() - 7200);
        $expiredService = new Incentives($store, [new IncentiveRule($expired)]); $same($expiredService->evaluate()['benefits'][0]['reason'], 'rule_outside_schedule', 'request-time expiry guard');
        WC()->cart->set_quantity($lineKey, 2, true); $service->apply($base['id'], 1);
        $staleErrors = new WP_Error(); $expiredService->validateCheckout($staleErrors, WC()->cart); $same($staleErrors->has_errors(), true, 'checkout rejects stale expired incentive terms');
        $expiredService->enforce(WC()->cart); $same(WC()->cart->has_discount($benefit->get_code()), false, 'expired rule cannot remain applied from cached UI');
        $alternativeCoupon = $nativeCoupon('alternative-' . $tag, '5'); $alternative = $base; $alternative['id'] = Input::stableId('alternative-incentive:' . $tag); $alternative['native_coupon_id'] = (string) $alternativeCoupon->get_id(); $alternative['priority'] = 20;
        $stacked = new Incentives($store, [new IncentiveRule($base), new IncentiveRule($alternative)]); $stackedResults = array_column($stacked->evaluate()['benefits'], null, 'rule_id'); $same($stackedResults[$base['id']]['reason'], 'exclusive_stacking_group', 'one benefit per exclusive group'); $same($stackedResults[$alternative['id']]['eligible'], true, 'reviewed priority determines group winner');
        $marketplaceFilter = static fn (): bool => true; add_filter('wooshop_commerce_incentive_marketplace_cart', $marketplaceFilter); $same($service->evaluate()['benefits'][0]['reason'], 'marketplace_funding_unavailable', 'unknown marketplace funding fails closed'); remove_filter('wooshop_commerce_incentive_marketplace_cart', $marketplaceFilter);
        $bad = $base; $bad['discount_minor'] = '99999'; try { new IncentiveRule($bad); throw new RuntimeException('Client discount field was accepted.'); } catch (DomainError $error) { $same($error->errorCode, 'invalid_fields', 'unknown monetary input rejected'); }
        throw new IncentiveFixtureRollback();
    });
} catch (IncentiveFixtureRollback $rollback) { echo 'Incentive WordPress checks passed: ' . $incentiveChecks . "; synthetic native cart/coupons rolled back; product shutdown protection enabled.\n"; }
