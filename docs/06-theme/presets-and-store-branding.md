# Presets and store branding

Document ID: DOC-U-13 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Theme Developer
Reviewers: Design System Designer; Accessibility Specialist; Application Security Engineer
Scope: reusable native block theme presentation and customization
Requirements: FR-U024; FR-U036 | ADRs: ADR-01; ADR-12; ADR-15; ADR-16
Dependencies: [component catalog](../05-ux-ui/component-specifications.md); [settings](../05-ux-ui/settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–9; [source register](../00-governance/sources.csv)

## One theme, three original presets (FR-U024)

| Preset | Visual values and density | Homepage/PDP emphasis | Never changes |
|---|---|---|---|
| Outdoor | action #245746; warm canvas #F6F5EF; comfortable cards16px; card radius8px | field photography, activity collections, fit/specification/care guidance | Pricing, permission, stock, tax, returns and supported product capabilities |
| Smart Home | action #284F9E; canvas #F4F6FA; comfortable cards; radius12px | clean product photography, compatibility evidence, installation and solution guides | Same commerce service contracts and customer/seller identity model |
| Neutral marketplace | action #234D70; canvas #F6F7F9; dense cards12px; radius8px | category discovery, offers and clearly labelled promotions | Same native inheritance and module feature gates |

All palettes inherit high-contrast semantic status colors and the protected focus style. Logos, text, icons and photography are original or licensed assets supplied later; this assignment has not created them. The Digikala reference affects information-density goals only. Fonts default to system stack until DEC-12 licensing and script-coverage checks close. Branding never implies a jurisdiction or a currency.

## Application workflow

Store administrator chooses a draft preset, maps local logo/media, selects density and navigation composition, previews core screens in English/Persian and saves a native/custom-settings revision. Applying a preset does not silently erase user global styles or edited templates; show retained overrides and a separate reset action. Existing product/content records remain unchanged. Imported IDs are mapped to destination assets/categories; missing resources are reported before publishing. A third website can create a profile that uses any preset with different schema and modules without a code fork.

Seller storefront branding is a bounded overlay: approved logo, banner focal point, accent from a safe palette and short display text. It is confined to the seller header and cannot disguise preferred-offer selection, change checkout buttons, hide platform disclosures or alter global navigation. Moderator review is required for claims/marks; seller image ownership and MIME/content validation apply.

## Acceptance

TEST-U024 compares all three presets across simple/variable/external/PDP/cart/account/seller screens, verifies layout at320/768/1280px and both directions, and confirms identical server totals/permissions. A theme switch and profile export/import retain business data. A missing banner uses text and a neutral surface. DEC-10 human design approval remains open; no pixel measurements or usability results are claimed.
