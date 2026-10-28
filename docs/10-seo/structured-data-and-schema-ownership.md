# Structured data and schema ownership

Document ID: DOC-S-SEO03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Structured data and schema ownership | Requirements: SEO-S005; SEO-S006
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section 10; SRC-S004–SRC-S010; [source register](../00-governance/sources.csv)

## Versioned projection

SEO-S005: The SEO renderer produces a single graph from a published commerce projection with `catalog_version`, `offer_version`, locale and currency. Product identity is stable across seller choice. A variation is a concrete Product with its own SKU/GTIN evidence; a ProductGroup describes variants by explicit dimensions. For the provisional single-page variation design, a selectable URL preselects the variant but canonicalizes to the group page. Do not canonicalize truly distinct products together. An offer selector follows a separate removal policy: crawlable `noindex,follow` with normalized self canonical, as recorded in the URL matrix; it does not also claim canonical consolidation to the group. A future separate-variant-page design requires an ADR update, unique content, self canonicals and its own feed tests.

| Node | Data and omission rule |
|---|---|
| Product | Native ID, name, description, images, brand and evidenced identifiers; omit unavailable attributes |
| ProductGroup | Parent ID and real `variesBy` dimensions; contains concrete variant Products, never sellers |
| Offer | Specific seller offer, item condition, currency, price, availability and selectable URL; disclose the actual seller |
| AggregateOffer | Optional general summary of multiple eligible sellers of the same product/variant; no synthetic variation-price aggregation |
| Review / AggregateRating | Published WooCommerce product reviews only; exact visible count and rating, excluding seller feedback |
| BreadcrumbList | Current visible hierarchy and canonical links |
| Organization | Verified store entity and public contact; no fabricated certification |
| Shipping / return policy | Approved country/profile terms for this offer; omit if unresolved rather than invent a default |

Google's [variant guidance](https://developers.google.com/search/docs/appearance/structured-data/product-variants) (SRC-S005) distinguishes grouped variants from individual Products. [Schema.org AggregateOffer](https://schema.org/AggregateOffer) (SRC-S009) describes aggregated offers; that alone does not establish eligibility for a Google feature. Google's [merchant listing guidance](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing) (SRC-S006) requires Offer, not AggregateOffer, and the merchant must be seller; required product fields include name/image/offers and an offer includes price/currency. Accordingly, third-party marketplace landing pages stay outside a merchant-listing eligibility claim until DEC-02/04 establish the model. Markup describes truth even when rich results are unavailable.

## Consistency and edge cases

SEO-S006: The chosen variant/offer shown on initial page load, its JSON-LD and a feed item's selector URL use the same currency and current commercial terms. Server-rendered HTML includes current purchasable offer information. A null price has no fabricated zero Offer. Grouped/external/bundle/subscription/booking pages use the verified adapter's semantics; omit unsupported offer markup rather than flatten a schedule into a scalar price. Temporary out-of-stock is explicit availability; discontinued is not an expired in-stock sale. No fabricated reviews, expired countdowns or hidden cheaper offers qualify a page.

On projection failure, render truthful native content and omit optional stale schema; log the item/version and schedule rebuild. Feed export holds the affected item. Acceptance compares HTML price, selection, JSON-LD, feed and checkout for changed price, revoked seller, deleted variation and cache delay. Rich Results Test and schema validators are future checks, not certification, and search display is never guaranteed.
