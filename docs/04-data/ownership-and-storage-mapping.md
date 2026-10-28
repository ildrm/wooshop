# Ownership and storage mapping

Document ID: DOC-D-STORAGE | Title: Ownership and storage mapping | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D005; FR-D008; FR-D032–FR-D034 | ADRs: ADR-03; ADR-07; ADR-10
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Logical to proposed physical mapping

| Logical aggregate | Proposed storage/access | Index/constraint intent | Restore/rebuild rule |
|---|---|---|---|
| Native products/variations/reviews/tax/coupons | WooCommerce CRUD and native stores | Native indexes; typed specification projection only where necessary | Native backup authoritative |
| Native users/content/media/global styles | WordPress APIs | Native identity and capabilities | Preserve attachment/stylesheet references |
| Customer orders/refunds/item metadata | WooCommerce order CRUD/data store; HPOS target | Supported query interface; order operation reference indexed through supported extension approach | Restore plus payment-provider reconciliation before writes |
| Seller/membership/profile | Consolidated plugin seller aggregate | Unique seller/user membership and seller slug; state lookup | Restricted source data, not a search projection |
| Offers/pools/reservations | Plugin transactional aggregate | Unique seller SKU; offer product/variation/state; pool version; reservation operation/line; due expiry | Recovery replays journal; do not infer stock from cached cards |
| Allocations/returns/shipment relations | Plugin transaction aggregate; provider mapping if delegated | Order/seller and item/quantity constraints | Reconcile WC item totals and provider status |
| Ledger/journal | Plugin append-only finance aggregate or verified provider-ledger mapping | Unique event and command keys; journal/line; currency/account; seller statement index | Immutable audit export + balanced reconciliation |
| Operations/outbox/inbox/jobs | Durable plugin operational aggregate | Unique delivery/consumer; state/due_at; resource/version | Retry safely with deduplication; poison queue retained |
| Specs/compatibility/campaign config | Versioned plugin metadata/records | Schema key/version; subject/target/region; review_due | Rebuild facet projection from source |
| Search/cache/reporting | Rebuildable indexes | Site/locale/currency/region and visibility dimension | Never restored as transaction authority |

These are design groupings, not SQL DDL or final table names. Database/Data Architect selects actual indexes after query plans under approved capacity scenarios. Native order data is not mirrored into plugin tables merely to simplify reporting; only allocation/snapshot extensions are stored there.

## Transaction boundaries

Within plugin-owned transactional aggregates, inventory reservation and operation deduplication share one database transaction or equivalent atomic compare-and-swap. WooCommerce order persistence and external provider effects cannot be assumed atomic with that transaction. A durable operation journal records stages and stable references; recovery is an explicit state machine. The event outbox is committed with the owning plugin mutation when feasible; otherwise reconciliation observes missing events through source versions and repairs projections.

No cross-database transaction spans the two stores. No distributed “exactly once” claim is made. At-least-once delivery is combined with unique business operation keys and idempotent effects. An external timeout can leave an unknown result; workers query by provider idempotency/reference before retrying a money action.

## Required invariants and access

FR-D032 requires data constraints to reject duplicate seller SKU, mismatched variation parent, out-of-site references and unbalanced journal transactions. FR-D033 requires listed high-use query plans to meet approved latency at the selected scenario. FR-D034 requires rebuilding a fresh search/report index from the authoritative data to produce equal published counts and money totals, excluding intentionally delayed windows. Access to finance and verification partitions uses capability and resource scope even for reports and jobs.

## HPOS policy

The project target is HPOS with order CRUD access; legacy order storage is a migration/testing compatibility path only when an explicit adapter matrix row authorizes it. An extension that requires direct order-post-table behavior is not supported by default. [SRC-D001](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/) is external guidance, not evidence that the proposed plugin has passed HPOS tests.
