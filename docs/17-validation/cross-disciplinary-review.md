# Cross-disciplinary analytical review

Document ID: DOC-G-REVIEW | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: Solution/Software Architect
Reviewers: Product Owner; Business Analyst; Accessibility Specialist; Application Security Engineer; Finance/Tax Specialist; QA/Test Engineer; DevOps/SRE Engineer
Scope: five end-to-end design walkthroughs and analytical consistency review | Requirements: FR-G018; FR-G017
ADRs: ADR-01; ADR-03; ADR-04; ADR-05; ADR-07; ADR-08; ADR-09; ADR-13; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [tests](../12-quality/test-cases.csv); [API operations](../09-api-integrations/api-operation-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF section17; [sources](../00-governance/sources.csv)

This is AI-generated analytical review of proposed contracts. Bounded documentation agents and the primary author compared domain specifications, not a running application. It is not independent human assurance, a usability study, legal opinion, audit or certification. External reviews and all runtime tests remain pending. The [walkthrough trace register](walkthrough-traces.csv) gives individual layer references; the narratives state expected behavior and failure paths.

## WALK-G001 — Outdoor variable product

Use synthetic FIX-S-OUT: a product with sizesM/L, manufacturer-evidenced materials/weight and two seller offers against sizeM. The shopper starts on SCR-U003, reads SCR-U007, chooses through CMP-U011 and compares CMP-U014, then enters SCR-U014/015/016. CMP-U015 shows evidence-backed specifications, CMP-U010 the selected price/availability and CMP-U025 separate shipment/return progress. A missing or unavailable size cannot silently become another variation.

FR-G012 governs provenance; FR-D021–024 and FR-D025–027 govern offer/inventory/cart/snapshot behavior. DATA-D01/02 identify the native product/variation; DATA-D05/06/07 the chosen offer/pool/hold; DATA-D08/09 the immutable item/allocation. API-D001 returns eligible public offers. Native Store API extensions carry selected offer/version/configuration digest, with authoritative validation at add/update/checkout. API-D008–010 handle scoped allocation/shipment reads and commands; API-D011–013 initiate and approve a remedy. Public reads never disclose seller-private costs or customer details.

At final checkout, only the authority adapter reserves stock. A last-unit race has one winner; changed price/seller/terms require reconfirmation. Native order/tax/coupon processing remains authoritative through its integration contract. Capture posts one balanced sale and commits inventory once; duplicate callbacks have no second effect. Fulfillment and returns progress independently; a refund does not imply physical restock. A seller cannot refund another seller's line or change its snapshot. Product/group canonical behavior and the selected offer's visible price/schema/feed remain consistent; one server purchase event records the order and separate allocation dimensions.

Expected design outcome: the recorded seller/size/terms survive later catalog edits. Evidence still needed: TEST-S100/103/105/106/107/108/113/116 plus linked domain cases; actual stock deltas, provider references, ledger totals, keyboard/RTL task evidence and rendered SEO output. Work packages WP-G013/018/019/020/022/025/026 own the slice. Unknown safety claims or unsupported product combinations remain unavailable rather than guessed.

## WALK-G002 — Smart-home compatibility and solution bundle

Use FIX-S-HOME: device version2 in regionR1, firmware3.2, required evidenced hubH1, unsupported/unknown hubH2 and optional installation service. The shopper reads CMP-U015/016 on SCR-U006 or SCR-U011 and configures through CMP-U017; SCR-U013 represents a booking/service provider flow when enabled. Matching Matter,Thread,Zigbee or Wi-Fi labels alone do not authorize a compatibility claim. FR-G012 and FR-G005 govern evidence and capability bounds; DATA-D11 captures subject/target/region/firmware/review provenance.

API-D027/028 returns the published schema and values, API-D026 evaluates the selected evidence context, and API-D001 shows eligible offers. Signed-in or guest-session scope follows the API contract; client claims of compatibility or price are not accepted as authority. A merchant can edit approved fields; a seller proposes evidence without granting themselves canonical approval. The bundle/configuration digest and provider resource/time identity enter DATA-D08 only through a verified adapter. Unknown combinations display the evidence gap with support access, not a false compatible badge.

Same-seller native bundles/composites and installation are enabled only for the exact adapter/mode tested. Cross-seller bundles, marketplace renewals and bookings remain blocked until their declared capability gates pass; the shopper can browse component information and use supported independent purchases where that is a truthful alternative. Component amounts and stock effects cannot be counted again at the parent. Cancellation/rescheduling/component returns follow the provider contract, with one financial allocation and original-price refund ceiling. SEO marks only visible evidenced attributes and actual offers; selection analytics emits one canonical event and no raw home/network details.

Expected design outcome: a dated conditional claim can be revoked without rewriting an old purchase, while current recommendations and search projections stop asserting it. TEST-S102/105/108/112/116 and the module compatibility cases supply future evidence; WP-G009/013/016/023/025 own delivery. Support on SCR-U023 receives the purchased model/region/firmware/terms snapshot, not an inferred guarantee of safe electrical installation.

## WALK-G003 — Third-party seller lifecycle

A seller applicant uses SCR-U030, then an approved organization owner invites scoped catalog,fulfillment and finance memberships through API-D004–007. DATA-D03 is the organization and DATA-D04 the user relationship; a user account is not the seller itself. The independent reviewer checks required provider/legal evidence before approval. CMP-U026/027 presents public profile and private tasks without disclosing verification documents. Catalog managers match or propose canonical content on SCR-U032; only an authorized catalog authority resolves duplicates and evidence conflicts.

FR-D020,FR-D061–065 and SEC-S002 govern lifecycle,offers and scope. API-D002 publishes valid own offers with expected version, API-D003 performs authorized pool adjustments, and API-D008–010 exposes only assigned allocations/shipments. Price/stock/seller eligibility is revalidated before checkout. Rankings follow the disclosed eligibility/price/service/tie policy; sponsored content is distinguishable and manual overrides have expiry/reason. No new checkout silently substitutes a seller.

SCR-U034 shows a seller's statement through scoped finance reads. API-D014–016 prepares,independently approves and observes payouts under an approved funds model. Preparation and approval use different principals; destination changes trigger review/hold. A timeout remains unknown until reconciliation. Refund after payout creates an explicit receivable/approved reversal/funding path rather than editing a paid historical payout. Suspension blocks new offers/checkout while fulfillment,returns,disputes and legally required records remain serviceable. Seller/service ratings remain distinct from product reviews.

Expected design outcome: the seller can fulfill and reconcile their own business without cross-seller data access or control of platform funds policy. TEST-S104/107/108/109/110 and domain payout cases are future evidence. WP-G002/017/018/019/020/025/028 own the slice. SEO removes/deindexes a closed storefront under its lifecycle policy while product URLs and purchased disclosures remain stable; operational seller/payout analytics is scoped and distinct from customer revenue.

## WALK-G004 — Generic third website and unrelated theme

A site administrator configures a third independent installation with a neutral preset, a new schema profile and only selected modules. The same WordPress and WooCommerce numeric IDs on another site do not identify the same customer/product. FR-G002/007/020,ADR-14 and TEST-S119 require separate credentials,data,cache namespaces and exports. Branding changes use the settings inventory and native editor precedence; arbitrary executable customization and unlicensed assets are excluded. API-D034–036 validates module lifecycle and allowlisted preview/export; changing presentation does not change data ownership.

An unrelated compatible theme renders accessible plugin forms,tables and account tasks with CMP-U031 error/recovery feedback. The custom theme without the companion plugin continues ordinary native WooCommerce in a native-only deployment. Previously migrated marketplace products require the persisted native guard and independent dependency-health maintenance path: the absent plugin cannot promise to enforce its own hooks. Direct native requests and pre-existing carts must fail closed for affected marketplace commerce. If isolation cannot be guaranteed, checkout is suspended while browsing/support remains available.

A new product type supplies a capability descriptor,provider/version evidence,identity/money/stock/fulfillment mapping and tests before purchase is enabled. Missing provider or unsupported module disables that capability and preserves historical data and obligations. Private exports omit secrets/customer data; unknown schema keys fail dry-run validation. SEO ownership remains one configured emitter, and analytics does not stitch customers across sites by default.

Expected design outcome: no store-specific code branch or fork is required. TEST-S112/114/115/118/119 and TEST-G020 provide future evidence; WP-G010/011/016/024/025 own delivery. Failure of the independent marketplace health guard blocks marketplace activation; it cannot be excused as a cosmetic fallback issue.

## WALK-G005 — Single-seller migration without rewriting history

Use FIX-S-MIG with 50 products,100 variations,20 historical orders,an open reservation,pending refund and scheduled job. FR-D018/021/041/056/057/075 and FR-G006 preserve native identifiers,URLs,legacy context and financial history. A first-party DATA-D03 facade initially reads native price/stock. An approved cutover creates DATA-D05/06 mappings and increments authority epoch after native in-flight payment/reservation uncertainty is reconciled. API-D031/032 supports bounded import/operation reporting; the migration itself is a privileged supervised workflow,not an unguarded public toggle.

Freeze affected writes,validate mappings and baselines,save source digests,install persisted non-purchasable native shells,verify the independent dependency-loss guard,switch authority and rebuild public projections. Seller/catalog/finance/SRE roles perform separate approvals. A historical refund or renewal continues through its registered authority path; a legacy restock after migration must be routed to the mapped pool with operation identity,not increment the native zero-stock shell. When the plugin is fully absent, operators cannot rely on its hooks: maintenance remains in effect and the external recovery runbook owns the action.

Before new marketplace transactions, a reconciled mapping/configuration rollback may be possible. After new captures/orders/payouts, pause affected sales and repair forward; restoring an old database would lose obligations. Historic purchased commercial fields stay untouched. Search/canonical URLs remain stable; only verified duplicate mappings receive redirects. Analytics reports one purchase per genuine business transaction and does not emit historical orders anew during import.

Expected design outcome: first-party operation can evolve to third-party offers with conserved stock/money and audit lineage. TEST-S106/107/108/114/117/118/119 plus TEST-G006/018 trace to WP-G008/018/019/021/025/027. Future evidence includes before/after ID/URL digests,authority epochs,stock/ledger reconciliation,callback replay and failure at every checkpoint.

## Findings repaired through the authoring review

| Finding | Design resolution and owning artifact | Future proof still required |
|---|---|---|
| Generic settings implied every native control had responsive/state editing | Explicit native/extended/constrained/unavailable support in settings inventory | Editor/browser/a11y tests |
| Offer/sort noindex conflicted with canonical-consolidation intent | Crawlable noindex+self canonical for removal;variation consolidation without noindex in SEO matrix | Rendered/crawler checks |
| Public offer TTL differed across data/operations | Shared15second public-offer target;authoritative purchase always fresh | Invalidation/load/outage tests |
| UI aliases could become duplicate analytics wire events | Map UI labels to canonical events;one server purchase producer | Consent/dedup integration tests |
| Plugin loss exposed native marketplace projection | Persisted non-purchasable native shell plus independent health/maintenance gate | Direct/native/existing-cart/dependency-loss tests |
| Legacy restock could reopen guarded native stock | Epoch-aware pool routing;no native restock during absent-plugin recovery | Historical refund/restock with adapter missing |
| Backorders could make on_hand negative | Marketplace backorders disabled until separate verified demand/debt model | Quantity/race tests |
| Payout/order state summaries omitted uncertainty | Explicit unknown payout and captured payment with paid-stock-exception flag | Out-of-order/late callback cases |
| Fixed commission lacked currency constraint | Currency/exponent frozen with commission version | Mixed-currency rejection |
| Cookie-authenticated REST GET omitted native nonce | Require native wp_rest nonce on authenticated cookie reads and writes (SRC-G011) | Authentication and object-access contract tests |
| Finance approval could bypass step-up with integration credential | Human approval channel includes independent principal and fresh step-up | Integration-token negative approval tests |

## Final contract reconciliation

A final bounded AI review identified and repaired four further contradictions. OpenAPI and the API limits document now use the same scoped quotas. The shared webhook/job table owns retry counts (including the initial attempt),class-specific leases and feed quarantine;plugin/SEO documents link to that policy. Event dedup expressions now use exact payload/envelope fields or the explicit trusted producer_key context. Payout analytics are restricted to finance operations and cannot be exported for marketing even with consent. These are documentation corrections;future runtime verification is still required.

## Separate professional perspectives

| Perspective | Analytical disposition | Human evidence outstanding |
|---|---|---|
| Product | Full phases,profiles and safe unsupported capabilities represented;unknown business choices gated | Product scope/KPI and commercial approval |
| Architecture | Native/custom authority and provider boundaries are explicit;guard/recovery dependency now visible | Architecture review of exact implementation/providers |
| Design/accessibility | Original tokens,screen/component states and RTL/focus rules specified | Real frames,usability sessions,manual conformance review |
| Engineering | Typed contracts,ID/money fields and lifecycle failure paths reviewed | Builds,tests,profiling and exact-version integrations |
| SEO | Removal and consolidation intent separated;one emitter and price/feed consistency specified | Actual rendered/schema/feed/crawl evidence |
| Security/privacy | Resource scope,privileged separation,uploads/consent/retention and dependency loss considered | Independent threat/control applicability and security testing |
| Finance/marketplace | Illustrative arithmetic balanced;immutable adjustments and unknown provider states explicit | Approved lawful funds/tax policy and sandbox reconciliation |
| QA | Critical fixtures,negative cases and trace links identify future evidence | Executed test results and defect dispositions |
| Operations | Cache/SLO targets,guard ownership,restore and forward recovery scoped | Staffed on-call,measured capacity and verified drills |

The final documentation report records local validation outcomes. These analytical dispositions describe documentation consistency within the reviewed scope; they do not certify the future system safe,compatible,compliant,accessible or production ready.
