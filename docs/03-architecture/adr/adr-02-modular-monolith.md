# ADR-02: Modular plugin monolith

Document ID: DOC-D-ADR-02 | Title: Modular plugin monolith | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D003; FR-D006 | ADRs: ADR-02
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-06; [source register](../../00-governance/sources.csv)

## Context

Reusability and advanced features require explicit boundaries, but no scale evidence justifies distributed business services.

## Decision

Release one companion plugin with a dependency-aware module registry, stable domain interfaces and durable jobs. Keep components in-process and consolidate storage by consistency requirements.

## Alternatives

One monolithic feature switch obscures obligations. Separate plugins/services per capability multiply deployment and transaction failure boundaries.

## Consequences and reversibility

A kernel maintenance path remains active for outstanding refunds, notifications, subscriptions and settlement reconciliation after feature shutdown. Background work still needs reliable scheduling and monitoring.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

WordPress Plugin/PHP Developer demonstrates dependency cycles rejected, activation preflight, disabled consumers and obligation drain.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
