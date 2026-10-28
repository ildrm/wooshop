<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Support\DomainError;

/** Trusted finance-reviewed single-merchant coupon terms. No HTTP route accepts an approval flag. */
final class IncentiveRule
{
    public readonly array $data;
    public function __construct(array $data)
    {
        $keys = ['id', 'version', 'approval_reference', 'prepared_by', 'reviewed_by', 'currency', 'exponent', 'threshold_minor', 'maximum_benefit_minor', 'starts_at', 'ends_at', 'stacking_group', 'priority', 'native_coupon_id', 'basis', 'action', 'mode', 'funding_party', 'eligible_product_ids'];
        Input::keys($data, $keys, $keys); Input::uuid($data['id']); Input::uuid($data['approval_reference']); Input::positiveInt($data['version']); Input::id($data['prepared_by']); Input::id($data['reviewed_by']); Input::id($data['native_coupon_id']);
        if ($data['prepared_by'] === $data['reviewed_by']) { throw new DomainError('rule_self_approval', 'An independent funding reviewer is required.', 403); }
        foreach (['threshold_minor', 'maximum_benefit_minor'] as $key) { if (!is_string($data[$key]) || !preg_match('/^[1-9][0-9]{0,14}$/D', $data[$key])) { throw new DomainError('invalid_rule_money', 'Positive integer minor-unit rule amounts are required.', 422); } }
        if (!is_string($data['currency']) || !preg_match('/^[A-Z]{3}$/D', $data['currency']) || !is_int($data['exponent']) || $data['exponent'] < 0 || $data['exponent'] > 6) { throw new DomainError('invalid_rule_currency', 'Invalid rule currency or exponent.', 422); }
        if ($data['basis'] !== 'after_discounts_excluding_tax_shipping' || $data['action'] !== 'coupon' || $data['mode'] !== 'single_seller' || $data['funding_party'] !== 'merchant' || $data['eligible_product_ids'] !== []) { throw new DomainError('unsupported_incentive_adapter', 'Product-scoped, marketplace-funded, gift or shipping benefits need a separately verified adapter.', 422); }
        foreach (['starts_at', 'ends_at'] as $key) {
            if (!is_string($data[$key])) { throw new DomainError('invalid_rule_date', 'UTC ISO8601 dates are required.', 422); }
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $data[$key], new \DateTimeZone('UTC'));
            if (!$date || $date->format('Y-m-d\TH:i:s\Z') !== $data[$key]) { throw new DomainError('invalid_rule_date', 'UTC ISO8601 dates are required.', 422); }
        }
        if (strcmp($data['ends_at'], $data['starts_at']) <= 0) { throw new DomainError('invalid_rule_schedule', 'Rule end must follow start.', 422); }
        if (!is_string($data['stacking_group']) || !preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $data['stacking_group']) || !is_int($data['priority']) || $data['priority'] < 0 || $data['priority'] > 1000) { throw new DomainError('invalid_stacking_group', 'A stable stacking group and bounded priority are required.', 422); }
        $this->data = $data;
    }
}
