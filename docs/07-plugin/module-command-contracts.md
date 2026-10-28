# Module command and integration contracts

Document ID: DOC-U-23 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending integration approval | Owner: API/Integration Engineer
Reviewers: Application Security Engineer; WooCommerce Extension Developer; QA/Test Engineer
Scope: typed internal command/query contracts and mapping to exposed APIs
Requirements: FR-U028; FR-U041–FR-U066 | ADRs: ADR-02; ADR-04; ADR-16
Dependencies: [OpenAPI](../09-api-integrations/openapi.yaml); [module catalog](module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section7; [source register](../00-governance/sources.csv)

## Boundary and common envelope

Internal namespace `commerce.domain.v1` is a proposed in-process service interface, not an HTTP route or runtime implementation. Consumer is an authorized module/controller/worker; producer is the owning domain service. Exposed routes below resolve against `/commerce/v1` in the OpenAPI. Native Store API/WordPress/provider calls remain separate contracts. A module may not publish an extra REST route simply because an internal command exists here; external exposure requires a versioned OpenAPI change and resource-authorization review.

Every command envelope contains request UUID, site resolved from execution context, actor/session reference resolved by authentication, correlation/causation IDs, command version, expected resource revision and typed payload. Actor, site, owner, approval and totals are never accepted as authoritative client fields. Query returns only the authorized public/own-scope projection. Extension IDs are UUIDs; core IDs are positive integers; times ISO8601 UTC; money is integer minor-unit string plus currency and exponent. Unknown fields are rejected. Optional means nullable/omittable explicitly; otherwise fields are required.

Common result: success with resource/revision, validation_failed with field codes, unauthenticated, forbidden/not_found (nonenumerating private object), conflict, rate_limited, provider_unavailable, unsupported_capability or operation_pending. HTTP adapters use400/401/403or404/409/429/503/422/202 respectively as defined in the OpenAPI; internal results have no HTTP status. Never expose exception/secret payloads. A mutation idempotency key is scoped to actor/site/operation and payload digest; conflicting reuse yields conflict. Financial/provider keys remain for the obligation retention; optional commands minimum24h pending approved retention. Page queries use bounded opaque cursor, default20/max100, stable ID tie-break; public cache excludes private data. Proposed default limits: public reads60/min per bounded client token; customer mutation20/min; seller write60/min; bulk export2/hour; configuration10/min, with adaptive abuse review. Exact endpoint limits in the OpenAPI win.

At-least-once jobs validate current scope/consent and dedup business effect; no global event-order assumption. Transient provider timeout uses shared retry/reconciliation; uncertain effects remain unknown/pending, never blindly repeated. Additive optional fields retain v1 compatibility; meaning/type/auth changes need v2, migration window and contract tests. Each row refines one module's FR-U041–066 and paired TEST-U041–066.

## MOD-U001 / specifications.publish

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: commerce.catalog.publish; reviewed canonical product.

Input schema: product_id WC ID; schema_revision integer>=1; values typed map of declared field keys; evidence_refs UUID list; expected_revision. Output schema: specification_revision integer; approved_fields list; withheld_fields with reason.

Validation and failure: Reject unknown keys/units/missing claim evidence; no seller self-approval. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /products/{product_id}/specifications; /specification-schemas. Events/jobs: catalog.specification.changed; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U041, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U002 / search.configure

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: commerce.settings.manage; search policy.

Input schema: synonym_set list of {canonical,aliases}; ranking enum exact-model/name/synonym/description; expected_revision. Output schema: rule_revision; index_refresh_operation.

Validation and failure: Max1000synonym groups/20aliases each; reject cycles/emptyalias; not user query log export. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /search/suggestions is public read only; configuration internal. Events/jobs: catalog projection refresh; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U042, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U003 / wishlist.merge

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: authenticated customer + proof of same guest session.

Input schema: guest_token opaque single-use; expected_account_revision; merge_policy enum union. Output schema: list_ref; item_count; duplicate_count; merge_revision.

Validation and failure: Cap200items; dedup product/variation/configuration; no consent transfer. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /wishlists/me and items paths; merge internal adapter. Events/jobs: wishlist.merged; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U043, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U004 / comparison.update

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: own customer/session.

Input schema: product_ids WC ID list2..4; differences_only bool; display_units dimension map; expected_revision. Output schema: comparison_ref; normalized rows with value/unknown/noncomparable flags.

Validation and failure: Reject private/unpublished products; incompatible units never coerced. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /comparisons. Events/jobs: optional canonical discovery event only; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U044, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U005 / history.record-or-clear

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: own session; applicable storage preference.

Input schema: action record/clear; product_id for record; viewed_at server; expected_deletion_revision. Output schema: item_count; deletion_revision.

Validation and failure: Cap50; server time only; reject delayed write after clear; no cross-site sync. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; no extra HTTP route. Events/jobs: none; consent changes govern collection; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U045, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U006 / alerts.subscribe-or-suppress

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: verified own recipient/customer or signed scoped suppression token.

Input schema: product_id; variation_id optional; offer_id optional; trigger stock/price; target_minor optional string; currency/exponent; purpose preference revision. Output schema: subscription_id; pending/active/suppressed state; expiry.

Validation and failure: Max50default; price target>=0; server verifies scope/consent; send-time eligibility recheck. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /alerts; /preferences/me. Events/jobs: notification.requested; consent.changed; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U046, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U007 / questions.moderate-answer

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: moderator; seller can propose own answer only.

Input schema: question_id; answer_text<=4000; evidence_refs optional; action approve/reject/request-revision; expected_revision. Output schema: question_revision; public_state; private_reason.

Validation and failure: No executable markup; technical safety evidence required; author cannot self-approve. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /questions for submission; moderation internal. Events/jobs: notification.requested; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U047, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U008 / reviews.enhance

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: native review ownership/moderation plus scoped policy.

Input schema: native_review_id; approved_media_ids list<=5; moderation_action; expected_revision. Output schema: native_review_state; enhancement_revision.

Validation and failure: Verified-purchase is derived not accepted input; no direct aggregate mutation. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native WC review contract; internal enhancement. Events/jobs: native review change projection invalidation; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U048, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U009 / campaigns.schedule

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: commerce.settings.manage + commercial funding approval.

Input schema: starts_at/ends_at UTC; timezone; native_coupon_refs; eligibility rule; badge_text<=40; expected_revision. Output schema: campaign_id/revision; scheduled state; activation_operation.

Validation and failure: End>start; no past fake countdown reset; coupon authority native; publication approval separate. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; no extra HTTP route. Events/jobs: campaign.changed; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U049, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U010 / merchandising.place

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: commerce.catalog.publish; approved placement.

Input schema: placement_key; source enum; curated_product_ids<=20; sponsored bool; expected_revision. Output schema: placement_revision; rejected_product_ids; public projection.

Validation and failure: Only approved public products; mandatory sponsored label; no implied compatibility. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; no extra HTTP route. Events/jobs: placement projection invalidated; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U050, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U011 / relationships.propose-publish

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: catalog canonical publish or own seller proposal.

Input schema: source_product_id; target_product_id; type; evidence_refs; valid_from/to; expected_revision. Output schema: relation_id/revision; pending/published state.

Validation and failure: No selfrelation; compatible/safety needs evidence; bounded traversal1. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; no extra HTTP route. Events/jobs: compatibility.evidence.changed when applicable; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U051, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U012 / compatibility.evaluate

Provider/consumer: module domain service ← authorized caller; kind `QUERY`. Authentication/authorization: public approved facts; context privacy minimized.

Input schema: product_ids; model/hardware/region/firmware context; configuration_digest. Output schema: result compatible/conditional/incompatible/unknown; conditions; evidence_refs; evidence_revision.

Validation and failure: Missing required context unknown; conflict unknown; unknown model not guessed. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /compatibility/evaluate. Events/jobs: optional discovery event mapped only; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U052, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U013 / bundle.quote

Provider/consumer: module domain service ← authorized caller; kind `QUERY`. Authentication/authorization: own cart session; approved provider capability.

Input schema: provider_key/version; product_id; component IDs/quantities/options; offer refs. Output schema: configuration_digest; quote revision/expiry; components; money fields; allowed_actions.

Validation and failure: Provider product capability must be verified; multi-seller combinations disabled by default. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native provider/Store API adapter; no custom order bypass. Events/jobs: no committed purchase event on quote; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U053, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U014 / service.quote-reschedule

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: own booking/order entitlement; approved provider.

Input schema: service_id; territory; slot UTC/timezone; existing_booking_ref optional; expected_revision. Output schema: pending/confirmed/needs-reconfirmation; provider_ref; money/terms snapshot.

Validation and failure: Provider capacity authority; explicit price/time consent; no automatic paid add-on. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native verified service provider adapter. Events/jobs: transactional notification request; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U054, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U015 / delivery.estimate

Provider/consumer: module domain service ← authorized caller; kind `QUERY`. Authentication/authorization: public coarse destination or own exact session.

Input schema: product/offer refs; coarse_region optional; cart session scope for exact quote. Output schema: min/max_date; timezone; source_time; assumptions; unavailable reason.

Validation and failure: No public address echo; digital skips; final shipping quote native/WC authority. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native shipping adapter; no new public route. Events/jobs: none; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U055, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U016 / notifications.request

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: trusted domain event owner; recipient scoped.

Input schema: template_key/version; purpose; channel; recipient_ref; source_event_id; safe_variables declared by template. Output schema: queued/suppressed/unknown result; delivery_ref.

Validation and failure: Allowlisted template variables; preference checked twice; duplicate native event owner rejected. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; preferences through /preferences/me. Events/jobs: notification.requested/delivered/failed; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U056, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U017 / price-history.read-correct

Provider/consumer: module domain service ← authorized controller or worker; kind `QUERY/COMMAND`. Authentication/authorization: public approved read; platform correction capability.

Input schema: offer_id; currency/exponent; time_window; correction_source_ref and amount for authorized correction. Output schema: observations [{amount_minor,effective_at,source_revision,correction_of}]; gaps.

Validation and failure: No mixing sellers/currencies/taxbasis; correction appends; legal display gate. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal; no extra HTTP route. Events/jobs: offer projection invalidation; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U057, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U018 / rewards.reserve-adjust

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: own customer redemption; finance adjustment dual review.

Input schema: account_ref derived actor; points positive int; source_order/refund; rule_revision; adjustment_reason if privileged. Output schema: journal_ref; pending/available/held points; coupon_ref optional.

Validation and failure: Atomic no overspend; immutable journal; negative reversal controlled; no cash wallet. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal WC coupon adapter; no generic balance endpoint. Events/jobs: financial audit; canonical purchase/refund consumption; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U058, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U019 / incentives.evaluate

Provider/consumer: module domain service ← authorized caller; kind `QUERY`. Authentication/authorization: own cart session; configured approved rules.

Input schema: cart_ref fromsession; applicable rule revisions. Output schema: eligible benefits; remaining_threshold_minor; funding allocation; native coupon refs.

Validation and failure: Use authoritative totals; ignore client amounts; no gift preselection; recompute at checkout. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native WC cart calculation integration. Events/jobs: no purchase event on evaluate; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U059, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U020 / analytics.emit

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: consent-aware client allowlist or trusted server effect.

Input schema: canonical event/version; event_id; authorized opaque refs; amount_minor/currency/exponent when defined; consent_revision. Output schema: accepted/suppressed/duplicate; no personal payload echo.

Validation and failure: Client cannot emit purchase/refund; rawquery/name/email/address dropped; UI aliases mapped once. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: internal provider adapter; no unrestricted tracking endpoint. Events/jobs: single operations taxonomy; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U060, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U021 / imports.stage-run

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: catalog submit/publish or specific export authority.

Input schema: schema_version; approved file_ref/digest; scope; mapping; expected_revisions; dry_run flag. Output schema: operation_id; staged counts; rowerrors; checkpoint; completed/partial state.

Validation and failure: File/row/field limits; no raw SQL; formulas escaped on humanCSV export; foreign scope rejected. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /imports; /operations/{operation_id}. Events/jobs: import.completed; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U061, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U022 / jobs.inspect-replay

Provider/consumer: module domain service ← authorized controller or worker; kind `QUERY/COMMAND`. Authentication/authorization: commerce.settings.manage + relevant obligation authority.

Input schema: job_id; expected_lease_revision; replay_reason; source_correction_ref. Output schema: redacted status; newattempt or conflict; correlation.

Validation and failure: Only registered handler; no arbitrary code; cannot cancel owed financial effect. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /diagnostics; operation status; replay internal. Events/jobs: module.state.changed only on state transition; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U062, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U023 / connectors.deliver-reconcile

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: signed provider/service account; resource/field allowlist.

Input schema: provider/event_id; payload_version; signature verified envelope; mapping namespace; source revision. Output schema: accepted/duplicate/quarantined; receipt_ref.

Validation and failure: Payload<=256KBdefault; HTTPS allowlist; replay window; no private network fetch; no raw paid status trust. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: webhook contracts; not arbitrary module method dispatch. Events/jobs: domain event after authoritative commit; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U063, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U024 / marketplace.select-offer

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: own cart/session; eligible seller/offer capability.

Input schema: WC product/variation; offer_id/version; configuration_digest; quantity. Output schema: validated quote or conflict; immutable accepted terms at purchase.

Validation and failure: Failclosed native shells if adapter absent; no silent seller change; one stock authority. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: Store API extensions.commerce; /offers read. Events/jobs: offer selection UI canonical event; confirmed cart effect server; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U064, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U025 / media.review

Provider/consumer: module domain service ← authorized controller or worker; kind `COMMAND`. Authentication/authorization: native upload + own asset; moderator publish.

Input schema: attachment_id; media_role; alt/caption; license/source; scan result from trustedworker; expected_revision. Output schema: pending/approved/rejected; rendition refs; missing asset fallback.

Validation and failure: Actual MIME/content/size validation; privateownership; SVGfont/executable restrictions; source claim review. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: native WP media contract + internal review. Events/jobs: public catalog invalidation; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U065, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.

## MOD-U026 / configuration.preview-apply-export

Provider/consumer: module domain service ← authorized controller or worker; kind `QUERY/COMMAND`. Authentication/authorization: commerce.settings.manage; native edit_theme_options when applying styles.

Input schema: profile/schema_version; typed allowed settings; asset/category mappings; expected_revision; dry_run. Output schema: preview diff/errors; approved revision or export artifact ref/digest/expiry.

Validation and failure: Reject unknown/executable/private/secret fields; no cross-site identity; business gate changes explicit. Common conflict/idempotency/rate/error policies apply. Private results never enter shared catalog cache; successful revision changes invalidate the owning module projection.

Integration mapping: /settings/preview; /settings/export; application internal authorized adapter. Events/jobs: module.state.changed if activation follows separately; sensitive fields are references only and obey the shared event/job contract. Acceptance: TEST-U066, including missing/invalid/foreign-scope payload, replay, conflicting revision, provider timeout and maximum page/rate boundaries.


### Notification suppression endpoint alignment

API-D038 exposes `POST /commerce/v1/notifications/unsubscribe` (OpenAPI path `/notifications/unsubscribe`) for signed, suppression-only tokens without login. GET of the noindex confirmation landing performs no mutation. This is the sole optional-message suppression authority; provider/native mechanisms map to it. The token grants no preference read, resubscription, address change or suppression of mandatory operational notices. The exact scope, expiry, generic204 response, replay, storage-failure503, rate-limit and token-log-redaction rules are in the [signed unsubscribe contract](../09-api-integrations/api-and-authentication-strategy.md). FR-G021/TEST-G021 govern this endpoint; MOD-U006 and MOD-U016 recheck committed suppression before sending.
