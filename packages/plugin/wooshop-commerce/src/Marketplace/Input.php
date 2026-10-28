<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;
use WooShop\Commerce\Support\DomainError;

final class Input
{
    public static function fields(array $body, array $allowed, array $required = []): void
    {
        if (array_diff(array_keys($body), $allowed) || array_diff($required, array_keys($body))) { throw new DomainError('invalid_fields', 'Unknown or missing request fields.', 400); }
    }
    public static function text(mixed $value, int $max, bool $empty = false): string
    {
        if (!is_string($value) || preg_match('//u', $value) !== 1 || (!$empty && trim($value) === '') || preg_match_all('/./us', $value) > $max || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f<>]/', $value)) { throw new DomainError('invalid_text', 'Invalid bounded plain-text field.', 400); }
        return $value;
    }
    public static function nativeId(mixed $id): string
    {
        if (!is_string($id) || !preg_match('/^[1-9][0-9]{0,18}$/D', $id) || (strlen($id) === 19 && strcmp($id, (string) PHP_INT_MAX) > 0)) { throw new DomainError('invalid_native_id', 'A native decimal-string ID is required.', 400); }
        return $id;
    }
    public static function quantity(mixed $value): int
    {
        if (!is_int($value) || $value < 1 || $value > 10000) { throw new DomainError('invalid_quantity', 'Quantity must be an integer from 1 to 10000.', 400); }
        return $value;
    }
}
