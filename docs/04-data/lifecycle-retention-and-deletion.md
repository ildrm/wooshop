# Lifecycle, retention and deletion

Document ID: DOC-D-LIFECYCLE | Title: Lifecycle, retention and deletion | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D006; FR-D040; FR-D042; FR-D058 | ADRs: ADR-10; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Retention authority

The central privacy schedule and DEC-07 determine approved durations before launch. This document defines data dependencies and proposed operational defaults, not a legal retention conclusion. Each record carries purpose, classification, owner, retention trigger, delete/anonymize action and legal-hold flag. A deletion request does not justify erasing immutable transaction evidence required by the approved legal model; unnecessary identity fields can be removed while references and totals remain.

| Data class | Proposed trigger/default pending DEC-07 | Action and dependency |
|---|---|---|
| Anonymous unpurchased quote | Expiry + 24 hours | Delete contact/session details; retain aggregate nonidentifying error counts |
| Reservation dedup record | Terminal + 90 days | Strip session linkage; retain operation reference needed for reconciliation |
| Failed import staging | 7 days | Delete original file and sensitive row errors; retain audit digest/status |
| Generated operational export | 24 hours | Delete artifact; retain access audit under security schedule |
| Provider inbox raw body | Verified/processed + 30 days | Minimize to event ID, signature outcome, digest and business reference; legal hold overrides |
| Seller verification files | Rejected/terminated + approved review window | Provider storage preferred; restrict and delete under legal schedule |
| Offer/specification revision | Retired and no active references | Tombstone ID; preserve purchased/evidence references and redirects |
| Order/ledger/tax/commission evidence | Jurisdiction-specific duration unresolved | Freeze destructive purge until Legal/Finance decision, not indefinite unexamined retention |
| Search/cache projection | Invalidation/TTL | Remove/rebuild immediately; erasure propagates to all copies |

## Deletion graph and access

Delete/revoke sensitive attachments, invalidate signed URLs and remove search/report projections before reporting completion. Jobs queued before consent withdrawal re-check delivery eligibility. A seller suspension does not delete customer claims, financial liabilities or evidence; it narrows access and disables new sales. Revoked memberships stop live API, export, media and job access immediately. A deleted user's organization can continue with another verified owner; organization history is not keyed to a password account.

## Deactivation and uninstall

A disabled storefront module hides new feature entry points but preserves its data export and required administration path. Marketplace shutdown stops new offers/checkouts, resolves reservations, fulfills/refunds active orders, settles or transfers balances under approved arrangements, and exports evidence. Background reconciliation remains under kernel control until a signed operational handoff. Uninstall is blocked while open obligations or legal holds exist; eventual purge is a separate auditable authorized process, never the default plugin deactivation behavior.

## Acceptance

FR-D058 requires a synthetic subject-erasure fixture across primary stores, media, exports, search, caches, logs and pending jobs. The result lists erased, anonymized and lawfully retained fields plus reason and owner. FR-D042 checks that disabled modules still complete an owed refund and expose historic statements without re-enabling new sales. No retention durations here supersede the approved privacy schedule.
