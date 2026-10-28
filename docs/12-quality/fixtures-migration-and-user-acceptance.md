# Fixtures, migration and user acceptance

Document ID: DOC-S-QA06 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: QA/Test Engineer | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Fixtures, migration and user acceptance | Requirements: NFR-S010; NFR-S011; NFR-S012
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; ASM-03; [source register](../00-governance/sources.csv)

## Synthetic fixture catalog

All fixtures describe future test data, not real customers or executed migrations. Their IDs are local to test design and map to actual native/provider IDs at setup.

| Fixture | Records and boundary cases |
|---|---|
| FIX-S-OUT | Outdoor variable tent P100 with V101 sizeM and V102 sizeL; offers OA from sellerA and OB from sellerB against V101; OA stock1, OB stock3; approved materials/weight evidence; missing-size state |
| FIX-S-HOME | Controller P200 version2 regionR1 firmware3.2; evidenced hubH1 requirement; unsupported hubH2; installation service and same-seller component bundle; unknown compatibility state |
| FIX-S-AUTH | Sites outdoor/smart with separate keys; sellersA/B; owner/catalog/fulfillment/finance memberships; customerC/D; platform/support/dispute roles; revoked member |
| FIX-S-MONEY | Example USD exponent2 only: itemA10000/itemB5000; coupon1500 allocated1000/500; shipping600/400; illustrative tax10% on discounted items only; total15850; no asserted business policy |
| FIX-S-TYPES | Simple physical, external URL, virtual/downloadable with private asset, grouped children; named proposed adapters with enabled and unsupported marketplace combinations |
| FIX-S-EVENT | One provider capture success delivered twice, refund event before capture acknowledgement, worker crash after provider request, signed payload altered under same event ID |
| FIX-S-SEO | Published and draft products, two variations/sellers, empty category/facet, retired seller/product, page2, locale translation gap, stale feed item |
| FIX-S-MIG | Native single-seller catalog with50products/100variations,20historical paid/refunded orders, open reservation, scheduled job and pending refund; legacy URLs and native stock snapshots |

The USD/tax example is test arithmetic only. FIX-S-MONEY total is9000+4500+1000+1350=15850; tax/commission applicability is approved separately. Financial examples in the marketplace family are authoritative for their own cases.

## Migration acceptance

Capture native identities, URLs, stock, open-order totals and pending obligations before the future rehearsal. Map first-party seller without rewriting historical order items. Run dry-run validation and identity collision detection; record checkpoints and apply only version-matched catalog changes. Reconcile stock and monetary totals before enabling third-party offers. During a cutover freeze new writes for the shortest rehearsed interval; process already accepted payments through a monitored exception lane. If no irreversible effects occurred, revert mapping/configuration; after new orders or provider events, disable new marketplace sales and repair forward. Historical records are never overwritten to make reconciliation appear clean.

## User acceptance sessions

Product Owner recruits representative buyer, seller owner, catalog editor, fulfillment operator, finance and support participants; independent Accessibility review accompanies relevant tasks. Use synthetic orders and sandbox payment only. Tasks: select outdoor size/seller and return one line; validate smart-home hub evidence and add service; onboard seller and moderate offer; reconcile payout/refund; rebrand third site and disable one optional module; recover failed payment without duplication.

Observe task completion, hesitation, errors and understanding of seller/fulfillment/price terms. Proposed gate:>=8of10 unaided completions per priority persona across10participants, zero unrecognized wrong-seller purchase or double-charge path, and documented fixes for every critical error. This is a provisional usability gate, not a confidence interval or real user research result. Training review requires participants to find the relevant guide and execute the procedure correctly. Record actual sessions and approvals only in future work.
