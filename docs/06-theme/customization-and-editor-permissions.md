# Customization, precedence and editor permissions

Document ID: DOC-U-12 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: WordPress Theme Developer
Reviewers: Design System Designer; Accessibility Specialist; Application Security Engineer
Scope: reusable native block theme presentation and customization
Requirements: FR-U014; FR-U015; FR-U016; FR-U035; FR-U036 | ADRs: ADR-01; ADR-12; ADR-15; ADR-16
Dependencies: [component catalog](../05-ux-ui/component-specifications.md); [settings](../05-ux-ui/settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–9; [source register](../00-governance/sources.csv)

## Native precedence and separate business settings (FR-U014)

Verified fact: WordPress documents a low-to-high hierarchy of core defaults, theme, child theme and saved user configuration. Plugin/theme filters can affect these values; they are not an extra merchant-facing inheritance layer (SRC-U009, [native hierarchy](https://developer.wordpress.org/themes/core-concepts/global-settings-and-styles/)). Native style scopes distinguish root, elements and blocks; local block styles can be more specific in the CSS cascade (SRC-U008). The cited hierarchy page is older and its embedded schema-version claim is not used to choose the release schema.

Proposed contract: each preset is a native style variation/default selection. Changing preset previews the resulting native merge and explicitly warns about retained user overrides. A template's composition and a block's local attributes may yield a different appearance without changing global token ownership. The system does not promise a universal “global→template→instance→breakpoint” order independent of native specificity. Plugin business settings (offer ranking, rewards, consent, schema) use their own typed records and never overwrite global styles. Visual plugin blocks consume shared native tokens plus their declared attributes.

## Controlled customization

FR-U035 divides controls into native (supported styles/editor), extended (future typed controls for responsive layouts, component states and presets search), constrained (approved palette/font/layout values), and unavailable (arbitrary executable markup/script/PHP, unreviewed CSS/remote fonts, hidden disclosures). The exact mapping is in [inventory](../05-ux-ui/settings-inventory.csv). Responsive overrides are only offered for the enumerated component layout/spacing/visibility keys; their scoped generated styles are a future theme feature, not claimed native breakpoint UI. State variants preserve focus visibility and minimum contrast.

| Editor persona | Allowed | Denied/reviewed |
|---|---|---|
| Platform administrator with theme management | Install approved packages, global styles/templates, profile import | Financial settings require independent finance capabilities; no implicit seller access |
| Store design editor | Approved global style and template operations if explicitly granted | Plugin activation, credentials and raw code |
| Content/catalog editor | Page content, product text/media, patterns within locks | Global templates, money/stock authority settings, seller policy suppression |
| Seller owner/catalog manager | Own approved logo/banner/accent/display text fields | Global stylesheet, templates, arbitrary URL embeds, other seller records |
| Moderator | Review seller branding/content | No unilateral payout/finance control |

Hiding controls is not security. Each custom mutation validates capability, object ownership, field allowlist and current revision server-side. Native administration remains privileged; project guarantees apply to supported editor routes, not hostile full-system administrators.

## Preview/reset/history (FR-U015–U016)

Preview creates an isolated draft revision, displays desktop/mobile/RTL states and prevents cache publication. Save validates complete changed settings, then increments revision and invalidates styles atomically. Reset one property removes the relevant user override so native inheritance resumes; reset section/global is an explicit scoped action with diff and revision checkpoint. Undo before save uses editor history; persisted restore reapplies an earlier compatible revision through validation, never restores credentials or business data. Native history availability and supported reset APIs are verified in implementation; custom settings require explicit audit/revision records regardless.

Settings search indexes labels, keys, help and supported scopes; results name the owner and link to the relevant editor screen. Import accepts versioned profile data with a size ceiling, known keys, asset manifest, license labels and digest. Dry-run reports unknown/deprecated keys, unavailable modules, unmapped taxonomies and assets. It does not import users, orders, credentials, consent or private records. Applying uses optimistic revision checks and an audit entry. Missing font/image assets fall back to system text/neutral images; no runtime remote fetch to arbitrary imported URLs. TEST-U014–U016 covers precedence, reset, malformed input, cross-seller scope and interrupted import.
