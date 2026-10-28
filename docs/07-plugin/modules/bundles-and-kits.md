# Bundles, kits and composite adapters

Document ID: DOC-U-M013 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U013, both independent stores and generic profiles
Requirements: FR-U053 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Present and validate configured kits through a named extension adapter instead of inventing a parallel bundle engine. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: candidate WooCommerce Product Bundles/Composite Products; capability gate; relation/compatibility optional. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: provider namespace/version; configuration_digest; parent product; component IDs/variation/quantity/required flag; provider price/stock capabilities; immutable purchased component snapshot. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `bundle.max_components:integer:20:1..50;bundle.marketplace_enabled:boolean:false:false-until-verified;bundle.show_components:boolean:true:true`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: customer configure public supported products; catalog editor provider-native configuration; seller only authorized offer capability. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Select supported configuration → validate required components/quantities/compatibility → provider quote → allocation/stock validation → purchased configuration snapshot. Child and parent stock ownership declared by adapter. Missing component cannot silently swap. Component refunds use purchased allocation and provider rules; return restrictions disclosed.

UI contract: CMP-U017; per-component choice/status/price contribution; total shape explicit; read-only unsupported state. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

capability/quote service and native provider contracts; configured cart digest; component changes invalidate; provider jobs delegated with obligations preserved. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Configuration hash+offer identity prevents wrong cart merging; server requotes on edit/checkout; stock one authority per managed component. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Order component snapshots follow financial retention; abandoned config proposed24h; no personal free text unless service purpose approved. No false simple price/schema; product page remains accessible explanation. Disable new configurations; existing orders/returns/renewals kept serviceable by provider adapter. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U053: Required component unavailable blocks commit; same digest replay does not double add under idempotency; component partial refund preserves allocation conservation; unsupported multi-seller kit rejected. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Marketplace bundles/composites default unsupported until provider/stock/refund tests pass; candidate extensions not installed or runtime verified.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
