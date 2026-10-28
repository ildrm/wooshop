# Price history and prior-price transparency

Document ID: DOC-U-M017 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U017, both independent stores and generic profiles
Requirements: FR-U057 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Store auditable public offer price observations for charts and lawful comparisons without rewriting order prices. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: authoritative native/offer price events; currency policy; legal prior-price review. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: observation UUID; product/variation/offer; amount_minor integer string; currency/exponent; tax display basis; observed_at UTC; validity range; source revision; correction_of nullable. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `history.window_days:integer:90:30-or90-or180-or365;history.min_observations:integer:2:2..100;history.public_enabled:boolean:false:true-after-legal-review`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: system records source events; commerce.catalog.publish controls public exposure; sellers cannot rewrite past prices. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Capture approved actual prices on change; preserve currency/tax basis and offer identity; chart gaps as gaps; never compare different currencies or sellers without explicit labelled scope. Corrections append records. Prior-price badge policy is separate legal rule, not automatically lowest observed chart price.

UI: CMP-U032; text summary+table alternative; hover points keyboard reachable; insufficient history explanation. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

offer.changed/campaign.changed consumption; internal history query; daily integrity/source audit. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Unique source event per offer/version; ordering by effective time with correction linkage; public cache source version; replay no duplicate observation. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Public commercial data; retention duration DEC-07 and applicable advertising law gate; order snapshots independent; delete seller public chart only under lifecycle policy retaining audit as required. Chart visible facts match any emitted price claims; no guaranteed rich result. Disable public display and new optional observation policy; preserve evidence required for current promotions. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U057: Repeated price event one point; mixedcurrency excluded; missingdays not interpolated as real prices; correction does not mutate earlier order; chart keyboard/table equivalent. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Historic observation is not a legally valid reference-price policy by itself.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
