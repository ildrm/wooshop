<?php
declare(strict_types=1);
/** Twenty actual PHP/MariaDB connections compete for one account's points. Synthetic local site only. */
$loyaltyWorker = ($argv[1] ?? '') === 'worker';
if ($loyaltyWorker) { define('SHORTINIT', true); }
require_once '/var/www/html/wp-load.php';
if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Refusing reward race outside the isolated fixture store.'); }
require_once '/workspace/packages/plugin/wooshop-commerce/autoload.php';
require_once '/workspace/tests/catalog/loyalty-fixture.php';

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Merchandising\Loyalty;
use WooShop\Commerce\Merchandising\LoyaltyPolicy;
use WooShop\Commerce\Support\DomainError;

$rewardRaceDb = new Database($GLOBALS['wpdb']); $rewardRaceStore = new EntityStore($rewardRaceDb);
if ($loyaltyWorker) {
    $directory = $argv[2] ?? ''; $index = $argv[3] ?? '';
    if (!str_starts_with($directory, '/tmp/wooshop-loyalty-race-') || !is_dir($directory) || !ctype_digit($index)) { throw new RuntimeException('Invalid synthetic barrier.'); }
    $fixture = json_decode((string) file_get_contents($directory . '/fixture.json'), true, 32, JSON_THROW_ON_ERROR);
    $GLOBALS['loyalty_fixture_actor'] = $fixture['customer_id'];
    if (!function_exists('get_current_user_id')) { function get_current_user_id(): int { return (int) $GLOBALS['loyalty_fixture_actor']; } }
    if (!function_exists('current_user_can')) { function current_user_can(string $capability): bool { return false; } }
    $service = new Loyalty($rewardRaceDb, $rewardRaceStore, new LoyaltyPolicy($fixture['policy']), true);
    file_put_contents($directory . '/ready-' . $index, 'ready');
    $deadline = microtime(true) + 40;
    while (!is_file($directory . '/go') && microtime(true) < $deadline) { usleep(10000); clearstatcache(); }
    if (!is_file($directory . '/go')) { fwrite(STDERR, "barrier_timeout\n"); exit(2); }
    try { $reservation = $service->reserve(100, 'synthetic-race-' . basename($directory) . '-' . $index); echo json_encode(['result' => 'held', 'id' => $reservation['id']], JSON_THROW_ON_ERROR); }
    catch (DomainError $error) { echo json_encode(['result' => $error->errorCode], JSON_THROW_ON_ERROR); }
    exit;
}
if (!function_exists('proc_open')) { throw new RuntimeException('proc_open unavailable; reward race NOT RUN.'); }
$tag = bin2hex(random_bytes(8)); $admin = get_users(['role' => 'administrator', 'number' => 1])[0];
$customer = wp_insert_user(['user_login' => 'reward_race_' . $tag, 'user_email' => 'reward-race-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
if (is_wp_error($customer)) { throw new RuntimeException('Cannot create isolated race owner.'); }
$policy = catalog_loyalty_policy_fixture(get_woocommerce_currency(), wc_get_price_decimals(), (string) $admin->ID, (string) $customer);
$programId = Input::stableId('loyalty_program'); $previousProgram = $rewardRaceStore->get('loyalty_program', $programId);
$service = new Loyalty($rewardRaceDb, $rewardRaceStore, $policy, true); wp_set_current_user($customer);
$order = wc_create_order(['customer_id' => $customer]); if (is_wp_error($order)) { throw new RuntimeException('Cannot create race order.'); }
$order->set_total('100'); $order->set_currency(get_woocommerce_currency()); $order->set_date_paid(time() - 1); $order->set_status('completed'); $order->update_meta_data('_wooshop_catalog_test_origin', 'loyalty-race:' . $tag); $order->save();
$service->earnPaidOrder((string) $order->get_id()); $service->releaseDue();
$directory = '/tmp/wooshop-loyalty-race-' . $tag; if (!mkdir($directory, 0700)) { throw new RuntimeException('Cannot create race barrier.'); }
file_put_contents($directory . '/fixture.json', json_encode(['customer_id' => $customer, 'policy' => $policy->data], JSON_THROW_ON_ERROR));
$processes = [];
for ($i = 0; $i < 20; $i++) {
    $pipes = []; $process = proc_open([PHP_BINARY, __FILE__, 'worker', $directory, (string) $i], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/var/www/html');
    if (!is_resource($process)) { throw new RuntimeException('Cannot start reward race worker.'); } fclose($pipes[0]); $processes[] = [$process, $pipes];
}
$deadline = microtime(true) + 40;
do { $ready = count(glob($directory . '/ready-*')); if ($ready === 20) { break; } usleep(10000); clearstatcache(); } while (microtime(true) < $deadline);
file_put_contents($directory . '/go', 'go'); $results = []; $errors = [];
foreach ($processes as [$process, $pipes]) {
    $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process); $result = json_decode($stdout, true);
    if ($code !== 0 || !is_array($result)) { $errors[] = ['exit' => $code, 'stderr' => $stderr, 'stdout' => $stdout]; } else { $results[] = $result['result']; }
}
$counts = array_count_values($results); $balance = $service->balance();
foreach (glob($directory . '/*') as $file) { unlink($file); } rmdir($directory);
if ($ready !== 20 || $errors || ($counts['held'] ?? 0) !== 1 || ($counts['insufficient_points'] ?? 0) !== 19 || $balance['available'] !== 0 || $balance['held'] !== 100) { throw new RuntimeException('Reward race invariant failed: ' . json_encode(['ready' => $ready, 'counts' => $counts, 'balance' => $balance, 'errors' => $errors], JSON_THROW_ON_ERROR)); }
// Retain native orders and immutable movements as evidence. Only the temporary barrier was removed.
if ($previousProgram === null) { wp_set_current_user($admin->ID); $storedProgram = $rewardRaceStore->get('loyalty_program', $programId); $service->setMode('drain', $storedProgram['version']); }
echo "PASS real20-process reward race:1held,19insufficient_points;available=0held=100;synthetic order/journal evidence retained;no gateway requested.\n";
