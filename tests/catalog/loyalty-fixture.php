<?php
/** Synthetic reviewed-policy values for tests only. No business or human approval is asserted. */
function catalog_loyalty_policy_fixture(string $currency, int $exponent, string $prepared, string $reviewed, array $overrides = []): \WooShop\Commerce\Merchandising\LoyaltyPolicy
{
    return new \WooShop\Commerce\Merchandising\LoyaltyPolicy(array_replace([
        'id' => \WooShop\Commerce\Catalog\Input::stableId('synthetic_policy:' . $prepared . ':' . $reviewed), 'version' => 1,
        'approval_reference' => \WooShop\Commerce\Catalog\Input::stableId('synthetic_review:' . $prepared . ':' . $reviewed),
        'prepared_by' => $prepared, 'reviewed_by' => $reviewed, 'currency' => $currency, 'exponent' => $exponent,
        'earn_numerator' => 1, 'earn_denominator_minor' => 10 ** $exponent,
        'earn_basis' => 'order_total', 'earn_rounding' => 'floor', 'refund_rounding' => 'cumulative_floor', 'negative_policy' => 'restrict_redemption',
        'pending_seconds' => 0, 'expiry_seconds' => 2592000, 'expiry_notice_required' => false,
        'redemption_points_per_minor' => 1, 'redemption_min_points' => 1, 'redemption_max_points' => 1000000, 'redemption_hold_seconds' => 900,
        'max_open_redemptions' => 20, 'coupon_stacking' => 'exclusive', 'redeemed_refund_policy' => 'manual_review',
        'qualifying_statuses' => ['completed', 'processing'], 'terms_uri' => 'https://example.invalid/synthetic-rewards-terms',
    ], $overrides));
}
