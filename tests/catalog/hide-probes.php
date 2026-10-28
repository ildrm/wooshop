<?php
/** Positive provenance remediation for this invocation's pre-origin-metadata catalog test probes. */
if (!defined('WP_CLI') || !WP_CLI || !defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Synthetic isolated stores only.'); }
use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
$store = new EntityStore(new Database($GLOBALS['wpdb'])); $remediated = []; $unconfirmed = [];
$candidates = new WP_Query(['post_type' => 'product', 'post_status' => ['publish', 'draft', 'pending', 'private'], 's' => 'catalog fixture', 'posts_per_page' => 500, 'fields' => 'ids', 'no_found_rows' => true]);
foreach ($candidates->posts as $id) {
    $product = wc_get_product($id); if (!$product || $product->get_meta('_wooshop_fixture_key', true) !== '') { continue; }
    if (!preg_match('/^کیف catalog fixture ([AB]) ([a-f0-9]{12})$/uD', $product->get_name(), $match)) { continue; }
    $tag = $match[2]; $which = $match[1]; $spec = $store->get('spec_values', Input::stableId('spec_values:' . $id));
    $schema = $spec ? $store->get('spec_schema', $spec['data']['schema_id']) : null;
    $expected = ($which === 'A' ? 'fixture_' : 'fixture_kg_') . $tag;
    if (!$schema || $schema['data']['key'] !== $expected || ($which === 'A' && $product->get_sku() !== 'CAT-A-' . $tag)) { $unconfirmed[] = (string) $id; continue; }
    $product->update_meta_data('_wooshop_catalog_test_origin', 'legacy-catalog-suite:' . $tag); $product->set_status('draft'); $product->set_catalog_visibility('hidden'); $product->save();
    $remediated[] = ['product_id' => (string) $id, 'run' => $tag, 'evidence' => ['exact_test_title', 'matching_persisted_fixture_schema', $which === 'A' ? 'matching_exact_sku' : 'paired_fixture_schema'], 'state' => 'draft_hidden'];
}
echo wp_json_encode(['remediated' => $remediated, 'unconfirmed_untouched' => $unconfirmed], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
