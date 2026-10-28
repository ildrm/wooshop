# ADR-12: Native customization inheritance

Document ID: DOC-D-ADR-12 | Title: Native customization inheritance | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D013; FR-D037 | ADRs: ADR-12
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D004; DEC-10; [source register](../../00-governance/sources.csv)

## Context

The brief requests broad customization but platform settings already have precedence and persistence rules.

## Decision

Use theme defaults/style variations and native saved global-style overrides. Component instance controls remain native block attributes where available. Plugin domain settings have explicit scopes and do not form a competing universal CSS inheritance chain.

## Alternatives

A separate page-builder style tree diverges from native editing. Arbitrary CSS/scripts exceed the safe controlled catalog.

## Consequences and reversibility

Reset removes the selected override and reveals native inherited value; preset switch previews effects without silently discarding saved user choices. Export omits executable content/secrets.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Design System Designer validates each declared control against settings inventory; security verifies import and seller-branding scope.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
