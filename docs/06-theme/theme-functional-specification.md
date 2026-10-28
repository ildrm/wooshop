# Theme functional specification

Document ID: DOC-U-10 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Theme Developer
Reviewers: Design System Designer; Accessibility Specialist; Application Security Engineer
Scope: reusable native block theme presentation and customization
Requirements: FR-U021; FR-U023 | ADRs: ADR-01; ADR-12; ADR-15; ADR-16
Dependencies: [component catalog](../05-ux-ui/component-specifications.md); [settings](../05-ux-ui/settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–9; [source register](../00-governance/sources.csv)

## Product boundary (FR-U021)

The theme is one reusable distributable for all stores. Its responsibility is templates, patterns, semantic markup, presentation tokens, responsive layouts and editor previews. Persistent product attributes, seller membership, stock, prices, rewards, notifications, capabilities and calculations live in WordPress, WooCommerce or the companion plugin as assigned. Switching theme must preserve them. The theme has no hidden commerce database, customer event duplicator, schema emitter or proprietary page builder.

Verified external context: WordPress supports theme-level global settings/styles and native editing surfaces (SRC-U001); templates/parts customized through Site Editor are saved in the site's database (SRC-U003). The design proposed here selects these native mechanisms. No actual theme files are delivered by this assignment.

## Feature ownership and fallback

| Theme feature | Runtime owner/dependency | Without companion plugin | With unsupported provider |
|---|---|---|---|
| Header, logo, announcement, navigation, footer | WordPress identity/navigation/content; theme presentation | Fully functional basic links/search | Provider-independent |
| Home/landing patterns | WP content and WC native collections; custom campaign module optional | Static editorial and native collections; no misleading live countdown | Scheduled campaign control removed if data unavailable |
| Catalog/grid/list/cards | WooCommerce products; optional search/spec module | WC native sort, categories and product links | Unsupported action links to product details |
| Rich PDP | WC types/attributes; plugin specs/offers/compatibility | Native gallery/description/attributes/purchase | Read-only unsupported capability notice; no fabricated price |
| Cart/checkout/account | WooCommerce blocks/session/order authorization | Native commerce preserved | Checkout methods/types disabled according to adapter policy |
| Wishlist/compare/history/alerts/Q&A | Companion modules | Controls absent with links not dead placeholders | Informative unavailable state for existing saved routes |
| Seller offers/portals/allocations | Marketplace module/provider adapter | Marketplace cannot initiate sales; native single merchant unaffected | Obligation access follows service-mode kernel; no fake seller checkout |
| Editorial/policy/404 | WordPress and routing/system owner | Available | Independent |
| Loading/offline/permission/maintenance | Request/session/operations owner; theme presents | Valid HTTP/session semantics preserved | Retry/status route without private-data leak |

## Template decisions

Native Cart/Checkout blocks are baseline. Custom fields/data use supported extension mechanisms; visual DOM replacement of checkout is outside the design. WooCommerce's official extensibility guide distinguishes frontend extension facilities and supported integration surfaces (SRC-U004). Legacy shortcode checkout is a separately verified compatibility lane, not implicitly supported merely because its styling resembles blocks.

The storefront is server-rendered where supported for discoverable content, with progressive enhancement for drawers, filters and quick actions. Product titles, prices and category links are available without requiring a carousel script. Session data does not enter public fragment caches. All theme output is escaped for context; seller content cannot inject template markup. The approved palette and content do not imply safe arbitrary CSS/HTML imports.

## Acceptance and operational limits

TEST-U021 switches to an unrelated compatible theme and back, comparing product/customer/order/offer identifiers and balances; no persistent record changes. It also disables the companion plugin in native mode and completes simple/variable checkout via normal WC screens. TEST-U023 checks unavailable enhancement routes. Theme status can indicate a backend failure but cannot declare an order paid or resolve a reservation. Customer/seller forms retain semantic fallbacks. Third-party extensions require capability-matrix verification before release; “universal” describes reuse and bounded adapters, not compatibility with every plugin.
