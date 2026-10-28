<?php
declare(strict_types=1);
namespace WooShop\Commerce\Infrastructure;

use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;

/** Bounded storage for plugin extension aggregates; never a WooCommerce order store. */
final class EntityStore
{
    public function __construct(private readonly Database $db) {}

    public function create(string $kind, array $data, string $owner = '0', ?string $id = null): array
    {
        $this->kind($kind);
        $id ??= Ids::uuid();
        if (!Ids::valid($id) || strlen($owner) > 128) {
            throw new DomainError('invalid_identity', 'The extension identity is invalid.');
        }
        $now = $this->db->now();
        $ok = $this->db->wpdb()->insert($this->db->table('entities'), [
            'id' => $id, 'kind' => $kind, 'owner_id' => $owner,
            'payload' => $this->encode($data), 'version' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ], ['%s', '%s', '%s', '%s', '%d', '%s', '%s']);
        if ($ok === false) {
            throw new DomainError('entity_conflict', 'The record could not be created; its identity may already exist.', 409);
        }
        return $this->get($kind, $id) ?? throw new DomainError('storage_unavailable', 'The saved record could not be read.', 503);
    }

    public function get(string $kind, string $id): ?array
    {
        $this->kind($kind);
        $sql = $this->db->wpdb()->prepare('SELECT * FROM ' . $this->db->table('entities') . ' WHERE kind=%s AND id=%s', $kind, $id);
        $row = $this->db->wpdb()->get_row($sql, ARRAY_A);
        return $row ? $this->decode($row) : null;
    }

    public function find(string $kind, ?string $owner = null, int $limit = 20, ?string $cursor = null): array
    {
        $this->kind($kind);
        $limit = max(1, min(100, $limit));
        $where = 'kind=%s';
        $args = [$kind];
        if ($owner !== null) { $where .= ' AND owner_id=%s'; $args[] = $owner; }
        if ($cursor !== null && $cursor !== '') {
            if (!Ids::valid($cursor)) { throw new DomainError('invalid_cursor', 'The continuation cursor is invalid.'); }
            $where .= ' AND id>%s'; $args[] = $cursor;
        }
        $args[] = $limit;
        $sql = $this->db->wpdb()->prepare('SELECT * FROM ' . $this->db->table('entities') . ' WHERE ' . $where . ' ORDER BY id ASC LIMIT %d', ...$args);
        return array_map($this->decode(...), $this->db->wpdb()->get_results($sql, ARRAY_A) ?: []);
    }

    public function update(string $kind, string $id, int $version, array $data): array
    {
        $this->kind($kind);
        if (in_array($kind, ['audit', 'settings_revision'], true)) { throw new DomainError('immutable_record', 'This record is append-only.', 409); }
        if ($version < 1) { throw new DomainError('precondition_required', 'A positive record version is required.', 428); }
        $sql = $this->db->wpdb()->prepare('UPDATE ' . $this->db->table('entities') . ' SET payload=%s,version=version+1,updated_at=%s WHERE kind=%s AND id=%s AND version=%d', $this->encode($data), $this->db->now(), $kind, $id, $version);
        if ($this->db->wpdb()->query($sql) !== 1) {
            throw new DomainError('version_conflict', 'The record changed. Reload it before applying this change.', 412);
        }
        return $this->get($kind, $id) ?? throw new DomainError('storage_unavailable', 'The changed record could not be read.', 503);
    }

    public function delete(string $kind, string $id, int $version): bool
    {
        $this->kind($kind);
        if (in_array($kind, ['audit', 'operation', 'consent', 'suppression', 'settings_revision'], true)) {
            throw new DomainError('retention_required', 'This record requires its governed retention workflow.', 409);
        }
        $sql = $this->db->wpdb()->prepare('DELETE FROM ' . $this->db->table('entities') . ' WHERE kind=%s AND id=%s AND version=%d', $kind, $id, $version);
        if ($this->db->wpdb()->query($sql) !== 1) { throw new DomainError('version_conflict', 'The record changed or no longer exists.', 412); }
        return true;
    }

    private function kind(string $kind): void
    {
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $kind)) { throw new DomainError('invalid_kind', 'The record kind is invalid.'); }
    }
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 262144) { throw new DomainError('payload_too_large', 'This extension record exceeds the supported size.', 413); }
        return $json;
    }
    private function decode(array $row): array
    {
        $row['data'] = json_decode($row['payload'], true, 64, JSON_THROW_ON_ERROR);
        unset($row['payload']);
        $row['version'] = (int) $row['version'];
        return $row;
    }
}
