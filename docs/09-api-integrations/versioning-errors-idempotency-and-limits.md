# Versioning, errors, idempotency and limits

Document ID: DOC-G-API-VERSION | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending external verification and human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Solution Specialist; Application Security Engineer; Payments/Settlement Engineer; QA/Test Engineer
Scope: proposed contracts; no operating endpoints or selected commercial providers | Requirements: FR-D043; FR-D095; FR-G015
ADRs: ADR-03; ADR-04; ADR-06; ADR-09; ADR-16 | Dependencies: [OpenAPI](openapi.yaml); [operation catalog](api-operation-catalog.csv); [events](event-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; SRC-G011; SRC-G012; SRC-G013; [source register](../00-governance/sources.csv)

## Version and schema contract

`commerce/v1` is a proposed major contract. Additive optional response fields can ship in a minor package release only when strict clients tolerate them; removal,meaning changes,new required request fields or different money/identity semantics require a new major or negotiated explicit capability. Unknown request fields are rejected,while clients should ignore unknown optional response fields. Enum expansion requires consumer handling or a versioned change. Deprecation includes replacement,owner,deadline and an obligation-preserving migration; no provider is abandoned while renewals/refunds remain due.

[OpenAPI](openapi.yaml) owns exact request/response types and native versus custom-ID encoding. Documentation-side JSON/YAML parsing is not OpenAPI runtime conformance. The [dictionary](../04-data/data-dictionary.md) owns field meaning,nullable states and precision. Typed per-key settings validation narrows the generic value schema: no arbitrary objects,CSS,HTML or code are accepted merely because JSON can represent them. Full preview/export remains bounded by the schema's supported key count and payload size; larger approved migrations use the audited import operation rather than silently truncating settings.

## Error semantics

| Status | Meaning and consumer action |
|---|---|
| 400 | Malformed structure;fix request before retry |
| 401 | Session/credential missing or invalid;reauthenticate without exposing object existence |
| 403 | Visible resource but capability,state or step-up restriction;do not retry blindly |
| 404 | Absent or not visible resource;consistent across foreign seller/customer IDs |
| 409 | Business/idempotency conflict;reconcile current intent and quoted terms |
| 412 | Stale `If-Match` version;read current authorized representation and explicitly reapply |
| 422 | Semantically invalid or unsupported capability;show actionable field/reason message |
| 428 | Required version precondition missing |
| 429 | Scoped quota exceeded;honor `Retry-After` and backoff |
| 503 | Dependency unavailable;do not assume external money action failed or succeeded |

Errors use the OpenAPI Error envelope with stable code,safe message,field information where permitted and correlation reference. No raw provider payload,secrets,SQL,verification documents or another tenant's existence is exposed. A friendly message never replaces the machine code. Native WooCommerce errors keep their supported contract and are translated for display without inventing native custom status guarantees.

## Optimistic concurrency and idempotency

`If-Match` contains the quoted current resource version where required. A compare-and-update checks the authoritative version inside the mutation boundary; two concurrent updates cannot both overwrite the same revision. Version mismatch has no partial mutation. Price/terms reconfirmation is an explicit new accepted commercial digest,not automatic retry.

`Idempotency-Key` is16–128characters,scoped by site,principal and operation. Persist request digest,state,result reference and timestamps. Same key/same intent returns the original completed result or pending operation; same key/different body returns409. Ordinary response replay records have a proposed24hour window; financial effect identities/provider references persist with their transaction audit retention,so key-cache expiry cannot cause a second charge/refund/payout. Retrying an unknown external outcome reuses its original provider key and queries provider truth before a replacement attempt. There is no exactly-once delivery claim.

## Limits and pagination

Proposed custom list pagination uses scoped opaque cursors,default20 and maximum100 results. Bind cursor to site,principal/filter/sort snapshot;reject tampering or reuse under a different scope. Stable sort includes an immutable tie key; expiring cursors return an actionable restart without broadening access. Native Store API pagination retains its own documented shape.

Initial custom quotas are60public reads/minute per combined edge/session bucket,30ordinary mutations/minute per principal+site,5financial approval attempts/minute per principal and 2imports/hour per principal with one concurrent bulk import per site. Large shared networks require fair session-aware throttling; quotas are planning targets pending load tests. Enforce provider-specific stricter quotas in adapters. Proposed ordinary JSON body ceiling is1MiB, with settings preview/import capped at256KiB and500 registered entries; media/import uploads use their separately reviewed content and decompression limits. Record accepted/rejected counts without logging sensitive raw bodies. Credential rotation,retries and cursor tests are part of FR-D095 and the associated API tests.
