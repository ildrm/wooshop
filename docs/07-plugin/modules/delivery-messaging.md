# Delivery messaging and eligibility

Document ID: DOC-U-M015 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U015, both independent stores and generic profiles
Requirements: FR-U055 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Present destination-aware delivery estimates without becoming a second shipping/tax engine. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: WC shipping zones/methods; marketplace shipping allocation; carrier adapter. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: destination coarse region or normalized postcode; fulfillment origin; method_id; estimate min/max dates; cutoff timezone; source timestamp; holiday calendar revision; quote validity. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `delivery.location_prompt:boolean:false:true-or-false;delivery.estimate_cache_minutes:integer:15:1..30;delivery.show_confidence:boolean:true:true`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: public coarse estimate; own cart/address exact quote; merchant shipping capability; seller owns only approved origin/profile. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Request coarse destination optionally; calculate supported shipping estimate with source assumptions; label estimated vs committed promise. Final checkout server quote controls shipping charge. Split-seller cart shows separate packages/date ranges; virtual items omit shipping. Backorders/preorders carry explicit policy, never inferred stock certainty.

UI: header location CMP-U002; offer/purchase CMP-U014/U013; no address leak in header shared cache. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

shipping provider/WC quote contracts; offer/origin change invalidates; tracking handled marketplace; schedule calendar refresh. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Cache coarse nonpersonal region/site/method/source revision; exact addresses/session quotes private; stale estimate labelled unavailable; duplicate quote reads no effect. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Minimize postcode precision before purchase; no background geolocation; preference expiry proposed30days; order address under financial retention policy. Visible shipping information consistent with SEO/feed; date/time localized with timezone. Disable custom estimates keeps native checkout shipping selection. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U055: Digital order has no delivery prompt; two origins show two estimates; carrier timeout says unavailable; invalid postcode has inline error; seller cannot edit another origin. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: No guaranteed arrival date without provider-backed commitment; business calendars/jurisdictions unselected.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
