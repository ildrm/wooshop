# Reusable component specifications

Document ID: DOC-U-07 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: UI/Visual Designer
Reviewers: Accessibility Specialist; Frontend Engineer; Content Designer
Scope: shared storefront and customer/seller presentation contracts
Requirements: FR-U004; FR-U005; FR-U012; FR-U020 | ADRs: ADR-01; ADR-04; ADR-12; ADR-15
Dependencies: [tokens](design-system-and-tokens.md); [settings](settings-inventory.csv); [interaction rules](responsive-rtl-and-interactions.md)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 6 and 9; [source register](../00-governance/sources.csv)

## Shared component contract (FR-U020)

Every numbered component below inherits token families, responsive breakpoints, interaction/focus rules, content handling and the five-state contract: initial/loading, empty, error, success/populated, disabled. Local exceptions override presentation only, never authorization. Public components consume published projections; private ones require object-scoped server authorization. No component owns money, stock, permissions or canonical metadata. Theme configuration references [settings inventory](settings-inventory.csv); unavailable controls are explicit limitations. Event names here are semantic analytics actions; the event catalog owns wire versions and consent rules.

All components use inline direction from locale, bidirectional isolation for identifiers/prices, logical spacing and44px target goals. Components containing tables, dialogs, swatches or menus also inherit their specific keyboard rules. Values come from original proposed tokens; unmeasured dimensions are not assertions about the reference site. Default test fixture is a long Persian/English title, one missing image, a stale server response and an unauthorized access attempt. TEST-U020 verifies every component's inherited states, semantics and configuration; specific functional tests add domain behavior.

## CMP-U001 — Announcement

- Purpose/regions: announcement presents editor text/link/start/end; label/heading precedes content and actions.
- Data/source and variants: editor text/link/start/end; single notice; dismissible service notice.
- Tokens/responsive: full container; padding8; no fixed height; use shared breakpoint/RTL rules.
- Semantics/focus: plain region; close label names notice.
- States/content: missing/expired hidden; failed content omitted; dismissal retained for notice version; loading reserves layout; success announces only a committed change.
- Controls: `announcement.enabled; announcement.dismissible`; validated inventory controls only.
- Dependencies/permission: WordPress content; no plugin required; public published data or current user/org scope as applicable.
- SEO/analytics: No schema; campaign_select with campaign ID; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U002 — Header and logo

- Purpose/regions: header and logo presents WP site identity, navigation, account/cart summaries; label/heading precedes content and actions.
- Data/source and variants: WP site identity, navigation, account/cart summaries; desktop2rows/mobile2rows; comfortable/dense.
- Tokens/responsive: container1280; logo max160×48; minimum44px actions; use shared breakpoint/RTL rules.
- Semantics/focus: banner; logo has home name; labelled utility links.
- States/content: missing logo text site name; session loading no false signed-in state; cart failure link to cart; loading reserves layout; success announces only a committed change.
- Controls: `header.variant; header.sticky; logo.asset; logo.width`; validated inventory controls only.
- Dependencies/permission: WP identity; WC cart; enhancement optional; public published data or current user/org scope as applicable.
- SEO/analytics: Primary landmarks; navigation_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U003 — Mega menu

- Purpose/regions: mega menu presents WP navigation taxonomy links; label/heading precedes content and actions.
- Data/source and variants: WP navigation taxonomy links; desktop disclosure/mobile drill-down drawer.
- Tokens/responsive: 4columns max desktop; one column mobile; gap24; use shared breakpoint/RTL rules.
- Semantics/focus: disclosure controls with expanded state; Escape returns trigger.
- States/content: empty parent remains link; failed dynamic section removed; no hover-only access; loading reserves layout; success announces only a committed change.
- Controls: `navigation.columns; navigation.promoted_limit`; validated inventory controls only.
- Dependencies/permission: WP navigation; custom theme presentation; public published data or current user/org scope as applicable.
- SEO/analytics: Crawlable genuine links; navigation_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U004 — Search box and suggestions

- Purpose/regions: search box and suggestions presents query; public approved product/category suggestions; label/heading precedes content and actions.
- Data/source and variants: query; public approved product/category suggestions; header/full results; loading/active.
- Tokens/responsive: minfield44px; popover width bounded by viewport; use shared breakpoint/RTL rules.
- Semantics/focus: combobox/listbox; visible label; Escape; Enter searches.
- States/content: no suggestions shows search all; timeout retains query and standard submit; old result ignored; loading reserves layout; success announces only a committed change.
- Controls: `search.suggestions_limit; search.debounce_ms`; validated inventory controls only.
- Dependencies/permission: WC search fallback; MOD-U002 enhancement; public published data or current user/org scope as applicable.
- SEO/analytics: Search routes noindex by SEO owner; search_submit/suggestion_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U005 — Mini cart

