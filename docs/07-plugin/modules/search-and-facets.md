# Search, facets, synonyms and ranking

Document ID: DOC-U-M002 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U002, both independent stores and generic profiles
Requirements: FR-U042 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Provide public discovery from approved product/offer projections with category-specific facets and deterministic ranking. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC native search fallback; specs module for typed facets; optional external index behind adapter. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: query normalized string≤120chars; locale; category WC ID optional; facet field/value typed; synonym set version; rank rule version; index source revision; public offer eligibility projection. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `search.max_query:integer:120:1..200;search.max_facets:integer:12:1..20;search.synonyms_revision:integer:1:positive`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: commerce.settings.manage for synonyms/ranking; public queries rate limited; no private seller/customer index fields. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Normalize Unicode and configured Persian variants for matching while retaining original query for display; do not change exact model identifiers. Rank exact model/name before approved synonym and descriptive matches; paid placements labelled separately. Facets count within current context; stable tie-break product ID. Explicit mobile Apply produces normalized URL; Back restores filters.

UI contract: CMP-U004/U020/U021; results server-rendered; empty result suggestions never pretend to match. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

search/suggestions read contract; catalog.specification.changed and offer.changed trigger reindex; scheduled full reconciliation compares source revisions. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Read cache keyed site/locale/currency/public eligibility/category/query/filter/sort; max30s suggestions/60s catalog proposal; stock revalidated at purchase. Newer index revisions supersede older jobs. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Do not persist raw personal search text; anonymized aggregate terms only after privacy review; private search logs off default. Index deletion follows product publication changes. SEO facet rules govern indexation; searchnoindex; announced result count once. Disable restores WC search and communicates reduced facets; delete only reconstructable indexes. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U042: Search Persian/Latin model variants; filter weight by normalized unit; verify unpublished/foreign-private records absent; external index outage produces native results and labelled reduced filters. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No claim of perfect linguistic normalization or provider compatibility; relevance needs curated fixtures and human review.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
