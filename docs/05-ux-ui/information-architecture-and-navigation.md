# Information architecture and navigation

Document ID: DOC-U-02 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: Design System Designer
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U001; FR-U003; FR-U007; FR-U008 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 6 and 9; [source register](../00-governance/sources.csv)

## Proposed navigation model

FR-U001, FR-U003, FR-U007 and FR-U008 define a task-first hierarchy. Global landmarks are skip link, announcement, header, primary navigation, main, contextual support and footer. The order is identical in DOM and visual reading order; RTL changes inline direction without reversing the logical task sequence. A site logo links home with the accessible name “Outdoor home” or “Smart Home home”. Search and cart remain available without opening the category menu.

| Navigation zone | Content owner | Structure and limits | Behavior |
|---|---|---|---|
| Primary catalog | Catalog administrator | Department → category → optional subcategory; maximum three visible levels | Each parent is a genuine destination link; adjacent disclosure button expands children |
| Discovery | Merchandiser | Brands, new arrivals, guides, campaigns | Labels describe destination; expired campaigns are removed or linked to archived guide |
| Utilities | Store administrator | Account, help, optional coarse delivery region | Account changes label after login; location defaults to “Choose delivery area”, never infers address |
| Customer workspace | WooCommerce plus modules | Orders, addresses, downloads, saved items, preferences; subscriptions/bookings only when supported | Current destination is identified; guest redirect returns to the requested safe relative route |
| Seller workspace | Marketplace module | Overview, catalog, offers, orders, returns, team; finance only for authorized membership | Every screen resolves active seller organization; no tenant selector bypass |
| Footer | Content editor | About/contact, delivery/returns, accessibility, privacy/preferences, seller information | Legal titles and policy versions supplied through qualified review gate |

## URL and content organization

Product category is not the same as brand, compatibility ecosystem or an activity collection. Category taxonomy drives primary browsing; typed schema facets filter within a category; curated collections are editorial landing pages with unique introductions. One canonical product can belong to several browsing categories but has one product URL. Seller offers remain selections against a canonical product, not duplicate editorial product pages. See [SEO indexation matrix](../10-seo/url-indexation-canonical-matrix.csv).

Proposed site-specific examples: Outdoor → Packs → Daypacks; Activity → Hiking as a collection. Smart Home → Lighting → Bulbs; Ecosystem → named integration as an evidence-backed collection. These are configurable entries, not hardcoded code paths. A third site can rename or replace all taxonomies.

## Search and wayfinding

Search accepts product names, model identifiers and approved synonyms. The field has a visible label and clear action. Suggestions are supplementary; Enter navigates to a complete server-rendered search page. Breadcrumbs reflect the selected primary category and never include private seller/order data. Product pages expose “Back to results” only when a safe same-site context exists; canonical breadcrumbs remain stable.

Mobile categories open a titled dialog containing links and explicit back buttons between levels. The desktop mega menu is a disclosure, not an application menu: Tab moves through links, Escape collapses and returns focus to its button; pointer hover may preview only when click/keyboard also work. No navigation requires hover. A maximum of eight promoted links per column is a design default, with overflow through “View all”, avoiding an unbounded tab sequence.

## Content/state contract

Missing categories show a useful catalog entry route; a deleted link yields a genuine 404 with search. Empty categories show guidance and related editorial content rather than unrelated promotions masquerading as results. Long Persian labels wrap to two lines in navigation; exact model identifiers remain unbroken where practical and use bidirectional isolation. Navigation analytics record normalized destination ID, never raw search text or address. Acceptance: TEST-U001 verifies all destinations by keyboard at 320 CSS px and RTL; TEST-U003 checks crawlable category/result links and browser history.
