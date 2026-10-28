# Screen and template specifications

Document ID: DOC-U-08 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: UI/Visual Designer
Reviewers: Accessibility Specialist; Frontend Engineer; Content Designer
Scope: shared storefront and customer/seller presentation contracts
Requirements: FR-U001; FR-U002; FR-U003; FR-U005; FR-U006; FR-U007; FR-U008; FR-U009; FR-U019 | ADRs: ADR-01; ADR-04; ADR-12; ADR-15
Dependencies: [tokens](design-system-and-tokens.md); [settings](settings-inventory.csv); [interaction rules](responsive-rtl-and-interactions.md)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 6 and 9; [source register](../00-governance/sources.csv)

## Screen inheritance and fixtures (FR-U019)

These are unique page/template families; product variants are separate because their purchase capability changes. Each screen inherits the shared header/footer only when appropriate; checkout reduces promotional distractions, and private seller/customer screens omit third-party marketing embeds. The catalog states the content order, data owner, components, access, SEO, events and errors. Responsive dimensions, RTL, keyboard, states and editable tokens are inherited from the cited components, avoiding contradictory screen-level copies. All visible headings/labels are localizable. Success means server-confirmed state, never an optimistic payment assertion.

Required state fixture set for every screen: populated valid data, no records, slow request, rejected/timeout request, disabled/unavailable action, 200-character mixed-direction title and missing media. Page-level loading reserves heading/card geometry; initial server-rendered public content remains navigable; private screens never render another user's cached response. TEST-U019 exercises this set for every family; FR-specific tests add transaction requirements. Screen-specific semantics: one h1, named main and navigation landmarks, inline errors connected to controls, and focus moved only when the task context changes. Configurable properties are component inventory keys; screen composition is an allowlisted template variation, not arbitrary seller markup.

## SCR-U001 — Home

Purpose and region hierarchy: announcement→header→hero→category shortcuts→collections→guides→footer.

Data/dependencies: WP page/patterns + WC collections. Components: CMP-U001;CMP-U002;CMP-U006;CMP-U007;CMP-U008. Access: public; editor can publish content.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: failed campaign omitted; empty collection removed; newsletter feedback visible. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index/self canonical; Organization owned by SEO. Analytics: home_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U002, TEST-U002 and TEST-U019.

## SCR-U002 — Campaign landing

Purpose and region hierarchy: title/terms→campaign hero→filters→eligible collection→guide.

Data/dependencies: WP editorial + campaign eligibility. Components: CMP-U006;CMP-U009;CMP-U020. Access: public; merchandiser campaign scope.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: expired terms remove urgency and purchase claim; retain useful archived content. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index only unique approved landing; expired redirect/archive policy. Analytics: campaign_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U002, TEST-U002 and TEST-U019.

## SCR-U003 — Category archive

Purpose and region hierarchy: breadcrumb/title/intro→subcategories→facets/sort→products→pagination.

Data/dependencies: WC category/schema faceting. Components: CMP-U007;CMP-U009;CMP-U020;CMP-U021. Access: published public catalog.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: empty result clear filters; empty category curated guidance; failed enhancement standard links. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index/self; pagination own URL; BreadcrumbList. Analytics: category_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U003, TEST-U003 and TEST-U019.

## SCR-U004 — Brand archive

Purpose and region hierarchy: brand intro→categories→facets→products→pagination.

Data/dependencies: approved taxonomy brand content. Components: CMP-U009;CMP-U020;CMP-U021. Access: public published.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: missing brand404; no products no fabricated popularity. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index only meaningful original content; self canonical. Analytics: brand_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U003, TEST-U003 and TEST-U019.

## SCR-U005 — Search results

Purpose and region hierarchy: query heading→suggestion→facets/sort→results→pagination.

Data/dependencies: search adapter public projection. Components: CMP-U004;CMP-U009;CMP-U020;CMP-U021. Access: public; query length/rate bounds.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: no results spelling/category suggestions; timeout preserves query; no private hits. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex; crawl policy in SEO matrix. Analytics: search_results, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U003, TEST-U003 and TEST-U019.

## SCR-U006 — PDP simple

Purpose and region hierarchy: breadcrumb→gallery/title/rating→price/seller/quantity→specs/reviews→related.

Data/dependencies: WC simple + eligible offer. Components: CMP-U010;CMP-U012;CMP-U013;CMP-U015. Access: public; purchase requires valid availability.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: stock/price change reconfirm; nullprice no purchase; imageerror fallback. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: product self; Product/Offer centrally emitted. Analytics: product_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U007 — PDP variable

