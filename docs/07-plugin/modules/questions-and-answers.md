# Product questions and answers

Document ID: DOC-U-M007 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U007, both independent stores and generic profiles
Requirements: FR-U047 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Host moderated product Q&A separately from native product reviews and seller service feedback. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC product ID; WP user; moderation and notification services. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: question UUID; product_id; author_ref private; question plain text≤2000; status pending/published/rejected/withdrawn; answer UUID/text≤4000; author_type staff/seller/customer; provenance optional. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `qa.max_question_chars:integer:2000:1..2000;qa.allow_guest:boolean:false:true-or-false;qa.rate_per_day:integer:5:1..20`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: customer own question; verified guest if enabled; seller answer only own associated product scope; moderator publish; author cannot self-approve. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Submit sanitized text → spam/rate checks → pending → moderator review → published question → separately reviewed answer. Technical/safety answers require specialist evidence. Abuse report opens review without immediately deleting legitimate content; edits return to moderation.

UI contract: CMP-U019; pending visible only to author; public author pseudonym and role, no email; rejection reason private. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

questions contract; internal moderation/answer commands; notification.requested for opted-in answer updates; spam review queue. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Client idempotency prevents duplicate posts; optimistic revision on moderation; public cache purges on publish/unpublish; no global private queue cache. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Email/user reference minimized; rejected content proposed90days; approved content survives account erase with anonymization where lawful; legal retention gate. Q&A does not automatically become eligible FAQ markup; text headings/disclosures accessible. Disable stops new submissions but retains approved read-only archive/export. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U047: Stored markup displayed as safe text; guest flood rate limited; seller cannot publish own unsupported answer; rejected question absent public API/search. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Not a substitute for qualified safety advice; moderation needs staffing.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
