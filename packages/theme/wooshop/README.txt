=== Wooshop ===
Version: 0.1.0
License: GPL-2.0-or-later
Requires at least: WordPress 7.1.3
Requires PHP: 8.3

An original native block theme with shared Outdoor, Smart Home and Neutral marketplace style variations. The implementation integration baseline is WordPress7.1.3/WooCommerce11.2.1; the full compatibility and assistive technology matrix remains unverified. The theme.json file uses the conservative version3 schema reference introduced at6.6; that schema reference does not declare support below the selected7.1.3 project baseline.

Install the wooshop directory in wp-content/themes and activate it. There is no development build step or external font dependency. WooCommerce is optional for editorial pages and required for commerce. The companion plugin is optional for native commerce.

Use Appearance > Editor to edit templates, header/footer parts, native navigation, global styles and patterns. The header and footer contain native Navigation blocks. Select the outer Store header block for announcement, dismissal, sticky/compact presentation, logo attachment and width. Select Category shortcuts for its heading, maximum count and media shape. Select Native product collection for its heading, WooCommerce source, count, density and grid/manual-rail layout. Footer supports minimal presentation. Native attribute storage, editor history and user style precedence remain WordPress-owned. No proprietary breakpoint/state editor or import/reset/history service is implemented.

Choose Outdoor, Smart Home or Neutral marketplace in native Styles. Saved user overrides remain WordPress-owned. These are visual selections; they do not set a currency, enable marketplace sales, change permissions or alter prices/stock. A style variation is not a business profile import.

The default front page contains the Storefront discovery pattern. A selected static page may use a customized front-page template containing Post Content; the local fixture importer creates that template once for a new fixture installation. Outdoor discovery and Smart home discovery are reusable patterns, not domain-specific code forks. All product/category collections read actual native WooCommerce objects. Empty collections are omitted. Prices/reviews/sales are never invented. Native simple, variable, grouped, external, virtual and downloadable flows retain WooCommerce's purchase and authorization behavior. Unknown product adapters require the companion plugin's capability guard; the theme does not authorize purchases.

WooCommerce Cart/Checkout pages preserve their saved native blocks; Account uses native WooCommerce. Product and catalog templates compose the WooCommerce legacy-template block shipped by the pinned WooCommerce version, keeping native variation forms, reviews and extensions. This does not imply support for legacy shortcode checkout. Mini-cart uses the native block and a permanent full-cart link. Public page/template content is server rendered; WooCommerce block checkout has its own JavaScript dependency.

Commerce module surface supports only registered commerce_wishlist, commerce_compare, commerce_seller_dashboard or commerce_unsubscribe shortcodes. The offer enhancement uses registered commerce_offers only. Catalog specifications/questions are composed by the plugin through native WooCommerce tabs, avoiding duplicate output. Missing module surfaces show a useful catalog recovery state. The theme stores no private lists, seller data, inventory, orders, ledger or schema. The plugin owns the module's authorization, output escaping and lifecycle.

Category links work without the theme script using native details elements. The script adds mobile dialog focus containment, inert background, Escape/close restoration, announcement dismissal scoped to notice version and session, and a connection notice. Sticky header is limited to wide, tall viewports; there is no sticky purchase bar. Motion preferences and logical RTL spacing are supported. Human keyboard/screen-reader, zoom/reflow, native checkout and rendered contrast review remain release gates; this is not an accessibility conformance claim.

System font stack only. assets/landscape.svg and assets/connected.svg are original decorative artwork authored for this implementation and licensed with this theme. Persian translations are authored implementation translations; fluent review is pending and remaining strings use readable English fallback. No reference-site assets, fake reviews, trust seals or certifications are included.

See coverage.json for every documented component, screen and setting limitation. Advanced quick view, labelled swatches, gallery video/360, custom facets, advanced product adapter UI, complete seller/customer workflows, newsletter consent integration, custom appearance import/export/revision/search UI and all-component five-state acceptance are incomplete. Native platform editing is privileged; no claim is made to constrain hostile administrators.

Validation from repository root:
rtk proxy python3 tests/theme/check.py
Real runtime validation, after creating local synthetic products:
rtk proxy python3 tools/dev.py wp outdoor eval-file /workspace/tests/theme/wordpress.php
The latter requires the actual isolated development stack. See repository implementation records for observed results and limits.
