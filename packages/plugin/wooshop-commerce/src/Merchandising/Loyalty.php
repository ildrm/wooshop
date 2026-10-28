<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

/** Immutable point movements; account rows serialize all economic changes, never store a writable balance. */
final class Loyalty
{
    private Database $db; private EntityStore $store; private ?LoyaltyPolicy $policy; private bool $enabled;
    public function __construct(Database $db, EntityStore $store, ?LoyaltyPolicy $policy = null, bool $enabled = false)
    {
        $this->db = $db; $this->store = $store; $this->policy = $policy; $this->enabled = $enabled && $policy !== null;
        if ($policy !== null) {
            $id = Input::stableId('loyalty_policy:' . $policy->data['id'] . ':' . $policy->data['version']);
            $existing = $store->get('loyalty_policy', $id);
            if ($existing && $existing['data'] !== $policy->data) { throw new DomainError('immutable_policy', 'Use a new policy revision for changed program terms.', 409); }
            if (!$existing) { $store->create('loyalty_policy', $policy->data, 'finance', $id); }
            $programId = Input::stableId('loyalty_program');
            if (!$store->get('loyalty_program', $programId)) { try { $store->create('loyalty_program', ['mode' => $this->enabled ? 'active' : 'disabled'], 'finance', $programId); } catch (DomainError $error) { if ($error->status !== 409) { throw $error; } } }
        }
    }

    private function actor(): string { if (get_current_user_id() < 1) { throw new DomainError('unauthenticated', 'Sign in to use your own rewards.', 401); } return 'user:' . get_current_user_id(); }
    private function manager(): bool { return current_user_can('commerce.ledger.adjust') || current_user_can('manage_woocommerce'); }
    private function assertOwner(string $owner): void { if ($owner !== 'user:' . get_current_user_id() && !$this->manager() && get_current_user_id() !== 0) { throw new DomainError('not_found', 'Reward resource unavailable.', 404); } }
    private function policy(): LoyaltyPolicy { if (!$this->enabled || !$this->policy || $this->mode() !== 'active') { throw new DomainError('loyalty_disabled', 'New rewards are disabled pending reviewed program policy.', 422); } return $this->policy; }
    private function mode(): string { $record = $this->latest('loyalty_program', Input::stableId('loyalty_program')); return $record['data']['mode'] ?? 'disabled'; }

    public function setMode(string $mode, int $revision): array
    {
        if (!$this->manager()) { throw new DomainError('forbidden', 'Program lifecycle permission is required.', 403); }
        if (!in_array($mode, ['active', 'drain', 'disabled'], true)) { throw new DomainError('invalid_mode', 'Choose active, drain or disabled.', 422); }
        if ($mode === 'active' && (!$this->enabled || !$this->policy)) { throw new DomainError('policy_required', 'Reviewed policy and the activation gate are required.', 422); }
        return $this->db->transaction(function () use ($mode, $revision): array {
        $id = Input::stableId('loyalty_program'); $record = $this->latest('loyalty_program', $id);
        if ($mode === 'disabled') {
            foreach ($this->all('reward_lot') as $lot) { $data = $lot['data']; if ($data['pending'] > 0 || $data['available'] > 0 || $data['held'] > 0) { throw new DomainError('reward_obligations_open', 'Use drain mode while point liabilities or issued redemptions remain.', 409); } }
        }
        if ($record) { Records::expect($record, $revision); return $this->store->update('loyalty_program', $id, $revision, ['mode' => $mode]); }
        if ($revision !== 1) { throw new DomainError('stale_revision', 'Initial lifecycle revision is 1.', 412); }
        return $this->store->create('loyalty_program', ['mode' => $mode], 'finance', $id);
        });
    }

