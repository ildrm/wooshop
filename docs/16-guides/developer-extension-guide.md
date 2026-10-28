# Developer extension guide

Document ID: DOC-S-GUIDE01 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: WordPress Plugin/PHP Developer | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Developer extension guide | Requirements: FR-S006; NFR-S007; NFR-S011
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Read before extension work

This guide describes future extension development; no packages or code were implemented. Start with [ownership](../03-architecture/ownership-and-module-boundaries.md), [capabilities](../04-data/product-types-and-capabilities.md), [module contracts](../07-plugin/extension-contracts-and-provider-adapters.md) and [API contract](../09-api-integrations/openapi.yaml). Preserve WordPress/WooCommerce native identities and CRUD/data-store access. Persistent data, permissions, money and lifecycle belong to the plugin; theme hooks can change presentation only.

FR-S006: A new module/provider adapter is admitted through a capability declaration and verified behavior, not a claim to support arbitrary product types. Supply identity mapping, required dependencies/version bounds, purchasability/pricing mode, quantity/stock authority, cart identity, fulfillment semantics, return/refund/renewal rules, authorization, data ownership and disable/uninstall obligations. Map native product/variation IDs plus site-local offer/seller UUIDs. A provider that stores a product per seller needs an explicit canonical-to-provider mapping with conflict handling; never silently merge listings by title.

## Proposed extension workflow

1. Add or trace a requirement and provisional ADR; identify existing native ownership before creating data.
2. Define versioned input/output schemas with errors, field allowlists, resource permissions and state transitions. Custom namespace is `/commerce/v1`; storefront cart/checkout uses documented Store API extension contracts and `extensions.commerce` data, not DOM hacks.
3. Declare product capability/mode combinations in the matrix. Unknown price stays nullable; external products retain external action; subscriptions/bookings require lifecycle-specific adapters rather than simple-product coercion.
4. Specify event producer/consumer, at-least-once delivery, dedup/business keys, retries, poison handling and reconciliation. Money uses integer minor-unit strings plus currency/exponent; immutable snapshots outlive adapters.
5. Build future synthetic fixtures, provider sandbox contract evidence and negative authorization/race tests. Do not mark compatibility from a vendor advertisement alone.
6. Provide accessible semantic fallback with an unrelated compatible theme, localization keys, settings schema/export rules, metrics and migration/exit guide.
7. Review with architecture, security, QA and the transactional owner; record exact tested versions before activation.

## Failure behavior and compatibility

A failed dependency check disables new affected operations and shows a diagnostic with remediation; existing obligations move to a drain/recovery path. Registration cannot override another module's stock decrement, coupon engine, review authority or schema renderer. Theme switching must not erase private data or run financial changes. Cache/search are projections; revalidate authority for every mutation. Jobs preserve actor/site/seller scope and recheck revoked memberships.

Future public-contract changes are additive within a version; breaking meaning requires a new major and migration window. Test minimum and target versions from the canonical baseline, HPOS, Cart/Checkout blocks, disabled module, provider outage and current/past snapshot reads. Evidence required is a reviewed contract plus executed future tests; this guide itself is not evidence that any adapter exists.
