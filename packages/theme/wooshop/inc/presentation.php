<?php
/** Stateless theme renderers. No order, price, stock or customer persistence. */
if (!defined('ABSPATH')) {
    exit;
}

function wooshop_block_contracts(): array
{
    return [
        'store-header' => [
            'title' => __('Store header', 'wooshop'), 'render' => 'wooshop_render_header',
            'attributes' => [
                'announcement' => ['type' => 'string', 'default' => ''],
                'dismissible' => ['type' => 'boolean', 'default' => false],
                'sticky' => ['type' => 'boolean', 'default' => true],
                'compact' => ['type' => 'boolean', 'default' => false],
                'logoWidth' => ['type' => 'number', 'default' => 160],
                'logoId' => ['type' => 'number', 'default' => 0],
            ],
        ],
        'category-shortcuts' => [
            'title' => __('Category shortcuts', 'wooshop'), 'render' => 'wooshop_render_categories',
            'attributes' => ['count' => ['type' => 'number', 'default' => 8], 'title' => ['type' => 'string', 'default' => ''], 'shape' => ['type' => 'string', 'enum' => ['square', 'circle', 'none'], 'default' => 'square']],
        ],
        'product-collection' => [
            'title' => __('Native product collection', 'wooshop'), 'render' => 'wooshop_render_collection',
            'attributes' => [
                'source' => ['type' => 'string', 'enum' => ['new', 'featured', 'bestselling', 'sale'], 'default' => 'new'],
                'count' => ['type' => 'number', 'default' => 10],
                'title' => ['type' => 'string', 'default' => ''],
                'layout' => ['type' => 'string', 'enum' => ['grid', 'rail'], 'default' => 'grid'],
                'density' => ['type' => 'string', 'enum' => ['comfortable', 'dense'], 'default' => 'comfortable'],
            ],
        ],
        'store-footer' => ['title' => __('Store footer', 'wooshop'), 'render' => 'wooshop_render_footer', 'attributes' => ['minimal' => ['type' => 'boolean', 'default' => false]]],
        'enhancement' => [
            'title' => __('Commerce module surface', 'wooshop'), 'render' => 'wooshop_render_enhancement',
            'attributes' => ['surface' => ['type' => 'string', 'enum' => ['wishlist', 'compare', 'seller_dashboard', 'unsubscribe'], 'default' => 'wishlist']],
        ],
    ];
}

function wooshop_bounded_int($value, int $default, int $minimum, int $maximum): int
{
    return is_numeric($value) ? max($minimum, min($maximum, (int) $value)) : $default;
}

function wooshop_label($value, string $fallback = ''): string
{
    return is_string($value) ? wp_html_excerpt(wp_strip_all_tags($value), 240, '') : $fallback;
}

function wooshop_shop_url(): string
{
    return function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
}

function wooshop_icon(string $name): string
{
    $paths = [
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
        'account' => '<circle cx="12" cy="8" r="3.5"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'bag' => '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    ];
    return '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? $paths['grid']) . '</svg>';
}

function wooshop_search_form(): string
{
    $id = wp_unique_id('wooshop-search-');
    return '<form role="search" method="get" class="wooshop-search" action="' . esc_url(home_url('/')) . '"><label for="' . esc_attr($id) . '">' . esc_html__('Search the catalog', 'wooshop') . '</label><div class="wooshop-search-field"><input id="' . esc_attr($id) . '" type="search" name="s" value="' . esc_attr(get_search_query()) . '" placeholder="' . esc_attr__('Product, category or model', 'wooshop') . '" maxlength="120"/>' . (class_exists('WooCommerce') ? '<input type="hidden" name="post_type" value="product"/>' : '') . '<button type="submit"><span class="screen-reader-text">' . esc_html__('Search', 'wooshop') . '</span>' . wooshop_icon('search') . '</button></div></form>';
}

