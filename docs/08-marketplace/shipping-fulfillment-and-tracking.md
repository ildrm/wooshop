# Shipping, fulfillment and tracking

Document ID: DOC-D-SHIPPING | Title: Shipping, fulfillment and tracking | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D026; FR-D047; FR-D067; FR-D068 | ADRs: ADR-04; ADR-08; ADR-09
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Shipping model

Shipping profiles identify seller, fulfillment origin, eligible destinations, product constraints, service levels, handling time and return address policy. Exact carriers, country coverage and insurance are DEC-04 decisions. The proposed quote adapter receives only required package dimensions/weight, destination and service constraints. It returns rate ID/version, price/currency, tax treatment reference, expiry and estimated delivery window. The checkout never trusts a browser-submitted rate amount.

Packages group compatible items by seller/origin/service and provider constraints. A two-seller cart may have two shipping charges and deliveries. Virtual/download-only lines do not create shipping packages; services use appointments/milestones. Dangerous/restricted goods and electrical/safety restrictions require reviewed policy before publication. Storefront delivery messages distinguish estimate, dispatch commitment and carrier guarantee; absent destination data cannot produce a false guaranteed date.

## Fulfillment workflow

Allocation accepted → picking → packed → dispatched → delivered, with blocked/cancelled exceptions. Only seller fulfillment members or delegated platform operators update assigned allocations. Shipment line quantities cannot exceed remaining unshipped purchased quantity. Partial shipment records keep outstanding quantities visible. Tracking events are authenticated or pulled from an approved carrier adapter and matched to shipment/site/provider; arbitrary customer tracking input cannot mark an order delivered.

Proof-of-delivery evidence is restricted and retained by policy. A tracking link must use an allowed HTTPS carrier domain and escape its identifier. Customer views display the seller, items, shipment status and support action; no other seller's customer contacts or packages appear. Seller exports include only the address/contact fields needed for assigned fulfillment.

## Exceptions and returns

A carrier outage leaves rates unavailable or a clearly approved static fallback; it never silently prices shipping at zero. Unknown delivery status remains unknown and queues reconciliation. Lost/damaged shipments open a support/return case with seller/platform responsibility under approved terms. Address changes after payment require fraud-aware review, updated rate/tax if necessary and customer acceptance; they do not mutate the immutable original quote silently. Cancellation attempts lock unshipped quantities and race safely with dispatch; already shipped items enter return flow.

For bundles, packaging and component shipping flags come from the named adapter. A component return uses original shipping refund policy and allocated amounts; it does not refund the same package twice. FR-D067 tests split shipments, quantity overrun denial, virtual-only cart and carrier timeout. FR-D068 tests unauthorized seller tracking updates, duplicate carrier callbacks, cancellation-versus-dispatch and customer visibility of independent shipment states.
