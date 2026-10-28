<?php
/** Actual idempotency and edit-preservation checks for the explicit local fixture importer. */
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true) {
    throw new RuntimeException('Fixture tests require the isolated local runtime.');
}
$fixtureTestProfile = $args[0] ?? '';
if (!in_array($fixtureTestProfile, ['outdoor', 'smart-home', 'neutral'], true)) {
    throw new RuntimeException('An explicit seeded profile is required.');
}
$fixtureTestState = get_option('wooshop_fixture_profile');
$fixtureTestMarker = get_option('wooshop_profile_seeded');
if (!$fixtureTestMarker || ($fixtureTestState['profile_key'] ?? '') !== $fixtureTestProfile) {
    throw new RuntimeException('Seed and configure the selected local profile before running this test.');
}
$fixtureTestChecks = 0;
$fixtureTestAssert = static function (bool $condition, string $message) use (&$fixtureTestChecks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$fixtureTestChecks;
};
$fixtureTestIds = static function (): array {
    return get_posts(['post_type' => ['product', 'product_variation', 'page', 'post', 'attachment', 'wp_global_styles', 'wp_navigation', 'wp_template', 'wp_template_part'], 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => [['key' => '_wooshop_fixture_key', 'compare' => 'EXISTS']], 'orderby' => 'ID', 'order' => 'ASC']);
};
$fixtureTestBeforeIds = $fixtureTestIds();
$fixtureTestHome = get_post((int) $fixtureTestState['page_ids']['home']);
$fixtureTestOldHome = $fixtureTestHome->post_content;
$fixtureTestEditedHome = $fixtureTestOldHome . "\n<!-- local-fixture-user-edit-preservation-test -->";
$fixtureTestStyleRecords = get_posts(['post_type' => 'wp_global_styles', 'post_status' => 'any', 'posts_per_page' => 1, 'meta_query' => [['key' => '_wooshop_fixture_key', 'value' => 'fixture:' . $fixtureTestProfile . ':styles']]]);
$fixtureTestStyle = $fixtureTestStyleRecords[0] ?? null;
$fixtureTestAssert((bool) $fixtureTestStyle, 'Native configured style fixture missing.');
$fixtureTestOldStyle = $fixtureTestStyle->post_content;
$fixtureTestStyleData = json_decode($fixtureTestOldStyle, true, 32, JSON_THROW_ON_ERROR);
$fixtureTestStyleData['styles']['elements']['link']['color']['text'] = '#A12622';
$fixtureTestEditedStyle = wp_json_encode($fixtureTestStyleData);
wp_update_post(wp_slash(['ID' => $fixtureTestHome->ID, 'post_content' => $fixtureTestEditedHome]));
wp_update_post(wp_slash(['ID' => $fixtureTestStyle->ID, 'post_content' => $fixtureTestEditedStyle]));
try {
    $args = [$fixtureTestProfile, 'configure-profile'];
    ob_start();
    include dirname(__DIR__, 2) . '/tools/seed.php';
    $fixtureTestRun = json_decode(trim(ob_get_clean()), true, 32, JSON_THROW_ON_ERROR);
    $fixtureTestAssert($fixtureTestRun['created_count'] === 0, 'Reseeding created duplicate fixture records.');
    $fixtureTestAssert($fixtureTestRun['initial_configuration_applied'] === false, 'Reseeding reapplied initial defaults.');
    $fixtureTestAssert($fixtureTestBeforeIds === $fixtureTestIds(), 'Fixture IDs changed on repeat import.');
    $fixtureTestAssert(get_post($fixtureTestHome->ID)->post_content === $fixtureTestEditedHome, 'Reseeding overwrote edited native page content.');
    $fixtureTestAssert(get_post($fixtureTestStyle->ID)->post_content === $fixtureTestEditedStyle, 'Reseeding overwrote saved native user styles.');
    $fixtureTestAssert(get_option('wooshop_profile_seeded') === $fixtureTestMarker, 'Once-only configuration marker changed.');
    $fixtureTestStateAfter = get_option('wooshop_fixture_profile');
    $fixtureTestAssert($fixtureTestStateAfter['native_product_ids'] === $fixtureTestState['native_product_ids'], 'Native product identity changed.');
    foreach (['cart' => 'woocommerce/cart', 'checkout' => 'woocommerce/checkout'] as $fixtureTestPageKey => $fixtureTestBlock) {
        $fixtureTestContent = get_post((int) $fixtureTestState['page_ids'][$fixtureTestPageKey])->post_content;
        $fixtureTestAssert(has_block($fixtureTestBlock, $fixtureTestContent), 'Native commerce block missing from ' . $fixtureTestPageKey);
        $fixtureTestAssert(count(parse_blocks($fixtureTestContent)[0]['innerBlocks']) > 0, 'Native block tree is empty for ' . $fixtureTestPageKey);
    }
    $fixtureTestGrouped = wc_get_product($fixtureTestState['native_product_ids']['grouped']);
    $fixtureTestAssert(count($fixtureTestGrouped->get_children()) === 2, 'Grouped fixture must reference two actual independent children.');
    $fixtureTestVariable = wc_get_product($fixtureTestState['native_product_ids']['variable']);
    $fixtureTestAssert(count($fixtureTestVariable->get_children()) === 2, 'Variable fixture must have two actual variations.');
    $fixtureTestAssert(wc_get_product($fixtureTestState['native_product_ids']['unknown-price'])->get_price() === '', 'Unknown price was converted to zero.');
    $fixtureTestAssert(wc_get_product($fixtureTestState['native_product_ids']['zero-price'])->get_price() === '0', 'Explicit zero price was lost.');
    $fixtureTestAssert(!wc_get_product($fixtureTestState['native_product_ids']['sold-out'])->is_in_stock(), 'Sold-out fixture stock is incorrect.');
    $fixtureTestAssert(wc_get_product($fixtureTestState['native_product_ids']['external'])->get_type() === 'external', 'External fixture must remain native external.');
    $fixtureTestDownload = wc_get_product($fixtureTestState['native_product_ids']['download']);
    $fixtureTestAssert($fixtureTestDownload->is_downloadable(), 'Downloadable fixture flag missing.');
    $fixtureTestAssert(count($fixtureTestDownload->get_downloads()) === 1, 'Downloadable fixture must have an actual protected native file reference.');
    echo wp_json_encode(['status' => 'PASS', 'checks' => $fixtureTestChecks, 'profile' => $fixtureTestProfile, 'scope' => 'real additive import identity, native block compositions and native content/style preservation']) . "\n";
} finally {
    // Restore only the test edits; a concurrent editor's newer content is never overwritten.
    if (get_post($fixtureTestHome->ID)->post_content === $fixtureTestEditedHome) {
        wp_update_post(wp_slash(['ID' => $fixtureTestHome->ID, 'post_content' => $fixtureTestOldHome]));
    }
    if (get_post($fixtureTestStyle->ID)->post_content === $fixtureTestEditedStyle) {
        wp_update_post(wp_slash(['ID' => $fixtureTestStyle->ID, 'post_content' => $fixtureTestOldStyle]));
    }
    WP_Theme_JSON_Resolver::clean_cached_data();
}
