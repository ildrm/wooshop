<?php
declare(strict_types=1);
/** One-time, explicit ID cleanup of this worker's pre-provenance local probes. */
if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local' || (int) wp_parse_url(home_url('/'), PHP_URL_PORT) !== 18880) { throw new RuntimeException('Historical probe inventory is scoped only to the isolated Outdoor fixture.'); }
global $wpdb;
$decisions = [];
$expectedIds = [10, 49, 53, 56, 70, 79, 82, 91];
foreach ($expectedIds as $id) {
    $product = wc_get_product($id);
    if (!$product || $product->get_meta('_wooshop_fixture_key', true) || $product->get_name() !== 'Changed catalog name') { $decisions[] = ['id' => $id, 'action' => 'preserved', 'reason' => 'Missing product, protected profile fixture, or exact integration probe title no longer matches.']; continue; }
    $rows = $wpdb->get_results($wpdb->prepare('SELECT o.id AS offer_id,o.seller_sku,s.id AS seller_id,s.applicant_id,s.public_slug,s.display_name,s.data FROM '.$wpdb->prefix.'wooshop_offers o JOIN '.$wpdb->prefix.'wooshop_sellers s ON s.id=o.seller_id WHERE o.product_id=%d', $id), ARRAY_A);
    $proof = null;
    foreach ($rows as $row) {
        $data = json_decode($row['data'], true);
        $user = get_userdata((int) $row['applicant_id']);
        if (!preg_match('/^fixture-([a-f0-9]{10})$/D', $row['public_slug'], $match)) { continue; }
        $tag = $match[1];
        if ($user && $user->user_login === 'marketplace-fixture-'.$tag && $row['display_name'] === 'Synthetic seller '.$tag && ($data['business_profile_ref'] ?? '') === 'synthetic-not-a-legal-document' && ($data['accepted_terms_version'] ?? null) === 999 && in_array($row['seller_sku'], ['fixture-sku-'.$tag, 'fixture-new-'.$tag, 'rest-own-'.$tag], true)) {
            $proof = ['run' => $tag, 'offer_id' => $row['offer_id'], 'seller_id' => $row['seller_id'], 'actor_id' => (string) $row['applicant_id']]; break;
        }
    }
    if (!$proof) { $decisions[] = ['id' => $id, 'action' => 'preserved', 'reason' => 'Explicit integration provenance could not be proven.']; continue; }
    $before = $product->get_status();
    $product->update_meta_data('_wooshop_test_origin', 'marketplace.integration');
    $product->update_meta_data('_wooshop_test_run', $proof['run']);
    $product->update_meta_data('_wooshop_test_legacy_provenance', ['source' => 'tests/marketplace/integration.php', 'offer_id' => $proof['offer_id'], 'seller_id' => $proof['seller_id'], 'actor_id' => $proof['actor_id']]);
    $product->update_meta_data('_wooshop_test_cleanup', gmdate('Y-m-d\TH:i:s\Z'));
    $product->set_status('draft'); $product->set_catalog_visibility('hidden'); $product->save();
    $decisions[] = ['id' => $id, 'action' => 'drafted', 'before' => $before, 'after' => 'draft', 'provenance' => $proof, 'historical_orders' => 'unchanged'];
}
// Root explicitly authorized these exact IDs from the empty isolated invocation, matched
// to this worker's known Store API execution timestamps. This is not a title-based scan.
foreach ([84, 88, 89] as $id) {
    $product = wc_get_product($id);
    if (!$product || $product->get_meta('_wooshop_fixture_key', true) || $product->get_name() !== 'Synthetic Store API stock authority fixture') { $decisions[] = ['id' => $id, 'action' => 'preserved', 'reason' => 'Absent, profile-protected, or exact expected Store API probe title no longer matches.']; continue; }
    $before = $product->get_status();
    $provenance = ['source' => 'tests/marketplace/store-api.php', 'identification' => 'Root-authorized exact local IDs from this empty isolated invocation and known test timestamps; original run ID was not persisted.', 'product_id' => $id];
    $product->update_meta_data('_wooshop_test_origin', 'marketplace.store-api');
    $product->update_meta_data('_wooshop_test_run', 'legacy-product-'.$id);
    $product->update_meta_data('_wooshop_test_legacy_provenance', $provenance);
    $product->update_meta_data('_wooshop_test_cleanup', gmdate('Y-m-d\TH:i:s\Z'));
    $product->set_status('draft'); $product->set_catalog_visibility('hidden'); $product->save();
    $decisions[] = ['id' => $id, 'action' => 'drafted', 'before' => $before, 'after' => 'draft', 'provenance' => $provenance, 'historical_orders' => 'unchanged'];
}
echo wp_json_encode(['site' => 'outdoor-local-18880', 'decisions' => $decisions, 'scope' => 'Only explicit positively linked integration IDs and root-authorized exact Store API IDs changed through WooCommerce CRUD. Profile fixtures, native historical orders and SQL race records preserved.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