    private function locked(string $owner, callable $operation)
    {
        return $this->db->transaction(function () use ($owner, $operation) {
            // Lifecycle transitions and account mutations take this lock in the same order.
            $this->latest('loyalty_program', Input::stableId('loyalty_program'));
            $id = Input::stableId('reward_account:' . $owner);
            $account = $this->store->get('reward_account', $id);
            if (!$account) {
                try { $this->store->create('reward_account', ['journal_revision' => 0], $owner, $id); }
                catch (DomainError $error) { if ($error->status !== 409) { throw $error; } }
            }
            $wpdb = $this->db->wpdb(); $table = $this->db->table('entities');
            $locked = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id=%s AND kind='reward_account' AND owner_id=%s FOR UPDATE", $id, $owner));
            if ($locked !== $id) { throw new DomainError('reward_storage_unavailable', 'Reward account locking is unavailable.', 503); }
            return $operation();
        });
    }

    private function all(string $kind, ?string $owner = null): array
    {
        $all = []; $cursor = null;
        for ($page = 0; $page < 1000; $page++) {
            $wpdb = $this->db->wpdb(); $table = $this->db->table('entities'); $where = 'kind=%s'; $arguments = [$kind];
            if ($owner !== null) { $where .= ' AND owner_id=%s'; $arguments[] = $owner; }
            if ($cursor !== null) { $where .= ' AND id>%s'; $arguments[] = $cursor; }
            $records = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY id LIMIT 100 FOR UPDATE", ...$arguments), ARRAY_A) ?: [];
            foreach ($records as &$row) { $row['data'] = json_decode($row['payload'], true, 64, JSON_THROW_ON_ERROR); $row['version'] = (int) $row['version']; unset($row['payload']); } unset($row);
            $all = array_merge($all, $records);
            if (count($records) < 100) { return $all; } $cursor = end($records)['id'];
        }
        throw new DomainError('reward_reconciliation_required', 'This account needs a paged reconciliation adapter before further mutation.', 503);
    }

    /** Current locking read avoids stale repeatable-read snapshots after waiting on another redemption. */
    private function latest(string $kind, string $id): ?array
    {
        $wpdb = $this->db->wpdb(); $table = $this->db->table('entities');
        $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE kind=%s AND id=%s FOR UPDATE", $kind, $id), ARRAY_A);
        if (!$record) { return null; } $record['data'] = json_decode($record['payload'], true, 64, JSON_THROW_ON_ERROR); $record['version'] = (int) $record['version']; unset($record['payload']); return $record;
    }

    private function append(string $owner, string $event, string $type, array $delta, array $source): array
    {
        $id = Input::stableId('reward_journal:' . $owner . ':' . $event);
        $data = ['event' => $event, 'type' => $type, 'delta' => array_replace(['pending' => 0, 'available' => 0, 'held' => 0, 'redeemed' => 0, 'expired' => 0], $delta), 'source' => $source, 'actor' => (string) get_current_user_id()];
        $existing = $this->latest('reward_journal', $id);
        if ($existing) { if ($existing['data']['type'] !== $type || $existing['data']['delta'] !== $data['delta'] || $existing['data']['source'] !== $source) { throw new DomainError('reward_event_conflict', 'The source event was reused with different economic input.', 409); } return $existing; }
        $record = $this->store->create('reward_journal', $data, $owner, $id);
        $account = $this->latest('reward_account', Input::stableId('reward_account:' . $owner));
        $this->store->update('reward_account', $account['id'], $account['version'], ['journal_revision' => $account['data']['journal_revision'] + 1]);
        return $record;
    }

    private function balanceFor(string $owner): array
    {
        $balance = ['pending' => 0, 'available' => 0, 'held' => 0, 'redeemed' => 0, 'expired' => 0];
        foreach ($this->all('reward_journal', $owner) as $entry) { foreach ($balance as $bucket => $value) { $delta = $entry['data']['delta'][$bucket]; if (!is_int($delta) || abs($delta) > 1000000000 || abs($value) > PHP_INT_MAX - abs($delta)) { throw new DomainError('reward_journal_invalid', 'Reward journal integrity needs review.', 503); } $balance[$bucket] += $delta; } }
        return $balance + ['restricted' => $balance['available'] < 0];
    }

