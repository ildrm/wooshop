<?php
declare(strict_types=1);
namespace WooShop\Commerce\Finance;
use WooShop\Commerce\Support\DomainError;

/** An explicit immutable policy value. No illustrative default is installed as a commercial rule. */
final class CommissionPolicy
{
    public function __construct(public readonly string $id, public readonly int $version, public readonly int $basisPoints, public readonly Money $fixedFee, public readonly string $approvalReference)
    {
        if (!$id || $version < 1 || $basisPoints < 0 || $basisPoints > 10000 || $fixedFee->minor < 0 || !$approvalReference) { throw new DomainError('invalid_commission_policy', 'Commission requires an explicit versioned policy and approval reference.', 422); }
    }

    public function calculate(Money $discountedGoodsNet): Money
    {
        $discountedGoodsNet->sameUnit($this->fixedFee);
        if ($discountedGoodsNet->minor < 0) { throw new DomainError('invalid_commission_basis', 'Commission basis must be nonnegative.', 422); }
        [$minor, $remainder] = Allocation::multiplyDivide($discountedGoodsNet->minor, $this->basisPoints, 10000);
        // Explicit half-up rounding at the policy boundary. Stored value is frozen at purchase.
        if ($remainder >= 5000) { ++$minor; }
        $minor += $this->fixedFee->minor;
        if ($minor > $discountedGoodsNet->minor) { throw new DomainError('commission_exceeds_goods', 'Commission cannot exceed its approved goods basis.', 422); }
        return new Money((string) $minor, $discountedGoodsNet->currency, $discountedGoodsNet->exponent);
    }

    public function snapshot(): array
    {
        return ['id' => $this->id, 'version' => $this->version, 'basis' => 'discounted_goods_net', 'rate_basis_points' => $this->basisPoints, 'fixed_fee' => $this->fixedFee->jsonSerialize(), 'rounding' => 'half_up', 'approval_ref' => $this->approvalReference];
    }
}