- Purpose/regions: mini cart presents WC session cart and allocation summary; label/heading precedes content and actions.
- Data/source and variants: WC session cart and allocation summary; popover/drawer; empty/populated.
- Tokens/responsive: drawer min(420px,viewport); rows≥64; use shared breakpoint/RTL rules.
- Semantics/focus: labelled dialog; close and checkout controls; focus return.
- States/content: loading preserves count label; stale amounts marked refreshing; failure links full cart; loading reserves layout; success announces only a committed change.
- Controls: `cart.drawer; cart.group_sellers`; validated inventory controls only.
- Dependencies/permission: WC blocks; MOD-U024 groups; public published data or current user/org scope as applicable.
- SEO/analytics: Private; cart_view; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U006 — Campaign hero

- Purpose/regions: campaign hero presents editor approved image/title/CTA/schedule; label/heading precedes content and actions.
- Data/source and variants: editor approved image/title/CTA/schedule; single image or manual slides.
- Tokens/responsive: 16:9desktop;4:3mobile; heading≤48px; use shared breakpoint/RTL rules.
- Semantics/focus: heading/link hierarchy; pause if rotation opted in.
- States/content: no art uses surface text; expired removes CTA; image failure retains text; loading reserves layout; success announces only a committed change.
- Controls: `hero.ratio; hero.overlay; hero.autoplay`; validated inventory controls only.
- Dependencies/permission: WP pattern; MOD-U009 schedule; public published data or current user/org scope as applicable.
- SEO/analytics: Landing metadata from SEO owner; campaign_view/select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U007 — Category shortcuts

- Purpose/regions: category shortcuts presents taxonomy thumbnail/name; label/heading precedes content and actions.
- Data/source and variants: taxonomy thumbnail/name; circle/square/list.
- Tokens/responsive: 64pxmedia;44pxlinks;2–8columns responsive; use shared breakpoint/RTL rules.
- Semantics/focus: list of destination links.
- States/content: missing media text fallback; long labels wrap; unpublished nodes omitted; loading reserves layout; success announces only a committed change.
- Controls: `category.media_shape; category.columns`; validated inventory controls only.
- Dependencies/permission: WP taxonomy; theme; public published data or current user/org scope as applicable.
- SEO/analytics: Internal links; category_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U008 — Product collection rail

- Purpose/regions: product collection rail presents WC product collection or curated IDs; label/heading precedes content and actions.
- Data/source and variants: WC product collection or curated IDs; new/featured/bestselling/editorial/recent.
- Tokens/responsive: 2–5columns; gap16/24; manual overflow; use shared breakpoint/RTL rules.
- Semantics/focus: section heading; link to collection; carousel rules if scrolling.
- States/content: empty omit rail except explicit search results; prices from fresh public projection; loading reserves layout; success announces only a committed change.
- Controls: `collection.source; collection.count; collection.layout`; validated inventory controls only.
- Dependencies/permission: WC collections; MOD-U005 history; MOD-U010 recommendations; public published data or current user/org scope as applicable.
- SEO/analytics: Crawlable products; collection_view; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U009 — Product card

- Purpose/regions: product card presents public product/variation capabilities/eligible offer summary; label/heading precedes content and actions.
- Data/source and variants: public product/variation capabilities/eligible offer summary; simple/variable/external/grouped/extension; dense/list.
- Tokens/responsive: 1:1contain; padding12/16; title≤3lines; use shared breakpoint/RTL rules.
- Semantics/focus: product link distinct from action; badges textual; rating label count.
- States/content: unknown price unavailable; soldout no quick add; noimage labelled neutral; error links PDP; loading reserves layout; success announces only a committed change.
- Controls: `card.density; card.image_ratio; card.show_rating; card.quick_add`; validated inventory controls only.
- Dependencies/permission: WC native; plugin offers optional; public published data or current user/org scope as applicable.
- SEO/analytics: SEO payload owned centrally; product_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U010 — Price and availability

