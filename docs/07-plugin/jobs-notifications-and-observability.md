# Background jobs, notifications and observability

Document ID: DOC-U-18 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: DevOps/SRE Engineer
Reviewers: WooCommerce Extension Developer; Application Security Engineer; QA/Test Engineer
Scope: proposed reusable modular commerce plugin, independent sites
Requirements: FR-U029 | ADRs: ADR-02; ADR-03; ADR-04; ADR-07; ADR-16
Dependencies: [module catalog](module-catalog.csv); [domain model](../04-data/domain-model-and-erd.md); [API contract](../09-api-integrations/openapi.yaml)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–8; [source register](../00-governance/sources.csv)

## Job envelope (FR-U029)

Proposed envelope fields: job UUID, site ID, module key, handler/payload version, obligation category, business object reference, deduplication key, created/available/expiry instants, attempt count, correlation/causation IDs and lease revision. Payload contains minimal references; workers resolve current authorization, consent and domain state. No raw card data, secrets or full customer profile belongs in a job.

Delivery is at-least-once. A worker claims a bounded lease, checks idempotent effect key, validates current domain version, performs an authorized effect, records outcome and acknowledges. A crash after provider submission is reconciled using provider request/idempotency reference before retrying a non-idempotent side effect. It is not safe to assume a timed-out request failed.

## Retry/expiry policy

The [canonical webhook/job policy](../09-api-integrations/webhook-and-job-contracts.md) owns class-specific delays,maximum total attempts,leases and poison escalation; it replaces any generic retry default. Total attempts includes the initial execution. Validation/authorization errors do not retry until corrected. Critical money/fulfillment obligations remain quarantined and serviced after automatic retries stop. Optional campaign/alert expiry may stop retries earlier. Unknown payload versions quarantine pending a compatible handler or approved migration.

| Class | Scheduling/deadline proposal | Dedup/reconciliation | Disable policy |
|---|---|---|---|
| Price/stock alerts | batch every5min, current state recheck before send | recipient-purpose-product-trigger version; no repeat per threshold crossing | cancel optional sends, preserve opt-outs |
| Catalog/search projection | enqueue on approved change; search freshness alert>60s;ordinary queue age>5min | source revision monotonic; replay from source | stop new projection; native fallback |
| Import/media processing | chunk with checkpoints; worker lease120s,heartbeat40s,renewable | import row mapping/source digest; retry only safe step | pause/restart checkpoint |
| Transactional notifications | within2min target after committed event | object-event-template-recipient key; provider status reconcile | service mode until obligations handled |
| Ledger/payment/stock reconciliation | schedule per finance operations policy | immutable transaction/provider IDs; exceptions not auto-erased | service mode and escalation |

## Observability and privacy

Each execution logs type, site, hashed/opaque object reference, elapsed time, outcome code, attempts and correlation. Metrics expose oldest age, throughput, retry ratio, poison count, provider failures and suppression reasons. Access to logs is role-scoped; customer-facing support sees a correlation reference, not payloads. Proposed logs30days and delivery metadata90days await retention gate; audit/financial obligations follow their approved longer policy. Alert notifications themselves are rate-limited to avoid storms.

TEST-U029 kills work between lease/side effect/ack, replays the event, withdraws consent while queued, changes seller membership before export and simulates provider timeout. Expected: no duplicated business effect, no revoked marketing send/export, preserved critical obligation and actionable poison queue. All tests are specifications, not executed here.
