# Native versus custom decisions

Document ID: DOC-D-NATIVE | Title: Native versus custom decisions | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future implementation verification
Owner: WooCommerce Solution Specialist | Reviewers: WooCommerce Solution Specialist; QA/Test Engineer; Application Security Engineer
Scope: proposed system design only | Requirements: FR-D002; FR-D003; FR-D004; FR-D008; FR-D013 | ADRs: ADR-01; ADR-03; ADR-04; ADR-12; ADR-13
Dependencies: [requirements](../02-requirements/requirements.csv); [source register](../00-governance/sources.csv)
Assumptions: ASM-01 independent sites; ASM-02 jurisdiction/providers undecided; ASM-03 planning scenarios
Sources: USER-BRIEF sections 2–8; SRC-D001–SRC-D009

## Fulfillment choices

| Capability | Proposed owner | Custom addition and boundary |
|---|---|---|
| Content, users, media, menus | WordPress native | Scoped seller editorial workflow; no replacement identity system |
| Global styles/templates/patterns | Native block theme/editor | Controlled token presets and documented component settings |
| Simple/variable/grouped/external products | WooCommerce native | Capability descriptor and original presentation |
| Virtual/downloadable traits | WooCommerce native | Display/permission integration; no parallel download entitlement engine |
| Reviews and coupons | WooCommerce native | Moderation metadata, incentives rules as native coupon integration; no duplicate review or coupon store |
| Cart/Checkout | WooCommerce blocks + Store API | Offer identity, seller grouping, validation, allocations; no DOM patch checkout |
| Product technical attributes | Native attributes where adequate | Typed specification schema/evidence for units and compatibility |
| Wishlists, comparisons, Q&A, alerts | Custom companion modules | Separate consent and retention; not cart or review clones |
| Search | Native retrieval baseline | Approved indexed facet projection and optional external adapter after threshold |
| Bundles/composites/subscriptions/bookings | Named official extension candidates | Narrow adapters; no claim of cross-extension or marketplace compatibility |
| Seller organizations/offers/ledger | Custom companion marketplace modules | Native identities preserved; no independently built card processor |
| Payments/payouts/shipping/messages | Approved provider integrations | Signed callback, minimization, idempotency and reconciliation contracts |
| Metadata/schema/sitemaps | SEO coordination + one renderer | Native WooCommerce rendering fallback when plugin absent |
| Analytics | One consent-aware event coordinator | Server purchase deduplication; no theme event duplication |

## Native facts and proposed contracts

WordPress documents global settings/styles in `theme.json`; this supports using the native Styles interface instead of a proprietary builder. Exact schema version is pinned during future theme implementation against the selected release, because introductory examples can lag. [SRC-D004](https://developer.wordpress.org/themes/global-settings-and-styles/introduction-to-theme-json/).

WooCommerce exposes a customer-session Store API separately from its administrative REST API. Endpoint extensions can publish additional fields and request server-side cart updates. These are the chosen integration surfaces; exact offer integration still requires future contract tests. [Store API, SRC-D002](https://developer.woocommerce.com/docs/apis/store-api/) and [extensions, SRC-D003](https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/).

## Avoiding duplicate authority

FR-D013 requires activation preflight to inventory installed coupon, review, schema, stock and finance providers. Only one writer per domain may be active. The plugin uses filters/contracts to integrate a native owner, rather than reconstructing it. Where a provider cannot expose safe authority controls, affected sales remain disabled. No compatibility claim follows merely because two plugins activate together.

## Settings and fallback

Native user global-style changes retain their native precedence. Plugin domain configuration is independent of theme styling and is not overlaid into an invented universal inheritance chain. A switch to another compatible theme preserves commerce data and provides accessible unstyled functional interfaces. Removing the companion plugin leaves native storefront commerce intact only for native-mode products; marketplace products are put in an explicit non-purchasable operational state until authority is migrated or obligations are drained. The theme alone cannot repair missing marketplace business services.
