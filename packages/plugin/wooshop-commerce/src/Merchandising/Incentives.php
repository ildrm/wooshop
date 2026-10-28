<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Options;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

/** WooCommerce calculates discounts/taxes. This module validates eligibility and never rewrites prices. */
final class Incentives
{
    private EntityStore $store; private array $rules = []; private bool $recalculating = false;
    public function __construct(EntityStore $store, array $reviewedRules = [])
    {
        $this->store = $store;
        if (count($reviewedRules) > Options::integer('incentives.max_rules', 5, 0, 10)) { throw new DomainError('incentive_rule_limit', 'Too many incentive rules for this site.', 422); }
        $couponIds = [];
        foreach ($reviewedRules as $rule) {
            if (!$rule instanceof IncentiveRule) { throw new DomainError('reviewed_rule_required', 'An explicit trusted reviewed incentive rule is required.', 422); }
            $data = $rule->data;
            if (isset($this->rules[$data['id']]) || isset($couponIds[$data['native_coupon_id']])) { throw new DomainError('duplicate_rule', 'Rule and native coupon identities must be unique.', 422); }
            $id = Input::stableId('incentive_rule:' . $data['id'] . ':' . $data['version']); $existing = $store->get('incentive_rule', $id);
            if ($existing && $existing['data'] !== $data) { throw new DomainError('immutable_rule', 'Publish a new incentive rule version to change terms.', 409); }
            if (!$existing) { $store->create('incentive_rule', $data, 'finance', $id); }
            $couponIds[$data['native_coupon_id']] = true; $this->rules[$data['id']] = $rule;
        }
    }

    private function cart($cart = null)
    {
        $cart = $cart ?? (function_exists('WC') ? WC()->cart : null);
        if (!$cart instanceof \WC_Cart) { throw new DomainError('cart_unavailable', 'A native WooCommerce cart session is required.', 503); }
        return $cart;
    }
    public function hasRules(): bool { return $this->rules !== []; }

    public function evaluate($cart = null): array
    {
        $cart = $this->cart($cart); $results = []; $eligibleGroups = [];
        // Marketplace funding/allocation is intentionally outside this verified single-merchant adapter.
        $marketplace = Options::get('marketplace.enabled', false) === true || (bool) apply_filters('wooshop_commerce_incentive_marketplace_cart', false, $cart);
        foreach ($cart->get_cart() as $line) {
            foreach (['wooshop_offer_id', 'wooshop_offer', 'commerce_offer', 'wooshop_commerce'] as $key) { if (!empty($line[$key])) { $marketplace = true; } }
            $product = $line['data'] ?? null;
            if ($product instanceof \WC_Product) {
                if ($product->get_meta('_wooshop_authority_mode', true) === 'marketplace_plugin') { $marketplace = true; }
                if ($product->is_type('variation')) { $parent = wc_get_product($product->get_parent_id()); if ($parent && $parent->get_meta('_wooshop_authority_mode', true) === 'marketplace_plugin') { $marketplace = true; } }
            }
        }
        $subtotal = (int) PriceHistory::minor(wc_format_decimal($cart->get_cart_contents_total(), wc_get_price_decimals()), wc_get_price_decimals());
        $groupDiscounts = [];
        foreach ($this->rules as $rule) { $data = $rule->data; $coupon = new \WC_Coupon((int) $data['native_coupon_id']); if ($coupon->get_id() && $cart->has_discount($coupon->get_code())) { $groupDiscounts[$data['stacking_group']] = ($groupDiscounts[$data['stacking_group']] ?? 0) + (int) PriceHistory::minor(wc_format_decimal($cart->get_coupon_discount_amount($coupon->get_code(), true), wc_get_price_decimals()), wc_get_price_decimals()); } }
        foreach ($this->rules as $id => $rule) {
            $data = $rule->data; $coupon = new \WC_Coupon((int) $data['native_coupon_id']); $code = $coupon->get_code();
            $reason = null;
            if ($marketplace) { $reason = 'marketplace_funding_unavailable'; }
            elseif ($data['currency'] !== get_woocommerce_currency() || $data['exponent'] !== wc_get_price_decimals()) { $reason = 'currency_changed'; }
            elseif (strtotime($data['starts_at']) > time() || strtotime($data['ends_at']) <= time()) { $reason = 'rule_outside_schedule'; }
            elseif (!$coupon->get_id() || get_post_status($coupon->get_id()) !== 'publish' || $coupon->get_discount_type() !== 'fixed_cart') { $reason = 'native_coupon_unavailable'; }
            elseif ((int) PriceHistory::minor((string) $coupon->get_amount('edit'), $data['exponent']) > (int) $data['maximum_benefit_minor']) { $reason = 'benefit_exceeds_reviewed_limit'; }
            $ownDiscount = $code !== '' && $cart->has_discount($code) ? (int) PriceHistory::minor(wc_format_decimal($cart->get_coupon_discount_amount($code, true), $data['exponent']), $data['exponent']) : 0;
            $basis = $subtotal + ($groupDiscounts[$data['stacking_group']] ?? 0); // Candidates in one exclusive group share the same basis before that group's benefit.
            $remaining = max(0, (int) $data['threshold_minor'] - $basis);
            if ($reason === null && $remaining > 0) { $reason = 'threshold_not_met'; }
            $results[$id] = ['rule_id' => $id, 'version' => $data['version'], 'eligible' => $reason === null, 'reason' => $reason, 'remaining_threshold_minor' => (string) $remaining, 'basis_minor' => (string) $basis, 'currency' => $data['currency'], 'exponent' => $data['exponent'], 'native_coupon_id' => $data['native_coupon_id'], 'applied' => $code !== '' && $cart->has_discount($code), 'applied_discount_minor' => (string) $ownDiscount, 'funding_allocation' => ['merchant' => (string) $ownDiscount], 'stacking_group' => $data['stacking_group'], 'priority' => $data['priority']];
            if ($reason === null) { $eligibleGroups[$data['stacking_group']][] = $id; }
        }
        foreach ($eligibleGroups as $ids) {
            usort($ids, static fn (string $a, string $b): int => $results[$b]['priority'] <=> $results[$a]['priority'] ?: strcmp($a, $b));
            foreach (array_slice($ids, 1) as $loser) { $results[$loser]['eligible'] = false; $results[$loser]['reason'] = 'exclusive_stacking_group'; }
        }
        return ['source' => 'woocommerce_cart_totals', 'basis' => 'after_discounts_excluding_tax_shipping', 'benefits' => array_values($results)];
    }

