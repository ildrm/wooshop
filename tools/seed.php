<?php
/** Local-only, additive synthetic fixtures. Invoke: wp eval-file /workspace/tools/seed.php outdoor */
if (!defined('ABSPATH') || !function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'local' || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true) {
    throw new RuntimeException('Fixture import refused: a local WordPress environment and WOOSHOP_LOCAL_FIXTURES=true are both required.');
}
if (!class_exists('WooCommerce') || !class_exists('WC_Product_Simple')) {
    throw new RuntimeException('Activate actual WooCommerce before importing fixtures.');
}
$profileKey = $args[0] ?? '';
if (!in_array($profileKey, ['outdoor', 'smart-home', 'neutral'], true)) {
    throw new RuntimeException('Choose the explicit fixture profile: outdoor, smart-home or neutral.');
}
$root = dirname(__DIR__);
$profile = json_decode(file_get_contents($root . '/profiles/' . $profileKey . '.json'), true, 32, JSON_THROW_ON_ERROR);
$previous = get_option('wooshop_fixture_profile');
if ($previous && ($previous['profile_key'] ?? '') !== $profileKey) {
    throw new RuntimeException('This site already has another fixture profile. Use an independent local installation; no reset or cross-profile overwrite is performed.');
}
if (!add_option('wooshop_fixture_seed_lock', ['profile' => $profileKey, 'started_at' => gmdate('c')], '', false)) {
    throw new RuntimeException('A fixture import lock already exists. Inspect the other local run before removing its lock.');
}
$created = [];
$reused = [];
$notes = [];
$find = static function (string $key, string $type = 'product'): int {
    $ids = get_posts(['post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_wooshop_fixture_key', 'meta_value' => $key, 'orderby' => 'ID', 'order' => 'ASC']);
    if (count($ids) > 1) {
        throw new RuntimeException('Duplicate fixture identity: ' . $key . '. No existing record was overwritten.');
    }
    return $ids ? (int) $ids[0] : 0;
};
$post = static function (string $key, string $type, string $title, string $content, string $slug = '', string $status = 'publish') use ($find, &$created, &$reused): int {
    $existing = $find($key, $type);
    if ($existing) {
        $reused[$key] = $existing;
        return $existing;
    }
    $id = wp_insert_post(wp_slash(['post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => $content, 'post_name' => $slug, 'meta_input' => ['_wooshop_fixture_key' => $key, '_wooshop_synthetic_fixture' => true]]), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $created[$key] = (int) $id;
    return (int) $id;
};
try {
    $existingProducts = get_posts(['post_type' => 'product', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1]);
    $existingStyles = get_posts(['post_type' => 'wp_global_styles', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 1]);
    $configureRequested = in_array('configure-profile', $args, true);
    $configured = get_option('wooshop_profile_seeded');
    // Preserve the once-applied state from the first additive importer revision.
    if (!$configured && !empty($previous['configuration_applied'])) {
        $configured = ['profile_key' => $profileKey, 'configured_at' => $previous['last_seed_at'] ?? gmdate('c'), 'importer_revision' => 1];
        add_option('wooshop_profile_seeded', $configured, '', false);
    }
    $canConfigure = !$configured && $configureRequested;
    if ($canConfigure && $existingStyles) {
        throw new RuntimeException('Initial profile configuration refused because native user styles already exist. Existing styles are preserved.');
    }
    if (!$canConfigure) {
        $notes[] = $configured ? 'Initial fixture configuration was already applied; all later theme, content and commerce edits are preserved.' : 'Fixture data only. Add the positional configure-profile marker to explicitly apply initial local defaults once.';
    }
    $prefix = 'fixture:' . $profileKey . ':';
    $categories = [];
    foreach ($profile['categories'] as $index => $name) {
        $key = $prefix . 'category:' . $index;
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 2, 'meta_query' => [['key' => '_wooshop_fixture_key', 'value' => $key, 'compare' => '=']]]);
        if (is_wp_error($terms) || count($terms) > 1) {
            throw new RuntimeException('Cannot resolve fixture category identity.');
        }
        if ($terms) {
            $categories[] = (int) $terms[0]->term_id;
            $reused[$key] = (int) $terms[0]->term_id;
            continue;
        }
        $insert = wp_insert_term($name, 'product_cat', ['slug' => 'synthetic-' . $profileKey . '-' . $index, 'description' => 'SYNTHETIC LOCAL FIXTURE: editorial category, not a safety or compatibility claim.']);
        if (is_wp_error($insert)) {
            throw new RuntimeException($insert->get_error_message());
        }
        $id = (int) $insert['term_id'];
        update_term_meta($id, '_wooshop_fixture_key', $key);
        $categories[] = $id;
        $created[$key] = $id;
    }
    $mediaKey = $prefix . 'image';
    $imageId = $find($mediaKey, 'attachment');
    if (!$imageId) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $source = $root . '/profiles/' . $profile['image'];
        $temporary = wp_tempnam('wooshop-' . $profileKey . '.png');
        if (!$temporary || !copy($source, $temporary)) {
            throw new RuntimeException('Cannot copy the original local fixture illustration.');
        }
        $imageId = media_handle_sideload(['name' => 'wooshop-synthetic-' . $profileKey . '.png', 'tmp_name' => $temporary], 0, 'Synthetic local product illustration');
        if (is_wp_error($imageId)) {
            @unlink($temporary);
            throw new RuntimeException($imageId->get_error_message());
        }
        update_post_meta($imageId, '_wooshop_fixture_key', $mediaKey);
        update_post_meta($imageId, '_wp_attachment_image_alt', $profile['image_alt']);
        $created[$mediaKey] = $imageId;
    } else {
        $reused[$mediaKey] = $imageId;
    }
    foreach ($categories as $id) {
        // Add fixture art only to categories that this run created.
        if (in_array($id, $created, true) && !get_term_meta($id, 'thumbnail_id', true)) {
            update_term_meta($id, 'thumbnail_id', $imageId);
        }
    }
    $makeProduct = static function (string $key, string $type, string $title, string $price, int $category, array $flags = []) use ($find, $profile, $imageId, &$created, &$reused): WC_Product {
        $existing = $find($key);
        if ($existing) {
            $product = wc_get_product($existing);
            if (!$product || $product->get_type() !== $type) {
                throw new RuntimeException('Existing fixture product type changed; no overwrite: ' . $key);
            }
            $reused[$key] = $existing;
            return $product;
        }
        $classes = ['simple' => WC_Product_Simple::class, 'variable' => WC_Product_Variable::class, 'grouped' => WC_Product_Grouped::class, 'external' => WC_Product_External::class];
        $product = new $classes[$type]();
        $product->set_name($title);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_sku('WS-FIX-' . strtoupper(substr(hash('sha256', $key), 0, 16)));
        $product->set_description('<p><strong>SYNTHETIC LOCAL FIXTURE — not a real saleable product.</strong></p><p>' . esc_html($profile['guide_copy']) . '</p><p>No real certification, safety approval, warranty or device interoperability is supplied.</p>');
        $product->set_short_description('Synthetic local test record. No real purchase, certification or compatibility promise.');
        $product->set_category_ids([$category]);
        $product->set_image_id((int) $imageId);
        $product->set_tax_status('none');
        $product->set_regular_price($price);
        $product->set_price($price);
        $product->set_virtual(!empty($flags['virtual']));
        if ($type === 'simple' && empty($flags['virtual'])) {
            $product->set_weight('0.98');
            $product->set_length('52');
            $product->set_width('28');
            $product->set_height('22');
        }
        if (in_array($type, ['simple', 'variable'], true)) {
            $product->set_manage_stock($type === 'simple');
            $product->set_stock_quantity($type === 'simple' ? ($flags['stock'] ?? 5) : null);
            $product->set_backorders('no');
            $product->set_stock_status(($flags['stock'] ?? 5) > 0 ? 'instock' : 'outofstock');
        }
        if (!empty($flags['featured'])) {
            $product->set_featured(true);
        }
        if (!empty($flags['sale'])) {
            $product->set_sale_price('24.90');
            $product->set_price('24.90');
        }
        $attributes = [];
        foreach ($profile['attributes'] as $name => $value) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($name);
            $attribute->set_options([$value]);
            $attribute->set_visible(true);
            $attributes[] = $attribute;
        }
        if ($type === 'variable') {
            $size = new WC_Product_Attribute();
            $size->set_name('Fixture size');
            $size->set_options(['M', 'L']);
            $size->set_visible(true);
            $size->set_variation(true);
            $attributes[] = $size;
        }
        $product->set_attributes($attributes);
        if ($type === 'external') {
            $product->set_product_url('https://example.invalid/wooshop-synthetic-supplier');
            $product->set_button_text('Synthetic supplier link (external)');
        }
        $product->update_meta_data('_wooshop_fixture_key', $key);
        $product->update_meta_data('_wooshop_synthetic_fixture', true);
        $product->save();
        $created[$key] = $product->get_id();
        return $product;
    };
    $products = [];
    $products['simple'] = $makeProduct($prefix . 'product:simple', 'simple', $profile['primary_product'], '29.90', $categories[0], ['featured' => true, 'sale' => true]);
    $products['variable'] = $makeProduct($prefix . 'product:variable', 'variable', $profile['variable_product'], '', $categories[1], ['featured' => true]);
    foreach (['M' => ['39.90', 1], 'L' => ['44.90', 3]] as $size => [$price, $stock]) {
        $key = $prefix . 'variation:' . $size;
        $existing = $find($key, 'product_variation');
        if ($existing) {
            $reused[$key] = $existing;
            continue;
        }
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($products['variable']->get_id());
        $variation->set_attributes(['fixture-size' => $size]);
        $variation->set_regular_price($price);
        $variation->set_price($price);
        $variation->set_manage_stock(true);
        $variation->set_stock_quantity($stock);
        $variation->set_backorders('no');
        $variation->set_status('publish');
        $variation->set_image_id((int) $imageId);
        $variation->set_sku('WS-FIX-' . strtoupper(substr(hash('sha256', $key), 0, 16)));
        $variation->update_meta_data('_wooshop_fixture_key', $key);
        $variation->update_meta_data('_wooshop_synthetic_fixture', true);
        $variation->save();
        $created[$key] = $variation->get_id();
    }
    WC_Product_Variable::sync($products['variable']->get_id());
    $products['external'] = $makeProduct($prefix . 'product:external', 'external', 'Synthetic external supplier product — no local checkout', '49.90', $categories[2]);
    $products['virtual'] = $makeProduct($prefix . 'product:virtual', 'simple', 'Synthetic fixed-scope virtual service — no scheduling', '19.90', $categories[3], ['virtual' => true]);
    $downloadKey = $prefix . 'product:download';
    $downloadExisted = (bool) $find($downloadKey);
    $products['download'] = $makeProduct($downloadKey, 'simple', 'Synthetic downloadable training note', '4.90', $categories[4], ['virtual' => true]);
    if (!$downloadExisted) {
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            throw new RuntimeException('Local uploads directory unavailable.');
        }
        $directory = $uploads['basedir'] . '/woocommerce_uploads';
        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException('Cannot create protected local fixture download directory.');
        }
        if (!file_exists($directory . '/.htaccess')) {
            file_put_contents($directory . '/.htaccess', "deny from all\n");
        }
        $filename = 'wooshop-synthetic-' . $profileKey . '.txt';
        if (!file_exists($directory . '/' . $filename)) {
            file_put_contents($directory . '/' . $filename, "SYNTHETIC LOCAL FIXTURE\nThis original training file contains no safety, certification or interoperability instructions.\n");
        }
        $download = new WC_Product_Download();
        $download->set_id(substr(hash('sha256', $downloadKey), 0, 32));
        $download->set_name('Synthetic local training note');
        $download->set_file($uploads['baseurl'] . '/woocommerce_uploads/' . $filename);
        $products['download']->set_downloadable(true);
        $products['download']->set_download_limit(3);
        $products['download']->set_download_expiry(7);
        $products['download']->set_downloads([$download]);
        $products['download']->save();
    }
    $groupKey = $prefix . 'product:grouped';
    $groupExisted = (bool) $find($groupKey);
    $products['grouped'] = $makeProduct($groupKey, 'grouped', 'Synthetic grouped products — choose children separately', '', $categories[5]);
    if (!$groupExisted) {
        $products['grouped']->set_children([$products['simple']->get_id(), $products['virtual']->get_id()]);
        $products['grouped']->save();
    }
    $products['sold-out'] = $makeProduct($prefix . 'product:sold-out', 'simple', 'Synthetic unavailable product — zero stock', '12.00', $categories[0], ['stock' => 0]);
    $products['unknown-price'] = $makeProduct($prefix . 'product:unknown-price', 'simple', 'Synthetic product — price not supplied', '', $categories[1]);
    $products['zero-price'] = $makeProduct($prefix . 'product:zero-price', 'simple', 'Synthetic free sample — zero is distinct from unknown', '0', $categories[2], ['virtual' => true]);
    for ($index = 1; $index <= 16; ++$index) {
        $makeProduct($prefix . 'product:collection-' . $index, 'simple', 'Synthetic collection product ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . ($index === 1 ? ' · محصول آزمایشی با عنوان طولانی برای بررسی راست‌به‌چپ SH-220-A' : ''), (string) (10 + $index) . '.00', $categories[$index % count($categories)], ['featured' => $index <= 4]);
    }
    $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($profile['pattern']);
    $homeContent = ($pattern['content'] ?? '') ?: '<!-- wp:heading {"level":1} --><h1>Synthetic local store</h1><!-- /wp:heading --><!-- wp:wooshop/category-shortcuts /--><!-- wp:wooshop/product-collection /-->';
    // Capture the pinned WC installer's actual block compositions through its public filter.
    // Returning no page definitions prevents that installer call from creating or assigning pages.
    $nativePages = [];
    $capturePages = static function (array $definitions) use (&$nativePages): array {
        $nativePages = $definitions;
        return [];
    };
    add_filter('woocommerce_create_pages', $capturePages, PHP_INT_MAX);
    WC_Install::create_pages();
    remove_filter('woocommerce_create_pages', $capturePages, PHP_INT_MAX);
    if (empty($nativePages['cart']['content']) || empty($nativePages['checkout']['content']) || !has_block('woocommerce/cart', $nativePages['cart']['content']) || !has_block('woocommerce/checkout', $nativePages['checkout']['content'])) {
        throw new RuntimeException('Native Cart/Checkout block compositions are unavailable; no shortcode substitution is performed.');
    }
    $pages = [];
    $homeAlreadyExisted = (bool) $find($prefix . 'page:home', 'page');
    $initialHeroTarget = wc_get_page_permalink('shop');
    $pages['home'] = $post($prefix . 'page:home', 'page', 'Synthetic storefront', $homeContent, 'synthetic-home');
    if (!$homeAlreadyExisted) {
        update_post_meta($pages['home'], '_wooshop_fixture_hero_target', $initialHeroTarget);
    }
    $pages['shop'] = $post($prefix . 'page:shop', 'page', 'Synthetic catalog', '', 'shop');
    $pages['cart'] = $post($prefix . 'page:cart', 'page', 'Cart', $nativePages['cart']['content'], 'cart');
    $pages['checkout'] = $post($prefix . 'page:checkout', 'page', 'Checkout', $nativePages['checkout']['content'], 'checkout');
    $pages['myaccount'] = $post($prefix . 'page:account', 'page', 'My account', '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->', 'my-account');
    $pages['help'] = $post($prefix . 'page:help', 'page', 'Synthetic local help', '<!-- wp:heading --><h2>Synthetic local environment</h2><!-- /wp:heading --><!-- wp:paragraph --><p>These products, prices, shipping and offline payment methods are local test fixtures. No real purchases, product claims, returns policy or business approval are supplied.</p><!-- /wp:paragraph -->', 'synthetic-help');
    $pages['wishlist'] = $post($prefix . 'page:wishlist', 'page', 'Saved products', '<!-- wp:wooshop/enhancement {"surface":"wishlist"} /-->', 'saved-products');
    $pages['compare'] = $post($prefix . 'page:compare', 'page', 'Compare products', '<!-- wp:wooshop/enhancement {"surface":"compare"} /-->', 'compare-products');
    $pages['unsubscribe'] = $post($prefix . 'page:unsubscribe', 'page', 'Notification unsubscribe', '<!-- wp:wooshop/enhancement {"surface":"unsubscribe"} /-->', 'unsubscribe');
    $post($prefix . 'post:guide', 'post', $profile['guide_title'], '<!-- wp:paragraph --><p>' . esc_html($profile['guide_copy']) . '</p><!-- /wp:paragraph -->', 'synthetic-guide');
    if ($canConfigure) {
        if (wp_get_theme()->get_stylesheet() !== 'wooshop') {
            switch_theme('wooshop');
        }
        foreach (['shop', 'cart', 'checkout', 'myaccount'] as $kind) {
            update_option('woocommerce_' . $kind . '_page_id', $pages[$kind]);
        }
        update_option('blogname', $profile['title']);
        update_option('blogdescription', 'SYNTHETIC LOCAL FIXTURE · No real purchases or verified product claims');
        update_option('blog_public', '0');
        update_option('show_on_front', 'page');
        update_option('page_on_front', $pages['home']);
        update_option('woocommerce_currency', $profile['fixture_currency']);
        update_option('woocommerce_default_country', $profile['fixture_country']);
        update_option('woocommerce_calc_taxes', 'no');
        update_option('woocommerce_weight_unit', 'kg');
        update_option('woocommerce_dimension_unit', 'cm');
        update_option('woocommerce_downloads_require_login', 'yes');
        update_option('woocommerce_file_download_method', 'force');
        update_option('woocommerce_cod_settings', ['enabled' => 'yes', 'title' => 'Local fixture: offline payment (no real funds)', 'description' => 'SYNTHETIC LOCAL TEST ONLY. No payment is transferred.', 'instructions' => 'Local test order only. No real purchase.', 'enable_for_virtual' => 'yes']);
        $zone = new WC_Shipping_Zone(0);
        $instance = $zone->add_shipping_method('flat_rate');
        if ($instance) {
            update_option('woocommerce_flat_rate_' . $instance . '_settings', ['title' => 'Synthetic local shipping', 'tax_status' => 'none', 'cost' => '5.00']);
        }
        $variationPath = get_template_directory() . '/styles/' . $profile['preset'] . '.json';
        $variation = json_decode(file_get_contents($variationPath), true, 32, JSON_THROW_ON_ERROR);
        $variation['isGlobalStylesUserThemeJSON'] = true;
        unset($variation['$schema'], $variation['title']);
        $styleId = $post($prefix . 'styles', 'wp_global_styles', 'Synthetic fixture preset: ' . $profile['preset'], wp_json_encode($variation), 'wp-global-styles-wooshop');
        wp_set_object_terms($styleId, 'wooshop', 'wp_theme');
        $links = '';
        foreach (['shop' => 'Catalog', 'help' => 'Local fixture help', 'wishlist' => 'Saved products', 'compare' => 'Compare'] as $kind => $label) {
            $links .= '<!-- wp:navigation-link ' . wp_json_encode(['label' => $label, 'type' => 'page', 'id' => $pages[$kind], 'url' => get_permalink($pages[$kind]), 'kind' => 'post-type']) . ' /-->';
        }
        $navId = $post($prefix . 'navigation', 'wp_navigation', 'Synthetic fixture navigation', $links, 'synthetic-fixture-navigation');
        $headerContent = '<!-- wp:wooshop/store-header ' . wp_json_encode(['sticky' => true, 'announcement' => 'Synthetic local store — no real purchases or product claims', 'dismissible' => true]) . ' --><!-- wp:navigation ' . wp_json_encode(['ref' => $navId, 'overlayMenu' => 'never', 'submenuVisibility' => 'click', 'maxNestingLevel' => 3]) . ' /--><!-- /wp:wooshop/store-header -->';
        $templateParts = get_posts(['post_type' => 'wp_template_part', 'post_status' => 'any', 'posts_per_page' => 1, 'name' => 'header']);
        if (!$templateParts) {
            $headerId = $post($prefix . 'part:header', 'wp_template_part', 'Synthetic fixture header', $headerContent, 'header');
            wp_set_object_terms($headerId, 'wooshop', 'wp_theme');
            wp_set_object_terms($headerId, 'header', 'wp_template_part_area');
        }
        $templates = get_posts(['post_type' => 'wp_template', 'post_status' => 'any', 'posts_per_page' => 1, 'name' => 'front-page']);
        if (!$templates) {
            $baseTemplate = file_get_contents(get_template_directory() . '/templates/front-page.html');
            $homeTemplate = str_replace('<!-- wp:pattern {"slug":"wooshop/home"} /-->', '<!-- wp:post-content {"layout":{"type":"constrained"}} /-->', $baseTemplate);
            $templateId = $post($prefix . 'template:home', 'wp_template', 'Synthetic fixture front page', $homeTemplate, 'front-page');
            wp_set_object_terms($templateId, 'wooshop', 'wp_theme');
        }
        WP_Theme_JSON_Resolver::clean_cached_data();
        wc_delete_product_transients();
        add_option('wooshop_profile_seeded', ['profile_key' => $profileKey, 'configured_at' => gmdate('c'), 'importer_revision' => 2], '', false);
        $notes[] = 'Initial local fixture configuration applied once: USD, fictional/test geography US:CA, no taxes, shipping5.00 and offline COD; no commercial/legal choices inferred.';
    }
    // The pattern was resolved before new fixture Woo page assignments. Repair only
    // the owned hero's unchanged default destination, preserving every other byte and
    // any merchant-edited URL. Existing imports without metadata accept only the known
    // original native /shop/ destination as a one-time migration.
    $fixtureHeroPage = get_post($pages['home']);
    if ($fixtureHeroPage && get_post_meta($pages['home'], '_wooshop_fixture_key', true) === $prefix . 'page:home') {
        $fixtureHeroExpected = get_post_meta($pages['home'], '_wooshop_fixture_hero_target', true);
        $fixtureHeroExpected = is_string($fixtureHeroExpected) && $fixtureHeroExpected !== '' ? [$fixtureHeroExpected] : [home_url('/shop/'), '/shop/'];
        $fixtureHeroTarget = wc_get_page_permalink('shop');
        $fixtureHeroCandidates = [];
        $fixtureFindHeroButtons = static function (array $blocks, bool $insideHero = false) use (&$fixtureFindHeroButtons, &$fixtureHeroCandidates): void {
            foreach ($blocks as $block) {
                $classes = ' ' . ($block['attrs']['className'] ?? '') . ' ';
                $isHero = $insideHero || ($block['blockName'] === 'core/group' && str_contains($classes, ' wooshop-hero '));
                if ($isHero && $block['blockName'] === 'core/button') {
                    $tags = new WP_HTML_Tag_Processor($block['innerHTML']);
                    if ($tags->next_tag(['tag_name' => 'A', 'class_name' => 'wp-block-button__link'])) {
                        $fixtureHeroCandidates[] = ['html' => $block['innerHTML'], 'url' => $tags->get_attribute('href')];
                    }
                }
                $fixtureFindHeroButtons($block['innerBlocks'], $isHero);
            }
        };
        $fixtureFindHeroButtons(parse_blocks($fixtureHeroPage->post_content));
        $fixtureHeroMatches = array_values(array_filter($fixtureHeroCandidates, static fn(array $candidate): bool => in_array($candidate['url'], $fixtureHeroExpected, true)));
        if (count($fixtureHeroMatches) === 1 && substr_count($fixtureHeroPage->post_content, $fixtureHeroMatches[0]['html']) === 1) {
            $fixtureHeroMatch = $fixtureHeroMatches[0];
            if ($fixtureHeroMatch['url'] !== $fixtureHeroTarget) {
                $tags = new WP_HTML_Tag_Processor($fixtureHeroMatch['html']);
                $tags->next_tag(['tag_name' => 'A', 'class_name' => 'wp-block-button__link']);
                $tags->set_attribute('href', $fixtureHeroTarget);
                $updatedHome = str_replace($fixtureHeroMatch['html'], $tags->get_updated_html(), $fixtureHeroPage->post_content);
                $updated = wp_update_post(wp_slash(['ID' => $pages['home'], 'post_content' => $updatedHome]), true);
                if (is_wp_error($updated)) {
                    throw new RuntimeException('Could not repair the owned fixture hero catalog destination.');
                }
                $notes[] = 'Unedited owned fixture hero now targets the actual assigned WooCommerce catalog.';
            }
            update_post_meta($pages['home'], '_wooshop_fixture_hero_target', $fixtureHeroTarget);
        } elseif (count($fixtureHeroCandidates) === 1 && $fixtureHeroCandidates[0]['url'] === $fixtureHeroTarget) {
            update_post_meta($pages['home'], '_wooshop_fixture_hero_target', $fixtureHeroTarget);
        } else {
            $notes[] = 'Hero destination preserved: merchant-edited, missing or ambiguous fixture CTA.';
        }
    }
    $state = ['schema_version' => 1, 'profile_key' => $profileKey, 'classification' => 'synthetic local only', 'preset' => $profile['preset'], 'created_at' => $previous['created_at'] ?? gmdate('c'), 'last_seed_at' => gmdate('c'), 'fixture_manifest_sha256' => hash_file('sha256', $root . '/profiles/' . $profileKey . '.json'), 'native_product_ids' => array_map(static fn(WC_Product $product): int => $product->get_id(), $products), 'page_ids' => $pages, 'configuration_applied' => $canConfigure || !empty($previous['configuration_applied'])];
    update_option('wooshop_fixture_profile', $state, false);
    echo wp_json_encode(['status' => 'SEEDED', 'profile' => $profileKey, 'created_count' => count($created), 'reused_count' => count($reused), 'initial_configuration_applied' => $canConfigure, 'products' => $state['native_product_ids'], 'pages' => $pages, 'notes' => $notes], JSON_UNESCAPED_UNICODE) . "\n";
} finally {
    delete_option('wooshop_fixture_seed_lock');
}
