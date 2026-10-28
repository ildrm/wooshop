# Notifications, providers, templates and preferences

Document ID: DOC-U-M016 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U016, both independent stores and generic profiles
Requirements: FR-U056 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Deliver purpose-specific transactional/optional messages through one provider abstraction with suppression and consent checks. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: kernel jobs; WP/WC native mail ownership mapped to prevent duplicate send; approved email/SMS provider. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: template_key/version/locale; purpose transactional/marketing/alert; recipient reference; channel; preference_revision; delivery ID/provider_ref; status queued/sent/delivered/bounced/suppressed/unknown; unsubscribe token hash. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `notifications.marketing_default:boolean:false:false;notifications.delivery_metadata_days:integer:90:1..365;notifications.max_attempts:integer:6:1..10`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: own preferences/signed scoped opt-out; commerce.settings.manage for provider/template changes; sender identity approval; no seller arbitrary bulk recipient access. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Choose event owner/template once; render safe allowlisted variables; check lawful purpose/preference at enqueue and send; send idempotently; process signed delivery callback; reconcile unknown result. Transactional messages include only necessary order data. Marketing opt-in cannot be inferred from purchase. Native WC messages remain sole owner unless explicit adapter suppresses/replaces that same event.

UI: CMP-U028; locale fallback disclosed; accessible plain text/HTML templates; opt-out easy and immediate. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

preferences/alerts endpoints; notification.requested/delivered/failed; consent.changed; send/reconcile/suppression cleanup jobs. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Dedup event+recipient+purpose+template version; provider idempotency if supported; unknown timeout reconcile; at-least-once jobs do not imply exactly-once delivery. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Recipient PII resolved only by sender; logs masked;90day metadata proposal; suppression retained to prevent recontact under approved policy; exported preferences omit token values. Noindex preference routes; notification links authorized/scoped. Disable optional sends; transactional obligations use service mode or approved native fallback without duplicates. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U056: Opt-out while queued suppresses; same event replay sends once where provider contract allows; timeout remains unknown until reconciliation; wrong-org export denied; raw HTML variable escaped. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: SMS/provider costs, geographic availability and legal messaging basis remain DEC-01/04 gates.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.

## Signed unsubscribe integration

Use API-D038, `POST /commerce/v1/notifications/unsubscribe` (OpenAPI path `/notifications/unsubscribe`), for optional-purpose/channel suppression with a scoped signed token and no account login. The email/SMS link opens a noindex confirmation landing whose GET does not mutate state. Follow the [canonical signed unsubscribe contract](../../09-api-integrations/api-and-authentication-strategy.md) for token scope/expiry, harmless replay, generic204 outcomes,503 on failed persistence, rate limits and exclusion of tokens from logs. Suppression commits before acknowledgement and is checked again at send time. Tokens cannot read preferences, resubscribe, replace the recipient or suppress mandatory operational obligations. Native/provider opt-outs feed this same authority. FR-G021 and TEST-G021 cover the endpoint; this module adds no competing unsubscribe route.