    public function apply(string $ruleId, int $version): array
    {
        Input::uuid($ruleId); $cart = $this->cart(); $cart->calculate_totals();
        $rule = $this->rules[$ruleId] ?? null;
        if (!$rule) { throw new DomainError('not_found', 'Incentive unavailable.', 404); }
        if ($rule->data['version'] !== $version) { throw new DomainError('stale_revision', 'Incentive terms changed. Reload and retry.', 412); }
        $benefits = array_column($this->evaluate($cart)['benefits'], null, 'rule_id');
        if (empty($benefits[$ruleId]['eligible'])) { throw new DomainError('incentive_ineligible', 'The current cart does not meet this incentive’s reviewed terms.', 422); }
        $coupon = new \WC_Coupon((int) $rule->data['native_coupon_id']);
        if (!$cart->has_discount($coupon->get_code()) && !$cart->apply_coupon($coupon->get_code())) { throw new DomainError('native_coupon_rejected', 'WooCommerce rejected this coupon. Review the native coupon terms.', 422); }
        $cart->calculate_totals(); return $this->evaluate($cart);
    }

    public function enforce($cart): void
    {
        if ($this->recalculating || !$this->rules) { return; } $cart = $this->cart($cart); $changed = false;
        foreach ($this->evaluate($cart)['benefits'] as $benefit) {
            if ($benefit['applied'] && !$benefit['eligible']) { $coupon = new \WC_Coupon((int) $benefit['native_coupon_id']); $cart->remove_coupon($coupon->get_code()); $changed = true; }
        }
        if ($changed) { $this->recalculating = true; try { $cart->calculate_totals(); } finally { $this->recalculating = false; } }
    }

    public function validateCheckout($errors, $cart = null): void
    {
        if (!$this->rules) { return; }
        $cart = $this->cart($cart);
        foreach ($this->evaluate($cart)['benefits'] as $benefit) { if ($benefit['applied'] && !$benefit['eligible']) { $errors->add('wooshop_incentive_changed', __('An applied incentive no longer meets its reviewed terms. Recalculate your cart.', 'wooshop-commerce')); } }
    }

    public function register(): void
    {
        add_action('woocommerce_after_calculate_totals', [$this, 'enforce'], 50);
        add_action('woocommerce_store_api_cart_errors', [$this, 'validateCheckout'], 20, 2);
        add_action('woocommerce_after_checkout_validation', function ($data, $errors): void { $this->validateCheckout($errors); }, 20, 2);
    }
}
