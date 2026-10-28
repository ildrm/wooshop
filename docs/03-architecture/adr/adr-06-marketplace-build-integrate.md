# ADR-06: Marketplace build versus integrate

Document ID: DOC-D-ADR-06 | Title: Marketplace build versus integrate | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D014; FR-D015 | ADRs: ADR-06
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D005; SRC-D006; DEC-04; DEC-05; [source register](../../00-governance/sources.csv)

## Context

The brief requires shared canonical offers, precise allocations and finance integrity, beyond a generic multivendor toggle.

## Decision

Recommend custom bounded marketplace domain plus approved payment/provider adapters, using the scored evaluation. Keep Dokan and Product Vendors as evaluated alternatives/import adapters. Funding and provider gates precede implementation commitment.

## Alternatives

Dokan has documented cloned-product semantics; Product Vendors assigns products to vendor accounts. Neither inspected documentation proves the full required capability set.

## Consequences and reversibility

Custom development costs and ongoing assurance are substantial. If budget fails, business scope must change explicitly; no silent equivalent-support claim.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Product Owner decides DEC-05 after estimates; Marketplace Solution Architect and QA must close critical provider capability unknowns.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
