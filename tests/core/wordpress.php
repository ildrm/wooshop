<?php
// Executed inside a real WordPress process; WP-CLI eval-file cannot prepend strict_types.
use WooShop\Commerce\Kernel\Plugin;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;

if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES) { throw new RuntimeException('Local fixture environment required.'); }
global $wpdb;
$kernel = Plugin::instance();
if (!$kernel) { throw new RuntimeException('Activate the real companion plugin first.'); }
$db = $kernel->database(); $store = $kernel->entities(); $checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void { $checks++; if (!$condition) { throw new RuntimeException($message); } };
$reject = static function (callable $work, string $code) use ($assert): void { try { $work(); } catch (DomainError $e) { $assert($e->errorCode === $code, 'Expected ' . $code . '; got ' . $e->errorCode); return; } throw new RuntimeException('Missing rejection: ' . $code); };
$admin = get_user_by('login', 'fixture-admin'); if (!$admin) { throw new RuntimeException('Missing synthetic administrator.'); }
$previous = get_current_user_id(); wp_set_current_user($admin->ID);
$wpdb->query('START TRANSACTION');
try {
    $record = $store->create('core_probe', ['value' => 1], 'user:' . $admin->ID);
    $assert($store->get('core_probe', $record['id'])['data']['value'] === 1, 'Real persistence read');
    $changed = $store->update('core_probe', $record['id'], 1, ['value' => 2]);
    $assert($changed['version'] === 2 && $changed['data']['value'] === 2, 'Atomic revision change');
    $reject(fn () => $store->update('core_probe', $record['id'], 1, ['value' => 3]), 'version_conflict');
    $assert(count($store->find('core_probe', 'user:' . $admin->ID, 20)) === 1, 'Owner-scoped query');
    $assert($store->find('core_probe', 'user:0', 20) === [], 'Foreign owner not fetched');
    $reject(fn () => $db->table('posts;DROP TABLE'), 'invalid_kind'); // handled below as argument error, not SQL execution.
} catch (InvalidArgumentException $e) { $assert($e->getMessage() === 'Unknown plugin table.', 'Table allowlist protects identifiers'); }
finally { $wpdb->query('ROLLBACK'); wp_set_current_user($previous); }

wp_set_current_user($admin->ID); $wpdb->query('START TRANSACTION');
try {
    $preview = $kernel->settings()->preview([['key' => 'wishlist.max_items', 'value' => 75, 'scope' => 'site']]);
    $saved = $kernel->settings()->commit($preview['preview_id'], $kernel->settings()->snapshot()['version']);
    $assert($saved['values']['wishlist.max_items'] === 75, 'Typed settings persisted');
    $assert($saved['version'] >= 2, 'First committed settings advance beyond the initial revision');
    $reject(fn () => $kernel->settings()->commit($preview['preview_id'], $saved['version'] - 1), 'version_conflict');
    $reject(fn () => $kernel->settings()->preview([['key' => 'marketplace.enabled', 'value' => true, 'scope' => 'site']]), 'approval_required');
    $audit = $store->create('audit', ['fixture' => true], 'user:' . $admin->ID);
    $reject(fn () => $store->delete('audit', $audit['id'], 1), 'retention_required');
    $queue = $kernel->queue(); $runs = 0;
    $queue->register('core.fixture', static function () use (&$runs): void { $runs++; }, static fn ($payload, $actor) => $actor > 0 && user_can($actor, 'manage_options'));
    $effect = 'core-test-' . Ids::uuid();
    $first = $queue->enqueue('core.fixture', ['fixture' => true], ['effect_key' => $effect]);
    $again = $queue->enqueue('core.fixture', ['fixture' => true], ['effect_key' => $effect]);
    $assert($first === $again, 'Durable queue effect deduplication');
    $reject(fn () => $queue->enqueue('core.fixture', ['fixture' => false], ['effect_key' => $effect]), 'job_idempotency_conflict');
    $reject(fn () => $queue->enqueue('core.fixture', ['fixture' => true], ['effect_key' => $effect, 'actor_id' => 0]), 'job_idempotency_conflict');
    $queue->run(100); $assert($runs === 1, 'Real leased job executes once in this uninterrupted scenario');
    $queue->register('core.denied', static function (): void { throw new RuntimeException('Unauthorized handler ran'); }, static fn () => false);
    $denied = $queue->enqueue('core.denied', [], ['effect_key' => 'deny-' . Ids::uuid()]); $queue->run(100);
    $state = $wpdb->get_var($wpdb->prepare('SELECT state FROM ' . $db->table('jobs') . ' WHERE id=%s', $denied));
    $assert($state === 'poison', 'Revoked permission quarantines the job');
    $reject(fn () => $queue->enqueue('missing.handler', [], ['effect_key' => 'missing-' . Ids::uuid()]), 'handler_unavailable');
    $diagnostic = $kernel->diagnostics(); $assert(count($diagnostic['checks']) >= 4, 'Safe diagnostics available');
    $assert(!str_contains(wp_json_encode($diagnostic), DB_PASSWORD), 'Diagnostics exclude database credentials');
    $assert(!$kernel->modules()->enabled('MOD-U024'), 'Marketplace activation unavailable without prerequisites');
    $reject(fn () => $kernel->modules()->transition('MOD-U001', 'disabled', 1, 'fixture_test'), 'module_lifecycle_unavailable');
    $modules = $kernel->modules();
    $moduleVersion = $modules->all()['MOD-U003']['version'];
    $stopped = $modules->transition('MOD-U003', 'disabled_new_operations', $moduleVersion, 'fixture_test');
    $assert($stopped['version'] === $moduleVersion + 1 && !$modules->accepts('MOD-U003', 'new') && $modules->accepts('MOD-U003', 'read'), 'Stop new wishlist work but keep reads');
    $reject(fn () => $modules->transition('MOD-U003', 'enabled', $moduleVersion, 'fixture_test'), 'version_conflict');
    $disabled = $modules->transition('MOD-U003', 'disabled', $stopped['version'], 'fixture_test');
    $assert(!$modules->accepts('MOD-U003', 'read') && $modules->accepts('MOD-U003', 'cleanup'), 'Disabled wishlist preserves cleanup rights');
    $again = $modules->transition('MOD-U003', 'enabled', $disabled['version'], 'fixture_test');
    $assert($again['version'] === $moduleVersion + 3 && $modules->enabled('MOD-U003'), 'Reenable existing module data without reset');
} finally { $wpdb->query('ROLLBACK'); wp_set_current_user($previous); }
echo wp_json_encode(['suite' => 'core WordPress/MariaDB integration', 'assertions' => $checks, 'result' => 'PASS', 'wp' => get_bloginfo('version'), 'woocommerce' => WC_VERSION, 'php' => PHP_VERSION]) . PHP_EOL;
