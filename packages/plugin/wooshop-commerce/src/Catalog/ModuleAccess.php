<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Support\DomainError;

/** Lifecycle is checked at execution time, independently of owner authorization. */
final class ModuleAccess
{
    public static function accepts(string $module, string $action): bool
    {
        if (function_exists('wooshop_commerce_module_accepts')) { return wooshop_commerce_module_accepts($module, $action); }
        // Standalone domain arithmetic tests do not bootstrap WordPress. Production fails closed.
        return !defined('ABSPATH') && in_array($action, ['read', 'new', 'cleanup'], true);
    }

    public static function require(string $module, string $action): void
    {
        if (!self::accepts($module, $action)) {
            $message = $action === 'new' ? 'New activity for this feature is currently unavailable. Existing saved data is retained.' : 'This feature is currently unavailable. Existing saved data is retained.';
            if (function_exists('__')) { $message = $action === 'new' ? __('New activity for this feature is currently unavailable. Existing saved data is retained.', 'wooshop-commerce') : __('This feature is currently unavailable. Existing saved data is retained.', 'wooshop-commerce'); }
            throw new DomainError('module_unavailable', $message, 503, ['module_id' => $module]);
        }
    }

    public static function revision(string $module): int
    {
        if (class_exists(\WooShop\Commerce\Kernel\Plugin::class)) { return (int) (\WooShop\Commerce\Kernel\Plugin::instance()?->modules()->all()[$module]['version'] ?? 1); }
        return 1;
    }
}
