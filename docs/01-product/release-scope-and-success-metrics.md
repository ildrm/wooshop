# Release scope and success metrics

Document ID: DOC-G-METRICS | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Analytics/Conversion Specialist | Reviewers: Business Analyst; Solution/Software Architect; QA/Test Engineer
Scope: Release scope and success metrics | Requirements: FR-G009; FR-G013 | ADRs: ADR-03; ADR-14
Dependencies: [conventions](../00-governance/documentation-conventions.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Release decisions

| Release | Capability outcome | Required evidence | Does not imply |
|---|---|---|---|
| P0 foundation | Reusable native storefront/editor;canonical offer-ready identities;operational/security/SEO baseline | Native critical purchase tests,theme/plugin independence,scoped auth,profile configuration | Sellers or payouts enabled |
| P1 advanced commerce | Technical schemas/search/relationships/customer modules/merchandising | Module tests,consent/error/lifecycle handling,performance and a11y budget | Every extension interoperates |
| P2 marketplace activation | Verified seller/offer/fulfillment/refund/settlement operations | Approved funds/legal/provider model;ledger/race/migration tests;trained staff | Unapproved custody or complex marketplace subscriptions |
| P3 scale and evolution | Measured optimization,provider expansion,updates and deprecation | Capacity evidence,exit/recovery drills,updated support matrix | Unlimited capacity or zero operational work |

All releases require ongoing security, accessibility, privacy, analytics, SEO and operations review. The ten phase gates are distinct from release names and repeat for changes to enabled capabilities.

## KPI measurement plan

| Proposed metric | Definition/segmentation | Collection owner | Decision use and limitations |
|---|---|---|---|
| Search usefulness | Product-detail visits after search divided by consented search sessions;language/device/profile | Analytics/Conversion Specialist | Compare after baseline;do not infer all visitors from consented sample |
| Purchase completion | Confirmed paid customer orders / initiated checkout attempts;capability/mode/device | WooCommerce server purchase event authority | Deduplicate by site/order/payment intent;exclude retries;no target conversion promised |
| Offer selection clarity | Tasks completed without seller/term misunderstanding in moderated scripts | UX Researcher | Proposed threshold at least8 of10 representative tasks;sample not market forecast |
| Catalog integrity | Published required fields with accepted provenance / applicable published fields | Catalog specialist | Proposed100% for safety/compatibility claims;missing evidence blocks claim |
| Financial conservation | Sum of unexplained settlement differences by currency | Finance/Tax Specialist | Proposed0 unexplained minor units;any discrepancy freezes affected payouts |
| Seller fulfillment | Allocations dispatched within their purchased promise / due allocations | Marketplace Operations Manager | Policy-specific baseline after actual operation;no fabricated SLA |
| Customer support resolution | Time to first meaningful response and resolved tickets by issue type | Customer Support Representative | Observe at launch;commit service hours only after staffing decision |
| Experience performance | Field p75 LCP/INP/CLS by page/device/network/locale | Performance Engineer | Targets defined in NFR register;lab proxies separate;no measured score here |
| Accessibility task success | Critical tasks completed with keyboard/screen reader/zoom | Accessibility Specialist | All critical tasks pass;automated scanner alone insufficient |

## Baselines and decision cadence

Every current baseline is **not measured**. Discovery confirms business volume and capacity scenario; future staging measures technical budgets; a consent-aware production baseline begins only after lawful analytics configuration. Review weekly during launch stabilization and monthly thereafter, with currency/site segmentation and bot/test filtering. Missing consent is not bypassed to improve statistics. Quantitative targets are proposed planning thresholds, not a promise of rankings, traffic, revenue or operational performance.

The [event catalog](../09-api-integrations/event-catalog.csv) and [operations analytics contract](../13-operations/slos-monitoring-alerts-and-analytics.md) specify authoritative emitters, sensitive-data filtering and deduplication. Purchase values represent the customer order once; per-seller allocations are dimensions for finance, not extra purchases.
