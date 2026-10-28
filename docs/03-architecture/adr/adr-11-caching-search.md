# ADR-11: Caching and search

Document ID: DOC-D-ADR-11 | Title: Caching and search | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D035; FR-D036 | ADRs: ADR-11
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-06; [source register](../../00-governance/sources.csv)

## Context

Dense catalogs need responsive discovery without leaking personal data or authorizing stale stock.

## Decision

Use public catalog caches and native search baseline. Namespace by site/locale/currency/region/capability. Invalidate product/offer generations on commit; use optional external search only after measured need. Quote, cart, finance and authorization bypass public caches.

## Alternatives

Caching rendered personalized carts risks leaks. External search from day one adds cost without scale evidence.

## Consequences and reversibility

Offer summaries may be stale up to the documented public limit; checkout always validates live authority. External-search outage falls back to native retrieval with visibly reduced facets.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Performance Engineer and Security Engineer test cache keys, stale invalidation, deleted content and cross-user boundaries.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
