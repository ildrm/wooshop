# Product types and capabilities

Document ID: DOC-D-TYPES | Title: Product types and capabilities | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D004; FR-D016; FR-D017; FR-D044–FR-D048 | ADRs: ADR-04; ADR-07; ADR-08
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Descriptor contract

A descriptor exposes `adapter_key`, `adapter_version`, `native_type`, `purchase_flow` (direct/select_variation/select_children/configure/redirect/schedule/unsupported), `price_shape` (scalar/range/configuration/recurring/none), `requires_shipping`, `download_entitlement`, `stock_mode`, `child_graph`, `supports_partial_return`, `supports_renewal`, `supports_reschedule`, and a supported mode/provider/version matrix. Runtime availability also intersects seller eligibility, destination, evidence and active dependencies. A type label alone does not grant a capability.

The [capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) is the authoritative support boundary. “Planned” means specified and awaiting implementation tests; it never means currently available. Adapter gate DEC-09 requires minimum/target extension versions and vendor support evidence before activation.

## Type-specific contracts

| Type/trait | Purchase and price behavior | Stock/fulfillment and returns | Safe unsupported state |
|---|---|---|---|
| Simple | Explicit current offer/scalar quote | One authoritative pool; physical or virtual | Readable product with reason purchase unavailable |
| Variable | Exact variation required; range shown until selected | Parent stock only if descriptor explicitly maps all selected variants to same pool; otherwise variation pool | Invalid/missing/out-of-stock variation cannot add |
| Grouped | Parent is navigation; selected children each validated | Independent child lines and sellers; no fictitious parent price or stock | Unsupported child is labeled and excluded with explicit consent |
| External/affiliate | Clearly labeled external destination; no local add-to-cart | No local reservation/order/payout; external terms disclosed | Unsafe/unapproved URL hidden; no local checkout fallback |
| Virtual | Shipping not requested for virtual-only cart | May require service fulfillment evidence; tax still server-calculated | Physical shipping fields not fabricated |
| Downloadable | Native entitlement after permitted order/payment state | Customer-scoped expiring access; refunds follow approved policy | Public media URL never substitutes protected download |
| Bundle | Configuration graph and sum-preserving allocated price | One seller per bundle for initial supported marketplace adapter; component stock/refund mapping | Cross-seller bundle purchase blocked until new adapter gate |
| Composite | Valid required components before quote | Component stock and declared parent packaging/shipping model | Incompatible/unknown component prevents completion |
| Subscription | Signup and recurring amount/interval stated separately | Each renewal is own order/effect; cancellation leaves earned obligations; payment retries bounded | Multi-seller recurring contracts disabled until renewal/payout/refund support proven |
| Booking | Resource/interval/person/timezone selection; quote can be provisional until confirmation | Provider capacity reservation, cancellation/reschedule rules and price deltas; not integer goods stock | Multi-seller booking/resource combinations blocked pending provider tests |
| Unscheduled service | Explicit virtual simple service with scope and fulfillment SLA | Fulfillment milestone rather than shipment; refund policy and qualification gate | Scheduling need redirects to booking flow, never a free-text hidden reservation |
| Other custom type | Provider-specific descriptor and UI contract | Explicit allocation/cancellation/financial semantics | Read-only detail and diagnostic reason; no generic scalar coercion |

## Named candidate adapters and evidence

`wc-native-v1`, `wc-product-bundles-v1`, `wc-composite-products-v1`, `wc-subscriptions-v1` and `wc-bookings-v1` are proposed project adapter names. The official Bundles overview describes grouping products; Composite Products describes configurable inventory-managed components; Bookings describes time-based services. Subscriptions documents gateway capability flags. Those facts justify candidate evaluation only; no cross-extension block, HPOS or marketplace compatibility is assumed. [SRC-D007](https://woocommerce.com/document/bundles/), [SRC-D008](https://woocommerce.com/document/composite-products/), [SRC-D009](https://woocommerce.com/document/subscriptions/develop/payment-gateway-integration/), [SRC-D010](https://woocommerce.com/document/introduction-to-woocommerce-bookings/).

## Lifecycle details

Renewal commission uses the contract's disclosed policy or an explicitly consented new contract version, not today's unrelated checkout promotion. Suspension stops new subscriptions; active subscription obligations are handled under the approved seller-transition policy and provider capability, with customer notice and no silent seller substitution. Booking reschedule rechecks availability atomically before releasing the old slot and records any approved charge/refund delta. Component returns use original allocated amounts; they do not recalculate the original bundle promotion to over-refund. All these financial effects use the same ledger and deduplication invariants.
