<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Finance\Money;
use WooShop\Commerce\Support\DomainError;

/** WooCommerce owns tax/coupon/order values. Item extension snapshots use supported CRUD only. */
final class OrderSnapshots
{
    public function register(): void
    {
        add_action('woocommerce_checkout_order_created', [$this, 'capture'], 10, 1);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'capture'], 10, 1);
    }

    public function capture(object $order): void
    {
        $currency = $order->get_currency();
        $exponent = (int) wc_get_price_decimals();
        foreach ($order->get_items('line_item') as $itemId => $item) {
            $existing = $item->get_meta('_wooshop_purchase_snapshot', true);
            if ($existing) { continue; } // Historical terms cannot be rewritten by current catalog/settings.
            $quantity = $item->get_quantity();
            if (!is_numeric($quantity) || (int) $quantity != $quantity || $quantity < 1 || $quantity > 10000) { throw new DomainError('unsupported_quantity', 'Purchase snapshots require integral supported quantities.', 422); }
            $product = $item->get_product();
            $subtotal = Money::decimal((string) $item->get_subtotal(), $currency, $exponent);
            $net = Money::decimal((string) $item->get_total(), $currency, $exponent);
            $tax = Money::decimal((string) $item->get_total_tax(), $currency, $exponent);
            $data = [
                'schema_version' => 1, 'order_id' => (string) $order->get_id(), 'order_item_id' => (string) $itemId,
                'product_id' => (string) $item->get_product_id(), 'variation_id' => $item->get_variation_id() ? (string) $item->get_variation_id() : null,
                'offer_id' => null, 'offer_version' => null, 'seller_id' => null,
                'seller_display_name' => get_bloginfo('name'), 'seller_sku' => $product ? (string) $product->get_sku() : '',
                'quantity' => (int) $quantity, 'configuration' => $item->get_meta_data() ? array_values(array_filter(array_map(static fn ($meta): ?array => str_starts_with($meta->key, '_') ? null : ['key' => $meta->key, 'value' => is_scalar($meta->value) ? (string) $meta->value : ''], $item->get_meta_data()))) : [],
                'amounts' => ['list' => $subtotal->jsonSerialize(), 'net' => $net->jsonSerialize(), 'discount' => (new Money((string) ($subtotal->minor - $net->minor), $currency, $exponent))->jsonSerialize(), 'tax' => $tax->jsonSerialize()],
                'shipping' => ['authority' => 'woocommerce_order_shipping_items', 'item_allocation' => null],
                'fees' => ['authority' => 'woocommerce_order_fee_items', 'item_allocation' => null],
                'price_source' => 'woocommerce_order_item', 'authority_mode' => 'native_wc', 'authority_epoch' => 0,
                'commission_policy_version' => null, 'terms_version' => null, 'warranty_terms_ref' => null,
                'captured_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            if ($item->get_meta('_wooshop_offer_id', true) || ($product && (new NativeGuard())->mapped($product))) {
                // No partial marketplace snapshot can masquerade as an accepted commercial contract.
                (new ActivationGate())->requireMarketplace();
            }
            $item->add_meta_data('_wooshop_purchase_snapshot', $data, true);
            $item->add_meta_data('_wooshop_purchase_snapshot_digest', hash('sha256', Records::canonical($data)), true);
            $item->save();
        }
    }
}
