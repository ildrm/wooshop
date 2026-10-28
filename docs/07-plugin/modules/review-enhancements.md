# Review enhancements and seller reputation separation

Document ID: DOC-U-M008 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U008, both independent stores and generic profiles
Requirements: FR-U048 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Enhance WooCommerce product reviews with verified-purchase labels, media moderation and helpfulness without a duplicate rating engine. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC native product reviews authoritative; marketplace seller service score separate. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: native_review_id; verified_purchase derived entitlement; moderation_flags enum; media_refs approved; helpful_vote unique(review_id,owner_hash); seller_service_feedback references allocation separately. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `reviews.media_enabled:boolean:false:true-or-false;reviews.page_size:integer:10:5..20;reviews.min_seller_sample:integer:5:1..100`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: customer eligible review; own edit subject moderation; moderator approval; seller response scoped; no seller rating edits. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Native review submission/review state retained. Verified label derives from actual permitted purchase evidence, never self-assertion. Media queued/scanned; helpful votes deduped/rate limited; suspected fraud quarantined. Product rating aggregates only eligible approved native product reviews; seller service score has distinct denominator/time window.

UI contract: CMP-U018/U026; show0reviews rather than invented score; report action; media captions and privacy warning. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

native review contracts plus internal enhancement metadata; moderation notification events; periodic abuse signals recompute. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Review approval invalidates aggregate/cache; vote uniqueness and replay-safe flag changes; reconciliation compares native review aggregate and projection. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Pseudonym public; purchase linkage private; remove personal media on erasure when lawful; moderation evidence retention gate; no exposed order numbers. Only approved real reviews feed schema owner; no manufactured ratings. Disable keeps WC reviews intact, removes enhancements/service score surface, preserves moderation state. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U048: Unapproved review excluded from count/schema; fake verified flag ignored; seller service rating not merged with product stars; duplicate helpful vote counts once. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No guarantee fraud detection catches all abuse; native moderation compatibility must be verified.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
