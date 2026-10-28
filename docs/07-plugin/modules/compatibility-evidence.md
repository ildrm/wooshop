# Compatibility rules and evidence

Document ID: DOC-U-M012 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U012, both independent stores and generic profiles
Requirements: FR-U052 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Evaluate product combinations using explicit model/region/firmware-scoped evidence, preserving unknown state. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: product specifications and relationships; domain specialist review. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: evidence UUID; source publisher/URI/document digest; model IDs; hardware revision; region; firmware min/max strings using provider comparator; protocol; ecosystem; tested_context; asserted_date/review_due; confidence verified/conditional/unverified; result compatible/incompatible/unknown. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `compatibility.review_days:integer:180:30..365;compatibility.require_region:boolean:true:true;compatibility.unknown_purchase_policy:enum:warn:warn-or-block-by-category`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: specialist publish evidence; catalog editor draft; seller propose own claims; public evaluate uses approved projection only. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Enter evidence → distinguish protocol from ecosystem → define conditions → specialist approval → evaluate exact context. Matching label alone never proves interoperability. Conditional result lists missing hub/controller/version; conflicting sources produce unknown with review task. Region/firmware omitted yields unknown if rule requires them.

UI contract: CMP-U016; version/date/source visible; claim pending or expired clearly labelled; incompatible blocks unsafe configured purchase according policy. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

compatibility/evaluate contract; compatibility.evidence.changed; scheduled review-expiry jobs. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Deterministic evaluator keyed evidence revision and normalized context; no cross-tenant rule cache; new revision supersedes result; failures return unknown, not compatible. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Public claims and provenance retained with product revision; source documents access rights respected; reviewer identity private; export includes conditions not just boolean. SEO only approved visible claims; language labels localize while model IDs exact. Disable uses unknown/no-assurance messaging and retains evidence export; no automatic unsafe substitution. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U052: Matter/Thread labels alone yield unknown; wrong region/firmware incompatible or conditional; conflicting evidence quarantines claim; cache invalidates on revocation. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No independent certification claim; actual product verification and legal review DEC-08 required.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
