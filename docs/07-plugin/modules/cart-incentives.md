# Advanced cart incentives

Document ID: DOC-U-M019 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U019, both independent stores and generic profiles
Requirements: FR-U059 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Offer truthful thresholds, eligible gifts and shipping incentives while WooCommerce remains discount/tax authority. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: WC coupons/totals/shipping; marketplace allocation/funding policy. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: rule UUID/version; eligible product/seller/mode; threshold_minor/currency; funding party; action coupon/free-shipping/gift; start/end; stacking group; maximum benefit; redemption source. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `incentives.max_rules:integer:5:0..10;incentives.auto_gift:boolean:false:false;incentives.stacking:enum:exclusive-by-group:exclusive-by-group-or-approved-stack`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce.settings.manage with finance-approved funding; customer may accept gift; seller proposals scoped and reviewed. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Evaluate server-calculated eligible subtotal under explicit tax/discount basis; display remaining amount accurately; apply native coupon/shipping method once; gifts explicit choice unless policy clearly allows free optional addition with removal. Mixed-seller coupons allocate discount/funding by authoritative financial policy. Removing eligible items recomputes incentive; no client price trust.

UI: cart banner/progress labelled text; no pressure timer; excludeditems explained; total immediately reflects committed rule. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

native cart calculation integration; internal rule evaluation; no separate discount endpoint; rule change invalidates quotes. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Rule/source version in quote; one benefit identity per cart/rule; checkout revalidation; private cart cache; duplicate callback never issues another gift. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Cart state retention native policy; benefit/redemption audit tied order; no cross-seller cart detail in seller reporting. Noindex cart; promotions landing only approved public claims. Disable new incentives, preserve purchased totals and coupon obligations; no historic commission recalc. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U059: Threshold aftersharedcoupon computed correctly; giftremove honored; two sellers funding conserved; expiredrule cannot persist from cached UI; malformed client discount ignored. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Advanced incentive legal/promotion budgets and supported extension combinations require finance review.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
