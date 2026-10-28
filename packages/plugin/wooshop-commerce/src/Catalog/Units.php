<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Support\DomainError;

/** Exact decimal fixed point conversion for measurements with six decimal places. */
final class Units
{
    private const DEFINITIONS = [
        'g' => ['mass', 1, 1], 'kg' => ['mass', 1000, 1], 'lb' => ['mass', 45359237, 100000],
        'mm' => ['length', 1, 1], 'cm' => ['length', 10, 1], 'm' => ['length', 1000, 1],
        'mL' => ['volume', 1, 1], 'L' => ['volume', 1000, 1],
        'V' => ['voltage', 1, 1], 'W' => ['power', 1, 1], 'A' => ['current', 1, 1],
        'Hz' => ['frequency', 1, 1], 'C' => ['temperature', 1, 1], '%' => ['ratio', 1, 1],
    ];

    public static function decimal($value): int
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new DomainError('invalid_decimal', 'A finite decimal measurement is required.', 422);
        }
        $text = (string) $value;
        if (!preg_match('/^(-?)([0-9]{1,6})(?:\.([0-9]{1,6}))?$/D', $text, $parts)) {
            throw new DomainError('invalid_decimal', 'Measurement allows six integer and six fractional digits.', 422);
        }
        $scaled = (int) $parts[2] * 1000000 + (int) str_pad($parts[3] ?? '', 6, '0');
        return ($parts[1] === '-' ? -1 : 1) * $scaled;
    }

    public static function format(int $scaled): string
    {
        $sign = $scaled < 0 ? '-' : '';
        $scaled = abs($scaled);
        $fraction = rtrim(str_pad((string) ($scaled % 1000000), 6, '0', STR_PAD_LEFT), '0');
        return $sign . intdiv($scaled, 1000000) . ($fraction === '' ? '' : '.' . $fraction);
    }

    public static function convert($value, string $from, string $to): string
    {
        $a = self::DEFINITIONS[$from] ?? null;
        $b = self::DEFINITIONS[$to] ?? null;
        if (!$a || !$b || $a[0] !== $b[0]) {
            throw new DomainError('incompatible_unit', 'Measurement units must share a dimension.', 422);
        }
        $number = self::decimal($value);
        // Decimal digits and conversion magnitude are bounded before multiplication.
        $numerator = $a[1] * $b[2];
        $denominator = $a[2] * $b[1];
        $gcd = self::gcd($numerator, $denominator);
        $numerator = intdiv($numerator, $gcd);
        $denominator = intdiv($denominator, $gcd);
        if (abs($number) > intdiv(PHP_INT_MAX, max(1, $numerator))) {
            throw new DomainError('measurement_overflow', 'Measurement exceeds the safe conversion range.', 422);
        }
        $product = $number * $numerator;
        if ($product % $denominator !== 0) {
            throw new DomainError('measurement_precision', 'Conversion exceeds six decimal places; supply an exact supported measurement.', 422);
        }
        return self::format(intdiv($product, $denominator));
    }

    public static function dimension(string $unit): string
    {
        if (!isset(self::DEFINITIONS[$unit])) {
            throw new DomainError('unknown_unit', 'Unsupported measurement unit.', 422);
        }
        return self::DEFINITIONS[$unit][0];
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) { [$a, $b] = [$b, $a % $b]; }
        return $a;
    }
}
