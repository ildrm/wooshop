# Fallback, compatibility and theme upgrades

Document ID: DOC-U-14 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Theme Developer
Reviewers: Design System Designer; Accessibility Specialist; Application Security Engineer
Scope: reusable native block theme presentation and customization
Requirements: FR-U023; FR-U025 | ADRs: ADR-01; ADR-12; ADR-15; ADR-16
Dependencies: [component catalog](../05-ux-ui/component-specifications.md); [settings](../05-ux-ui/settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–9; [source register](../00-governance/sources.csv)

## Capability-based fallback (FR-U023)

The [capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) is authoritative. Unknown types render title/media/description and a clear unsupported purchase state; they never inherit a simple-product button by accident. External products retain outbound purchase behavior; grouped parents expose valid child actions; virtual/downloadable flags remove inappropriate shipping UI. Complex marketplace subscriptions/bookings/bundles remain disabled until a named adapter passes its gate.

| Failure/change | Presentation | Persistent obligations |
|---|---|---|
| Companion plugin absent in native mode | Native WC templates/blocks and attributes; enhancements hidden | Native orders/downloads continue through WC |
| Marketplace provider absent | New affected purchases disabled; customer gets status/help | Kernel/service-mode tools preserve allocation/refund/support access; alert operator |
| Unrelated compatible theme | Plugin provides semantic forms/tables with basic styles | No business data moves to theme |
| Search provider unavailable | Native search fallback, label reduced filters when necessary | Cached public suggestions expire; no private leakage |
| Notification/media provider failure | Pending status/transcript/still image as appropriate | Jobs remain retryable; no false delivered state |
| JavaScript unavailable/interrupted | Public navigation/server-rendered product links/forms where supported | WC block-runtime dependency clearly stated; no claim of scriptless checkout compatibility |

## Upgrade contract (FR-U025)

Released theme versions have a declared minimum WP/WC baseline and a tested-in-future matrix; no runtime compatibility was tested here. Before changing a site: export supported settings/templates, snapshot current revision, verify child theme/custom blocks, stage with synthetic data, perform visual/localization/capability smoke checks and approve. Native user edits are preserved; an updated base template must not overwrite saved customized templates without an explicit diff/rebase choice. Deprecated tokens/attributes keep aliases for one documented support window; deletion requires migration notes.

The migration report lists changed default tokens, template version differences, unknown controls and unavailable assets. Semantic schema migrations belong to the plugin; theme upgrades do not perform transaction migrations. If a style release fails, reverting package/settings revision is distinct from rolling back commerce records; never restore an old database over new orders. An obligation-bearing module cannot be safely removed merely to restore a visual page.

## Evidence required

TEST-U023/U025 checks native theme-only checkout, plugin workflows on another compatible theme, provider outage, saved templates after upgrade, RTL overflowing labels and missing assets. Version/extension pins come from [version baseline](../00-governance/version-baseline.csv), not an unqualified latest label. Release/Maintenance Owner receives visual diffs and unresolved extension caveats. The product cannot claim universal extension compatibility or accessibility before those tests and human reviews.

## Marketplace adapter loss: fail-closed boundary

Marketplace-mapped native WC products/variations are proposed persisted nonpurchasable shells: native managed stock0 and backorders disabled, with the verified active offer adapter supplying eligible availability/quote. Native catalog projections are never a fallback stock/price authority. If the adapter disappears, direct native add-to-cart, cart-update and checkout—including existing sessions—must fail for mapped lines; native-mode products retain normal WC behavior. This guard is a future integration acceptance gate, not a claim that a specific hook already works. External health/maintenance controls stop affected sales on abrupt plugin loss; operators restore/transfer service to reconcile pending payment/refund/fulfillment obligations. Theme hiding alone is insufficient. TEST-U023/U030/U064 includes forced dependency loss and direct native requests.
