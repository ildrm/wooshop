# Backups, recovery and runbooks

Document ID: DOC-S-OPS04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; operational validation NOT RUN
Owner: DevOps/SRE Engineer | Reviewers: DevOps/SRE Engineer; QA/Test Engineer; Release/Maintenance Owner
Scope: Backups, recovery and runbooks | Requirements: NFR-S004; FR-S004
ADRs: ADR-07; ADR-09; ADR-11; ADR-14; ADR-16
Dependencies: [version baseline](../00-governance/version-baseline.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; [source register](../00-governance/sources.csv)

## Backup and restore contract

NFR-S004 targets RPO15minutes and RTO4hours for core transactional service, subject to approved hosting capability. Use encrypted database backups/log capture, daily catalog/media/config snapshots and a separate failure domain with35day provisional retention. Provider credentials are restored from the secret manager under rotation policy, not bundled in downloadable backups. Access is least privilege, audited and periodically reviewed. Backup success includes restorable data/checksums and retention verification; file existence alone is insufficient.

Monthly restore rehearsal and before irreversible migration: select recovery point, provision isolated target, verify checksums/schema/package compatibility, restore data, apply pending deletion tombstones, reconcile provider captures/refunds/payouts after the recovery point, reconstruct missed events through idempotent import, validate orders/stock/ledger and permissions, then authorize traffic. Record measured recovery time and irrecoverable gap. Finance signs monetary reconciliation, Privacy signs deletion preservation, SRE signs health. No restore was performed here.

## Runbook templates with concrete branches

FR-S004: Every incident records site, time, operator, affected resource IDs, last known good checkpoint, action, expected result and verification. Do not paste credentials/customer data into tickets.

| Trigger | Diagnose / contain | Recover / verify |
|---|---|---|
| Checkout errors after release | Compare release/error/correlation IDs; pause rollout; stop new checkout only if integrity at risk | Restore compatible code/config or forward patch; synthetic cart/payment sandbox check and live aggregate monitors |
| Payment request timed out | Mark payment pending/unknown; inspect stored idempotency/provider transaction | Authenticated provider lookup; apply one confirmed result; never blindly recapture |
| Webhook backlog | Check signature/key and queue worker health; preserve events | Resume version-compatible worker; replay by event/business ID; reconcile provider totals |
| Search unavailable | Confirm provider status and index health; use bounded native search | Rebuild versioned index; compare publication/eligibility; switch only after freshness verification |
| Inventory mismatch | Disable affected offers; compare reservation/decrement audit | Reconcile authoritative stock with approved inventory evidence; append adjustment; reopen after race test |
| Settlement mismatch | Hold affected batch and destination changes | Finance matches captures/refunds/allocations/fees; append correction; independent approval before release |
| Notification provider failure | Stop rapid retries; inspect credential/quota/bounce without exposing address | Backoff/alternate approved provider; recheck consent before send; dedup notification key |
| Malicious seller upload | Quarantine asset and disable its serving; identify references | Remove public derivative, review scope, rescan/review; preserve incident evidence and inform authorized owner |
| Cache leakage suspected | Bypass/purge affected namespace; private no-store | Audit key dimensions and vary behavior; cross-site/role regression before re-enable |
| Site/DB loss | Incident command; stop writers/webhooks or durably queue accepted callbacks | Restore isolated; reconcile gaps and tombstones; staged reopening with finance approval |

## Rollback limits

Recovery cannot erase customer orders accepted after a snapshot. Separate code/config rollback from data restoration. If schema is irreversible, deploy forward-compatible repair and keep affected capability closed while obligations are serviced. External provider state cannot be rolled back by restoring local DB. Preserve originals for investigation; reconcile rather than editing historical ledger balances. A failed restore exercise creates a release-blocking work item when it threatens the stated recovery target.
