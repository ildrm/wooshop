<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Finance\Money;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class AllocationService
{
    private Records $r;
    public function __construct(private Database $db, private EntityStore $entities) { $this->r = new Records($db); }

    /** Persist an adapter's complete allocation plan after checking it against the authoritative WC order. */
    public function create(string $orderId, array $plan, string $key): array
    {
        Input::nativeId($orderId);
        $order = wc_get_order((int) $orderId);
        if (!$order) { throw new DomainError('not_found', 'Order not found.', 404); }
        if (!$plan || count($plan) > 100) { throw new DomainError('invalid_plan', 'A bounded allocation plan is required.', 422); }
        $items = $order->get_items('line_item');
        $seen = [];
        $groups = [];
        $currency = $order->get_currency();
        $exponent = (int) wc_get_price_decimals();
        $total = new Money('0', $currency, $exponent);
        $componentTotals = array_fill_keys(['net', 'tax', 'shipping', 'shipping_tax', 'fees', 'fees_tax'], 0);
        foreach ($plan as $line) {
            Input::fields($line, ['order_item_id', 'seller_id', 'origin_id', 'fulfillment_type', 'amounts'], ['order_item_id', 'seller_id', 'origin_id', 'fulfillment_type', 'amounts']);
            $itemId = Input::nativeId($line['order_item_id']);
            if (!isset($items[(int) $itemId]) || isset($seen[$itemId])) { throw new DomainError('allocation_overlap', 'Every purchased line must be assigned exactly once.', 422); }
            $item = $items[(int) $itemId];
            $this->r->one('sellers', $line['seller_id']);
            Input::text($line['origin_id'], 80);
            if (!in_array($line['fulfillment_type'], ['physical', 'digital', 'service'], true)) { throw new DomainError('invalid_fulfillment', 'Unsupported fulfillment type.', 422); }
            $snapshot = $item->get_meta('_wooshop_purchase_snapshot', true);
            if (!$snapshot || !hash_equals((string) $item->get_meta('_wooshop_purchase_snapshot_digest', true), hash('sha256', Records::canonical($snapshot)))) { throw new DomainError('snapshot_required', 'An intact immutable purchase snapshot is required.', 409); }
            if (($snapshot['seller_id'] ?? null) !== $line['seller_id'] || ($snapshot['authority_mode'] ?? '') !== 'marketplace_plugin') { throw new DomainError('snapshot_scope_conflict', 'The allocation must preserve purchased seller and authority.', 422); }
            $amounts = $line['amounts'];
            Input::fields($amounts, ['net', 'tax', 'shipping', 'shipping_tax', 'fees', 'fees_tax'], ['net', 'tax', 'shipping', 'shipping_tax', 'fees', 'fees_tax']);
            $lineTotal = new Money('0', $currency, $exponent);
            foreach ($amounts as $component => $value) {
                $money = Money::from($value);
                if ($money->minor < 0) { throw new DomainError('negative_allocation', 'Allocation amounts must be nonnegative.', 422); }
                $lineTotal = $lineTotal->add($money);
                $componentTotals[$component] += $money->minor;
            }
            if (Money::from($amounts['net'])->minor !== Money::decimal((string) $item->get_total(), $currency, $exponent)->minor || Money::from($amounts['tax'])->minor !== Money::decimal((string) $item->get_total_tax(), $currency, $exponent)->minor) { throw new DomainError('allocation_amount_conflict', 'Allocated goods and tax must match the original WooCommerce line.', 422); }
            $nativeQuantity = $item->get_quantity();
            if (!is_numeric($nativeQuantity) || (int) $nativeQuantity != $nativeQuantity) { throw new DomainError('unsupported_quantity', 'Fractional quantities require a separately verified adapter.', 422); }
            $quantity = Input::quantity((int) $nativeQuantity);
            $groupKey = $line['seller_id'].'|'.$line['origin_id'].'|'.$line['fulfillment_type'];
            if (!isset($groups[$groupKey])) { $groups[$groupKey] = ['seller_id' => $line['seller_id'], 'origin_id' => $line['origin_id'], 'fulfillment_type' => $line['fulfillment_type'], 'lines' => [], 'amounts' => [], 'total' => new Money('0', $currency, $exponent)]; }
            $groups[$groupKey]['lines'][] = ['order_item_id' => $itemId, 'quantity' => $quantity, 'product_id' => (string) $item->get_product_id(), 'variation_id' => $item->get_variation_id() ? (string) $item->get_variation_id() : null];
            $groups[$groupKey]['amounts'][$itemId] = $amounts;
            $groups[$groupKey]['total'] = $groups[$groupKey]['total']->add($lineTotal);
            $total = $total->add($lineTotal);
            $seen[$itemId] = true;
        }
        if (count($seen) !== count($items) || $total->minor !== Money::decimal((string) $order->get_total(), $currency, $exponent)->minor) { throw new DomainError('allocation_not_reconciled', 'Allocated quantities and all components must reconcile exactly to the customer order.', 422); }
        $fee = $feeTax = 0;
        foreach ($order->get_items('fee') as $item) {
            $fee += Money::decimal((string) $item->get_total(), $currency, $exponent)->minor;
            $feeTax += Money::decimal((string) $item->get_total_tax(), $currency, $exponent)->minor;
        }
        if ($componentTotals['shipping'] !== Money::decimal((string) $order->get_shipping_total(), $currency, $exponent)->minor || $componentTotals['shipping_tax'] !== Money::decimal((string) $order->get_shipping_tax(), $currency, $exponent)->minor || $componentTotals['fees'] !== $fee || $componentTotals['fees_tax'] !== $feeTax) { throw new DomainError('allocation_component_conflict', 'Shipping, fee and tax components must independently reconcile to their WooCommerce authority.', 422); }
        return $this->r->command('allocate:'.$orderId, $key, $plan, function () use ($orderId, $groups): array {
            $result = [];
            foreach ($groups as $group) {
                $id = Records::id();
                $data = ['lines' => $group['lines'], 'amounts' => $group['amounts'], 'total' => $group['total']->jsonSerialize(), 'shipped_quantities' => [], 'return_quantities' => []];
                $this->r->insert('allocations', ['id' => $id, 'order_id' => $orderId, 'seller_id' => $group['seller_id'], 'origin_id' => $group['origin_id'], 'fulfillment_type' => $group['fulfillment_type'], 'fulfillment_state' => 'allocated', 'data' => Records::json($data), 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
                $result[] = $this->project($this->r->one('allocations', $id));
            }
            return $result;
        });
    }

    public function shipment(array $body, int $actor, string $key): array
    {
        Input::fields($body, ['allocation_id', 'line_quantities', 'carrier_code', 'tracking_reference'], ['allocation_id', 'line_quantities', 'carrier_code']);
        Input::text($body['carrier_code'], 80);
        if (isset($body['tracking_reference'])) { Input::text($body['tracking_reference'], 200); }
        if (!is_array($body['line_quantities']) || !$body['line_quantities'] || count($body['line_quantities']) > 100) { throw new DomainError('invalid_shipment', 'A shipment requires 1–100 line quantities.', 400); }
        return $this->r->command('shipment:'.$actor, $key, $body, function () use ($body, $actor): array {
            $allocation = $this->r->one('allocations', $body['allocation_id'], true);
            $order = wc_get_order((int) $allocation['order_id']);
            if (!$order || !$order->is_paid() || in_array($allocation['fulfillment_state'], ['cancelled', 'blocked', 'delivered'], true)) { throw new DomainError('shipment_state_conflict', 'Paid, open allocations are required for fulfillment.', 409); }
            if ($allocation['fulfillment_type'] !== 'physical') { throw new DomainError('shipping_not_required', 'Digital and service allocations do not create physical packages.', 422); }
            $data = json_decode($allocation['data'], true, 512, JSON_THROW_ON_ERROR);
            $allowed = array_column($data['lines'], 'quantity', 'order_item_id');
            $seen = [];
            foreach ($body['line_quantities'] as $line) {
                Input::fields($line, ['order_item_id', 'quantity'], ['order_item_id', 'quantity']);
                $item = Input::nativeId($line['order_item_id']);
                $q = Input::quantity($line['quantity']);
                if (isset($seen[$item]) || !isset($allowed[$item]) || ($data['shipped_quantities'][$item] ?? 0) + $q > $allowed[$item]) { throw new DomainError('shipment_quantity_conflict', 'Shipment exceeds remaining assigned quantities.', 409); }
                $data['shipped_quantities'][$item] = ($data['shipped_quantities'][$item] ?? 0) + $q;
                $seen[$item] = true;
            }
            $shipment = $this->entities->create('shipment', ['allocation_id' => $allocation['id'], 'seller_id' => $allocation['seller_id'], 'line_quantities' => $body['line_quantities'], 'carrier_code' => $body['carrier_code'], 'tracking_reference' => $body['tracking_reference'] ?? null, 'tracking_url' => null, 'state' => 'created', 'created_by' => (string) $actor], $allocation['seller_id']);
            $w = $this->db->wpdb();
            $this->r->execute($w->prepare('UPDATE '.$this->db->table('allocations').' SET data=%s,fulfillment_state=%s,version=version+1,updated_at=%s WHERE id=%s', Records::json($data), 'picking', $this->db->now(), $allocation['id']));
            return ['id' => $shipment['id'], 'allocation_id' => $allocation['id'], 'state' => 'created', 'tracking_url' => null, 'version' => 1];
        });
    }

    public function requestReturn(array $body, int $actor, string $key, ?string $scopedSeller = null): array
    {
        Input::fields($body, ['order_id', 'order_item_id', 'quantity', 'reason_code', 'evidence_refs'], ['order_id', 'order_item_id', 'quantity', 'reason_code']);
        $orderId = Input::nativeId($body['order_id']);
        $itemId = Input::nativeId($body['order_item_id']);
        $quantity = Input::quantity($body['quantity']);
        Input::text($body['reason_code'], 80);
        if (isset($body['evidence_refs'])) {
            if (!is_array($body['evidence_refs']) || count($body['evidence_refs']) > 10) { throw new DomainError('invalid_evidence', 'Evidence references are bounded.', 400); }
            foreach ($body['evidence_refs'] as $ref) { Input::text($ref, 200); }
        }
        $order = wc_get_order((int) $orderId);
        if (!$order || !$order->is_paid()) { throw new DomainError('not_found', 'Purchased order not found.', 404); }
        $w = $this->db->wpdb();
        $allocations = $w->get_results($w->prepare('SELECT * FROM '.$this->db->table('allocations').' WHERE order_id=%s ORDER BY id', $orderId), ARRAY_A);
        $found = null;
        foreach ($allocations as $allocation) {
            $data = json_decode($allocation['data'], true);
            foreach ($data['lines'] as $line) { if ($line['order_item_id'] === $itemId) { $found = $allocation; break 2; } }
        }
        if (!$found || ($scopedSeller !== null ? $found['seller_id'] !== $scopedSeller : (int) $order->get_customer_id() !== $actor)) { throw new DomainError('not_found', 'Purchased item not found.', 404); }
        return $this->r->command('return:'.$actor, $key, $body, function () use ($found, $body, $quantity, $itemId, $orderId, $actor): array {
            $allocation = $this->r->one('allocations', $found['id'], true);
            $data = json_decode($allocation['data'], true, 512, JSON_THROW_ON_ERROR);
            $allowed = array_column($data['lines'], 'quantity', 'order_item_id');
            if (($data['return_quantities'][$itemId] ?? 0) + $quantity > $allowed[$itemId]) { throw new DomainError('return_quantity_conflict', 'Return request exceeds the remaining purchased quantity.', 409); }
            $data['return_quantities'][$itemId] = ($data['return_quantities'][$itemId] ?? 0) + $quantity;
            $case = $this->entities->create('return', ['order_id' => $orderId, 'order_item_id' => $itemId, 'allocation_id' => $allocation['id'], 'seller_id' => $allocation['seller_id'], 'requested_by' => (string) $actor, 'quantity' => $quantity, 'reason_code' => $body['reason_code'], 'evidence_refs' => $body['evidence_refs'] ?? [], 'state' => 'requested', 'money_state' => 'not_requested', 'physical_state' => 'not_received', 'next_action' => 'Await authorized policy review; this request neither refunds nor restocks.'], $allocation['seller_id']);
            $this->r->execute($this->db->wpdb()->prepare('UPDATE '.$this->db->table('allocations').' SET data=%s,version=version+1,updated_at=%s WHERE id=%s', Records::json($data), $this->db->now(), $allocation['id']));
            return ['id' => $case['id'], 'state' => 'requested', 'order_id' => $orderId, 'version' => 1, 'next_action' => $case['data']['next_action']];
        });
    }

    public function project(array $a): array
    {
        $d = json_decode($a['data'], true, 512, JSON_THROW_ON_ERROR);
        return ['id' => $a['id'], 'order_id' => (string) $a['order_id'], 'seller_id' => $a['seller_id'], 'fulfillment_state' => $a['fulfillment_state'], 'lines' => $d['lines'], 'total' => $d['total'], 'version' => (int) $a['version']];
    }
}
