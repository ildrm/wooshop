# Product relationships and accessories

Document ID: DOC-U-M011 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U011, both independent stores and generic profiles
Requirements: FR-U051 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Maintain explicit compatible-with, accessory, replacement and complementary relationships with meaning-specific evidence. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC linked products where equivalent; specs/compatibility for evidence; no duplicated core upsell engine. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: relation UUID; source_product/target_product; type enum accessory/replacement/complement/compatible; directional bool; evidence_ref nullable except compatibility/safety; valid_from/to; status; unique active pair+type+scope. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `relationships.max_per_type:integer:20:1..50;relationships.require_review:boolean:true:true`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: catalog relationship edit scoped; domain specialist approves safety/compatibility relation; sellers propose. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Create directional relation → validate existing products and prevent self-links → attach evidence where required → approve → expose in related section. Complement means merchandising, not compatibility. Replacement requires explicit equivalence scope and lifecycle guidance; expired evidence removes compatibility claim while preserving audit.

UI contract: PDP related cards group by meaning; manuals distinguish required accessory from optional recommendation. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

internal relationship commands; catalog.specification.changed and compatibility.evidence.changed invalidate dependent projections; evidence-expiry scan. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Unique relation version and optimistic locking; bounded traversal depth1 for storefront; cache by relation/source revision; deleted target becomes admin repair item. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Public product links; reviewer personal details private; retain superseded evidence according policy; export graph edges with typed meaning. Avoid duplicate doorway pages; accessible group headings. Disable removes custom relation display but native upsells remain; export preserves evidence. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U051: Selflink/duplicate/cycle-heavy payload rejected or bounded; complement not labelled compatible; expired safety evidence no longer displayed; seller cannot edit other seller draft. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Does not infer safe climbing kits or electrical interoperability.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
