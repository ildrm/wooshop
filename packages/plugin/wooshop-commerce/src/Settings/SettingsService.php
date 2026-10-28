<?php
declare(strict_types=1);
namespace WooShop\Commerce\Settings;

use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;

final class SettingsService
{
    private const ID = '05c6713a-665f-4c4b-9f75-af4f62eb6a22';
    private readonly Validator $validator;
    public function __construct(private readonly EntityStore $store, private readonly Database $db)
    {
        $this->validator = new Validator(json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/settings.json'), true, 64, JSON_THROW_ON_ERROR));
    }
    public function validator(): Validator { return $this->validator; }
    public function get(string $key, mixed $fallback = null): mixed
    {
        $record = $this->store->get('site_settings', self::ID);
        if (isset($record['data']['values']) && array_key_exists($key, $record['data']['values'])) { return $record['data']['values'][$key]; }
        try { return $this->validator->default($key); } catch (DomainError) { return $fallback; }
    }
    public function snapshot(): array
    {
        $record = $this->store->get('site_settings', self::ID);
        return ['version' => $record['version'] ?? 1, 'values' => $record['data']['values'] ?? []];
    }
    public function preview(array $entries): array
    {
        $this->requireEditor();
        if (count($entries) > 500 || strlen(json_encode($entries, JSON_THROW_ON_ERROR)) > 262144) { throw new DomainError('settings_too_large', 'A preview may contain at most 500 entries and 256 KiB.', 413); }
        $effective = []; $seen = [];
        foreach ($entries as $entry) {
            if (!is_array($entry) || array_diff(array_keys($entry), ['key', 'value', 'scope']) || !isset($entry['key'], $entry['scope']) || !array_key_exists('value', $entry)) { throw new DomainError('invalid_settings', 'Expected key, value and scope only.'); }
            if (isset($seen[$entry['key']])) { throw new DomainError('duplicate_setting', 'A preview may not repeat a setting key.'); }
            $seen[$entry['key']] = true;
            if ($entry['scope'] !== 'site') { throw new DomainError('scope_not_implemented', 'Use the scoped seller settings workflow for seller branding.', 422); }
            $effective[] = ['key' => $entry['key'], 'value' => $this->validator->validate($entry['key'], $entry['value'], 'site'), 'scope' => 'site'];
        }
        $record = $this->store->create('settings_preview', ['entries' => $effective, 'expires_at' => time() + 1800], 'user:' . get_current_user_id());
        return ['preview_id' => $record['id'], 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 1800), 'validation_errors' => [], 'effective_settings' => $effective];
    }
    public function commit(string $previewId, int $version): array
    {
        $this->requireEditor();
        return $this->db->transaction(fn (): array => $this->commitPreview($previewId, $version));
    }
    private function commitPreview(string $previewId, int $version): array
    {
        $preview = $this->store->get('settings_preview', $previewId);
        if (!$preview || $preview['owner_id'] !== 'user:' . get_current_user_id()) { throw new DomainError('not_found', 'Preview not found.', 404); }
        if (($preview['data']['expires_at'] ?? 0) < time()) { throw new DomainError('preview_expired', 'Preview expired. Create a fresh preview.', 409); }
        $current = $this->store->get('site_settings', self::ID);
        if (($current['version'] ?? 1) !== $version) { throw new DomainError('version_conflict', 'Settings changed. Create a new preview.', 412); }
        $values = ($preview['data']['replace'] ?? false) ? [] : ($current['data']['values'] ?? []);
        foreach ($preview['data']['entries'] as $entry) { $values[$entry['key']] = $this->validator->validate($entry['key'], $entry['value'], 'site'); }
        if (!$current) { $this->store->create('site_settings', ['values' => []], '0', self::ID); }
        $this->retainRevision($version, $current['data']['values'] ?? []);
        $result = $this->store->update('site_settings', self::ID, $version, ['values' => $values]);
        $this->retainRevision($result['version'], $values);
        $this->store->create('audit', ['action' => isset($preview['data']['restore_revision']) ? 'settings.restored' : 'settings.committed', 'actor_id' => get_current_user_id(), 'keys' => array_column($preview['data']['entries'], 'key'), 'revision' => $result['version'], 'source_revision' => $preview['data']['restore_revision'] ?? null], 'user:' . get_current_user_id());
        return ['version' => $result['version'], 'values' => $values];
    }
    public function previewRevision(string $revisionId): array
    {
        $this->requireEditor();
        $revision = $this->store->get('settings_revision', $revisionId);
        if (!$revision || $revision['owner_id'] !== '0') { throw new DomainError('not_found', 'Configuration revision not found.', 404); }
        if (($revision['data']['schema_version'] ?? null) !== 1 || !is_array($revision['data']['values'] ?? null)) { throw new DomainError('revision_incompatible', 'This configuration revision needs a reviewed migration.', 409); }
        $entries = [];
        foreach ($revision['data']['values'] as $key => $value) { $entries[] = ['key' => $key, 'value' => $value, 'scope' => 'site']; }
        // Revalidate old values against current schemas and activation policy.
        $preview = $this->preview($entries);
        $record = $this->store->get('settings_preview', $preview['preview_id']);
        $this->store->update('settings_preview', $record['id'], $record['version'], [...$record['data'], 'replace' => true, 'restore_revision' => $revisionId]);
        return [...$preview, 'replaces_site_configuration' => true, 'source_revision' => $revision['data']['revision'], 'removed_keys' => array_values(array_diff(array_keys($this->snapshot()['values']), array_keys($revision['data']['values'])))];
    }
    /** Recent revisions use deterministic IDs for one bounded indexed lookup. */
    public function revisions(): array
    {
        $this->requireEditor();
        $version = $this->snapshot()['version']; $ids = [];
        for ($i = $version; $i >= max(1, $version - 19); --$i) { $ids[] = $this->revisionId($i); }
        $placeholders = implode(',', array_fill(0, count($ids), '%s'));
        $rows = $this->db->wpdb()->get_results($this->db->wpdb()->prepare('SELECT id,payload,created_at FROM ' . $this->db->table('entities') . " WHERE kind='settings_revision' AND owner_id='0' AND id IN ($placeholders)", ...$ids), ARRAY_A) ?: [];
        $result = [];
        foreach ($rows as $row) { $data = json_decode($row['payload'], true, 64, JSON_THROW_ON_ERROR); $result[] = ['id' => $row['id'], 'revision' => $data['revision'], 'key_count' => count($data['values']), 'created_at' => $row['created_at']]; }
        usort($result, static fn (array $a, array $b): int => $b['revision'] <=> $a['revision']);
        return $result;
    }
    private function retainRevision(int $version, array $values): void
    {
        $id = $this->revisionId($version); $existing = $this->store->get('settings_revision', $id);
        $data = ['schema_version' => 1, 'revision' => $version, 'values' => $values];
        if ($existing) {
            if ($existing['data'] !== $data || $existing['owner_id'] !== '0') { throw new DomainError('revision_conflict', 'Immutable configuration history conflicts with current data.', 409); }
            return;
        }
        $this->store->create('settings_revision', $data, '0', $id);
    }
    private function revisionId(int $version): string
    {
        $hex = hash('sha256', 'wooshop.settings.revision.' . $version);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3) . '-8' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12);
    }
    private function requireEditor(): void
    {
        if (!current_user_can('commerce.settings.manage') && !current_user_can('manage_woocommerce') && !current_user_can('manage_options')) { throw new DomainError('forbidden', 'Configuration management permission is required.', 403); }
    }
    public function export(): array
    {
        return ['schema_version' => 1, 'kind' => 'configuration', 'settings' => array_map(static fn ($key, $value) => ['key' => $key, 'value' => $value, 'scope' => 'site'], array_keys($this->snapshot()['values']), array_values($this->snapshot()['values']))];
    }
}
