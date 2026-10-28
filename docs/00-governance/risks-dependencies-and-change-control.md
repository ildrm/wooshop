# Risks, dependencies and change control

Document ID: DOC-G-RISKS | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Project/Program Manager | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Risks, dependencies and change control | Requirements: FR-G010; FR-G015; FR-G016 | ADRs: ADR-06; ADR-09; ADR-16
Dependencies: [documentation conventions](documentation-conventions.md) | Assumptions: ASM-01; ASM-02; ASM-03
Sources: USER-BRIEF; [source register](sources.csv). All proposals await the designated human gate; runtime validation is not executed.

## Risk governance

The [risk register](risks.csv) distinguishes planning likelihood from demonstrated incidents. Every high-impact item has an accountable role, mitigating work and a phase gate. Financial correctness, cross-seller isolation, unsafe compatibility claims and unapproved provider/legal arrangements are release blockers. Delaying their review to phase8 would create architectural rework; review starts in discovery and repeats at each affected change.

## Dependencies and procurement

WordPress/WooCommerce are core dependencies. Paid extensions, marketplace services, gateways, search, messaging, anti-malware, hosting, fonts and design media are optional or conditional dependencies with separate licensing/cost/operations/exit responsibilities in the [integration catalog](../09-api-integrations/integration-catalog-and-data-mapping.md). A proposed provider contract is not an implemented adapter. Dependency costs include renewals, transaction charges, support, development, data export, migration and replacement testing; prices are unknown until approved procurement evidence exists.

The repository's GPLv2 license is observed context. Distribution compatibility and each dependency/media/font license require DEC-12 review. The assignment neither changes the license nor grants rights to third-party material. Procurement retains a machine-readable export path and documentation of source-of-truth mappings before adoption.

## Change process and decision evidence

A change record contains trigger, affected IDs, risk, proposed behavior, authority transitions, backwards compatibility, UI/SEO/privacy consequences, migration/reconciliation strategy, tests and human gate. The Product Owner decides scope; the Solution/Software Architect reconciles contracts; specialist reviewers can block unsafe release. The Project/Program Manager updates phase dependencies and effort ranges. Technical Product Writer records retired IDs and redirects. No schedule pressure permits weakening financial invariants without an explicitly approved design change.

For an incident-driven patch, Release/Maintenance Owner may shorten feature review but still requires security review, targeted tests, signed release evidence and post-release reconciliation. For legal/provider changes, deactivate new activity where required, continue existing obligations through a controlled drain, and preserve an auditable transition. Never assume a database rollback can undo new paid orders.

## Escalation triggers

Escalate to the accountable decision owner when evidence contradicts a baseline, provider exports cannot reconcile, exact-version tests fail, or a cost range exceeds the approved budget. Freeze the affected activation gate, not unrelated specification work. Record accepted residual risk with expiry and recheck conditions; unrecorded silence is not risk acceptance.
