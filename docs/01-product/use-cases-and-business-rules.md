# Use cases and business rules

Document ID: DOC-G-USECASES | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Business Analyst | Reviewers: Business Analyst; Solution/Software Architect; QA/Test Engineer
Scope: Use cases and business rules | Requirements: FR-G005; FR-G006; FR-G007; FR-G008; FR-G010; FR-G011; FR-G020 | ADRs: ADR-03; ADR-14
Dependencies: [conventions](../00-governance/documentation-conventions.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Cross-cutting rules

FR-G005: the capability resolver decides purchasability, required selections, monetary display, shipping and fulfillment. Unknown provider capability fails closed for purchase. FR-G006: customer identity and seller organization identity differ; a user may belong to more than one organization but requests carry exactly one active scope. Cart lines include offer identity. FR-G007: every mutation uses the authority selected for that product/mode and a single stock-decrement owner. FR-G010: a provisional business/legal policy cannot activate payouts.

Commercial terms visible at purchase are stored with the item. Later edits to catalog, offer, commission, seller policy or template do not rewrite a historical purchase. A customer must explicitly accept changed seller/price/fulfillment terms; the platform never silently substitutes a preferred offer. Discounts allocate with deterministic rounding and platform/seller funding attribution. Product reviews and seller/service ratings have separate subjects. Promotions disclose sponsorship and actual end conditions.

## Use case records

| ID | Trigger/actors | Normal outcome | Alternate/error/authorization path | Acceptance links |
|---|---|---|---|---|
| UC-G001 | Guest selects native simple item | Native cart/checkout with authoritative price and inventory | Out-of-stock explains availability;invalid quantity rejected server-side | FR-G005;TEST-G005 |
| UC-G002 | Customer picks variation and seller | Distinct line stores canonical/variation/offer mapping | Missing variation or suspended seller blocks;no automatic substitution | FR-G006;TEST-G006 |
| UC-G003 | Customer returns paid item after payout | Refund and seller obligation recorded through immutable reversal | Insufficient seller balance creates negative obligation/hold;never duplicate refund | FR-G006;TEST-G006 plus finance tests |
| UC-G004 | Seller manager publishes offer | Organization-scoped offer passes eligibility and moderation | Attempted foreign seller ID denied;catalog ownership cannot be mass-assigned | FR-G008;TEST-G008 |
| UC-G005 | Merchant changes preset | Presentation changes while commerce data persists | Unsupported asset/control falls back;unauthorized template edit rejected | FR-G004;FR-G011;TEST-G011 |
| UC-G006 | Operator activates marketplace | Controlled first-party mapping and authority cutover | Unresolved providers/legal gate blocks activation;historical orders untouched | FR-G006;FR-G010;TEST-G010 |
| UC-G007 | User signs in with guest wishlist/history | Approved merge preserves explicit opt-outs and deduplicates identity | Expired/withdrawn data excluded;foreign guest token cannot attach | Relevant customer-module requirements in feature catalog |
| UC-G008 | Third website adopts packages | New profile/modules/preset use same contracts | New custom type browse-only until adapter passes | FR-G020;TEST-G020 |

## Conflicting inputs and race decisions

A cache/search projection can suggest a result but cannot authorize a transaction. Server reads validate the selected variation/offer at cart update and checkout. A version mismatch returns a retriable conflict with the current safe representation. Retrying a payment/payout/refund request uses the same idempotency identity; changed business intent receives a new one only after explicit reconciliation of the previous outcome. Out-of-order callbacks do not regress a settled terminal state.

Catalog conflict resolution preserves provenance: sellers propose corrections; the catalog authority reviews evidence and merges or rejects without overwriting other sellers' commercial terms. No seller can declare their own unverified product claim certified through a visual badge. Unknown compatibility results display the evidence gap and an actionable support path.

## Detailed authority

These are product-level refinements of the linked requirements, not a second financial rulebook. Exact transitions, money calculations and idempotency contracts live in [data/finance](../04-data/orders-financial-records-and-invariants.md), [marketplace](../08-marketplace/cart-checkout-and-order-allocation.md), [APIs](../09-api-integrations/api-and-authentication-strategy.md) and named module specifications.