- Purpose/regions: price and availability presents server projection currency/exponent/price shape/eligibility; label/heading precedes content and actions.
- Data/source and variants: server projection currency/exponent/price shape/eligibility; scalar/range/from/quote/external/unavailable.
- Tokens/responsive: 16–24pxprice; nowrap amount only; label wraps; use shared breakpoint/RTL rules.
- Semantics/focus: semantic old/current price; stock text not color.
- States/content: unknown does not equal zero; stale range labelled until refreshed; no fabricated scarcity; loading reserves layout; success announces only a committed change.
- Controls: `price.emphasis; stock.display`; validated inventory controls only.
- Dependencies/permission: WC native mode; offer authority marketplace; public published data or current user/org scope as applicable.
- SEO/analytics: Visible data matches schema/feed; price_reconfirmed; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U011 — Variant selector

- Purpose/regions: variant selector presents WC attribute values and eligibility graph; label/heading precedes content and actions.
- Data/source and variants: WC attribute values and eligibility graph; radio/swatches/select; native unavailable selection.
- Tokens/responsive: min44pxtarget; wrap options; gap8; use shared breakpoint/RTL rules.
- Semantics/focus: fieldset+legend; swatches show text; unavailable reason associated.
- States/content: partial selection prompts remaining; invalid resets only invalid value with announcement; no silent choice; loading reserves layout; success announces only a committed change.
- Controls: `variant.presentation; variant.show_unavailable`; validated inventory controls only.
- Dependencies/permission: WC variation; MOD-U024 eligible offers; public published data or current user/org scope as applicable.
- SEO/analytics: Selection URL policy from SEO; variant_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U012 — Gallery and media

- Purpose/regions: gallery and media presents WP attachments, alt/caption, media role; label/heading precedes content and actions.
- Data/source and variants: WP attachments, alt/caption, media role; image/video/optional360.
- Tokens/responsive: main1:1; thumbnail64px;16:9video; use shared breakpoint/RTL rules.
- Semantics/focus: thumbnail buttons; zoom dialog; captions/transcript.
- States/content: imagefailure alt+retry; video failure poster/transcript;360fallback still images; loading reserves layout; success announces only a committed change.
- Controls: `gallery.zoom; gallery.video; gallery.spin; gallery.thumb_position`; validated inventory controls only.
- Dependencies/permission: WP media; MOD-U025 optional360 provider; public published data or current user/org scope as applicable.
- SEO/analytics: Images/video metadata via SEO owner; media_view; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U013 — Purchase panel

- Purpose/regions: purchase panel presents server offer quote/options/quantity; label/heading precedes content and actions.
- Data/source and variants: server offer quote/options/quantity; native/external/configure/booking/subscription.
- Tokens/responsive: desktopmin280px; mobilefull;44pxCTA; use shared breakpoint/RTL rules.
- Semantics/focus: fieldset options; clear seller and action; sticky points same form.
- States/content: price changed requires confirmation; no stock disables with reason; pending no duplicate submits; loading reserves layout; success announces only a committed change.
- Controls: `purchase.sticky; purchase.show_terms`; validated inventory controls only.
- Dependencies/permission: WC and verified capability adapter; public published data or current user/org scope as applicable.
- SEO/analytics: No independent schema; add_to_cart committed; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U014 — Seller offer card/table

- Purpose/regions: seller offer card/table presents eligible seller/condition/price/warranty/shipping/rating; label/heading precedes content and actions.
- Data/source and variants: eligible seller/condition/price/warranty/shipping/rating; single/preferred/all offers; stacked small screen.
- Tokens/responsive: table≥720pxscroll or cards; padding16; use shared breakpoint/RTL rules.
- Semantics/focus: radio selection with composite seller label; table headers; preferred criteria link.
- States/content: suspended/expired removed from eligibility but selected stale offer explained; no silent swap; loading reserves layout; success announces only a committed change.
- Controls: `offers.layout; offers.show_reputation; offers.default_sort`; validated inventory controls only.
- Dependencies/permission: MOD-U024; plugin preferred-offer policy; public published data or current user/org scope as applicable.
- SEO/analytics: Canonical product context; offer_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U015 — Specifications

