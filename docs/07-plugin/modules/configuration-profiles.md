# Configuration profiles and safe portability

Document ID: DOC-U-M026 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U026, both independent stores and generic profiles
Requirements: FR-U066 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Manage reusable profile/schema/module presets without cross-site personal data or a second global-style engine. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: native WordPress global styles/theme presets; kernel typed settings; profile schemas. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: profile_key/version; module capability requirements; typed settings allowlist; schema references; category mapping; asset manifest/license; source digest; import revision; environment-independent IDs. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `profile.max_import_kb:integer:256:1..256;profile.include_personal_data:boolean:false:false;profile.unknown_keys:enum:reject:reject`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: commerce.settings.manage plus edit_theme_options for style application; seller own storefront fields only; no credential export. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Export approved config/schema/theme tokens and asset references → destination dry-run version/dependency/map validation → preview → authorized atomic revision save → audit/rollback compatible config only. Native core/theme/child/user styles retain their native hierarchy. New site does not inherit customer/seller/order identity or live secrets.

UI: settings search/preview/diff/reset; source of inherited value visible; unavailable module controls disabled with reason. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

settings preview/export and module lifecycle contracts; module.state.changed; bounded import validation job. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Optimistic revision and digest; repeated import returns same applied result; cache key site+style/settings revision; partialassets blockpublish or explicit safe fallback. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

No private data/secrets/consent included; assetlicense preserved; temporarypackage24h proposal; audit retains source/change IDs. Style changes do not emit duplicate schema; sellerbranding constrained. Disable profile manager preserves saved native styles/current settings; export remains recovery path. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U066: Import into thirdstore maps local IDs only; unknownkey/script/URL rejected; unchangedsecond site; missing font fallback; reset removes native override without erasing commerce. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: No arbitrary JS/PHP/CSS; provider configs with legal/financial effect require separate gates.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
