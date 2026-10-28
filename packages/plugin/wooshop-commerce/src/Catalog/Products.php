<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Support\DomainError;

final class Products
{
    public function get(string $id)
    {
        Input::id($id);
        if (!function_exists('wc_get_product')) { throw new DomainError('provider_unavailable', 'WooCommerce is unavailable.', 503); }
        $product = wc_get_product((int) $id);
        if (!$product || $product->get_status() !== 'publish' || get_post_field('post_password', (int) $id) !== '') { throw new DomainError('not_found', 'Product unavailable.', 404); }
        if ($product->is_type('variation')) {
            $parent = wc_get_product($product->get_parent_id());
            if (!$parent || $parent->get_status() !== 'publish' || get_post_field('post_password', $parent->get_id()) !== '') { throw new DomainError('not_found', 'Product unavailable.', 404); }
        }
        return $product;
    }

    public function selection(array $input): array
    {
        Input::keys($input, ['product_id', 'variation_id', 'offer_id'], ['product_id']);
        $product = $this->get(Input::id($input['product_id']));
        if ($product->is_type('variation')) { throw new DomainError('invalid_selection', 'Use the parent product and exact variation reference.', 422); }
        if (isset($input['variation_id'])) {
            $variation = $this->get(Input::id($input['variation_id']));
            if (!$variation->is_type('variation') || (string) $variation->get_parent_id() !== $input['product_id']) { throw new DomainError('invalid_variation', 'Variation does not belong to this product.', 422); }
        }
        if (isset($input['offer_id'])) {
            Input::uuid($input['offer_id']);
            // The marketplace module must positively validate the exact selected offer.
            if (!apply_filters('wooshop_commerce_public_offer_valid', false, $input['offer_id'], $input['product_id'], $input['variation_id'] ?? null)) {
                throw new DomainError('invalid_offer', 'Selected offer is unavailable.', 422);
            }
        }
        return ['product_id' => $input['product_id'], 'variation_id' => $input['variation_id'] ?? null, 'offer_id' => $input['offer_id'] ?? null];
    }

    public function capabilities($product): array
    {
        $type = $product->get_type();
        $flows = ['simple' => 'direct', 'variable' => 'select_variation', 'variation' => 'direct', 'grouped' => 'select_children', 'external' => 'redirect'];
        $flow = $flows[$type] ?? 'unsupported';
        if ($flow === 'redirect' && !preg_match('#^https://#i', $product->get_product_url())) { $flow = 'unsupported'; }
        return ['adapter_key' => 'wc-native-v1', 'native_type' => $type, 'purchase_flow' => $flow,
            'price_shape' => in_array($type, ['variable', 'grouped'], true) ? 'range' : ($flow === 'unsupported' ? 'none' : 'scalar'),
            'requires_shipping' => $flow !== 'redirect' && $flow !== 'unsupported' && !$product->is_virtual(),
            'download_entitlement' => $product->is_downloadable(), 'supported' => $flow !== 'unsupported',
            'reason' => $flow === 'unsupported' ? 'A verified provider adapter is required.' : null];
    }

    public function publicCard($product): array
    {
        return ['product_id' => (string) $product->get_id(), 'name' => $product->get_name(), 'url' => $product->get_permalink(),
            'capabilities' => $this->capabilities($product), 'price_html' => $product->get_price_html(), 'in_stock' => $product->is_in_stock()];
    }
}
