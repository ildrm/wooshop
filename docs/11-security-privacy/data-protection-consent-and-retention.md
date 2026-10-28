# Data protection, consent and retention

Document ID: DOC-S-SEC04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; implementation verification NOT RUN
Owner: Privacy Specialist | Reviewers: Application Security Engineer; Privacy Specialist; QA/Test Engineer
Scope: Data protection, consent and retention | Requirements: SEC-S012; SEC-S013
ADRs: ADR-03; ADR-09; ADR-14; ADR-16 | Dependencies: [authorization matrix](roles-capabilities-and-object-authorization.csv); [control matrix](controls-and-verification-matrix.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section11; SRC-S001; SRC-S011–SRC-S013; [source register](../00-governance/sources.csv)

## Purpose and classification

SEC-S012: A data inventory binds each field to purpose, access classification, authoritative owner, retention class and deletion method. Public catalog and seller trade disclosures are separate from restricted verification records; customer addresses are shared with a seller only for its allocated fulfillment, not the customer's entire multi-seller order. Analytics accepts product/offer IDs and aggregate values, not email, phone, address, search text containing personal information or raw payment identifiers. Pseudonymous identifiers remain restricted, not automatically anonymous.

| Class | Provisional retention / action | Access and exception |
|---|---|---|
| Guest wishlist/history | Local session or consented storage;30days rolling | User device/session only; erase on clear/withdrawal; no cross-device identity guessing |
| Account wishlist/history | Until user removal; purge inactive history after180days | Account owner; selected support read only by explicit case |
| Unconfirmed alert subscription |7days then delete | Notification service minimal address/token hash |
| Confirmed marketing preferences | Active until unsubscribe; suppression hash retained12months provisionally | Privacy/notification service; no marketing sends after withdrawal |
| Operational logs |30days application;90days redacted security | SRE/Security; legal hold records incident reason and expiry |
| Seller verification documents | Provider-held preferred; local copy only if essential;90days after review/termination provisionally | Restricted onboarding reviewers; legal/contract retention gate may extend |
| Orders, invoices, ledger, disputes | Legal duration unresolved DEC-07; no automatic purge | Finance and scoped operational views; immutable economic evidence with minimized identity |
| Backups | Daily35day window provisionally; encrypted and access-logged | Restore only to controlled environment; replay deletion tombstones before opening access |

These durations are planning defaults, not jurisdictional legal conclusions. DEC-07 must approve purposes, lawful bases, periods, legal holds and regional transfer rules before production. Financial retention can require preserving ledger identifiers while erasing unnecessary customer fields. Private attachments are not retained indefinitely just because linked to an order.

## Consent and rights workflow

Essential checkout/session/security processing is documented separately from optional analytics, recommendations, advertising and newsletters. Where consent is required, no optional storage/event before opt-in; decline is equally usable. Record preference version, purpose, timestamp, method and region decision; withdrawal blocks queued optional messages before send and invalidates derived personalization. Transactional receipts/support updates use their permitted purpose and cannot be relabeled promotional to bypass preference controls. Default notification templates contain no third-party marketing pixels.

SEC-S013: Export/erasure requests authenticate the requester, collect only minimum verification, scope all sites independently, and route legal holds to Privacy/Finance. Proposed operational target is acknowledgement within2business days and triage within5; legal deadline is set by DEC-01/07. Export includes the user's own records in readable format without other sellers' private data or other customers' identities. Erasure records entity tombstones; removes preferences, tokens, optional history and unnecessary PII; pseudonymizes retained financial references under approved policy. A second attempt is idempotent. Jobs/indexes/cache/provider deletion receipts are reconciled; unavailable provider marks request incomplete rather than falsely finished. Backup restore applies tombstones before serving traffic. Do not delete evidence subject to a valid documented hold; notify user of limited retention through reviewed language.
