<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Customer\CustomerService;
use WooShop\Commerce\Customer\Notifications;
use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Customer\Session;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Rest;

final class RestController
{
    private Specifications $specs; private Search $search; private CustomerService $customer; private Community $community; private Compatibility $compatibility; private Notifications $notifications; private Session $session; private Records $records; private RateLimit $rate;
    public function __construct(Specifications $specs, Search $search, CustomerService $customer, Community $community, Compatibility $compatibility, Notifications $notifications, Session $session, Records $records, RateLimit $rate)
    { $this->specs = $specs; $this->search = $search; $this->customer = $customer; $this->community = $community; $this->compatibility = $compatibility; $this->notifications = $notifications; $this->session = $session; $this->records = $records; $this->rate = $rate; }

    public function register(): void
    {
        $this->route('/wishlists/me', 'GET', fn ($r) => $this->customer->wishlist(), true);
        $this->route('/wishlists/me/items', 'POST', fn ($r) => $this->customer->saveItem($this->body($r), Rest::version($r), Rest::idempotency($r)), true, 201);
        $this->route('/wishlists/me/items/(?P<product_id>[1-9][0-9]{0,18})', 'DELETE', function ($r) { $this->query($r, []); if ((string) $r->get_body() !== '') { $this->body($r, []); } return $this->customer->removeItem(Input::id($r['product_id']), Rest::version($r), Rest::idempotency($r)); }, true);
        $this->route('/preferences/me', 'GET', fn ($r) => $this->customer->preferences(), true);
        $this->route('/preferences/me', 'PATCH', fn ($r) => $this->customer->updatePreferences($this->body($r), Rest::version($r), Rest::idempotency($r)), true);
        $this->route('/alerts', 'POST', fn ($r) => $this->notifications->subscribe($this->body($r), Rest::idempotency($r)), true, 202);
        $this->route('/questions', 'GET', function ($r) { $this->query($r, ['limit', 'cursor']); return $this->community->questions($this->limit($r), $this->cursor($r)); });
        $this->route('/questions', 'POST', fn ($r) => $this->community->question($this->body($r), Rest::idempotency($r)), true, 201);
        $this->route('/comparisons', 'POST', fn ($r) => $this->customer->compare($this->body($r), Rest::idempotency($r)), true);
        $this->route('/compatibility/evaluate', 'POST', function ($r) { $body = $this->body($r); $result = $this->compatibility->evaluate($body); $this->records->command($this->session->owner(), 'compatibility.evaluate', Rest::idempotency($r), $body, static fn (): array => ['accepted' => true]); return $result; }, true);
        $this->route('/specification-schemas', 'GET', function ($r) { $this->query($r, ['limit', 'cursor']); return $this->specs->schemas($this->limit($r), $this->cursor($r)); });
        $this->route('/products/(?P<product_id>[1-9][0-9]{0,18})/specifications', 'GET', fn ($r) => $this->specs->read(Input::id($r['product_id'])));
        $this->route('/products/(?P<product_id>[1-9][0-9]{0,18})/specifications', 'PUT', function ($r) {
            $body = $this->body($r); return $this->records->command($this->session->owner(), 'specifications.publish:' . $r['product_id'], Rest::idempotency($r), $body, fn () => $this->specs->publish(Input::id($r['product_id']), $body, Rest::version($r)));
        }, true);
        $this->route('/search/suggestions', 'GET', function ($r) { $this->query($r, ['q']); return $this->search->suggestions(Input::text($r['q'], 2, 100)); });
        $this->route('/notifications/unsubscribe', 'POST', function ($r) { $body = $this->body($r); $this->rate->check('unsubscribe:' . hash('sha256', is_string($body['token'] ?? null) ? $body['token'] : ''), 20); $this->notifications->unsubscribe($body); return null; }, false, 204);
    }

    private function route(string $path, string $method, callable $callback, bool $private = false, int $status = 200): void
    {
        register_rest_route('commerce/v1', $path, ['methods' => $method, 'permission_callback' => function ($request) use ($private) {
            return Rest::respond(function () use ($private, $request) { if ($private) { $this->session->authorize($request->get_header(get_current_user_id() > 0 ? 'X-WP-Nonce' : 'X-Commerce-CSRF'), $request->get_method() !== 'GET'); } return true; });
        }, 'callback' => function ($request) use ($callback, $private, $status, $method, $path) {
            return Rest::respond(function () use ($request, $callback, $private, $status, $method, $path) {
                $this->rate->check($method === 'GET' ? 'catalog.read' : 'catalog.write', $method === 'GET' ? 60 : 20);
                if ($method === 'GET' && !in_array($path, ['/questions', '/specification-schemas', '/search/suggestions'], true)) { $this->query($request, []); }
                if ($method !== 'GET') { $this->query($request, []); }
                $data = $callback($request);
                $response = new \WP_REST_Response($data, $status);
                if (is_array($data) && isset($data['version'])) { $response->header('ETag', '"' . $data['version'] . '"'); }
                elseif ($method === 'GET') { $response->header('ETag', '"' . substr(hash('sha256', wp_json_encode($data)), 0, 32) . '"'); }
                if ($private || $method !== 'GET') { $response->header('Cache-Control', 'private, no-store'); $response->header('Vary', 'Cookie, X-WP-Nonce, X-Commerce-CSRF'); }
                else { $response->header('Cache-Control', 'public, max-age=' . ($path === '/search/suggestions' ? '30' : '60')); }
                $response->header('X-Robots-Tag', 'noindex, follow');
                return $response;
            });
        }]);
    }

    private function body($request, ?array $keys = null): array
    {
        $raw = (string) $request->get_body();
        if (strlen($raw) > 65536) { throw new DomainError('payload_too_large', 'Payload exceeds the permitted size.', 400); }
        $body = $request->get_json_params();
        if (!is_object(json_decode($raw)) || !is_array($body)) { throw new DomainError('invalid_json', 'A JSON object is required.', 400); }
        if ($keys !== null) { Input::keys($body, $keys); }
        return $body;
    }

    private function query($request, array $keys): void { Input::keys($request->get_query_params(), $keys); }
    private function limit($r): int { $value = $r->get_param('limit') ?? 20; if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1 || (int) $value > 100) { throw new DomainError('invalid_limit', 'Limit must be 1–100.', 400); } return (int) $value; }
    private function cursor($r): ?string { $value = $r->get_param('cursor'); return $value === null ? null : Input::uuid($value); }
}
