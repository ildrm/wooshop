# ADR-08: Customer order and seller allocation

Document ID: DOC-D-ADR-08 | Title: Customer order and seller allocation | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D025; FR-D026; FR-D027 | ADRs: ADR-08
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-02; [source register](../../00-governance/sources.csv)

## Context

One checkout may create several fulfillment and financial obligations, but customers need one coherent purchase.

## Decision

WooCommerce owns one customer order. The plugin persists immutable purchased item snapshots and seller allocations; provider suborders, when required, are explicitly mapped projections and never second customer charges.

## Alternatives

Independent seller checkouts simplify accounting but contradict mixed-seller cart requirements. Treating provider suborders as duplicate orders risks repeated revenue and emails.

## Consequences and reversibility

Refunds and shipping are line/allocation aware. Aggregate order state is derived conservatively from child obligations. Historical pre-migration orders stay intact.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Marketplace QA Specialist verifies distinct same-variation seller lines, split shipment, coupon allocations and one purchase analytics event.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
