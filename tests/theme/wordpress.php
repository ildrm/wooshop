<?php
/** Run through wp eval-file in a disposable WP + WC installation with Wooshop active. */
if (!defined('ABSPATH') || !function_exists('wc_get_products')) {
    throw new RuntimeException('This integration test requires an actual WordPress/WooCommerce runtime.');
}
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
$assert(wp_get_theme()->get_stylesheet() === 'wooshop', 'Wooshop must be the active theme.');
$assert(wp_is_block_theme(), 'WordPress must recognize a real block theme.');
$registry = WP_Block_Type_Registry::get_instance();
foreach (['wooshop/store-header', 'wooshop/store-footer', 'wooshop/category-shortcuts', 'wooshop/product-collection', 'wooshop/enhancement', 'woocommerce/legacy-template', 'woocommerce/mini-cart'] as $name) {
    $assert($registry->is_registered($name), 'Missing registered block: ' . $name);
}
$assert(wooshop_bounded_int('9999999', 10, 1, 20) === 20, 'Product collection counts must be bounded.');
$assert(wooshop_bounded_int(['bad'], 10, 1, 20) === 10, 'Malformed scalar must safely use default.');
$assert(wooshop_label('<script>alert(1)</script><b>safe</b>') === 'safe', 'Merchant text must not retain executable markup.');
$html = wooshop_render_header(['announcement' => '<b>Safe notice</b>', 'logoWidth' => 999999]) . wooshop_render_header(['announcement' => 'Second instance']);
preg_match_all('/\sid="([^"]+)"/', $html, $ids);
$assert(count($ids[1]) === count(array_unique($ids[1])), 'Repeated header instances produced duplicate IDs.');
$assert(!str_contains($html, '<b>Safe notice</b>'), 'Announcement markup must be escaped.');
$assert(str_contains($html, '--wooshop-logo-width:200px'), 'Logo width clamp must reach rendered output.');
$assert(str_contains($html, esc_url(wc_get_cart_url())), 'Full-cart recovery link must exist.');
$utilityTags = new WP_HTML_Tag_Processor($html);
foreach (['wooshop-account', 'wooshop-cart-link'] as $utilityClass) {
    $utilityTags = new WP_HTML_Tag_Processor($html);
    $assert($utilityTags->next_tag(['tag_name' => 'A', 'class_name' => $utilityClass]), 'Missing utility link: ' . $utilityClass);
    $assert(is_string($utilityTags->get_attribute('aria-label')) && trim($utilityTags->get_attribute('aria-label')) !== '', 'Utility link must retain an accessible name when its visible label is hidden.');
}

