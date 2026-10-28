<?php
/** Isolated native WooCommerce fixtures only. No gateway payment/refund or customer communication is requested. */
if (!defined('WP_CLI') || !WP_CLI || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Requires the authorized synthetic local store.'); }
if (!defined('WP_PLUGIN_DIR') || !in_array('wooshop-commerce/wooshop-commerce.php', (array) get_option('active_plugins', []), true) || !is_readable(WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php') || !class_exists(\WooShop\Commerce\Kernel\Plugin::class, false) || !\WooShop\Commerce\Kernel\Plugin::instance()) { throw new RuntimeException('The installed WooShop Commerce plugin must already be active and bootstrapped.'); }
require_once WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php';
if (!apply_filters('wooshop_commerce_catalog_services', [])) { throw new RuntimeException('The active installed plugin did not register its catalog services.'); }
require_once '/workspace/tests/catalog/loyalty-fixture.php';

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Merchandising\Loyalty;
use WooShop\Commerce\Support\DomainError;

final class LoyaltyFixtureRollback extends RuntimeException {}
$loyaltyDb = new Database($GLOBALS['wpdb']); $loyaltyStore = new EntityStore($loyaltyDb); $loyaltyChecks = 0;
$equal = static function ($actual, $expected, string $case) use (&$loyaltyChecks): void { $loyaltyChecks++; if ($actual !== $expected) { throw new RuntimeException($case . ': ' . wp_json_encode(['actual' => $actual, 'expected' => $expected])); } };
$reject = static function (callable $operation, string $code) use ($equal): void { try { $operation(); } catch (DomainError $error) { $equal($error->errorCode, $code, 'rejection'); return; } throw new RuntimeException('Expected ' . $code); };
try {
    $loyaltyDb->transaction(function () use ($loyaltyDb, $loyaltyStore, $equal, $reject): void {
        $tag = bin2hex(random_bytes(6)); $admin = get_users(['role' => 'administrator', 'number' => 1])[0];
        $customer = wp_insert_user(['user_login' => 'reward_' . $tag, 'user_email' => 'reward-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        $foreign = wp_insert_user(['user_login' => 'reward_foreign_' . $tag, 'user_email' => 'reward-foreign-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        if (is_wp_error($customer) || is_wp_error($foreign)) { throw new RuntimeException('Cannot create synthetic reward accounts.'); }
        $policy = catalog_loyalty_policy_fixture(get_woocommerce_currency(), wc_get_price_decimals(), (string) $admin->ID, (string) $foreign);
        $loyalty = new Loyalty($loyaltyDb, $loyaltyStore, $policy, true);
        $program = $loyaltyStore->get('loyalty_program', Input::stableId('loyalty_program'));
        if ($program['data']['mode'] !== 'active') { wp_set_current_user($admin->ID); $loyalty->setMode('active', $program['version']); }
        $disabled = new Loyalty($loyaltyDb, $loyaltyStore); wp_set_current_user($customer);
        $reject(fn () => $disabled->reserve(1, wp_generate_uuid4()), 'loyalty_disabled');
        $makeOrder = static function (string $total, int $paidAt) use ($customer, $tag) {
            $order = wc_create_order(['customer_id' => $customer]); if (is_wp_error($order)) { throw new RuntimeException('Order fixture unavailable.'); }
            $order->set_currency(get_woocommerce_currency()); $order->set_total($total); $order->set_date_paid($paidAt); $order->set_status('completed'); $order->update_meta_data('_wooshop_catalog_test_origin', 'loyalty-suite:' . $tag); $order->save(); return $order;
        };
        $order = $makeOrder('100', time() - 10); $earned = $loyalty->earnPaidOrder((string) $order->get_id());
        $equal($earned['points'], 100, 'verified native fixture earns explicit synthetic policy');
        $equal($loyalty->earnPaidOrder((string) $order->get_id()), $earned, 'duplicate paid source earns once');
        $equal($loyalty->balance()['pending'], 100, 'earned points pending before release');
        $loyalty->releaseDue(); $equal($loyalty->balance()['available'], 100, 'due points released');
        $key = wp_generate_uuid4(); $reservation = $loyalty->reserve(80, $key);
        $equal($loyalty->balance()['available'], 20, 'reserve debits available'); $equal($loyalty->balance()['held'], 80, 'reserve holds points');
        $equal($loyalty->reserve(80, $key)['id'], $reservation['id'], 'redemption replay holds once');
        $reject(fn () => $loyalty->reserve(70, $key), 'idempotency_conflict');
        $reject(fn () => $loyalty->reserve(21, wp_generate_uuid4()), 'insufficient_points');
        wp_set_current_user($foreign); $reject(fn () => $loyalty->issueCoupon($reservation['id']), 'not_found'); $reject(fn () => $loyalty->earnPaidOrder((string) $order->get_id()), 'not_found'); wp_set_current_user($customer);
        WC()->customer = new WC_Customer($customer); WC()->customer->set_billing_email('reward-' . $tag . '@example.invalid');
        if (!WC()->session) { WC()->session = new WC_Session_Handler(); WC()->session->init(); }
        WC()->cart = new WC_Cart();
        $issued = $loyalty->issueCoupon($reservation['id']); $coupon = new WC_Coupon((int) $issued['data']['coupon_id']);
        $equal($coupon->get_discount_type(), 'fixed_cart', 'native coupon authority'); $equal($coupon->get_usage_limit(), 1, 'native coupon single use');
        $equal($loyalty->issueCoupon($reservation['id'])['data']['coupon_id'], $issued['data']['coupon_id'], 'coupon issuance replay creates one coupon');
        $equal($coupon->is_valid(), true, 'installed WooCommerce accepts own held reward coupon');
        wp_set_current_user($foreign); $equal($coupon->is_valid(), false, 'native coupon rejects foreign owner'); wp_set_current_user($customer);
        $purchase = $makeOrder('100', time()); $couponItem = new WC_Order_Item_Coupon(); $couponItem->set_code($coupon->get_code()); $couponItem->set_discount(Loyalty::major($issued['data']['amount_minor'], $issued['data']['exponent'])); $purchase->add_item($couponItem); $purchase->save();
        $loyalty->attachToOrder($purchase); $loyalty->consumePaidOrder((string) $purchase->get_id()); $loyalty->consumePaidOrder((string) $purchase->get_id());
        $equal($loyalty->balance()['held'], 0, 'purchase consumes held points once'); $equal($loyalty->balance()['redeemed'], 80, 'spent points separately auditable');
        $equal($coupon->is_valid(), false, 'consumed reward cannot be replayed');
        $refund = wc_create_refund(['order_id' => $order->get_id(), 'amount' => '50', 'reason' => 'Synthetic reward reversal fixture', 'refund_payment' => false, 'restock_items' => false]);
        if (is_wp_error($refund)) { throw new RuntimeException('Local refund fixture failed: ' . $refund->get_error_code()); }
        $reversed = $loyalty->refund((string) $refund->get_id()); $equal($reversed['reversed_points'], 50, 'refund uses original rule');
        $equal($loyalty->refund((string) $refund->get_id()), $reversed, 'refund source replay deduplicates');
        $equal($loyalty->balance()['available'], -30, 'refund after redemption creates restricted negative points'); $equal($loyalty->balance()['restricted'], true, 'negative points restrict future redemption');
        $reject(fn () => $loyalty->reserve(1, wp_generate_uuid4()), 'insufficient_points');
        $oldOrder = $makeOrder('40', time() - 2678400); $loyalty->earnPaidOrder((string) $oldOrder->get_id()); $settled = $loyalty->releaseDue(); $equal($settled['expired'], 40, 'expiry uses original paid instant');
        $equal($loyalty->balance()['expired'], 40, 'expired points separately auditable');
        wp_set_current_user($admin->ID); $program = $loyaltyStore->get('loyalty_program', Input::stableId('loyalty_program')); $drain = $loyalty->setMode('drain', $program['version']); wp_set_current_user($customer);
        $reject(fn () => $loyalty->reserve(1, wp_generate_uuid4()), 'loyalty_disabled');
        $reject(fn () => $loyalty->earnPaidOrder((string) $order->get_id()), 'loyalty_disabled');
        $equal($loyalty->balance()['redeemed'], 80, 'drain retains historical journal');
        throw new LoyaltyFixtureRollback();
    });
} catch (LoyaltyFixtureRollback $rollback) { echo 'Loyalty WordPress checks passed: ' . $loyaltyChecks . "; synthetic native order/refund/coupon records rolled back; no gateway requested.\n"; }
