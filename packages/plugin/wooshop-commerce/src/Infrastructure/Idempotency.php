<?php
declare(strict_types=1);
namespace WooShop\Commerce\Infrastructure;

use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Rest;

final class Idempotency
{
    public function __construct(private readonly Database $db) {}
    public function run(\WP_REST_Request $request, callable $command): mixed
    {
        $key = Rest::idempotency($request);
        $scope = hash('sha256', get_current_user_id() . '|' . $request->get_method() . '|' . $request->get_route() . '|' . $key);
        $intent = $request->get_json_params();
        $digest = hash('sha256', json_encode([$this->canonical($intent), $request->get_header('if-match')], JSON_THROW_ON_ERROR));
        return $this->db->transaction(function () use ($scope, $digest, $command): mixed {
            $wpdb = $this->db->wpdb(); $table = $this->db->table('operations');
            $insert = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$table} (operation_key,actor_id,request_digest,state,created_at) VALUES (%s,%d,%s,'pending',%s)", $scope, get_current_user_id(), $digest, $this->db->now()));
            if ($insert === false) { throw new DomainError('operation_storage_unavailable', 'Command state could not be saved.', 503); }
            $record = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE operation_key=%s FOR UPDATE", $scope), ARRAY_A);
            if (!$record || !hash_equals($record['request_digest'], $digest)) { throw new DomainError('idempotency_conflict', 'This command key was used for different intent.', 409); }
            if ($record['state'] === 'succeeded') {
                $saved = json_decode($record['response'], true, 64, JSON_THROW_ON_ERROR);
                return new \WP_REST_Response($saved['data'], $saved['status'], $saved['headers']);
            }
            if ($insert === 0) { throw new DomainError('command_pending', 'The original command is not yet resolved.', 409); }
            $response = rest_ensure_response($command());
            if (is_wp_error($response)) { throw new DomainError('command_failed', 'The command was not completed.', 422); }
            $saved = json_encode(['data' => $response->get_data(), 'status' => $response->get_status(), 'headers' => $response->get_headers()], JSON_THROW_ON_ERROR);
            if ($wpdb->query($wpdb->prepare("UPDATE {$table} SET state='succeeded',response=%s WHERE operation_key=%s", $saved, $scope)) !== 1) { throw new DomainError('operation_commit_failed', 'Command result could not be saved.', 503); }
            return $response;
        });
    }
    private function canonical(mixed $value): mixed
    {
        if (!is_array($value)) { return $value; }
        if (!array_is_list($value)) { ksort($value, SORT_STRING); }
        return array_map($this->canonical(...), $value);
    }
}
