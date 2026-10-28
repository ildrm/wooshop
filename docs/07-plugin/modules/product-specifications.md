# Product specifications and units

Document ID: DOC-U-M001 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U001, both independent stores and generic profiles
Requirements: FR-U041 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Maintain versioned category-specific typed specifications without duplicating WC product identity. Catalog editors submit values; domain specialists approve safety claims. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: WC products/attributes; no alternate product or stock system. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: schema_key unique string1..64; schema_revision positive integer; product_id WC ID; field_key unique per schema; typed value decimal/string/enum/bool; unit enum; source_uri optional approved URI; evidence_revision/date required for safety claims; verified_by private user reference. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `specs.max_fields:integer:120:1..200;specs.require_evidence:boolean:true:true`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: commerce.catalog.publish for schemas; edit_product on exact product for values; seller catalog membership can propose only own submission. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Draft schema → validate unit/dimension → review migration impact → publish immutable schema revision → validate product values → specialist approval. Convert compatible units with declared precision; store original value/unit and normalized value. Missing means unknown; zero is a real value. Incompatible units fail validation. Facet/compare uses normalized typed values only.

UI contract: CMP-U015, SCR-U006/U007; editor shows field help/unit/source and missing required claims. Public projection contains only approved values. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

specification-schemas and product specification endpoints; catalog.specification.changed after commit; index rebuild job per source revision. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Schema updates use revision conflicts; uniqueness product/schema/field; source-versioned cache invalidated on approved value change; failed rebuild keeps prior approved version and marks stale. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Public technical facts retained with product history; private reviewer references minimize exposure; safety evidence retention DEC-07/08; export includes schema+units+provenance. Product SEO consumes approved facts only; units translated as labels, exact values accessible. Disable removes custom facets but retains read-only spec export; never erases native attributes. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U041: Verify normalized1000g=1kg; reject length-as-weight, missing evidence and foreign seller edits; old product value remains attributable after schema revision. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: No inferred safety/compatibility; unusual measurement conversions require domain-approved rules.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