Purpose and region hierarchy: breadcrumb→media/title→attribute selection→eligible sellers→purchase→specs.

Data/dependencies: WC variation capabilities + offer per variation. Components: CMP-U011;CMP-U014;CMP-U013. Access: public; server validates selected variation.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unselected Choose options; unavailable value reason; no price until valid quote. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: ProductGroup/variant URL rules centrally. Analytics: variant_select, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U008 — PDP grouped

Purpose and region hierarchy: title/media→child product rows→independent quantities→summary→details.

Data/dependencies: WC grouped parent and purchasable children. Components: CMP-U009;CMP-U013;CMP-U015. Access: public; each child validated.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unavailable child isolated; group has no assumed parent inventory. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: parent product-group policy, no false aggregate sellable price. Analytics: group_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U009 — PDP external

Purpose and region hierarchy: title/media→supplier disclosure→external CTA→details.

Data/dependencies: WC external URL + approved vendor text. Components: CMP-U010;CMP-U013;CMP-U015. Access: public; outbound URL allowlist.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: no local cart/shipping; unsafe/missing URL action unavailable. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: canonical product policy; external nature visible. Analytics: affiliate_click, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U010 — PDP virtual/downloadable

Purpose and region hierarchy: title/media→license/access terms→price→purchase→downloads description.

Data/dependencies: WC product flags; access after verified order. Components: CMP-U013;CMP-U015. Access: public preview; files require own order entitlement.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: never expose paid asset URL before entitlement; expired link account recovery. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: Product with accurate nonshipping facts. Analytics: digital_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U011 — PDP bundle/composite

Purpose and region hierarchy: title→component choices→compatibility→configuration total→purchase.

Data/dependencies: verified bundle adapter; MOD-U013. Components: CMP-U016;CMP-U017;CMP-U013. Access: public; supported capability gate.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unsupported marketplace combination read-only explanation; child unavailable blocks. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: configuration schema only when valid; no fake scalar. Analytics: configuration_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U012 — PDP subscription

Purpose and region hierarchy: title→renewal amount/interval/terms→initial total→purchase.

Data/dependencies: verified subscription adapter. Components: CMP-U010;CMP-U013;CMP-U015. Access: public; recurring provider capability gate.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: renewal/payment limitations stated; unsupported marketplace disabled. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: subscription price disclosure consistent with feed eligibility. Analytics: subscription_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U013 — PDP booking/service

Purpose and region hierarchy: title→timezone/date/resource/options→availability→quote→purchase.

Data/dependencies: verified booking/service adapter. Components: CMP-U017;CMP-U013. Access: public; capacity/destination validation.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: slot expired retain form and reselect; no asynchronous silent booking. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: service/booking schema only if supported. Analytics: booking_select, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U005, TEST-U005 and TEST-U019.

## SCR-U014 — Cart

Purpose and region hierarchy: title→seller groups/items→coupon→shipping estimate→totals→checkout.

Data/dependencies: WC cart with allocation projections. Components: CMP-U009;CMP-U024;CMP-U010. Access: session-scoped; server totals.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: stale selected offer requires review; no silently substituted seller; failed update retain prior cart. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex; private no shared cache. Analytics: cart_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U006, TEST-U006 and TEST-U019.

## SCR-U015 — Checkout

Purpose and region hierarchy: contact→addresses/delivery→seller disclosures→payment→review→commit.

Data/dependencies: WC Checkout block + approved adapters. Components: CMP-U024;CMP-U014;CMP-U010. Access: session+CSRF context; no client-trusted totals.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: field errors summary; payment timeout pending not failure/success guess; session expiry safe restart. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: checkout_start, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U006, TEST-U006 and TEST-U019.

## SCR-U016 — Confirmation/payment recovery

Purpose and region hierarchy: order reference→payment status→allocations→next steps/support.

Data/dependencies: authorized WC order + verified payment state. Components: CMP-U025;CMP-U031. Access: order ownership/key policy; no raw query trust.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: pending refresh safe; failure retry same obligation per server; confirmed never duplicated. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: purchase_confirmed, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U006, TEST-U006 and TEST-U019.

## SCR-U017 — Account dashboard

Purpose and region hierarchy: greeting→task shortcuts→recent orders→preferences.

Data/dependencies: own WC customer records. Components: CMP-U023;CMP-U028. Access: authenticated customer current object scope.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: empty account welcome; expiredsession login; no other user data. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: account_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U018 — Login/registration/recovery

Purpose and region hierarchy: form heading→credentials/recovery→status→safe return.

