<?php
/** Product-only safety net if a native/plugin transaction boundary commits a synthetic probe. */
function catalog_save_product_fixture(WC_Product $product, string $run): string
{
    $product->update_meta_data('_wooshop_catalog_test_origin', $run);
    $id = (string) $product->save();
    $GLOBALS['wooshop_catalog_owned_products'][$id] = $run;
    if (empty($GLOBALS['wooshop_catalog_cleanup_registered'])) {
        $GLOBALS['wooshop_catalog_cleanup_registered'] = true;
        register_shutdown_function(static function (): void {
            if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES) { return; }
            global $wpdb;
            foreach ($GLOBALS['wooshop_catalog_owned_products'] ?? [] as $productId => $origin) {
                $confirmed = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_wooshop_catalog_test_origin'", (int) $productId));
                if ($confirmed !== $origin || $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID=%d AND post_type='product'", (int) $productId)) === null) { continue; }
                $product = wc_get_product((int) $productId);
                if ($product) { $product->set_status('draft'); $product->set_catalog_visibility('hidden'); $product->save(); }
            }
        });
    }
    return $id;
}
