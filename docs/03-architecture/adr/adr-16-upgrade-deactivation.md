# ADR-16: Upgrades and obligation-aware shutdown

Document ID: DOC-D-ADR-16 | Title: Upgrades and obligation-aware shutdown | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D006; FR-D041; FR-D042 | ADRs: ADR-16
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-07; DEC-09; DEC-11; [source register](../../00-governance/sources.csv)

## Context

Modules can own obligations that outlive the visible feature and database rollback may erase new transactions.

## Decision

Use versioned additive migrations and resumable checkpoints; preflight backups and compatibility gates. Disable new operations first, drain or transfer obligations, retain history and audit. Destructive uninstall is an explicit separately governed operation after holds and balances clear.

## Alternatives

Blind database restore after live orders loses transactions. Deactivation that cancels refund/subscription work abandons obligations.

## Consequences and reversibility

Operational maintenance interfaces must work with feature UI disabled and with another theme. Forward recovery is preferred after irreversible external effects.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Release/Maintenance Owner proves interrupted migration restart, read-only degraded operation, obligation drain and reconciliation before release.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
