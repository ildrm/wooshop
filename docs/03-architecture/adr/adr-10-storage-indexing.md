# ADR-10: Storage and indexing

Document ID: DOC-D-ADR-10 | Title: Storage and indexing | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D032; FR-D033; FR-D034 | ADRs: ADR-10
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-06; [source register](../../00-governance/sources.csv)

## Context

Native content fits platform stores; offer concurrency and ledger audit require constrained access patterns.

## Decision

Keep native objects in their supported stores. Propose consolidated plugin relational aggregates for seller membership, offer/inventory/reservation, operation/outbox, allocation and immutable ledger entries. Define logical uniqueness/atomicity before future physical DDL.

## Alternatives

Putting all transactional facts in unstructured post meta weakens constraints. A physical table per conceptual entity is unnecessary and unproven.

## Consequences and reversibility

Index exact active-offer/stock/version, seller/resource, idempotency and due-job paths. Search/reporting are rebuildable projections. No SQL DDL is part of this assignment.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Database/Data Architect validates explain plans and concurrency in future capacity tests; QA verifies uniqueness conflicts and rebuild parity.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
