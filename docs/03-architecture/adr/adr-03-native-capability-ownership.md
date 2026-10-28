# ADR-03: Native capability ownership

Document ID: DOC-D-ADR-03 | Title: Native capability ownership | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D005; FR-D008; FR-D013 | ADRs: ADR-03
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D001; DEC-09; [source register](../../00-governance/sources.csv)

## Context

Two review, coupon, stock or order systems create inconsistent customer and financial outcomes.

## Decision

Use WordPress/WooCommerce as owners of native data and calculations. Extend their supported APIs. Register exactly one writer for every added field and operation; all other copies are projections.

## Alternatives

Reimplementing commerce maximizes control but loses native compatibility. Unrestricted third-party composition permits duplicate writers.

## Consequences and reversibility

Provider installation may be rejected when it cannot disable conflicting behavior. Domain ownership is reviewed before adding a dependency.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

WooCommerce Solution Specialist verifies no direct order-post access and no duplicate coupon/review/schema engines.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
