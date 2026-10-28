# Scheduled campaigns, promotional badges and countdowns

Document ID: DOC-U-M009 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U009, both independent stores and generic profiles
Requirements: FR-U049 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Coordinate editorial campaigns and truthful promotion presentation using native WC coupon/sale authority. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WP editorial content; WC coupons/sale prices; notifications optional. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: campaign UUID; content_id; starts_at/ends_at UTC; timezone; audience enum public/approved segment; eligibility rule version; badge text≤40; status draft/scheduled/active/ended; native coupon references. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `campaign.max_active:integer:10:1..30;campaign.countdown_enabled:boolean:false:true-or-false;campaign.badge_limit:integer:2:0..3`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: commerce.settings.manage; seller may propose own funded promotion but cannot alter platform coupon engine. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Draft schedule/terms → validate discounts with WC → approve → activate by real instant → expire and remove CTA/badge. Display timezone when meaningful. No reset timers or randomized stock scarcity. Campaign discount and compare-at claims require applicable legal review; coupon limits remain server-enforced.

UI contract: CMP-U006/U008/U009; visible end date; countdown never live-announces each second; ended state persists until content replaced. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

internal campaign commands; campaign.changed; scheduled activation/expiry jobs plus request-time expiry guard. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Idempotent activation version; request-time actual instant prevents latejob stale discount claim; cache invalidated at start/end and price events. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Campaign/audit version retained to explain purchased terms; optional segmentation privacy review; no unconsented targeted messages. Unique curated landing may index; expired lifecycle SEO policy; no false rich-result claim. Disable stops new campaigns and removes timers; honors already applied order snapshots. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U049: Delayed expiry job still shows ended state; duplicate activation no duplicate discount; rejected seller promotion cannot mutate platform coupon; reduced motion countdown accessible. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Promotion funding/tax/legal comparisons remain decision-gated.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
