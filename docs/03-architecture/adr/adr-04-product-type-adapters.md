# ADR-04: Capability-based product adapters

Document ID: DOC-D-ADR-04 | Title: Capability-based product adapters | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D004; FR-D016; FR-D017 | ADRs: ADR-04
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D007; SRC-D008; SRC-D009; SRC-D010; DEC-09; [source register](../../00-governance/sources.csv)

## Context

Product type names do not establish price, inventory, shipping or purchase semantics.

## Decision

A versioned descriptor and named provider adapter declare purchase flow, price shape, stock owner, children, renewal/booking behavior and supported marketplace combinations. Unknown combinations are readable but non-purchasable.

## Alternatives

A universal scalar price and add-to-cart API fails external, grouped, booking and subscription cases. Provider UI alone cannot guarantee marketplace financial semantics.

## Consequences and reversibility

Complex marketplace combinations remain disabled until both adapter and payment/shipping tests pass; all are documented now.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

WooCommerce Extension Developer supplies contract fixtures for unavailable variations, virtual downloads, grouped children, kit refunds, renewals and rescheduling.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
