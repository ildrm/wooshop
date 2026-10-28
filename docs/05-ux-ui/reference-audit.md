# Reference audit and evidence limits

Document ID: DOC-U-01 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: UX Researcher
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U018 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: SRC-U005; SRC-U006; SRC-U007; [source register](../00-governance/sources.csv)

## Method and evidence

Research date: 2026-10-28. A public text extraction of the [Digikala homepage](https://www.digikala.com/) succeeded. It exposed search, promotional navigation, product links with titles/prices/discounts and a delivery-location prompt. This is an observation of extracted public content, not a screenshot or an interaction test (SRC-U005). No assets, copy, logo or distinctive styling are adopted.

A linked product request returned HTTP 429 and the promotional listing request timed out. These access restrictions were respected. No authenticated customer or seller area was visited. Viewport dimensions, layout positions, mobile adaptations, focus behavior, sticky behavior and actual interactive states were not measured. The initial theme design therefore uses the brief and original design proposals, not claims of visual fidelity.

| Area | Evidence classification | What is known or missing | Intentional adaptation and future verification |
|---|---|---|---|
| Desktop header/navigation | Observed text; layout not observed | Search and promotional links exposed; menu mechanics unavailable | Persistent search with click-controlled category disclosure; inspect desktop keyboard sequence during future audit |
| Mobile header/navigation | Not observed | No mobile viewport session | Proposed two-row header, search beneath actions and modal category drawer; test touch and reflow independently |
| Homepage | Observed text | Many product/campaign links and delivery prompt | Cap competing promotions, prioritize one campaign heading and semantic section names |
| Category/search/filter | Not observed | Listing fetch failed; ranking/faceting unknown | Server-rendered results with URL-backed filters and crawlable pagination |
| Product-card hierarchy | Observed text; visual order unverified | Titles, old/new amounts and percentage labels appear | Title, key attribute, rating, actual price, seller disclosure, action; unknown price never represented as zero |
| Gallery/specifications | Not observed | Product fetch rate-limited | Accessible thumbnail buttons and disclosure-based specs, source provenance near safety claims |
| Variants/seller offers | Not observed | No variant or offer interaction inspected | Explicit variant selection before offer eligibility; never silently switch sellers |
| Cart/checkout | Not observed | No cart manipulation or purchase | WooCommerce native blocks with seller grouping and server-authoritative totals |
| Account/order | Not observed | Private area intentionally not accessed | Focus on independent shipment and refund status; authorized ownership checks |
| Promotions | Observed text; timer behavior not observed | Discount and campaign labels exposed | Real start/end instants; no reset countdowns or manufactured urgency |
| Error/empty states | Not observed | Fetch failure is a research limitation, not a storefront error observation | Designed empty/filter/timeout/offline recovery paths |

## Interpretation and proposal

Information density and product discovery are useful reference themes supplied by the brief. The proposal gives promotional content a limited hierarchy: one hero, up to two promotional rails before editorial help, and persistent access to categories. This cap is an original provisional decision, not a measured property of the reference. Outdoor and Smart Home use different color/media treatments while sharing every commerce component.

## Completion gate

FR-U018 requires a future dated audit with desktop/mobile screenshots owned by the project, provenance for each observation, and separate public interaction notes. The UX Researcher records inaccessible areas rather than inferring them. Human visual approval and legal review of identity originality remain required. This research does not establish usability or accessibility of either site.