    public function balance(): array { $owner = $this->actor(); return $this->locked($owner, function () use ($owner): array { return $this->balanceFor($owner); }); }
    public function availability(): array { return ['accepting_new' => $this->enabled && $this->policy !== null && $this->mode() === 'active', 'mode' => $this->mode(), 'terms_uri' => $this->policy ? $this->policy->data['terms_uri'] : null]; }
    public function redemptions(): array { return $this->store->find('reward_reservation', $this->actor(), 100); }

    private function paidOrder(string $id)
    {
        Input::id($id); $order = wc_get_order((int) $id);
        if (!$order || $order instanceof \WC_Order_Refund || $order->get_customer_id() < 1) { throw new DomainError('not_found', 'Eligible account order unavailable.', 404); }
        $this->assertOwner('user:' . $order->get_customer_id());
        if (!$order->is_paid() || !$order->get_date_paid()) { throw new DomainError('payment_unverified', 'A native paid order with payment date is required.', 422); }
        return $order;
    }

    public function earnPaidOrder(string $orderId): array
    {
        $policy = $this->policy(); $order = $this->paidOrder($orderId); $p = $policy->data;
        if (!in_array($order->get_status(), $p['qualifying_statuses'], true) || $order->get_currency() !== $p['currency'] || wc_get_price_decimals() !== $p['exponent']) { throw new DomainError('order_not_eligible', 'Order does not match this reviewed program policy.', 422); }
        $minor = (int) PriceHistory::minor((string) $order->get_total('edit'), $p['exponent']);
        if ($p['earn_basis'] === 'items_excluding_tax_shipping') { $minor -= (int) PriceHistory::minor((string) $order->get_shipping_total('edit'), $p['exponent']) + (int) PriceHistory::minor((string) $order->get_total_tax('edit'), $p['exponent']); }
        if ($minor < 0) { throw new DomainError('invalid_earn_basis', 'Eligible order amount is inconsistent.', 422); }
        $points = LoyaltyPolicy::proportional($minor, $p['earn_numerator'], $p['earn_denominator_minor']);
        $owner = 'user:' . $order->get_customer_id(); $earnedAt = $order->get_date_paid()->getTimestamp();
        return $this->locked($owner, function () use ($owner, $orderId, $minor, $points, $p, $earnedAt): array {
            $this->policy();
            $id = Input::stableId('reward_lot:' . $orderId);
            $existing = $this->latest('reward_lot', $id);
            if ($existing) { return ['lot_id' => $id, 'journal_id' => $existing['data']['earn_journal'], 'points' => $existing['data']['initial_points']]; }
            $entry = $this->append($owner, 'earn:' . $orderId, 'earn_pending', ['pending' => $points], ['order_id' => $orderId, 'policy_id' => $p['id'], 'policy_version' => $p['version'], 'basis_minor' => (string) $minor]);
            $this->store->create('reward_lot', ['order_id' => $orderId, 'policy_id' => $p['id'], 'policy_version' => $p['version'], 'initial_points' => $points, 'basis_minor' => (string) $minor, 'refunded_minor' => '0', 'refunded_points' => 0, 'pending' => $points, 'available' => 0, 'held' => 0, 'redeemed' => 0, 'expired' => 0, 'available_at' => $earnedAt + $p['pending_seconds'], 'expires_at' => $earnedAt + $p['expiry_seconds'], 'earn_journal' => $entry['id']], $owner, $id);
            return ['lot_id' => $id, 'journal_id' => $entry['id'], 'points' => $points];
        });
    }

    private function sourcePolicy(array $data): LoyaltyPolicy
    {
        $record = $this->store->get('loyalty_policy', Input::stableId('loyalty_policy:' . $data['policy_id'] . ':' . $data['policy_version']));
        if (!$record) { throw new DomainError('original_policy_unavailable', 'Original reward terms need recovery.', 503); }
        return new LoyaltyPolicy($record['data']);
    }

    public function releaseDue(): array
    {
        $owner = $this->actor(); return $this->locked($owner, function () use ($owner): array { return $this->settleDue($owner); });
    }

