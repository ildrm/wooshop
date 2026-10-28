# Webhooks and external connectors

Document ID: DOC-U-M023 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U023, both independent stores and generic profiles
Requirements: FR-U063 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Integrate external systems through versioned authenticated adapters with explicit authority, mapping, retries and exit plans. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: kernel jobs; provider registry; approved secrets manager; domain services. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: connector UUID/provider/version; direction; allowed events/fields; object mapping unique(site,provider,external_id); signature key reference; event UUID; payload_version; timestamp; delivery state; dedup/replay record. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `connectors.timeout_seconds:integer:10:1..30;connectors.max_payload_kb:integer:256:1..1024;connectors.replay_window_seconds:integer:300:30..900`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce.settings.manage connector config; domain-specific scoped service account for mutations; seller connector limited own org and allowed fields. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Verify signature/rawbody and freshness → dedup envelope → validate version/scope → enqueue → authoritative service mutation → record outcome. Outbound callbacks allowlisted HTTPS, no arbitrary private-network fetch, signed and rate limited. Mapping conflicts quarantine. Provider callbacks do not directly set paid/payout states without reconciliation.

UI: admin delivery/mapping status with masked endpoint; seller sees own status only. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

webhook/job contracts and event catalog authoritative; retry/backoff/dead-letter/replay; export adapter state for exit. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. At-least-once; unique event/provider ID; ordered state monotonic or source version reconcile; no global order assumption; uncertain side effect queries provider. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Field minimization/redaction; secret rotation dual-key overlap limited; raw payload restricted retention reviewed; exports omit secrets. Public catalog connector changes invalidate SEO/feed; private API noindex. Disable stops new outbound optional events; financial callbacks service-mode or verified transfer before revocation. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U063: Invalid signature/replay rejected; out-of-order events reconcile; repeated external ID no duplicate object; SSRF target denied; foreign seller external map denied. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Availability/legal/cost/license verified per provider; adapter interface not universal compatibility.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
