# ADR-07: Price and stock authority

Document ID: DOC-D-ADR-07 | Title: Price and stock authority | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D021; FR-D022; FR-D023; FR-D024 | ADRs: ADR-07
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-09; [source register](../../00-governance/sources.csv)

## Context

Stock/price duplicated in WooCommerce, marketplace providers and custom offers risks overselling and incorrect charging.

## Decision

Each product has an authority mode/epoch. Native mode delegates to WooCommerce price/stock. Marketplace mode uses plugin offer price and inventory pools, with supported interception preventing native/provider decrements for mapped lines. A server quote validates every transition.

## Alternatives

Always using parent product stock cannot represent seller stock. Dual-write synchronization cannot safely resolve simultaneous purchases.

## Consequences and reversibility

Migration pauses sales per product and reconciles stock before switching. Provider adapters unable to guarantee single reduction block sales. Stock projections are never checkout authority.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

QA runs last-unit races, reserve expiry, duplicate callbacks, refund restock and migration authority checks. One accepted sale reduces one authoritative balance once.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
