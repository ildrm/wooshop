<?php
declare(strict_types=1);
namespace WooShop\Commerce\Jobs;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;

final class Queue
{
    private array $handlers = [];
    private const DELAYS = [
        'financial' => [30, 120, 600, 1800, 7200],
        'critical' => [30, 120, 600, 1800, 7200],
        'projection' => [10, 60, 300, 900],
        'ordinary' => [10, 60, 300, 900],
        'import' => [10, 60, 300, 900],
        'optional' => [60, 300, 1800],
        'transactional_notification' => [60, 300, 1800, 7200],
    ];
    public function __construct(private readonly Database $db) {}
    public function register(string $name, callable $handler, callable $permission): void
    {
        if (!preg_match('/^[a-z][a-z0-9_.-]{0,99}$/D', $name)) { throw new \InvalidArgumentException('Invalid trusted handler name.'); }
        $this->handlers[$name] = ['handler' => $handler, 'permission' => $permission];
    }
    public function enqueue(string $handler, array $payload, array $options = []): string
    {
        $class = $options['class'] ?? 'ordinary';
        if (!isset(self::DELAYS[$class]) || !isset($this->handlers[$handler])) { throw new DomainError('handler_unavailable', 'A compatible registered job handler is required.', 503); }
        $json = json_encode(['version' => 1, 'data' => $this->canonical($payload)], JSON_THROW_ON_ERROR);
        if (strlen($json) > 262144) { throw new DomainError('job_too_large', 'Job payload exceeds its bounded size.', 413); }
        $key = $options['effect_key'] ?? null;
        if (!is_string($key) || strlen($key) < 16 || strlen($key) > 256) { throw new DomainError('effect_key_required', 'A stable business-effect key is required.'); }
        $effect = hash('sha256', $handler . ':' . $key);
        $id = Ids::uuid(); $now = $this->db->now();
        $sql = $this->db->wpdb()->prepare('INSERT IGNORE INTO ' . $this->db->table('jobs') . ' (id,handler,job_class,payload,actor_id,effect_key,state,attempts,available_at,expires_at,created_at,updated_at) VALUES (%s,%s,%s,%s,%d,%s,%s,0,%s,NULLIF(%s,\'\'),%s,%s)', $id, $handler, $class, $json, (int) ($options['actor_id'] ?? get_current_user_id()), $effect, 'pending', gmdate('Y-m-d H:i:s', (int) ($options['available_at'] ?? time())), isset($options['expires_at']) ? gmdate('Y-m-d H:i:s', (int) $options['expires_at']) : '', $now, $now);
        if ($this->db->wpdb()->query($sql) === false) { throw new DomainError('queue_unavailable', 'The job could not be durably queued.', 503); }
        $stored = $this->db->wpdb()->get_row($this->db->wpdb()->prepare('SELECT id,payload,actor_id,job_class FROM ' . $this->db->table('jobs') . ' WHERE effect_key=%s', $effect), ARRAY_A);
        if (!$stored) { throw new DomainError('queue_unavailable', 'The queued command could not be read.', 503); }
        if ((int) $stored['actor_id'] !== (int) ($options['actor_id'] ?? get_current_user_id()) || $stored['job_class'] !== $class || !hash_equals(hash('sha256', $stored['payload']), hash('sha256', $json))) {
            throw new DomainError('job_idempotency_conflict', 'This job key is already bound to a different command scope or payload.', 409);
        }
        return $stored['id'];
    }
    public function run(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit)); $counts = ['succeeded' => 0, 'retried' => 0, 'poison' => 0, 'cancelled' => 0, 'lease_lost' => 0];
        $wpdb = $this->db->wpdb(); $table = $this->db->table('jobs');
        for ($i = 0; $i < $limit; $i++) {
            $token = Ids::uuid(); $now = $this->db->now();
            $job = $this->db->transaction(function () use ($wpdb, $table, $token, $now): ?array {
                $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE (state='pending' AND available_at<=%s) OR (state='running' AND lease_until<%s) ORDER BY available_at,id LIMIT 1 FOR UPDATE", $now, $now), ARRAY_A);
                if (!$row) { return null; }
                $seconds = $row['job_class'] === 'import' ? 120 : 60;
                if ($wpdb->query($wpdb->prepare("UPDATE {$table} SET state='running',attempts=attempts+1,lease_token=%s,lease_until=%s,updated_at=%s WHERE id=%s", $token, gmdate('Y-m-d H:i:s', time() + $seconds), $now, $row['id'])) !== 1) { throw new DomainError('job_claim_failed', 'The worker could not persist its lease.', 503); }
                $row['attempts'] = (int) $row['attempts'] + 1;
                $row['lease_token'] = $token;
                return $row;
            });
            if (!$job) { break; }
            $previousActor = get_current_user_id();
            try {
                if ($job['expires_at'] && strtotime($job['expires_at'] . ' UTC') <= time() && $job['job_class'] === 'optional') {
                    $counts[$this->finish($job, 'cancelled', 'opportunity_expired') ? 'cancelled' : 'lease_lost']++; continue;
                }
                $envelope = json_decode($job['payload'], true, 64, JSON_THROW_ON_ERROR);
                if (($envelope['version'] ?? null) !== 1) { throw new DomainError('payload_version_unsupported', 'Job payload version is unsupported.', 422); }
                if (!isset($this->handlers[$job['handler']])) { throw new DomainError('handler_unavailable', 'Job handler is unavailable.', 503); }
                $handler = $this->handlers[$job['handler']];
                wp_set_current_user((int) $job['actor_id']);
                if (!(($handler['permission'])($envelope['data'], (int) $job['actor_id']))) { throw new DomainError('job_permission_revoked', 'The initiating scope no longer permits this work.', 403); }
                ($handler['handler'])($envelope['data'], $job);
                if (!$this->finish($job, 'succeeded', null)) { throw new DomainError('lease_lost', 'Worker lease expired or was reclaimed.', 409); }
                $counts['succeeded']++;
            } catch (\Throwable $error) {
                $code = $error instanceof DomainError ? $error->errorCode : 'handler_failed';
                $permanent = $error instanceof DomainError && in_array($error->status, [400, 401, 403, 404, 422], true);
                $delays = self::DELAYS[$job['job_class']] ?? [];
                if ($permanent || $job['attempts'] > count($delays)) {
                    $counts[$this->finish($job, 'poison', $code) ? 'poison' : 'lease_lost']++;
                } else {
                    $delay = $delays[$job['attempts'] - 1];
                    $changed = $wpdb->query($wpdb->prepare("UPDATE {$table} SET state='pending',error_code=%s,available_at=%s,lease_token=NULL,lease_until=NULL,updated_at=%s WHERE id=%s AND lease_token=%s AND lease_until>=%s", $code, gmdate('Y-m-d H:i:s', time() + $delay), $this->db->now(), $job['id'], $job['lease_token'], $this->db->now()));
                    $counts[$changed === 1 ? 'retried' : 'lease_lost']++;
                }
            } finally { wp_set_current_user($previousActor); }
        }
        return $counts;
    }
    public function heartbeat(string $id, string $token): bool
    {
        $wpdb = $this->db->wpdb();
        return $wpdb->query($wpdb->prepare('UPDATE ' . $this->db->table('jobs') . " SET lease_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL CASE WHEN job_class='import' THEN 120 ELSE 60 END SECOND) WHERE id=%s AND state='running' AND lease_token=%s AND lease_until>=UTC_TIMESTAMP()", $id, $token)) === 1;
    }
    public function counts(): array
    {
        $rows = $this->db->wpdb()->get_results('SELECT state,COUNT(*) AS total FROM ' . $this->db->table('jobs') . ' GROUP BY state', ARRAY_A);
        $result = []; foreach ($rows ?: [] as $row) { $result[$row['state']] = (int) $row['total']; } return $result;
    }
    private function finish(array $job, string $state, ?string $error): bool
    {
        $wpdb = $this->db->wpdb();
        return $wpdb->query($wpdb->prepare('UPDATE ' . $this->db->table('jobs') . ' SET state=%s,error_code=%s,lease_token=NULL,lease_until=NULL,updated_at=%s WHERE id=%s AND lease_token=%s AND lease_until>=%s', $state, $error ?? '', $this->db->now(), $job['id'], $job['lease_token'], $this->db->now())) === 1;
    }
    private function canonical(mixed $value): mixed
    {
        if (!is_array($value)) { return $value; }
        if (!array_is_list($value)) { ksort($value, SORT_STRING); }
        return array_map($this->canonical(...), $value);
    }
}
