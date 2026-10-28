<?php
declare(strict_types=1);

namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Finance\Money;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Support\DomainError;

final class OfferService
{
    private Records $r;
    public function __construct(private Database $db) { $this->r = new Records($db); }

    /** Internal catalog command: creates a moderated proposal and never changes canonical claims. */
    public function createDraft(string $seller, string $product, ?string $variation, string $pool, array $terms, string $sku, ?Money $price, int $epoch): array
    {
        $this->nativeProduct($product, $variation);
        $this->r->one('sellers', $seller);
        $p = $this->r->one('inventory_pools', $pool);
        if ($p['seller_id'] !== $seller || (int) $p['authority_epoch'] !== $epoch) { throw new DomainError('pool_scope_conflict', 'Pool seller and authority epoch must match.', 422); }
        Input::text($sku, 64);
        Input::fields($terms, ['condition', 'origin_id', 'shipping_profile_ref', 'warranty_terms_ref', 'return_terms_ref', 'destination_regions', 'estimated_delivery_end'], ['condition', 'origin_id', 'shipping_profile_ref', 'warranty_terms_ref', 'return_terms_ref', 'destination_regions']);
        if (!in_array($terms['condition'], ['new', 'used', 'refurbished'], true) || !is_array($terms['destination_regions']) || !$terms['destination_regions'] || count($terms['destination_regions']) > 250 || $terms['origin_id'] !== $p['origin_id']) { throw new DomainError('invalid_terms', 'Offer requires valid condition, own origin and explicit destinations.', 422); }
        foreach (['origin_id', 'shipping_profile_ref', 'warranty_terms_ref', 'return_terms_ref'] as $field) { Input::text($terms[$field], 200); }
        foreach ($terms['destination_regions'] as $region) { if (!is_string($region) || !preg_match('/^[A-Z0-9-]{2,20}$/D', $region)) { throw new DomainError('invalid_destination', 'Invalid destination region.', 422); } }
        $currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'XXX';
        $exponent = function_exists('wc_get_price_decimals') ? wc_get_price_decimals() : 2;
        if ($price !== null) { $this->price($price, $currency, $exponent); }
        $id = Records::id();
        $this->r->insert('offers', ['id' => $id, 'seller_id' => $seller, 'product_id' => $product, 'variation_id' => $variation ?? 0, 'pool_id' => $pool, 'seller_sku' => $sku, 'state' => 'draft', 'price_minor' => $price?->minor, 'currency' => $currency, 'exponent' => $exponent, 'terms_version' => 1, 'authority_epoch' => $epoch, 'data' => Records::json($terms), 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
        return $this->project($this->r->one('offers', $id), null, true);
    }

    public function update(string $id, array $body, int $version, int $actor, string $key): array
    {
        Input::fields($body, ['price', 'seller_sku', 'state', 'terms_version']);
        if (!$body) { throw new DomainError('invalid_fields', 'At least one writable offer field is required.', 400); }
        return $this->r->command('offer:'.$actor.':'.$id, $key, [$body, $version], function () use ($id, $body, $version): array {
            $offer = $this->r->one('offers', $id, true);
            Records::version($offer, $version);
            $seller = $this->r->one('sellers', $offer['seller_id']);
            if ($seller['state'] !== 'approved') { throw new DomainError('seller_unavailable', 'An approved seller is required for commercial updates.', 409); }
            $target = $body['state'] ?? $offer['state'];
            $allowed = ['draft' => ['draft', 'review', 'retired'], 'review' => ['draft', 'review', 'retired'], 'active' => ['active', 'paused', 'review', 'retired'], 'paused' => ['paused', 'review', 'retired'], 'retired' => ['retired']];
            if (!is_string($target) || !in_array($target, $allowed[$offer['state']] ?? [], true)) { throw new DomainError('invalid_transition', 'Offer requires independent review before publication.', 409); }
            $changes = ['state' => $target, 'updated_at' => $this->db->now(), 'version' => $version + 1];
            if (array_key_exists('price', $body)) {
                $price = $body['price'] === null ? null : Money::from($body['price']);
                if ($price) { $this->price($price, $offer['currency'], (int) $offer['exponent']); }
                if ($price === null && $target !== 'draft') { throw new DomainError('price_required', 'Null price is allowed only on a draft offer.', 422); }
                $changes['price_minor'] = $price?->minor;
            }
            if (isset($body['seller_sku'])) { $changes['seller_sku'] = Input::text($body['seller_sku'], 64); }
            if (isset($body['terms_version'])) {
                if (!is_int($body['terms_version']) || $body['terms_version'] <= (int) $offer['terms_version']) { throw new DomainError('invalid_terms_version', 'Changed terms require a newer version and review.', 422); }
                $changes['terms_version'] = $body['terms_version'];
                $changes['state'] = 'review';
            }
            if ($target === 'active') { (new ActivationGate())->requireMarketplace(); }
            if ($this->db->wpdb()->update($this->db->table('offers'), $changes, ['id' => $id, 'version' => $version]) !== 1) { throw new DomainError('write_conflict', 'Offer could not be updated.', 409); }
            return $this->project($this->r->one('offers', $id), null, true);
        });
    }

    public function publicPage(string $product, ?string $variation, ?string $destination, int $limit, ?string $after): array
    {
        $this->nativeProduct($product, $variation);
        if ($destination !== null && !preg_match('/^[A-Z0-9-]{2,20}$/D', $destination)) { throw new DomainError('invalid_destination', 'Invalid destination region.', 400); }
        $w = $this->db->wpdb();
        $rows = $w->get_results($w->prepare('SELECT * FROM '.$this->db->table('offers').' WHERE product_id=%s AND variation_id=%s AND state=%s AND id>%s ORDER BY id LIMIT %d', $product, $variation ?? '0', 'active', $after ?? '', min(1000, max(1, $limit) * 10 + 1)), ARRAY_A);
        $items = [];
        foreach ($rows as $row) {
            $offer = $this->project($row, $destination);
            if ($offer['eligible']) { $items[] = $offer; }
            if (count($items) >= $limit + 1) { break; }
        }
        $hasMore = count($items) > $limit;
        $items = array_slice($items, 0, $limit);
        return ['items' => $items, 'after' => $hasMore ? end($items)['id'] : null];
    }

    public function project(array $row, ?string $destination, bool $owned = false): array
    {
        $seller = $this->r->one('sellers', $row['seller_id']);
        $pool = $this->r->one('inventory_pools', $row['pool_id']);
        $terms = json_decode($row['data'], true, 512, JSON_THROW_ON_ERROR);
        $code = $row['state'] !== 'active' ? 'offer_unpublished' : ($seller['state'] !== 'approved' ? 'seller_unavailable' : ($row['price_minor'] === null ? 'price_unavailable' : ($pool['state'] !== 'active' ? 'stock_quarantined' : ((int) $pool['on_hand'] <= (int) $pool['reserved'] ? 'stock_unavailable' : null))));
        if ($code === null && ($destination === null || !in_array($destination, $terms['destination_regions'] ?? [], true))) { $code = 'destination_required_or_unsupported'; }
        if ($code === null) { $code = 'marketplace_activation_blocked'; }
        $price = $row['price_minor'] === null ? null : (new Money((string) $row['price_minor'], $row['currency'], (int) $row['exponent']))->jsonSerialize();
        $result = ['id' => $row['id'], 'product_id' => (string) $row['product_id'], 'variation_id' => (int) $row['variation_id'] ? (string) $row['variation_id'] : null, 'seller_id' => $row['seller_id'], 'seller_display_name' => $seller['display_name'], 'condition' => $terms['condition'], 'price' => $price, 'availability' => $code === null ? 'available' : 'unavailable', 'terms_version' => (int) $row['terms_version'], 'version' => (int) $row['version'], 'estimated_delivery_end' => $terms['estimated_delivery_end'] ?? null, 'eligible' => $code === null, 'ineligibility_code' => $code];
        if ($owned) { $result['seller_sku'] = $row['seller_sku']; $result['state'] = $row['state']; }
        return $result;
    }

    public static function lineIdentity(string $site, string $product, ?string $variation, string $offer, string $configuration, string $fulfillment, string $contract = ''): string
    {
        return hash('sha256', Records::canonical([$site, $product, $variation, $offer, $configuration, $fulfillment, $contract]));
    }

    public function nativeProduct(string $product, ?string $variation): object
    {
        Input::nativeId($product);
        if ($variation !== null) { Input::nativeId($variation); }
        if (!function_exists('wc_get_product')) { throw new DomainError('woocommerce_unavailable', 'WooCommerce is required.', 503); }
        $parent = wc_get_product((int) $product);
        if (!$parent || $parent->get_status() !== 'publish' || $parent->is_type('variation')) { throw new DomainError('not_found', 'Product not found.', 404); }
        if ($parent->is_type('variable')) {
            $item = $variation ? wc_get_product((int) $variation) : null;
            if (!$item || !$item->is_type('variation') || (string) $item->get_parent_id() !== $product || $item->get_status() !== 'publish') { throw new DomainError('exact_variation_required', 'Choose a published exact variation of this product.', 422); }
            return $item;
        }
        if ($variation !== null || !$parent->is_type('simple')) { throw new DomainError('unsupported_product', 'This marketplace product type requires a verified adapter.', 422); }
        return $parent;
    }

    private function price(Money $price, string $currency, int $exponent): void
    {
        if ($price->minor < 0 || $price->currency !== $currency || $price->exponent !== $exponent) { throw new DomainError('invalid_offer_price', 'Price must be nonnegative and use the configured currency and exponent.', 422); }
    }
}
