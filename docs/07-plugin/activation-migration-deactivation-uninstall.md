# Activation, migration, deactivation and uninstall

Document ID: DOC-U-19 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: WooCommerce Extension Developer; Application Security Engineer; QA/Test Engineer
Scope: proposed reusable modular commerce plugin, independent sites
Requirements: FR-U030 | ADRs: ADR-02; ADR-03; ADR-04; ADR-07; ADR-16
Dependencies: [module catalog](module-catalog.csv); [domain model](../04-data/domain-model-and-erd.md); [API contract](../09-api-integrations/openapi.yaml)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–8; [source register](../00-governance/sources.csv)

## Activation and schema changes (FR-U030)

Activation validates WC/PHP/WordPress versions, permissions, required modules, provider contracts and schema compatibility. A dry-run reports blocked combinations and planned data changes. Migrations are resumable, bounded and versioned with preflight counts/checkpoints; public writes remain gated until the required schema is coherent. Existing core IDs/URLs/orders are not regenerated. Background indexes can rebuild from authoritative records and are not a reason to rewrite transaction history.

Safe migration order: backup/recovery evidence → validate mappings → additive schema capability → backfill with checkpoints → compare counts/digests/reconciliation → switch read projection/authority at an explicit cutover → retire old projection only after retention and exit review. A native-to-marketplace cutover is a distinct financial/inventory procedure described in marketplace migration; toggling a setting cannot perform it implicitly.

## Deactivation obligation inventory

Before disabling, inventory active subscriptions/bookings, pending payments, reserved stock, outstanding seller allocations, returns/disputes, payout holds, undelivered transactional notifications and queued exports/imports. Stop new optional activity, drain/cancel jobs according to class, retain service-only handlers and show operator obligations. Complete plugin removal is blocked operationally until a verified successor/support workflow owns every obligation. WordPress permits administrators to deactivate plugins; therefore project support policy and preflight safeguards cannot be described as an absolute defense against a privileged manual deletion. External recovery procedures are required for that failure.

| Data class | Default disable behavior | Explicit uninstall/exit behavior |
|---|---|---|
| Customer saved lists/history | UI unavailable; records retained to approved expiry | Offer export/erase; consent-aware purge |
| Specs/compatibility evidence | Read-only export; core attributes still visible where native | Export typed values/provenance; preserve claim audit as required |
| Campaigns/recommendations | Stop new publication/sends | Remove optional projections; no transaction edits |
| Transactions/ledger/allocations | Service mode; immutable records and reconciliation continue | Verified transfer/retention legal gate; never one-click wipe |
| Settings/assets | Mark module inactive; retain approved revision | Export allowlist; shared WP media never blindly deleted |
| Jobs/webhooks | Drain critical work; quarantine unknown callbacks | Transfer callbacks/credentials, reconcile then revoke secrets |

## Restore/rollback distinction

A failed package update may be reverted when schema compatibility permits. Once new orders/events exist, restoring a pre-upgrade database can erase obligations; forward repair and reconciliation are the preferred recovery route. Uninstall requires a privileged explicit data-retention choice, affected-record counts, export verification, legal/finance approval where required and recorded handoff. No destructive uninstall action occurs in this documentation assignment.

TEST-U030 covers interrupted migration, repeated activation, module disable with a pending refund/renewal, forced dependency loss, reinstall with old schema and exported successor reconciliation. Acceptance requires stable identities, no abandoned obligation and a clear blocked/partial state. Uninstall/backups must be proven with synthetic data in future implementation.

## Marketplace adapter loss: fail-closed boundary

Marketplace-mapped native WC products/variations are proposed persisted nonpurchasable shells: native managed stock0 and backorders disabled, with the verified active offer adapter supplying eligible availability/quote. Native catalog projections are never a fallback stock/price authority. If the adapter disappears, direct native add-to-cart, cart-update and checkout—including existing sessions—must fail for mapped lines; native-mode products retain normal WC behavior. This guard is a future integration acceptance gate, not a claim that a specific hook already works. External health/maintenance controls stop affected sales on abrupt plugin loss; operators restore/transfer service to reconcile pending payment/refund/fulfillment obligations. Theme hiding alone is insufficient. TEST-U023/U030/U064 includes forced dependency loss and direct native requests.
