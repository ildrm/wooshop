# Compatibility and risk-based coverage

Document ID: DOC-S-QA02 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: QA/Test Engineer | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Compatibility and risk-based coverage | Requirements: NFR-S007; NFR-S010; NFR-S011
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; ASM-03; [source register](../00-governance/sources.csv)

## Bounded support

The canonical [version baseline](../00-governance/version-baseline.csv) defines minimum/target/future test versions. Every row is NOT RUN for this project. The [capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) controls which combinations can be enabled. A native product type does not imply every marketplace, payment, shipping, Cart/Checkout block or extension combination works.

| Dimension | Required sampling |
|---|---|
| Site/profile | Outdoor and Smart Home; additional neutral third-site onboarding fixture |
| Mode | Native single merchant; marketplace first-party+third-party; vendor-owned listing adapter |
| Product capability | Simple, variable, grouped, external, virtual, downloadable; proposed bundle/composite/subscription/booking/service adapters |
| Actor | Guest, customer, every seller team role, platform/admin/support/dispute roles; revoked membership |
| Language/device | English LTR and Persian RTL; small phone320/390px, tablet768px, desktop1440px; keyboard-only |
| Storage/checkout | HPOS chosen policy; native Cart/Checkout blocks; declared legacy fallback only if matrix permits |
| Versions/providers | Minimum and target combinations; each named adapter's pinned candidate; provider sandbox failures |
| Data extremes | Long/missing text,0/2/3 minor-unit currencies, huge quantities rejected, stale version, empty/no-stock |

Critical monetary/permission coverage is exhaustive across supported authority paths and every app role/object boundary, including same variation from two sellers, cross-site IDs, duplicate webhook and late capture. Exhaustive does not mean every browser crossed with every SKU: browser rendering combinations can be pairwise while money services are fully covered at integration level. Lower-risk layout/content combinations use pairwise coverage with documented selection. Add regressions for every production incident and boundary change.

NFR-S011: Theme without companion plugin retains native browse/cart/account behavior and removes unavailable custom controls. Plugin with another compatible theme exposes semantic, keyboard-operable fallback forms/tables. Deactivated optional providers show a safe unavailable state; no stale purchase affordance or abandoned obligation. Verify these without assuming the universal theme masks errors.

Unsupported combinations are explicit fixture outcomes: multi-seller recurring subscriptions, cross-seller bundle component returns and booking capacity must stay disabled until their own adapter contract/financial tests pass. Attempting activation or adding them to cart gives a localized capability error, not an improvised simple-product checkout. Legacy flows require separate evidence; supporting blocks never implies classic checkout compatibility or vice versa.

At phase8, QA exports the selected matrix with test IDs, versions, result, evidence link and exceptions. Parent/product approval can reduce launch scope only by keeping affected capabilities disabled; detailed documentation remains in this repository.
