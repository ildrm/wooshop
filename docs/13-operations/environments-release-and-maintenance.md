# Environments, release and maintenance

Document ID: DOC-S-OPS01 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; operational validation NOT RUN
Owner: Release/Maintenance Owner | Reviewers: DevOps/SRE Engineer; QA/Test Engineer; Release/Maintenance Owner
Scope: Environments, release and maintenance | Requirements: FR-S001; NFR-S007; NFR-S012
ADRs: ADR-07; ADR-09; ADR-11; ADR-14; ADR-16
Dependencies: [version baseline](../00-governance/version-baseline.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; [source register](../00-governance/sources.csv)

## Environment responsibilities

FR-S001: Development, test, staging and production use the same declared application/adapter major versions and configuration schema; differences are explicit in an environment inventory. Each independent store has separate customer/order data, databases, secrets, buckets, cache namespace and provider credentials. Released theme/plugin packages are shared artifacts, not shared live writable directories. Local/test fixtures are synthetic; production copies require irreversible anonymization and Privacy approval before nonproduction use.

| Environment | Data/provider boundary | Owner and release use |
|---|---|---|
| Local | Synthetic catalog/users; fake or sandbox providers; no production secrets | Engineers; fast isolated checks |
| Integration/test | Versioned fixtures and provider sandbox; disposable secrets | QA; contracts/races/migrations |
| Staging | Production-like topology/capacity profile; synthetic/anonymized data; outbound messages/payment fenced | SRE; performance/recovery/release rehearsal |
| Production per site | Approved live provider credentials and policies; restricted operations | SRE; reviewed artifacts only |

Staging has authentication and noindex with private media, and a visible environment label. Email/SMS route only to test sinks. Production checks explicitly verify payment/email destinations, canonical host, robots, locale/tax config and alert routing. Never assume environment parity from identical package names alone.

## Release sequence and evidence

1. Release Owner assembles separate theme/plugin versions, checksums, SBOM, migration plan, compatibility matrix and change notes. Required gates include security, accessibility, SEO, finance/invariants and provider contracts.
2. SRE captures backup and recovery checkpoint; Finance records open captures/refunds/payouts and reconciliation state. Migration preflight reports required space, locking and irreversible changes.
3. Rehearse on staging with the same configuration schema and representative capacity. Complete canary checks and verify no staging flags/secrets in production artifact.
4. Apply release to one approved site first; protect checkout acceptance if an incompatible schema transition requires a short maintenance period. Do not run incompatible old/new workers against the same jobs.
5. Verify native browse/cart/checkout, site isolation, schema/feed, ledger totals, queue recovery and scoped seller access. Observe for an approved interval before second site rollout.
6. Record actual release and evidence. Unresolved critical defects stop rollout. Publish future operational notices only through separately authorized communication processes.

## Rollback and forward repair

Classify migration as reversible configuration, additive compatible schema, or irreversible data/contract change. Old code can be restored only if compatible with current data/jobs. After new orders or provider effects, database rollback can erase obligations and duplicate money; prefer disabling new capability and forward repair from checkpoints. A destructive migration requires a separate approved operational plan and tested restore/reconciliation. This repository contains no migration implementation.

Maintenance windows are proposed during measured low traffic after business approval; no invented launch date or window. Keep customer status accurate and retries safe. Theme update preserves saved native user styles and plugin data. Module shutdown enters obligation-draining mode before disabling workers; uninstall follows explicit retention/export/legal gates. See [support lifecycle](support-upgrades-and-end-of-life.md).
