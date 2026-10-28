# Ownership and module boundaries

Document ID: DOC-D-OWNERSHIP | Title: Ownership and module boundaries | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future implementation verification
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; QA/Test Engineer; Application Security Engineer
Scope: proposed system design only | Requirements: FR-D002; FR-D003; FR-D005; FR-D006; FR-D008 | ADRs: ADR-02; ADR-03; ADR-07; ADR-08; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [source register](../00-governance/sources.csv)
Assumptions: ASM-01 independent sites; ASM-02 jurisdiction/providers undecided; ASM-03 planning scenarios
Sources: USER-BRIEF sections 2–8; SRC-D001–SRC-D009

## Authority matrix

| Fact or operation | Authoritative writer | Other copies | Constraint |
|---|---|---|---|
| User identity, password/session | WordPress identity subsystem | Seller membership references user ID | Seller organization is separate from user |
| Product/variation identity and core content | WooCommerce CRUD + catalog editor workflow | Search/feed/card projections | Seller offer cannot overwrite canonical technical claims |
| Editorial page/media | WordPress | CDN thumbnails | Asset access policy persists outside theme |
| Native-mode price and stock | WooCommerce adapter | First-party offer facade | Facade has no writable duplicate balance |
| Marketplace offer price/terms/stock | Plugin offer/inventory service | WC display projection and search summaries | Never also decrement product stock |
| Quote/tax/coupon/shipping totals | WooCommerce calculation pipeline + verified offer integration | Cart UI | Client values ignored; versioned quote binds acceptance |
| Customer order/refund object | WooCommerce CRUD/data store | Plugin allocation references | HPOS is target; no post-table coupling |
| Seller allocation/shipment | Plugin allocation service or one selected provider adapter | Seller portal, WC order notes | A provider suborder is a projection unless explicitly delegated |
| Gateway authorization/capture/refund/transfer | Approved payment provider | Local verified event journal | Local status changes never assert external money movement |
| Commission policy and subledger | Plugin finance service | Statements/reports | Versioned policy; reversal rather than mutation |
| Payout settlement evidence | Provider event/API reconciled by finance service | Seller balance view | No spendable wallet or unapproved custody |
| SEO decision | Plugin SEO coordination module | One configured renderer | Theme emits visible semantics, no competing graph |
| Consent/preferences | Plugin preference module for optional features; native account identity stays WordPress | Job eligibility cache | Delivery checks current suppression before send |

FR-D008 requires every adapter installation to declare field and operation ownership. Enabling two writers for stock, commission, tax, coupons, review content, metadata or schema is invalid configuration. A module's read access does not grant write authority.

## Dependency and lifecycle rules

Modules call public domain contracts, never another module's storage. Optional consumers subscribe to versioned events and tolerate duplicates. Catalog/specification modules may exist without marketplace. Marketplace sales depend on seller membership, offers, reservation coordinator, allocation, finance journal, notifications and reconciliation; activation fails if any mandatory dependency is absent. Provider capabilities are intersected with product capabilities and site policy.

Deactivation has two stages: stop new operations, then drain obligations. Financial, refund, subscription and fulfillment records remain accessible to authorized operators through a minimal operational interface. Active reservations expire or are explicitly released; queued mandatory jobs remain scheduled under the kernel. Uninstall refuses destructive purge while obligations, legal holds or unread export acknowledgements exist. Switching a theme cannot change permissions, balances, routes or data retention.

## Order-access evidence

Verified external fact: WooCommerce's HPOS guidance requires WooCommerce-specific order APIs; direct post-table access can read stale or ineffective records. The proposal therefore uses its CRUD/data-store contract for orders. [HPOS recipe book, SRC-D001](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/).

## Review checklist

For each new field, reviewers identify exactly one writer, permitted projections, freshness limit and deletion owner. For each command, reviewers identify capability, resource relation, transaction boundary, deduplication key and obligation after disable. Any blank stops the adapter design gate. Tests FR-D002/003/005/006/008 cover theme independence, ownership conflicts, HPOS access and module drain behavior.

## Missing marketplace adapter

Marketplace product/variation native inventory is a persisted fail-closed shell (managed stock zero and backorders disabled), not saleable replicated stock. The active verified adapter supplies offer availability. FR-D076 validates direct native add/update/checkout and existing carts after forced plugin loss. An independent operator/host maintenance lane handles abrupt dependency failure and restoration; existing obligations require recovery even when the plugin cannot execute its own disable hooks. Native-mode products continue native behavior.
