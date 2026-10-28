# Performance, concurrency and recovery tests

Document ID: DOC-S-QA04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: Performance Engineer | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Performance, concurrency and recovery tests | Requirements: NFR-S002; NFR-S003; NFR-S004; NFR-S005; NFR-S008; NFR-S009
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; SRC-S003; [source register](../00-governance/sources.csv)

## Measurement design

NFR-S002 adopts proposed field targets LCP<=2.5s, INP<=200ms and CLS<=0.1 at p75, separately for mobile and desktop. [Web Vitals](https://web.dev/articles/vitals) (SRC-S003) verifies these current definitions. Measure home, category, PDP and checkout independently over rolling28days with sufficient samples; report insufficient sample rather than a false pass. Geography/network and consent-limited sampling affect interpretation. Third-party payment pages are separately monitored and cannot be guaranteed by theme performance.

Lab budgets are diagnostic: median of5 cold-cache runs on a defined midrange mobile profile and4G-like network; report LCP/CLS and main-thread work, not substitute a lab interaction proxy for field INP. Proposed initial transfer budgets: homepage/PDP compressed initial JavaScript<=250KiB, CSS<=100KiB, primary LCP image<=250KiB with responsive sizes. Exceptions require measured benefit and Performance review. Ads,360media/video and optional widgets lazy-load only when appropriate without hiding essential content.

## Load and races

Use the exact planning scenarios in [capacity design](../13-operations/capacity-performance-and-cache-design.md). Run a30minute steady phase after10minute warm-up plus2x checkout burst for5minutes. Record p50/p95/p99 server latency, DB locks, cache hit/staleness, queue age, errors and successful paid orders. Proposed catalog cached response p95<=500ms, uncached search<=800ms and server checkout processing<=2s excluding external-provider wait. No numerical pass can excuse stock or money corruption.

Critical race fixture has one available unit and20 concurrent sessions. Exactly one valid claim can become a paid fulfillment allocation; others receive sold-out/retry outcomes with no capture. Expire a hold while payment is unknown, then deliver late success: reconcile and either safely fulfill if claim is re-established or refund/hold for operator action; never silently oversell. Repeated callbacks and worker restarts produce one business effect.

## Recovery experiments

Kill a worker after provider request but before local acknowledgement; replay job and reconcile provider ID before a second monetary request. Interrupt import after checkpoint; resume without duplicated products/offers. Disable search provider and confirm native bounded search fallback; stale offers still require authority checks. Corrupt a cache entry and verify invalidation plus no tenant leakage. Delay notification delivery across opt-out and suppress the optional send.

Restore a synthetic backup into isolated infrastructure; verify RPO<=15minutes and RTO<=4hours target including application integrity checks, then reconcile transactions occurring after backup and replay deletion tombstones before opening traffic. No database rollback is permitted merely because code rollback is convenient. Record actual recovery times and gaps only after future execution. SRE/Finance sign evidence for captures, refunds, orders and ledger conservation.
