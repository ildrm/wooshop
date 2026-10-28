# Templates, patterns and block contracts

Document ID: DOC-U-11 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Theme Developer
Reviewers: Design System Designer; Accessibility Specialist; Application Security Engineer
Scope: reusable native block theme presentation and customization
Requirements: FR-U022; FR-U035; FR-U036 | ADRs: ADR-01; ADR-12; ADR-15; ADR-16
Dependencies: [component catalog](../05-ux-ui/component-specifications.md); [settings](../05-ux-ui/settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–9; [source register](../00-governance/sources.csv)

## Template catalog (FR-U022)

| Family | Native template/route concept | Parts/patterns | Data and override rule |
|---|---|---|---|
| Home/landing | front page; selected custom page template | header, footer, hero/category/collection/editorial patterns | WP page content; campaigns inserted only through typed module block |
| Product archive | Woo catalog/category/brand route | breadcrumb, introduction, filter/results region | WC query; custom facets augment, never duplicate query ownership |
| Product detail | Woo single-product template | media, product summary, purchase, details, related | Capability renderer selects native/adapter purchase surface |
| Cart/checkout | assigned Woo pages with Cart/Checkout blocks | reduced header; help footer | WC owns form/state; immutable required legal/seller disclosure slots |
| Account | Woo account route surface | account navigation; task panel | WC/private plugin renderer; no public query block for orders |
| Seller public/private | plugin registered route/view | public seller header/catalog; private workspace shell | Theme styles plugin semantic contract; does not register seller entities |
| Blog/guide/page | single/archive/page templates | readable-content, byline, related guides | WordPress content; reviewed safety claims retained with source |
|404/status |404template; operations/request state views | state panel, search/help | Routing determines status; theme never converts errors to200 |

Templates are composition; patterns are reusable editorial starting points; template parts share header/footer structure. Their full screen inventory is [SCR-U001–U040](../05-ux-ui/screen-and-template-specifications.md). Future product blocks render data from the authoritative service at request time, not prices saved in the editor. Native template hierarchy and edited-template precedence must be verified at the selected release baseline; no custom loader supersedes WordPress user overrides.

## Block contract catalog

| Proposed block | Attributes permitted | Context/output | Editor preview and fallback |
|---|---|---|---|
| commerce/specifications | group keys, layout, provenance visibility | product ID from context; typed approved values | sample marker in preview; ordinary attributes table if module absent |
| commerce/offer-selector | layout, eligible sort, reputation display | product/variation and region; offer projection | synthetic two-seller preview; link to native purchase in native mode only |
| commerce/compatibility | evidence display, required-context fields | product/configuration + requested region/firmware | explicit unknown preview; no automated compatibility assertion |
| commerce/customer-list | list purpose and density, never user ID override | authenticated/session owner | placeholder preview has no actual private records; route fallback |
| commerce/campaign | campaign ID, style variant | active publication projection and real deadline | preview labels simulated date; expired CTA removed on frontend |
| commerce/seller-panel | panel allowlist, density | active membership organization from server | no foreign org attribute; semantic table/form fallback |

Block names are proposed interfaces, not created files. Attributes reject arbitrary SQL, HTML, scripts, raw CSS and customer/seller scope identifiers. Dynamic output uses request authorization and contextual escaping. Static pattern text is editorial; sensitive logic is never serialized in block attributes. Versioned attributes support a documented migration and old saved content rendering; removed optional attributes use defaults. Missing block dependencies show an editor diagnostic and a useful public fallback instead of raw serialized markup.

## Composition constraints

Merchant content editors may edit text/media and approved pattern content; template structure changes require global template capability. Marketplace disclosures, accessible labels, error summaries and form ordering are protected invariants. Selling terms cannot be suppressed through responsive visibility controls. Repeated block instances use unique accessible IDs and do not enqueue duplicate behavior. TEST-U022 covers two instances, edited templates after upgrade, missing module/provider, copied patterns between sites, and unauthorized attempts to publish forbidden attributes.
