<?php
/** Run only in the synthetic isolated installations through tools/dev.py wp <site> eval-file. */
if (!defined('WP_CLI') || !WP_CLI || !str_contains(home_url('/'), 'localhost:1888')) { throw new RuntimeException('This test requires the authorized isolated localhost store.'); }
if (!defined('WP_PLUGIN_DIR') || !in_array('wooshop-commerce/wooshop-commerce.php', (array) get_option('active_plugins', []), true) || !is_readable(WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php') || !class_exists(\WooShop\Commerce\Kernel\Plugin::class, false) || !\WooShop\Commerce\Kernel\Plugin::instance()) { throw new RuntimeException('The installed WooShop Commerce plugin must already be active and bootstrapped.'); }
require_once WP_PLUGIN_DIR . '/wooshop-commerce/autoload.php';
if (!apply_filters('wooshop_commerce_catalog_services', [])) { throw new RuntimeException('The active installed plugin did not register its catalog services.'); }
require_once '/workspace/tests/catalog/fixture-cleanup.php';

use WooShop\Commerce\Catalog\Input;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Infrastructure\Schema;
use WooShop\Commerce\Support\DomainError;

final class CatalogRollback extends RuntimeException {}
$catalogDb = new Database($GLOBALS['wpdb']); $catalogStore = new EntityStore($catalogDb);
Schema::install($catalogDb);
$services = apply_filters('wooshop_commerce_catalog_services', []);
$catalogChecks = 0;
$same = static function ($actual, $expected, string $case) use (&$catalogChecks): void { $catalogChecks++; if ($actual !== $expected) { throw new RuntimeException($case . ': ' . wp_json_encode(['actual' => $actual, 'expected' => $expected])); } };
$denied = static function (callable $call, string $expected) use ($same): void { try { $call(); } catch (DomainError $error) { $same($error->errorCode, $expected, 'domain denial'); return; } throw new RuntimeException('Expected domain denial ' . $expected); };
$rest = static function (string $method, string $path, ?array $payload = null, array $headers = []): WP_REST_Response {
    $request = new WP_REST_Request($method, '/commerce/v1' . $path);
    if ($payload !== null) { $request->set_header('Content-Type', 'application/json'); $request->set_body(wp_json_encode($payload)); }
    foreach ($headers as $key => $value) { $request->set_header($key, $value); }
    $response = rest_get_server()->dispatch($request);
    if ($response->get_status() >= 500) {
        // Replay only safe GET steps outside the REST exception mapper to expose a useful local test stack.
        if ($method === 'GET' && $path === '/wishlists/me') {
            $services = apply_filters('wooshop_commerce_catalog_services', []);
            $services['customer']->wishlist();
            $session = new \WooShop\Commerce\Customer\Session();
            $session->authorize($request->get_header('X-WP-Nonce'), false);
            (new \WooShop\Commerce\Catalog\RateLimit(new EntityStore(new Database($GLOBALS['wpdb']))))->check('catalog.read', 60);
            Input::keys($request->get_query_params(), []);
            $registered = rest_get_server()->get_routes()['/commerce/v1/wishlists/me'][0];
            $permission = $registered['permission_callback']($request);
            if (is_wp_error($permission)) { throw new RuntimeException('Catalog permission mapper: ' . wp_json_encode($permission)); }
            $direct = $registered['callback']($request);
            if (is_wp_error($direct)) { throw new RuntimeException('Catalog response mapper: ' . wp_json_encode($direct)); }
        }
        throw new RuntimeException('Internal REST failed: ' . wp_json_encode($response->get_data()));
    }
    return $response;
};
try {
    $catalogDb->transaction(function () use ($services, $catalogStore, $same, $denied, $rest): void {
        $tag = substr(bin2hex(random_bytes(6)), 0, 12);
        $administrator = get_users(['role' => 'administrator', 'number' => 1])[0]; wp_set_current_user($administrator->ID);
        $productA = new WC_Product_Simple(); $productA->set_name('کیف catalog fixture A ' . $tag); $productA->set_status('publish'); $productA->set_regular_price('10.00'); $productA->set_sku('CAT-A-' . $tag); $a = catalog_save_product_fixture($productA, 'catalog-suite:' . $tag);
        $productB = new WC_Product_Simple(); $productB->set_name('کیف catalog fixture B ' . $tag); $productB->set_status('publish'); $productB->set_regular_price('12.00'); $b = catalog_save_product_fixture($productB, 'catalog-suite:' . $tag);
        $hidden = new WC_Product_Simple(); $hidden->set_name('private fixture ' . $tag); $hidden->set_status('draft'); $privateId = catalog_save_product_fixture($hidden, 'catalog-suite:' . $tag);
        $schema = $services['specifications']->publishSchema(['key' => 'fixture_' . $tag, 'version' => 1, 'fields' => [['key' => 'mass', 'type' => 'number', 'label' => 'Mass', 'required' => true, 'unit' => 'g', 'allowed_units' => ['g', 'kg'], 'min' => '0.000001', 'facet' => true, 'compare' => true], ['key' => 'water_rating', 'type' => 'number', 'label' => 'Water rating', 'required' => false, 'unit' => 'mm', 'evidence_required' => true, 'safety' => true, 'compare' => true]]]);
        $schemaInput = ['schema_id' => $schema['id'], 'schema_version' => 1, 'product_id' => $a, 'version' => 1, 'values' => [['key' => 'mass', 'value' => '1', 'unit' => 'kg']]];
        $published = $services['specifications']->publish($a, $schemaInput, 1);
        $same($published['values'][0]['value'], '1000', 'TEST-U041 normalized 1kg');
        $same($published['values'][0]['unit'], 'g', 'canonical mass unit');
        $bad = $schemaInput; $bad['values'][0]['unit'] = 'mm'; $denied(fn () => $services['specifications']->publish($a, $bad, 1), 'incompatible_unit');
        $bad = $schemaInput; $bad['values'][] = ['key' => 'water_rating', 'value' => '1000', 'unit' => 'mm']; $denied(fn () => $services['specifications']->publish($a, $bad, 1), 'evidence_required');
        $schemaKg = $services['specifications']->publishSchema(['key' => 'fixture_kg_' . $tag, 'version' => 1, 'fields' => [['key' => 'mass', 'type' => 'number', 'label' => 'Mass', 'required' => true, 'unit' => 'kg', 'allowed_units' => ['g', 'kg'], 'compare' => true]]]);
        $services['specifications']->publish($b, ['schema_id' => $schemaKg['id'], 'schema_version' => 1, 'product_id' => $b, 'version' => 1, 'values' => [['key' => 'mass', 'value' => '1000', 'unit' => 'g']]], 1);
        $comparison = $services['customer']->comparison([$a, $b]);
        $same($comparison['products'][0]['values'][0]['display_value'], $comparison['products'][1]['values'][0]['display_value'], 'TEST-U044 same mass across canonical units');
        $same($comparison['products'][0]['values'][1]['missing'], true, 'TEST-U044 water rating remains unknown');
        $same($comparison['products'][0]['values'][1]['display_value'], __('Not supplied', 'wooshop-commerce'), 'missing never zero');
        $same($services['compatibility']->evaluate(['product_id' => $a, 'target_product_id' => $b, 'region' => 'R1'])['result'], 'unknown', 'TEST-U052 no evidence means unknown');
        $evidence = $services['specifications']->evidence(['product_ids' => [$a, $b], 'source_uri' => 'https://manufacturer.example.invalid/fixture.pdf', 'document_sha256' => str_repeat('a', 64), 'asserted_at' => gmdate('Y-m-d\TH:i:s\Z', time() - 3600), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 604800), 'scope' => 'Synthetic exact model and region R1 evidence.', 'safety' => true]);
        $reviewers = [];
        foreach (['one', 'two'] as $reviewerName) { $reviewerId = wp_insert_user(['user_login' => 'cat_reviewer_' . $reviewerName . '_' . $tag, 'user_email' => 'cat-reviewer-' . $reviewerName . '-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'editor']); if (is_wp_error($reviewerId)) { throw new RuntimeException('Reviewer fixture failed.'); } $reviewer = new WP_User($reviewerId); $reviewer->add_cap('commerce.evidence.approve'); $reviewers[] = $reviewerId; }
        wp_set_current_user($reviewers[0]); $reviewed = $services['specifications']->reviewEvidence($evidence['id'], 1, 'approve'); $same($reviewed['data']['state'], 'pending', 'safety requires second reviewer');
        wp_set_current_user($reviewers[1]); $reviewed = $services['specifications']->reviewEvidence($evidence['id'], 2, 'approve'); $same($reviewed['data']['state'], 'approved', 'second independent specialist approves safety');
        $services['compatibility']->publishRule(['product_id' => $a, 'target_product_id' => $b, 'evidence_id' => $evidence['id'], 'region' => 'R1', 'model_revision' => '2', 'firmware_min' => '2.4', 'result' => 'compatible', 'conditions' => 'Reviewed exact synthetic model 2 in region R1.']);
        $same($services['compatibility']->evaluate(['product_id' => $a, 'target_product_id' => $b, 'region' => 'R1', 'model_revision' => '2'])['result'], 'unknown', 'firmware missing preserves unknown');
        $same($services['compatibility']->evaluate(['product_id' => $a, 'target_product_id' => $b, 'region' => 'R1', 'model_revision' => '2', 'firmware' => '2.4'])['result'], 'compatible', 'exact reviewed context');
        wp_set_current_user($administrator->ID); $safetyValues = $schemaInput; $safetyValues['values'][] = ['key' => 'water_rating', 'value' => '1000', 'unit' => 'mm', 'evidence_ref' => $evidence['id']]; $services['specifications']->publish($a, $safetyValues, 1);
        $same(count($services['specifications']->read($a)['values']), 2, 'approved safety claim visible');
        wp_set_current_user($reviewers[0]); $services['specifications']->reviewEvidence($evidence['id'], 3, 'revoke');
        $same(count($services['specifications']->read($a)['values']), 1, 'revocation immediately withholds claim');
        $same($services['compatibility']->evaluate(['product_id' => $a, 'target_product_id' => $b, 'region' => 'R1', 'model_revision' => '2', 'firmware' => '2.4'])['result'], 'unknown', 'revocation immediately removes compatibility');
        wp_set_current_user($administrator->ID);
        $search = $services['search']->find('كيف'); $same(in_array($a, array_column($search['items'], 'product_id'), true), true, 'TEST-U042 Arabic/Persian matching');
        $denied(fn () => $services['products']->get($privateId), 'not_found');

        $first = wp_insert_user(['user_login' => 'cat_a_' . $tag, 'user_email' => 'cat-a-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        $second = wp_insert_user(['user_login' => 'cat_b_' . $tag, 'user_email' => 'cat-b-' . $tag . '@example.invalid', 'user_pass' => wp_generate_password(32), 'role' => 'customer']);
        if (is_wp_error($first) || is_wp_error($second)) { throw new RuntimeException('Synthetic customer creation failed.'); }
        wp_set_current_user($first);
        $list = $services['customer']->wishlist(); $key = wp_generate_uuid4();
        $saved = $services['customer']->saveItem(['product_id' => $a], $list['version'], $key);
        $same(count($saved['items']), 1, 'TEST-U043 saved item persisted');
        $same($services['customer']->saveItem(['product_id' => $a], $list['version'], $key), $saved, 'wishlist replay is identical');
        $denied(fn () => $services['customer']->saveItem(['product_id' => $b], $saved['version'], $key), 'idempotency_conflict');
        $denied(fn () => $services['customer']->saveItem(['product_id' => $b], 1, wp_generate_uuid4()), 'stale_revision');
        $denied(fn () => $services['specifications']->publish($a, $schemaInput, 1), 'forbidden');
        $preferences = $services['customer']->preferences();
        $same($preferences['marketing_email'], false, 'marketing defaults off');
        $same($preferences['history_enabled'], false, 'history defaults off');
        $denied(fn () => $services['customer']->history('record', $a, 1, wp_generate_uuid4()), 'consent_required');
        $version = $services['customer']->updatePreferences(['consent_notice_version' => (int) get_option('wooshop_commerce_consent_notice_version', 1), 'marketing_email' => true], $preferences['version'], wp_generate_uuid4());
        $denied(fn () => $services['notifications']->subscribe(['product_id' => $a, 'kind' => 'back_in_stock', 'channel' => 'email'], wp_generate_uuid4()), 'contact_unverified');
        // This synthetic trusted verification marker is a fixture, never an account self-verification API.
        update_user_meta($first, 'wooshop_verified_email_at', gmdate('Y-m-d\TH:i:s\Z'));
        $operation = $services['notifications']->subscribe(['product_id' => $a, 'kind' => 'back_in_stock', 'channel' => 'email'], wp_generate_uuid4());
        $same($operation['state'], 'pending', 'no provider cannot claim sent');
        $same($services['notifications']->eligible($operation['resource_id']), true, 'eligible before withdrawal');
        $token = $services['notifications']->unsubscribeToken('user:' . $first, 'alert', 'email');
        $services['notifications']->unsubscribe(['token' => $token]); $services['notifications']->unsubscribe(['token' => $token]);
        $same($services['notifications']->eligible($operation['resource_id']), false, 'TEST-G021 suppression blocks queued send');
        $same($services['customer']->preferences()['marketing_email'], true, 'alert token cannot alter unrelated marketing preference');
        $question = $services['community']->question(['product_id' => $a, 'text' => 'Synthetic question about model dimensions?'], wp_generate_uuid4());
        $same($question['state'], 'pending', 'question starts pending');
        $same(in_array($question['id'], array_column($services['community']->questions()['items'], 'id'), true), false, 'pending excluded public');
        wp_set_current_user($second); $same($services['customer']->wishlist()['items'], [], 'TEST-U043 foreign account isolation');
        $same($services['customer']->preferences()['marketing_email'], false, 'foreign preferences isolated');
        wp_set_current_user(0); $guestList = $services['customer']->wishlist(); $oldGuest = $services['session']->guestOwner(); $oldCsrf = $services['session']->csrf();
        $services['customer']->saveItem(['product_id' => $b], $guestList['version'], wp_generate_uuid4());
        wp_set_current_user($first); $accountList = $services['customer']->wishlist(); $mergeKey = wp_generate_uuid4();
        $merged = $services['customer']->mergeWishlist($accountList['version'], $mergeKey); $same(count($merged['items']), 2, 'guest/account union retains unique products');
        $same($services['customer']->mergeWishlist($accountList['version'], $mergeKey), $merged, 'guest merge replay gives one union');
        $same($catalogStore->get('wishlist', Input::stableId('wishlist:' . $oldGuest))['data']['merged'], true, 'guest source consumed');
        wp_set_current_user(0); $same($services['session']->guestOwner() !== $oldGuest, true, 'guest credential rotated');
        $denied(fn () => $services['session']->authorize($oldCsrf, true), 'csrf_invalid');
        wp_set_current_user($second);
        $same($rest('GET', '/wishlists/me')->get_status(), 200, 'own wishlist REST works');
        $same($rest('POST', '/wishlists/me/items', ['product_id' => $a], ['Idempotency-Key' => wp_generate_uuid4()])->get_status(), 428, 'missing If-Match fails');
        $same($rest('POST', '/wishlists/me/items', ['product_id' => $a, 'owner' => 'user:' . $first], ['Idempotency-Key' => wp_generate_uuid4(), 'If-Match' => '"1"'])->get_status(), 400, 'REST unknown owner rejected');
        $same($rest('POST', '/comparisons', ['product_ids' => [$a, $b, $a]], ['Idempotency-Key' => wp_generate_uuid4()])->get_status(), 422, 'duplicate compare products rejected');
        wp_set_current_user($administrator->ID);
        $services['community']->moderate($question['id'], $question['version'], 'publish');
        $same(in_array($question['id'], array_column($services['community']->questions()['items'], 'id'), true), true, 'moderated question published');
        $placement = $services['merchandising']->placement(['placement_key' => 'fixture_' . $tag, 'curated_product_ids' => [$a, $b], 'sponsored' => true], 1);
        $cards = $services['merchandising']->recommendations('fixture_' . $tag); $same(count($cards), 2, 'curated placement'); $same($cards[0]['sponsored'], true, 'sponsored disclosure retained');
        $point = $services['price_history']->observe($productA); $again = $services['price_history']->observe($productA); $same($point['id'], $again['id'], 'TEST-U057 duplicate price event creates one point');
        $same(str_contains(do_shortcode('[commerce_specs product_id="' . $a . '"]'), 'scope="row"'), true, 'accessible spec table rendered');
        $same(str_contains(do_shortcode('[commerce_questions product_id="' . $a . '"]'), 'Synthetic question'), true, 'approved question UI rendered');
        throw new CatalogRollback('Synthetic transaction rolled back.');
    });
} catch (CatalogRollback $rollback) {
    echo 'Catalog WordPress checks passed: ' . $catalogChecks . "; synthetic transaction rolled back.\n";
}
