<?php
declare(strict_types=1);
/** Real 20-process MariaDB race, run with php inside the isolated site's container. */
if (($argv[1] ?? '') === 'worker') { define('SHORTINIT', true); }
require_once '/var/www/html/wp-load.php';
if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Refusing stock race outside the isolated fixture site.'); }
require_once '/workspace/packages/plugin/wooshop-commerce/autoload.php';
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\Schema;
use WooShop\Commerce\Inventory\InventoryService;
use WooShop\Commerce\Marketplace\Records;
use WooShop\Commerce\Support\DomainError;

$db = new Database($GLOBALS['wpdb']);
if (($argv[1] ?? '') === 'worker') {
    $pool = $argv[2]; $directory = $argv[3]; $i = $argv[4];
    if (!str_starts_with($directory, '/tmp/wooshop-marketplace-race-') || !is_dir($directory)) { throw new RuntimeException('Invalid isolated race directory.'); }
    file_put_contents($directory.'/ready-'.$i, 'ready');
    $deadline = microtime(true) + 45;
    while (!is_file($directory.'/go') && microtime(true) < $deadline) { usleep(10000); clearstatcache(); }
    if (!is_file($directory.'/go')) { fwrite(STDERR, "start_timeout\n"); exit(2); }
    try {
        $hold = (new InventoryService($db))->reserveGroup('parallel-fixture-'.basename($directory).'-'.$i, [['pool_id' => $pool, 'line_key' => 'last-unit', 'quantity' => 1, 'authority_epoch' => 1]], 0);
        echo Records::json(['result' => 'held', 'id' => $hold[0]['id']]);
    } catch (DomainError $e) {
        echo Records::json(['result' => $e->errorCode]);
    }
    exit;
}
if (!function_exists('proc_open')) { throw new RuntimeException('proc_open unavailable; concurrency test NOT RUN.'); }
Schema::install($db);
$tag = bin2hex(random_bytes(8));
$r = new Records($db); $seller = Records::id();
$r->insert('sellers', ['id' => $seller, 'applicant_id' => 0, 'public_slug' => 'race-'.$tag, 'display_name' => 'Synthetic concurrency seller', 'state' => 'approved', 'provider_eligibility' => 'unverified', 'data' => '{}', 'created_at' => $db->now(), 'updated_at' => $db->now()]);
$inventory = new InventoryService($db); $pool = $inventory->createPool($seller, 'synthetic-race-origin', 1, 1);
$directory = '/tmp/wooshop-marketplace-race-'.$tag;
if (!mkdir($directory, 0700)) { throw new RuntimeException('Cannot create isolated race barrier.'); }
$processes = [];
for ($i = 0; $i < 20; ++$i) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, 'worker', $pool['pool_id'], $directory, (string) $i], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/var/www/html');
    if (!is_resource($process)) { throw new RuntimeException('Race worker failed to start.'); }
    fclose($pipes[0]);
    $processes[] = [$process, $pipes];
}
$deadline = microtime(true) + 40;
do {
    $ready = count(glob($directory.'/ready-*'));
    if ($ready === 20) { break; }
    usleep(10000); clearstatcache();
} while (microtime(true) < $deadline);
file_put_contents($directory.'/go', 'go');
$results = []; $errors = [];
foreach ($processes as [$process, $pipes]) {
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process); $result = json_decode($output, true);
    if ($code !== 0 || !is_array($result)) { $errors[] = ['exit' => $code, 'stderr' => $error, 'stdout' => $output]; } else { $results[] = $result['result']; }
}
$counts = array_count_values($results); $state = $inventory->pool($pool['pool_id']);
foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory);
if ($ready !== 20 || $errors || ($counts['held'] ?? 0) !== 1 || ($counts['insufficient_stock'] ?? 0) !== 19 || $state['on_hand'] !== 1 || $state['reserved'] !== 1) { throw new RuntimeException('Concurrent stock invariant failed: '.Records::json(['ready' => $ready, 'counts' => $counts, 'errors' => $errors, 'state' => $state])); }
echo "PASS real 20-process last-unit race: 1 held, 19 insufficient_stock; physical=1 reserved=1. No payments requested.\n";
