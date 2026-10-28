# Capacity, performance and cache design

Document ID: DOC-S-OPS02 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; operational validation NOT RUN
Owner: Performance Engineer | Reviewers: DevOps/SRE Engineer; QA/Test Engineer; Release/Maintenance Owner
Scope: Capacity, performance and cache design | Requirements: NFR-S008; NFR-S009
ADRs: ADR-07; ADR-09; ADR-11; ADR-14; ADR-16
Dependencies: [version baseline](../00-governance/version-baseline.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; [source register](../00-governance/sources.csv)

## Planning scenarios, not forecasts

ASM-03: No actual catalog volume, traffic, sellers or budget was supplied. The following scenarios size future experiments and reveal thresholds; they are not expected business results.

| Metric | S | M reference target | L scale investigation |
|---|---:|---:|---:|
| Canonical products |2,000|20,000|100,000|
| Variations |8,000|80,000|500,000|
| Offers |16,000|240,000|2,000,000|
| Sellers |20|500|5,000|
| Monthly visits |30,000|300,000|3,000,000|
| Simultaneous checkout sessions |5|50|250|
| Orders/day |100|1,000|10,000|
| Background jobs/day |1,000|20,000|200,000|
| Import rows/batch |1,000|10,000|100,000 split into <=10,000-row validated batches|
| Order retention sizing scenario |1year|3years|7years; legal duration still DEC-07|

At M, assume offer row+indexes2KiB and search projection3KiB:240,000offers require about469MiB authoritative offer storage and703MiB search projection before replication, versions or backups. Assume order+lines+ledger evidence20KiB:1,000orders/day over3years is about20.9GiB excluding media/logs. These are explicit rough allocation assumptions, not measured table sizes. Large media lives in object storage with derivative/rights lifecycle; do not store all images in cache. Measure actual bytes/row after implementation and revise estimates by2x headroom initially.

## Storage and scale triggers

Start a modular monolith with database indexes for offer eligibility/product variation/seller/status, reservation expiry, event identity and ledger account/date. Batch imports/checkpoints and paginated reads avoid loading full catalogs. Queue workers separate costly feed/search/notification jobs from checkout request time. External search is optional until native indexed search misses target or facets exceed bounded query cost; an adapter retains native fallback. No microservice or table per conceptual entity is assumed.

Investigate scale when p95 target is missed in two representative load runs, DB CPU>70% for30minutes, lock waits>200ms p95, queue age>5minutes, index freshness>60seconds or offer count approaches M benchmark limit. First improve query plans/indexes, cache invalidation and worker concurrency; then evaluate external search/replicas under consistency and cost review. Never read transactional stock from a lagging replica.

## Cache boundaries and invalidation

NFR-S009: Keys include site, locale, currency, pricing context, permission/publication version and resource version where relevant. Personalization that cannot be safely keyed is uncacheable. Administrative previews are private and bypass public/CDN caches.

| Data | Proposed maximum staleness | Authority/invalidation and failure |
|---|---|---|
| Public category/product HTML |60seconds|Catalog publish/price/offer event purges relevant keys; no cart/account fragments in shared output |
| Public eligible-offer projection |15seconds|Offer/stock/seller change invalidates; checkout always revalidates authority |
| Search documents/facets |60seconds|Catalog/offer projection job versioned; fail to bounded native search; show no stale guarantee |
| Cart/account/checkout/order/support |0 shared-cache exposure|Private no-store; session-specific server state; never CDN cached |
| Price/tax/stock/reservation used for purchase |0 authoritative staleness|Read/revalidate within transaction coordinator; no cached value authorizes purchase |
| Permission/membership for sensitive operation |0 revocation tolerance|Authoritative current membership check; cached UI visibility is not enforcement |
| Static theme assets |Content-addressed release cache|Versioned immutable asset URL; no secrets; previous release retained during rollout |

Stale public merchandising can degrade to no optional rail. A search outage cannot skip seller eligibility. Expiring campaign cache uses server schedule and never extends a countdown. A queue backlog alerts before freshness SLO is breached; coalescing supersedes old projections but not financial events. Per-site purge and cache-leak tests are release gates.
