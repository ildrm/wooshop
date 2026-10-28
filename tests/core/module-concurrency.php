<?php
// Invoked only by tools/module-concurrency.py through WP-CLI eval-file.
use WooShop\Commerce\Kernel\Plugin;
use WooShop\Commerce\Support\DomainError;

if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local' || !in_array((int) wp_parse_url(home_url('/'), PHP_URL_PORT), [18880, 18881], true)) { throw new RuntimeException('Local source fixture environment required.'); }
$mode = $args[0] ?? ''; $run = $args[1] ?? '';
if (!in_array($mode, ['setup', 'command', 'transition', 'restore'], true) || !preg_match('/^[a-f0-9]{32}$/D', $run)) { throw new RuntimeException('Invalid fixed assurance command.'); }
$kernel = Plugin::instance();
if (!$kernel) { throw new RuntimeException('Installed companion plugin unavailable.'); }
$admin = get_user_by('login', 'fixture-admin');
if (!$admin || !user_can($admin, 'manage_options')) { throw new RuntimeException('Verified synthetic administrator is missing.'); }
wp_set_current_user($admin->ID);
$db = $kernel->database(); $modules = $kernel->modules(); $entities = $kernel->entities();
$module = 'MOD-U003';
$emit = static function (array $value): void { fwrite(STDOUT, 'WOOSHOP_MODULE_RACE='.wp_json_encode($value, JSON_UNESCAPED_SLASHES)."\n"); fflush(STDOUT); };
$getState = static function () use ($modules, $module): array {
    $state = $modules->all()[$module] ?? null;
    if (!$state || empty($state['lifecycle_supported'])) { throw new RuntimeException('Managed wishlist lifecycle is unavailable.'); }
    return $state;
};
$hex = hash('sha256', 'wooshop.assurance.module.'.$run);
$proofId = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-4'.substr($hex, 13, 3).'-8'.substr($hex, 17, 3).'-'.substr($hex, 20, 12);
try {
    if ($mode === 'setup') {
        $prior = $getState();
        $modules->transition($module, 'enabled', $prior['version'], 'assurance_prepare_'.$run);
        $current = $getState();
        if ($current['state'] !== 'enabled' || !$current['record_id'] || !$entities->get('module_state', $current['record_id'])) { throw new RuntimeException('Stable committed module state row was not initialized.'); }
        $emit(['event' => 'setup', 'prior_state' => $prior['state'], 'prior_version' => $prior['version'], 'prepared_version' => $current['version'], 'record_id' => $current['record_id'], 'proof_id' => $proofId]);
    } elseif ($mode === 'command') {
        $db->wpdb()->query('SET SESSION innodb_lock_wait_timeout=5');
        $db->transaction(function () use ($modules, $module, $entities, $run, $proofId, $emit): void {
            if (!$modules->accepts($module, 'new')) { throw new RuntimeException('Enabled module rejected the transaction before the race.'); }
            $entities->create('assurance.module-command', ['run' => $run, 'module_id' => $module, 'effect' => 'accepted_new_work'], 'assurance:module-race', $proofId);
            $emit(['event' => 'shared_lock_ready', 'proof_id' => $proofId, 'in_transaction' => true]);
            stream_set_timeout(STDIN, 8);
            $instruction = fgets(STDIN);
            if ($instruction === false || trim($instruction) !== 'COMMIT') { throw new RuntimeException('Controlled command was aborted or timed out; transaction rolls back.'); }
        });
        $emit(['event' => 'command_committed', 'proof_id' => $proofId]);
    } elseif ($mode === 'transition') {
        $db->wpdb()->query('SET SESSION innodb_lock_wait_timeout=5');
        $state = $getState(); $started = microtime(true);
        $emit(['event' => 'transition_attempt', 'version' => $state['version']]);
        $changed = $modules->transition($module, 'disabled_new_operations', $state['version'], 'assurance_race_'.$run);
        $denied = !$modules->accepts($module, 'new');
        $proof = $entities->get('assurance.module-command', $proofId);
        if (!$denied || !$proof || $proof['data']['run'] !== $run) { throw new RuntimeException('Transition completed without denial or committed admitted-work evidence.'); }
        $emit(['event' => 'transition_committed', 'state' => $changed['state'], 'version' => $changed['version'], 'new_work_denied' => $denied, 'admitted_effect_committed' => true, 'wait_seconds' => microtime(true) - $started]);
    } else {
        $priorState = $args[2] ?? ''; $preparedVersion = (int) ($args[3] ?? 0);
        if (!in_array($priorState, ['enabled', 'disabled_new_operations', 'draining', 'disabled'], true) || $preparedVersion < 1) { throw new RuntimeException('Invalid origin-bound restoration target.'); }
        $current = $getState();
        $record = $entities->get('module_state', $current['record_id']);
        $ownReasons = ['assurance_prepare_'.$run, 'assurance_race_'.$run];
        $unchangedEnabled = $priorState === 'enabled' && $current['state'] === 'enabled' && $current['version'] === $preparedVersion;
        if (!$unchangedEnabled && !in_array($record['data']['reason_code'] ?? '', $ownReasons, true)) { throw new RuntimeException('Another writer changed module state; refusing to overwrite it during restoration.'); }
        $restored = $modules->transition($module, $priorState, $current['version'], 'assurance_restore_'.$run);
        if ($restored['state'] !== $priorState) { throw new RuntimeException('Prior logical state was not restored.'); }
        $emit(['event' => 'restored', 'state' => $restored['state'], 'version' => $restored['version'], 'evidence_retained' => true]);
    }
} catch (Throwable $error) {
    $emit(['event' => 'failed', 'code' => $error instanceof DomainError ? $error->errorCode : 'assurance_failure', 'message' => $error->getMessage()]);
    exit(1);
}