function wooshop_category_tree(int $parent = 0, int $depth = 0, ?array $groups = null): string
{
    if (!taxonomy_exists('product_cat') || $depth > 2) {
        return '';
    }
    if ($groups === null) {
        // One bounded public taxonomy query, rather than a query for every menu branch.
        $records = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 256, 'orderby' => 'parent', 'order' => 'ASC']);
        if (is_wp_error($records)) {
            return '';
        }
        $groups = [];
        foreach ($records as $record) {
            $groups[(int) $record->parent][] = $record;
        }
        foreach ($groups as &$group) {
            usort($group, static fn($a, $b): int => strnatcasecmp($a->name, $b->name));
        }
        unset($group);
    }
    $terms = array_slice($groups[$parent] ?? [], 0, $depth ? 8 : 16);
    if (!$terms) {
        return '';
    }
    $html = '<ul class="wooshop-category-level wooshop-level-' . $depth . '">';
    foreach ($terms as $term) {
        $url = get_term_link($term);
        if (is_wp_error($url)) {
            continue;
        }
        $children = wooshop_category_tree((int) $term->term_id, $depth + 1, $groups);
        $link = '<a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a>';
        $html .= '<li>' . $link;
        if ($children) {
            $id = wp_unique_id('wooshop-subcategory-');
            $html .= '<details class="wooshop-subcategory"><summary aria-controls="' . esc_attr($id) . '"><span class="screen-reader-text">' . esc_html(sprintf(__('Subcategories of %s', 'wooshop'), $term->name)) . '</span><span aria-hidden="true">+</span></summary><div id="' . esc_attr($id) . '">' . $children . '</div></details>';
        }
        $html .= '</li>';
    }
    return $html . '</ul>';
}

function wooshop_render_header(array $attributes = [], string $content = ''): string
{
    $id = wp_unique_id('wooshop-categories-');
    $compact = !empty($attributes['compact']) || (function_exists('is_checkout') && is_checkout());
    $classes = 'wooshop-header' . (!empty($attributes['sticky']) ? ' wooshop-header-sticky' : '') . ($compact ? ' wooshop-header-compact' : '');
    $width = wooshop_bounded_int($attributes['logoWidth'] ?? 160, 160, 80, 200);
    $announcement = wooshop_label($attributes['announcement'] ?? '');
    $logo = has_custom_logo() ? get_custom_logo() : '<a class="wooshop-site-name" href="' . esc_url(home_url('/')) . '" rel="home">' . esc_html(get_bloginfo('name')) . '</a>';
    $logoId = wooshop_bounded_int($attributes['logoId'] ?? 0, 0, 0, PHP_INT_MAX);
    if ($logoId && wp_attachment_is_image($logoId)) {
        $logo = '<a class="custom-logo-link" href="' . esc_url(home_url('/')) . '" rel="home">' . wp_get_attachment_image($logoId, 'full', false, ['class' => 'custom-logo', 'alt' => get_bloginfo('name')]) . '</a>';
    }
    $html = '';
    if ($announcement !== '') {
        $noticeId = 'notice-' . substr(hash('sha256', $announcement), 0, 12);
        $html .= '<div class="wooshop-announcement" data-notice="' . esc_attr($noticeId) . '"><div class="wooshop-container"><p>' . esc_html($announcement) . '</p>';
        if (!empty($attributes['dismissible'])) {
            $html .= '<button class="wooshop-notice-close wooshop-js-only" type="button" aria-label="' . esc_attr__('Dismiss announcement', 'wooshop') . '">' . wooshop_icon('close') . '</button>';
        }
        $html .= '</div></div>';
    }
    $html .= '<div class="' . esc_attr($classes) . '" style="--wooshop-logo-width:' . $width . 'px"><div class="wooshop-header-main wooshop-container"><div class="wooshop-brand">' . $logo . '</div>';
    if (!$compact) {
        $html .= wooshop_search_form();
    }
    $html .= '<div class="wooshop-utilities">';
    if (function_exists('wc_get_page_permalink')) {
        $account = wc_get_page_permalink('myaccount');
        if ($account) {
            $html .= '<a class="wooshop-account" href="' . esc_url($account) . '" aria-label="' . esc_attr(is_user_logged_in() ? __('My account', 'wooshop') : __('Sign in', 'wooshop')) . '">' . wooshop_icon('account') . '<span>' . esc_html(is_user_logged_in() ? __('My account', 'wooshop') : __('Sign in', 'wooshop')) . '</span></a>';
        }
        if (!$compact && !(defined('REST_REQUEST') && REST_REQUEST) && WP_Block_Type_Registry::get_instance()->is_registered('woocommerce/mini-cart')) {
            $html .= do_blocks('<!-- wp:woocommerce/mini-cart {"hasHiddenPrice":true} /-->');
        }
        $html .= '<a class="wooshop-cart-link" href="' . esc_url(wc_get_cart_url()) . '" aria-label="' . esc_attr__('Cart', 'wooshop') . '">' . wooshop_icon('bag') . '<span>' . esc_html__('Cart', 'wooshop') . '</span></a>';
    }
    $html .= '</div></div>';
    if (!$compact) {
        $html .= '<div class="wooshop-header-nav wooshop-container"><details class="wooshop-categories"><summary aria-controls="' . esc_attr($id) . '">' . wooshop_icon('menu') . '<span>' . esc_html__('Shop by category', 'wooshop') . '</span></summary><div class="wooshop-category-panel" id="' . esc_attr($id) . '"><div class="wooshop-panel-heading"><h2 tabindex="-1" id="' . esc_attr($id) . '-title">' . esc_html__('Shop by category', 'wooshop') . '</h2><button type="button" class="wooshop-category-close wooshop-js-only" aria-label="' . esc_attr__('Close categories', 'wooshop') . '">' . wooshop_icon('close') . '</button></div><nav aria-label="' . esc_attr__('Product categories', 'wooshop') . '">' . wooshop_category_tree() . '<a class="wooshop-view-all" href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('View all products', 'wooshop') . ' ' . wooshop_icon('arrow') . '</a></nav></div></details><div class="wooshop-primary-nav">';
        if (trim($content) !== '') {
            $html .= $content; // Rendered native navigation inner block, escaped by its owner.
        } elseif (has_nav_menu('primary')) {
            $html .= '<nav aria-label="' . esc_attr__('Primary navigation', 'wooshop') . '">' . wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'echo' => false, 'depth' => 3, 'fallback_cb' => false]) . '</nav>';
        } else {
            $html .= '<nav aria-label="' . esc_attr__('Primary navigation', 'wooshop') . '"><ul><li><a href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('Browse products', 'wooshop') . '</a></li></ul></nav>';
        }
        $html .= '</div></div>';
    }
    return $html . '</div>';
}

