# Installation and service add-ons

Document ID: DOC-U-M014 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U014, both independent stores and generic profiles
Requirements: FR-U054 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Attach optional installation/service choices with explicit provider, territory, scheduling and cancellation terms; never preselect paid extras. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: candidate WC Bookings/service adapter; Woo product/order; marketplace capability gate. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: service product_id; provider_id; territory rule version; location input minimized; slot UTC/timezone optional; duration minutes positive; prerequisites; price_shape; accepted_terms_revision. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `service.default_selected:boolean:false:false;service.marketplace_enabled:boolean:false:false-until-verified;service.quote_expiry_minutes:integer:15:1..30`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: catalog administrator configures provider-approved service; customer selects; seller fulfillment only own allocation. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Check territory/prerequisites before quote; show optional choice unchecked; disclose price/timezone; reserve provider slot only under supported contract. Cancellation/reschedule revalidates capacity and price with explicit consent. Service failure does not silently remove installed equipment order obligations.

UI: CMP-U017, SCR-U013; accessible date/time field alternative; no endless unavailable calendar. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

provider-native booking/quote contracts; internal service eligibility command; booking state changes trigger transactional notice; slot expiry reconciliation. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Provider owns slot capacity; one booking idempotency key/order allocation; late callback reconciles before retry; personalized location not in public cache. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Service address only fulfillment purpose; retention tied order/legal policy; abandoned location proposed24h; export only permitted customer/own seller scope. Service terms visible; schema eligibility reviewed separately. Disable new add-ons while keeping reschedule/cancel obligations serviceable. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U054: Outside-territory rejected; paid extra never preselected; duplicate callback one booking; expired slot requires reselection; unsupported multi-seller service blocked. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Provider availability, field technician qualifications and legal terms require human verification.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
