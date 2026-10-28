# Universal commerce documentation

Document ID: DOC-G-README | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Technical Product Writer | Reviewers: Product Owner; Solution/Software Architect; QA/Test Engineer
Scope: navigation and documentation handover | Requirements: FR-G001; FR-G009; FR-G017
ADRs: ADR-01; ADR-02; ADR-14 | Dependencies: DOCUMENTATION_MANIFEST.csv; GENERATION_STATUS.md
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](00-governance/sources.csv)

This repository specifies one reusable WordPress block theme and one modular companion plugin for independent outdoor and smart-home stores, including full marketplace operation and advanced commerce. **These are proposed design contracts and implementation plans; no application has been implemented or tested.** All ten delivery phases and P0–P3 capability releases are in scope.

Start with the [manifest](DOCUMENTATION_MANIFEST.csv) for exact files, owners, coverage and validation, and [generation status](GENERATION_STATUS.md) for actual completion. [Final handover](17-validation/final-handover-summary.md), [validation results](17-validation/documentation-validation-report.md) and [open decision gates](17-validation/unresolved-decisions-and-readiness-gates.md) separate documentation checks from future human approvals and runtime evidence.

## Read by responsibility

| Reader | Recommended route |
|---|---|
| Product or program owner | [Charter](00-governance/project-charter.md) → [PRD](01-product/product-requirements.md) → [features](01-product/feature-catalog.csv) → [roadmap](14-delivery/phased-roadmap-and-dependencies.md) |
| Architect or implementer | [SRS](02-requirements/software-requirements-specification.md) → [architecture](03-architecture/architecture-overview.md) → [ADRs](03-architecture/adr/README.md) → [data](04-data/domain-model-and-erd.md) → [API contract](09-api-integrations/openapi.yaml) |
| Designer or theme engineer | [Reference audit](05-ux-ui/reference-audit.md) → [tokens](05-ux-ui/design-system-and-tokens.md) → [screens](05-ux-ui/screen-and-template-specifications.md) → [components](05-ux-ui/component-specifications.md) → [settings](05-ux-ui/settings-inventory.csv) → [theme](06-theme/theme-functional-specification.md) |
| Plugin or marketplace engineer | [Kernel](07-plugin/plugin-kernel-and-module-lifecycle.md) → [module catalog](07-plugin/module-catalog.csv) → [marketplace models](08-marketplace/operating-models-and-catalog-governance.md) → [financial examples](08-marketplace/worked-transaction-examples.md) |
| Security, SEO or quality reviewer | [SEO](10-seo/seo-strategy-and-responsibility-boundaries.md) → [threat model](11-security-privacy/security-requirements-and-threat-model.md) → [test strategy](12-quality/quality-and-test-strategy.md) → [walkthroughs](17-validation/cross-disciplinary-review.md) |
| Operator, editor or seller support | [Operations](13-operations/environments-release-and-maintenance.md) → [store profiles](15-store-profiles/independent-configuration-and-onboarding.md) → [administrator](16-guides/administrator-customization-guide.md), [catalog](16-guides/catalog-editor-and-merchandiser-guide.md), [seller/finance](16-guides/seller-and-finance-operations-guide.md), [support](16-guides/customer-support-and-training-guide.md) guides |

## Canonical registers

[Requirements](02-requirements/requirements.csv), [quality requirements](02-requirements/nonfunctional-requirements.csv), [traceability](02-requirements/traceability.csv), [tests](12-quality/test-cases.csv), [capability matrix](02-requirements/capability-and-compatibility-matrix.csv), [settings](05-ux-ui/settings-inventory.csv), [sources](00-governance/sources.csv), [version baseline](00-governance/version-baseline.csv), [assumptions](00-governance/assumptions.csv), [decisions](00-governance/decisions.csv), [risks](00-governance/risks.csv), [roles](00-governance/role-catalog.csv), [RACI](00-governance/raci.csv), [work packages](14-delivery/work-breakdown-structure.csv) and [backlog](14-delivery/implementation-backlog.csv) each own their stated facts. Specifications refine their linked requirement IDs; the trace register makes cross-family relationships explicit.

## Working assumptions

The proposed baseline uses native WordPress editing, WooCommerce CRUD/HPOS and Cart/Checkout extension contracts, a modular plugin and separate site data. Marketplace canonical products/variations differ from seller offers; prices, stock and immutable purchased terms have explicit authorities. A controlled authority migration preserves old order history. The build/integrate recommendation and every provider combination remain subject to documented gates.

English and Persian examples require LTR/RTL behavior without implying business jurisdiction. Jurisdiction, lawful funds flow, currencies/tax, providers, budget, capacity, retention, product evidence, branding and launch remain human decisions. Source evidence and current release listings do not prove runtime compatibility. AI analytical reviews do not constitute independent assurance or human approval.

Read [conventions](00-governance/documentation-conventions.md) before extending IDs or contracts. Future contributors preserve unrelated documents and use [change control](00-governance/risks-dependencies-and-change-control.md). All examples and diagrams describe a future system; OpenAPI is a declarative proposal, not an operating endpoint.
