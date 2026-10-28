# SEO strategy and responsibility boundaries

Document ID: DOC-S-SEO01 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: SEO strategy and responsibility boundaries | Requirements: SEO-S001; SEO-S002
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section 10; SRC-S004–SRC-S010; [source register](../00-governance/sources.csv)

## Contract and ownership

SEO-S001: The companion plugin's SEO coordination module owns the decision about canonical URL, indexation, sitemap inclusion and commerce schema. A single configured renderer emits those decisions: native WooCommerce output with verified filtering, or an approved SEO extension adapter. The theme presents headings, meaningful links and breadcrumbs but emits no second commerce graph. A renderer manifest records which provider owns each head element; activation fails on unresolved ownership. If the companion plugin is absent, ordinary native WooCommerce/WordPress output remains available; marketplace-specific schema disappears with marketplace purchase capability.

| Artifact | Decision/input owner | Output owner | Conflict behavior |
|---|---|---|---|
| Product identity, selected variation and offers | WooCommerce product plus offer module | SEO coordination renderer | Suppress duplicate native/extension nodes after adapter verification |
| Titles, description, canonical, robots | SEO coordination; editor overrides within policy | One SEO renderer | Refuse competing providers; show diagnostic to administrator |
| Sitemap | SEO coordination eligibility; WordPress sitemap service or selected adapter | One provider per site | Exclude private and selector URLs; no second sitemap index |
| Breadcrumb navigation | Catalog taxonomy and editorial placement | Theme HTML; same renderer's BreadcrumbList | One canonical taxonomy trail; no seller-supplied arbitrary parents |
| Review counts | WooCommerce moderated product reviews | Commerce schema renderer | Seller service ratings never enter product aggregate |
| Organization / policies | Verified store configuration | SEO renderer | Omit unresolved organization or policy claims |
| Merchant feed | Feed adapter from published price/availability projection | Connector job | Stop item publication when checkout/landing parity fails |

SEO-S002: Titles default to `{product name} | {store name}`, categories to `{category} | {store name}`, guides to `{editorial title} | {store name}`. Editorial overrides permit plain text, Unicode normalization and locale-specific punctuation; no scripting or hidden keyword blocks. Search snippets are not guaranteed to use the provided description. One visible H1 describes the page; heading levels follow content structure. Images have useful alt text or empty alt when decorative, human-verified rights, and stable crawlable URLs; video has captions, poster and transcript where meaningful. Product identifiers are recorded only from evidence, never invented for feed completeness.

## Release review

SEO specialist selects a sample for every row of the [URL matrix](url-indexation-canonical-matrix.csv). QA compares rendered HTML, response headers, sitemap, structured data and feed to the same catalog version. A product changing seller or price invalidates its SEO projection and feed work item, not just its visible card. Private screens are excluded even when their URLs are guessed. Crawler accessibility is evaluated separately from customer authentication.

These are proposed product decisions. Stable URL and pagination guidance is supported by [Google URL guidance](https://developers.google.com/search/docs/specialty/ecommerce/designing-a-url-structure-for-ecommerce-sites) (SRC-S007) and [pagination guidance](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading) (SRC-S010). Rankings, impressions and crawl budgets have no measured baseline. See [acceptance](migration-monitoring-and-seo-acceptance.md).
