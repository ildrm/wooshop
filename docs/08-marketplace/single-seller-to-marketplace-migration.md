# Single-seller to marketplace migration

Document ID: DOC-D-MIGRATION | Title: Single-seller to marketplace migration | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D018; FR-D021; FR-D041; FR-D056; FR-D057; FR-D075 | ADRs: ADR-05; ADR-07; ADR-08; ADR-14; ADR-16
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Preconditions and dry run

The migration requires approved legal/provider model, version/adaptor matrix, first-party seller organization, backup/restore plan, support staffing and reconciliation baseline. Inventory every product type, variation-stock owner, URL, extension, open order, subscription/booking, return, refund and provider mapping. Unsupported complex combinations remain native or unavailable for marketplace sale; they are not coerced into simple products.

Dry run on synthetic/sanitized staging produces counts by type, native IDs/URLs, proposed offer mappings, stock balances, pending reservations and financial totals. Catalog/SEO owners review canonical/redirect effects; Finance reviews opening balance semantics. Pre-migration historical orders keep their original native identities, amounts and seller attribution context. They may receive a separate legacy first-party mapping for reporting, never rewritten commercial snapshots claiming data that was not captured.

## Cutover sequence

1. Freeze affected product price/stock editing and new checkouts; allow unrelated products only if scope can be isolated safely.
2. Drain or reconcile native in-flight carts/reservations/payments for affected products. Unknown payments stay in the exception queue and prevent authority switch.
3. Create deterministic first-party offer mappings and inventory pools from reconciled native balances. Record source digest and authority epoch.
4. Validate product/variation references, seller/offer uniqueness, zero unexplained stock/money drift, SEO identity and export readiness.
5. Switch one product's authority flag/epoch atomically; fence old native/provider writers and activate mapped-line decrement suppression.
6. Rebuild public offer/search/feed projections. Re-open with monitored limited traffic and reconciliation, then proceed in bounded batches.
7. Onboard third-party sellers/offers only after the first-party marketplace path passes its gate.

## Historical continuity

Product/variation IDs and canonical URLs remain unchanged. Orders before cutover continue through native refund/download/subscription handling. Orders after cutover capture explicit offer/allocation snapshots. Renewals created by existing subscription contracts stay on their registered adapter and authority policy until a separately approved contract migration; do not alter a customer's seller or commercial terms silently. Stock changes from outstanding old orders are reconciled through a legacy adapter with operation keys, not applied blindly to both old/new stores.

## Rollback versus forward recovery

Before new marketplace transactions occur, revert staged mappings/authority with the captured reconciled state and invalidations. Once a new order, capture or payout exists, do not restore a stale database snapshot. Pause affected sales, retain new records, fix the adapter or create compensating operations, reconcile external systems, then resume. Provider timeouts and unknown payments prevent a simplistic rollback. Recovery decisions have accountable Release/Maintenance Owner and Finance sign-off.

## Acceptance and evidence

FR-D075 compares before/after native IDs, canonical URLs, old-order totals, stock pools and provider transactions; expects no duplicates, lost orders or unexplained deltas. Run migration twice to confirm idempotent mapping; interrupt each checkpoint and resume. Verify same variation from two sellers, historical refund, old subscription renewal, search continuity and unsupported-product safe state. Evidence includes row-level migration report, sampled URL checks, balance/stock reconciliations and approved exception disposition. No migration has been executed here.

## Native-shell cutover guard

Before reopening a marketplace-mapped product, persist managed native stock zero/backorders disabled for its purchasable product/variation shells. Actual stock remains in the reconciled plugin pool. Test normal adapter purchase and forced adapter absence: direct native cart mutations and checkout, including old carts, must fail closed. Rollback to native sales is an explicit stock-authority migration with reconciliation; do not simply deactivate the plugin and leave stale projections purchasable. Legacy pending orders are drained/reconciled before this guard changes their authority epoch. FR-D076 is a release-blocking cutover condition.

After cutover, a legacy native order refund must not use native restock on a marketplace-mapped product. Its physical disposition becomes an epoch-aware plugin inventory adjustment while the native shell remains zero. During complete plugin loss, no plugin interception exists: mandatory independent maintenance blocks purchase and native restock/admin mutation until recovery, or all checkout if isolation cannot be guaranteed. Money-only provider recovery is reconciled later without changing native stock. This dependency-loss guard is an activation gate and must be tested, not assumed.
