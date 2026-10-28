<?php
declare(strict_types=1);

/** Local test product provenance and reversible cleanup. Orders and profile fixtures are never removed. */
final class MarketplaceFixtureProducts
{
    private static array $tracked = [];
    private static bool $registered = false;

    public static function track(object $product, string $origin, string $run): void
    {
        self::guard();
        if (!in_array($origin, ['marketplace.integration', 'marketplace.store-api'], true) || !preg_match('/^[a-f0-9]{10,32}$/D', $run)) { throw new RuntimeException('Invalid test product provenance.'); }
        $product->update_meta_data('_wooshop_test_origin', $origin);
        $product->update_meta_data('_wooshop_test_run', $run);
        self::$tracked[spl_object_id($product)] = [$product, $origin, $run];
        if (!self::$registered) { register_shutdown_function([self::class, 'cleanup']); self::$registered = true; }
    }

    public static function cleanup(): void
    {
        self::guard();
        foreach (self::$tracked as $key => [$source, $origin, $run]) {
            $id = $source->get_id();
            if (!$id) { unset(self::$tracked[$key]); continue; }
            $product = wc_get_product($id);
            if (!$product || $product->get_meta('_wooshop_test_origin', true) !== $origin || $product->get_meta('_wooshop_test_run', true) !== $run || $product->get_meta('_wooshop_fixture_key', true)) { throw new RuntimeException('Refusing cleanup of unverified or profile product ID '.$id); }
            $product->set_status('draft');
            $product->set_catalog_visibility('hidden');
            $product->update_meta_data('_wooshop_test_cleanup', gmdate('Y-m-d\TH:i:s\Z'));
            $product->save();
            unset(self::$tracked[$key]);
        }
    }

    private static function guard(): void
    {
        if (!defined('WOOSHOP_LOCAL_FIXTURES') || !WOOSHOP_LOCAL_FIXTURES || wp_get_environment_type() !== 'local') { throw new RuntimeException('Refusing test fixture cleanup outside isolated local development.'); }
    }
}
