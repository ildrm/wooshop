# Loyalty and rewards

Document ID: DOC-U-M018 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U018, both independent stores and generic profiles
Requirements: FR-U058 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Provide an auditable points/reward program using native WooCommerce coupon redemption boundaries and explicit liability policy. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: WC orders/refunds/coupons; finance review; no second cash payout ledger. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: reward_account owner; journal entry UUID; order/refund source; points signed integer; state pending/available/redeemed/reversed/expired; rule_version; expires_at; reward coupon reference; balance derived. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `loyalty.enabled:boolean:false:true-after-finance-review;loyalty.pending_days:integer:14:0..90;loyalty.expiry_days:integer:365:30..730`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: own customer balance; finance-approved rules; platform finance adjustments with reason/reviewer; seller cannot mint points. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Earn after verified qualifying payment with pending hold; release per returns policy; redeem through a single WC coupon/discount mechanism; reserve redemption then consume on successful purchase; failure releases. Refund reverses earned points proportionally using original rule; spent-points reversal may create restricted negative points, not unapproved cash debt. Expiry requires notice if policy demands.

UI: CMP-U032; available/pending/expiry distinct; estimate not guaranteed earn; terms link. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

order/refund committed domain events; internal rewards command; release/expiry jobs and reconciliation. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Immutable points journal; source-event uniqueness; atomic reservation prevents double spend; balance cache derived/rebuildable; no direct balance overwrite. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Account-linked benefits; legal/liability retention DEC-03/07; erasure reconciles outstanding obligations with pseudonymization; no marketing consent inferred. Private balance noindex; accessible points/currency distinction. Disable new earning/redemption while servicing existing issued coupons/liability or approved migration. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U058: Duplicate purchase event earns once; simultaneous redemption cannot overspend; refund reverses original-rule earn; afterredemption refund preserves auditable adjustment; denied seller mint. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: Not stored money or transferable wallet; legal/tax/funding rules unresolved before activation.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
