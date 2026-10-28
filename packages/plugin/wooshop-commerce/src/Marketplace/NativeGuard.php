<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

/** Hooks verified against WooCommerce 11.2.1 installed-source archive. Activation is still gated. */
final class NativeGuard
{
    private static bool $schemaRegistered = false;
    public function register(): void
    {
        add_filter('woocommerce_is_purchasable', [$this, 'purchasable'], 100, 2);
        add_filter('woocommerce_variation_is_purchasable', [$this, 'purchasable'], 100, 2);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'addValidation'], 100, 6);
        add_filter('woocommerce_update_cart_validation', [$this, 'updateValidation'], 100, 4);
        add_action('woocommerce_check_cart_items', [$this, 'checkCart']);
        add_action('woocommerce_store_api_validate_cart_item', [$this, 'storeValidation'], 100, 2);
        add_filter('woocommerce_store_api_add_to_cart_data', [$this, 'storeAddData'], 100, 2);
        add_filter('woocommerce_order_item_quantity', [$this, 'nativeReductionQuantity'], 100, 3);
        add_filter('woocommerce_can_restore_order_stock', [$this, 'nativeRestoreAllowed'], 100, 2);
        add_filter('woocommerce_can_restock_refunded_items', [$this, 'nativeRestoreAllowed'], 100, 2);
        add_action('woocommerce_blocks_loaded', [$this, 'storeSchema']);
        if (did_action('woocommerce_blocks_loaded')) { $this->storeSchema(); }
    }

    public function mapped(object $product): bool
    {
        if ($product->get_meta('_wooshop_authority_mode', true) === 'marketplace_plugin') { return true; }
        // Cart sessions can retain a product object created before an authority cutover.
        // Resolve current native metadata through WooCommerce CRUD before accepting that object.
        $current = $product->get_id() ? wc_get_product($product->get_id()) : false;
        if ($current && $current->get_meta('_wooshop_authority_mode', true) === 'marketplace_plugin') { return true; }
        if ($product->get_parent_id()) {
            $parent = wc_get_product($product->get_parent_id());
            return $parent && $parent->get_meta('_wooshop_authority_mode', true) === 'marketplace_plugin';
        }
        return false;
    }

    public function purchasable(bool $allowed, object $product): bool { return $this->mapped($product) ? false : $allowed; }

    public function addValidation(bool $allowed, int $productId, mixed $quantity, int $variationId = 0, array $variations = [], array $cartData = []): bool
    {
        $product = wc_get_product($variationId ?: $productId);
        if (($product && $this->mapped($product)) || isset($cartData['wooshop_offer_id'])) {
            wc_add_notice(__('This marketplace item is unavailable while required payment and inventory safeguards are being verified.', 'wooshop-commerce'), 'error');
            return false;
        }
        return $allowed;
    }

    public function updateValidation(bool $allowed, string $key, array $values, mixed $quantity): bool
    {
        return $this->addValidation($allowed, (int) $values['product_id'], $quantity, (int) ($values['variation_id'] ?? 0), [], $values);
    }

    public function checkCart(): void
    {
        if (!function_exists('WC') || !WC()->cart) { return; }
        foreach (WC()->cart->get_cart() as $values) {
            $product = $values['data'] ?? null;
            if (($product && $this->mapped($product)) || isset($values['wooshop_offer_id'])) {
                wc_add_notice(__('Marketplace checkout is unavailable. Remove the affected item or contact support.', 'wooshop-commerce'), 'error');
                break;
            }
        }
    }

    public function storeValidation(object $product, array $cartItem): void
    {
        if ($this->mapped($product) || isset($cartItem['wooshop_offer_id'])) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('commerce_marketplace_unavailable', __('Marketplace checkout requires verified payment and stock safeguards.', 'wooshop-commerce'), 503);
        }
    }

    public function storeAddData(array $data, object $request): array
    {
        $extensions = $request['extensions'] ?? [];
        $commerce = $extensions['commerce'] ?? [];
        if (!is_array($commerce)) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('commerce_invalid_extension', 'Invalid commerce extension.', 400);
        }
        if ($commerce) {
            throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException('commerce_marketplace_unavailable', __('Marketplace offers are unavailable pending activation verification.', 'wooshop-commerce'), 503);
        }
        return $data;
    }

    public function nativeReductionQuantity(mixed $quantity, object $order, object $item): mixed
    {
        $product = $item->get_product();
        return ($item->get_meta('_wooshop_offer_id', true) || ($product && $this->mapped($product))) ? 0 : $quantity;
    }

    /** Legacy mapped-line restock must use epoch-aware inspected adjustment, never native shell stock. */
    public function nativeRestoreAllowed(bool $allowed, object $order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($item->get_meta('_wooshop_offer_id', true) || ($product && $this->mapped($product))) { return false; }
        }
        return $allowed;
    }

    /** A cutover cannot be enabled by a UI toggle. No mutation occurs before all gates pass. */
    public function persistShell(object $product, int $epoch): void
    {
        (new ActivationGate())->requireMarketplace();
        $product->set_manage_stock(true);
        $product->set_stock_quantity(0);
        $product->set_stock_status('outofstock');
        $product->set_backorders('no');
        $product->update_meta_data('_wooshop_authority_mode', 'marketplace_plugin');
        $product->update_meta_data('_wooshop_authority_epoch', $epoch);
        $product->save();
    }

    public function storeSchema(): void
    {
        if (self::$schemaRegistered || !function_exists('woocommerce_store_api_register_endpoint_data')) { return; }
        woocommerce_store_api_register_endpoint_data([
            'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
            'namespace' => 'commerce',
            'data_callback' => static function (array $item): array {
                return ['offer_id' => null, 'offer_version' => null, 'configuration_digest' => null, 'accepted_quote_id' => null, 'marketplace_available' => false];
            },
            'schema_callback' => static fn (): array => [
                'offer_id' => ['type' => ['string', 'null'], 'readonly' => true],
                'offer_version' => ['type' => ['integer', 'null'], 'readonly' => true],
                'configuration_digest' => ['type' => ['string', 'null'], 'readonly' => true],
                'accepted_quote_id' => ['type' => ['string', 'null'], 'readonly' => true],
                'marketplace_available' => ['type' => 'boolean', 'readonly' => true],
            ],
            'schema_type' => ARRAY_A,
        ]);
        self::$schemaRegistered = true;
    }
}