- Purpose/regions: specifications presents typed schema values/units/provenance; label/heading precedes content and actions.
- Data/source and variants: typed schema values/units/provenance; grouped definition list/table.
- Tokens/responsive: maxtext720; rowsmin44 if interactive; use shared breakpoint/RTL rules.
- Semantics/focus: proper term/value relation; disclosures real headings.
- States/content: unknown dash labelled Not supplied; invalid claim withheld; long units wrap; loading reserves layout; success announces only a committed change.
- Controls: `specs.layout; specs.show_provenance`; validated inventory controls only.
- Dependencies/permission: MOD-U001; fallback WC attributes; public published data or current user/org scope as applicable.
- SEO/analytics: Visible facts; specs_expand; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U016 — Compatibility notice

- Purpose/regions: compatibility notice presents evidence-scoped rule result/version/region/firmware; label/heading precedes content and actions.
- Data/source and variants: evidence-scoped rule result/version/region/firmware; verified/conditional/incompatible/unknown.
- Tokens/responsive: status panel padding16; icon+text; use shared breakpoint/RTL rules.
- Semantics/focus: status heading; evidence link; no alarm-only color.
- States/content: unknown never becomes compatible; expired evidence labels review needed; loading reserves layout; success announces only a committed change.
- Controls: `compatibility.show_evidence; compatibility.block_unsafe`; validated inventory controls only.
- Dependencies/permission: MOD-U012 and profile schema; public published data or current user/org scope as applicable.
- SEO/analytics: Claims only verified scoped; compatibility_check; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U017 — Bundle/service configurator

- Purpose/regions: bundle/service configurator presents verified adapter components/time/region/price shape; label/heading precedes content and actions.
- Data/source and variants: verified adapter components/time/region/price shape; fixed kit/composite/service add-on.
- Tokens/responsive: stacked rows≤720px; total visible; use shared breakpoint/RTL rules.
- Semantics/focus: fieldset; each component requirement and optional choice explicit.
- States/content: unavailable component blocks configured purchase; unsupported adapter shows read-only details; loading reserves layout; success announces only a committed change.
- Controls: `bundle.show_components; service.default_selected`; validated inventory controls only.
- Dependencies/permission: MOD-U013/U014; provider required; public published data or current user/org scope as applicable.
- SEO/analytics: No false single scalar; configuration_complete; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U018 — Review summary/list

- Purpose/regions: review summary/list presents WC product reviews; module moderation flags; label/heading precedes content and actions.
- Data/source and variants: WC product reviews; module moderation flags; summary/paginated list/verified purchase.
- Tokens/responsive: text720; stars labelled; pagination44; use shared breakpoint/RTL rules.
- Semantics/focus: heading, rating distribution links, reviewer pseudonym.
- States/content: no reviews explicitly0reviews; no invented average; moderation feedback private; loading reserves layout; success announces only a committed change.
- Controls: `reviews.show_verified; reviews.page_size`; validated inventory controls only.
- Dependencies/permission: WC reviews; MOD-U008 enhancements; public published data or current user/org scope as applicable.
- SEO/analytics: Only approved actual reviews; review_submit; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U019 — Question and answer

- Purpose/regions: question and answer presents approved questions/answers and moderation state; label/heading precedes content and actions.
- Data/source and variants: approved questions/answers and moderation state; accordion/list/form.
- Tokens/responsive: text720; button44; use shared breakpoint/RTL rules.
- Semantics/focus: question headings; answer author type; form labels.
- States/content: pending question visible only to author; abuse report; timeout retains safe draft; loading reserves layout; success announces only a committed change.
- Controls: `qa.page_size; qa.allow_guest`; validated inventory controls only.
- Dependencies/permission: MOD-U007; public published data or current user/org scope as applicable.
- SEO/analytics: No fabricated FAQ markup; question_submit; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U020 — Facet controls and chips

- Purpose/regions: facet controls and chips presents public facetable schema/counts; normalized URL state; label/heading precedes content and actions.
- Data/source and variants: public facetable schema/counts; normalized URL state; sidebar/drawer; checkbox/range.
- Tokens/responsive: sidebar280px; fullmobile; gap8; use shared breakpoint/RTL rules.
- Semantics/focus: fieldset legends; applied-chip remove names; Apply/Clear.
- States/content: zero option labelled disabled; failed request preserves last result; clear-all reversible; loading reserves layout; success announces only a committed change.
- Controls: `facets.presentation; facets.counts; facets.apply_mode`; validated inventory controls only.
- Dependencies/permission: MOD-U002 or WC limited fallback; public published data or current user/org scope as applicable.
- SEO/analytics: SEO facet policy; filter_apply/remove; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U021 — Sorting and pagination

