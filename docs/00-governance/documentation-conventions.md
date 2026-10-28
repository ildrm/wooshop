# Documentation conventions

Document ID: DOC-G-CONVENTIONS | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Technical Product Writer | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Documentation conventions | Requirements: FR-G001; FR-G003; FR-G015 | ADRs: ADR-03; ADR-16
Dependencies: [documentation conventions](documentation-conventions.md) | Assumptions: ASM-01; ASM-02; ASM-03
Sources: USER-BRIEF; [source register](sources.csv). All proposals await the designated human gate; runtime validation is not executed.

## Evidence and authority

The user brief supplies requirements, not facts about providers. Labels used throughout are **verified external fact**, **user requirement**, **observed reference behavior**, **proposed design decision**, **planning assumption**, **unresolved business/legal decision**, and **unverified dependency claim**. An accessed source proves only the cited claim. A release page proves release status, not compatibility.

The source of truth for obligations is [requirements.csv](../02-requirements/requirements.csv), with quality attributes in [nonfunctional-requirements.csv](../02-requirements/nonfunctional-requirements.csv). Every normative detail in a specification is a refinement of its listed requirement IDs. MUST is release-blocking; SHOULD needs a documented exception; MAY is optional and requires its acceptance tests when enabled. Priority P0/P1/P2/P3 describes release ordering, not documentation completeness. Phase is the numbered implementation phase, not the writing sequence.

## Stable identifiers and links

IDs never get reused. Documentation IDs start DOC-G-, DOC-D-, DOC-U-, or DOC-S- for governed authoring partitions. Requirements use FR-G/D/U/S followed by three digits; SEC-S, SEO-S and NFR-S likewise. TEST shares the partition suffix and numeric sequence. ADR-01 through ADR-16 name architectural decisions. MOD-U, SCR-U and CMP-U identify module/screen/component definitions; DATA-D, API-D and EVT-D identify commerce contracts. FTR-G identifies catalog entries, WP-G work packages, ROLE-G professional roles, ASM-01.. assumptions, DEC-01.. business gates, and RISK-01.. risks. Extension IDs beyond these allocated ranges are appended; retired IDs retain tombstone records.

CSV references store docs-relative paths and one atomic relation per traceability row. Semicolon-separated reviewer/dependency labels are human lists, not a substitute for many-to-many trace rows. A requirement has one accountable owner even where several roles perform the work. CSV is UTF-8 with an explicit header and RFC-style quoting. Dates are ISO8601; timestamps in domain contracts are UTC with explicit presentation timezone. Monetary amounts are integer minor-unit strings with a currency/exponent tuple, never binary floats. Empty means unspecified only where the schema permits it; `not applicable` includes a reason; `0` is a real zero; `unverified` is not false.

## Document states and change discipline

`drafted`, `internally checked`, `blocked by decision`, `pending external verification`, and `pending human approval` describe documentation states. No artifact is approved by AI. Runtime test cases remain `NOT RUN`. PASS on a link or syntax check is not production readiness. A global internal check can coexist with open external decisions; the manifest records both the document state and actual validation result.

A change request states affected IDs, motivating evidence, alternatives, safety/finance/privacy/SEO consequences, migrations, tests, owner, decision gate and rollback/forward-recovery approach. The Technical Product Writer maintains links; the domain owner decides substance. Keep previous version history through future version control; do not overwrite financial history to simplify specifications.

## Shared record contracts

A module inherits common kernel lifecycle/auth/logging rules only by explicit reference, then supplies its feature-specific data, settings, workflows, jobs, failure semantics, retention, SEO, localization and tests. Components inherit tokens and universal interaction states but list their unique controls and state outcomes. Screen/module variants are not new applications. Unknown provider capability uses a safe disabled state, never a fabricated adapter.

This repository contains Markdown, CSV, declarative OpenAPI/JSON schemas and Mermaid design views only. Diagrams are conceptual unless labeled proposed physical. Inline examples are illustrative contracts; there are no implemented runtime packages. The document manifest is the navigable artifact inventory; the working status records actual completion and limitations.

## Supplemental index namespaces

The [identifier registry](identifier-registry.csv) is a definition-location index,not a duplicate fact registry. `REG-<uppercase normalized path>` identifies non-Markdown artifacts deterministically; `FTR-Gnnn` identifies the initial feature catalog, `BL-Gnnn` backlog items, `CAP-Dnnn` bounded capability rows, `CMD-Unnn` internal commands, `UC-Gnnn` use cases, `FIX-S-*` synthetic fixtures, `WALK-Gnnn` analytical slices, `GATE-nn` phase gates, `REL-Gnnn` release approvals, `OPS-Gnnn` operational responsibilities and `FAMILY-nn` documentation families. Security/SEO matrix record namespaces and financial check IDs retain their IDs from their canonical register. Future additions append without reassigning old numbers. FR-U037–040 and TEST-U037–040 are unallocated reserved gaps,not missing requirements. API-D038 and FR/TEST-G021 close signed unsubscribe behavior. Dates and statuses describe documentation work only.
