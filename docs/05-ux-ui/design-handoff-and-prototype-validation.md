# Design handoff and future prototype validation

Document ID: DOC-U-06 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: UX Researcher
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U017 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 6 and 9; [source register](../00-governance/sources.csv)

## Deliverable boundary

FR-U017 is a future handoff contract. This repository contains textual design specifications, not a Figma project, screenshots, a functional prototype or human usability results. Design System Designer owns future component authoring; UX Researcher runs task studies; Product Owner accepts scope only after issues are triaged. AI analytical review is not independent human approval.

## Future frame/component naming

Frames: `Store/Mode/Locale/Viewport/Screen/State`, for example `Outdoor/Marketplace/fa-RTL/390/PDP/price-changed`. Components: `Commerce/Card/Product`, `Commerce/Offer/Selector`, `Navigation/MegaMenu`, with variant properties density, state and direction. Token names follow semantic families in [tokens](design-system-and-tokens.md); avoid naming a color after a store in reusable components. Each frame carries SCR/CMP/FR/TEST IDs, content fixture ID, component version and owner. A future library release includes an annotated spacing grid, focus order, accessible names, empty/error/loading states and responsive behavior.

## Required prototype tasks

| Task | Fixture and steps | Proposed success/evidence |
|---|---|---|
| Outdoor fit/offer purchase | Long Persian pack title; select size; compare two sellers; price changes in cart | Participant can identify chosen size/seller/new total and reconfirm without hidden substitution |
| Smart home evidence | Bulb needs a hub; firmware/region compatibility uncertain; optional installation | Participant finds source/conditions, understands unknown compatibility and avoids accidental service purchase |
| Mobile facets and comparison |390px width; six facets; two saved products | Apply/reset filters, restore via Back, compare one criterion without horizontal page scroll |
| Keyboard error recovery | Invalid postcode, unavailable offer, gateway return pending | Reach summary and field, retain data, distinguish pending from paid |
| Seller scoped work | Seller catalog vs finance memberships | Each role locates permitted work, understands denied operation and cannot see unrelated organization |
| Branding portability | Third generic store, no companion plugin; then unrelated theme with plugin | Ordinary catalog/checkout works in first; essential enhanced workflows remain accessible in second |

Proposed recruitment is5–8participants per distinct audience/locale cohort for formative study, including assistive-technology users; it is a planning range, not a statistical proof. Tasks have no pre-filled success claims. Record completion, assistance, errors, time and subjective confidence; preserve consent, anonymize notes and do not recruit through this assignment.

## Approval and visual baselines

After the first trial, defects are logged against IDs; blockers include inaccessible purchase path, silent seller switch and misleading price/claim. Updated designs require Accessibility Specialist, domain specialist and engineering feasibility reviews, then Product Owner acceptance. Baseline images are captured only from the implemented, approved version using deterministic synthetic fixtures. Visual diffs are reviewed for layout/meaning, never accepted purely by pixel threshold. Breakpoint/RTL/preset/state coverage follows the test strategy. This handoff remains pending human approval and implementation.