- Purpose/regions: sorting and pagination presents allowed sorts; cursor/page; totals; label/heading precedes content and actions.
- Data/source and variants: allowed sorts; cursor/page; totals; grid/list; page links/load-more enhancement.
- Tokens/responsive: toolbarwrap;44pxcontrols; use shared breakpoint/RTL rules.
- Semantics/focus: labelled select; nav aria-label; current page; skip results.
- States/content: unknowncount omit total; loadfailure retry ordinary next link; sort resets page; loading reserves layout; success announces only a committed change.
- Controls: `catalog.view; catalog.page_size; catalog.enhanced_loading`; validated inventory controls only.
- Dependencies/permission: WC query; MOD-U002; public published data or current user/org scope as applicable.
- SEO/analytics: Self canonical page policy; sort_change/results_page; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U022 — Wishlist and compare actions

- Purpose/regions: wishlist and compare actions presents own list membership and capacity; label/heading precedes content and actions.
- Data/source and variants: own list membership and capacity; icon+text; guest/account; toggle.
- Tokens/responsive: 44pxbutton; pressed state; use shared breakpoint/RTL rules.
- Semantics/focus: action name names product; count badge labelled.
- States/content: optimisticstate rolls back on error; limit explained; unauth private read denied; loading reserves layout; success announces only a committed change.
- Controls: `wishlist.max_items; compare.max_items`; validated inventory controls only.
- Dependencies/permission: MOD-U003/U004; public published data or current user/org scope as applicable.
- SEO/analytics: Private/noindex; wishlist_add/compare_add; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U023 — Account navigation and record list

- Purpose/regions: account navigation and record list presents authorized WC orders/downloads/addresses/preferences; label/heading precedes content and actions.
- Data/source and variants: authorized WC orders/downloads/addresses/preferences; desktopside/mobiletop; paginated.
- Tokens/responsive: sidebar240px; contentfluid; cards small; use shared breakpoint/RTL rules.
- Semantics/focus: current-page link; order number isolated.
- States/content: no orders helpful browse link; denied generic; expired download recovery; loading reserves layout; success announces only a committed change.
- Controls: `account.navigation; account.page_size`; validated inventory controls only.
- Dependencies/permission: WC account; optional modules; public published data or current user/org scope as applicable.
- SEO/analytics: Private/noindex; account_task; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U024 — Checkout field/error summary

- Purpose/regions: checkout field/error summary presents WC cart/customer state plus approved extra fields; label/heading precedes content and actions.
- Data/source and variants: WC cart/customer state plus approved extra fields; guest/account/digital/physical.
- Tokens/responsive: singlecolumnmobile; desktopform+summary; use shared breakpoint/RTL rules.
- Semantics/focus: persistent labels, autocomplete, summary links; mandatory purpose.
- States/content: validation retains inputs; paymentpending persistent; no success on timeout; loading reserves layout; success announces only a committed change.
- Controls: `checkout.summary_sticky; forms.radius`; validated inventory controls only.
- Dependencies/permission: WC Cart/Checkout extension APIs; public published data or current user/org scope as applicable.
- SEO/analytics: Private/noindex; checkout_start/error; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U025 — Order shipment/return timeline

- Purpose/regions: order shipment/return timeline presents authorized allocation shipment/return state; label/heading precedes content and actions.
- Data/source and variants: authorized allocation shipment/return state; one/many sellers; partial fulfilled/refunded.
- Tokens/responsive: verticaltimeline mobile; one card per allocation; use shared breakpoint/RTL rules.
- Semantics/focus: orderedlist with dates/status text; tracking external labelled.
- States/content: unknowncarrier no invented scan; failed tracking shows last timestamp; support action; loading reserves layout; success announces only a committed change.
- Controls: `tracking.group_sellers; tracking.show_dates`; validated inventory controls only.
- Dependencies/permission: WC order; marketplace allocations; public published data or current user/org scope as applicable.
- SEO/analytics: Private; shipment_view/return_request; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U026 — Seller profile/reputation

- Purpose/regions: seller profile/reputation presents approved seller public fields and service scores; label/heading precedes content and actions.
- Data/source and variants: approved seller public fields and service scores; directorycard/storefrontheader.
- Tokens/responsive: banner3:1; avatar80px; text720; use shared breakpoint/RTL rules.
- Semantics/focus: seller heading; rating labelled seller service; disclosures links.
- States/content: suspended removes buying actions; historical support route remains; no score until threshold; loading reserves layout; success announces only a committed change.
- Controls: `seller.banner; seller.accent; seller.show_rating`; validated inventory controls only.
- Dependencies/permission: MOD-U024; public published data or current user/org scope as applicable.
- SEO/analytics: Seller URL policy; seller_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U027 — Seller dashboard/KPI