function wooshop_render_categories(array $attributes = []): string
{
    if (!taxonomy_exists('product_cat')) {
        return '';
    }
    $terms = get_terms(['taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => wooshop_bounded_int($attributes['count'] ?? 8, 8, 2, 16), 'orderby' => 'name']);
    if (is_wp_error($terms) || !$terms) {
        return '';
    }
    $title = wooshop_label($attributes['title'] ?? '', __('Explore the categories', 'wooshop'));
    if ($title === '') {
        $title = __('Explore the categories', 'wooshop');
    }
    $shape = in_array($attributes['shape'] ?? '', ['square', 'circle', 'none'], true) ? $attributes['shape'] : 'square';
    $id = wp_unique_id('wooshop-category-title-');
    $html = '<section class="wooshop-category-shortcuts" aria-labelledby="' . esc_attr($id) . '"><div class="wooshop-section-heading"><h2 id="' . esc_attr($id) . '">' . esc_html($title) . '</h2><a href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('All categories', 'wooshop') . '</a></div><ul class="wooshop-category-grid wooshop-shape-' . esc_attr($shape) . '">';
    foreach ($terms as $term) {
        $url = get_term_link($term);
        if (is_wp_error($url)) {
            continue;
        }
        $imageId = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
        $image = $imageId ? wp_get_attachment_image($imageId, 'woocommerce_thumbnail', false, ['alt' => '', 'loading' => 'lazy']) : wooshop_icon('grid');
        $html .= '<li><a href="' . esc_url($url) . '">' . ($shape !== 'none' ? '<span class="wooshop-category-image">' . $image . '</span>' : '') . '<span>' . esc_html($term->name) . '</span></a></li>';
    }
    return $html . '</ul></section>';
}

function wooshop_render_collection(array $attributes = []): string
{
    if (!function_exists('wc_get_products') || !shortcode_exists('products')) {
        return '';
    }
    $sources = ['new' => __('New arrivals', 'wooshop'), 'featured' => __('Featured products', 'wooshop'), 'bestselling' => __('Bestselling products', 'wooshop'), 'sale' => __('Current offers', 'wooshop')];
    $candidate = $attributes['source'] ?? 'new';
    $source = is_string($candidate) && isset($sources[$candidate]) ? $candidate : 'new';
    $count = wooshop_bounded_int($attributes['count'] ?? 10, 10, 1, 20);
    $title = wooshop_label($attributes['title'] ?? '');
    $title = $title !== '' ? $title : $sources[$source];
    $arguments = ' limit="' . $count . '" columns="5" visibility="' . ($source === 'featured' ? 'featured' : 'visible') . '"';
    if ($source === 'featured') {
        $arguments .= ' orderby="date" order="DESC"';
    } elseif ($source === 'bestselling') {
        $arguments .= ' best_selling="true"';
    } elseif ($source === 'sale') {
        $arguments .= ' on_sale="true"';
    } else {
        $arguments .= ' orderby="date" order="DESC"';
    }
    // WC owns visibility, prices, ratings, stock, product type and add-to-cart behavior.
    $products = do_shortcode('[products' . $arguments . ']');
    if (!str_contains($products, 'class="products ') && !str_contains($products, "class='products ")) {
        return '';
    }
    $id = wp_unique_id('wooshop-collection-');
    $layout = ($attributes['layout'] ?? '') === 'rail' ? 'rail' : 'grid';
    $density = ($attributes['density'] ?? '') === 'dense' ? 'dense' : 'comfortable';
    return '<section class="wooshop-collection wooshop-collection-' . $layout . ' wooshop-density-' . $density . '" aria-labelledby="' . esc_attr($id) . '"><div class="wooshop-section-heading"><h2 id="' . esc_attr($id) . '">' . esc_html($title) . '</h2><a href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('Browse all', 'wooshop') . ' <span aria-hidden="true">↗</span></a></div>' . ($layout === 'rail' ? '<p class="wooshop-overflow-hint">' . esc_html__('Scroll to explore. Every product also has a full details page.', 'wooshop') . '</p><div class="wooshop-product-scroll" tabindex="0" role="region" aria-label="' . esc_attr($title) . '">' . $products . '</div>' : $products) . '</section>';
}

function wooshop_render_footer(array $attributes = [], string $content = ''): string
{
    $minimal = !empty($attributes['minimal']) || (function_exists('is_checkout') && is_checkout());
    $html = '<div class="wooshop-footer-inner wooshop-container' . ($minimal ? ' wooshop-footer-minimal' : '') . '"><div><a class="wooshop-site-name" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>';
    $description = get_bloginfo('description');
    if (!$minimal && $description) {
        $html .= '<p>' . esc_html($description) . '</p>';
    }
    $html .= '</div><div class="wooshop-footer-navigation">';
    if (trim($content) !== '') {
        $html .= $content;
    } elseif (has_nav_menu('footer')) {
        $html .= '<nav aria-label="' . esc_attr__('Footer navigation', 'wooshop') . '">' . wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'echo' => false, 'depth' => 2, 'fallback_cb' => false]) . '</nav>';
    } else {
        $html .= '<nav aria-label="' . esc_attr__('Footer navigation', 'wooshop') . '"><ul><li><a href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('Browse products', 'wooshop') . '</a></li>';
        $privacy = get_privacy_policy_url();
        if ($privacy) {
            $html .= '<li><a href="' . esc_url($privacy) . '">' . esc_html__('Privacy policy', 'wooshop') . '</a></li>';
        }
        $html .= '</ul></nav>';
    }
    return $html . '</div><p class="wooshop-footer-credit">' . esc_html(sprintf(__('© %1$s %2$s', 'wooshop'), wp_date('Y'), get_bloginfo('name'))) . '</p></div>';
}

