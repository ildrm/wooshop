# Quality and test strategy

Document ID: DOC-S-QA01 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: QA/Test Engineer | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Quality and test strategy | Requirements: NFR-S001–NFR-S012
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; ASM-03; [source register](../00-governance/sources.csv)

## Evidence model

This is a future verification plan. The [test register](test-cases.csv) contains scenarios and expected outcomes, not executed tests. Documentation checks do not establish runtime compatibility, security, accessibility, financial correctness or performance. NFR-S010 requires traceable evidence for all release-critical requirements and all20 critical slices described in the brief. A requirement passes only when its observable expected results, failure handling and authorization behavior pass in the supported configuration.

| Test level | Scope / evidence | Owner / gate |
|---|---|---|
| Unit | Calculation/rounding, eligibility, capability mapping, normalization, state transition boundary | Plugin engineer; every future change |
| Integration | WC CRUD/HPOS, Store API extension, reservation, ledger, jobs and adapter interactions | WooCommerce Extension Developer; phases3–6 |
| Contract | Request/response/version/error/idempotency against provider sandbox and declared schemas | API/Integration Engineer; adapter admission |
| End-to-end | Guest/customer/seller workflows and payment failure/recovery | Test Automation Engineer; phase8 |
| Visual | Original approved frames by preset/viewport/RTL/states | UI/Visual Designer; phase2 baseline and each visual change |
| Accessibility/localization | Automated diagnostics plus keyboard, screen reader, zoom and mixed-direction content | Accessibility/RTL specialists; each UI increment |
| Security/privacy | Object/field denials, malicious content, consent, deletion and secret boundaries | Security/Privacy specialists; design and release |
| SEO/feed | HTTP/canonical/schema/sitemap/feed parity across page families | SEO/Content Strategist; each template/adapter change |
| Concurrency/load/resilience | Races, late callbacks, provider outage, queue recovery and scenario capacity | Performance/SRE; phase8 and scale gates |
| Migration/recovery | Rehearsed checkpoint, history preservation, restore and forward repair | Data Architect/SRE/Finance; before migration |
| User acceptance | Representative buyer, seller, finance and admin tasks with observed outcomes | Product Owner; phase8/9 |

## Entry, exit and defect rules

Entry requires named versions/configurations, synthetic fixture IDs, provider sandbox eligibility, observable event/correlation IDs and reviewed expected calculations. Release exit requires no unresolved critical/high exploitable security or monetary-integrity defects; all critical scenarios pass; remaining lower-severity deviations have accountable acceptance and expiry. Missing evidence is NOT RUN, not pass. An unavailable provider yields BLOCKED for its combinations; do not turn that into universal adapter support.

A failure report records build, site/profile, product capability, role/scope, locale, viewport, exact actions, expected/actual, sanitized evidence and reproducibility. Re-test the changed boundary and affected cases; avoid endlessly rerunning unrelated suites. Independent reviewers are required for money/authorization and human accessibility acceptance. Future CI can automate subsets but must not replace manual criteria.

## Measurable quality baseline

[NFR registry](../02-requirements/nonfunctional-requirements.csv) is authoritative for target values. User-facing targets are segmented by mobile/desktop and important page families; financial integrity and isolation permit zero unexplained violation. Availability and recovery targets are proposals accepted with staffing and infrastructure planning. Usability tasks require at least8of10 unaided completions in a proposed10participant pilot per priority persona, with all critical errors reviewed; this is a planning acceptance criterion, not statistical market proof. Maintainability requires one documented owner and contract per persistent operation, verified by architecture review and extension tests.
