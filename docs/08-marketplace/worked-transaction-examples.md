# Worked transaction examples

Document ID: DOC-D-EXAMPLES | Title: Worked transaction examples | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Payments/Settlement Engineer | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D025; FR-D028–FR-D031; FR-D055; FR-D066; FR-D071 | ADRs: ADR-07; ADR-08; ADR-09
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Illustrative policy only

All examples use USD with exponent 2, 10% tax on goods and shipping, 10% platform commission on discounted goods net, and seller entitlement to shipping. These are arithmetic fixtures, not applicable tax, fee, currency or legal policy. No gateway fee or FX is assumed unless named. Amounts below are integer minor units; 10000 means USD 100.00. Under this illustrative model the platform's subledger holds tax payable for later remittance by the approved responsible party.

## 1. Simple purchase

Goods 10000 + tax 1000 + shipping 500 + shipping tax 50 = capture 11550. Commission = 10000 × 10% = 1000. Seller payable = goods 10000 + shipping 500 − commission 1000 = 9500. Tax payable = 1050. Sale journal: Dr provider_clearing 11550; Cr seller_payable 9500; Cr tax_payable 1050; Cr platform_commission 1000. Credits sum 11550. Payout of 9500: Dr seller_payable 9500; Cr provider_clearing 9500. Remaining clearing 2050 equals tax 1050 + commission 1000. Stock hold for quantity1 commits once; a second callback changes no amount or stock.

## 2. Two sellers, shared coupon and split shipping

Seller A has quantity2 × 5000 = 10000 goods; B has quantity1 × 5000 = 5000. A seller-funded shared coupon of 1500 allocates proportionally: A1000 and B500. Net goods A9000, B4500. Tax A900, B450. Shipping A1000 and B500; shipping tax A100 and B50. Customer capture = 9000 + 4500 + 900 + 450 + 1000 + 500 + 100 + 50 = 16500.

| Allocation | Net goods | Shipping | Total tax | Commission | Seller payable |
|---|---:|---:|---:|---:|---:|
| A | 9000 | 1000 | 1000 | 900 | 9100 |
| B | 4500 | 500 | 500 | 450 | 4550 |
| Total | 13500 | 1500 | 1500 | 1350 | 13650 |

Sale journal: Dr provider_clearing 16500; Cr seller_payable_A 9100; Cr seller_payable_B 4550; Cr tax_payable 1500; Cr platform_commission 1350. Credits = 9100 + 4550 + 1500 + 1350 = 16500. Shipments A and B progress independently; one purchase event uses order gross16500 and explicit allocation shares, not two duplicate purchases.

## 3. Partial refund before payout

Use example2; return one A unit. Its original net goods share is 4500 and tax450. Shipping is not refunded under this illustrative case. Refund = 4950; commission reversal450; seller entitlement reversal4050. Journal: Dr seller_payable_A 4050; Dr tax_payable 450; Dr platform_commission 450; Cr provider_clearing 4950. A payable becomes 5050, B4550, tax1050, commission900; clearing becomes11550. Check: 5050 + 4550 + 1050 + 900 = 11550. A damaged returned item is not restocked; an inspected resalable item gets a separate one-unit restock operation.

## 4. Refund after payout

Use original example2 and first pay A9100 and B4550. Clearing then equals 2850 (tax1500 + commission1350). Return the same A unit for4950. Journal: Dr seller_receivable_A 4050; Dr tax_payable450; Dr platform_commission450; Cr provider_clearing4950. Clearing is now −2100, receivable4050, tax1050 and commission900. Assets clearing−2100 + receivable4050 =1950, matching liabilities/revenue1050 +900. A negative provider clearing balance signals funding/liability handling, not proof a provider permits overdraft. Actual refund submission may require platform funds or a verified transfer reversal under the approved provider arrangement. A future A earning offsets the receivable lawfully; old payout history stays paid.

## 5. Repeated and out-of-order payment webhook

Provider event `evt-example-001` reports capture16500 for the same provider payment reference. First verified delivery creates one inbox record, one payment transition, one stock commit per line and one sale journal. Second delivery has identical event ID/digest and returns acknowledgement with no new business effects. A different event ID reporting the same capture is deduplicated by provider payment/capture reference as well. A later delivery of an older pending event cannot revert captured state. A mismatched amount/currency enters exception review; it does not post a second sale.

## 6. Same variation bought from two sellers

Native product101, variation102 (size M) is offered by A as offer-A at3000 and B as offer-B at3200. Quantity1 each creates two distinct cart keys and pools. Goods total6200, tax620, no shipping in this illustrative pickup case; capture6820. Commission A300, B320; seller payable A2700, B2880. Credits2700 +2880 +620 +620 =6820. A stock decreases1 and B stock decreases1; native variation stock is not also decremented in marketplace mode. A's refund cannot consume B's refundable quantity.

## Rounding and FX boundary

For a 1-unit discount split across three equally eligible lines, exact shares are one-third. Floors are0,0,0 and the remaining1 goes to the smallest stable order-item key among equal remainders. Sum remains1. Tax rounding follows the configured native WooCommerce policy and is snapshotted; these examples have no fractional tax remainder. Settlement FX is excluded until an approved provider contract defines both currencies, rates, fees and accounting; no undocumented conversion is inferred.

## Verification record

The arithmetic in this document is checked by documentation-only integer calculations; future tests must additionally establish actual provider, tax engine, idempotency and stock behavior. Test cases in the [register](../12-quality/test-cases.csv) cover duplicate, late, partial and post-payout scenarios. No real payments, accounts or customer data were used.
