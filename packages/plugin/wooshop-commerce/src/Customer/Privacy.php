<?php
declare(strict_types=1);
namespace WooShop\Commerce\Customer;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class Privacy
{
    private Database $db; private EntityStore $store;
    private const OWNED = ['wishlist', 'preferences', 'recent_history', 'comparison', 'product_alert', 'review_helpful', 'question_counter', 'unsubscribe_token', 'customer_command'];
    public function __construct(Database $db, EntityStore $store) { $this->db = $db; $this->store = $store; }

    public function register(): void
    {
        add_action('init', static function (): void { if (!wp_next_scheduled('wooshop_commerce_customer_cleanup')) { wp_schedule_event(time() + 3600, 'hourly', 'wooshop_commerce_customer_cleanup'); } });
        add_action('wooshop_commerce_customer_cleanup', [$this, 'cleanup']);
        add_filter('wp_privacy_personal_data_exporters', function (array $exporters): array {
            $exporters['wooshop-customer'] = ['exporter_friendly_name' => __('Commerce customer preferences and saved products', 'wooshop-commerce'), 'callback' => [$this, 'export']]; return $exporters;
        });
        add_filter('wp_privacy_personal_data_erasers', function (array $erasers): array {
            $erasers['wooshop-customer'] = ['eraser_friendly_name' => __('Commerce optional customer data', 'wooshop-commerce'), 'callback' => [$this, 'erase']]; return $erasers;
        });
    }

    public function export(string $email, int $page = 1): array
    {
        $user = get_user_by('email', $email); if (!$user) { return ['data' => [], 'done' => true]; }
        $records = $this->owned('user:' . $user->ID, max(0, $page - 1) * 100); $data = [];
        foreach ($records as $record) {
            $kind = $record['kind'];
            $payload = $record['data']; unset($payload['token_hash'], $payload['digest'], $payload['result']);
            $data[] = ['group_id' => 'wooshop-customer', 'group_label' => __('Commerce customer data', 'wooshop-commerce'), 'item_id' => $record['id'], 'data' => [['name' => __('Record type', 'wooshop-commerce'), 'value' => $kind], ['name' => __('Saved data', 'wooshop-commerce'), 'value' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE)]]];
        }
        return ['data' => $data, 'done' => count($records) < 100];
    }

    public function erase(string $email, int $page = 1): array
    {
        $user = get_user_by('email', $email); if (!$user) { return ['items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true]; }
        $removed = false; $owner = 'user:' . $user->ID;
        foreach ($this->owned($owner) as $record) { $this->store->delete($record['kind'], $record['id'], $record['version']); $removed = true; }
        // Pending optional messages are stopped independently of provider availability.
        foreach (['email', 'sms'] as $channel) {
            foreach (['alert', 'marketing'] as $purpose) {
                $id = Input::stableId($owner . ':' . $purpose . ':' . $channel);
                if (!$this->store->get('notification_suppression', $id)) { $this->store->create('notification_suppression', ['purpose' => $purpose, 'channel' => $channel, 'suppressed_at' => gmdate('Y-m-d\TH:i:s\Z'), 'reason' => 'erasure'], $owner, $id); }
            }
        }
        $retained = $this->store->find('operation', $owner, 1) !== [] || $this->store->find('product_question', $owner, 1) !== [];
        return ['items_removed' => $removed, 'items_retained' => $retained, 'messages' => $retained ? [__('Question archives, minimal operation and suppression metadata require the store retention review. Complete provider or evidence deletion is not claimed.', 'wooshop-commerce')] : [], 'done' => $this->owned($owner) === []];
    }

    private function owned(string $owner, int $offset = 0): array
    {
        $wpdb = $this->db->wpdb(); $table = $this->db->table('entities');
        $placeholders = implode(',', array_fill(0, count(self::OWNED), '%s'));
        $sql = $wpdb->prepare("SELECT kind,id FROM {$table} WHERE owner_id=%s AND kind IN ({$placeholders}) ORDER BY id LIMIT 100 OFFSET %d", $owner, ...array_merge(self::OWNED, [$offset]));
        $result = [];
        foreach ($wpdb->get_results($sql, ARRAY_A) ?: [] as $reference) { $record = $this->store->get($reference['kind'], $reference['id']); if ($record && $record['owner_id'] === $owner) { $result[] = $record; } }
        return $result;
    }

    /** Bounded optional-data expiry only; never touches orders, evidence, ledger or other modules. */
    public function cleanup(): void
    {
        $kinds = ['wishlist', 'preferences', 'recent_history', 'comparison', 'product_alert', 'unsubscribe_token', 'customer_command', 'catalog_rate'];
        foreach ($kinds as $kind) {
            $option = 'wooshop_customer_cleanup_' . $kind;
            $cursor = get_option($option, null); $cursor = is_string($cursor) && $cursor !== '' ? $cursor : null;
            $records = $this->store->find($kind, null, 100, $cursor); $last = null;
            foreach ($records as $record) {
                $last = $record['id']; $data = $record['data']; $expired = false;
                if ($kind === 'catalog_rate') { $expired = ($data['expires_at'] ?? PHP_INT_MAX) < time(); }
                elseif ($kind === 'customer_command') { $expired = strtotime($record['created_at'] . ' UTC') < time() - 172800; }
                elseif (isset($data['expires_at'])) { $expiry = is_int($data['expires_at']) ? $data['expires_at'] : strtotime($data['expires_at']); $expired = $expiry <= time(); }
                elseif (str_starts_with($record['owner_id'], 'guest:')) { $expired = strtotime($record['updated_at'] . ' UTC') < time() - 2592000; }
                if ($kind === 'recent_history' && !$expired) {
                    $items = array_values(array_filter($data['items'], static fn (array $item): bool => $item['viewed_at'] > time() - 2592000));
                    if ($items !== $data['items']) { $data['items'] = $items; try { $this->store->update($kind, $record['id'], $record['version'], $data); } catch (DomainError $error) { /* A newer user change wins; next bounded sweep reconciles. */ } }
                }
                if ($expired) { try { $this->store->delete($kind, $record['id'], $record['version']); } catch (DomainError $error) { /* Never erase a concurrently revised record. */ } }
            }
            update_option($option, count($records) === 100 ? $last : null, false);
        }
    }
}
