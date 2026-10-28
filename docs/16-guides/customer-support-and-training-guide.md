# Customer support and training guide

Document ID: DOC-S-GUIDE05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: Customer Support Representative | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Customer support and training guide | Requirements: FR-S010; SEC-S002; SEC-S013
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Support intake and identity

FR-S010: Verify the requester through the approved account/order access flow before discussing private details. Ask for order reference and issue description, never full card details, password, MFA code or seller identity document. Guest order recovery uses secure verification, not order ID alone. Support scope is an assigned case; unrelated customer/seller data remains inaccessible. Record the minimum needed facts and correlation IDs in a restricted case.

| Customer need | Support action | Escalation / prohibited shortcut |
|---|---|---|
| Payment failed/unknown | Check authoritative order/payment state and provider reconciliation flag; explain pending state accurately | Payments engineer for unknown result; never advise repeated blind payment |
| Different sellers/shipments | Explain per-line seller and shipment/tracking; contact only allocated seller through approved workflow | Do not expose other seller's private contact or financial data |
| Price or offer changed | Show current terms and original purchase snapshot; distinguish cart quote from accepted order | No silent seller swap or retrospective snapshot edit |
| Outdoor safety/fit question | Link reviewed product evidence/size guide; acknowledge missing information | Product specialist; no invented safety recommendation |
| Smart-home compatibility | Check model/region/firmware/hub evidence and support date | Technical specialist; matching protocol label alone is insufficient |
| Return/refund/cancellation | Open scoped case with reason/item/quantity; check remaining entitlement and policy | Returns manager; no unauthorized refund approval or guarantee of timing |
| Download/account access | Verify owner/payment entitlement; issue approved recovery/access workflow | Never send another customer's private URL |
| Privacy request | Route authenticated request to Privacy; record tracking and legal hold status | Do not erase ledger or promise completion while provider task pending |
| Seller complaint/dispute | Capture neutral evidence and deadline, route independent case | Do not publicly declare fraud without review |

## Communication and handoff

Use plain localized language and specific next step, owner and expected update window. Avoid unsupported delivery/refund guarantees, internal stack traces and blame. A timeout means unknown/pending until reconciled; describe that state rather than claiming failure or success. Accessible templates do not depend on color/images and keep mixed-direction order/SKU references readable. The future notification module handles preferences and sensitive-data minimization.

Escalation packet contains site/release, case/order/line IDs, customer-described action, actual status, correlation ID, prior attempts and evidence link. Finance/engineering receive only necessary fields. Close a case when the customer-visible outcome and underlying authoritative state agree; send-time authorization/consent rules still apply. Support cannot override payout/commission/identity verification.

## Training plan

Provide separate sessions for customer support, catalog, seller fulfillment and finance. Each uses synthetic fixtures and a sandbox provider. Demonstrate failed payment recovery, split shipment, one-line return, inaccessible download, unsafe compatibility claim and privacy request; participant repeats without coach intervention. Proposed acceptance is8of10 successful priority tasks and zero privacy/financial authorization violation. Record participant role, version of guide, task outcomes and follow-up training; no training session or human approval occurred in this assignment. Refresh after material workflow/provider changes and review incident-driven gaps monthly.
