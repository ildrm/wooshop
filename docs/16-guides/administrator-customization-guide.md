# Administrator customization guide

Document ID: DOC-S-GUIDE02 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: Store Administrator | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Administrator customization guide | Requirements: FR-S007; SEC-S008
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Starting a store configuration

FR-S007: Administrators configure each site independently. Confirm site/environment indicator, active approved theme/plugin versions and backup/revision checkpoint before editing. Use Outdoor, Smart Home or neutral marketplace preset as a starting visual choice; presets do not change catalog authority or fork commerce logic. Consult [settings inventory](../05-ux-ui/settings-inventory.csv) for native versus future custom controls, valid scopes and reset behavior.

1. Select preset and preview desktop/mobile and RTL/LTR. Check logo variants, typography license, semantic colors, spacing and content density against readable examples.
2. Configure native global styles and templates through the approved Site Editor capabilities. Saved user changes remain native overrides; a theme update does not silently restore vendor defaults.
3. Set store-owned branding, navigation, category shortcuts, promotional sections and footer/policy links. Required legal/provider texts stay unpublished until approved gate evidence exists.
4. Configure plugin modules and dependencies in the future module settings screen. Read disable consequences and active-obligation warnings before changing availability.
5. Review validation errors, accessibility contrast/focus, long product titles, empty data and missing assets in preview; publish only after assigned review.

## Scope and reset

Global theme editing requires native `edit_theme_options`; content editing uses native object checks. Plugin global settings require policy action `commerce.settings.manage`. Sellers receive only `commerce.storefront.write` within their active organization and constrained logo/color/text controls; they cannot edit templates, global CSS or scripts. The [authorization matrix](../11-security-privacy/roles-capabilities-and-object-authorization.csv) is authoritative for sensitive actions.

Reset affects the selected scope and shows what will be removed. Preserve an export/revision before resetting; preview resulting fallback. Undo/revision expectations follow the native editor for native styles and the documented plugin change history for plugin settings. Missing logo/font uses a safe local fallback; no broken layout or arbitrary remote download. Settings search returns labels/help and scope, not secret values.

## Import, export and failure handling

Export only allowlisted design/module configuration with schema/version and referenced asset/license manifest. Exclude customer/order data, API keys, provider tokens, seller verification and site-specific IDs. Import to a preview validates schema, assets, permissions and dependency constraints; report field errors and conflicts, then apply atomically. Unknown future keys are rejected or explicitly ignored with a report according to migration policy, never executed. Do not paste CSS/JS or use an unsafe HTML workaround to bypass a missing control.

If publishing fails, retain current live settings, preserve the draft and show correlation ID. If a required provider disappears, keep obligations visible and follow the operator runbook. Before changing currency/tax, seller model or checkout provider, use the decision gates; these are commercial decisions, not styling preferences. Training acceptance requires the administrator to rebrand a synthetic third site, reset one component and import a profile without changing another site's data.
