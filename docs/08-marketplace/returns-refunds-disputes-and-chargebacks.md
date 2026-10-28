# Returns, refunds, disputes and chargebacks

Document ID: DOC-D-RETURNS | Title: Returns, refunds, disputes and chargebacks | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D029; FR-D030; FR-D054; FR-D070; FR-D071; FR-D072 | ADRs: ADR-04; ADR-08; ADR-09; ADR-16
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Separate workflows

A return is a physical/service remedy case; a refund is an external financial operation; a dispute is a contested claim with evidence and deadlines; a chargeback is a provider/network financial event. They link to the same purchased item/allocation but do not share one state flag. Policies depend on jurisdiction/product/provider and require DEC-01/02/03 approval. Customer-visible language uses the applicable purchased policy, not a subsequently edited page.

## Return and refund steps

Customer requests eligible item quantities/reason with evidence; the server checks ownership, prior returns/refunds, deadlines and product capabilities. Returns/Dispute staff authorize or reject with reason and appeal route. Seller sees only assigned case fields. Physical receipt/inspection records condition and restock disposition. Authorized finance staff prepare a refund amount from original line/discount/tax/shipping allocations. A separate approver authorizes sensitive or threshold-exceeding refunds under approved policy; the provider command uses a stable idempotency key. Only verified provider success records a completed refund and corresponding WooCommerce refund/ledger effects.

Refund amount cannot exceed captured amount less previous confirmed refunds. Pending refunds reserve refundable amount so two requests cannot over-refund. Timeout is unknown; query/reconcile before retry. Partial return does not necessarily refund shipping; policy explicitly determines which original shipping amounts are refundable. Native tax adjustments are reconciled to the originally calculated tax and approved jurisdiction rule. Commission reversal uses the original commission basis/rate, unless a separately disclosed nonrefundable fee policy applies.

## Before and after payout

Before payout, refund debits the seller payable and reverses commission/tax shares. After payout, record seller receivable/negative balance and the platform/provider funding consequence explicitly; do not pretend the old payout never happened. Future payouts may offset lawful receivables under approved terms. If the provider can reverse a transfer, record that separately and avoid double collection. Insufficient provider balance creates a visible finance exception and customer support escalation, not false refund success.

## Disputes and chargebacks

Opened disputes create evidence deadlines, holds and responsible owner. Evidence is access-restricted, minimized and retained under legal hold. The platform distinguishes provisional provider debit, outcome and recovery. A won dispute reverses the relevant suspense/receivable; a lost dispute allocates goods/tax/fees/liability under the approved policy. Provider dispute fees are explicit, not silently deducted twice from seller balance. Customer refund and chargeback overlap is reconciled to avoid double remedy where prohibited while preserving required consumer rights.

## Product-specific cases

Download refunds follow native entitlement revocation and approved rights policy; protected download access is audited. Service/booking cancellations and rescheduling use provider availability and disclosed deadlines. Subscription cancellation stops future renewal intent under its adapter but does not reverse earned prior periods. Bundle/composite component returns use frozen component allocations and stock ownership; unsupported cross-seller combinations remain blocked. Seller suspension never removes the customer's remedy path.

FR-D071 verifies duplicate partial requests, refund limits, shipping/tax shares and provider unknown result. FR-D072 verifies dispute evidence access/deadlines, overlapping chargeback/refund, negative balance and old-order access after seller termination. [Worked examples](worked-transaction-examples.md) contain exact illustrative postings.