function wooshop_render_enhancement(array $attributes = []): string
{
    $surfaces = ['wishlist' => 'commerce_wishlist', 'compare' => 'commerce_compare', 'seller_dashboard' => 'commerce_seller_dashboard', 'unsubscribe' => 'commerce_unsubscribe'];
    $candidate = $attributes['surface'] ?? '';
    $surface = is_string($candidate) ? ($surfaces[$candidate] ?? '') : '';
    if ($surface && shortcode_exists($surface)) {
        return '<div class="wooshop-module-surface">' . do_shortcode('[' . $surface . ']') . '</div>';
    }
    return '<div class="wooshop-state"><h2>' . esc_html__('This feature is unavailable', 'wooshop') . '</h2><p>' . esc_html__('You can continue browsing the catalog or sign in to your account.', 'wooshop') . '</p><a href="' . esc_url(wooshop_shop_url()) . '">' . esc_html__('Browse products', 'wooshop') . '</a></div>';
}

function wooshop_product_enhancements(): void
{
    foreach (['commerce_offers'] as $shortcode) {
        if (shortcode_exists($shortcode)) {
            $output = do_shortcode('[' . $shortcode . ']');
            if (trim($output) !== '') {
                echo '<div class="wooshop-module-surface">' . $output . '</div>'; // Registered plugin owns and escapes this output.
            }
        }
    }
}
