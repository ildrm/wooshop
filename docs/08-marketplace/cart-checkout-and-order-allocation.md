# Cart, checkout and order allocation

Document ID: DOC-D-CHECKOUT | Title: Cart, checkout and order allocation | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D023–FR-D027; FR-D052; FR-D066 | ADRs: ADR-04; ADR-07; ADR-08; ADR-09
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Cart-line identity

The logical key is site + product + exact variation + offer + configuration digest + fulfillment/service selection + applicable recurring/booking contract identity. Quantity updates merge only identical keys. The same variation from seller A and seller B produces two lines, each with its own terms, inventory and shipping grouping. A product ID alone is never sufficient. Guest session ownership and authenticated customer ownership remain enforced by the native cart/session boundary.

Cart/Checkout blocks consume namespaced `extensions.commerce` fields from supported Store API extension mechanisms. Additional fields are offer_id, offer_version, configuration_digest and a server-issued accepted_quote_id/digest. Client money values are ignored. Custom administrative APIs do not substitute for session checkout. [Store API strategy](../09-api-integrations/api-and-authentication-strategy.md) defines the boundary.

## Validation checkpoints

1. Add-to-cart: validate product capability, exact variation/parent, seller eligibility, offer ownership, quantity and current terms. Return a current quote or precise unavailable state.
2. Cart update: repeat checks, recalculate native tax/coupons/shipping, and flag changed quote versions. Preserve unrelated valid lines; do not auto-remove a paid obligation.
3. Checkout: bind destination/currency/terms to accepted quote, acquire all required inventory or booking reservations, create pending order and purchased snapshots, then request provider payment.
4. Payment result: verify signature/provider truth; deduplicate; commit inventory once; post allocations and balanced sale journal; update WooCommerce payment state and notifications. Unknown results stay pending with reconciliation.

## Allocation algorithm contract

Each purchased item/component quantity is assigned exactly once to an allocation for seller + origin + fulfillment type. Bundle parents may be display-only; their amounts are distributed across components under the adapter's original allocation plan. Grouped selections are separate purchased children. Shipping packages group only compatible origins/services, not merely sellers. Server totals reconcile: sum allocation goods discounts/taxes/shipping/fees equals customer order totals, with explicit platform-funded items where applicable.

Order-wide coupons are evaluated once by the native coupon owner. Eligible seller/line shares use the fixed allocation rule in [financial invariants](../04-data/orders-financial-records-and-invariants.md). A seller-funded coupon cannot reduce another seller's payable. Platform-funded promotions post a platform expense and preserve seller entitlement. A repeated checkout operation returns its existing pending/result order; it cannot create a second charge.

## Customer experience and failure paths

Cart groups lines by seller and shows independent shipping charges/windows. Checkout explains one customer payment and separate fulfillment, subject to approved legal model. Payment error retains order reference and permits safe recovery without repeating a captured charge. If one seller becomes suspended before final charge, checkout stops for affected lines and asks for revision. After purchase, suspension does not erase the allocation; operators retain refund/support/fulfillment duties.

If a provider cannot transact the mixed cart legally or technically, the combination is blocked before payment with an explanation; no undisclosed split charge is invented. FR-D066 covers allocation rounding, gateway failure after order persistence, reload/retry, partial package failure and seller suspension timing. Expected evidence is one customer charge, one stock effect per purchased unit, balanced allocations and no duplicate purchase event.
