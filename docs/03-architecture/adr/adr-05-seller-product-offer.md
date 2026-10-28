# ADR-05: Separate seller, product and offer

Document ID: DOC-D-ADR-05 | Title: Separate seller, product and offer | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D018; FR-D019; FR-D020 | ADRs: ADR-05
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D005; SRC-D006; DEC-05; [source register](../../00-governance/sources.csv)

## Context

Several sellers may sell the same variation with different terms without duplicating canonical technical claims.

## Decision

Retain WooCommerce product/variation identity; create site-local seller organizations and offer IDs. An offer targets one product or exact variation, seller, condition, origin and terms. Provider listings map through a provenance-aware identity map.

## Alternatives

Copying products per seller matches some providers but creates duplicate content and weak canonical governance. Treating a seller as a user loses teams and ownership continuity.

## Consequences and reversibility

Matching and disputes require catalog moderation; published offers cannot alter canonical facts. Unique vendor-owned products remain distinct until verified matching.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Marketplace Solution Architect tests two offers against one variation and user membership in multiple organizations without cross-resource access.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
