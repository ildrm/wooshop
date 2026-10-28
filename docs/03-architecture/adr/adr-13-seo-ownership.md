# ADR-13: One SEO decision and rendering contract

Document ID: DOC-D-ADR-13 | Title: One SEO decision and rendering contract | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D038 | ADRs: ADR-13
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-09; [source register](../../00-governance/sources.csv)

## Context

Multiple emitters can disagree on product identity, price, canonical and robots policy.

## Decision

The plugin SEO coordinator owns policy and feeds one configured renderer. Theme emits visible semantic content/breadcrumbs only. A verified SEO adapter or native WooCommerce renderer emits one consistent commerce graph; native fallback remains when plugin absent.

## Alternatives

Letting every extension emit independently is rejected. Theme-owned business schema does not survive theme changes.

## Consequences and reversibility

Shared products use canonical product identity; distinct vendor products retain their own identity unless verified duplicates. Offer selection cannot change seller silently or invent aggregate rich-result eligibility.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

SEO/Content Strategist verifies HTML schema uniqueness, canonical/robots/feed parity and selected variation/offer consistency before adapter enable.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
