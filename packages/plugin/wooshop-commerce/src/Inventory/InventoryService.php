<?php
declare(strict_types=1);

namespace WooShop\Commerce\Inventory;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Marketplace\Records;
use WooShop\Commerce\Support\DomainError;

/** Row locks and conditional database updates are the stock authority, never cache locks. */
final class InventoryService
{
    private Records $r;
    public function __construct(private Database $db, private ?CaptureVerifier $captures = null) { $this->r = new Records($db); }

    public function createPool(string $seller, string $origin, int $epoch, int $onHand = 0): array
    {
        if ($epoch < 1 || $onHand < 0 || $onHand > 1000000000 || !$origin || strlen($origin) > 80) {
            throw new DomainError('invalid_pool', 'Invalid pool origin, epoch or physical quantity.', 422);
        }
        return $this->db->transaction(function () use ($seller, $origin, $epoch, $onHand): array {
            $this->r->one('sellers', $seller);
            $id = Records::id();
            $this->r->insert('inventory_pools', ['id' => $id, 'seller_id' => $seller, 'origin_id' => $origin, 'on_hand' => $onHand, 'reserved' => 0, 'authority_epoch' => $epoch, 'state' => 'active', 'version' => 1, 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
            if ($onHand > 0) { $this->movement($id, 'opening:'.$id, 0, $epoch, $onHand, 0, 'opening_count', ''); }
            return $this->pool($id);
        });
    }

    public function pool(string $id): array
    {
        $p = $this->r->one('inventory_pools', $id);
        return ['pool_id' => $id, 'on_hand' => (int) $p['on_hand'], 'reserved' => (int) $p['reserved'], 'version' => (int) $p['version'], 'authority_epoch' => (int) $p['authority_epoch']];
    }

    public function adjust(string $poolId, int $delta, string $reason, int $epoch, int $version, int $actor, string $key, string $evidence = ''): array
    {
        if ($delta === 0 || abs($delta) > 1000000 || !in_array($reason, ['count_correction', 'received_supply', 'inspected_return', 'damage'], true) || strlen($evidence) > 200) {
            throw new DomainError('invalid_adjustment', 'Invalid adjustment quantity, reason or evidence.', 422);
        }
        if ($reason === 'inspected_return' && ($delta < 1 || !$evidence)) {
            throw new DomainError('inspection_required', 'Positive inspected restock requires independent inspection evidence.', 422);
        }
        return $this->r->command('inventory:'.$actor.':'.$poolId, $key, compact('delta', 'reason', 'epoch', 'version', 'evidence'), function () use ($poolId, $delta, $reason, $epoch, $version, $actor, $key, $evidence): array {
            $p = $this->r->one('inventory_pools', $poolId, true);
            Records::version($p, $version);
            $this->epoch($p, $epoch);
            if ($p['state'] !== 'active') { throw new DomainError('pool_quarantined', 'Reconcile the quarantined pool before adjusting.', 409); }
            if ($reason === 'inspected_return') { $this->claimInspectedReturn($p, $delta, $evidence); }
            $w = $this->db->wpdb();
            $n = $this->r->execute($w->prepare('UPDATE '.$this->db->table('inventory_pools').' SET on_hand=CAST(on_hand AS SIGNED)+%d,version=version+1,updated_at=%s WHERE id=%s AND version=%d AND authority_epoch=%d AND CAST(on_hand AS SIGNED)+%d>=reserved AND CAST(on_hand AS SIGNED)+%d<=1000000000', $delta, $this->db->now(), $poolId, $version, $epoch, $delta, $delta));
            if ($n !== 1) { throw new DomainError('stock_conflict', 'Adjustment would consume reserved stock or overflow.', 409); }
            $this->movement($poolId, 'adjust:'.$actor.':'.$key, $actor, $epoch, $delta, 0, $reason, $evidence);
            return $this->pool($poolId);
        });
    }

    /** Acquire a checkout group in deterministic pool order. Entire group rolls back on any failure. */
    public function reserveGroup(string $operation, array $lines, int $actor): array
    {
        if (!$lines || count($lines) > 100) { throw new DomainError('invalid_reservation', 'Reserve 1–100 lines.', 422); }
        $keys = [];
        foreach ($lines as $line) {
            if (!isset($line['pool_id'], $line['line_key'], $line['quantity'], $line['authority_epoch']) || !is_int($line['quantity']) || $line['quantity'] < 1 || $line['quantity'] > 10000 || isset($keys[$line['line_key']])) {
                throw new DomainError('invalid_reservation', 'Unique line keys and bounded positive quantities are required.', 422);
            }
            $keys[$line['line_key']] = true;
        }
        usort($lines, static fn (array $a, array $b): int => strcmp($a['pool_id'].'|'.$a['line_key'], $b['pool_id'].'|'.$b['line_key']));
        return $this->r->command('reservation:'.$actor, $operation, $lines, function () use ($operation, $lines, $actor): array {
            // Lock every seller before any pool; otherwise overlapping groups can deadlock
            // when one group owns seller S while another holds its next pool waiting for S.
            $sellerIds = [];
            foreach ($lines as $line) { $hint = $this->r->one('inventory_pools', $line['pool_id']); $sellerIds[$hint['seller_id']] = true; }
            $sellerIds = array_keys($sellerIds); sort($sellerIds, SORT_STRING);
            foreach ($sellerIds as $sellerId) {
                $seller = $this->r->one('sellers', $sellerId, true);
                if ($seller['state'] !== 'approved') { throw new DomainError('stock_unavailable', 'Seller is unavailable for new reservations.', 409); }
            }
            $result = [];
            foreach ($lines as $line) {
                $p = $this->r->one('inventory_pools', $line['pool_id'], true);
                $this->expirePool($p['id']);
                $p = $this->r->one('inventory_pools', $line['pool_id'], true);
                $this->epoch($p, (int) $line['authority_epoch']);
                if ($p['state'] !== 'active') {
                    throw new DomainError('stock_unavailable', 'Seller or inventory is unavailable for new reservations.', 409);
                }
                $w = $this->db->wpdb();
                $n = $this->r->execute($w->prepare('UPDATE '.$this->db->table('inventory_pools').' SET reserved=reserved+%d,version=version+1,updated_at=%s WHERE id=%s AND authority_epoch=%d AND state=%s AND on_hand>=reserved+%d', $line['quantity'], $this->db->now(), $p['id'], $p['authority_epoch'], 'active', $line['quantity']));
                if ($n !== 1) { throw new DomainError('insufficient_stock', 'Requested stock is no longer available.', 409); }
                $id = Records::id();
                $expires = gmdate('Y-m-d H:i:s', strtotime($this->db->now().' UTC') + 900);
                $this->r->insert('reservations', ['id' => $id, 'operation_key' => hash('sha256', $operation), 'line_key' => hash('sha256', $line['line_key']), 'pool_id' => $p['id'], 'quantity' => $line['quantity'], 'authority_epoch' => $p['authority_epoch'], 'actor_id' => $actor, 'state' => 'held', 'expires_at' => $expires, 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
                $this->movement($p['id'], 'hold:'.$id, $actor, (int) $p['authority_epoch'], 0, $line['quantity'], 'hold', $id);
                $result[] = ['id' => $id, 'pool_id' => $p['id'], 'quantity' => $line['quantity'], 'state' => 'held', 'expires_at' => $expires];
            }
            return $result;
        });
    }

    public function commit(string $reservation, string $orderId, string $captureReference): array
    {
        if ($this->captures === null) { throw new DomainError('verified_capture_unavailable', 'A verified payment adapter is required to commit stock.', 503); }
        $this->captures->requireCapture($orderId, $captureReference); // No transaction spans provider calls.
        if (!preg_match('/^[1-9][0-9]{0,18}$/D', $orderId)) { throw new DomainError('invalid_order', 'Invalid native order ID.', 422); }
        try { return $this->finish($reservation, 'committed', $orderId, $captureReference); }
        catch (DomainError $error) {
            if ($error->errorCode === 'reservation_expired') {
                // The capture remains captured. There is no fabricated refund or oversell to hide the race.
                $hold = $this->r->one('reservations', $reservation);
                $pool = $this->r->one('inventory_pools', $hold['pool_id']);
                $this->r->command('late-capture:'.$reservation, 'capture:'.hash('sha256', $captureReference), ['order_id' => $orderId, 'capture_reference' => $captureReference], function () use ($reservation, $orderId, $captureReference, $pool): array {
                    $case = (new EntityStore($this->db))->create('stock.exception', ['order_id' => $orderId, 'reservation_id' => $reservation, 'capture_reference' => $captureReference, 'payment_state' => 'captured', 'state' => 'paid_stock_exception', 'next_action' => 'Reconcile current stock and captured payment; use an approved adapter to safely re-reserve or request an idempotent refund.'], $pool['seller_id']);
                    return ['case_id' => $case['id']];
                });
                if (function_exists('wc_get_order') && ($order = wc_get_order((int) $orderId))) {
                    $order->update_meta_data('_wooshop_paid_stock_exception', true);
                    $order->update_meta_data('_wooshop_verified_payment_state', 'captured');
                    $order->set_status('on-hold');
                    $order->save();
                }
            }
            throw $error;
        }
    }

    public function release(string $reservation): array { return $this->finish($reservation, 'released'); }

    public function expire(int $limit = 100): int
    {
        $w = $this->db->wpdb();
        $ids = $w->get_col($w->prepare('SELECT id FROM '.$this->db->table('reservations').' WHERE state=%s AND expires_at<=%s ORDER BY expires_at,id LIMIT %d', 'held', $this->db->now(), max(1, min(1000, $limit))));
        $count = 0;
        foreach ($ids as $id) {
            $result = $this->finish($id, 'expired');
            if ($result['state'] === 'expired') { ++$count; }
        }
        return $count;
    }

    private function finish(string $id, string $target, ?string $order = null, string $evidence = ''): array
    {
        return $this->db->transaction(function () use ($id, $target, $order, $evidence): array {
            // All paths lock pool before reservation to avoid acquire/finish deadlocks.
            $hint = $this->r->one('reservations', $id);
            $p = $this->r->one('inventory_pools', $hint['pool_id'], true);
            $hold = $this->r->one('reservations', $id, true);
            if ($hold['state'] !== 'held') {
                if ($target === 'committed' && $hold['state'] === 'expired') { throw new DomainError('reservation_expired', 'Payment arrived after stock expiry; reconcile the captured payment.', 409); }
                if ($hold['state'] === $target && ($target !== 'committed' || $hold['order_id'] === $order)) {
                    return ['id' => $id, 'state' => $hold['state'], 'order_id' => $hold['order_id']];
                }
                throw new DomainError('reservation_state_conflict', 'Reservation has already reached a different terminal state.', 409);
            }
            $this->epoch($p, (int) $hold['authority_epoch']);
            if ($target === 'committed' && $hold['expires_at'] <= $this->db->now()) {
                throw new DomainError('reservation_expired', 'Payment arrived after the stock deadline; reconcile before capture fulfillment.', 409);
            }
            if ($target === 'expired' && $hold['expires_at'] > $this->db->now()) { throw new DomainError('hold_not_due', 'Reservation has not expired.', 409); }
            $quantity = (int) $hold['quantity'];
            $sold = $target === 'committed' ? $quantity : 0;
            $w = $this->db->wpdb();
            $n = $this->r->execute($w->prepare('UPDATE '.$this->db->table('inventory_pools').' SET on_hand=on_hand-%d,reserved=reserved-%d,version=version+1,updated_at=%s WHERE id=%s AND reserved>=%d AND on_hand>=%d AND authority_epoch=%d', $sold, $quantity, $this->db->now(), $p['id'], $quantity, $sold, $p['authority_epoch']));
            if ($n !== 1) { throw new DomainError('inventory_drift', 'Inventory invariants failed; reconciliation is required.', 409); }
            $this->r->execute($w->prepare('UPDATE '.$this->db->table('reservations').' SET state=%s,order_id=NULLIF(%s,\'\'),version=version+1,updated_at=%s WHERE id=%s AND state=%s', $target, $order ?? '', $this->db->now(), $id, 'held'));
            $this->movement($p['id'], $target.':'.$id, (int) $hold['actor_id'], (int) $p['authority_epoch'], -$sold, -$quantity, $target, $evidence ?: $id);
            return ['id' => $id, 'state' => $target, 'order_id' => $order];
        });
    }

    /** Persist quarantine before reporting mismatch; do not roll it back with an exception. */
    public function reconcile(string $id, int $observedPhysical, string $evidence): array
    {
        if ($observedPhysical < 0 || !$evidence || strlen($evidence) > 200) { throw new DomainError('invalid_reconciliation', 'Physical count and evidence are required.', 422); }
        return $this->db->transaction(function () use ($id, $observedPhysical, $evidence): array {
            $p = $this->r->one('inventory_pools', $id, true);
            $w = $this->db->wpdb();
            $held = (int) $w->get_var($w->prepare('SELECT COALESCE(SUM(quantity),0) FROM '.$this->db->table('reservations').' WHERE pool_id=%s AND state=%s', $id, 'held'));
            $matches = $observedPhysical === (int) $p['on_hand'] && $held === (int) $p['reserved'];
            if (!$matches) {
                $this->r->execute($w->prepare('UPDATE '.$this->db->table('inventory_pools').' SET state=%s,version=version+1,updated_at=%s WHERE id=%s', 'quarantined', $this->db->now(), $id));
                (new EntityStore($this->db))->create('inventory.drift', ['pool_id' => $id, 'state' => 'open', 'authority_epoch' => (int) $p['authority_epoch'], 'recorded_on_hand' => (int) $p['on_hand'], 'observed_on_hand' => $observedPhysical, 'reserved' => (int) $p['reserved'], 'held' => $held, 'evidence_ref' => $evidence], $p['seller_id']);
            }
            return ['pool_id' => $id, 'matches' => $matches, 'state' => $matches ? $p['state'] : 'quarantined', 'recorded_on_hand' => (int) $p['on_hand'], 'observed_on_hand' => $observedPhysical, 'held' => $held, 'evidence_ref' => $evidence];
        });
    }

    private function epoch(array $pool, int $epoch): void
    {
        if ((int) $pool['authority_epoch'] !== $epoch) { throw new DomainError('authority_epoch_conflict', 'The inventory authority changed; reload.', 409); }
    }

    private function expirePool(string $pool): void
    {
        $w = $this->db->wpdb();
        $ids = $w->get_col($w->prepare('SELECT id FROM '.$this->db->table('reservations').' WHERE pool_id=%s AND state=%s AND expires_at<=%s ORDER BY id FOR UPDATE', $pool, 'held', $this->db->now()));
        foreach ($ids as $id) { $this->finish($id, 'expired'); }
    }

    private function claimInspectedReturn(array $pool, int $quantity, string $returnId): void
    {
        $entities = new EntityStore($this->db);
        $case = $entities->get('return', $returnId);
        $data = $case['data'] ?? [];
        if (!$case || ($data['seller_id'] ?? '') !== $pool['seller_id'] || ($data['restock_pool_id'] ?? '') !== $pool['id'] || ($data['state'] ?? '') !== 'inspected' || ($data['disposition'] ?? '') !== 'resalable' || (int) ($data['inspected_quantity'] ?? 0) - (int) ($data['restocked_quantity'] ?? 0) < $quantity || empty($data['inspection_evidence_ref'])) {
            throw new DomainError('inspection_required', 'The return must have an authorized resalable inspection for this pool and remaining quantity.', 409);
        }
        $data['restocked_quantity'] = (int) ($data['restocked_quantity'] ?? 0) + $quantity;
        $entities->update('return', $returnId, $case['version'], $data);
    }

    private function movement(string $pool, string $operation, int $actor, int $epoch, int $physical, int $reserved, string $reason, string $evidence): void
    {
        $this->r->insert('inventory_movements', ['id' => Records::id(), 'operation_key' => hash('sha256', $operation), 'pool_id' => $pool, 'actor_id' => $actor, 'authority_epoch' => $epoch, 'on_hand_delta' => $physical, 'reserved_delta' => $reserved, 'reason' => $reason, 'evidence_ref' => substr($evidence, 0, 200), 'created_at' => $this->db->now()]);
    }
}
