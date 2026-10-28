<?php
declare(strict_types=1);
namespace WooShop\Commerce\Customer;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Products;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

/** Persists intention and suppression; no transport is activated without an approved provider. */
final class Notifications
{
    private Database $db; private EntityStore $store; private Records $records; private Session $session; private Products $products; private CustomerService $customer;
    public function __construct(Database $db, EntityStore $store, Session $session, Products $products, CustomerService $customer)
    { $this->db = $db; $this->store = $store; $this->records = new Records($db, $store); $this->session = $session; $this->products = $products; $this->customer = $customer; }

    public function subscribe(array $input, string $key): array
    {
        if (get_current_user_id() < 1) { throw new DomainError('unauthenticated', 'Sign in and verify your account contact to request alerts.', 401); }
        Input::keys($input, ['product_id', 'variation_id', 'offer_id', 'kind', 'threshold', 'channel'], ['product_id', 'kind', 'channel']);
        $selection = $this->products->selection(array_intersect_key($input, array_flip(['product_id', 'variation_id', 'offer_id'])));
        if (!in_array($input['channel'], ['email', 'sms'], true) || !in_array($input['kind'], ['back_in_stock', 'price_drop'], true)) { throw new DomainError('invalid_alert', 'Choose a supported alert and channel.', 422); }
        $channel = $input['channel'];
        if (!get_user_meta(get_current_user_id(), 'wooshop_verified_' . $channel . '_at', true)) { throw new DomainError('contact_unverified', 'Account contact verification is required before alerts can be requested.', 403); }
        $preference = $this->customer->preferences();
        if (!$preference['marketing_' . $channel] || $this->suppressed($this->session->owner(), 'alert', $channel)) { throw new DomainError('consent_required', 'Enable this optional channel in preferences before requesting alerts.', 403); }
        if ($input['kind'] === 'price_drop' && !isset($input['threshold'])) { throw new DomainError('threshold_required', 'Choose a price threshold.', 422); }
        if (isset($input['threshold'])) {
            if (!is_array($input['threshold'])) { throw new DomainError('invalid_money', 'A typed price threshold is required.', 422); }
            Input::keys($input['threshold'], ['amount_minor', 'currency', 'exponent'], ['amount_minor', 'currency', 'exponent']);
            $money = $input['threshold'];
            if (!is_string($money['amount_minor']) || !preg_match('/^(0|[1-9][0-9]{0,14})$/D', $money['amount_minor']) || !is_string($money['currency']) || $money['currency'] !== get_woocommerce_currency() || !is_int($money['exponent']) || $money['exponent'] !== wc_get_price_decimals()) { throw new DomainError('invalid_money', 'Threshold must use this store currency, exponent and nonnegative minor units.', 422); }
        }
        return $this->records->command($this->session->owner(), 'alert.subscribe', $key, $input, function () use ($input, $selection, $preference): array {
            $owner = $this->session->owner();
            $alerts = $this->store->find('product_alert', $owner, 100);
            $active = 0;
            foreach ($alerts as $existing) {
                if (in_array($existing['data']['state'], ['pending', 'active'], true) && strtotime($existing['data']['expires_at']) > time()) {
                    $active++;
                    if (CustomerService::selectionKey($existing['data']) === CustomerService::selectionKey($selection) && $existing['data']['channel'] === $input['channel'] && $existing['data']['kind'] === $input['kind']) { return $this->operation($existing['data']['operation_id']); }
                }
            }
            if ($active >= 50) { throw new DomainError('alert_limit', 'The alert limit is 50. Remove an existing alert before adding another.', 422); }
            $operation = $this->store->create('operation', ['state' => 'pending', 'resource_id' => null, 'correlation_id' => wp_generate_uuid4(), 'retry_after_seconds' => 0], $owner);
            $record = $this->store->create('product_alert', $selection + ['kind' => $input['kind'], 'channel' => $input['channel'], 'threshold' => $input['threshold'] ?? null, 'state' => 'pending', 'provider_state' => 'provider_unavailable', 'consent_revision' => $preference['consent_version'], 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 7776000), 'last_trigger_revision' => null, 'operation_id' => $operation['id']], $owner);
            $data = $operation['data']; $data['resource_id'] = $record['id'];
            $this->store->update('operation', $operation['id'], $operation['version'], $data);
            // Record is deliberately pending. There is no send or success event without a provider.
            return $this->operation($operation['id']);
        });
    }

    private function operation(string $id): array
    {
        $record = $this->store->get('operation', $id);
        if (!$record || $record['owner_id'] !== $this->session->owner()) { throw new DomainError('not_found', 'Operation unavailable.', 404); }
        return ['operation_id' => $record['id']] + $record['data'];
    }

    public function ownAlerts(): array { return $this->store->find('product_alert', $this->session->owner(), 100); }

    public function cancel(string $id, int $revision): void
    {
        $record = $this->store->get('product_alert', Input::uuid($id));
        if (!$record || $record['owner_id'] !== $this->session->owner()) { throw new DomainError('not_found', 'Alert unavailable.', 404); }
        Records::expect($record, $revision); $data = $record['data']; $data['state'] = 'suppressed';
        $this->store->update('product_alert', $id, $revision, $data);
    }

    public function suppressed(string $owner, string $purpose, string $channel): bool
    {
        return $this->store->get('notification_suppression', Input::stableId($owner . ':' . $purpose . ':' . $channel)) !== null;
    }

    /** Transport adapters call this immediately before sending, never rely on an enqueue snapshot. */
    public function eligible(string $alertId): bool
    {
        $record = $this->store->get('product_alert', $alertId);
        if (!$record || !in_array($record['data']['state'], ['active', 'pending'], true) || strtotime($record['data']['expires_at']) <= time()) { return false; }
        $data = $record['data']; $owner = $record['owner_id'];
        if (!preg_match('/^user:([1-9][0-9]*)$/D', $owner, $match) || !get_user_meta((int) $match[1], 'wooshop_verified_' . $data['channel'] . '_at', true) || $this->suppressed($owner, 'alert', $data['channel'])) { return false; }
        $preferences = $this->store->get('preferences', Input::stableId('preferences:' . $owner));
        if (!$preferences || !$preferences['data']['marketing_' . $data['channel']]) { return false; }
        try {
            $this->products->selection(array_intersect_key($data, array_flip(['product_id', 'variation_id', 'offer_id'])));
            $product = $this->products->get($data['variation_id'] ?? $data['product_id']);
        } catch (DomainError $error) { return false; }
        if ($data['kind'] === 'back_in_stock') { return $product->is_in_stock(); }
        if ($data['offer_id'] !== null) { return false; } // Offer price requires a verified marketplace projection adapter.
        $money = $data['threshold'];
        if ($money['currency'] !== get_woocommerce_currency() || $money['exponent'] !== wc_get_price_decimals()) { return false; }
        $price = $product->get_price();
        if (!is_string($price) || !preg_match('/^[0-9]+(?:\.[0-9]+)?$/D', $price)) { return false; }
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '');
        if (strlen($fraction) > $money['exponent'] && trim(substr($fraction, $money['exponent']), '0') !== '') { return false; }
        $minor = ltrim($whole . str_pad(substr($fraction, 0, $money['exponent']), $money['exponent'], '0'), '0') ?: '0';
        return strlen($minor) < strlen($money['amount_minor']) || (strlen($minor) === strlen($money['amount_minor']) && strcmp($minor, $money['amount_minor']) <= 0);
    }

    /** Internal sender use only; a token cannot read or replace the recipient. */
    public function unsubscribeToken(string $owner, string $purpose, string $channel): string
    {
        if (!in_array($purpose, ['alert', 'marketing'], true) || !in_array($channel, ['email', 'sms'], true) || !preg_match('/^user:[1-9][0-9]*$/D', $owner)) { throw new DomainError('invalid_scope', 'Invalid optional-message scope.', 422); }
        $random = bin2hex(random_bytes(32));
        $record = $this->store->create('unsubscribe_token', ['token_hash' => hash('sha256', $random), 'purpose' => $purpose, 'channel' => $channel, 'expires_at' => time() + 15552000, 'site' => home_url('/')], $owner);
        $base = $record['id'] . '.' . $random;
        return $base . '.' . hash_hmac('sha256', $base . ':' . $owner . ':' . $purpose . ':' . $channel . ':' . $record['data']['expires_at'] . ':' . home_url('/'), wp_salt('auth'));
    }

    public function unsubscribe(array $input): void
    {
        Input::keys($input, ['token'], ['token']);
        if (!is_string($input['token']) || strlen($input['token']) < 32 || strlen($input['token']) > 2048) { throw new DomainError('invalid_token_shape', 'A valid token field is required.', 400); }
        if (!preg_match('/^([a-f0-9-]{36})\.([a-f0-9]{64})\.([a-f0-9]{64})$/D', $input['token'], $parts)) { return; }
        $record = $this->store->get('unsubscribe_token', $parts[1]);
        if (!$record || $record['data']['expires_at'] <= time() || $record['data']['site'] !== home_url('/') || !hash_equals($record['data']['token_hash'], hash('sha256', $parts[2]))) { return; }
        $data = $record['data']; $owner = $record['owner_id'];
        $signature = hash_hmac('sha256', $parts[1] . '.' . $parts[2] . ':' . $owner . ':' . $data['purpose'] . ':' . $data['channel'] . ':' . $data['expires_at'] . ':' . home_url('/'), wp_salt('auth'));
        if (!hash_equals($signature, $parts[3])) { return; }
        try { $this->db->transaction(function () use ($owner, $data): void {
            $id = Input::stableId($owner . ':' . $data['purpose'] . ':' . $data['channel']);
            if (!$this->store->get('notification_suppression', $id)) { $this->store->create('notification_suppression', ['purpose' => $data['purpose'], 'channel' => $data['channel'], 'suppressed_at' => gmdate('Y-m-d\TH:i:s\Z')], $owner, $id); }
            foreach ($this->store->find('product_alert', $owner, 100) as $alert) {
                if ($data['purpose'] === 'alert' && $alert['data']['channel'] === $data['channel'] && $alert['data']['state'] !== 'suppressed') { $updated = $alert['data']; $updated['state'] = 'suppressed'; $this->store->update('product_alert', $alert['id'], $alert['version'], $updated); }
            }
        }); } catch (\Throwable $error) {
            if (!$this->suppressed($owner, $data['purpose'], $data['channel'])) { throw new DomainError('suppression_unavailable', 'The opt-out could not be recorded. Please retry.', 503); }
        }
    }
}
