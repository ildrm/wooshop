# SLOs, monitoring, alerts and analytics

Document ID: DOC-S-OPS03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; operational validation NOT RUN
Owner: DevOps/SRE Engineer | Reviewers: DevOps/SRE Engineer; QA/Test Engineer; Release/Maintenance Owner
Scope: SLOs, monitoring, alerts and analytics | Requirements: NFR-S003; FR-S002; FR-S003
ADRs: ADR-07; ADR-09; ADR-11; ADR-14; ADR-16
Dependencies: [version baseline](../00-governance/version-baseline.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; [source register](../00-governance/sources.csv)

## Service targets

NFR-S003: Proposed service availability is99.9% over rolling30days, equivalent to43.2minutes unavailable in a30day window. Count valid customer browse/cart/checkout requests succeeding without5xx or timeout; report business-declined payments separately from infrastructure failure. Maintenance that prevents purchases consumes the budget. Invalid abusive requests and health probes are not denominator padding. Measure provider availability separately and report end-to-end customer impact alongside internally controlled service SLI. No availability was demonstrated in this assignment.

FR-S002: Monitor both technical and commercial outcomes with site, release, operation and correlation IDs. Trace IDs contain no PII; payment provider references are restricted. Audit events append actor/scope/action/object/version/result/reason; access and tampering are monitored. Application logs30days and redacted security logs90days are provisional; ledger retention follows DEC-07. A missing signal is a monitoring failure, not healthy traffic.

| Alert | Proposed threshold / route | Required action |
|---|---|---|
| Checkout infrastructure failure |>=2% for5min with>=20 attempts or synthetic checkout fails3times | Page SRE; isolate release/provider; preserve payment uncertainty |
| Availability budget burn |14.4x over1hour and6x over6hours | Page SRE; pause nonessential releases |
| Ledger/order/provider drift |Any unexplained minor-unit difference after reconciliation | Page Finance+SRE; hold affected payouts |
| Stock drift/oversell |Any negative unapproved availability or duplicate decrement | Marketplace engineer; stop affected offer sales |
| Queue age |critical finance>1min;ordinary>5min;poison queue>0 | SRE/domain owner; inspect event identity before replay |
| Search/feed freshness |search>60s;feed95% updates>5min | Discovery/SEO owner; fallback/hold affected feed |
| Provider signature failures |>=5/min or step change | Security; review key rotation/attack; do not disable verification |
| Email/SMS |hard bounce>5% over100sends;provider unavailable15min | Notification owner; stop bad channel/retry; suppress known bounces |
| Private-data or authorization anomaly |Any confirmed leak | SEV1 Security incident |
| Capacity |DB CPU>70%30min or disk>80% | SRE; forecast exhaustion and expand after review |

Business thresholds require calibration after real baselines; low traffic uses synthetic checks and absolute counts. SRE owns dashboards/on-call; Finance approves reconciliation; Support receives customer-impact summary. Weekly error-budget review controls risky release pace.

## Consent-aware analytics taxonomy

FR-S003: One producer per event avoids client/server duplicate purchases. Event envelope v1 contains event_id, event_name, occurred_atUTC, site_id, session pseudonym only if allowed, consent-purpose/version, locale, currency and schema_version. Money objects serialize `amount_minor` as an integer string, with `currency` and integer `exponent`; display decimals are derived and never ledger floats. Discovery events never include raw search text, address, email or full URL query; use bounded normalized category/facet IDs and redacted query class. Server transactional analytics uses approved operational basis, and optional marketing export is gated separately.

| Event name v1 | Producer / fields | Dedup/value semantics |
|---|---|---|
| discovery_view | Storefront;page family,collection/product IDs | event_id per meaningful view;no money |
| search_submit / facet_apply | Search UI;query class,facet IDs,result count | one committed interaction;raw text excluded |
| product_select / variant_select / offer_select | UI;product/variation/offer IDs | user choice only;not automatic rerender |
| add_to_cart | Server-confirmed cart mutation;line identity,quantity | mutation ID;value accepted line amount |
| checkout_start | Cart coordinator;cart version,total | one per attempt;not revenue |
| purchase | Payment/order coordinator;stable order/capture allocation IDs | one business purchase ID;order total and seller allocation sums exactly match |
| refund / return_requested | Refund/returns service;original order/line,amount or quantity | refund/return ID;refund positive magnitude with explicit event type, never double negative |
| seller_application / seller_approved | Onboarding service;seller pseudonymous ID,state | transition version;no verification documents |
| offer_changed | Offer service;offer ID,changed field categories | offer version;no private seller costs |
| payout_prepared / payout_paid / payout_failed | Settlement service;batch/payout ID,amount,currency | business effect ID;restricted finance analytics;not new sales revenue |

Client events are at-least-once and deduplicated by event_id; financial events additionally use business ID. Retain raw optional events30days provisionally and aggregate90days under DEC-07; no cross-site stitching by default. Verify opt-out, duplicate purchase callback, refund after payout and multi-seller allocation sums. KPIs such as conversion, search success, return rate, seller activation and payout timeliness are definitions awaiting baseline, not measured improvements.
