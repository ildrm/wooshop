# Webhook and job contracts

Document ID: DOC-G-API-JOBS | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending external verification and human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Solution Specialist; Application Security Engineer; Payments/Settlement Engineer; QA/Test Engineer
Scope: proposed contracts; no operating endpoints or selected commercial providers | Requirements: FR-D009; FR-D055; FR-D094; FR-D095; FR-G008
ADRs: ADR-03; ADR-04; ADR-06; ADR-09; ADR-16 | Dependencies: [OpenAPI](openapi.yaml); [operation catalog](api-operation-catalog.csv); [events](event-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; SRC-G011; SRC-G012; SRC-G013; [source register](../00-governance/sources.csv)

## Envelope and durable delivery

The canonical [event catalog](event-catalog.csv) defines dotted domain events separately from underscore analytics wire events. Domain EventEnvelope v1 contains event_id, event_name, schema_version=1, site_id, aggregate_id, aggregate_version, occurred_atUTC, correlation_id and the event-specific payload. Event IDs are UUIDs; native product/order IDs inside payloads are decimal strings. Payload fields are versioned allowlists. Producer commits state plus an outbox/journal intent atomically where storage permits; otherwise a recoverable operation journal and reconciliation close the gap. A queued event is not evidence that its external effect happened.

Delivery is at-least-once. Inbox identity is `(site,producer,event_id)` plus a payload digest; same identity/different digest quarantines and alerts. Financial consumers also deduplicate by business command and provider payment/capture/refund/transfer reference,not only delivery ID. Ordering is not assumed globally. Aggregate versions reject stale projection updates; financial state changes use allowed transitions and current provider evidence. An older pending callback cannot reverse captured/paid state.

## External webhook ingestion

Each provider adapter declares endpoint ownership,signature algorithm/raw-body handling,key rotation,timestamp semantics,event identity,expected account/site and authoritative fetch/reconciliation API. No generic HMAC assumption establishes compatibility. A proposed maximum5minute delivery-signature replay window applies only where the verified provider contract supports it; an old business event with a fresh valid retry signature may still be legitimate. Provider-specific rules override this proposal through the exact-version gate.

Authenticate and validate size/content before durable inbox recording; acknowledge only after durable acceptance or known safe duplicate. Unsupported but valid event types are recorded minimally and ignored with a metric. Bad signatures,wrong account,amount/currency mismatch and same-ID changed bodies enter rejected/quarantined states and never post money. Slow processing occurs after acknowledgement; temporary storage failure returns the provider's retryable response. Callback URLs are configured server-side,not from untrusted payloads. Never fetch arbitrary callback/media URLs without egress and redirect restrictions.

## Job policy

This table is the canonical retry policy for all modules and feed workers. Maximum total attempts includes the initial execution; the listed delays precede retries. Module-specific opportunity expiry can stop optional jobs sooner, never extend this policy silently.

| Class | Retry/expiry proposal | Replay and failure outcome |
|---|---|---|
| Financial command/reconciliation | Backoff30s,2m,10m,30m,2h with jitter;maximum6total attempts (initial+5retries),then escalate | Unknown money remains open;no financial obligation expires merely because retries stop;manual reconciliation before replay |
| Catalog/search/feed projection | Backoff10s,1m,5m,15m maximum5total attempts (initial+4retries) | Superseded versions coalesce;rebuild from authority;poison record alerts owner |
| Optional marketing/alert | Backoff1m,5m,30m maximum4total attempts (initial+3retries) within campaign/alert eligibility | Expired/withdrawn consent cancels;send-time recipient/stock/terms check;no late stale promotion |
| Transactional notification | Backoff1m,5m,30m,2h;maximum5total attempts (initial+4retries),then delivery reconciliation | Retain owed notice;fallback support channel per approved policy;do not blindly repeat ambiguous sends |
| Import/export | One site-scoped lease;checkpoint every bounded batch;transient retries10s,1m,5m,15m;maximum5total attempts | Revalidate schema/source version/permissions;failed rows reported;no partial-publication lie |
| Reservation expiry | Frequent bounded scan plus request-time deadline check | Idempotent release;expired hold cannot be revived by browser retries |
| Retention/evidence expiry | Daily bounded scans;legal-hold and current purpose check | Audit deletion or claim withdrawal;cannot erase held financial evidence |

Workers use a proposed60second lease with heartbeats at20seconds;import/media chunks use120second leases with40second heartbeats; compare lease token before committing so a stale worker cannot finish a reclaimed job. Provider request timeout is shorter than the lease or explicitly extended under adapter policy. Queue permission checks use current membership and object state,not the enqueue-time role alone. Revoked seller commands cancel; approved financial obligations move to authorized service execution with preserved approval evidence.

## Poison queue, replay and observability

A poison record contains safe reason,attempts,first/last timestamps,correlation/source references and responsible module;sensitive bodies are encrypted/restricted or replaced with references. Replays require scoped operator capability,reason and retained original effect identity. Never replay a payout by issuing a new provider key merely to clear a queue. Reconciliation compares authoritative order,gateway,allocation,ledger and transfer records before declaring recovery.

Observe queue age,lease loss,retry counts,dedup hits,version lag,unknown payment age and poison entries. Finance queue age>1minute and any unexplained monetary discrepancy follow the [operations alerts](../13-operations/slos-monitoring-alerts-and-analytics.md). Module disable drains promised work or transfers it; full plugin loss requires the independent maintenance/recovery path. FR-D094 tests duplicates,reordering,crash-after-provider-success,lease theft,revoked membership,expired consent and poison replay. No jobs or provider callbacks have been executed here.

## Catalog type and scheduling conventions

Event payload fields in the catalog are required unless suffixed `?`; null is permitted only for such optional fields. `NativeID`, `UUID`, `Instant`, `Version` and `Money` use the data dictionary; string fields default to bounded256characters, arrays to100items unless a stricter owning schema applies. Enum values come from the owning entity state machine. Payload producer validation rejects unrecognized fields. Financial/reference strings use exact provider format after gate validation. `ordinary` jobs follow the bounded import/maintenance policy; `critical` state jobs use durable financial-style escalation where obligations exist and never expire those obligations. Notification retry/expiry distinguishes optional and transactional purpose as in the table. Analytics retries stop at their delivery TTL and never cause transaction re-execution.

The purchase coordinator emits its analytics purchase only when the customer order is confirmed paid for its accepted total. A provider capture notification is a domain input,not permission for every listener to emit another purchase. Partial-capture provider arrangements require an explicitly verified payment/allocation adapter before activation; the baseline does not infer that each partial capture is a separate order purchase. UI aliases are catalogued in [analytics mappings](../05-ux-ui/ui-analytics-aliases.csv); `none` means no wire event.

Deduplication expressions reference exact payload or envelope fields. `producer_key` is the trusted configured adapter/provider account namespace from execution context (never accepted from an untrusted payload); `site_id`, `event_id` and `event_name` are envelope fields. Literal event names separate transition kinds. Monetary consumers retain their additional business-effect deduplication even after envelope records expire.
