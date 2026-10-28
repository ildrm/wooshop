<?php
declare(strict_types=1);
namespace WooShop\Commerce\Finance;
use WooShop\Commerce\Support\DomainError;

/** Calculates original frozen unit entitlements; it neither authorizes nor submits a refund. */
final class RefundAllocation
{
    public static function quote(array $snapshot, int $previousQuantity, int $requestedQuantity, bool $includeShipping): array
    {
        $quantity = $snapshot['quantity'] ?? 0;
        $itemKey = $snapshot['order_item_id'] ?? '';
        if (!is_int($quantity) || $quantity < 1 || $quantity > 10000 || !$itemKey || $previousQuantity < 0 || $requestedQuantity < 1 || $previousQuantity + $requestedQuantity > $quantity) { throw new DomainError('refund_quantity_conflict', 'Requested units exceed the remaining original purchase.', 409); }
        $source = $snapshot['allocated_amounts'] ?? [];
        $required = ['net', 'tax', 'commission', 'shipping', 'shipping_tax'];
        if (array_diff($required, array_keys($source))) { throw new DomainError('original_allocations_required', 'Original frozen goods, tax, commission and shipping allocations are required.', 409); }
        $unit = Money::from($source['net']);
        $amounts = [];
        foreach ($required as $component) {
            $money = Money::from($source[$component]);
            $unit->sameUnit($money);
            if ($money->minor < 0) { throw new DomainError('invalid_original_amount', 'Original allocation cannot be negative.', 422); }
            $remaining = 0;
            if ($includeShipping || !in_array($component, ['shipping', 'shipping_tax'], true)) {
                // Equal rational unit shares with stable original unit tie keys.
                [$floor, $remainder] = Allocation::multiplyDivide($money->minor, 1, $quantity);
                for ($i = $previousQuantity; $i < $previousQuantity + $requestedQuantity; ++$i) { $remaining += $floor + ($i < $remainder ? 1 : 0); }
            }
            $amounts[$component] = new Money((string) $remaining, $unit->currency, $unit->exponent);
        }
        $gross = $amounts['net']->add($amounts['tax'])->add($amounts['shipping'])->add($amounts['shipping_tax']);
        $seller = new Money((string) ($amounts['net']->minor + $amounts['shipping']->minor - $amounts['commission']->minor), $unit->currency, $unit->exponent);
        if ($seller->minor < 0) { throw new DomainError('invalid_original_amount', 'Original commission exceeds seller entitlement.', 422); }
        return ['order_item_id' => $itemKey, 'quantity' => $requestedQuantity, 'unit_range' => [$previousQuantity, $previousQuantity + $requestedQuantity - 1], 'amounts' => array_map(static fn (Money $m): array => $m->jsonSerialize(), $amounts), 'refund' => $gross->jsonSerialize(), 'seller_entitlement_reversal' => $seller->jsonSerialize(), 'payment_state' => 'not_submitted'];
    }
}
