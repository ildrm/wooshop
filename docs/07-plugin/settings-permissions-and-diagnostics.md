# Plugin settings, permissions and diagnostics

Document ID: DOC-U-16 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: WooCommerce Extension Developer; Application Security Engineer; QA/Test Engineer
Scope: proposed reusable modular commerce plugin, independent sites
Requirements: FR-U027; FR-U015; FR-U016 | ADRs: ADR-02; ADR-03; ADR-04; ADR-07; ADR-16
Dependencies: [module catalog](module-catalog.csv); [domain model](../04-data/domain-model-and-erd.md); [API contract](../09-api-integrations/openapi.yaml)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–8; [source register](../00-governance/sources.csv)

## Typed settings and authority (FR-U027)

Plugin configuration stores only justified business functionality; native WooCommerce tax/coupon/stock settings remain native unless an ADR explicitly assigns a marketplace extension boundary. Settings have stable keys, type, bounds/default, permitted scope, capability, revision, migration and export classification. [Visual inventory](../05-ux-ui/settings-inventory.csv) enumerates theme/module presentation controls; each module spec lists business controls and safe defaults. Credentials are references, never exportable values.

Server evaluation order: validate known schema/version → authenticate/session-check → verify capability and object scope → validate value/dependencies → compare revision → record audit intent/result → publish new revision and invalidate owned projections. A request that names another seller or unknown setting is rejected even when the UI never offers it. No mass assignment. Seller fields are explicitly allowlisted; actor-provided `site_id`, owner, approval status and finance balance cannot be overwritten.

## Operational diagnostics

| Check | Safe output | Owner/action |
|---|---|---|
| Versions/capabilities | package versions, declared adapters, unresolved gates | release owner verifies supported matrix |
| Queue health | oldest ready age, retry/poison count, job type and redacted reference | SRE inspects backlog/runbook |
| Data integrity | missing mapping counts, stale projection count, reconciliation exception IDs | domain owner investigates without raw customer dump |
| Provider health | last successful ping/event age, degraded state, masked account reference | integration owner corrects credentials/provider incident |
| Storage/migration | schema version/checkpoint, migration error category | developer resumes safe checkpoint or forward recovery |
| Theme fallback | registered block/view contracts and missing renderer list | theme/plugin owners test semantic fallback |

Diagnostics require platform capability; sellers see only their own import/job statuses and actionable errors. Downloadable diagnostic bundle is an explicit previewed allowlist of versions, counts, redacted logs and configuration names; secrets, raw webhooks, addresses, emails and session tokens are excluded. Bundle expires after24hours and signed download is owner-scoped. These proposed retention values remain subject to DEC-07.

## Configuration workflow

Search by setting key/label/module, preview changed values, validate dependency conflicts, save atomically and show audit reference. Restore uses a prior compatible settings revision; financial history is never rolled back with settings. A changed campaign/commission/configuration version affects future transactions only, unless a documented correction workflow creates adjustments. Metrics show stale timestamp rather than invented zero values.

TEST-U027 checks cross-seller settings/export access, malformed numeric/URL input, credential masking, concurrent edits returning conflict, missing provider diagnostics and config restore that leaves orders/ledger unchanged. Acceptance requires both server denial and UI explanation; hiding a control alone fails.
