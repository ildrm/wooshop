<?php
declare(strict_types=1);
namespace WooShop\Commerce\Kernel;

use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;

final class ModuleRegistry
{
    private array $definitions;
    // Availability is deliberately narrower than the documented feature inventory.
    private const AVAILABLE = ['MOD-U001', 'MOD-U002', 'MOD-U003', 'MOD-U004', 'MOD-U005', 'MOD-U006', 'MOD-U007', 'MOD-U008', 'MOD-U009', 'MOD-U010', 'MOD-U011', 'MOD-U012', 'MOD-U016', 'MOD-U017', 'MOD-U018', 'MOD-U019', 'MOD-U022', 'MOD-U025', 'MOD-U026'];
    // These modules own no payment, fulfillment or delivery promises. Other modules
    // retain their explicit unavailable transition until their drain is implemented.
    private const MANAGED = ['MOD-U003' => [], 'MOD-U004' => ['MOD-U001'], 'MOD-U005' => ['MOD-U016']];
    public function __construct(private readonly EntityStore $store, private readonly Database $db)
    {
        $rows = json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/modules.json'), true, 64, JSON_THROW_ON_ERROR);
        $this->definitions = array_column($rows, null, 'id');
    }
    public function all(): array
    {
        $records = $this->store->find('module_state', null, 100); $states = [];
        foreach ($records as $record) { $states[$record['data']['module_id']] = $record; }
        $result = [];
        foreach ($this->definitions as $id => $definition) {
            $available = in_array($id, self::AVAILABLE, true);
            $result[$id] = ['id' => $id, 'name' => $definition['scope'], 'availability' => $available ? 'partial_local_implementation' : 'unavailable_pending_implementation_or_provider', 'state' => $states[$id]['data']['state'] ?? ($available ? 'enabled' : 'blocked'), 'version' => $states[$id]['version'] ?? 1, 'dependencies' => $definition['dependencies'], 'record_id' => $states[$id]['id'] ?? null, 'lifecycle_supported' => array_key_exists($id, self::MANAGED)];
        }
        return $result;
    }
    public function enabled(string $id): bool { return ($this->all()[$id]['state'] ?? 'blocked') === 'enabled'; }
    public function accepts(string $id, string $action): bool
    {
        if (!in_array($action, ['read', 'new', 'cleanup'], true)) { return false; }
        $state = $this->all()[$id]['state'] ?? 'blocked';
        if ($action === 'new' && array_key_exists($id, self::MANAGED)) {
            $connection = $this->db->wpdb(); $transaction = $connection->get_var('SELECT @@in_transaction');
            if ($transaction === null) { return false; }
            if ((int) $transaction === 1) {
                // A command rechecks inside its transaction. Hold a shared current-read
                // lock until commit so a completed disable cannot race past accepted work.
                $recordId = $this->stateId($id); $table = $this->db->table('entities'); $now = $this->db->now();
                $insert = $connection->query($connection->prepare("INSERT IGNORE INTO $table (id,kind,owner_id,payload,version,created_at,updated_at) VALUES (%s,'module_state','0',%s,1,%s,%s)", $recordId, wp_json_encode(['module_id' => $id, 'state' => 'enabled']), $now, $now));
                if ($insert === false) { throw new DomainError('storage_unavailable', 'Module state could not be locked.', 503); }
                $row = $connection->get_row($connection->prepare("SELECT payload FROM $table WHERE id=%s AND kind='module_state' LOCK IN SHARE MODE", $recordId), ARRAY_A);
                if (!$row) { throw new DomainError('storage_unavailable', 'Module state is unavailable.', 503); }
                $state = json_decode($row['payload'], true, 16, JSON_THROW_ON_ERROR)['state'];
            }
        }
        if ($state === 'blocked') { return false; }
        if ($action === 'cleanup') { return true; }
        return $state === 'enabled' || ($action === 'read' && in_array($state, ['disabled_new_operations', 'draining'], true));
    }
    public function transition(string $id, string $target, int $version, string $reason): array
    {
        $state = $this->all()[$id] ?? throw new DomainError('module_not_found', 'Module not found.', 404);
        if (!in_array($target, ['enabled', 'disabled_new_operations', 'draining', 'disabled'], true) || !preg_match('/^[a-z0-9_.-]{1,80}$/D', $reason)) { throw new DomainError('invalid_transition', 'Module state or reason code is invalid.'); }
        if ($target === 'enabled' && !in_array($id, self::AVAILABLE, true)) { throw new DomainError('module_unavailable', 'Required implementation, provider or approval is unavailable.', 409); }
        if ($state['version'] !== $version) { throw new DomainError('version_conflict', 'The module changed. Reload it first.', 412); }
        if (!array_key_exists($id, self::MANAGED) && $target !== $state['state']) {
            // Do not report disabled while an existing API/admin/domain entry point could still mutate.
            throw new DomainError('module_lifecycle_unavailable', 'Per-entry module gating and obligation drain are not complete. This version cannot safely change this module state.', 501);
        }
        if (!array_key_exists($id, self::MANAGED)) { return ['id' => $id, 'state' => $state['state'], 'version' => $state['version']]; }
        return $this->db->transaction(function () use ($id, $target, $version, $reason): array {
            // A stable row serializes first and subsequent transitions. INSERT IGNORE
            // plus a locking current read avoids duplicate initial rows and stale snapshots.
            $recordId = $this->stateId($id);
            $connection = $this->db->wpdb(); $table = $this->db->table('entities'); $now = $this->db->now();
            $insert = $connection->query($connection->prepare("INSERT IGNORE INTO $table (id,kind,owner_id,payload,version,created_at,updated_at) VALUES (%s,'module_state','0',%s,1,%s,%s)", $recordId, wp_json_encode(['module_id' => $id, 'state' => 'enabled']), $now, $now));
            if ($insert === false) { throw new DomainError('storage_unavailable', 'Module state could not be locked.', 503); }
            $current = $connection->get_row($connection->prepare("SELECT version,payload FROM $table WHERE id=%s AND kind='module_state' FOR UPDATE", $recordId), ARRAY_A);
            if (!$current) { throw new DomainError('storage_unavailable', 'Module state is unavailable.', 503); }
            $data = json_decode($current['payload'], true, 16, JSON_THROW_ON_ERROR);
            if ((int) $current['version'] !== $version) { throw new DomainError('version_conflict', 'The module changed. Reload it first.', 412); }
            if ($target === 'enabled') {
                foreach (self::MANAGED[$id] as $dependency) {
                    if (!$this->enabled($dependency)) { throw new DomainError('module_dependency_unavailable', 'A required module is not enabled.', 409); }
                }
            }
            if ($target === $data['state']) { return ['id' => $id, 'state' => $target, 'version' => $version]; }
            $saved = $this->store->update('module_state', $recordId, $version, ['module_id' => $id, 'state' => $target, 'reason_code' => $reason]);
            $this->store->create('audit', ['action' => 'module.transitioned', 'module_id' => $id, 'from' => $data['state'], 'to' => $target, 'actor_id' => get_current_user_id(), 'reason_code' => $reason, 'revision' => $saved['version']], 'user:' . get_current_user_id());
            return ['id' => $id, 'state' => $target, 'version' => $saved['version']];
        });
    }
    private function stateId(string $id): string
    {
        $hex = hash('sha256', 'wooshop.module.' . $id);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-8' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12);
    }
}
