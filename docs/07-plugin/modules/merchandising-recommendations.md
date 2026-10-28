# Merchandising and recommendations

Document ID: DOC-U-M010 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U010, both independent stores and generic profiles
Requirements: FR-U050 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Rank editorial/product-relationship recommendations with transparent fallbacks and no unapproved tracking. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC native collections for new/featured/bestselling; relation/spec modules; optional external recommender gated. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: placement key; curated product IDs; eligibility filters; rank version; optional consented segment; source reason enum curated/related/recent/popular; generated_at. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `recommendation.limit:integer:10:1..20;recommendation.personalized:boolean:false:true-or-false;recommendation.sponsored_label:boolean:true:true`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: merchandiser controls public placements; user consent governs personalization; sellers cannot promote themselves without approved campaign. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Choose placement and strategy → exclude unpublished/ineligible/unsupported products → rank stable tie-break → label sponsored results → present source rationale where useful. Default curated/related; native sales ranking uses authorized aggregate WC data, no customer identities. A recommendation never asserts equipment safety or ecosystem compatibility.

UI contract: CMP-U008/U009; empty placement omitted; manual accessible rail; links are normal product URLs. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

internal placement read/config commands; catalog/offer/campaign events invalidate; periodic aggregate refresh. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Cache public by site/locale/placement/source revisions; personal cache owner+consent scoped; fallback to curated on provider failure; no unlimited recursive relation loops. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

No cross-site profiling; personalized default off; aggregate retention approved by privacy owner; clear personal recommendations on withdrawal. Public links support discovery; no duplicate schema; accessible headings. Disable removes optional placement; native collections remain. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U050: Unpublished product never recommended; sponsored flag remains visible; external provider outage yields curated list; withdrawal removes personal cache. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No measured conversion lift or machine-learning quality claim; external service requires exit/export contract.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
