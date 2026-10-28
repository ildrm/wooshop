# Wishlists and guest-account merge

Document ID: DOC-U-M003 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U003, both independent stores and generic profiles
Requirements: FR-U043 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Store explicit saved shopping intentions without reserving stock or promising price. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC public product references; kernel session; notifications separate opt-in. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: list_id UUID; owner_kind guest/account; owner_ref opaque; items unique(product_id,variation_id nullable,configuration_digest nullable); visibility private; item_added_at; merge_token single-use. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `wishlist.max_items:integer:100:1..200;wishlist.guest_days:integer:30:1..90;wishlist.share_enabled:boolean:false:true-or-false`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: current customer ownership or opaque guest session; public sharing requires explicit scoped revocable token; staff access only support authorization. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Add/remove records current product reference; displayed offer/price refreshed on view. Login offers deterministic union merge, retaining earliest saved time and explicit variation/configuration. Merge never transfers tracking/marketing consent. Deleted products become removable tombstones; selected offer not silently replaced.

UI contract: CMP-U022, SCR-U024; saved state and capacity/error message; guest storage disclosure. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

wishlist/items endpoints; wishlist.merged event after committed merge; expiry cleanup job. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Idempotent item upsert and merge key; list revision for changes; owner-scoped private cache only; no global list count leak. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Guest30days/account until user erases or approved inactivity policy; share tokens expire30days/revocable; erase removes lists except minimal security audit. Private/shared lists noindex default; toggle button pressed state. Disable hides entry points, offers data export and retains until retention; uninstall requires explicit erase choice. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U043: Replay login merge twice gives one union, no duplicate item; foreign account/list ID404; expired guest list not revived; withdrawal blocks alert subscription. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Sharing default off; saved product is not a stock hold or locked quote.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
