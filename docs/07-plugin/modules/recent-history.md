# Recently viewed history

Document ID: DOC-U-M005 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U005, both independent stores and generic profiles
Requirements: FR-U045 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Provide optional local/account browsing history and a recent-products rail with explicit privacy controls. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC product references; kernel consent/preferences. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: owner_ref opaque; product_id; optional variation_id; viewed_at UTC; consent_revision; dedup product per owner; maximum item count. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `recent.max_items:integer:20:1..50;recent.expiry_days:integer:30:1..90;recent.account_sync:boolean:false:true-or-false`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: own session/customer only; consent/necessary-storage legal gate applies; no seller access. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Enable only under applicable lawful preference policy; append most recent product, dedup and cap; clear history immediate; optional login sync merges by newest view only after explicit choice. Incognito/unavailable storage simply omits rail.

UI contract: CMP-U008 recent variant; account clear/history preference; no false cross-device claim. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

internal history read/record/clear commands; consent.changed cancels sync; daily expiry cleanup. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Atomic capped list update; viewing same product refreshes timestamp; private cache keyed owner and consent revision; clear increments deletion version to reject late jobs. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Default local-only pending legal classification;30day retention proposal; no raw page history or cross-site IDs; export only when account sync enabled. Noindex personalized data; public page HTML avoids leaking another user rail; accessible ordered product links. Disable stops collection and clears local cache; account retention continues to expiry/erase. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U045: Clear while delayed write pending leaves no resurrected record; guest login without sync consent does not upload; disabled storage still supports ordinary browsing. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No behavioral advertising or cross-site tracking implied.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
