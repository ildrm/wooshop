# Migration, monitoring and SEO acceptance

Document ID: DOC-S-SEO06 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Migration, monitoring and SEO acceptance | Requirements: SEO-S010; SEO-S011
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section 10; SRC-S004–SRC-S010; [source register](../00-governance/sources.csv)

## Before any launch or migration

SEO-S010: Export existing public URLs, canonical mapping, status, sitemap membership and redirect targets from authorized nonpersonal catalog data. Classify each as preserve, move with one301, merge after identity review, or retire404/410. Preserve product/native IDs and slugs in the single-seller marketplace migration; adding offers alone does not change canonical URLs. Test a graph of redirects for loops and chains and check translated equivalents separately. No blanket homepage redirects. Staging is authenticated and noindex, with private origin/media restrictions; production gate explicitly checks those staging controls are not accidentally copied.

SEO-S011: Phase8 evidence includes server-rendered content, metadata/robots headers, a crawl of every template class, representative variant/offer cases, sitemap sample, schema test records, feed parity and an accessibility review of link labels. SEO approval is separate from runtime transaction acceptance. Future Search Console/Merchant Center access is least-privilege and site-specific; no account creation or configuration occurred in this assignment.

| Signal | Proposed trigger | Owner/action |
|---|---|---|
| Canonical/robots regression | Any private page indexable or product unexpectedly noindex | Release block; SEO and Security triage immediately |
| Sitemap invalid entries | Any non200, noindex or wrong-site entry | Remove affected entries; fix projection within1business day |
| Product/feed mismatch | Any confirmed monetary/stock mismatch | Hold item feed; commerce owner repairs within15minutes |
| Crawl404 increase | Twice seven-day baseline and >=50 requests/day | Investigate mappings; do not automatically redirect |
| Structured-data critical errors | Any new sampled template error | Block affected template release; capture validator output |
| Crawler server errors | >1% verified crawler requests for30minutes | SRE incident and capacity review |
| Search impressions/index coverage | Change >30% week-on-week after enough data | Investigate seasonality/content/crawl; not a promised causal diagnosis |

Baselines begin only after real measurement. A lack of Search Console data is unknown, not zero errors. Review daily for first14days after launch, weekly thereafter, and after provider/template updates. Keep change timestamps and releases with affected URLs; collect only aggregate crawler metrics without user search text.

## Retirement

Out-of-stock products remain useful200 with honest availability. A discontinued product with manuals/warranty/support value remains200 and clearly identifies alternatives without disguising them as the original. When no useful content remains, use410; use301 only for a genuine successor/replacement content equivalent. Campaign expiry removes stale sale terms, points users to relevant current content and retains200 only if evergreen value exists. Closed sellers' operational records remain private under retention rules.

Rollback restores the previous metadata renderer/configuration when reversible. If orders or identity merges have occurred, repair forward using the retained mapping; do not roll the entire database backward to fix SEO.
