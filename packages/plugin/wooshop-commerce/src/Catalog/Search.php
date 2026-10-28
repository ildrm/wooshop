<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Support\DomainError;

final class Search
{
    private Products $products; private Specifications $specs;
    public function __construct(Products $products, Specifications $specs) { $this->products = $products; $this->specs = $specs; }

    public static function normalize(string $query): string
    {
        if (class_exists('Normalizer')) { $query = \Normalizer::normalize($query, \Normalizer::FORM_C); }
        $query = strtr($query, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9', "\u{200C}" => ' ']);
        $query = trim((string) preg_replace('/\s+/u', ' ', $query));
        return function_exists('mb_strtolower') ? mb_strtolower($query, 'UTF-8') : strtolower($query);
    }

    /** Native, bounded fallback: no external index and no persisted raw query text. */
    public function find(string $query, array $filters = [], int $limit = 20, int $page = 1, ?string $category = null): array
    {
        $query = Input::text($query, 0, 120);
        if (count($filters) > 12 || $limit < 1 || $limit > 100 || $page < 1 || $page > 1000) { throw new DomainError('invalid_search', 'Search bounds are invalid.', 400); }
        if ($category !== null) { Input::id($category); }
        $normalized = self::normalize($query);
        $variants = array_unique([$query, strtr($query, ['ی' => 'ي', 'ک' => 'ك']), strtr($query, ['ي' => 'ی', 'ك' => 'ک'])]);
        $ids = []; $truncated = false;
        foreach ($variants as $variant) {
            $args = ['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 201, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'has_password' => false];
            if ($variant !== '') { $args['s'] = $variant; }
            if ($category !== null) { $args['tax_query'] = [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => [(int) $category]]]; }
            $native = new \WP_Query($args);
            if (count($native->posts) > 200) { $truncated = true; }
            foreach (array_slice($native->posts, 0, 200) as $id) { $ids[(string) $id] = true; }
        }
        if ($query !== '') {
            $skuArgs = ['post_type' => ['product', 'product_variation'], 'post_status' => 'publish', 'posts_per_page' => 20, 'fields' => 'ids', 'meta_query' => [['key' => '_sku', 'value' => $query, 'compare' => '=']], 'no_found_rows' => true, 'has_password' => false];
            foreach ((new \WP_Query($skuArgs))->posts as $id) { $ids[(string) $id] = true; }
        }
        $matches = []; $knownFilters = [];
        foreach (array_keys($ids) as $id) {
            try { $product = $this->products->get((string) $id); } catch (DomainError $error) { if ($error->status === 404) { continue; } throw $error; }
            if (!$product->is_visible()) { continue; }
            if ($category !== null && !has_term((int) $category, 'product_cat', $product->is_type('variation') ? $product->get_parent_id() : $product->get_id())) { continue; }
            $values = []; $fields = [];
            try { $spec = $this->specs->read((string) $id); $fields = $this->specs->fields($spec['schema_id']); foreach ($spec['values'] as $value) { if (!empty($fields[$value['key']]['facet']) && $value['value'] !== null) { $values[$value['key']] = $value; $knownFilters[$value['key']] = $fields[$value['key']]; } } }
            catch (DomainError $error) { if ($error->status !== 404) { throw $error; } }
            $rank = self::normalize($product->get_sku()) === $normalized && $normalized !== '' ? 0 : (self::normalize($product->get_name()) === $normalized ? 1 : 2);
            $matches[] = ['product' => $product, 'values' => $values, 'rank' => $rank];
        }
        foreach ($filters as $key => $condition) {
            if (!isset($knownFilters[$key]) || !is_string($condition) || strlen($condition) > 120) { throw new DomainError('unknown_facet', 'Unknown or unavailable filter. Clear this filter to continue.', 404); }
            if ($knownFilters[$key]['type'] === 'number') {
                if (!preg_match('/^(-?[0-9]{1,6}(?:\.[0-9]{1,6})?)\.\.(-?[0-9]{1,6}(?:\.[0-9]{1,6})?)$/D', $condition, $range) || Units::decimal($range[1]) > Units::decimal($range[2])) { throw new DomainError('invalid_range', 'Use a valid minimum..maximum range in canonical units.', 404); }
            }
        }
        $matches = array_values(array_filter($matches, static function (array $match) use ($filters, $knownFilters): bool {
            foreach ($filters as $key => $condition) {
                if (!isset($match['values'][$key])) { return false; }
                $value = $match['values'][$key]['value'];
                if ($knownFilters[$key]['type'] === 'number') { $range = explode('..', $condition); if (Units::decimal($value) < Units::decimal($range[0]) || Units::decimal($value) > Units::decimal($range[1])) { return false; } }
                elseif ((is_bool($value) ? ($value ? 'true' : 'false') : (string) $value) !== $condition) { return false; }
            }
            return true;
        }));
        usort($matches, static fn (array $a, array $b): int => $a['rank'] <=> $b['rank'] ?: $a['product']->get_id() <=> $b['product']->get_id());
        $facets = [];
        foreach ($matches as $match) { foreach ($match['values'] as $key => $value) { $display = is_bool($value['value']) ? ($value['value'] ? 'true' : 'false') : (string) $value['value']; $facets[$key]['label'] = $knownFilters[$key]['label']; $facets[$key]['unit'] = $value['unit']; $facets[$key]['values'][$display] = ($facets[$key]['values'][$display] ?? 0) + 1; } }
        $total = count($matches); $slice = array_slice($matches, ($page - 1) * $limit, $limit);
        if (($filters || $page > 1) && !$slice) { throw new DomainError('empty_facet_page', 'No products match this filter page. Clear a filter or return to page one.', 404); }
        return ['items' => array_map(fn (array $match): array => $this->products->publicCard($match['product']), $slice), 'facets' => $facets, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $limit), 'reduced_filters' => true, 'truncated' => $truncated];
    }

    public function suggestions(string $query): array
    {
        Input::text($query, 2, 100);
        $result = $this->find($query, [], 10);
        return ['items' => array_map(static fn (array $card): array => ['kind' => 'product', 'id' => $card['product_id'], 'label' => wp_html_excerpt(wp_strip_all_tags($card['name']), 200, ''), 'url' => $card['url']], $result['items']), 'next_cursor' => null];
    }
}
