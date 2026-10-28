<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class RateLimit
{
    private EntityStore $store;
    public function __construct(EntityStore $store) { $this->store = $store; }

    public function check(string $operation, int $maximum): void
    {
        $actor = get_current_user_id() > 0 ? 'user:' . get_current_user_id() : hash_hmac('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), wp_salt('nonce'));
        $id = Input::stableId('catalog_rate:' . $operation . ':' . $actor . ':' . intdiv(time(), 60));
        $record = $this->store->get('catalog_rate', $id);
        if (!$record) {
            try { $this->store->create('catalog_rate', ['count' => 1, 'expires_at' => time() + 120], 'rate', $id); return; }
            catch (DomainError $error) { $record = $this->store->get('catalog_rate', $id); if (!$record) { throw $error; } }
        }
        if ($record['data']['count'] >= $maximum) { throw new DomainError('rate_limited', 'Too many requests. Retry after one minute.', 429, ['retry_after' => 60]); }
        try { $this->store->update('catalog_rate', $id, $record['version'], ['count' => $record['data']['count'] + 1, 'expires_at' => $record['data']['expires_at']]); }
        catch (DomainError $error) { throw new DomainError('rate_limited', 'Concurrent requests exceeded the safe rate. Retry later.', 429, ['retry_after' => 60]); }
    }
}
