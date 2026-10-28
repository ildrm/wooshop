# Staffing, estimation and procurement

Document ID: DOC-G-STAFFING | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Project/Program Manager | Reviewers: Product Owner; Solution/Software Architect; QA/Test Engineer; Release/Maintenance Owner
Scope: Staffing, estimation and procurement | Requirements: FR-G009; FR-G016; FR-G019 | ADRs: ADR-02; ADR-06; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [decisions](../00-governance/decisions.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Capacity assumptions

A planning team might combine roles into a product/analysis lead, program coordinator, solution/commerce architect, UX/design lead, frontend/block engineer, two backend/commerce engineers, QA automation lead, and SRE support. Finance, legal, privacy, accessibility, SEO and the two product-domain specialists contribute at gates. This is an example capacity model, not a staffing commitment. Roles with part-time availability can govern the critical path more than total engineer count.

Assume only60–70% of a person's calendar capacity is available to planned delivery after reviews, incidents, coordination and leave; validate the assumption at discovery. Security, settlement and accessibility reviews need reserved slots before code completion. Maintain separate payout preparer/approver and independent reviewer availability even when other roles combine. [RACI](../00-governance/raci.csv) assigns functions; actual individuals remain unassigned.

## Estimation method

The WBS uses M=2–4, L=4–8 and XL=8–16 person-weeks per bounded package as provisional order-of-magnitude ranges. They are not elapsed weeks, measured productivity, quotes or a complete budget. Decompose each package by capability/adapter, reuse native functionality, identify unknowns, then estimate optimistic/likely/pessimistic effort with confidence and assumptions. Discovery and provider qualification replace guesses with proof. Recurring maintenance packages represent an iteration or planned service period and are not added as perpetual finite effort.

Use dependency graph scheduling and the capacity actually available per specialist. Do not divide total person-weeks by headcount to promise a launch date. Include review/rework, content evidence collection, migration rehearsal, provider outage contingencies, legal/procurement lead times and knowledge transfer. Avoid counting the same native WooCommerce capability as a custom build twice. A change to marketplace provider or funds model reopens affected estimates.

## Procurement worksheet

| Category | Need and cost basis | Responsible reviewer | Proof and exit requirement |
|---|---|---|---|
| Hosting/database/cache/backups | Per-site capacity,storage,egress,restore support | DevOps/SRE Engineer | Isolation,region,patched versions,restore/export/recovery evidence |
| Gateway/marketplace payouts | Transaction/refund/payout/dispute/FX fees and reserves | Finance/Tax Specialist with Legal/Compliance | Entity/country eligibility,funds flow,webhook/reconciliation,exports and residual obligations |
| Product-type extensions | License per site,renewals,support and adapter work | WooCommerce Solution Specialist | Exact blocks/HPOS/type combinations,renewal/booking data export and handoff |
| Search/messaging/scan services | Query/message/storage/scan units,overages and support | API/Integration Engineer | Privacy/subprocessors,rate limits,outage fallback,replacement mapping |
| Fonts/media | Per-site or distribution rights,creation and localization | Graphic/Product Media Specialist | License evidence,fallback fonts,original assets and portable sources |
| Specialist assurance | Independent reviews,devices,usability recruitment | QA/Test Engineer | Deliverable scope,qualified reviewer,remediation and retest budget |
| Maintenance | Updates,incident response,provider recertification and EOL | Release/Maintenance Owner | Service hours,response targets,ownership and exit documentation |

No price or vendor commitment is made. Use [integration catalog](../09-api-integrations/integration-catalog-and-data-mapping.md) and [provider exit plans](../09-api-integrations/provider-verification-and-exit-plans.md) for each proposed dependency. DEC-04/05/09/12 approve eligibility, build cost, compatibility and licensing before adoption. A free core dependency still has hosting, security and maintenance costs.

## Handover economics

Budget an overlap period for support training, restore and reconciliation drills, and provider migration. Contract termination does not end paid subscription, refund, dispute, tax record or seller-balance obligations. Ownership of export files and safe deletion verification belongs to named operators, not solely a vendor success contact.
