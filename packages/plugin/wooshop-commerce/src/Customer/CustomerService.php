<?php
declare(strict_types=1);
namespace WooShop\Commerce\Customer;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Products;
use WooShop\Commerce\Catalog\Specifications;
use WooShop\Commerce\Catalog\Units;
use WooShop\Commerce\Catalog\Options;
use WooShop\Commerce\Catalog\ModuleAccess;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class CustomerService
{
    private Database $db;
    private EntityStore $store;
    private Records $records;
    private Session $session;
    private Products $products;
    private Specifications $specs;

    public function __construct(Database $db, EntityStore $store, Session $session, Products $products, Specifications $specs)
    {
        $this->db = $db; $this->store = $store; $this->records = new Records($db, $store); $this->session = $session; $this->products = $products; $this->specs = $specs;
    }

    private function listRecord(?string $owner = null): array
    {
        $owner = $owner ?? $this->session->owner();
        $record = $this->records->own('wishlist', $owner, ['items' => [], 'merged' => false, 'expires_at' => str_starts_with($owner, 'guest:') ? time() + Options::integer('wishlist.guest_days', 30, 1, 90) * 86400 : null]);
        if (!empty($record['data']['merged']) || ($record['data']['expires_at'] !== null && $record['data']['expires_at'] <= time())) { throw new DomainError('not_found', 'This guest list has expired.', 404); }
        return $record;
    }

    private function wishlistProjection(array $record): array
    {
        return ['id' => $record['id'], 'items' => array_map(static function (array $item): array { return array_intersect_key($item, array_flip(['product_id', 'variation_id', 'offer_id'])); }, $record['data']['items']), 'version' => $record['version']];
    }

    public function wishlist(): array { ModuleAccess::require('MOD-U003', 'read'); return $this->wishlistProjection($this->listRecord()); }

    public function saveItem(array $input, int $version, string $key): array
    {
        ModuleAccess::require('MOD-U003', 'new');
        $item = $this->products->selection($input);
        $owner = $this->session->owner();
        return $this->records->command($owner, 'wishlist.add', $key, $input, function () use ($owner, $item, $version): array {
            ModuleAccess::require('MOD-U003', 'new');
            $record = $this->listRecord($owner); Records::expect($record, $version);
            $data = $record['data'];
            foreach ($data['items'] as $existing) { if (self::selectionKey($existing) === self::selectionKey($item)) { return $this->wishlistProjection($record); } }
            if (count($data['items']) >= Options::integer('wishlist.max_items', 100, 1, 100)) { throw new DomainError('wishlist_full', 'Remove an item before saving another. Your list has reached its capacity.', 422); }
            $data['items'][] = $item + ['added_at' => gmdate('Y-m-d\TH:i:s\Z')];
            return $this->wishlistProjection($this->store->update('wishlist', $record['id'], $version, $data));
        });
    }

    public static function selectionKey(array $item): string { return implode(':', [$item['product_id'], $item['variation_id'] ?? '', $item['offer_id'] ?? '', $item['configuration_digest'] ?? '']); }

    public function removeItem(string $product, int $version, string $key): array
    {
        ModuleAccess::require('MOD-U003', 'cleanup');
        Input::id($product); $owner = $this->session->owner();
        return $this->records->command($owner, 'wishlist.remove', $key, ['product_id' => $product], function () use ($owner, $product, $version): array {
            $record = $this->listRecord($owner); Records::expect($record, $version);
            $data = $record['data'];
            $data['items'] = array_values(array_filter($data['items'], static fn (array $item): bool => $item['product_id'] !== $product));
            return $this->wishlistProjection($this->store->update('wishlist', $record['id'], $version, $data));
        });
    }

    /** Explicit account action requires the still-present signed guest cookie; no client owner/token selector. */
    public function mergeWishlist(int $version, string $key): array
    {
        ModuleAccess::require('MOD-U003', 'new');
        if (get_current_user_id() < 1) { throw new DomainError('unauthenticated', 'Sign in before merging a guest list.', 401); }
        $owner = $this->session->owner(); $guest = $this->session->guestOwner();
        $result = $this->records->command($owner, 'wishlist.merge', $key, [], function () use ($owner, $guest, $version): array {
            ModuleAccess::require('MOD-U003', 'new');
            $account = $this->listRecord($owner); Records::expect($account, $version);
            $source = $this->store->get('wishlist', Input::stableId('wishlist:' . $guest));
            if (!$source || $source['owner_id'] !== $guest || $source['data']['merged'] || $source['data']['expires_at'] <= time()) { return $this->wishlistProjection($account); }
            $items = [];
            foreach (array_merge($account['data']['items'], $source['data']['items']) as $item) {
                $selection = self::selectionKey($item);
                if (!isset($items[$selection]) || strcmp($item['added_at'], $items[$selection]['added_at']) < 0) { $items[$selection] = $item; }
            }
            if (count($items) > Options::integer('wishlist.max_items', 100, 1, 100)) { throw new DomainError('wishlist_full', 'The merged list exceeds capacity. Remove items and retry.', 422); }
            uasort($items, static fn (array $a, array $b): int => strcmp($a['added_at'], $b['added_at']) ?: strcmp(self::selectionKey($a), self::selectionKey($b)));
            $data = $account['data']; $data['items'] = array_values($items);
            $updated = $this->store->update('wishlist', $account['id'], $version, $data);
            $guestData = $source['data']; $guestData['items'] = []; $guestData['merged'] = true;
            $this->store->update('wishlist', $source['id'], $source['version'], $guestData);
            return $this->wishlistProjection($updated);
        });
        $this->session->rotate();
        do_action('wooshop_commerce_wishlist_merged', $result['id'], $result['version']);
        return $result;
    }

    public function preferenceRecord(): array
    {
        return $this->records->own('preferences', $this->session->owner(), ['marketing_email' => false, 'marketing_sms' => false, 'history_enabled' => false, 'locale' => determine_locale(), 'consent_version' => 1, 'notice_version' => (int) get_option('wooshop_commerce_consent_notice_version', 1), 'consented_at' => null]);
    }

    private function preferenceProjection(array $record): array
    {
        return array_intersect_key($record['data'], array_flip(['marketing_email', 'marketing_sms', 'history_enabled', 'locale', 'consent_version'])) + ['version' => $record['version']];
    }

    public function preferences(): array { return $this->preferenceProjection($this->preferenceRecord()); }

    public function updatePreferences(array $input, int $revision, string $key): array
    {
        Input::keys($input, ['marketing_email', 'marketing_sms', 'history_enabled', 'locale', 'consent_notice_version'], ['consent_notice_version']);
        if (Input::positiveInt($input['consent_notice_version']) !== (int) get_option('wooshop_commerce_consent_notice_version', 1)) { throw new DomainError('notice_changed', 'Review the current privacy notice and retry.', 409); }
        foreach (['marketing_email', 'marketing_sms', 'history_enabled'] as $flag) { if (isset($input[$flag])) { Input::bool($input[$flag]); } }
        if (isset($input['locale'])) { Input::text($input['locale'], 2, 20); if (!preg_match('/^[a-zA-Z]{2,3}(?:[_-][a-zA-Z0-9]{2,8})*$/D', $input['locale'])) { throw new DomainError('invalid_locale', 'Invalid locale.', 422); } }
        if (!empty($input['history_enabled']) && !$this->preferences()['history_enabled']) { ModuleAccess::require('MOD-U005', 'new'); }
        $owner = $this->session->owner();
        $result = $this->records->command($owner, 'preferences.update', $key, $input, function () use ($input, $revision, $owner): array {
            $record = $this->preferenceRecord(); Records::expect($record, $revision);
            if (!empty($input['history_enabled']) && !$record['data']['history_enabled']) { ModuleAccess::require('MOD-U005', 'new'); }
            $data = array_replace($record['data'], array_intersect_key($input, array_flip(['marketing_email', 'marketing_sms', 'history_enabled', 'locale'])));
            $data['consent_version']++; $data['notice_version'] = $input['consent_notice_version']; $data['consented_at'] = gmdate('Y-m-d\TH:i:s\Z');
            $result = $this->store->update('preferences', $record['id'], $revision, $data);
            if (!$data['history_enabled']) {
                $history = $this->records->own('recent_history', $owner, ['items' => [], 'deletion_revision' => 1]);
                $this->store->update('recent_history', $history['id'], $history['version'], ['items' => [], 'deletion_revision' => $history['data']['deletion_revision'] + 1]);
            }
            foreach ($this->store->find('product_alert', $owner, 100) as $alert) {
                if (!$data['marketing_' . $alert['data']['channel']]) { $alertData = $alert['data']; $alertData['state'] = 'suppressed'; $this->store->update('product_alert', $alert['id'], $alert['version'], $alertData); }
            }
            return $this->preferenceProjection($result);
        });
        do_action('wooshop_commerce_consent_changed', $owner, $result['consent_version']);
        return $result;
    }

    /** Internal history command. Account upload remains unavailable without explicit reviewed sync configuration. */
    public function history(string $action, ?string $product, int $deletionRevision, string $key): array
    {
        if (!in_array($action, ['read', 'record', 'clear'], true)) { throw new DomainError('invalid_action', 'Unsupported history action.', 422); }
        ModuleAccess::require('MOD-U005', ['read' => 'read', 'record' => 'new', 'clear' => 'cleanup'][$action]);
        $owner = $this->session->owner();
        $read = fn (): array => $this->records->own('recent_history', $owner, ['items' => [], 'deletion_revision' => 1]);
        if ($action === 'read') { $record = $read(); return $record['data']; }
        return $this->records->command($owner, 'history.' . $action, $key, ['product_id' => $product, 'deletion_revision' => $deletionRevision], function () use ($action, $product, $deletionRevision, $owner, $read): array {
            ModuleAccess::require('MOD-U005', $action === 'clear' ? 'cleanup' : 'new');
            $record = $read(); $data = $record['data'];
            if ($deletionRevision !== $data['deletion_revision']) { throw new DomainError('history_cleared', 'History changed; late writes are discarded.', 409); }
            if ($action === 'clear') { $data['items'] = []; $data['deletion_revision']++; }
            else {
                if (!$this->preferences()['history_enabled']) { throw new DomainError('consent_required', 'Enable browsing history before recording a view.', 403); }
                if (str_starts_with($owner, 'user:') && Options::get('recent.account_sync', false) !== true) { throw new DomainError('account_sync_disabled', 'History remains on this device; account synchronization is disabled.', 422); }
                $this->products->get(Input::id($product));
                $data['items'] = array_values(array_filter($data['items'], static fn (array $item): bool => $item['product_id'] !== $product && $item['viewed_at'] > time() - 2592000));
                array_unshift($data['items'], ['product_id' => $product, 'viewed_at' => time(), 'consent_revision' => $this->preferences()['consent_version']]);
                $data['items'] = array_slice($data['items'], 0, Options::integer('recent.max_items', 20, 1, 50));
            }
            return $this->store->update('recent_history', $record['id'], $record['version'], $data)['data'];
        });
    }

    public function compare(array $input, string $key): array
    {
        ModuleAccess::require('MOD-U004', 'new');
        Input::keys($input, ['product_ids'], ['product_ids']);
        if (!is_array($input['product_ids']) || !array_is_list($input['product_ids']) || count($input['product_ids']) < 2 || count($input['product_ids']) > Options::integer('compare.max_items', 4, 2, 4) || count(array_unique($input['product_ids'], SORT_REGULAR)) !== count($input['product_ids'])) { throw new DomainError('comparison_limit', 'Select distinct products within the comparison limit (maximum four).', 422); }
        foreach ($input['product_ids'] as $id) { $this->products->get(Input::id($id)); }
        $this->records->command($this->session->owner(), 'comparison.create', $key, $input, function () use ($input): array {
            ModuleAccess::require('MOD-U004', 'new');
            $record = $this->records->own('comparison', $this->session->owner(), ['product_ids' => [], 'expires_at' => time() + 2592000]);
            $this->store->update('comparison', $record['id'], $record['version'], ['product_ids' => $input['product_ids'], 'expires_at' => time() + 2592000]);
            return ['product_ids' => $input['product_ids']];
        });
        // Evidence expiry/revocation is authoritative even when a selection request is replayed.
        return $this->comparison($input['product_ids']);
    }

    public function savedComparison(): ?array
    {
        $record = $this->comparisonSelection();
        if (!$record || count($record['data']['product_ids']) < 2) { return null; }
        return $this->comparison($record['data']['product_ids']);
    }

    public function comparisonSelection(): ?array
    {
        ModuleAccess::require('MOD-U004', 'read');
        $record = $this->store->get('comparison', Input::stableId('comparison:' . $this->session->owner()));
        if (!$record || $record['owner_id'] !== $this->session->owner() || $record['data']['expires_at'] <= time()) { return null; }
        return $record;
    }

    public function clearComparison(int $version, string $key): void
    {
        ModuleAccess::require('MOD-U004', 'cleanup');
        $owner = $this->session->owner();
        $this->records->command($owner, 'comparison.clear', $key, [], function () use ($owner, $version): array {
            $record = $this->store->get('comparison', Input::stableId('comparison:' . $owner));
            if (!$record || $record['owner_id'] !== $owner) { throw new DomainError('not_found', 'No saved comparison was found.', 404); }
            Records::expect($record, $version); $data = $record['data']; $data['product_ids'] = [];
            $this->store->update('comparison', $record['id'], $version, $data);
            return ['cleared' => true];
        });
    }

    public function comparison(array $productIds): array
    {
        ModuleAccess::require('MOD-U004', 'read');
        $columns = []; $keys = []; $dimensions = []; $units = []; $schemaVersion = 1;
        foreach ($productIds as $id) {
            $this->products->get($id);
            try { $spec = $this->specs->read($id); }
            catch (DomainError $error) { if ($error->status !== 404) { throw $error; } $spec = ['values' => [], 'schema_version' => 1, 'schema_id' => '']; }
            $schemaVersion = max($schemaVersion, $spec['schema_version']);
            $fields = $this->specs->fields($spec['schema_id']); $values = [];
            foreach ($fields as $fieldKey => $field) { if (!empty($field['compare'])) { $keys[$fieldKey] = true; } }
            foreach ($spec['values'] as $value) {
                if (empty($fields[$value['key']]['compare'])) { continue; }
                $keys[$value['key']] = true;
                $dimensions[$value['key']][$id] = $value['unit'] === null ? ($fields[$value['key']]['type'] ?? 'text') : Units::dimension($value['unit']);
                if ($value['unit'] !== null && !isset($units[$value['key']])) { $units[$value['key']] = $value['unit']; }
                $values[$value['key']] = $value;
            }
            $columns[$id] = $values;
        }
        $products = [];
        foreach ($columns as $id => $values) {
            $rows = [];
            foreach (array_keys($keys) as $fieldKey) {
                $value = $values[$fieldKey] ?? null;
                $missing = $value === null || $value['value'] === null;
                $nonComparable = isset($dimensions[$fieldKey]) && count(array_unique($dimensions[$fieldKey])) > 1;
                if (!$missing && !$nonComparable && $value['unit'] !== null && $value['unit'] !== $units[$fieldKey]) {
                    try { $value['value'] = Units::convert($value['value'], $value['unit'], $units[$fieldKey]); $value['unit'] = $units[$fieldKey]; }
                    catch (DomainError $error) { $nonComparable = true; }
                }
                $display = $missing ? __('Not supplied', 'wooshop-commerce') : ($nonComparable ? __('Not comparable', 'wooshop-commerce') : (is_bool($value['value']) ? ($value['value'] ? __('Yes', 'wooshop-commerce') : __('No', 'wooshop-commerce')) : (string) $value['value']) . ($value['unit'] ? ' ' . $value['unit'] : ''));
                $rows[] = ['field_key' => $fieldKey, 'display_value' => $display, 'missing' => $missing];
            }
            $products[] = ['product_id' => (string) $id, 'values' => $rows];
        }
        return ['schema_version' => $schemaVersion, 'products' => $products];
    }
}
