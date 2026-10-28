# ADR-14: Independent installations versus Multisite

Document ID: DOC-D-ADR-14 | Title: Independent installations versus Multisite | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D001; FR-D011; FR-D012 | ADRs: ADR-14
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-01; DEC-06; [source register](../../00-governance/sources.csv)

## Context

The two businesses operate independently; cross-site users, carts and stock are not requested.

## Decision

Use independently deployed WordPress installations sharing only released packages. Keep data, credentials, cookie scopes, cache, backups and organization identifiers separate.

## Alternatives

Multisite may reduce administration but introduces correlated update risk and shared identity/operating assumptions; a separate future ADR may revisit it.

## Consequences and reversibility

Deployment effort is duplicated. Configuration profiles are portable, while customer/order/provider state is not copied through ordinary settings export.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

DevOps/SRE Engineer and Privacy Specialist validate isolation, restore and configuration import; Product Owner approves any future cross-site requirement.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
