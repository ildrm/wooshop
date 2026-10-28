<?php
declare(strict_types=1);

namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;

/** Prepared statements only; neither table names nor SQL identifiers come from requests. */
final class Records
{
    public function __construct(public readonly Database $db) {}

    public static function id(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 15) | 64);
        $b[8] = chr((ord($b[8]) & 63) | 128);
        $h = bin2hex($b);
        return substr($h, 0, 8).'-'.substr($h, 8, 4).'-'.substr($h, 12, 4).'-'.substr($h, 16, 4).'-'.substr($h, 20);
    }

    public static function json(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function canonical(array $data): string
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = json_decode(self::canonical($value), true, 512, JSON_THROW_ON_ERROR);
            }
        }
        unset($value);
        if (!array_is_list($data)) {
            ksort($data, SORT_STRING);
        }
        return self::json($data);
    }

    public function one(string $suffix, string $id, bool $lock = false): array
    {
        $w = $this->db->wpdb();
        $sql = $w->prepare('SELECT * FROM '.$this->db->table($suffix).' WHERE id=%s'.($lock ? ' FOR UPDATE' : ''), $id);
        $row = $w->get_row($sql, ARRAY_A);
        if (!$row) {
            throw new DomainError('not_found', 'Resource not found.', 404);
        }
        return $row;
    }

    public function insert(string $suffix, array $data): void
    {
        if ($this->db->wpdb()->insert($this->db->table($suffix), $data) === false) {
            throw new DomainError('write_conflict', 'The resource could not be saved.', 409);
        }
    }

    public function execute(string $sql): int
    {
        $n = $this->db->wpdb()->query($sql);
        if ($n === false) {
            throw new DomainError('database_unavailable', 'The operation could not be persisted.', 503);
        }
        return (int) $n;
    }

    public static function version(array $row, int $version): void
    {
        if ((int) $row['version'] !== $version) {
            throw new DomainError('version_conflict', 'The resource changed; reload before retrying.', 412, ['current_version' => (int) $row['version']]);
        }
    }

    /** The receipt and all business effects commit together; duplicate callers serialize on its row. */
    public function command(string $scope, string $key, array $request, callable $work): array
    {
        if (!preg_match('/^[\x21-\x7e]{16,128}$/D', $key)) {
            throw new DomainError('invalid_idempotency_key', 'A 16–128 character idempotency key is required.', 400);
        }
        return $this->db->transaction(function () use ($scope, $key, $request, $work): array {
            $w = $this->db->wpdb();
            $id = hash('sha256', $scope.'|'.$key);
            $digest = hash('sha256', self::canonical($request));
            $table = $this->db->table('marketplace_commands');
            $this->execute($w->prepare("INSERT IGNORE INTO {$table} (id,digest,created_at) VALUES (%s,%s,%s)", $id, $digest, $this->db->now()));
            $row = $w->get_row($w->prepare("SELECT * FROM {$table} WHERE id=%s FOR UPDATE", $id), ARRAY_A);
            if (!$row || !hash_equals($row['digest'], $digest)) {
                throw new DomainError('idempotency_conflict', 'This key was used for different input.', 409);
            }
            if ($row['response'] !== null) {
                return json_decode($row['response'], true, 512, JSON_THROW_ON_ERROR);
            }
            $result = $work();
            $this->execute($w->prepare("UPDATE {$table} SET response=%s WHERE id=%s", self::json($result), $id));
            return $result;
        });
    }
}
