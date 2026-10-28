# Roles, RACI and handoffs

Document ID: DOC-G-RACI | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Project/Program Manager | Reviewers: Business Analyst; Solution/Software Architect; QA/Test Engineer
Scope: Roles, RACI and handoffs | Requirements: FR-G008; FR-G009; FR-G014 | ADRs: ADR-03; ADR-14
Dependencies: [conventions](documentation-conventions.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](sources.csv)

## Professional responsibility

[The role catalog](role-catalog.csv) names every requested professional function. A function is not an employee count or a WordPress role. [The RACI register](raci.csv) is the authoritative assignment of one accountable role and one or more responsible roles for each documentation family/artifact, work package, decision gate, release approval and operational responsibility. Consulted and informed roles are supporting participants. Multiple humans can perform one role, but one accountable function remains identifiable. Missing human names are explicit staffing dependencies, not approval.

## Practical role combinations

For initial delivery, a Product Owner may also perform Business Analyst duties with a separate Project/Program Manager tracking change and capacity. A Solution/Software Architect may cover WooCommerce Solution Specialist and Marketplace Solution Architect until complexity demands specialists. UI/Visual Designer and Design System Designer can share one practitioner; Information Architect/Content Designer and UX Researcher can combine. Frontend and Gutenberg engineering can combine; PHP/WooCommerce engineering can combine. A technical writer can cover product documentation and training.

Keep finance transaction preparation separate from approval; the same person cannot exercise both permissions for the same payout, bank-detail change or manual adjustment. Security reviewer should not be the sole author approving privileged/payment code. Release approver cannot substitute their own confidence for QA evidence. Seller moderation appeals require a reviewer distinct from the original decision maker where a material dispute exists. Product safety/technical claim reviews require competent domain reviewers; marketing cannot self-certify evidence. Legal advice, payment eligibility and tax policy need qualified humans.

## Handoffs and evidence

| Handoff | Sender → recipient | Required evidence | Rejection condition |
|---|---|---|---|
| Discovery → architecture | Business Analyst → Solution/Software Architect | Actors, capability matrix, unresolved gate owners, IDs | Missing fund/stock authority boundary |
| UX → engineering | Design System Designer → Frontend Engineer | Named screens/components, token/settings inventory, RTL/focus/states, test scripts | Unspecified error state or controls without permission |
| Catalog → storefront | Catalog specialist → WooCommerce Extension Developer | Valid schemas, units, provenance, variation identity and rejected-row report | Unverified safety/compatibility claim |
| Marketplace → finance | Marketplace Backend Engineer → Finance/Tax Specialist | Immutable item allocations, balanced postings, refund and replay examples | Any unexplained minor-unit difference |
| Engineering → QA | Module owner → QA/Test Engineer | Exact build/dependency manifest, test fixtures, contract examples, migration plan | Unsupported combination advertised enabled |
| QA → release | QA/Test Engineer → Release/Maintenance Owner | Critical scenario evidence, security/accessibility/SEO reviews, restore drill | Critical open defect or missing business gate |
| Release → operations | Release owner → Store Administrator/DevOps | Runbooks, alert owners, known limits, training competency, rollback/forward plan | No on-call coverage or reconciliation owner |

## Authorization is a separate contract

The [application capability matrix](../11-security-privacy/roles-capabilities-and-object-authorization.csv) defines platform/marketplace/catalog administrators, moderator, seller owner/catalog/fulfillment/finance managers, platform finance, support, dispute manager, customer and guest. Effective permission requires active membership, capability, site/organization/object ownership and state-dependent restrictions. A professional Finance/Tax Specialist is not automatically granted production finance access. Hiding controls is insufficient. Cross-seller APIs, downloads, exports, media, queues and logs obey the same boundary.

## Review status

All role perspectives produced here are analytical AI work. Separate human product, architecture, design/accessibility, engineering, SEO, security/privacy, finance/marketplace, QA and operations approval remains pending. Staff these reviews before their phase gates, not after implementation is complete.
