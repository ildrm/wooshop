# Seller and finance operations guide

Document ID: DOC-S-GUIDE04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: Marketplace Operations Manager | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Seller and finance operations guide | Requirements: FR-S009; SEC-S011
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Seller workflow

FR-S009: Seller organizations are independent from WordPress users; owners invite scoped catalog, fulfillment and finance members. Complete verification through approved provider/process, review terms/version and obtain approval before publishing offers. Do not upload identity documents into public product media. Suspended sellers retain scoped access to active obligations and appeal paths; suspension does not delete orders or balances.

Catalog managers match a canonical product/variation, propose offer SKU/price/stock/condition/warranty/origin and submit required moderation. Preview seller disclosure and shipping terms. Stock imports must use the declared authority and version; do not update both native and offer stock as separate decrements. A rejected offer shows reason and resubmission route. Changed price/seller terms require customer reconfirmation at checkout.

Fulfillment managers see only their seller allocation, relevant items and minimum delivery data. Confirm release-to-fulfill status, pack items, record carrier/tracking and shipment quantity, then monitor exceptions. Split shipments retain line/quantity mapping. Never mark a whole multi-seller order completed because one seller shipped. Return requests reference purchased snapshots and remaining quantity; evidence is restricted to the case.

## Finance workflow and safeguards

Seller finance sees its own fees, commission version, refunds, holds, adjustments and statements; customer/order data is minimized. A balance displayed is an accounting obligation, not a spendable platform wallet. Destination changes use approved provider token, fresh MFA, independent review and24hour proposed cooling period. A seller cannot mark payout paid or modify commission history.

Platform finance performs daily four-way reconciliation: WooCommerce paid/refunded orders, gateway captures/refunds, seller allocations and settlement subledger. Investigate each unexplained minor-unit difference. Prepare a payout batch only from eligible reconciled amounts after return/dispute holds; a separate authorized user with fresh MFA approves. Verify provider status before retrying a timed-out submission. Repeated callbacks do not create repeated payments.

Refund before payout reduces eligible liability using linked reversal/adjustment; refund after payout creates explicit negative seller balance or platform recovery obligation under approved terms. Partial refunds use line-level remaining refundable amounts and original discount/tax/commission rules; no overwriting historical entries. Chargebacks may arrive after customer refund and require provider evidence to prevent double recovery. Currency conversion, if later supported, requires approved boundary and rate provenance; do not subtract different currencies.

## Exceptions and closing a seller

For disputed item, place reasoned hold, collect evidence, record deadline and independent decision, then apply reviewed financial effect. For uncertain payment, do not ship until approved release policy resolves it. For negative balance, follow approved collection/offset terms; no unapproved automatic debit. Before seller termination, stop new offers, fulfill/refund outstanding orders, reconcile payouts/holds, export permitted statements, revoke members/credentials and apply retention. Training requires a two-seller order, partial refund before/after payout, duplicate webhook and self-approval denial using synthetic fixtures.
