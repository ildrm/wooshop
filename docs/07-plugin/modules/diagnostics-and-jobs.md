# Diagnostics and background-job administration

Document ID: DOC-U-M022 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U022, both independent stores and generic profiles
Requirements: FR-U062 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Expose actionable module health and safe job supervision without exporting secrets or allowing arbitrary code execution. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: kernel lifecycle/job envelope; operations monitoring. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: diagnostic snapshot UUID/version; check_id/status/count/time; job UUID/type/module/source/ref; lease/attempt/error code; operator action/reason; report digest/expiry. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `diagnostics.report_hours:integer:24:1..72;jobs.max_retries:integer:5:0..10;jobs.lease_seconds:integer:120:30..600`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce.settings.manage diagnostics; operational replay capability and object scope; finance jobs require finance authorization; sellers own import status only. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Run read-only health checks → display failure owner/runbook → inspect redacted job → retry only eligible handler → preserve idempotency/reconciliation. Poison job manual replay demands reason and source correction; cancel cannot abandon financial obligation. Diagnostic export preview shows exact fields.

UI: status cards/table with severity text; no generic Execute command field; missingprovider has install/verification guidance. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

diagnostics/module lifecycle contracts; module.state.changed; queue age/poison monitoring. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Snapshot immutable; replay job action idempotent by operator request; concurrency lease and fencing revision; logs append outcome; no global sensitive cache. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Report24h signed owner download; log redaction and approved retention; no raw headers/tokens/payment payload; source audit retained where required. Private noindex; keyboard table and screen-reader status. Disable UI leaves critical worker/service-mode operations; removal needs external monitoring handoff. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U062: Retry race yields one valid lease; forbiddenhandler rejected; redactedreport contains no secret; seller cannot read platform poison queue; pendingpayout cannot be discarded. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Metrics are proposed operational visibility, not demonstrated SLO compliance.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
