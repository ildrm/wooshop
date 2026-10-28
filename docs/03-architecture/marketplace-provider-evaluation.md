# Marketplace provider evaluation

Document ID: DOC-D-PROVIDERS | Title: Marketplace provider evaluation | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future implementation verification
Owner: Marketplace Solution Architect | Reviewers: WooCommerce Solution Specialist; QA/Test Engineer; Application Security Engineer
Scope: proposed system design only | Requirements: FR-D014; FR-D015 | ADRs: ADR-05; ADR-06; ADR-09
Dependencies: [requirements](../02-requirements/requirements.csv); [source register](../00-governance/sources.csv)
Assumptions: ASM-01 independent sites; ASM-02 jurisdiction/providers undecided; ASM-03 planning scenarios
Sources: USER-BRIEF sections 2–8; SRC-D001–SRC-D009

## Provisional recommendation

Build the bounded seller/offer/allocation/ledger domain in the companion plugin; integrate an approved payment/payout provider and named product-type extensions. Keep provider adapters for importing vendor-owned listing systems. This is the recommended design, conditional on DEC-05 budget and DEC-04 provider verification. It avoids making a provider's duplicate-product semantics the universal catalog model, but creates substantial engineering and operating responsibility. If funding fails, the business may choose vendor-owned listings through a provider and explicitly defer canonical shared-offer activation; it cannot quietly claim equivalent functionality.

## Evidence and limitations

Verified documentation: Product Vendors assigns products to vendor accounts, supports vendor teams and commissions, and documents PayPal Payouts prerequisites. These facts do not prove the requested shared canonical-offer or complete block/HPOS contract. [SRC-D005](https://woocommerce.com/document/product-vendors/).

Dokan's Single Product Multiple Vendor documentation says cloning creates a new product, rather than a live mirror. A mapping adapter is therefore needed for this project's canonical product. Its documentation update date is 2026-02-25. [SRC-D006](https://dokan.co/docs/wordpress/modules/single-product-multiple-vendor/).

No packages were installed or vendor functionality tested. A score of 0-U means unknown, not proof of absence. Scores are analytical planning judgments: 0 unknown/unmet, 1 substantial adaptation, 2 partial fit, 3 good proposed fit. Custom scores describe design fit, not implemented capability.

| Criterion | Weight | Custom bounded core | Dokan + adapter | Product Vendors + adapter |
|---|---:|---:|---:|---:|
| Canonical product/variation/offers | 5 | 3 | 1 | 0-U |
| Product-type adapter flexibility | 3 | 3 | 1 | 1 |
| Block checkout + HPOS assurance | 4 | 1 | 0-U | 0-U |
| Scoped organizations/permissions | 4 | 3 | 1 | 2 |
| Approved fund flow/refunds | 5 | 1 | 1 | 1 |
| Shipping/partial returns | 3 | 2 | 1 | 1 |
| Versioned APIs/migration | 3 | 3 | 1 | 1 |
| Cost/time to first vendor sales | 4 | 0 | 2 | 2 |
| Portability/exit control | 3 | 3 | 1 | 1 |
| Maintenance burden | 3 | 0 | 2 | 2 |
| Weighted total /111 | 37 | 69 | 40 | 39 |

Totals are weighted sums, not benchmarks. Unknown critical compatibility has veto power regardless of score. The custom option scores low on delivery cost and maintenance deliberately.

## Procurement and gate checklist

Before activating any marketplace provider, Marketplace Solution Architect owns proof of: exact release/license/support tier; clone/canonical mapping; same variation from two sellers; supported product combinations; object authorization; block checkout and HPOS; one stock decrement; coupon/shipping/refund allocations; webhook/transfer reversal; exported IDs/history; upgrade and exit path. QA supplies reproducible evidence; Finance/Tax and Legal/Compliance approve fund flow separately. Two failed critical adapter cases block activation rather than trigger hidden fallback to single-seller accounting.

Cost categories include extension subscriptions per installation, gateway/transfer/dispute fees, support contracts, security maintenance, custom adapter upkeep, onboarding review and reconciliation staffing. No prices or budget commitments are assumed. License terms and rights to redistribute dependency code remain DEC-12. The two custom products do not bundle commercial extensions by default.

Exit exports contain canonical IDs, provider mapping, seller organizations/memberships, immutable purchased snapshots, balances and unresolved obligations. Provider secrets and identity documents use separate protected migration. During exit, stop new payouts, reconcile, dual-read projections only, then switch authority once; never dual-write money or stock.