    private function settleDue(string $owner): array
    {
        $result = ['released' => 0, 'expired' => 0, 'notice_blocked' => 0];
        foreach ($this->all('reward_reservation', $owner) as $reservation) { if ($reservation['data']['state'] === 'held' && $reservation['data']['order_id'] === null && $reservation['data']['expires_at'] <= time()) { $this->releaseReservation($reservation, 'expired'); } }
        foreach ($this->all('reward_lot', $owner) as $lot) {
            $data = $lot['data']; $policy = $this->sourcePolicy($data)->data;
            if ($data['expires_at'] <= time() && ($data['pending'] > 0 || $data['available'] > 0)) {
                if ($policy['expiry_notice_required']) { $result['notice_blocked']++; continue; }
                $pending = $data['pending']; $available = max(0, $data['available']);
                $entry = $this->append($owner, 'expire:' . $lot['id'] . ':' . $lot['version'], 'expire', ['pending' => -$pending, 'available' => -$available, 'expired' => $pending + $available], ['lot_id' => $lot['id'], 'policy_id' => $data['policy_id'], 'policy_version' => $data['policy_version']]);
                $data['pending'] = 0; $data['available'] -= $available; $data['expired'] += $pending + $available; $result['expired'] += $pending + $available;
                $this->store->update('reward_lot', $lot['id'], $lot['version'], $data);
            } elseif ($data['available_at'] <= time() && $data['pending'] > 0) {
                $points = $data['pending']; $this->append($owner, 'release:' . $lot['id'], 'release', ['pending' => -$points, 'available' => $points], ['lot_id' => $lot['id'], 'policy_id' => $data['policy_id'], 'policy_version' => $data['policy_version']]);
                $data['pending'] = 0; $data['available'] += $points; $result['released'] += $points; $this->store->update('reward_lot', $lot['id'], $lot['version'], $data);
            }
        }
        return $result;
    }

    public function reserve(int $points, string $key): array
    {
        $p = $this->policy()->data; $owner = $this->actor(); Input::text($key, 16, 128);
        if ($points < $p['redemption_min_points'] || $points > $p['redemption_max_points'] || $points % $p['redemption_points_per_minor'] !== 0) { throw new DomainError('invalid_redemption', 'Points must match the reviewed redemption bounds and exact conversion.', 422); }
        return $this->locked($owner, function () use ($owner, $points, $key, $p): array {
            $this->policy();
            $id = Input::stableId('reward_reservation:' . $owner . ':' . $key); $existing = $this->latest('reward_reservation', $id);
            if ($existing) { if ($existing['data']['points'] !== $points) { throw new DomainError('idempotency_conflict', 'Redemption key was used with different points.', 409); } return $existing; }
            $this->settleDue($owner);
            $open = array_filter($this->all('reward_reservation', $owner), static fn (array $record): bool => $record['data']['state'] === 'held');
            if (count($open) >= $p['max_open_redemptions']) { throw new DomainError('redemption_limit', 'Complete or release an existing redemption first.', 422); }
            if ($this->balanceFor($owner)['available'] < $points) { throw new DomainError('insufficient_points', 'Available points are insufficient or restricted.', 409); }
            $lots = $this->all('reward_lot', $owner); usort($lots, static fn (array $a, array $b): int => $a['data']['expires_at'] <=> $b['data']['expires_at'] ?: strcmp($a['id'], $b['id']));
            $needed = $points; $allocations = [];
            foreach ($lots as $lot) {
                $data = $lot['data']; $take = min($needed, max(0, $data['available'])); if ($take < 1) { continue; }
                $data['available'] -= $take; $data['held'] += $take; $this->store->update('reward_lot', $lot['id'], $lot['version'], $data); $allocations[] = ['lot_id' => $lot['id'], 'points' => $take]; $needed -= $take; if ($needed === 0) { break; }
            }
            if ($needed !== 0) { throw new DomainError('reward_reconciliation_required', 'Available journal and source lots disagree.', 503); }
            $this->append($owner, 'reserve:' . $id, 'reserve', ['available' => -$points, 'held' => $points], ['reservation_id' => $id, 'policy_id' => $p['id'], 'policy_version' => $p['version']]);
            return $this->store->create('reward_reservation', ['points' => $points, 'allocations' => $allocations, 'policy_id' => $p['id'], 'policy_version' => $p['version'], 'amount_minor' => (string) intdiv($points, $p['redemption_points_per_minor']), 'currency' => $p['currency'], 'exponent' => $p['exponent'], 'state' => 'held', 'expires_at' => time() + $p['redemption_hold_seconds'], 'order_id' => null, 'coupon_id' => null], $owner, $id);
        });
    }

