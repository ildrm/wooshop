<?php
declare(strict_types=1);
namespace WooShop\Commerce\Merchandising;

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Catalog\Products;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class PriceHistory
{
    private Database $db; private EntityStore $store; private Products $products;
    public function __construct(Database $db, EntityStore $store, Products $products) { $this->db = $db; $this->store = $store; $this->products = $products; }

    public static function minor(string $decimal, int $exponent): string
    {
        if ($exponent < 0 || $exponent > 6 || !preg_match('/^(0|[1-9][0-9]{0,14})(?:\.([0-9]{1,6}))?$/D', $decimal, $parts)) { throw new DomainError('invalid_price', 'A nonnegative exact decimal price is required.', 422); }
        $fraction = $parts[2] ?? '';
        if (strlen($fraction) > $exponent && trim(substr($fraction, $exponent), '0') !== '') { throw new DomainError('invalid_price_precision', 'Price exceeds the configured currency precision.', 422); }
        $minor = ltrim($parts[1] . str_pad(substr($fraction, 0, $exponent), $exponent, '0'), '0') ?: '0';
        if (strlen($minor) > 15) { throw new DomainError('price_out_of_range', 'Price exceeds the supported range.', 422); }
        return $minor;
    }

    /** Trusted WooCommerce source only. Does not accept customer-supplied amounts. */
    public function observe($product): ?array
    {
        if (!$product instanceof \WC_Product || !in_array($product->get_type(), ['simple', 'variation'], true) || $product->get_status() !== 'publish' || $product->get_price('edit') === '') { return null; }
        $id = (string) $product->get_id(); $this->products->get($id);
        $data = ['product_id' => $id, 'offer_id' => null, 'amount_minor' => self::minor((string) $product->get_price('edit'), wc_get_price_decimals()), 'currency' => get_woocommerce_currency(), 'exponent' => wc_get_price_decimals(), 'tax_basis' => wc_prices_include_tax() ? 'catalog_including_tax' : 'catalog_excluding_tax'];
        $digest = hash('sha256', wp_json_encode($data));
        return $this->db->transaction(function () use ($id, $data, $digest): array {
            $sourceId = Input::stableId('price_source:' . $id); $source = $this->store->get('price_source', $sourceId);
            if ($source && $source['data']['digest'] === $digest) { return $this->store->get('price_observation', $source['data']['observation_id']); }
            $sourceVersion = $source ? $source['version'] + 1 : 1;
            $observation = $this->store->create('price_observation', $data + ['observed_at' => gmdate('Y-m-d\TH:i:s\Z'), 'source_revision' => $sourceVersion, 'correction_of' => null], 'product:' . $id, Input::stableId('price:' . $id . ':' . $sourceVersion));
            $sourceData = ['digest' => $digest, 'observation_id' => $observation['id']];
            if ($source) { $this->store->update('price_source', $sourceId, $source['version'], $sourceData); }
            else { $this->store->create('price_source', $sourceData, 'product:' . $id, $sourceId); }
            return $observation;
        });
    }

    public function points(string $product, string $currency, int $exponent, string $basis, int $days = 90): array
    {
        $this->products->get(Input::id($product));
        if (!in_array($days, [30, 90, 180, 365], true) || !preg_match('/^[A-Z]{3}$/D', $currency) || $exponent < 0 || $exponent > 6) { throw new DomainError('invalid_history_scope', 'Invalid price history scope.', 422); }
        $points = []; $cursor = null;
        // The query remains bounded; source history is never interpolated or mixed across currencies/tax bases.
        for ($page = 0; $page < 10; $page++) {
            $records = $this->store->find('price_observation', 'product:' . $product, 100, $cursor);
            foreach ($records as $record) {
                $data = $record['data']; $cursor = $record['id'];
                if ($data['currency'] === $currency && $data['exponent'] === $exponent && $data['tax_basis'] === $basis && strtotime($data['observed_at']) >= time() - $days * 86400) { $points[] = ['id' => $record['id']] + $data; }
            }
            if (count($records) < 100) { break; }
        }
        usort($points, static fn (array $a, array $b): int => strcmp($a['observed_at'], $b['observed_at']) ?: $a['source_revision'] <=> $b['source_revision']);
        return $points;
    }

    public function register(): void
    {
        add_action('woocommerce_product_object_updated_props', function ($product, array $properties): void {
            if (!array_intersect($properties, ['regular_price', 'sale_price', 'price', 'date_on_sale_from', 'date_on_sale_to'])) { return; }
            try { $this->observe($product); } catch (DomainError $error) { do_action('wooshop_commerce_price_observation_failed', (string) $product->get_id(), $error->errorCode); }
        }, 20, 2);
        add_shortcode('commerce_price_history', function ($attributes): string {
            $approved = function_exists('wooshop_commerce_setting') ? wooshop_commerce_setting('history.public_enabled', false) : false;
            if ($approved !== true) { return '<p>' . esc_html__('Public price history is unavailable pending store policy review.', 'wooshop-commerce') . '</p>'; }
            $attributes = shortcode_atts(['product_id' => (string) get_the_ID()], $attributes);
            try { $points = $this->points((string) $attributes['product_id'], get_woocommerce_currency(), wc_get_price_decimals(), wc_prices_include_tax() ? 'catalog_including_tax' : 'catalog_excluding_tax'); }
            catch (DomainError $error) { return '<p>' . esc_html($error->getMessage()) . '</p>'; }
            if (count($points) < 2) { return '<p>' . esc_html__('Insufficient price history. Missing days are not estimated.', 'wooshop-commerce') . '</p>'; }
            $html = '<table class="commerce-table"><caption>' . esc_html__('Recorded catalog prices', 'wooshop-commerce') . ' · ' . esc_html(wc_prices_include_tax() ? __('Including tax', 'wooshop-commerce') : __('Excluding tax', 'wooshop-commerce')) . '</caption><thead><tr><th scope="col">' . esc_html__('Observed at (UTC)', 'wooshop-commerce') . '</th><th scope="col">' . esc_html__('Price', 'wooshop-commerce') . '</th></tr></thead><tbody>';
            foreach ($points as $point) { $html .= '<tr><th scope="row"><time datetime="' . esc_attr($point['observed_at']) . '">' . esc_html($point['observed_at']) . '</time></th><td>' . wp_kses_post(wc_price(Loyalty::major($point['amount_minor'], $point['exponent']), ['currency' => $point['currency']])) . '</td></tr>'; }
            return $html . '</tbody></table>';
        });
    }
}
