# Orders, financial records and invariants

Document ID: DOC-D-FINANCE-DATA | Title: Orders, financial records and invariants | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D025–FR-D031; FR-D054; FR-D055 | ADRs: ADR-08; ADR-09; ADR-10
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Purchased snapshot and allocation

A customer order is WooCommerce's authoritative commercial object. Each line captures native product/variation, offer/seller identity, condition, seller SKU, quantity, configuration/components, currency/exponent, list/net/discount/tax/shipping/fee amounts, selected shipping and warranty/terms, commission policy version, price source version and authority epoch. These commercial snapshots do not change when the catalog, seller name or commission settings change. Corrections are linked adjustment records and customer-visible notices as appropriate.

An allocation groups purchased item quantities by seller and fulfillment origin. Its totals reconcile exactly to order lines; unallocated, multiply allocated or negative quantities fail validation. Provider suborders map to allocations and are excluded from duplicate customer charges, receipts and purchase analytics. A returned item has separate physical, customer-remedy and money states.

## Money and allocation invariants

All amounts use the [dictionary](data-dictionary.md) Money type. One order has one transaction currency; settlement conversion is a provider contract with source amount, target amount, rate, fees and timestamp. No mixed-currency summation is permitted. Price, tax, shipping, discount and commission calculations run server-side. WooCommerce remains tax/coupon authority through the selected adapter. Calculations keep adequate decimal/rational precision internally, then snapshot integer minor units under a configured rounding policy consistent with WooCommerce.

Order-wide discounts allocate by eligible pre-discount net amount using largest remainder: floor exact proportional shares, then distribute remaining minor units by descending remainder and stable order-item key. A discount never exceeds eligible value. The same allocation is reused for refunds. Commission basis is explicitly versioned; proposed illustrative basis is discounted goods net, excluding tax and shipping. Marketplace-funded promotions debit platform promotion expense rather than silently reducing seller payable. Refundable quantities/amounts are bounded by original snapshot less prior confirmed refunds.

## Accounting subledger

A journal transaction balances debits and credits within one currency. Account classes are provider_clearing asset, seller_payable liability, tax_payable liability, platform_commission revenue, gateway_fee expense, promotion_expense, seller_receivable asset and dispute_suspense. These names specify a commerce subledger, not a substitute for jurisdiction-specific general accounting.

Sale: debit provider clearing for captured gross; credit seller payable for seller share, tax payable for collected tax where the approved model assigns it, and commission revenue. Payout: debit seller payable, credit provider clearing upon verified transfer. Refund: debit original seller payable or seller receivable after payout, reverse appropriate commission/tax, credit provider clearing. Charges/fees use explicit entries. A disputed charge moves to a traceable suspense/receivable path; no balancing entry is invented merely to force a report to zero.

Every journal has unique command/provider-event key, source order/allocation and immutable entries. Repeated webhook delivery produces one financial effect. Corrections reverse the original and post replacement; operator edits cannot rewrite historical balances. A zero balance is not evidence of payout without provider transfer confirmation.

## Independent state machines

| Domain | Proposed states and terminal behavior |
|---|---|
| Payment | created → pending/authorized → captured; failed/cancelled; captured → partially_refunded/refunded; disputes tracked separately |
| Order | pending → processing → completed; on_hold/failed/cancelled; partial refunds do not necessarily end fulfillment |
| Fulfillment | unallocated → allocated → picking → shipped → delivered; blocked/cancelled; returns independent |
| Return | requested → authorized/rejected → received → inspected → resolved; missing evidence keeps pending |
| Dispute | opened → evidence_due → under_review → won/lost/withdrawn; deadlines preserved |
| Commission | provisional → earned → held/released → reversed in whole/part; version frozen |
| Payout | eligible → prepared → approved → submitted → pending → paid/failed/unknown; paid → reversed; unknown is reconciled before retry |

No payment state auto-completes a shipment, no delivery auto-pays a seller, and no refund success proves restock. FR-D054 requires each transition's actor, precondition, event and terminal side effects; FR-D055 requires daily order/gateway/allocation/ledger/payout reconciliation with zero unexplained minor-unit differences before payout release. Worked arithmetic is in [transaction examples](../08-marketplace/worked-transaction-examples.md).

## Transition authorization, prerequisites and audit effects

| Domain transition | Authorized actor/service and prerequisite | Atomic/audited business effect |
|---|---|---|
| Payment pending→authorized/captured | Verified payment adapter; signature/reference/currency/amount match; durable order operation | Unique provider result recorded; capture journal and stock commit deduplicated; never accepts browser success as proof |
| Payment captured→partially_refunded/refunded | Refund adapter after approved request and provider confirmation; remaining refundable amount reserved | Native refund plus immutable reversal journal; retry reuses operation key |
| Order pending→processing | WooCommerce payment integration after verified capture or separately approved offline policy | Preserve snapshot and allocation references; one receipt/purchase event |
| Order processing→completed | Authorized fulfillment/service policy after all required allocations terminal | Aggregate completion audit; no automatic payout assertion |
| Fulfillment allocated→picking→shipped→delivered | Own allocation fulfillment capability or trusted carrier; quantities within remaining amount | Shipment records and evidence; customer notice deduplicated; no finance rewrite |
| Return requested→authorized/rejected | Returns/dispute capability; ownership, policy and prior quantities checked | Reason/evidence/deadline audit; authorization alone does not refund or restock |
| Return received→inspected→resolved | Assigned returns operator; evidence and disposition | Separate inspected-restock command and linked approved refund outcome |
| Dispute opened→under_review→won/lost | Verified provider events and assigned dispute operator for evidence submission | Deadlines and holds; immutable suspense/reversal/fee entries from verified outcome |
| Commission provisional→earned→held/released | Finance policy service; capture/delivery/return-window prerequisites per approved contract | Versioned eligibility entry; holds prevent payout without deleting earned balance |
| Commission earned→reversed | Confirmed refund/chargeback or dual-approved adjustment | Reverse original policy share with source link; no historic rate editing |
| Payout eligible→prepared | finance prepare capability; reconciled payable, no holds, destination verified | Reserve included entitlements once and freeze batch digest |
| Payout prepared→approved | Different finance principal with step-up; unchanged batch digest | Approval audit binds exact amount/currency/destination |
| Payout approved→submitted→pending | Payment adapter; stable idempotency key; approved provider model | Durable provider reference; timeout stays unknown rather than retrying new money action |
| Payout pending→paid/failed/reversed | Verified provider truth; allowed monotonic transition and reconciliation | Immutable transfer journal; failure releases entitlement only after proven nonpayment |

For any invalid transition return a conflict, retain current state and record a redacted attempted-action audit. Manual finance corrections require distinct preparer/approver and immutable reversal/replacement. Concurrent transitions use aggregate versions and unique operation keys.
