# Consent-aware analytics and conversion

Document ID: DOC-U-M020 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U020, both independent stores and generic profiles
Requirements: FR-U060 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Emit one controlled analytics taxonomy for discovery and commerce without duplicating native/provider events. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: operations event taxonomy; consent module; WC verified transactions; provider adapter. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: event_id UUID; canonical event_name/version; occurred_at; site; anonymous/session pseudonym where permitted; consent_revision; product/variation/offer IDs; currency/value_minor; allocation references minimized; dedup source. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `analytics.enabled:boolean:false:true-after-privacy-review;analytics.raw_query:boolean:false:false;analytics.client_retention_days:integer:30:1..90`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: analytics configuration scoped administrator; access aggregates by role; sellers only own authorized allocations; no arbitrary client purchase events. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Map UI interaction labels to canonical events; send optional discovery only after applicable consent; server emits purchase/refund once per authoritative transaction effect. Strip email/address/name/search free text. Refund signs/value basis follow canonical taxonomy; totals across seller allocations reconcile without doublecounting parent+suborders. Withdrawal stops optional client collection and purges queues.

UI: No UI behavior requires tracker; preferences accessible; metrics show denominators/time/currency, not invented lift. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

canonical events in operations/event catalog; internal transport adapter; consent.changed; dedup/reconciliation/export aggregation jobs. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. event_id plus authoritative effect reference dedup; retries at-least-once; source order independent; server financial event cannot be forged through public pixel. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Pseudonymization not anonymity claim; retention approved by DEC-07; no cross-site identity; consent and accounting purposes separate. Analytics never changes canonical/schema content. Disable optional trackers without stopping authoritative order accounting; preserve lawful audit obligations. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U060: Repeated thankyou view no duplicate purchase; client forged price ignored; declinedconsent no optional event; two-seller order counted once at parent with allocations summing correctly. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: No measured conversion baselines; advertising providers and consent legal basis pending.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