    private function ownReservation(string $id): array
    {
        $record = $this->latest('reward_reservation', Input::uuid($id));
        if (!$record || $record['owner_id'] !== $this->actor()) { throw new DomainError('not_found', 'Redemption unavailable.', 404); }
        return $record;
    }

    private function releaseReservation(array $reservation, string $reason): array
    {
        if ($reservation['data']['state'] !== 'held') { return $reservation; }
        $owner = $reservation['owner_id']; $points = $reservation['data']['points'];
        foreach ($reservation['data']['allocations'] as $allocation) { $lot = $this->latest('reward_lot', $allocation['lot_id']); $data = $lot['data']; $data['held'] -= $allocation['points']; $data['available'] += $allocation['points']; $this->store->update('reward_lot', $lot['id'], $lot['version'], $data); }
        $this->append($owner, 'release_reservation:' . $reservation['id'], 'release_reservation', ['held' => -$points, 'available' => $points], ['reservation_id' => $reservation['id'], 'reason' => $reason]);
        $data = $reservation['data']; $data['state'] = 'released'; $data['release_reason'] = $reason;
        return $this->store->update('reward_reservation', $reservation['id'], $reservation['version'], $data);
    }

    public function release(string $id): array
    {
        $record = $this->ownReservation($id);
        if ($record['data']['order_id'] !== null) { throw new DomainError('order_obligation_open', 'The attached order must fail or cancel before points can be released.', 409); }
        return $this->locked($record['owner_id'], function () use ($id): array { $current = $this->ownReservation($id); if ($current['data']['order_id'] !== null) { throw new DomainError('order_obligation_open', 'An attached order must fail or cancel before release.', 409); } return $this->releaseReservation($current, 'customer_cancelled'); });
    }

    public static function major(string $minor, int $exponent): string
    {
        if (!preg_match('/^(0|[1-9][0-9]{0,14})$/D', $minor) || $exponent < 0 || $exponent > 6) { throw new DomainError('invalid_reward_money', 'Invalid reward currency amount.', 422); }
        if ($exponent === 0) { return $minor; } $digits = str_pad($minor, $exponent + 1, '0', STR_PAD_LEFT);
        return substr($digits, 0, -$exponent) . '.' . substr($digits, -$exponent);
    }

    public function issueCoupon(string $id): array
    {
        $record = $this->ownReservation($id);
        return $this->locked($record['owner_id'], function () use ($id): array {
            $record = $this->ownReservation($id); $data = $record['data'];
            if ($data['state'] !== 'held' || $data['expires_at'] <= time()) { throw new DomainError('redemption_expired', 'This redemption is no longer available.', 409); }
            if ($data['coupon_id'] !== null) { return $record; }
            if ($data['currency'] !== get_woocommerce_currency() || $data['exponent'] !== wc_get_price_decimals()) { throw new DomainError('currency_changed', 'Store currency changed; release and requote this redemption.', 409); }
            $policy = $this->sourcePolicy($data)->data; $user = wp_get_current_user();
            $coupon = new \WC_Coupon(); $coupon->set_code('reward-' . bin2hex(random_bytes(16))); $coupon->set_discount_type('fixed_cart'); $coupon->set_amount(self::major($data['amount_minor'], $data['exponent']));
            $coupon->set_usage_limit(1); $coupon->set_usage_limit_per_user(1); $coupon->set_email_restrictions([$user->user_email]); $coupon->set_individual_use($policy['coupon_stacking'] === 'exclusive'); $coupon->set_date_expires($data['expires_at']);
            $coupon->add_meta_data('_wooshop_reward_reservation', $id, true); $coupon->add_meta_data('_wooshop_reward_policy', $data['policy_id'] . ':' . $data['policy_version'], true);
            $couponId = $coupon->save(); if (!$couponId) { throw new DomainError('coupon_unavailable', 'Native reward coupon could not be created.', 503); }
            $data['coupon_id'] = (string) $couponId; return $this->store->update('reward_reservation', $id, $record['version'], $data);
        });
    }

