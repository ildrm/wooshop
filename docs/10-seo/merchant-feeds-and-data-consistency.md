# Merchant feeds and data consistency

Document ID: DOC-S-SEO05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Merchant feeds and data consistency | Requirements: SEO-S009
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section 10; SRC-S004–SRC-S010; [source register](../00-governance/sources.csv)

## Feed contract

SEO-S009: One approved feed adapter per destination consumes the published catalog/offer projection; it cannot become a stock or price authority. Feed identity is a stable site + variation + seller-offer mapping, not the display title. Store mapping includes native product/variation ID, offer UUID, provider item ID, locale, target market, currency, schema version, projection version, last export and provider acknowledgement. Idempotent upsert uses this identity. Deletion/unavailability produces an explicit remove or availability update as the destination contract requires. No undocumented fallback republishes an expired offer.

Required export checks: approved seller and product, eligible capability/provider, evidenced identifier, valid image rights and crawlable media, current active amount/currency, selected landing URL, shipping/return policy and approved market. Unknown jurisdiction, merchant-of-record, feed account model or legal/provider availability blocks feed activation (DEC-01/02/04). A general marketplace schema graph does not establish Merchant Center acceptance.

Google's [price attribute specification](https://support.google.com/merchants/answer/6324371) (SRC-S008) requires feed, landing and checkout price/currency agreement and warns against IP-dependent price changes. The proposed connector therefore verifies the actual selector URL using the same declared market and compares integer minor amounts before formatting destination decimals. Taxes and shipping presentation follow approved destination rules; the illustrative calculation currency elsewhere is not a feed currency decision.

## Freshness, failure and audit

Catalog/offer change enqueues an item export with version. Older queued work is coalesced into the newest projection; acknowledgement of an older export never overwrites a newer status. Proposed target: 95% of eligible updates sent within five minutes; provider processing time is measured separately. A mismatch detected in any sampled item suppresses that item's export, alerts merchandising within15minutes, and records sanitized difference/version. A feed outage keeps authoritative checkout functioning, stops claims that synchronization is current, uses the canonical projection retry policy (maximum5total attempts:initial plus4retries at10s,1m,5m,15m),then quarantines for owned reconciliation; daily full reconciliation catches missed events.

Tests include two sellers with equal variation but different price, sale expiry, stock zero, seller suspension, currency exponent0 and3, tax-display modes, wrong selector, unauthorized export access and out-of-order acknowledgement. Compare exact fields, not rounded display strings. Feed logs exclude customer data and credentials. Owner is SEO/Content Strategist; API/Integration Engineer maintains mapping; Store Administrator approves commercial settings; Finance/Tax Specialist approves tax representation.