- Purpose/regions: seller dashboard/kpi presents authorized org projections and pending tasks; label/heading precedes content and actions.
- Data/source and variants: authorized org projections and pending tasks; catalog/fulfillment/finance panels.
- Tokens/responsive: responsive cards; table alternative;min44controls; use shared breakpoint/RTL rules.
- Semantics/focus: task headings, labelled metrics with period/currency.
- States/content: denied panel absent plus server403; stale metrics timestamp; export pending; loading reserves layout; success announces only a committed change.
- Controls: `seller.dashboard_density; seller.metrics_period`; validated inventory controls only.
- Dependencies/permission: MOD-U024; permission scoped; public published data or current user/org scope as applicable.
- SEO/analytics: Private; seller_task; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U028 — Notification/preference form

- Purpose/regions: notification/preference form presents own channel purposes/consent/suppression; label/heading precedes content and actions.
- Data/source and variants: own channel purposes/consent/suppression; email/SMS; transactional/marketing clearly separate.
- Tokens/responsive: text720; grouped controls; use shared breakpoint/RTL rules.
- Semantics/focus: fieldset by purpose; consent wording links version.
- States/content: saveerror retains choices; mandatory transaction explains limitation; unsubscribe success clear; loading reserves layout; success announces only a committed change.
- Controls: `notifications.channel_default; notifications.marketing_default`; validated inventory controls only.
- Dependencies/permission: MOD-U016; provider optional; public published data or current user/org scope as applicable.
- SEO/analytics: Private; preference_changed; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U029 — Comparison table

- Purpose/regions: comparison table presents same-schema products normalized units; label/heading precedes content and actions.
- Data/source and variants: same-schema products normalized units; differences/all;2–4products; mobilecriterion.
- Tokens/responsive: tableoverflowlabelled or stacked; stickyrowlabels; use shared breakpoint/RTL rules.
- Semantics/focus: column product links; rowheaders; remove labelled.
- States/content: missingattribute Not supplied; incompatible units no numericcomparison; stale values timestamp; loading reserves layout; success announces only a committed change.
- Controls: `compare.max_items; compare.differences`; validated inventory controls only.
- Dependencies/permission: MOD-U004; public published data or current user/org scope as applicable.
- SEO/analytics: Private/noindex; comparison_view; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U030 — Editorial/support card

- Purpose/regions: editorial/support card presents WP title/excerpt/media/byline/reviewed date; label/heading precedes content and actions.
- Data/source and variants: WP title/excerpt/media/byline/reviewed date; guide/review/article/FAQ/support.
- Tokens/responsive: 4:3media; text720; grid2–3; use shared breakpoint/RTL rules.
- Semantics/focus: headinglink; author/review role not fake credential.
- States/content: missing media text; expired safety guidance reviewbadge; broken link removed; loading reserves layout; success announces only a committed change.
- Controls: `editorial.card_ratio; editorial.show_byline`; validated inventory controls only.
- Dependencies/permission: WP content; public published data or current user/org scope as applicable.
- SEO/analytics: Article policy centrally; guide_select; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U031 — State/feedback panel

- Purpose/regions: state/feedback panel presents typed error/status with recovery route; label/heading precedes content and actions.
- Data/source and variants: typed error/status with recovery route; empty/loading/validation/offline/403/404/maintenance.
- Tokens/responsive: textmax560; minheight for stable loading; use shared breakpoint/RTL rules.
- Semantics/focus: heading+body+action; status/alert only when relevant.
- States/content: no spinner forever: timeout shows retry; permission message no object existence leak; loading reserves layout; success announces only a committed change.
- Controls: `states.illustration; states.retry_timeout`; validated inventory controls only.
- Dependencies/permission: System owner determines status; theme presents; public published data or current user/org scope as applicable.
- SEO/analytics: True HTTP policy; error_display; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.

## CMP-U032 — Price-history/loyalty panel

