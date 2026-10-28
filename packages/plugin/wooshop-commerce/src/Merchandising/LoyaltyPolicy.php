<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Support\DomainError;

/** A trusted, reviewed policy value object. No request controller constructs or approves this object. */
final class LoyaltyPolicy
{
    public readonly array $data;
    public function __construct(array $data)
    {
        $keys = ['id', 'version', 'approval_reference', 'prepared_by', 'reviewed_by', 'currency', 'exponent', 'earn_numerator', 'earn_denominator_minor', 'earn_basis', 'earn_rounding', 'refund_rounding', 'negative_policy', 'pending_seconds', 'expiry_seconds', 'expiry_notice_required', 'redemption_points_per_minor', 'redemption_min_points', 'redemption_max_points', 'redemption_hold_seconds', 'max_open_redemptions', 'coupon_stacking', 'redeemed_refund_policy', 'qualifying_statuses', 'terms_uri'];
        Input::keys($data, $keys, $keys);
        Input::uuid($data['id']); Input::uuid($data['approval_reference']); Input::positiveInt($data['version']);
        Input::id($data['prepared_by']); Input::id($data['reviewed_by']);
        if ($data['prepared_by'] === $data['reviewed_by']) { throw new DomainError('policy_self_approval', 'A different finance reviewer must approve the policy.', 403); }
        if (!is_string($data['currency']) || !preg_match('/^[A-Z]{3}$/D', $data['currency'])) { throw new DomainError('invalid_currency', 'An ISO currency is required.', 422); }
        foreach (['exponent' => [0, 6], 'earn_numerator' => [1, 1000000], 'earn_denominator_minor' => [1, 1000000000], 'pending_seconds' => [0, 7776000], 'expiry_seconds' => [2592000, 63072000], 'redemption_points_per_minor' => [1, 1000000000], 'redemption_min_points' => [1, 1000000000], 'redemption_max_points' => [1, 1000000000], 'redemption_hold_seconds' => [300, 86400], 'max_open_redemptions' => [1, 20]] as $key => $range) {
            if (!is_int($data[$key]) || $data[$key] < $range[0] || $data[$key] > $range[1]) { throw new DomainError('invalid_policy_number', 'A policy integer is outside supported bounds.', 422); }
        }
        if ($data['pending_seconds'] >= $data['expiry_seconds'] || $data['redemption_min_points'] > $data['redemption_max_points']) { throw new DomainError('invalid_policy_range', 'Policy timing or redemption bounds conflict.', 422); }
        if (!in_array($data['earn_basis'], ['order_total', 'items_excluding_tax_shipping'], true) || $data['earn_rounding'] !== 'floor' || $data['refund_rounding'] !== 'cumulative_floor' || $data['negative_policy'] !== 'restrict_redemption' || !in_array($data['coupon_stacking'], ['exclusive', 'native_rules'], true) || $data['redeemed_refund_policy'] !== 'manual_review') { throw new DomainError('unsupported_policy', 'This policy needs a separately verified calculation or redemption adapter.', 422); }
        Input::bool($data['expiry_notice_required']);
        if (!is_array($data['qualifying_statuses']) || !$data['qualifying_statuses'] || array_diff($data['qualifying_statuses'], ['processing', 'completed'])) { throw new DomainError('invalid_payment_status', 'Choose supported paid order states explicitly.', 422); }
        if (!is_string($data['terms_uri']) || !filter_var($data['terms_uri'], FILTER_VALIDATE_URL) || parse_url($data['terms_uri'], PHP_URL_SCHEME) !== 'https') { throw new DomainError('invalid_terms', 'Reviewed HTTPS program terms are required.', 422); }
        $this->data = $data;
    }

    /** Decimal multiplication/division avoids overflowing amount×rate or using floating money. */
    public static function proportional(int $amount, int $numerator, int $denominator): int
    {
        if ($amount < 0 || $numerator < 0 || $denominator < 1 || $amount > 999999999999999 || $numerator > 1000000000 || $denominator > 999999999999999) { throw new DomainError('arithmetic_range', 'Calculation exceeds supported integer bounds.', 422); }
        $carry = 0; $product = '';
        foreach (array_reverse(str_split((string) $amount)) as $digit) { $value = (int) $digit * $numerator + $carry; $product = ($value % 10) . $product; $carry = intdiv($value, 10); }
        while ($carry > 0) { $product = ($carry % 10) . $product; $carry = intdiv($carry, 10); }
        $remainder = 0; $quotient = '';
        foreach (str_split($product) as $digit) { $value = $remainder * 10 + (int) $digit; $quotient .= (string) intdiv($value, $denominator); $remainder = $value % $denominator; }
        $quotient = ltrim($quotient, '0') ?: '0';
        if (strlen($quotient) > 10 || (int) $quotient > 1000000000) { throw new DomainError('points_out_of_range', 'Calculated points exceed the supported per-effect bound.', 422); }
        return (int) $quotient;
    }
}
