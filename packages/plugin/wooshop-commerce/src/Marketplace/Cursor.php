<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;
use WooShop\Commerce\Support\DomainError;

final class Cursor
{
    public static function issue(string $after, string $scope): string
    {
        $payload = rtrim(strtr(base64_encode(Records::json(['after' => $after, 'scope' => hash('sha256', $scope), 'expires' => time() + 900])), '+/', '-_'), '=');
        return $payload.'.'.hash_hmac('sha256', $payload, wp_salt('auth'));
    }
    public static function read(?string $cursor, string $scope): ?string
    {
        if ($cursor === null || $cursor === '') { return null; }
        if (strlen($cursor) > 1000 || !preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D', $cursor, $m) || !hash_equals(hash_hmac('sha256', $m[1], wp_salt('auth')), $m[2])) { throw new DomainError('invalid_cursor', 'Invalid continuation; restart the list.', 400); }
        $json = base64_decode(strtr($m[1], '-_', '+/'), true);
        $data = $json ? json_decode($json, true) : null;
        if (!is_array($data) || ($data['scope'] ?? '') !== hash('sha256', $scope) || ($data['expires'] ?? 0) < time() || !preg_match('/^[a-f0-9-]{36}$/D', $data['after'] ?? '')) { throw new DomainError('cursor_scope_conflict', 'Continuation expired or belongs to a different scope; restart the list.', 400); }
        return $data['after'];
    }
}
