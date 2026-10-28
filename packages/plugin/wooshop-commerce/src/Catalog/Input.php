<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Support\DomainError;

final class Input
{
    public static function keys(array $input, array $allowed, array $required = []): void
    {
        if (array_diff(array_keys($input), $allowed) || array_diff($required, array_keys($input))) {
            throw new DomainError('invalid_fields', 'Missing or unsupported fields.', 400);
        }
    }

    public static function id($value): string
    {
        if (!is_string($value) || !preg_match('/^[1-9][0-9]{0,18}$/D', $value) || strlen($value) > strlen((string) PHP_INT_MAX) || (strlen($value) === strlen((string) PHP_INT_MAX) && strcmp($value, (string) PHP_INT_MAX) > 0)) {
            throw new DomainError('invalid_id', 'A positive decimal product identifier is required.', 400);
        }
        return $value;
    }

    public static function uuid($value): string
    {
        if (!is_string($value) || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/Di', $value)) {
            throw new DomainError('invalid_reference', 'A valid resource reference is required.', 400);
        }
        return strtolower($value);
    }

    public static function stableId(string $scope): string
    {
        $hash = hash('sha256', $scope);
        return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-5' . substr($hash, 13, 3) . '-a' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
    }

    public static function text($value, int $min, int $max): string
    {
        if (!is_string($value) || !preg_match('//u', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            throw new DomainError('invalid_text', 'Valid plain text is required.', 422);
        }
        $value = trim($value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : preg_match_all('/./us', $value);
        if ($length < $min || $length > $max || strip_tags($value) !== $value) {
            throw new DomainError('invalid_text', 'Text is outside the permitted length or contains markup.', 422);
        }
        return $value;
    }

    public static function bool($value): bool
    {
        if (!is_bool($value)) {
            throw new DomainError('invalid_boolean', 'A boolean value is required.', 422);
        }
        return $value;
    }

    public static function positiveInt($value): int
    {
        if (!is_int($value) || $value < 1) {
            throw new DomainError('invalid_revision', 'A positive integer revision is required.', 422);
        }
        return $value;
    }
}
