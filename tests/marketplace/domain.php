<?php
declare(strict_types=1);
// Pure-domain verification only. Does not claim WooCommerce or payment-provider integration.
spl_autoload_register(static function (string $name): void {
    $prefix = 'WooShop\\Commerce\\';
    if (str_starts_with($name, $prefix)) {
        $file = dirname(__DIR__, 2).'/packages/plugin/wooshop-commerce/src/'.str_replace('\\', '/', substr($name, strlen($prefix))).'.php';
        if (is_file($file)) { require_once $file; }
    }
});
use WooShop\Commerce\Finance\Allocation;
use WooShop\Commerce\Finance\Ledger;
use WooShop\Commerce\Finance\Money;
use WooShop\Commerce\Finance\CommissionPolicy;
use WooShop\Commerce\Finance\RefundAllocation;
use WooShop\Commerce\Marketplace\ActivationGate;
use WooShop\Commerce\Marketplace\Input;
use WooShop\Commerce\Marketplace\OfferService;
use WooShop\Commerce\Support\DomainError;

$checks = 0;
function check(bool $condition, string $message): void { global $checks; ++$checks; if (!$condition) { throw new RuntimeException($message); } }
function rejects(callable $call, string $code): void { try { $call(); } catch (DomainError $e) { check($e->errorCode === $code, 'Expected '.$code.' got '.$e->errorCode); return; } throw new RuntimeException('Expected rejection '.$code); }
$m = static fn (string $n): Money => new Money($n, 'USD', 2);
check(Money::decimal('0.01', 'USD', 2)->minor === 1, 'Exact cent parse');
check(Money::decimal('-12.30', 'USD', 2)->decimalString() === '-12.30', 'Exact signed decimal roundtrip');
check(Money::decimal('100.0000', 'USD', 2)->minor === 10000, 'Harmless zero precision accepted');
check($m('9000000000000000')->decimalString() === '90000000000000.00', 'Maximum exact amount');
rejects(fn () => $m('9000000000000001'), 'invalid_money');
rejects(fn () => Money::from(['amount_minor' => 100, 'currency' => 'USD', 'exponent' => 2]), 'invalid_money');
rejects(fn () => Money::decimal('0.001', 'USD', 2), 'rounding_required');
rejects(fn () => $m('1')->add(new Money('1', 'EUR', 2)), 'currency_mismatch');
rejects(fn () => $m('9000000000000000')->add($m('1')), 'invalid_money');
$shares = Allocation::discount($m('1500'), ['A' => $m('10000'), 'B' => $m('5000')]);
check($shares['A']->minor === 1000 && $shares['B']->minor === 500, 'Worked seller coupon allocation');
$shares = Allocation::discount($m('1'), ['c' => $m('1'), 'a' => $m('1'), 'b' => $m('1')]);
check($shares['a']->minor === 1 && $shares['b']->minor === 0 && $shares['c']->minor === 0, 'Stable tie independent of input order');
$shares = Allocation::discount($m('9000000000000000'), ['a' => $m('4500000000000000'), 'b' => $m('4500000000000000')]);
check($shares['a']->minor === 4500000000000000 && $shares['b']->minor === 4500000000000000, 'Large rational intermediate avoids integer overflow');
rejects(fn () => Allocation::discount($m('5'), ['a' => $m('4')]), 'discount_exceeds_goods');
mt_srand(2751);
for ($i = 0; $i < 250; ++$i) {
    $weights = ['a' => $m((string) mt_rand(0, 100000)), 'b' => $m((string) mt_rand(0, 100000)), 'c' => $m((string) mt_rand(0, 100000))];
    $sum = array_sum(array_map(static fn (Money $v): int => $v->minor, $weights));
    $discount = mt_rand(0, $sum);
    $result = Allocation::discount($m((string) $discount), $weights);
    check(array_sum(array_map(static fn (Money $v): int => $v->minor, $result)) === $discount, 'Allocation conservation');
    foreach ($weights as $key => $weight) { check($result[$key]->minor >= 0 && $result[$key]->minor <= $weight->minor, 'Allocation cannot exceed eligible value'); }
}
$sellerA = 'a0000000-0000-4000-8000-000000000001';
$sellerB = 'b0000000-0000-4000-8000-000000000002';
$entry = static fn (string $account, string $side, string $n, string $seller = ''): array => ['account' => $account, 'side' => $side, 'amount' => ['amount_minor' => $n, 'currency' => 'USD', 'exponent' => 2], 'seller_id' => $seller];
$sale = [$entry('provider_clearing', 'D', '16500'), $entry('seller_payable', 'C', '9100', $sellerA), $entry('seller_payable', 'C', '4550', $sellerB), $entry('tax_payable', 'C', '1500'), $entry('platform_commission', 'C', '1350')];
check(Ledger::validate($sale)->minor === 16500, 'Two seller worked sale journal balances');
$refund = [$entry('seller_receivable', 'D', '4050', $sellerA), $entry('tax_payable', 'D', '450'), $entry('platform_commission', 'D', '450'), $entry('provider_clearing', 'C', '4950')];
check(Ledger::validate($refund)->minor === 4950, 'Post-payout refund uses explicit receivable, not overwritten payout');
$policy = new CommissionPolicy('synthetic-policy', 1, 1000, $m('0'), 'synthetic-example-only');
check($policy->calculate($m('9000'))->minor === 900, 'Versioned explicit goods-net commission');
check($policy->calculate($m('15'))->minor === 2, 'Explicit half-up commission rounding');
$original = ['order_item_id' => '123', 'quantity' => 2, 'allocated_amounts' => ['net' => $m('9000')->jsonSerialize(), 'tax' => $m('900')->jsonSerialize(), 'commission' => $m('900')->jsonSerialize(), 'shipping' => $m('1000')->jsonSerialize(), 'shipping_tax' => $m('100')->jsonSerialize()]];
$quote = RefundAllocation::quote($original, 0, 1, false);
check($quote['refund']['amount_minor'] === '4950' && $quote['seller_entitlement_reversal']['amount_minor'] === '4050', 'Refund uses frozen original unit tax/commission and excludes unapproved shipping');
check($quote['payment_state'] === 'not_submitted', 'Arithmetic quote cannot imply provider refund');
$quote2 = RefundAllocation::quote($original, 1, 1, true);
check($quote2['refund']['amount_minor'] === '5500', 'Original per-unit shipping refund is bounded');
rejects(fn () => RefundAllocation::quote($original, 1, 2, true), 'refund_quantity_conflict');
$bad = $sale; $bad[0]['amount']['amount_minor'] = '16499';
rejects(fn () => Ledger::validate($bad), 'unbalanced_journal');
$bad = $sale; $bad[1]['amount']['currency'] = 'EUR';
rejects(fn () => Ledger::validate($bad), 'currency_mismatch');
rejects(fn () => Ledger::validate([$entry('invented_balance', 'D', '1'), $entry('provider_clearing', 'C', '1')]), 'invalid_journal');
rejects(fn () => Input::fields(['price' => '1', 'seller_id' => 'foreign'], ['price']), 'invalid_fields');
rejects(fn () => Input::nativeId(12), 'invalid_native_id');
rejects(fn () => Input::quantity(0), 'invalid_quantity');
check(Input::text(str_repeat('ف', 120), 120) === str_repeat('ف', 120), 'Persian field limits count Unicode characters');
rejects(fn () => Input::text(str_repeat('ف', 121), 120), 'invalid_text');
check(OfferService::lineIdentity('site', '101', '102', 'offer-A', 'config', 'shipping') !== OfferService::lineIdentity('site', '101', '102', 'offer-B', 'config', 'shipping'), 'Same variation from two sellers has distinct cart identity');
check(OfferService::lineIdentity('site', '101', '102', 'offer-A', 'config', 'shipping') !== OfferService::lineIdentity('other-site', '101', '102', 'offer-A', 'config', 'shipping'), 'Cart identity binds site');
rejects(fn () => (new ActivationGate())->requireMarketplace(), 'marketplace_activation_blocked');
echo 'PASS '.$checks." pure domain assertions; provider and platform execution are separate.\n";
