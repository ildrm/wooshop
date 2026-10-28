# Facets, pagination and crawl management

Document ID: DOC-S-SEO02 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Facets, pagination and crawl management | Requirements: SEO-S003; SEO-S004
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section 10; SRC-S004–SRC-S010; [source register](../00-governance/sources.csv)

## URL rules

SEO-S003: Facets use an allowlisted parameter vocabulary, stable key ordering and deterministic value normalization. A duplicate scalar key, malformed range, unknown facet or impossible combination returns a helpful 404 with clear-filter controls. A valid but currently empty facet also returns 404; the user can remove a filter and retain their search intent. A curated category with useful editorial content can remain 200 while temporarily empty, with its indexation reviewed after 30 days. Server rendering returns the selected state and results without waiting for client hydration.

Parameter examples: `/category/tents/?capacity=2&season=4&page=2`; page1 removes the page parameter. Reordered equivalent parameters redirect once to normalized order. Tracking parameters are ignored for content and omitted from canonicals; redirecting them is optional only when attribution handling is defined. Personal delivery region is a session preference, not a crawlable address fragment.

## Two deliberate strategies

Baseline uses bounded, crawlable facet URLs with `noindex,follow`, excluded from sitemaps; their canonical is the normalized facet URL, not a false assertion that a filtered set equals the whole category. Sorting variants use their normalized self canonical and remain noindex; they do not also claim consolidation to a different URL. Internal links to products use clean canonical URLs. Indexable curated facets are published as editorial landing pages such as `/collections/winter-tents/`, with unique intent, content and self canonical, rather than arbitrarily turning query combinations indexable.

At the scale gate, SEO and SRE may switch unbounded parameter families to robots disallow. First remove known indexed URLs through crawlable noindex and verify deindexation; then disallow to reduce fetch load. Once blocked, do not rely on that URL's noindex or canonical being read. Robots blocking is not privacy or guaranteed removal. Critical product and curated paths stay allowed. This staged decision avoids mutually contradictory controls. [Google faceted-navigation guidance](https://developers.google.com/crawling/docs/faceted-navigation) (SRC-S004) identifies large parameter spaces as a crawl risk; this project's bounded baseline is a reversible design choice.

## Pagination and enhanced loading

SEO-S004: Each valid page has a stable URL, server-rendered products, next/previous anchor links and its own canonical. Out-of-range pages return 404 at the requested URL. Load-more enhances these links, maintains browser history and announces the number of added results without moving focus unexpectedly; retry retains already shown results. Disabling JavaScript preserves navigation. Page2 is not canonicalized to page1. The footer remains reachable and pagination is operable by keyboard. Google documents anchor-based discovery and separate page canonicals in [SRC-S010](https://developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading).

QA verifies normalization with permutations, duplicate keys, empty results, page boundaries and a crawler with JavaScript disabled. Proposed alert: parameter URLs exceed 30% of verified crawler requests over seven days, or unique parameter URLs exceed ten times published products; SEO triages within two working days. This is an investigation trigger, not a claimed crawler budget.
