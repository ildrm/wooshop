<?php
declare(strict_types=1);
namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Support\DomainError;

/** Configuration flags cannot manufacture provider, legal or dependency-loss verification. */
final class ActivationGate
{
    public function reasons(): array
    {
        return [
            'legal_funds_model_unapproved' => 'Legal entity, merchant role, tax and funds model require qualified approval (DEC-01–04).',
            'payment_provider_unconfigured' => 'No approved payment, refund or payout adapter has been configured and verified.',
            'independent_maintenance_unverified' => 'An independent dependency-loss maintenance guard has not been verified.',
            'stock_authority_cutover_unverified' => 'Pinned WooCommerce stock suppression, legacy restock and forced dependency loss require cutover verification.',
        ];
    }

    public function requireMarketplace(): void
    {
        throw new DomainError('marketplace_activation_blocked', 'Marketplace transactions remain disabled pending required approvals and verification.', 503, ['gates' => $this->reasons()]);
    }
}
