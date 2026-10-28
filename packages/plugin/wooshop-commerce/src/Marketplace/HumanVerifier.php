<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

/** Fresh, purpose-bound human step-up. HTTP headers and WordPress nonces alone never satisfy this. */
interface HumanVerifier
{
    public function requireFresh(int $principal, string $purpose, string $resourceDigest): void;
}
