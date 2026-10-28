# Import, export and migration

Document ID: DOC-D-IMPORT | Title: Import, export and migration | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D012; FR-D034; FR-D041; FR-D056; FR-D057 | ADRs: ADR-05; ADR-07; ADR-10; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Import contract

Imports are asynchronous staged operations with source digest, schema version, site, submitter, scope, file format, field map and dry-run result. CSV/JSON files are data, not executable packages. Proposed limit: 20 MB/file and 10,000 rows/job, with chunk size 200 and resumable cursor. Larger imports require approved splitting/capacity planning. Archive uploads are disabled by default; a future archive adapter must enforce expanded size/path limits. Formula-like CSV cells are escaped on export and treated as text on import. Files and URLs do not bypass upload/SSRF controls.

Validation reports row key, field, error code and safe message without leaking another seller's object. The importer rejects unknown fields, duplicate seller SKU, invalid native parent/variation references, monetary overflow, unit mismatch, unauthorized publication and unsupported type combinations. An all-or-nothing product aggregate is committed only after its components validate. Cross-product jobs are checkpointed; a partial job gives exact committed/rejected rows, not a false full success. Reusing source digest plus mapping version returns the prior result unless an explicitly new revision is requested.

## Ownership and concurrency

Seller import scope is derived from membership; seller_id in a row cannot widen it. Canonical technical claims are proposed for moderation; seller offers can update only permitted commercial fields with expected version. Inventory imports are adjustments against an authority epoch and snapshot time, not blind replacement of counts while orders are occurring. Conflicting stock updates go to reconciliation. Price/stock publishing emits versioned invalidation events only after commit.

## Export classes

| Export | Contents | Authorization and expiry |
|---|---|---|
| Configuration | Presets, public schema definitions, approved non-secret settings | Platform settings capability; preview import; no PII/secrets |
| Catalog | Native IDs, offer/provider mapping, typed values and provenance | Catalog or own-seller scope; signed download expires in 15 minutes |
| Operational | Assigned orders/shipments with minimized customer contact | Own fulfillment capability, purpose logged; 24-hour generated artifact retention |
| Finance | Allocations, journal lines, payout references, opening/closing balances | Finance scope + step-up; tamper-evident digest; legal retention schedule |
| Data subject | Verified subject's personal information | Identity verification; third-party data redacted; lawful holds explained |

The download endpoint re-checks current permission and site even after the job completed. Signed links are bearer-sensitive and never included in analytics. Provider secrets, full verification files and card data are excluded from ordinary exports.

## Migration and recovery

Single-seller migration is specified in the [marketplace migration runbook](../08-marketplace/single-seller-to-marketplace-migration.md). All migrations have source/target schema versions, preflight, dry-run counts, immutable checkpoints, row/error reports and reconciliation. Existing product IDs and URLs stay; old orders remain native historical records. Provider clone listings map to canonical IDs with reviewed evidence and aliases, not a destructive bulk merge.

Before irreversible external effects, rollback can restore staged configuration and authority flags. After live new orders or payouts, use forward recovery, replay and compensation; restoring a stale whole database is forbidden as a routine rollback. FR-D056 tests malformed rows, unauthorized seller rows, repeat imports and concurrent stock changes. FR-D057 tests export-token revocation and migration restart without duplicate identities or money.
