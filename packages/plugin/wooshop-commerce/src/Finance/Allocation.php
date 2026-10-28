<?php
declare(strict_types=1);

namespace WooShop\Commerce\Finance;

use WooShop\Commerce\Support\DomainError;

final class Allocation
{
    /** Stable largest-remainder allocation. Keys are immutable order-item keys, not display order. */
    public static function discount(Money $discount, array $eligible): array
    {
        if ($discount->minor < 0 || !$eligible) {
            throw new DomainError('invalid_discount', 'Discount must be nonnegative with eligible lines.', 422);
        }
        $sum = 0;
        foreach ($eligible as $key => $money) {
            if (!$money instanceof Money || $money->minor < 0) {
                throw new DomainError('invalid_allocation', 'Eligible amounts must be nonnegative money.', 422);
            }
            $discount->sameUnit($money);
            $sum += $money->minor;
            if ($sum > Money::MAX) {
                throw new DomainError('amount_overflow', 'Eligible total exceeds supported monetary range.', 422);
            }
        }
        if ($discount->minor > $sum) {
            throw new DomainError('discount_exceeds_goods', 'Discount cannot exceed eligible value.', 422);
        }
        $shares = $remainders = [];
        $allocated = 0;
        foreach ($eligible as $key => $money) {
            [$q, $r] = $sum === 0 ? [0, 0] : self::multiplyDivide($discount->minor, $money->minor, $sum);
            $shares[$key] = $q;
            $remainders[$key] = $r;
            $allocated += $q;
        }
        $keys = array_keys($shares);
        usort($keys, static fn ($a, $b): int => $remainders[$b] <=> $remainders[$a] ?: strcmp((string) $a, (string) $b));
        $remaining = $discount->minor - $allocated;
        foreach ($keys as $key) {
            if ($remaining-- <= 0) {
                break;
            }
            ++$shares[$key];
        }
        return array_map(static fn (int $n): Money => new Money((string) $n, $discount->currency, $discount->exponent), $shares);
    }

    /** Decimal long multiplication/division avoids the overflow in amount*weight. */
    public static function multiplyDivide(int $a, int $b, int $divisor): array
    {
        if ($a < 0 || $b < 0 || $divisor < 1 || $a > Money::MAX || $b > Money::MAX || $divisor > Money::MAX) {
            throw new DomainError('invalid_ratio', 'Ratio operands exceed supported range.', 422);
        }
        $digits = array_fill(0, strlen((string) $a) + strlen((string) $b), 0);
        $as = (string) $a;
        $bs = (string) $b;
        for ($i = strlen($as) - 1; $i >= 0; --$i) {
            for ($j = strlen($bs) - 1; $j >= 0; --$j) {
                $digits[$i + $j + 1] += (int) $as[$i] * (int) $bs[$j];
            }
        }
        for ($i = count($digits) - 1; $i > 0; --$i) {
            $digits[$i - 1] += intdiv($digits[$i], 10);
            $digits[$i] %= 10;
        }
        $remainder = 0;
        $quotient = '';
        foreach ($digits as $digit) {
            $remainder = $remainder * 10 + $digit; // at most 10*MAX, within int64.
            $quotient .= (string) intdiv($remainder, $divisor);
            $remainder %= $divisor;
        }
        $q = ltrim($quotient, '0') ?: '0';
        if (strlen($q) > 16 || (strlen($q) === 16 && strcmp($q, (string) Money::MAX) > 0)) {
            throw new DomainError('amount_overflow', 'Ratio result exceeds monetary range.', 422);
        }
        return [(int) $q, $remainder];
    }
}
