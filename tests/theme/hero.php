<?php
/** Real local fixture CTA migration and explicit merchant-link preservation. */
if (!defined('ABSPATH') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true) {
    throw new RuntimeException('Hero fixture tests require the isolated local runtime.');
}
$heroTestProfile = $args[0] ?? '';
$heroTestState = get_option('wooshop_fixture_profile');
if (($heroTestState['profile_key'] ?? '') !== $heroTestProfile || !get_option('wooshop_profile_seeded')) {
    throw new RuntimeException('An explicitly seeded and configured local profile is required.');
}
$heroTestHomeId = (int) $heroTestState['page_ids']['home'];
$heroTestOriginal = get_post($heroTestHomeId)->post_content;
$heroTestOriginalTarget = get_post_meta($heroTestHomeId, '_wooshop_fixture_hero_target', true);
$heroTestChecks = 0;
$heroTestAssert = static function (bool $condition, string $message) use (&$heroTestChecks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$heroTestChecks;
};
$heroTestTags = new WP_HTML_Tag_Processor($heroTestOriginal);
$heroTestAssert($heroTestTags->next_tag(['tag_name' => 'A', 'class_name' => 'wp-block-button__link']), 'Fixture hero CTA missing.');
$heroTestAssert($heroTestTags->get_attribute('href') === wc_get_page_permalink('shop'), 'Fixture hero must use the actual assigned native WooCommerce catalog.');
$heroTestTags->set_attribute('href', home_url('/shop/'));
$heroTestLegacy = $heroTestTags->get_updated_html();
$heroTestMerchantTags = new WP_HTML_Tag_Processor($heroTestOriginal);
$heroTestMerchantTags->next_tag(['tag_name' => 'A', 'class_name' => 'wp-block-button__link']);
$heroTestMerchantTags->set_attribute('href', get_permalink($heroTestState['page_ids']['help']));
$heroTestMerchant = $heroTestMerchantTags->get_updated_html() . "\n<!-- explicit-merchant-hero-edit-test -->";
try {
    wp_update_post(wp_slash(['ID' => $heroTestHomeId, 'post_content' => $heroTestLegacy]));
    delete_post_meta($heroTestHomeId, '_wooshop_fixture_hero_target');
    $args = [$heroTestProfile];
    ob_start();
    include dirname(__DIR__, 2) . '/tools/seed.php';
    $heroTestMigration = json_decode(trim(ob_get_clean()), true, 32, JSON_THROW_ON_ERROR);
    $heroTestAssert($heroTestMigration['created_count'] === 0, 'Hero repair must not duplicate fixture records.');
    $heroTestAssert($heroTestMigration['initial_configuration_applied'] === false, 'Hero repair must not reapply store defaults.');
    $heroTestAssert(get_post($heroTestHomeId)->post_content === $heroTestOriginal, 'Legacy hero repair must change only the default CTA destination.');
    $heroTestAssert(get_post_meta($heroTestHomeId, '_wooshop_fixture_hero_target', true) === wc_get_page_permalink('shop'), 'Updated fixture hero target marker missing.');
    wp_update_post(wp_slash(['ID' => $heroTestHomeId, 'post_content' => $heroTestMerchant]));
    $args = [$heroTestProfile];
    ob_start();
    include dirname(__DIR__, 2) . '/tools/seed.php';
    $heroTestPreserve = json_decode(trim(ob_get_clean()), true, 32, JSON_THROW_ON_ERROR);
    $heroTestAssert($heroTestPreserve['created_count'] === 0, 'Merchant link preservation must not create new fixture records.');
    $heroTestAssert(get_post($heroTestHomeId)->post_content === $heroTestMerchant, 'Reseeding must preserve an explicitly edited merchant hero URL and all surrounding content.');
    echo wp_json_encode(['status' => 'PASS', 'checks' => $heroTestChecks, 'profile' => $heroTestProfile, 'scope' => 'actual native catalog destination;legacy fixture CTA-only migration;explicit merchant URL preservation']) . "\n";
} finally {
    $heroTestCurrent = get_post($heroTestHomeId)->post_content;
    if (in_array($heroTestCurrent, [$heroTestLegacy, $heroTestMerchant, $heroTestOriginal], true)) {
        wp_update_post(wp_slash(['ID' => $heroTestHomeId, 'post_content' => $heroTestOriginal]));
        if ($heroTestOriginalTarget !== '') {
            update_post_meta($heroTestHomeId, '_wooshop_fixture_hero_target', $heroTestOriginalTarget);
        } else {
            delete_post_meta($heroTestHomeId, '_wooshop_fixture_hero_target');
        }
    }
}
