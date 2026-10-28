# ADR-15: Locale-aware presentation and identifiers

Document ID: DOC-D-ADR-15 | Title: Locale-aware presentation and identifiers | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D039; FR-D040 | ADRs: ADR-15
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: DEC-01; DEC-03; [source register](../../00-governance/sources.csv)

## Context

English and Persian examples require RTL/LTR without implying countries, currencies or legal rules.

## Decision

Persist canonical identifiers and UTC instants; localize display with explicit locale/calendar/timezone configuration. Use logical layout direction, bidirectional isolation for SKUs and money formatting without changing numeric amounts. Search normalization is a reversible indexing projection.

## Alternatives

Mirroring every icon/media asset is wrong. Storing localized digits as canonical numeric values breaks arithmetic and integrations.

## Consequences and reversibility

Electrical region, settlement currency and legal locale are independent settings. Transliteration/synonyms never alter product identity or manufacturer claims.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Localization/RTL Specialist checks mixed Persian/Latin titles, digit entry, calendar conversion and long messages; Finance verifies display roundtrip.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