$navigation = do_blocks('<!-- wp:navigation {"overlayMenu":"never","submenuVisibility":"click"} /-->');
$header = wooshop_render_header([], $navigation);
$assert(str_contains($header, 'wp-block-navigation'), 'Native editable navigation must render inside header.');
$headerContent = '<!-- wp:wooshop/store-header --><!-- wp:navigation {"overlayMenu":"never"} /--><!-- /wp:wooshop/store-header -->';
$hookTypes = ['woocommerce/customer-account', 'woocommerce/mini-cart', 'example/merchant-extension'];
$assert(wooshop_header_hooked_blocks($hookTypes, 'after', 'core/navigation', (object) ['content' => $headerContent]) === ['example/merchant-extension'], 'Owned header must suppress only duplicate automatically hooked commerce controls.');
$assert(wooshop_header_hooked_blocks($hookTypes, 'after', 'core/navigation', (object) ['post_content' => $headerContent]) === ['example/merchant-extension'], 'Native post contexts must use the same header rule.');
$assert(wooshop_header_hooked_blocks($hookTypes, 'after', 'core/navigation', ['content' => '<!-- wp:navigation /-->']) === $hookTypes, 'Unrelated native Navigation must retain its automatic blocks.');
$assert(wooshop_header_hooked_blocks($hookTypes, 'before', 'core/navigation', ['content' => $headerContent]) === $hookTypes, 'Other hook positions must remain untouched.');
$assert(wooshop_header_hooked_blocks($hookTypes, 'before', null, ['content' => $headerContent]) === $hookTypes, 'Native freeform blocks have a null anchor and must pass through safely.');
$nativeHeaderContext = new WP_Block_Template();
$nativeHeaderContext->content = $headerContent;
$nativeHeaderContext->type = 'wp_template_part';
$nativeHeaderContext->area = 'header';
$nativeHeaderContext->theme = 'wooshop';
$nativeHeaderContext->slug = 'header';
remove_filter('hooked_block_types', 'wooshop_header_hooked_blocks', 20);
try { $nativeDefaultHeader = apply_block_hooks_to_content($headerContent, $nativeHeaderContext); }
finally { add_filter('hooked_block_types', 'wooshop_header_hooked_blocks', 20, 4); }
$nativeHookVersion = get_option('woocommerce_hooked_blocks_version');
$nativeDuplicateControls = has_block('woocommerce/customer-account', $nativeDefaultHeader) && has_block('woocommerce/mini-cart', $nativeDefaultHeader);
if (is_string($nativeHookVersion) && $nativeHookVersion !== 'no' && version_compare($nativeHookVersion, '8.4.0', '>=')) {
    $assert($nativeDuplicateControls, 'Regression precondition: enabled native WooCommerce Block Hooks must really auto-insert both controls.');
} else {
    $assert(!$nativeDuplicateControls, 'Native disabled/version-gated Block Hooks must remain disabled; test does not change this setting.');
}
$hookedHeader = apply_block_hooks_to_content($headerContent, $nativeHeaderContext);
$assert(!has_block('woocommerce/customer-account', $hookedHeader) && !has_block('woocommerce/mini-cart', $hookedHeader), 'Actual native Block Hooks must not insert duplicate header utilities.');
$explicitHeader = str_replace('<!-- /wp:wooshop/store-header -->', '<!-- wp:woocommerce/customer-account /--><!-- wp:woocommerce/mini-cart /--><!-- /wp:wooshop/store-header -->', $headerContent);
$nativeHeaderContext->content = $explicitHeader;
$explicitHooked = apply_block_hooks_to_content($explicitHeader, $nativeHeaderContext);
$assert(has_block('woocommerce/customer-account', $explicitHooked) && has_block('woocommerce/mini-cart', $explicitHooked), 'Explicit merchant commerce blocks must survive native hook processing.');
$contracts = wooshop_block_contracts();
foreach ($contracts as $name => $contract) {
    foreach (['userId', 'sellerId', 'price', 'stock', 'orderId', 'customerId', 'rawHtml', 'css', 'javascript'] as $forbidden) {
        $assert(!array_key_exists($forbidden, $contract['attributes']), $name . ' must not accept ' . $forbidden);
    }
}
$assert(!str_contains(wooshop_render_enhancement(['surface' => ['malformed']]), '[commerce_'), 'Unavailable modules must not leak shortcodes.');
$assert(str_contains(wooshop_render_enhancement(['surface' => 'unknown']), 'wooshop-state'), 'Unknown module must show recovery state.');
$visit = static function (array $blocks) use (&$visit, $registry, $assert): void {
    foreach ($blocks as $block) {
        if ($block['blockName']) {
            $assert($registry->is_registered($block['blockName']), 'Template uses an unregistered block: ' . $block['blockName']);
        }
        $visit($block['innerBlocks']);
    }
};
foreach (glob(get_template_directory() . '/templates/*.html') as $path) {
    $visit(parse_blocks(file_get_contents($path)));
}
$settings = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
$assert(($settings['layout']['contentSize'] ?? '') === '1280px', 'Native content width must survive platform resolver.');
$assert(($settings['layout']['wideSize'] ?? '') === '1440px', 'Native wide width must survive platform resolver.');
$products = wc_get_products(['status' => 'publish', 'limit' => 1, 'return' => 'ids']);
$assert((bool) $products, 'Synthetic catalog fixture required for collection integration.');
$collection = wooshop_render_collection(['source' => 'new', 'count' => 1]);
$assert(str_contains($collection, 'class="products '), 'Live WC collection markup missing.');
// WC's shortcode adds an ID tie-breaker to date ordering; wc_get_products' default
// ordering can choose another same-second product. Verify the actual returned native
// identity and URL, rather than assuming these two different query defaults agree.
preg_match_all('/<li[^>]+class="[^"]*\bpost-([0-9]+)\b/', $collection, $renderedIds);
$assert(count($renderedIds[1]) === 1, 'Bounded collection must render exactly one native product.');
foreach ($renderedIds[1] as $renderedId) {
    $renderedProduct = wc_get_product((int) $renderedId);
    $assert($renderedProduct && $renderedProduct->get_status() === 'publish' && $renderedProduct->is_visible(), 'Collection exposed a private or unavailable product.');
    $assert(str_contains($collection, esc_url(get_permalink((int) $renderedId))), 'Collection must link the exact native product it renders.');
}
$assert(wooshop_render_collection(['source' => ['malformed'], 'count' => 1]) !== '', 'Malformed source must safely fall back to native collection.');
$fixtures = get_option('wooshop_fixture_profile');
if (!empty($fixtures['native_product_ids']['unknown-price'])) {
    $unknown = wc_get_product($fixtures['native_product_ids']['unknown-price']);
    $free = wc_get_product($fixtures['native_product_ids']['zero-price']);
    $assert(str_contains($unknown->get_price_html(), 'wooshop-price-unavailable'), 'Unknown price needs an explicit unavailable label.');
    $assert(!str_contains($free->get_price_html(), 'wooshop-price-unavailable'), 'Explicit zero price must remain a valid native price.');
    $assert(!$unknown->is_purchasable(), 'Unknown price display must not enable purchase.');
    $beforeProduct = $GLOBALS['product'] ?? null;
    $GLOBALS['product'] = wc_get_product($fixtures['native_product_ids']['external']);
    ob_start();
    woocommerce_template_loop_add_to_cart();
    $external = ob_get_clean();
    $assert(str_contains($external, 'wooshop-external-disclosure'), 'External card requires a visible supplier disclosure.');
    $assert(str_contains($external, esc_url($GLOBALS['product']->get_product_url())), 'External card must preserve its native supplier URL.');
    $assert(!str_contains($external, '?add-to-cart='), 'External card must not create a local add-to-cart action.');
    $GLOBALS['product'] = $beforeProduct;
}
echo wp_json_encode(['status' => 'PASS', 'checks' => $checks, 'wordpress' => get_bloginfo('version'), 'woocommerce' => WC_VERSION, 'php' => PHP_VERSION, 'native_hook_version' => $nativeHookVersion, 'native_duplicate_controls_reproduced_without_theme_filter' => $nativeDuplicateControls, 'scope' => 'real theme registration, block contracts, bounded/escaped rendering, native WC collection; no checkout/browser conformance claim']) . "\n";
