# Project charter

Document ID: DOC-G-CHARTER | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Product Owner | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Project charter | Requirements: FR-G001; FR-G002; FR-G009; FR-G010 | ADRs: ADR-14
Dependencies: [documentation conventions](documentation-conventions.md) | Assumptions: ASM-01; ASM-02; ASM-03
Sources: USER-BRIEF; [source register](sources.csv). All proposals await the designated human gate; runtime validation is not executed.

## Intended outcome and boundaries

Specify one reusable WordPress block theme and one modular companion plugin for independently operated outdoor and smart-home stores, with optional future sites. Each site can use native single-merchant commerce, vendor-owned listings, or a shared catalog with seller offers. The theme carries presentation; persistent commerce behavior survives a theme switch. No shared customer pool, cart, seller organization, inventory or financial account is presumed.

The current assignment ends with a linked, internally checked documentation repository. It does not authorize application code, installations, business account creation, vendor contact, production data access, purchases or deployments. Third-party extensions and hosted services are separate dependencies, not silently bundled custom products. Proposed money flows require qualified legal, finance and provider decisions before marketplace activation.

## Success and stakeholders

A future implementer can identify each required behavior, its authority, scope, failure/permission path, acceptance case, phase, owner and dependencies. A designer can create original responsive LTR/RTL frames from the screen/component/token records. Finance can reconcile the worked examples and challenge the proposed ledger. Store teams can distinguish what they may configure from what needs provider approval or engineering.

The Product Owner is accountable for scope; the Solution/Software Architect owns coherence; the Project/Program Manager controls future sequencing; specialists own reviews in [roles and handoffs](roles-raci-and-handoffs.md). Documentation reviews are AI-generated analytical perspectives. They do not replace stakeholder consent, regulatory advice, accessibility conformance evaluation or independent security assurance.

## Acceptance of this assignment

FR-G001 requires all changes to remain beneath docs and all runtime verification to stay NOT RUN. FR-G003 requires access-dated primary evidence and visible uncertainty. FR-G009 requires all ten phases and all P0–P3 releases to be specified now. FR-G010 makes human decisions explicit rather than treating a provisional architecture as commercial approval. The final [validation report](../17-validation/documentation-validation-report.md) records actual artifact checks; the [readiness gates](../17-validation/unresolved-decisions-and-readiness-gates.md) separate documentation readiness from launch readiness.

## Scope changes

Requests for a headless rebuild, shared multistore identity, unsupported regulated goods, new settlement jurisdictions, custom custody, a proprietary page builder or an unverified product type trigger a change request. Existing historical orders and customer obligations are protected during changes. Reversible branding and feature settings may be selected within their documented bounds without reopening the core architecture.
