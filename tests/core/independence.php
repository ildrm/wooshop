<?php
/** Installed-package independence probes. Local third site only; never a release file. */
(static function (array $arguments): void {
    if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'local'
        || !defined('WOOSHOP_LOCAL_FIXTURES') || WOOSHOP_LOCAL_FIXTURES !== true
        || untrailingslashit(home_url()) !== 'http://localhost:18882') {
        throw new RuntimeException('Only the isolated loopback package-check site is authorized.');
    }
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $op = $arguments[0] ?? 'inspect';
    $run = $arguments[1] ?? '';
    if (!in_array($op, ['inspect', 'setup', 'plugin', 'theme', 'wordpress', 'restore-options', 'restored', 'cleanup'], true)
        || ($op !== 'inspect' && !preg_match('/^independence-[a-f0-9-]{36}$/D', $run))) {
        throw new RuntimeException('Invalid bounded independence operation.');
    }
    $key = 'wooshop_independence_' . $run;
    $checks = 0;
    $assert = static function (bool $ok, string $message) use (&$checks): void {
        ++$checks;
        if (!$ok) { throw new RuntimeException($message); }
    };
    $emit = static function (array $result) use (&$checks, $op): void {
        echo 'WOOSHOP_INDEPENDENCE=' . wp_json_encode(['operation' => $op, 'assertions' => $checks] + $result, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    };
    $state = static fn (): array => ['theme' => get_stylesheet(), 'template' => get_template(),
        'active_plugins' => array_values(get_option('active_plugins', [])),
        'woocommerce' => is_plugin_active('woocommerce/woocommerce.php'),
        'companion' => is_plugin_active('wooshop-commerce/wooshop-commerce.php')];
    $guard = WPMU_PLUGIN_DIR . '/wooshop-local-guard.php';
    $assert(is_file($guard) && hash_file('sha256', $guard) === hash_file('sha256', '/workspace/tools/dev-guard.php')
        && has_filter('pre_wp_mail') !== false && has_filter('pre_http_request') !== false, 'Actual isolated transport guard required.');
    $profile = get_option('wooshop_fixture_profile', []);
    $nativeOptionKeys = ['woocommerce_coming_soon', 'woocommerce_store_pages_only', 'woocommerce_calc_taxes', 'woocommerce_default_country',
        'woocommerce_bacs_settings', 'woocommerce_cod_settings', 'woocommerce_cheque_settings', 'woocommerce_bacs_accounts',
        'woocommerce_gateway_order', 'woocommerce_enable_guest_checkout', 'woocommerce_downloads_require_login',
        'woocommerce_file_download_method', 'woocommerce_downloads_grant_access_after_payment'];
    $watch = static function () use ($profile, $nativeOptionKeys): array {
        $posts = [];
        $ids = array_values($profile['page_ids'] ?? []);
        foreach (get_posts(['post_type' => 'wp_global_styles', 'post_status' => 'any', 'numberposts' => 100, 'fields' => 'ids']) as $id) { $ids[] = $id; }
        foreach (array_unique($ids) as $id) {
            $post = get_post((int) $id);
            if ($post) { $posts[(string) $id] = hash('sha256', wp_json_encode([$post->post_type, $post->post_title, $post->post_content, $post->post_status])); }
        }
        ksort($posts);
        $commerce = [];
        foreach ($nativeOptionKeys as $option) {
            $absent = new stdClass(); $value = get_option($option, $absent);
            $commerce[$option] = ['exists' => $value !== $absent, 'sha256' => $value === $absent ? null : hash('sha256', serialize($value))];
        }
        return ['profile' => $profile, 'front_page' => get_option('page_on_front'), 'show_on_front' => get_option('show_on_front'),
            'posts' => $posts, 'currency' => get_option('woocommerce_currency'),
            'operational_mode' => get_option('wooshop_commerce_operational_mode'),
            'native_commerce_option_digests' => $commerce,
            'shop_page' => get_option('woocommerce_shop_page_id'), 'cart_page' => get_option('woocommerce_cart_page_id'),
            'checkout_page' => get_option('woocommerce_checkout_page_id'), 'account_page' => get_option('woocommerce_myaccount_page_id')];
    };
    if ($op === 'inspect') {
        $theme = wp_get_theme('twentytwentyfour');
        $assert($theme->exists() && !$theme->errors() && $theme->is_block_theme(), 'Installed unrelated native block theme required.');
        $assert(version_compare(get_bloginfo('version'), $theme->get('RequiresWP'), '>=')
            && version_compare(PHP_VERSION, $theme->get('RequiresPHP'), '>='), 'Unrelated theme requirements must be met normally.');
        $files = [];
        foreach (['plugin' => WP_PLUGIN_DIR . '/wooshop-commerce', 'theme' => get_theme_root() . '/wooshop'] as $kind => $base) {
            $files[$kind] = [];
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)) as $file) {
                $assert(!$file->isLink(), 'Installed package must contain actual files, not source symlinks.');
                if ($file->isFile()) { $files[$kind][str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($base) + 1))] = hash_file('sha256', $file->getPathname()); }
            }
            ksort($files[$kind]);
        }
        $emit(['result' => 'INSPECTED', 'state' => $state(), 'profile' => $profile['profile_key'] ?? null,
            'unrelated_theme' => ['slug' => 'twentytwentyfour', 'version' => $theme->get('Version'), 'requires_wp' => $theme->get('RequiresWP'),
                'requires_php' => $theme->get('RequiresPHP'), 'functions_sha256' => hash_file('sha256', $theme->get_stylesheet_directory() . '/functions.php')],
            'runtime_files' => $files, 'versions' => ['wp' => get_bloginfo('version'), 'wc' => defined('WC_VERSION') ? WC_VERSION : null, 'php' => PHP_VERSION]]);
        return;
    }
    if ($op === 'setup') {
        $assert(get_stylesheet() === 'wooshop' && $state()['woocommerce'] && $state()['companion'], 'Start from the normally active installed packages.');
        $assert(($profile['profile_key'] ?? '') === 'neutral', 'Explicit neutral fixture profile required.');
        $kernel = WooShop\Commerce\Kernel\Plugin::instance();
        $services = apply_filters('wooshop_commerce_catalog_services', []);
        $assert($kernel !== null && isset($services['customer'], $services['session']), 'Installed persistent customer services required.');
        $record = ['run' => $run, 'original' => $state(), 'watch' => $watch(), 'settings' => $kernel->settings()->snapshot(), 'product_id' => 0, 'page_id' => 0];
        foreach ([...$nativeOptionKeys, 'wooshop_commerce_operational_mode'] as $option) {
            $absent = new stdClass(); $value = get_option($option, $absent);
            $record['private_option_snapshot'][$option] = ['exists' => $value !== $absent, 'value' => $value === $absent ? null : $value];
        }
        $assert(add_option($key, $record, '', false), 'Run identity already exists; do not overwrite its retained evidence.');
        $product = new WC_Product_Simple();
        $product->set_name('Synthetic independence probe ' . $run);
        $product->set_status('publish'); $product->set_catalog_visibility('hidden');
        $product->set_virtual(true); $product->set_regular_price('1.23');
        $product->update_meta_data('_wooshop_test_origin', 'core.independence');
        $product->update_meta_data('_wooshop_test_run', $run); $product->update_meta_data('_wooshop_synthetic_fixture', true);
        $product->save(); $record['product_id'] = $product->get_id(); update_option($key, $record, false);
        $assert(!$product->is_visible(), 'Dedicated synthetic probe must be hidden from catalog listings.');
        $page = wp_insert_post(wp_slash(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Synthetic independence customer surface',
            'post_name' => $run, 'post_content' => '<!-- wp:paragraph --><p>SYNTHETIC LOCAL INDEPENDENCE PROBE. No real purchase or messaging.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[commerce_wishlist]<!-- /wp:shortcode -->',
            'meta_input' => ['_wooshop_test_origin' => 'core.independence', '_wooshop_test_run' => $run, '_wooshop_synthetic_fixture' => true]]), true);
        $assert(!is_wp_error($page), 'Unable to create the dedicated native shortcode page.');
        $record['page_id'] = (int) $page; update_option($key, $record, false);
        $services['session']->start();
        $cookieName = 'wooshop_guest_' . substr(hash('sha256', (string) get_current_blog_id() . home_url('/')), 0, 12);
        $record['private_guest_cookie'] = $_COOKIE[$cookieName]; // Never returned to runner/evidence.
        $before = $services['customer']->wishlist();
        $record['wishlist_before_version'] = $before['version'];
        $record['wishlist'] = $services['customer']->saveItem(['product_id' => (string) $record['product_id']], $before['version'], $run . '-save');
        $record['domain'] = $kernel->entities()->create('assurance.independence', ['run' => $run, 'origin' => 'core.independence', 'profile' => 'neutral'], 'assurance:independence');
        update_option($key, $record, false);
        $fixture = wc_get_product((int) $profile['native_product_ids']['simple']);
        $emit(['result' => 'SETUP', 'run' => $run, 'probe_product_id' => $record['product_id'], 'probe_product_name' => $product->get_name(),
            'probe_page_url' => get_permalink($page), 'native_product_url' => $fixture->get_permalink(), 'native_product_id' => $fixture->get_id(),
            'native_product_name' => $fixture->get_name(), 'shop_url' => wc_get_page_permalink('shop'), 'cart_url' => wc_get_cart_url(),
            'cart_api_url' => rest_url('wc/store/v1/cart'),
            'account_url' => wc_get_page_permalink('myaccount'), 'help_url' => get_permalink($profile['page_ids']['help']), 'original' => $record['original']]);
        return;
    }
    $record = get_option($key, []);
    $assert(($record['run'] ?? '') === $run, 'Matching retained probe identity required.');
    if ($op === 'restore-options') {
        $assert($state() === $record['original'], 'Restore supported plugin/theme activation state before its option snapshot.');
        foreach ($record['private_option_snapshot'] ?? [] as $option => $original) {
            $assert(in_array($option, [...$nativeOptionKeys, 'wooshop_commerce_operational_mode'], true), 'Option restoration allowlist rejected an unknown key.');
            if ($original['exists']) { update_option($option, $original['value']); }
            else { delete_option($option); }
        }
        if (empty($record['private_option_snapshot'])) {
            // Recovery for the first digest-only attempt: restore only proven prior absence.
            foreach ($record['watch']['native_commerce_option_digests'] as $option => $original) {
                if (!$original['exists']) { delete_option($option); }
            }
        }
        $after = $watch();
        $assert($after['native_commerce_option_digests'] === $record['watch']['native_commerce_option_digests']
            && $after['operational_mode'] === $record['watch']['operational_mode'], 'Native commerce option values/existence did not restore exactly.');
    } elseif ($op === 'plugin' || $op === 'restored') {
        $assert($state()['woocommerce'] && $state()['companion'], 'Normal active plugin dependencies required.');
        $assert(get_stylesheet() === ($op === 'plugin' ? 'twentytwentyfour' : $record['original']['theme']), 'Unexpected matrix theme.');
        $kernel = WooShop\Commerce\Kernel\Plugin::instance();
        $services = apply_filters('wooshop_commerce_catalog_services', []);
        $cookieName = 'wooshop_guest_' . substr(hash('sha256', (string) get_current_blog_id() . home_url('/')), 0, 12);
        $_COOKIE[$cookieName] = $record['private_guest_cookie'];
        $assert($services['customer']->wishlist() === $record['wishlist'], 'Customer wishlist did not survive independent theme/plugin lifecycle.');
        $replay = $services['customer']->saveItem(['product_id' => (string) $record['product_id']], $record['wishlist_before_version'], $run . '-save');
        $assert($replay === $record['wishlist'], 'Persistent customer command replay changed across lifecycle.');
        $assert($kernel->entities()->get('assurance.independence', $record['domain']['id']) === $record['domain'], 'Persistent extension domain record changed.');
        $assert($kernel->settings()->snapshot() === $record['settings'], 'Typed plugin settings/history revision changed.');
        $assert(shortcode_exists('commerce_wishlist'), 'Theme-independent native shortcode registration missing.');
        $html = do_shortcode('[commerce_wishlist]');
        $assert(str_contains($html, 'aria-label="Wishlist"') && str_contains($html, '<form') && str_contains($html, '<label>') && str_contains($html, 'type="submit"'), 'Native shortcode fallback lacks labelled section/form controls.');
    } elseif ($op === 'theme') {
        $assert($state()['woocommerce'] && !$state()['companion'] && get_stylesheet() === 'wooshop', 'Expected custom theme plus native WooCommerce only.');
        $assert(!class_exists('WooShop\\Commerce\\Kernel\\Plugin', false) && !shortcode_exists('commerce_wishlist'), 'Inactive companion must not be loaded by the theme.');
        $assert(function_exists('wooshop_render_header') && class_exists('WooCommerce'), 'Native theme presentation/WooCommerce unavailable.');
        $assert(wc_get_product((int) $record['product_id'])->get_catalog_visibility() === 'hidden', 'Dedicated probe leaked into the public catalog.');
    } elseif ($op === 'wordpress') {
        $assert(!$state()['woocommerce'] && !$state()['companion'] && get_stylesheet() === 'wooshop', 'Expected standalone native WordPress plus theme.');
        $assert(!class_exists('WooCommerce', false) && !function_exists('wc_get_product'), 'Inactive WooCommerce must not be artificially loaded.');
        $assert(function_exists('wooshop_render_header') && str_contains(wooshop_search_form(), 'type="search"'), 'Native WordPress search fallback unavailable.');
        $assert(!str_contains(wooshop_search_form(), 'name="post_type"'), 'Standalone search must not target an unavailable product post type.');
    } elseif ($op === 'cleanup') {
        // No deletion: preserve tagged probes/customer/domain history for evidence.
        foreach (['product_id' => 'product', 'page_id' => 'page'] as $field => $type) {
            $id = (int) ($record[$field] ?? 0);
            if (!$id) { continue; }
            $assert(get_post_type($id) === $type && get_post_meta($id, '_wooshop_test_origin', true) === 'core.independence'
                && get_post_meta($id, '_wooshop_test_run', true) === $run, 'Refusing cleanup of a foreign native record.');
            $updated = wp_update_post(['ID' => $id, 'post_status' => 'draft'], true);
            $assert(!is_wp_error($updated), 'Unable to draft the dedicated probe.');
        }
        $assert($state() === $record['original'], 'Activation/theme state was not restored exactly.');
        $after = $watch();
        // Auto-created default-theme style records are retained; every original style/page must match.
        foreach ($record['watch']['posts'] as $id => $digest) {
            $assert(($after['posts'][$id] ?? null) === $digest, 'Existing native page/style content changed.');
        }
        unset($after['posts']); $beforeWatch = $record['watch']; unset($beforeWatch['posts']);
        $assert($after === $beforeWatch, 'Native fixture/profile/page assignments or currency changed.');
        unset($record['private_guest_cookie'], $record['private_option_snapshot']); // Completed evidence retains no recovery secrets.
        $record['completed_at'] = gmdate('c'); update_option($key, $record, false);
        $assert(!isset(get_option($key)['private_guest_cookie']), 'Completed run must not retain its recoverable private guest cookie.');
    }
    $emit(['result' => 'PASS', 'run' => $run, 'state' => $state(), 'domain_id' => $record['domain']['id'] ?? null,
        'wishlist_id' => $record['wishlist']['id'] ?? null, 'probe_product_id' => $record['product_id'], 'probe_page_id' => $record['page_id'],
        'cleanup' => $op === 'cleanup' ? 'Dedicated native probes drafted; customer/domain/history retained; no deletion' : null]);
})(isset($args) && is_array($args) ? $args : []);
