# Architecture overview

Document ID: DOC-D-ARCH-OVERVIEW | Title: Architecture overview | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future implementation verification
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; QA/Test Engineer; Application Security Engineer
Scope: proposed system design only | Requirements: FR-D001–FR-D007 | ADRs: ADR-01–ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [source register](../00-governance/sources.csv)
Assumptions: ASM-01 independent sites; ASM-02 jurisdiction/providers undecided; ASM-03 planning scenarios
Sources: USER-BRIEF sections 2–8; SRC-D001–SRC-D009

## Proposed architecture and scope

One released block theme supplies presentation; one companion plugin supplies explicitly enabled modules. Each store has an independent WordPress/WooCommerce installation, database, media namespace, secrets, users and order sequence. A third website consumes the same packages with another profile. Neither the theme nor a profile contains merchant-specific business branches. The [version baseline](../00-governance/version-baseline.csv) is a proposed support contract, not tested compatibility.

WordPress remains the source for users, content, media and editor state. WooCommerce owns native products, variations, customer orders, tax/coupon calculations and supported checkout operations. The plugin extends these objects through supported APIs and owns seller organizations, canonical-to-offer mapping, offer inventory in marketplace mode, allocations and the accounting subledger. No implementation is present here.

## Transaction path

A customer selects a native variation and an eligible seller offer. The cart retains both identities and an immutable configuration digest. A server quote validates seller status, destination, quantity, terms and offer version. Checkout reserves the authoritative inventory, saves a WooCommerce order plus plugin snapshots, then requests payment. Durable journal entries and reconciliation bridge the non-atomic boundary between WooCommerce and an external payment service. Payment success cannot by itself prove allocation, shipping or payout success.

A native single-merchant product uses WooCommerce price/stock through the native adapter. A migrated marketplace product uses plugin offer price/stock through the marketplace adapter; native product/variation shells persist zero managed stock with backorders disabled to fail closed when the adapter is absent. An authority-mode flag and migration epoch prohibit dual writers. The plugin's marketplace adapter prevents ordinary WooCommerce or provider stock reduction for those offer lines; this interception is a release-blocking integration test, not an assumed hook guarantee. See [inventory](../04-data/offers-inventory-and-reservations.md).

## Components and extension policy

| Boundary | Responsibility | Failure behavior |
|---|---|---|
| Theme | Accessible templates, patterns, presets, responsive layout | Native WooCommerce views remain with plugin absent |
| Plugin kernel | Module registry, capabilities, configuration versions, diagnostics | Dependency failure disables new affected actions with an explicit reason |
| Catalog adapters | Capability descriptors, specifications, product relations | Unsupported type remains readable; purchase is blocked rather than coerced |
| Commerce coordinator | Quote, reservation, snapshot, payment/stock deduplication | Pending operation is recoverable by journal and reconciliation |
| Marketplace | Seller membership, offers, allocations, financial records | Suspension stops new sales while existing obligations continue |
| Provider adapters | Narrow translation of one documented provider/version contract | Timeout/circuit breaker and safe queue; no false success |
| Read projections | Search, public offer summaries, reports | Stale reads never authorize checkout or sensitive access |

The companion plugin has one deployment unit, with in-process typed contracts and a durable asynchronous job layer. External search is optional after measured thresholds; a microservice per entity is rejected. Financial data has append-only application semantics and auditable corrections. Physical storage is consolidated by access and transaction needs, not by conceptual entity count.

## Foundational acceptance gates

FR-D001–FR-D007 require: ordinary native purchase with theme alone; plugin workflows under an unrelated compatible theme; independent-site denial; no direct order-post-table reads; named product adapter rejection; and recovery after a payment/save interruption. Future evidence consists of contract fixtures, authorization traces, stock deltas and balanced ledger totals. Design review or parsed documentation does not establish any of those outcomes.

## Reading path

[Ownership](ownership-and-module-boundaries.md) → [views](system-views-and-data-flows.md) → [domain](../04-data/domain-model-and-erd.md) → [marketplace checkout](../08-marketplace/cart-checkout-and-order-allocation.md) → [API](../09-api-integrations/api-and-authentication-strategy.md). Architecture decisions are indexed in [ADRs](adr/README.md).
