# Jurisdiction and compliance decision matrix

Document ID: DOC-S-SEC05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; implementation verification NOT RUN
Owner: Legal/Compliance Specialist | Reviewers: Application Security Engineer; Privacy Specialist; QA/Test Engineer
Scope: Jurisdiction and compliance decision matrix | Requirements: SEC-S014
ADRs: ADR-03; ADR-09; ADR-14; ADR-16 | Dependencies: [authorization matrix](roles-capabilities-and-object-authorization.csv); [control matrix](controls-and-verification-matrix.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections2.5 and11; jurisdiction unknown; DEC-01–DEC-08; [source register](../00-governance/sources.csv)

## Decision gate and boundaries

SEC-S014: No live collection of money, seller onboarding evidence or marketplace activation proceeds until qualified reviewers resolve the applicable entity, operating/target countries, merchant/seller-of-record, fund flow, taxes and approved providers. English/Persian, timezone and Digikala reference are not evidence of jurisdiction. The table is a requirements outline for counsel and business owners, not legal advice or finalized terms. No jurisdiction-specific legal source has been selected because applicability is unknown.

| Topic / decision | Provisional approach | Alternatives / consequence | Owner and gate / required evidence |
|---|---|---|---|
| Entity, territories, provider availability DEC-01/04 | Independent store contracts and restricted approved territories | Additional markets expand laws, taxes and provider coverage | Legal; phase1 before procurement; entity/territory matrix and official sources |
| Merchant/seller of record and funds DEC-02 | Hosted regulated marketplace provider; no custom custody | Platform merchant vs seller merchant changes invoices/liability | Legal with Finance; phase3/6; signed legal/provider flow review |
| Seller/marketplace terms | Explicit role, obligations, moderation, suspension, appeal and exit | Seller-owned listing vs canonical offer terms differ | Legal; phase6; approved versioned agreement |
| Consumer cancellation/returns/warranty | Configurable jurisdiction-aware policy; preserve statutory rights | Different goods/services/subscriptions exceptions | Returns/Dispute; phase6; counsel-reviewed rules and customer text |
| Taxes/invoicing DEC-03 | Provider/native calculation only with approved tax model | Marketplace facilitator/withholding obligations may apply | Finance/Tax; phase3; official tax source and accountant sign-off |
| Privacy/cookies/transfers DEC-07 | Minimize; separate purposes; consent where required | Processing basis and localization differ by market | Privacy; phase1/8; data map and approved retention/notice |
| Accessibility obligations | WCAG2.2AA proposed product target | Legal standard may add requirements/reporting | Legal and Accessibility; phase2/8; applicable law assessment |
| Product safety/recalls DEC-08 | Evidenced claims; trace batches/sellers and recall workflow | Category restrictions or certification registration | Product specialists; phase7; approved catalog evidence |
| Restricted goods and age/usage limits | Deny unsupported/restricted category until review | May require licensing, checks or total prohibition | Trust and Safety; phase1/7; country/category matrix |
| Seller identification and reporting | Provider evidence preferred; scoped verification states | Platform reporting/KYC duties depend on model | Marketplace Operations; phase6; provider/counsel responsibility map |
| Dispute/chargeback handling | Time-bound evidence workflow; preserve records | Provider/network or legal deadlines vary | Returns/Dispute; phase6; approved deadlines and escalation |
| Warranty, electrical/technical claims | Manufacturer evidence with version/region/date | Local installer/certifier may be needed | Product specialists; phase7; approved claim records |
| Platform reporting/accounting | Reconciled immutable allocation/settlement evidence | Tax reporting forms and retention differ | Finance; phase6/8; auditor-reviewed reporting mapping |
| Marketing/loyalty/promotion rules | Honest pricing, opt-out and real campaign clocks | Prize/credit/loyalty terms can trigger special rules | Legal; phase5; approved campaign/reward policy |

## Approval evidence and unresolved effects

Each decision record requires recommended choice, alternatives, owner, reviewer identity/date, affected markets/capabilities and expiry/review trigger. No generated review is an approval. A provider's marketing page is insufficient proof of country or funds support; require official contract/docs and sandbox evidence under DEC-04/09. Keep affected functions disabled until gate completion while leaving public catalog and design work possible. If territory changes, rerun the decision matrix and migration/privacy review before exposing the new region.

Final policy pages are owned by Legal and Content Designer; this repository specifies required topics, version tracking, consent/acceptance evidence and presentation. Product safety restrictions are enforced at publish/import/API boundaries, not merely as a footer disclaimer. Recall execution preserves seller/order matching and contacts only affected customers through an approved operational workflow.
