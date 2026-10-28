# Personas, journeys and service blueprints

Document ID: DOC-G-PERSONAS | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: UX Researcher | Reviewers: Business Analyst; Solution/Software Architect; QA/Test Engineer
Scope: Personas, journeys and service blueprints | Requirements: FR-G004; FR-G006; FR-G012; FR-G018 | ADRs: ADR-03; ADR-14
Dependencies: [conventions](../00-governance/documentation-conventions.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Provisional personas, not researched demographics

| Persona | Goal and context | Information need | Failure to prevent |
|---|---|---|---|
| Prepared outdoor buyer | Select a correctly sized insulated product for stated conditions | Manufacturer rating provenance, fit/size, packed weight and return terms | Treating an inferred equipment combination as safe |
| Smart-home household buyer | Add a device to a known ecosystem in a known region | Model/firmware, power, hub, installation needs and dated compatibility evidence | Assuming Wi-Fi or Matter labels prove all features interoperate |
| Repeat mobile customer | Reorder a known item with accessible small-screen controls | Exact variation, chosen seller, delivery grouping, complete total | Silent seller substitution or duplicate payment |
| Seller operations team | Publish offers and fulfill assigned items | Organization permissions, stock source, deadlines, financial status | Seeing another seller's customer/export data |
| Merchant editor | Change campaigns and product content safely | Preview, validation, accessible defaults, reset and scheduled expiry | Breaking checkout via unscoped template edits |
| Finance/support team | Resolve a partially fulfilled order or refund after payout | Item snapshots, allocation/ledger trail and scoped redaction | Editing historical price or assuming payout reversal succeeded |

Validate these task hypotheses in future moderated sessions with English/Persian, RTL/LTR, assistive-technology and small-screen participants. No demographic or conversion claims are inferred.

## Purchase service blueprint

| Stage | Customer action/frontstage | Backstage/owner | Evidence and recovery |
|---|---|---|---|
| Discover | Search/facet or navigate category | Search projection; catalog owner | Explain no-results; retain safe query/filter state during outage |
| Understand | Read specifications, evidence and seller disclosure | Canonical data plus offer eligibility | Unknown compatibility stays unknown; stale offer cannot guarantee stock |
| Select | Choose variation, quantity and seller | Server validates capability/version/offer | Missing/unavailable combination blocks purchase with next steps |
| Commit | Review shipping, coupon, tax and changed terms | Reservation and native checkout integration | Explicit reconfirmation on commercial change;preserve cart on failure |
| Pay | Provider-hosted/tokenized flow | Payment callback reconciliation | Duplicate callback is idempotent;late success follows reservation policy |
| Fulfill | View independent shipments | Seller allocation and carrier adapter | Delays don't collapse all allocations into one status |
| Resolve | Request cancellation/return/support | Returns/disputes plus financial reversal | Customer sees pending/completed distinctions;refund not complete until evidence |

## Seller service blueprint

Application submission → verification tasks → review/appeal → organization/team setup → catalog match or moderated proposal → offer validation/publication → allocated order fulfillment → settlement eligibility → statement/payout → dispute/adjustment. Each transition has an authorized actor and audit reason. Suspension prevents new eligibility while preserving order, return, dispute and ledger obligations. Verification materials have restricted retention/access; they never become storefront public data.

## Editor service blueprint

Select current preset/template → search allowed setting → preview desktop/mobile and RTL → validate focus/contrast/content → save scoped revision → invalidate presentation cache → verify customer-visible page → undo selected scope if incorrect. Seller branding follows a smaller allowlist. Content owners review technical provenance separately from visual publishing.

[Wireframe/task flows](../05-ux-ui/journeys-task-flows-and-wireframes.md) specify exact screen regions. [Cross-disciplinary walkthroughs](../17-validation/cross-disciplinary-review.md) connect these journeys to data, contracts, authorization, tests and work packages.