    public function couponValid(bool $valid, $coupon): bool
    {
        $id = $coupon->get_meta('_wooshop_reward_reservation', true); if (!$id) { return $valid; }
        $record = is_string($id) ? $this->store->get('reward_reservation', $id) : null;
        return $valid && $record && $record['owner_id'] === 'user:' . get_current_user_id() && $record['data']['state'] === 'held' && $record['data']['expires_at'] > time() && $record['data']['coupon_id'] === (string) $coupon->get_id() && $record['data']['currency'] === get_woocommerce_currency() && $record['data']['exponent'] === wc_get_price_decimals();
    }

    public function attachToOrder($order): void
    {
        if (!$order instanceof \WC_Order || $order->get_customer_id() < 1) { return; }
        $owner = 'user:' . $order->get_customer_id();
        $this->assertOwner($owner);
        $this->locked($owner, function () use ($order, $owner): void {
            foreach ($order->get_items('coupon') as $item) {
                $coupon = new \WC_Coupon($item->get_code()); $id = $coupon->get_meta('_wooshop_reward_reservation', true); if (!is_string($id) || !$id) { continue; }
                $record = $this->latest('reward_reservation', $id);
                if (!$record || $record['owner_id'] !== $owner || $record['data']['coupon_id'] !== (string) $coupon->get_id() || $record['data']['state'] !== 'held' || ($record['data']['order_id'] !== null && $record['data']['order_id'] !== (string) $order->get_id())) { throw new DomainError('invalid_reward_coupon', 'Reward coupon does not belong to this order.', 422); }
                $data = $record['data']; $data['order_id'] = (string) $order->get_id(); $this->store->update('reward_reservation', $id, $record['version'], $data);
            }
        });
    }

    public function consumePaidOrder(string $orderId): void
    {
        $order = $this->paidOrder($orderId); $owner = 'user:' . $order->get_customer_id();
        $this->locked($owner, function () use ($orderId, $owner): void {
            foreach ($this->all('reward_reservation', $owner) as $record) {
                $data = $record['data']; if ($data['order_id'] !== $orderId || $data['state'] !== 'held') { continue; }
                foreach ($data['allocations'] as $allocation) { $lot = $this->latest('reward_lot', $allocation['lot_id']); $lotData = $lot['data']; $lotData['held'] -= $allocation['points']; $lotData['redeemed'] += $allocation['points']; $this->store->update('reward_lot', $lot['id'], $lot['version'], $lotData); }
                $this->append($owner, 'consume:' . $record['id'], 'redeem', ['held' => -$data['points'], 'redeemed' => $data['points']], ['reservation_id' => $record['id'], 'order_id' => $orderId]);
                $data['state'] = 'consumed'; $this->store->update('reward_reservation', $record['id'], $record['version'], $data);
            }
        });
    }

    public function releaseFailedOrder(string $orderId): void
    {
        $order = wc_get_order((int) Input::id($orderId)); if (!$order || !in_array($order->get_status(), ['failed', 'cancelled'], true) || $order->get_customer_id() < 1) { return; }
        $owner = 'user:' . $order->get_customer_id(); $this->assertOwner($owner);
        $this->locked($owner, function () use ($owner, $orderId): void { foreach ($this->all('reward_reservation', $owner) as $record) { if ($record['data']['order_id'] === $orderId) { $this->releaseReservation($record, 'order_failed_or_cancelled'); } } });
    }