- Purpose/regions: price-history/loyalty panel presents public historical prices or own rewards ledger; label/heading precedes content and actions.
- Data/source and variants: public historical prices or own rewards ledger; chart/table; available/expired.
- Tokens/responsive: charttablealternative; no raw private data; use shared breakpoint/RTL rules.
- Semantics/focus: chart summary and table; points have currency distinction.
- States/content: insufficienthistory says unavailable; rewardsheld explained; stale balance timestamp; loading reserves layout; success announces only a committed change.
- Controls: `history.window_days; loyalty.show_estimate`; validated inventory controls only.
- Dependencies/permission: MOD-U017/U018; public published data or current user/org scope as applicable.
- SEO/analytics: No false prior-price claims; history_view/reward_view; optional analytics require applicable consent.
- Acceptance: TEST-U020 plus the relevant screen test; populated example uses product `SH-220-A` or Outdoor Pack35L, never invented real claims.



## Analytics ownership clarification

UI labels above describe interaction intents, not additional wire events. [Alias mapping](ui-analytics-aliases.csv) maps them to the single [operations taxonomy](../13-operations/slos-monitoring-alerts-and-analytics.md). Emit only the canonical event; labels mapped to none are not collected as product analytics without a reviewed taxonomy change. Purchase/refund/confirmed cart mutations are server-owned. No optional event is required for core UI behavior.

## CMP-U033 — Footer and trust/navigation region

Purpose/regions: store identity, service/trust statements, policy/help navigation, optional newsletter and copyright. WP content/navigation is authoritative; claims require merchant review, never fake certification icons. Variants minimal/two/four columns; container1280px, gaps24px, mobile linear disclosures whose links remain reachable without hover. Semantic footer and separately named navigation lists; headings and44px target goals. Public only; missing column omitted, failed newsletter retains form/error, missing trust evidence suppresses claim. Controls footer.composition and footer.columns; content values are native page/navigation fields with editor capability. SEO links are genuine policy/help destinations; no independent schema. Analytics uses alias mapping; TEST-U002/U020/U022 verifies RTL, long labels, keyboard and plugin-free fallback.

## CMP-U034 — Quick view overlay

Purpose: inspect a product without losing archive context; full PDP link always available. Source is public product/capability projection, never cached private cart. Heading/product link → gallery → key facts → current price shape → capability-correct action. Simple supported products may quick add; variable/configured types link to full configuration or provide the same fully validated selector; external products use disclosed supplier link. Dialog width min(900px,viewport minus32px), max viewport height with internal scroll; mobile full drawer. Escape/close restores originating card; loading skeleton, no-product/error retry, disabled unsupported action and server-confirmed add feedback required. Long title wraps; missing image neutral fallback. `card.quick_view` is optional future theme control, defaultfalse. No independent URL/schema/indexation; product_select only once under canonical mapping. TEST-U004/U020 verifies stale offer, focus restoration, duplicate submit and no unintended cart mutation on close.

## CMP-U035 — Newsletter consent panel

Purpose: request optional marketing subscription separately from purchase; heading → purpose/frequency text → email → explicit unchecked consent → submit → verification state. Notification module owns verified subscription/preference; WP owns merchant copy. Text max560px,44px control, single column mobile and RTL labels. Visible label, described purpose and persistent validation summary; no keyboard trap. Empty initial form, pending verification, confirmed/suppressed, provider-error retry and disabled module states are distinct. `footer.show_newsletter` defaultfalse; enabled only with lawful-purpose/provider gate. No account enumeration or implicit opt-in; opt-out suppresses queued optional messages. Private result/noindex management; no extra analytics event. TEST-U002/U056 verifies consent, duplicate request, invalid address, provider timeout and disabled module removes form while preserving opt-out.

## CMP-U036 — Size guide and warranty disclosure

Purpose: distinguish product fit facts from seller commercial promise. Size guide references reviewed model/variation-specific table/manual; warranty references selected offer's policy/version/provider. Inline summary → labelled details button → accessible table/dialog or policy page. Text720px, responsive stacked rows or labelled table scroll; unit labels and mixed identifiers isolated. Unknown fit/warranty says not supplied; expired evidence removed; selected seller change refreshes warranty with explicit quote review. No invented universal size conversion. Controls `sizeguide.presentation` and `warranty.presentation`; mandatory commercial terms cannot be hidden. Semantic heading/table headers and modal focus restoration; no auto-focus on seller refresh. Public approved facts only; no independent schema duplication. TEST-U005/U020/U031 verifies sizevariation, differing seller warranty, missing manual, long Persian label and keyboard table access.
