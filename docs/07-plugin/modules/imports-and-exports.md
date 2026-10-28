# Bulk imports and exports

Document ID: DOC-U-M021 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U021, both independent stores and generic profiles
Requirements: FR-U061 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Provide bounded, reviewed data movement with per-row diagnostics and reversible staging rather than unrestricted file execution. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: kernel jobs; specs/canonical matching; WC supported APIs; security scanning. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: import UUID; owner/seller scope; schema_version; file digest; source provider; mapping version; staged row index/digest; status validation/ready/running/partial/failed/completed; error codes; checkpoint; export expiry. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `import.max_rows:integer:10000:1..50000;import.chunk_rows:integer:100:10..500;export.expiry_hours:integer:24:1..72`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce.catalog.submit for seller own records; commerce.catalog.publish for canonical import; export capability plus field/object scope; financial export step-up. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Upload permitted CSV/JSON shape → quarantine/content validation → parse size/field bounds → preview mappings/creates/updates/conflicts → approve → chunk supported domain mutations → reconcile counts/errors → download result. Formula-like cells neutralized in human spreadsheet exports; archive paths/URLs never trusted. Import does not approve claims or change financial history.

UI: admin table with row errors and downloadable safe report; resumable progress not fake percent when total unknown. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

imports contract; import.completed; chunk/checkpoint/export jobs. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Idempotent source row key/digest; expected revisions prevent overwriting newer edits; retry resumes checkpoint; partialstatus honest; exports snapshot scope and recheck authorization on download. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Temporaryfiles proposed24h quarantine/results7days; secrets/customer data excluded from style/profile exports; customer/finance export separate minimization gate. Imported product publication triggers SEO index/feed lifecycle; import screen noindex. Disable pauses safechunks and preserves result/export access until expiry; delete only owned temporary files. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U061: Malformed row does not execute formula/script; foreignseller mapping rejected; duplicatefile resume no duplicate product; interruptedchunk recover; permission revoked before download denies. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: No generic raw SQL/database export; large volumes require capacity assessment.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
