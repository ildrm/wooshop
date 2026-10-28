<?php
declare(strict_types=1);
namespace WooShop\Commerce\Infrastructure;

use WooShop\Commerce\Support\DomainError;

final class Database
{
    private int $depth = 0;
    private readonly string $savepointPrefix;
    private const TABLES = ['entities', 'jobs', 'events', 'operations', 'rate_limits', 'sellers', 'memberships', 'offers', 'inventory_pools', 'reservations', 'inventory_movements', 'journals', 'journal_entries', 'allocations', 'marketplace_commands'];

    public function __construct(private readonly object $connection) { $this->savepointPrefix = 'wooshop_' . bin2hex(random_bytes(4)) . '_'; }

    public function wpdb(): object { return $this->connection; }
    public function tablePrefix(): string { return $this->connection->prefix . 'wooshop_'; }
    public function now(): string { return gmdate('Y-m-d H:i:s'); }

    public function table(string $suffix): string
    {
        if (!in_array($suffix, self::TABLES, true)) {
            throw new \InvalidArgumentException('Unknown plugin table.');
        }
        return $this->tablePrefix() . $suffix;
    }

    public function transaction(callable $operation): mixed
    {
        $level = $this->depth++;
        // Preserve transactions started by native commerce or a supervised import.
        $ambient = false;
        if ($level === 0) {
            $state = $this->connection->get_var('SELECT @@in_transaction');
            if ($state === null) { $this->depth--; throw new DomainError('transaction_state_unavailable', 'The database transaction state cannot be verified.', 503); }
            $ambient = (int) $state === 1;
        }
        $ownsTransaction = $level === 0 && !$ambient;
        $savepoint = $this->savepointPrefix . $level;
        $start = $ownsTransaction ? 'START TRANSACTION' : 'SAVEPOINT ' . $savepoint;
        if ($this->connection->query($start) === false) {
            $this->depth--;
            throw new DomainError('storage_unavailable', 'Transactional storage is unavailable.', 503);
        }
        try {
            $result = $operation();
            $finish = $ownsTransaction ? 'COMMIT' : 'RELEASE SAVEPOINT ' . $savepoint;
            if ($this->connection->query($finish) === false) {
                throw new DomainError('commit_failed', 'The storage commit could not be confirmed.', 503);
            }
            return $result;
        } catch (\Throwable $error) {
            $this->connection->query($ownsTransaction ? 'ROLLBACK' : 'ROLLBACK TO SAVEPOINT ' . $savepoint);
            throw $error;
        } finally {
            $this->depth--;
        }
    }
}
