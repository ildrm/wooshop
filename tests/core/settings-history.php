<?php
// Real WP/WC execution; all setting/history/audit writes stay in an outer rollback.
use WooShop\Commerce\Kernel\Plugin;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;

if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES) { throw new RuntimeException('Local synthetic fixtures required.'); }
global $wpdb;
$kernel = Plugin::instance(); if (!$kernel) { throw new RuntimeException('Plugin unavailable.'); }
$settings = $kernel->settings(); $store = $kernel->entities(); $db = $kernel->database(); $checks = 0;
$assert = static function (bool $ok, string $why) use (&$checks): void { ++$checks; if (!$ok) { throw new RuntimeException($why); } };
$reject = static function (callable $work, string $expected) use ($assert): void { try { $work(); } catch (DomainError $error) { $assert($error->errorCode === $expected, 'Expected '.$expected.'; got '.$error->errorCode); return; } throw new RuntimeException('Expected rejection: '.$expected); };
$admin = get_user_by('login', 'fixture-admin'); if (!$admin) { throw new RuntimeException('Missing fixture administrator.'); }
$previousUser = get_current_user_id(); wp_set_current_user($admin->ID);
$original = $settings->snapshot();
$financialDigest = static function () use ($wpdb, $db): string {
    $data = [];
    foreach (['journals', 'journal_entries', 'allocations'] as $suffix) {
        $count = (int) $wpdb->get_var('SELECT COUNT(*) FROM '.$db->table($suffix));
        if ($count > 1000) { throw new RuntimeException('Use a bounded assurance dataset; do not silently truncate finance checks.'); }
        $data[$suffix] = $wpdb->get_results('SELECT * FROM '.$db->table($suffix).' ORDER BY id', ARRAY_A);
    }
    $orders = wc_get_orders(['limit' => 100, 'return' => 'ids', 'orderby' => 'ID', 'order' => 'ASC']);
    if (count($orders) === 100) { throw new RuntimeException('Native order dataset exceeds this bounded assurance fixture.'); }
    foreach ($orders as $id) { $order = wc_get_order($id); $data['native_orders'][] = [$order->get_id(), $order->get_status(), $order->get_currency(), $order->get_total()]; }
    return hash('sha256', wp_json_encode($data));
};
$beforeFinance = $financialDigest();
$wpdb->query('START TRANSACTION');
try {
    $first = $settings->preview([['key' => 'wishlist.max_items', 'value' => 75, 'scope' => 'site']]);
    $one = $settings->commit($first['preview_id'], $original['version']);
    $revisions = $settings->revisions(); $firstSaved = $revisions[0];
    $assert($firstSaved['revision'] === $one['version'] && count($revisions) >= 2, 'Both before and after revisions retained.');
    $savedRecord = $store->get('settings_revision', $firstSaved['id']);
    $reject(fn () => $store->update('settings_revision', $firstSaved['id'], 1, ['values' => []]), 'immutable_record');
    $reject(fn () => $store->delete('settings_revision', $firstSaved['id'], 1), 'retention_required');
    $second = $settings->preview([['key' => 'wishlist.max_items', 'value' => 90, 'scope' => 'site'], ['key' => 'history.window_days', 'value' => 180, 'scope' => 'site']]);
    $two = $settings->commit($second['preview_id'], $one['version']);
    $restore = $settings->previewRevision($firstSaved['id']);
    $assert($restore['replaces_site_configuration'] && $restore['source_revision'] === $one['version'], 'Restore explicitly describes replacement and source version.');
    $assert($settings->get('wishlist.max_items') === 90, 'Preview does not publish old values.');
    $assert(in_array('history.window_days', $restore['removed_keys'], true) === !array_key_exists('history.window_days', $savedRecord['data']['values']), 'Removed overrides are disclosed accurately.');
    $reject(fn () => $settings->commit($restore['preview_id'], $one['version']), 'version_conflict');
    $assert($settings->get('wishlist.max_items') === 90, 'Stale restore leaves current values unchanged.');
    $three = $settings->commit($restore['preview_id'], $two['version']);
    $assert($three['version'] === $two['version'] + 1 && $three['values'] === $savedRecord['data']['values'], 'Restore creates a new version with exact prior override set.');
    $assert($settings->get('wishlist.max_items') === 75, 'Restored override is effective.');
    $reject(fn () => $settings->commit($restore['preview_id'], $two['version']), 'version_conflict');
    $incompatible = $store->create('settings_revision', ['schema_version' => 999, 'revision' => 999, 'values' => []]);
    $reject(fn () => $settings->previewRevision($incompatible['id']), 'revision_incompatible');
    $unsafe = $store->create('settings_revision', ['schema_version' => 1, 'revision' => 998, 'values' => ['marketplace.enabled' => true]]);
    $reject(fn () => $settings->previewRevision($unsafe['id']), 'approval_required');
    $unknown = $store->create('settings_revision', ['schema_version' => 1, 'revision' => 997, 'values' => ['unknown.setting' => true]]);
    $reject(fn () => $settings->previewRevision($unknown['id']), 'unknown_setting');
    $foreign = $store->create('settings_preview', ['entries' => [], 'expires_at' => time() + 60], 'user:0');
    $reject(fn () => $settings->commit($foreign['id'], $three['version']), 'not_found');
    $expired = $settings->previewRevision($firstSaved['id']); $record = $store->get('settings_preview', $expired['preview_id']);
    $store->update('settings_preview', $record['id'], $record['version'], [...$record['data'], 'expires_at' => time() - 1]);
    $reject(fn () => $settings->commit($expired['preview_id'], $three['version']), 'preview_expired');
    $audits = $store->find('audit', 'user:'.$admin->ID, 100); $restoredAudit = null;
    foreach ($audits as $audit) { if (($audit['data']['action'] ?? '') === 'settings.restored' && ($audit['data']['source_revision'] ?? '') === $firstSaved['id']) { $restoredAudit = $audit; break; } }
    $assert($restoredAudit !== null && $restoredAudit['data']['revision'] === $three['version'], 'Restoration audit identifies original and new revision.');
    $reject(fn () => $store->update('audit', $restoredAudit['id'], 1, []), 'immutable_record');
    $assert(hash_equals($beforeFinance, $financialDigest()), 'Config restore must leave native orders, allocations and journals unchanged.');
    wp_set_current_user(0);
    $reject(fn () => $settings->previewRevision($firstSaved['id']), 'forbidden');
    $reject(fn () => $settings->revisions(), 'forbidden');
    $reject(fn () => $settings->preview([]), 'forbidden');
    $reject(fn () => $settings->commit($restore['preview_id'], $three['version']), 'forbidden');
} finally { $wpdb->query('ROLLBACK'); wp_set_current_user($previousUser); }
$assert($settings->snapshot() === $original, 'Probe rolls back every setting/history/audit mutation.');
echo wp_json_encode(['suite' => 'settings revision restore; real WP/WC/MariaDB', 'result' => 'PASS', 'assertions' => $checks, 'writes_rolled_back' => true]).PHP_EOL;