Data/dependencies: WP/WC account flows. Components: CMP-U024;CMP-U031. Access: guest; antiabuse controls.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: generic recovery acknowledgement; expired token clear restart; password-manager friendly. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: auth_task, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U019 — Addresses

Purpose and region hierarchy: address list→edit/add form→validation→saved confirmation.

Data/dependencies: own WC addresses. Components: CMP-U023;CMP-U024. Access: own account; reauth for high-risk actions as policy.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: invalid address retained; deletedaddress does not rewrite historical order. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: address_save, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U020 — Downloads

Purpose and region hierarchy: entitlement list→expiry/remaining uses→download action→help.

Data/dependencies: WC downloads permission. Components: CMP-U023;CMP-U031. Access: own valid entitlement only.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: expired/revoked reason without leaking storage path; support recovery. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private files protected. Analytics: download_request, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U021 — Orders and order detail

Purpose and region hierarchy: orders filter→order summary→items/allocations→shipment/refund/support.

Data/dependencies: WC orders + allocations. Components: CMP-U023;CMP-U025. Access: own order, seller allocation subset for sellers.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: partialstatus per seller; old seller name from snapshot; trackingoutage timestamp. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: order_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U022 — Cancellation/returns

Purpose and region hierarchy: eligible item selection→reason→evidence→refund estimate→submit/status.

Data/dependencies: return/dispute module and policy snapshot. Components: CMP-U025;CMP-U024. Access: own purchased lines; policy/server validation.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: ineligible explains policy/contact; request not refund promise; duplicate submission one case. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: return_request, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U023 — Support case

Purpose and region hierarchy: topic/order reference→message→attachments→case status.

Data/dependencies: support adapter least-privilege reference. Components: CMP-U024;CMP-U031. Access: own case; support role scope.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unsafeupload rejected; failed message retry with dedup key; no sensitive logs. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: support_request, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U024 — Wishlist

Purpose and region hierarchy: saved collection→availability→move/add/remove→share options.

Data/dependencies: MOD-U003 own guest/account lists. Components: CMP-U009;CMP-U022. Access: private by default; explicit public-share token optional.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: deletedproduct tombstone removal; expired guestlist notification; merge no duplication. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: private noindex; shared lists noindex baseline. Analytics: wishlist_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U025 — Comparison

Purpose and region hierarchy: selected products→criteria/differences→product details/remove.

Data/dependencies: MOD-U004 normalized schema. Components: CMP-U029;CMP-U022. Access: own list; public product data.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: insufficient selection chooseproducts; incompatible units no false equivalence. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex. Analytics: comparison_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U026 — Subscriptions/bookings account

Purpose and region hierarchy: contract list→schedule/renewal→allowed change/cancel→confirmation.

Data/dependencies: named provider adapter authorizations. Components: CMP-U023;CMP-U024. Access: own contract; scoped provider action.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unsupportedaction contact support; price/time change reconfirm; no deleting obligations. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: contract_manage, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U027 — Notification preferences

Purpose and region hierarchy: purpose/channel groups→consent history→save→unsubscribe.

Data/dependencies: MOD-U016 own preference state. Components: CMP-U028. Access: own account or scoped signed unsubscribe token.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unsubscribe immediate suppression; provider failure does not re-enable consent. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: preference_changed, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U007, TEST-U007 and TEST-U019.

## SCR-U028 — Seller directory

Purpose and region hierarchy: intro→seller filters→approved seller cards→pagination.

Data/dependencies: marketplace published seller projection. Components: CMP-U026;CMP-U021. Access: public approved sellers only.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: noeligible seller guidance; pending/suspended hidden. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index curated directory; self canonical. Analytics: seller_directory_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U029 — Seller storefront

Purpose and region hierarchy: seller identity/disclosures→service reputation→catalog→contact policy.

Data/dependencies: approved seller profile and offers. Components: CMP-U026;CMP-U009;CMP-U014. Access: public subset; seller cannot edit global styles.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: suspension removes new sales but support/historical obligations retained. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index eligible seller; canonical per SEO. Analytics: seller_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U030 — Seller onboarding

Purpose and region hierarchy: eligibility→application/team→verification→terms→status.

Data/dependencies: marketplace application state; provider verification. Components: CMP-U024;CMP-U031. Access: applicant own org; review limited to authorized staff.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: rejected/suspended status with appeal path; externalprovider outage pending not approved. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: seller_application, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U031 — Seller dashboard

Purpose and region hierarchy: organization selector→tasks→metrics→role-scoped navigation.

