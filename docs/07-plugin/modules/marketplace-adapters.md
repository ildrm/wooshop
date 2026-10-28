# Marketplace capability and portal adapter

Document ID: DOC-U-M024 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U024, both independent stores and generic profiles
Requirements: FR-U064 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Connect seller organizations, offers, allocations and obligations to the companion plugin while exposing consistent storefront/portal contracts. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: marketplace core/approved provider; WC customer order; inventory and settlement authority ADRs. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: native canonical product/variation IDs; seller/offer/allocation UUID; provider mapping namespace/version; role membership; public seller projection; entitlement capabilities; terms snapshot. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `marketplace.enabled:boolean:false:true-after-DEC-gates;marketplace.catalog_mode:enum:shared-plus-owned:shared-plus-owned;marketplace.complex_types:boolean:false:false-until-verified`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce storefront/catalog/offer/allocation/finance scoped actions; active organization membership; separation of moderator and seller self-approval. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Onboard/review seller → map owned listing or match canonical → publish eligible offer → quote selected variation/offer → immutable orderallocation → fulfill/refund/settle. Preferred selection transparent and no silent seller change. Suspension blocks new purchases but retains limited obligation access. Provider offer-as-product projection never becomes duplicate canonical authority.

UI: CMP-U014/U026/U027; seller workspace rejects foreign scope; customer sees seller/condition/warranty/shipment before commit. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

marketplace OpenAPI, Store API commerce extension, offer.changed/module.state.changed; obligation jobs per domain spec. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. WC native mode retains price/stock; marketplace offer adapter authoritative with single decrement; persisted WC shells failclosed when adapter missing; exactly-once financial effect via dedup+reconciliation not transport claim. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Seller customer access only own fulfillment fields; balances/reporting private; audit/financial retention legal gate; export mappings/snapshots for provider exit. Canonical shared-product and seller SEO policy; accessible offer tables. Disable new marketplace sales, service existing obligations, external recovery on forced removal. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U064: Same variation two sellers remains two cart lines; foreign allocation denied; seller suspension before checkout blocked and after purchase obligations retained; pluginloss direct WC add/checkout fails. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Complex multi-seller subscriptions/bookings/bundles disabled until verified; custody/fund flows require DEC-01..05.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
