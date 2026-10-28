<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

/** Read the kernel's validated, site-local settings; never trust request options. */
final class Options
{
    public static function get(string $key, $fallback)
    {
        return function_exists('wooshop_commerce_setting') ? wooshop_commerce_setting($key, $fallback) : $fallback;
    }
    public static function integer(string $key, int $fallback, int $minimum, int $maximum): int
    {
        $value = self::get($key, $fallback);
        return is_int($value) ? max($minimum, min($maximum, $value)) : $fallback;
    }
}