Data/dependencies: seller org membership + projections. Components: CMP-U027. Access: active membership with per-action capability.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: unassignedaccount no dashboard; stale metrics timestamp;403 on forged organization. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: seller_task, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U032 — Seller catalog/offers

Purpose and region hierarchy: submission list→match canonical→draft fields/offer terms→review→publish status.

Data/dependencies: canonical matching and offer workflow. Components: CMP-U027;CMP-U024. Access: seller catalog capability and same org.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: duplicateconflict escalates moderator; no direct canonicaloverwrite. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private editor; published products centrally indexed. Analytics: offer_edit, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U033 — Seller fulfillment/returns

Purpose and region hierarchy: allocation queue→line detail→tracking/return action→status.

Data/dependencies: own allocations and obligations. Components: CMP-U025;CMP-U027. Access: seller fulfillment membership; no full cross-seller order.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: suspendedseller limited obligation work; invalid tracking blocked; stale revision409. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: fulfillment_update, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U034 — Seller finance/statements

Purpose and region hierarchy: balances/holds→transactions→statements→payout status.

Data/dependencies: subledger authorized projection. Components: CMP-U027;CMP-U031. Access: seller finance membership; payoutdetails changes step-up.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: negativebalance explained; held not available; no manual paid marking. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: noindex/private. Analytics: seller_finance_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U008, TEST-U008 and TEST-U019.

## SCR-U035 — Blog/guide index

Purpose and region hierarchy: title→topics→article cards→pagination.

Data/dependencies: WP posts editorial taxonomy. Components: CMP-U030;CMP-U021. Access: public published.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: emptytopic route to broader guide; no fake author credentials. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index self; article discovery. Analytics: guides_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.

## SCR-U036 — Guide/editorial review

Purpose and region hierarchy: title/byline/review date→content/media→related products→sources.

Data/dependencies: WP editorial content approved factual review. Components: CMP-U030;CMP-U012. Access: public; editor/domain approval.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: outdatedclaim warning; missing media caption fallback; review distinct from customer rating. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: Article/Review type centrally governed. Analytics: guide_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.

## SCR-U037 — About/contact/FAQ/policies

Purpose and region hierarchy: title→structured content/forms→revision/contact→related help.

Data/dependencies: WP pages; reviewed legal text; form adapter. Components: CMP-U024;CMP-U030. Access: public; form rate limits; edits scoped.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: failedcontact submission retained; unknownjurisdiction legal text not finalized. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: index useful public pages; legal policy canonical. Analytics: help_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.

## SCR-U038 — 404

Purpose and region hierarchy: not found title→search→catalog/help links.

Data/dependencies: HTTP404 by WP/router; theme content. Components: CMP-U031;CMP-U004. Access: public; no object leak.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: malformed path not reflected unsafely; no search auto-run. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: 404; no sitemap; no soft200. Analytics: not_found, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.

## SCR-U039 — Maintenance/coming soon

Purpose and region hierarchy: status→estimated update if known→support/status link.

Data/dependencies: operations controlled switch; theme presentation. Components: CMP-U031. Access: public or preview-auth whitelist.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: no invented restore time; payment callbacks/obligations remain operational. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: 503 with Retry-After for temporary maintenance; launch policy separate. Analytics: maintenance_view, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.

## SCR-U040 — Offline/timeout/permission denied

Purpose and region hierarchy: specific status→retained safe context→retry/login/help.

Data/dependencies: request/session owner; theme status panel. Components: CMP-U031. Access: no private metadata in403; own session only.

Layout/RTL/focus: public content container1280px or readable text720px; private forms max720px; mobile linear reading follows region order. Inherit component breakpoint, bidirectional isolation and keyboard contracts. Page headings stay visible under sticky controls. Component variants select capability/density; layout never changes commerce semantics.

States and unusual content: no paid success from offline cache; retry avoids duplicate business effects. Inherit loading/empty/error/success/disabled fixture set; long titles wrap and missing values are labelled unknown, not fabricated.

SEO: proper status/noindex on private errors. Analytics: error_display, consent-aware and identifier-minimized. Controls: corresponding component keys in the settings inventory; editor-only composition. Acceptance: FR-U009, TEST-U009 and TEST-U019.



## Analytics ownership clarification

UI labels above describe interaction intents, not additional wire events. [Alias mapping](ui-analytics-aliases.csv) maps them to the single [operations taxonomy](../13-operations/slos-monitoring-alerts-and-analytics.md). Emit only the canonical event; labels mapped to none are not collected as product analytics without a reviewed taxonomy change. Purchase/refund/confirmed cart mutations are server-owned. No optional event is required for core UI behavior.
