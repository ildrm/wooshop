<?php
declare(strict_types=1);
namespace WooShop\Commerce\Customer;

use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Catalog\Options;

/** No guest state is ever owned by WordPress user zero. Cookie contains a signed random session. */
final class Session
{
    private string $cookie;
    private ?string $token = null;

    public function __construct()
    {
        $this->cookie = 'wooshop_guest_' . substr(hash('sha256', (string) get_current_blog_id() . home_url('/')), 0, 12);
    }

    private function signature(string $value): string { return hash_hmac('sha256', home_url('/') . ':' . get_current_blog_id() . ':' . $value, wp_salt('auth')); }

    public function start(): void
    {
        if ($this->token !== null) { return; }
        $value = $_COOKIE[$this->cookie] ?? '';
        if (is_string($value) && preg_match('/^([a-f0-9]{64})\.([0-9]{10})\.([a-f0-9]{64})$/D', $value, $parts)
            && (int) $parts[2] > time() && (int) $parts[2] <= time() + 7776000
            && hash_equals($this->signature($parts[1] . '.' . $parts[2]), $parts[3])) {
            $this->token = $value;
            return;
        }
        $this->rotate();
    }

    public function rotate(): void
    {
        if (headers_sent()) { throw new DomainError('session_unavailable', 'Reload the page to establish a private session.', 503); }
        $expiry = time() + Options::integer('wishlist.guest_days', 30, 1, 90) * 86400;
        $value = bin2hex(random_bytes(32)) . '.' . $expiry;
        $this->token = $value . '.' . $this->signature($value);
        setcookie($this->cookie, $this->token, ['expires' => $expiry, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax']);
        $_COOKIE[$this->cookie] = $this->token;
    }

    public function guestOwner(): string
    {
        $this->start();
        return 'guest:' . hash_hmac('sha256', explode('.', (string) $this->token)[0], wp_salt('auth'));
    }

    public function owner(): string
    {
        return get_current_user_id() > 0 ? 'user:' . get_current_user_id() : $this->guestOwner();
    }

    public function csrf(): string
    {
        if (get_current_user_id() > 0) { return wp_create_nonce('wp_rest'); }
        return $this->signature($this->guestOwner() . ':' . intdiv(time(), 3600));
    }

    public function authorize(?string $csrf, bool $mutation): void
    {
        if (get_current_user_id() > 0) {
            // Native REST authentication verifies application passwords separately.
            if (isset($_COOKIE[LOGGED_IN_COOKIE]) && (!$csrf || !wp_verify_nonce($csrf, 'wp_rest'))) {
                throw new DomainError('csrf_invalid', 'Refresh the page and retry.', 403);
            }
            return;
        }
        $owner = $this->guestOwner();
        if (!$mutation) { return; }
        foreach ([intdiv(time(), 3600), intdiv(time(), 3600) - 1] as $tick) {
            if (is_string($csrf) && hash_equals($this->signature($owner . ':' . $tick), $csrf)) { return; }
        }
        throw new DomainError('csrf_invalid', 'Refresh the page and retry.', 403);
    }
}
