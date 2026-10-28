<?php
/** Wooshop presentation bootstrap. Commerce data belongs to WooCommerce and plugins. */
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/inc/presentation.php';

/** The theme header already renders account/cart utilities above its Navigation. */
function wooshop_header_hooked_blocks(array $types, string $position, ?string $anchor, $context): array
{
    if ($position !== 'after' || $anchor !== 'core/navigation') {
        return $types;
    }
    $content = is_object($context) ? ($context->content ?? $context->post_content ?? '') : (is_array($context) ? ($context['content'] ?? '') : '');
    if (!is_string($content) || !has_block('wooshop/store-header', $content)) {
        return $types;
    }
    // Suppress automatic siblings only. Explicitly saved merchant blocks remain intact.
    return array_values(array_diff($types, ['woocommerce/customer-account', 'woocommerce/mini-cart']));
}
add_filter('hooked_block_types', 'wooshop_header_hooked_blocks', 20, 4);

add_action('after_setup_theme', function (): void {
    load_theme_textdomain('wooshop', get_template_directory() . '/languages');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('custom-logo', ['height' => 48, 'width' => 160, 'flex-width' => true, 'flex-height' => true]);
    add_theme_support('editor-styles');
    add_editor_style('assets/store.css');
    register_nav_menus(['primary' => __('Primary navigation', 'wooshop'), 'footer' => __('Footer navigation', 'wooshop')]);
});

add_action('wp_enqueue_scripts', function (): void {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('wooshop-store', get_template_directory_uri() . '/assets/store.css', [], $version);
    wp_enqueue_script('wooshop-store', get_template_directory_uri() . '/assets/store.js', [], $version, ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('wooshop-store', 'wooshopMessages', [
        'subcategories' => __('Subcategories of', 'wooshop'),
        'offline' => __('You appear to be offline. Reconnect and check the cart or order status before retrying.', 'wooshop'),
    ]);
});

add_action('init', function (): void {
    wp_register_script('wooshop-editor', get_template_directory_uri() . '/assets/editor.js', ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'], wp_get_theme()->get('Version'), true);
    wp_set_script_translations('wooshop-editor', 'wooshop', get_template_directory() . '/languages');
    foreach (wooshop_block_contracts() as $name => $contract) {
        register_block_type('wooshop/' . $name, [
            'api_version' => 3,
            'title' => $contract['title'],
            'category' => 'theme',
            'editor_script' => 'wooshop-editor',
            'attributes' => $contract['attributes'],
            'supports' => ['html' => false],
            'render_callback' => $contract['render'],
        ]);
    }
    register_block_pattern_category('wooshop', ['label' => __('Wooshop storefront', 'wooshop')]);
    register_block_style('core/group', ['name' => 'wooshop-panel', 'label' => __('Store panel', 'wooshop')]);
});

// Enhancements are rendered only by their registered owner; raw shortcodes never leak.
add_action('woocommerce_after_single_product_summary', 'wooshop_product_enhancements', 12);

add_filter('woocommerce_get_price_html', function (string $html, $product): string {
    if (trim($html) === '' && $product instanceof WC_Product && !$product->is_type('grouped')) {
        return '<span class="wooshop-price-unavailable">' . esc_html__('Price unavailable', 'wooshop') . '</span>';
    }
    return $html;
}, 20, 2);

add_filter('woocommerce_loop_add_to_cart_link', function (string $link, $product): string {
    return $product instanceof WC_Product && $product->is_type('external')
        ? $link . '<span class="wooshop-external-disclosure">' . esc_html__('External supplier', 'wooshop') . '</span>'
        : $link;
}, 20, 2);

add_action('woocommerce_before_add_to_cart_form', function (): void {
    global $product;
    if ($product instanceof WC_Product && $product->is_type('external')) {
        echo '<p class="wooshop-external-disclosure">' . esc_html__('Purchase takes place on the external supplier website.', 'wooshop') . '</p>';
    }
});
