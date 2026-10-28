<?php
declare(strict_types=1);

namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Inventory\InventoryService;
use WooShop\Commerce\Security\Authorization;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Rest;

final class MarketplaceModule
{
    private Records $r;
    private Authorization $auth;
    private SellerService $sellers;
    private OfferService $offers;
    private InventoryService $inventory;
    private AllocationService $allocations;

    public function __construct(private Database $db, private EntityStore $entities)
    {
        $this->r = new Records($db);
        $this->auth = new Authorization($db);
        $this->sellers = new SellerService($db, $entities);
        $this->offers = new OfferService($db);
        $this->inventory = new InventoryService($db);
        $this->allocations = new AllocationService($db, $entities);
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'routes']);
        add_action('wooshop_expire_reservations', [$this->inventory, 'expire']);
        add_filter('cron_schedules', static function (array $schedules): array {
            $schedules['wooshop_minute'] = ['interval' => 60, 'display' => 'WooShop reservation expiry'];
            return $schedules;
        });
        add_action('init', function (): void {
            if (!wp_next_scheduled('wooshop_expire_reservations')) { wp_schedule_event(time() + 60, 'wooshop_minute', 'wooshop_expire_reservations'); }
        });
        (new NativeGuard())->register();
        (new OrderSnapshots())->register();
        (new SellerPortal($this->db, $this->entities))->register();
        add_shortcode('commerce_offers', function (): string {
            global $product;
            if (!$product || !is_a($product, 'WC_Product') || !(new NativeGuard())->mapped($product)) { return ''; }
            return '<section class="wooshop-offers" aria-label="'.esc_attr__('Seller offers', 'wooshop-commerce').'"><h2>'.esc_html__('Seller offers', 'wooshop-commerce').'</h2><p>'.esc_html__('No seller offers are currently available. Contact support for help with an existing purchase.', 'wooshop-commerce').'</p></section>';
        });
    }

    public function capabilities(): array
    {
        return ['organizations' => 'implemented', 'scoped_offer_drafts' => 'implemented', 'atomic_inventory' => 'implemented', 'native_order_item_snapshots' => 'partial', 'marketplace_checkout' => 'blocked', 'live_or_sandbox_payment_refund_payout' => 'blocked', 'activation_gates' => (new ActivationGate())->reasons()];
    }

    private function route(string $path, string $method, callable $work, bool $public = false): void
    {
        register_rest_route('commerce/v1', $path, [
            'methods' => $method,
            'permission_callback' => $public ? '__return_true' : static function (): mixed {
                return is_user_logged_in() ? true : new \WP_Error('authentication_required', 'Authentication is required.', ['status' => 401]);
            },
            'callback' => function (\WP_REST_Request $request) use ($work, $public): mixed {
                return Rest::respond(function () use ($request, $work, $public): \WP_REST_Response {
                    if (strlen((string) $request->get_body()) > 65536) { throw new DomainError('body_too_large', 'Request body exceeds 64 KiB.', 413); }
                    $result = $work($request);
                    $status = 200;
                    if ($request->get_method() === 'POST') {
                        $path = $request->get_route();
                        if (preg_match('~/(seller-applications|shipments|returns|payouts)$~', $path) || str_ends_with($path, '/adjustments')) { $status = 201; }
                        if (str_ends_with($path, '/memberships') || preg_match('~/refunds(?:/[a-f0-9-]+/approval)?$~', $path)) { $status = 202; }
                    }
                    $response = new \WP_REST_Response($result, $status);
                    $response->header('Cache-Control', $public ? 'public, max-age=0, must-revalidate' : 'private, no-store');
                    if (isset($result['version'])) { $response->header('ETag', '"'.$result['version'].'"'); }
                    return $response;
                });
            },
        ]);
    }

    public function routes(): void
    {
        $uuid = '(?P<%s>[a-fA-F0-9-]{36})';
        $this->route('/offers', 'GET', function ($q): array {
            $params = $q->get_query_params();
            Input::fields($params, ['product_id', 'variation_id', 'destination_region', 'cursor', 'limit'], ['product_id']);
            $limit = $this->limit($params['limit'] ?? 20);
            $scope = $this->scope('offers', [$params['product_id'], $params['variation_id'] ?? null, $params['destination_region'] ?? null, $limit]);
            $page = $this->offers->publicPage(Input::nativeId($params['product_id']), isset($params['variation_id']) ? Input::nativeId($params['variation_id']) : null, $params['destination_region'] ?? null, $limit, Cursor::read($params['cursor'] ?? null, $scope));
            return ['items' => $page['items'], 'next_cursor' => $page['after'] ? Cursor::issue($page['after'], $scope) : null];
        }, true);
        $this->route('/offers/'.sprintf($uuid, 'offer_id'), 'PATCH', function ($q): array {
            $row = $this->visible('offers', $q['offer_id'], 'commerce.offer.write');
            return $this->offers->update($row['id'], $this->body($q), Rest::version($q), get_current_user_id(), Rest::idempotency($q));
        });
        $this->route('/inventory-pools/'.sprintf($uuid, 'pool_id').'/adjustments', 'POST', function ($q): array {
            $row = $this->visible('inventory_pools', $q['pool_id'], 'commerce.offer.write');
            $body = $this->body($q);
            Input::fields($body, ['delta', 'reason', 'authority_epoch', 'evidence_ref'], ['delta', 'reason', 'authority_epoch']);
            if (!is_int($body['delta']) || !is_int($body['authority_epoch']) || !is_string($body['reason'])) { throw new DomainError('invalid_adjustment', 'Integer delta/epoch and reason are required.', 400); }
            return $this->inventory->adjust($row['id'], $body['delta'], $body['reason'], $body['authority_epoch'], Rest::version($q), get_current_user_id(), Rest::idempotency($q), $body['evidence_ref'] ?? '');
        });
        $this->route('/seller-applications', 'POST', fn ($q): array => $this->sellers->apply($this->body($q), get_current_user_id(), Rest::idempotency($q), (int) get_option('wooshop_seller_terms_version', 0)));
        $this->route('/sellers/'.sprintf($uuid, 'seller_id').'/transitions', 'POST', function ($q): array {
            $this->visibleSeller($q['seller_id'], 'commerce.seller.manage');
            return $this->sellers->transition($q['seller_id'], $this->body($q), Rest::version($q), get_current_user_id(), Rest::idempotency($q));
        });
        $this->route('/sellers/'.sprintf($uuid, 'seller_id').'/memberships', 'POST', function ($q): array {
            $this->visibleSeller($q['seller_id'], 'commerce.team.manage');
            return $this->sellers->invite($q['seller_id'], $this->body($q), get_current_user_id(), Rest::idempotency($q));
        });
        $this->route('/sellers/'.sprintf($uuid, 'seller_id').'/memberships/'.sprintf($uuid, 'membership_id'), 'DELETE', function ($q): array {
            $this->visibleSeller($q['seller_id'], 'commerce.team.manage');
            return $this->sellers->revoke($q['seller_id'], $q['membership_id'], Rest::version($q), get_current_user_id(), Rest::idempotency($q));
        });
        $this->route('/allocations', 'GET', function ($q): array {
            $params = $q->get_query_params();
            Input::fields($params, ['seller_id', 'cursor', 'limit'], ['seller_id']);
            $seller = $this->visibleSeller($params['seller_id'], 'commerce.allocation.read.own');
            $limit = $this->limit($params['limit'] ?? 20);
            $scope = $this->scope('allocations', [$seller['id'], $limit]);
            $after = Cursor::read($params['cursor'] ?? null, $scope);
            $w = $this->db->wpdb();
            $rows = $w->get_results($w->prepare('SELECT * FROM '.$this->db->table('allocations').' WHERE seller_id=%s AND id>%s ORDER BY id LIMIT %d', $seller['id'], $after ?? '', $limit + 1), ARRAY_A);
            $more = count($rows) > $limit;
            $rows = array_slice($rows, 0, $limit);
            return ['items' => array_map($this->allocations->project(...), $rows), 'next_cursor' => $more ? Cursor::issue(end($rows)['id'], $scope) : null];
        });
        $this->route('/allocations/'.sprintf($uuid, 'allocation_id'), 'GET', fn ($q): array => $this->allocations->project($this->visible('allocations', $q['allocation_id'], 'commerce.allocation.read.own')));
        $this->route('/shipments', 'POST', function ($q): array {
            $body = $this->body($q);
            $this->visible('allocations', (string) ($body['allocation_id'] ?? ''), 'commerce.allocation.fulfill');
            return $this->allocations->shipment($body, get_current_user_id(), Rest::idempotency($q));
        });
        $this->route('/returns', 'POST', function ($q): array {
            $body = $this->body($q);
            $order = isset($body['order_id']) ? wc_get_order((int) Input::nativeId($body['order_id'])) : false;
            if (!$order) { throw new DomainError('not_found', 'Resource not found.', 404); }
            $seller = null;
            if ((int) $order->get_customer_id() !== get_current_user_id()) {
                $seller = $this->sellerForItem((string) $order->get_id(), (string) ($body['order_item_id'] ?? ''));
                if (!$seller || !$this->auth->can('commerce.refund.request', $seller)) { throw new DomainError('not_found', 'Resource not found.', 404); }
            }
            return $this->allocations->requestReturn($body, get_current_user_id(), Rest::idempotency($q), $seller);
        });
        $this->financialRoutes($uuid);
    }

    private function financialRoutes(string $uuid): void
    {
        $this->route('/refunds', 'POST', function ($q): array {
            $body = $this->body($q);
            Input::fields($body, ['return_id', 'requested_shipping_refund', 'reason_code'], ['return_id', 'requested_shipping_refund', 'reason_code']);
            $case = $this->entities->get('return', (string) $body['return_id']);
            if (!$case || !$this->auth->can('commerce.refund.request', $case['data']['seller_id'] ?? null)) { throw new DomainError('not_found', 'Resource not found.', 404); }
            Rest::idempotency($q);
            $this->financialGate();
        });
        $this->route('/refunds/'.sprintf($uuid, 'refund_id'), 'GET', function ($q): array {
            $record = $this->financialVisible('refund.preparation', $q['refund_id'], 'commerce.refund.request');
            return $this->financialProjection($record, false);
        });
        $this->route('/refunds/'.sprintf($uuid, 'refund_id').'/approval', 'POST', function ($q): array {
            $record = $this->financialVisible('refund.preparation', $q['refund_id'], 'commerce.refund.approve');
            $body = $this->body($q);
            Input::fields($body, ['reason_code', 'evidence_ref', 'prepared_digest'], ['reason_code', 'evidence_ref', 'prepared_digest']);
            Records::version($record, Rest::version($q)); Rest::idempotency($q);
            $this->independent($record, $body['prepared_digest'] ?? '', 'prepared_digest');
            $this->financialGate();
        });
        $this->route('/payouts', 'POST', function ($q): array {
            $body = $this->body($q);
            Input::fields($body, ['seller_id', 'currency', 'through_ledger_sequence'], ['seller_id', 'currency', 'through_ledger_sequence']);
            $this->visibleSeller($body['seller_id'], 'commerce.payout.prepare');
            Rest::idempotency($q);
            $this->financialGate();
        });
        $this->route('/payouts/'.sprintf($uuid, 'payout_id'), 'GET', fn ($q): array => $this->financialProjection($this->financialVisible('payout', $q['payout_id'], 'commerce.finance.read.own'), true));
        $this->route('/payouts/'.sprintf($uuid, 'payout_id').'/approval', 'POST', function ($q): array {
            $record = $this->financialVisible('payout', $q['payout_id'], 'commerce.payout.approve');
            $body = $this->body($q);
            Input::fields($body, ['batch_digest', 'reason_code'], ['batch_digest', 'reason_code']);
            Records::version($record, Rest::version($q)); Rest::idempotency($q);
            $this->independent($record, $body['batch_digest'] ?? '', 'batch_digest');
            $this->financialGate();
        });
    }

    private function financialGate(): never { throw new DomainError('verified_finance_unavailable', 'Fresh verified human step-up, qualified legal approval, provider capability and reconciliation are required. No refund or payout was submitted.', 503); }
    private function independent(array $record, string $digest, string $field): void
    {
        if ((string) ($record['data']['prepared_by'] ?? '') === (string) get_current_user_id()) { throw new DomainError('independent_approver_required', 'The preparer cannot approve this operation.', 403); }
        if (!preg_match('/^[a-f0-9]{64}$/D', $digest) || !hash_equals($record['data'][$field] ?? '', $digest)) { throw new DomainError('prepared_digest_conflict', 'The prepared operation changed.', 409); }
    }
    private function financialVisible(string $kind, string $id, string $capability): array
    {
        $record = $this->entities->get($kind, $id);
        if (!$record || !isset($record['data']['seller_id']) || !$this->auth->can($capability, $record['data']['seller_id'])) { throw new DomainError('not_found', 'Resource not found.', 404); }
        return $record;
    }
    private function financialProjection(array $record, bool $payout): array
    {
        $fields = $payout ? ['seller_id', 'amount', 'state', 'batch_digest'] : ['order_id', 'amount', 'prepared_digest', 'state', 'prepared_by'];
        return ['id' => $record['id']] + array_intersect_key($record['data'], array_flip($fields)) + ['version' => $record['version']];
    }
    private function visible(string $table, string $id, string $capability): array
    {
        $row = $this->r->one($table, $id);
        if (!$this->auth->can($capability, $row['seller_id'])) { throw new DomainError('not_found', 'Resource not found.', 404); }
        return $row;
    }
    private function visibleSeller(string $id, string $capability): array
    {
        $row = $this->r->one('sellers', $id);
        if (!$this->auth->can($capability, $id)) { throw new DomainError('not_found', 'Resource not found.', 404); }
        return $row;
    }
    private function sellerForItem(string $order, string $item): ?string
    {
        $w = $this->db->wpdb();
        $rows = $w->get_results($w->prepare('SELECT seller_id,data FROM '.$this->db->table('allocations').' WHERE order_id=%s', $order), ARRAY_A);
        foreach ($rows as $row) { foreach (json_decode($row['data'], true)['lines'] as $line) { if ($line['order_item_id'] === $item) { return $row['seller_id']; } } }
        return null;
    }
    private function body(\WP_REST_Request $q): array
    {
        $body = $q->get_json_params();
        if (!is_array($body) || (array_is_list($body) && $body !== [])) { throw new DomainError('invalid_body', 'A JSON object is required.', 400); }
        return $body;
    }
    private function limit(mixed $n): int
    {
        if ((!is_int($n) && (!is_string($n) || !ctype_digit($n))) || (int) $n < 1 || (int) $n > 100) { throw new DomainError('invalid_limit', 'List limit must be from 1 to 100.', 400); }
        return (int) $n;
    }
    private function scope(string $name, array $filters): string { return $this->db->tablePrefix().'|'.get_current_user_id().'|'.$name.'|'.Records::canonical($filters); }
}
