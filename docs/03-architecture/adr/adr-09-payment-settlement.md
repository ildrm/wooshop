# ADR-09: Payment and settlement integration

Document ID: DOC-D-ADR-09 | Title: Payment and settlement integration | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D028; FR-D029; FR-D030; FR-D031 | ADRs: ADR-09
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D011; DEC-01; DEC-02; DEC-03; DEC-04; [source register](../../00-governance/sources.csv)

## Context

Customer collection and seller payouts have jurisdiction-dependent obligations and asynchronous external state.

## Decision

Use approved hosted/tokenized payment and marketplace payout integration. Maintain a balanced audit subledger or approved equivalent provider ledger mapping; never operate an unapproved wallet/custody model. Use durable idempotency and reconciliation.

## Alternatives

Manual untracked transfers cannot prove balances. Building a card processor or taking custody is out of scope. Provider ledger alone is acceptable only with complete export, allocation and reversal guarantees.

## Consequences and reversibility

Provider country, legal entity, merchant-of-record, reserves, fees and liabilities remain gated. Payout is not implied by order completion.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Finance/Tax and Legal/Compliance decide DEC-02/03/04. Payments/Settlement Engineer demonstrates duplicate/out-of-order callback, refund-after-payout and chargeback recovery.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
