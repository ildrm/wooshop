<?php
/**
 * Plugin Name: Wooshop Commerce
 * Description: Modular catalog, customer and gated marketplace services for independent WooCommerce stores.
 * Version: 0.1.0
 * Requires at least: 7.1.3
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * WC requires at least: 11.2.1
 * WC tested up to: 11.2.1
 * Text Domain: wooshop-commerce
 * License: GPL-2.0-or-later
 */
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';

register_activation_hook(__FILE__, static function (): void {
    global $wpdb;
    \WooShop\Commerce\Infrastructure\Schema::install(new \WooShop\Commerce\Infrastructure\Database($wpdb));
    update_option('wooshop_commerce_operational_mode', 'normal', false);
    $role = get_role('administrator');
    foreach (['commerce.settings.manage', 'commerce.diagnostics.read', 'commerce.catalog.publish', 'commerce.modules.manage', 'commerce.jobs.manage', 'commerce.export.own', 'commerce.import.manage'] as $capability) {
        $role?->add_cap($capability);
    }
});
register_deactivation_hook(__FILE__, static function (): void {
    // Data and financial obligations survive. An absent plugin cannot execute its own workers.
    update_option('wooshop_commerce_operational_mode', 'service_only', false);
    wp_clear_scheduled_hook('wooshop_commerce_customer_cleanup');
    // Retain scheduled identities and durable jobs for restoration/successor handoff.
    // A fully deactivated plugin cannot service them; the operator must restore or transfer obligations.
});
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});
add_action('plugins_loaded', static function (): void {
    \WooShop\Commerce\Kernel\Plugin::boot();
}, 30);

function wooshop_commerce_setting(string $key, mixed $fallback = null): mixed
{
    return \WooShop\Commerce\Kernel\Plugin::instance()?->settings()->get($key, $fallback) ?? $fallback;
}
function wooshop_commerce_module_enabled(string $id): bool
{
    return \WooShop\Commerce\Kernel\Plugin::instance()?->modules()->enabled($id) ?? false;
}
function wooshop_commerce_module_accepts(string $id, string $action): bool
{
    return \WooShop\Commerce\Kernel\Plugin::instance()?->modules()->accepts($id, $action) ?? false;
}