    public function refund(string $refundId): array
    {
        $refund = wc_get_order((int) Input::id($refundId)); if (!$refund instanceof \WC_Order_Refund) { throw new DomainError('not_found', 'Native refund unavailable.', 404); }
        $order = wc_get_order($refund->get_parent_id()); if (!$order || $order->get_customer_id() < 1) { throw new DomainError('not_found', 'Account order unavailable.', 404); }
        $owner = 'user:' . $order->get_customer_id(); $this->assertOwner($owner);
        return $this->locked($owner, function () use ($owner, $refund, $refundId, $order): array {
            $eventId = Input::stableId('reward_refund:' . $refundId); $existing = $this->latest('reward_refund', $eventId); if ($existing) { return $existing['data']; }
            $lotId = Input::stableId('reward_lot:' . $order->get_id()); $lot = $this->latest('reward_lot', $lotId);
            if (!$lot) { return ['reversed_points' => 0, 'state' => 'no_earned_points']; }
            $policy = $this->sourcePolicy($lot['data'])->data;
            if ($order->get_currency() !== $policy['currency'] || $refund->get_currency() !== $policy['currency']) { throw new DomainError('refund_currency_mismatch', 'Refund currency differs from the original reward policy.', 422); }
            $minor = (int) PriceHistory::minor((string) $refund->get_amount('edit'), $policy['exponent']);
            if ($policy['earn_basis'] === 'items_excluding_tax_shipping') {
                $items = $refund->get_items('line_item'); if (!$items && $minor > 0) { throw new DomainError('refund_allocation_required', 'Item-level refund allocation is required under the original reward basis.', 422); }
                $minor = 0; foreach ($items as $item) { $total = (string) $item->get_total('edit'); $minor += (int) PriceHistory::minor(ltrim($total, '-'), $policy['exponent']); }
            }
            foreach ($this->all('reward_reservation', $owner) as $reservation) { if ($reservation['data']['state'] !== 'held') { continue; } foreach ($reservation['data']['allocations'] as $allocation) { if ($allocation['lot_id'] === $lotId) { if ($reservation['data']['order_id'] !== null) { throw new DomainError('redemption_obligation_open', 'An attached reward order needs operational review before this refund adjustment.', 409); } $this->releaseReservation($reservation, 'earning_order_refunded'); break; } } }
            $lot = $this->latest('reward_lot', $lotId); $data = $lot['data']; $basis = (int) $data['basis_minor'];
            $refundedMinor = (int) $data['refunded_minor'] + $minor;
            if ($refundedMinor > $basis) { throw new DomainError('refund_exceeds_reward_basis', 'Refund exceeds the original earned amount; allocation review is required.', 422); }
            $totalReversal = $basis === 0 ? 0 : LoyaltyPolicy::proportional($refundedMinor, $data['initial_points'], $basis); $points = $totalReversal - $data['refunded_points'];
            $remaining = $points; $pending = min($remaining, $data['pending']); $data['pending'] -= $pending; $remaining -= $pending;
            $available = min($remaining, max(0, $data['available'])); $data['available'] -= $available; $remaining -= $available;
            $expired = min($remaining, $data['expired']); $data['expired'] -= $expired; $remaining -= $expired;
            // Spent-point reversals become a restricted negative points balance, never a cash debt.
            $data['available'] -= $remaining; $data['refunded_points'] = $totalReversal; $data['refunded_minor'] = (string) $refundedMinor;
            $entry = $this->append($owner, 'refund:' . $refundId, 'refund_reversal', ['pending' => -$pending, 'available' => -$available - $remaining, 'expired' => -$expired], ['refund_id' => $refundId, 'order_id' => (string) $order->get_id(), 'lot_id' => $lotId, 'points' => $points, 'policy_id' => $data['policy_id'], 'policy_version' => $data['policy_version']]);
            $this->store->update('reward_lot', $lotId, $lot['version'], $data);
            $result = ['reversed_points' => $points, 'journal_id' => $entry['id'], 'state' => 'recorded', 'redeemed_reward_refund_policy' => 'manual_review']; $this->store->create('reward_refund', $result, $owner, $eventId);
            return $result;
        });
    }

