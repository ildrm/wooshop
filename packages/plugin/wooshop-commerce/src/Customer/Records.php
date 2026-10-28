<?php
declare(strict_types=1);
namespace WooShop\Commerce\Customer;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class Records
{
    private Database $db;
    private EntityStore $store;

    public function __construct(Database $db, EntityStore $store) { $this->db = $db; $this->store = $store; }

    public function own(string $kind, string $owner, array $defaults): array
    {
        $id = Input::stableId($kind . ':' . $owner);
        $record = $this->store->get($kind, $id);
        if (!$record) {
            try { $record = $this->store->create($kind, $defaults, $owner, $id); }
            catch (DomainError $error) { $record = $this->store->get($kind, $id); if (!$record) { throw $error; } }
        }
        if ($record['owner_id'] !== $owner) { throw new DomainError('not_found', 'Resource unavailable.', 404); }
        return $record;
    }

    /** Entire mutation and replay result commit together; replay does not reapply the effect. */
    public function command(string $owner, string $operation, string $key, array $payload, callable $callback): array
    {
        if (!preg_match('/^[\x21-\x7e]{16,128}$/D', $key)) { throw new DomainError('idempotency_required', 'A 16–128 character idempotency key is required.', 400); }
        $id = Input::stableId($owner . ':' . $operation . ':' . $key);
        $digest = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        return $this->db->transaction(function () use ($owner, $operation, $id, $digest, $callback): array {
            $previous = $this->store->get('customer_command', $id);
            if ($previous) {
                if ($previous['owner_id'] !== $owner || !hash_equals($previous['data']['digest'], $digest)) { throw new DomainError('idempotency_conflict', 'This key was already used with different input.', 409); }
                return $previous['data']['result'];
            }
            $result = $callback();
            $this->store->create('customer_command', ['operation' => $operation, 'digest' => $digest, 'result' => $result], $owner, $id);
            return $result;
        });
    }

    public static function expect(array $record, int $revision): void
    {
        if ($record['version'] !== $revision) { throw new DomainError('stale_revision', 'This resource changed. Reload and retry.', 412); }
    }
}
