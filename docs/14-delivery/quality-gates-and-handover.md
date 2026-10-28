# Quality gates and handover

Document ID: DOC-G-GATES | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Release/Maintenance Owner | Reviewers: Product Owner; Solution/Software Architect; QA/Test Engineer; Release/Maintenance Owner
Scope: Quality gates and handover | Requirements: FR-G009; FR-G016; FR-G019 | ADRs: ADR-02; ADR-06; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [decisions](../00-governance/decisions.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Gate evidence is explicit

Each GATE-01..10 in [roadmap](phased-roadmap-and-dependencies.md) has one accountable function recorded in [RACI](../00-governance/raci.csv). The responsible team supplies evidence and the accountable human records pass/fail/conditional disposition with date, exact scope, residual risks and expiry. AI analytical reviews in this repository are not those sign-offs.

| Release evidence | Approver function | Blocking condition |
|---|---|---|
| Scope and enabled capabilities | Product Owner | A critical customer promise has no requirement or test |
| Native/provider architecture and versions | Solution/Software Architect | Dual stock/payment/SEO authority or unsupported adapter enabled |
| Financial and marketplace operations | Finance/Tax Specialist | Any unexplained minor-unit variance,duplicate effect or unmanaged obligation |
| Legal/provider/privacy arrangement | Legal/Compliance Specialist | Unapproved funds flow,required terms/consent/retention unresolved |
| Security and privileged boundaries | Application Security Engineer | Critical exposure,authorization failure,unsafe secret or unverified release integrity |
| Accessibility/localization and design | Accessibility Specialist | Critical task inaccessible;RTL/zoom/sticky overlap prevents use |
| SEO/content and technical claims | SEO/Content Strategist | Wrong canonical/feed price or published unsupported safety claim |
| Executed quality and migration evidence | QA/Test Engineer | Failed critical test,unreconciled migration or unjustified coverage gap |
| Operations and recovery | DevOps/SRE Engineer | No verified restore/reconciliation path or unowned alerts |
| Training/support | Technical Writer/Trainer | Operators cannot perform required scoped recovery/escalation |
| Final go/no-go | Release/Maintenance Owner | Any above blocker or missingDEC-11 |

A lower-priority exception requires explicit safe degraded behavior, named owner, expiration and retest. It cannot waive unlawful fund handling, cross-seller privacy, financial conservation, critical accessibility barriers or paid-order loss. Disabling new sales does not remove existing refund/renewal/settlement obligations.

## Concrete handover package

Provide package versions and dependency evidence, configured site/profile exports excluding secrets/personal data, applied migrations with checkpoints, validated catalog/content evidence, exact test reports, unresolved risk/decision dispositions, runbooks, on-call contacts, incident escalation, backup and restore records, finance reconciliation opening balances, staff competency records and maintenance schedule. The documentation manifest and change log accompany the released packages. None of this runtime evidence is claimed produced by this assignment.

## Staged launch model

Select a low-risk approved capability/site slice, verify production configuration without live customer data in test environments, rehearse rollback/forward recovery, and confirm provider operations. Enable new activity gradually under staffed monitoring. Reconcile orders, gateway transactions, allocations and ledger before each expansion. Stop new affected activity on conservation/auth failure while preserving support and existing obligations. Record launch observation windows and actual thresholds from operations contracts rather than inventing a date.

## Documentation handover now

The present package is accepted on substantive completeness, traceability and actual documentation validation. [Generation status](../GENERATION_STATUS.md) and [final handover](../17-validation/final-handover-summary.md) identify what exists and remains pending. Providers, legal choices, human approvals, implementation and runtime tests remain distinct future work. No source code, configuration deployment, accounts or vendor messages are part of this handover.