    public function register(): void
    {
        add_filter('woocommerce_coupon_is_valid', [$this, 'couponValid'], 20, 2);
        add_action('woocommerce_checkout_order_created', [$this, 'attachToOrder']);
        add_action('woocommerce_payment_complete', function ($id): void { try { $this->consumePaidOrder((string) $id); if ($this->enabled && $this->mode() === 'active') { $this->earnPaidOrder((string) $id); } } catch (DomainError $error) { do_action('wooshop_commerce_rewards_review_required', (string) $id, $error->errorCode); } });
        add_action('woocommerce_order_refunded', function ($orderId, $refundId): void { try { $this->refund((string) $refundId); } catch (DomainError $error) { do_action('wooshop_commerce_rewards_review_required', (string) $orderId, $error->errorCode); } }, 20, 2);
        foreach (['failed', 'cancelled'] as $state) { add_action('woocommerce_order_status_' . $state, function ($id): void { $this->releaseFailedOrder((string) $id); }); }
        add_action('init', static function (): void { if (!wp_next_scheduled('wooshop_commerce_rewards_reconcile')) { wp_schedule_event(time() + 3600, 'hourly', 'wooshop_commerce_rewards_reconcile'); } });
        add_action('wooshop_commerce_rewards_reconcile', function (): void {
            $cursor = get_option('wooshop_reward_sweep_cursor', null); $cursor = is_string($cursor) && $cursor !== '' ? $cursor : null;
            $result = $this->sweep($cursor); update_option('wooshop_reward_sweep_cursor', $result['next_cursor'], false);
        });
        add_filter('wp_privacy_personal_data_exporters', function (array $exporters): array {
            $exporters['wooshop-rewards'] = ['exporter_friendly_name' => __('Commerce reward journal', 'wooshop-commerce'), 'callback' => function (string $email, int $page = 1): array {
                $user = get_user_by('email', $email); if (!$user) { return ['data' => [], 'done' => true]; }
                $wpdb = $this->db->wpdb(); $table = $this->db->table('entities');
                $rows = $wpdb->get_results($wpdb->prepare("SELECT id,payload FROM {$table} WHERE kind='reward_journal' AND owner_id=%s ORDER BY id LIMIT 100 OFFSET %d", 'user:' . $user->ID, max(0, $page - 1) * 100), ARRAY_A) ?: [];
                $data = [];
                foreach ($rows as $row) { $payload = json_decode($row['payload'], true, 64, JSON_THROW_ON_ERROR); unset($payload['actor']); $data[] = ['group_id' => 'wooshop-rewards', 'group_label' => __('Reward points history', 'wooshop-commerce'), 'item_id' => $row['id'], 'data' => [['name' => __('Immutable movement', 'wooshop-commerce'), 'value' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE)]]]; }
                return ['data' => $data, 'done' => count($rows) < 100];
            }]; return $exporters;
        });
        add_filter('wp_privacy_personal_data_erasers', function (array $erasers): array {
            $erasers['wooshop-rewards'] = ['eraser_friendly_name' => __('Reward obligations retention review', 'wooshop-commerce'), 'callback' => function (string $email, int $page = 1): array {
                $user = get_user_by('email', $email); $retained = $user && $this->store->find('reward_journal', 'user:' . $user->ID, 1) !== [];
                return ['items_removed' => false, 'items_retained' => (bool) $retained, 'messages' => $retained ? [__('Reward movements and outstanding benefits require the approved liability and retention review; erasure is not falsely reported complete.', 'wooshop-commerce')] : [], 'done' => true];
            }]; return $erasers;
        });
    }

    public function sweep(?string $cursor = null): array
    {
        if (get_current_user_id() !== 0 && !$this->manager()) { throw new DomainError('forbidden', 'Reward reconciliation permission is required.', 403); }
        if ($cursor !== null) { Input::uuid($cursor); }
        $accounts = $this->store->find('reward_account', null, 100, $cursor); $summary = ['released' => 0, 'expired' => 0, 'notice_blocked' => 0, 'next_cursor' => count($accounts) === 100 ? end($accounts)['id'] : null];
        foreach ($accounts as $account) { $result = $this->locked($account['owner_id'], function () use ($account): array { return $this->settleDue($account['owner_id']); }); foreach (['released', 'expired', 'notice_blocked'] as $key) { $summary[$key] += $result[$key]; } }
        return $summary;
    }
}
