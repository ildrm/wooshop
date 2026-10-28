<?php
declare(strict_types=1);

namespace WooShop\Commerce\Finance;

use WooShop\Commerce\Support\DomainError;

/** Exact signed minor units on the supported 64-bit PHP runtime. No float conversions. */
final class Money implements \JsonSerializable
{
    public const MAX = 9000000000000000;
    public readonly int $minor;
    public readonly string $currency;
    public readonly int $exponent;

    public function __construct(string $minor, string $currency, int $exponent)
    {
        if (PHP_INT_SIZE < 8 || !preg_match('/^-?(0|[1-9][0-9]{0,15})$/D', $minor)
            || !preg_match('/^[A-Z]{3}$/D', $currency) || $exponent < 0 || $exponent > 4
            || abs((int) $minor) > self::MAX) {
            throw new DomainError('invalid_money', 'Invalid exact money amount, currency or exponent.', 422);
        }
        $this->minor = (int) $minor;
        $this->currency = $currency;
        $this->exponent = $exponent;
    }

    public static function from(array $value): self
    {
        if (array_diff(array_keys($value), ['amount_minor', 'currency', 'exponent'])
            || !isset($value['amount_minor'], $value['currency'], $value['exponent'])
            || !is_string($value['amount_minor']) || !is_string($value['currency']) || !is_int($value['exponent'])) {
            throw new DomainError('invalid_money', 'Money requires amount_minor string, currency and exponent.', 400);
        }
        return new self($value['amount_minor'], $value['currency'], $value['exponent']);
    }

    public static function decimal(string $decimal, string $currency, int $exponent): self
    {
        if (!preg_match('/^(-?)([0-9]+)(?:\.([0-9]+))?$/D', $decimal, $m) || $exponent < 0 || $exponent > 4) {
            throw new DomainError('invalid_decimal', 'An exact decimal string is required.', 422);
        }
        $fraction = $m[3] ?? '';
        if (strlen($fraction) > $exponent && trim(substr($fraction, $exponent), '0') !== '') {
            throw new DomainError('rounding_required', 'Amount requires an explicit rounding policy.', 422);
        }
        $minor = ltrim($m[2].str_pad(substr($fraction, 0, $exponent), $exponent, '0'), '0') ?: '0';
        return new self(($m[1] === '-' && $minor !== '0' ? '-' : '').$minor, $currency, $exponent);
    }

    public function decimalString(): string
    {
        $s = str_pad((string) abs($this->minor), $this->exponent + 1, '0', STR_PAD_LEFT);
        return ($this->minor < 0 ? '-' : '').($this->exponent ? substr($s, 0, -$this->exponent).'.'.substr($s, -$this->exponent) : $s);
    }

    public function add(self $other): self
    {
        $this->sameUnit($other);
        return new self((string) ($this->minor + $other->minor), $this->currency, $this->exponent);
    }

    public function sameUnit(self $other): void
    {
        if ($this->currency !== $other->currency || $this->exponent !== $other->exponent) {
            throw new DomainError('currency_mismatch', 'Money units must match.', 422);
        }
    }

    public function jsonSerialize(): array
    {
        return ['amount_minor' => (string) $this->minor, 'currency' => $this->currency, 'exponent' => $this->exponent];
    }
}
