# Financial integrity and abuse prevention

Document ID: DOC-S-SEC03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; implementation verification NOT RUN
Owner: Payments/Settlement Engineer | Reviewers: Application Security Engineer; Privacy Specialist; QA/Test Engineer
Scope: Financial integrity and abuse prevention | Requirements: SEC-S009; SEC-S010; SEC-S011
ADRs: ADR-03; ADR-09; ADR-14; ADR-16 | Dependencies: [authorization matrix](roles-capabilities-and-object-authorization.csv); [control matrix](controls-and-verification-matrix.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section11; SRC-S001; SRC-S011–SRC-S013; [source register](../00-governance/sources.csv)

## Transaction boundary

SEC-S009: Every monetary input displayed by the client is advisory. At cart mutation and checkout the server loads the chosen product/variation/offer, validates seller eligibility and quantity, recalculates current price, approved tax, coupon and shipping, and snapshots the accepted terms. Changed seller or material terms require customer reconfirmation; no silent substitution. Amounts are integer minor-unit strings with currency/exponent, never binary-float ledger values. The stock adapter owns decrement: native WooCommerce for native inventory, marketplace offer adapter for mapped marketplace lines with native decrement suppressed. See [financial invariants](../04-data/orders-financial-records-and-invariants.md).

Payment collection and payouts use an approved regulated provider and approved merchant/funds model (DEC-01/02/04). The plugin subledger records obligations and reconciliations; it is not a wallet or unauthorized custody system. Hosted/tokenized payment is the provisional card-data boundary. Card numbers and security codes never enter plugin forms, APIs, logs, analytics or tickets. Qualified payment-security review must determine actual scope; hosted payment does not eliminate all obligations.

## Abuse cases and controls

| Abuse | Proposed control and outcome |
|---|---|
| Price/coupon/commission tampering | Ignore submitted amounts; signed calculation version is verified against server state; reject stale terms409 |
| Stock race | Atomic offer reservation; expiry and payment reconciliation; at most available units confirmed |
| Coupon/reward replay | Per-order idempotent redemption record; account and campaign limits; reversals reference original entry |
| Fake review or seller rating | Ownership/eligibility proof, moderation and separate product versus service aggregates |
| Mass alert subscription | Double opt-in where required, rate and per-address limits, neutral responses, immediate suppression on opt-out |
| Payout destination takeover | Recent MFA, separate approver, verified provider destination,24hour cooling period and alert to old contact |
| Seller collusion or sudden return spike | Human-reviewed anomaly queues; no automatic public guilt labels; payout hold with reason/appeal |
| Refund exceeding capture | Per-line remaining refundable amount and gateway capture state; versioned approval; no negative residual by overwrite |

SEC-S010: Webhook adapters verify provider-documented signature and timestamp policy before JSON processing; unknown key/signature is rejected and redacted. Store provider+event ID durably, hash the normalized payload, reject changed payload under same event ID, and deduplicate business effects by capture/refund/payout ID as well. Proposed replay window5minutes applies only if provider signature semantics support it; a delayed legitimate event is reconciled through authenticated provider lookup, not trusted by age alone. Duplicate or out-of-order callbacks return a safe acknowledgement after stored status check, never issue another refund or stock decrement. Polling reconciliation resolves uncertain outcomes after timeout before retrying a monetary mutation.

SEC-S011: Payout preparation and approval are distinct users with non-overlapping actions for the batch. A seller finance manager views only its own ledger/statement and cannot approve platform payouts or alter commission versions. Refund requests from fulfillment/support staff cannot exceed their assigned policy or approve their own high-risk adjustments. All manual corrections append a reasoned adjustment/reversal linked to original records. Daily reconciliation verifies gateway captures/refunds, WooCommerce paid/refunded totals, allocations and ledger balance; any unexplained difference freezes affected payouts and alerts Finance. Suspension preserves fulfillment/refund obligations while stopping new sales. Negative balances remain explicit and are collected only under approved terms.
