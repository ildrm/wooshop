# Independent configuration and onboarding

Document ID: DOC-U-22 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending domain/business approval | Owner: Store Administrator
Reviewers: Product Catalog/Merchandising Specialist; Solution/Software Architect; QA/Test Engineer
Scope: configurable site profile without shared application logic fork
Requirements: FR-U033; FR-U016 | ADRs: ADR-03; ADR-04; ADR-14; ADR-15
Dependencies: [specifications module](../07-plugin/modules/product-specifications.md); [presets](../06-theme/presets-and-store-branding.md)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.5; [source register](../00-governance/sources.csv)

## Site isolation (FR-U033)

Two independent WordPress installations share released theme/plugin packages, not runtime customer, order, cart, inventory, seller organization, credentials or database records. Site-local numeric WC IDs are never globally unique; mappings and caches include site identity. A profile package is configuration only. The generic third-website walkthrough demonstrates reusability by changing taxonomy, schemas, palette, content and enabled adapters without adding store-specific logic.

## Proposed onboarding sequence and gates

1. Name the site/business owner; choose jurisdiction/entity, merchant/funds model, currencies/tax and provider availability through DEC-01–04. Locale/preset is not evidence for these choices.
2. Install approved releases only in future implementation; confirm declared version/capability baseline and independent secret stores. This documentation assignment does not perform installation.
3. Select one preset as a draft; map licensed logos/fonts/media and verify native user-style precedence. Fill local navigation/content/policies with human approval; no copied reference assets.
4. Import a profile in dry-run: schema versions, module dependencies, destination taxonomies, media/license mappings and unknown controls. Reject users/orders/secrets/consent and foreign-site opaque IDs.
5. Configure native commerce first; activate selected optional modules through kernel validation. Set first-party seller mapping and marketplace data foundation without enabling third-party sales before gates.
6. Load a small synthetic catalog covering simple/variable/external/digital/grouped and gated advanced types. Validate technical provenance, variation fields and unsupported states.
7. Prepare actual catalog/content only under approved business procedures in future work; run all required functional/privacy/security/SEO/RTL/restore checks and train administrators.
8. Complete release-readiness gate with provider contracts, reconciliation, support/runbooks and human sign-off. The documentation does not imply launch authorization.

## Portability manifest content

Permitted: profile_key/schema_version, token/preset references, allowed module settings, category/schema definitions, localized labels, public content templates and asset references with rights. IDs map by explicit destination lookup or approved creation; no blind reuse of numeric source IDs. Excluded: password/API keys, webhook secrets, sessions, orders, customers, seller memberships, private files, payment details and consent records. Commercially material settings such as currency/commission/tax are displayed as proposed import values requiring the relevant local decision gate, never silently applied by style import.

## Example third website

A stationery store selects neutral preset, size/material/page-count schema, native products, wishlists and Q&A; disables smart-home compatibility and outdoor claims. Plugin with a different compatible theme still exposes typed specs and private saved-list forms. A new custom engraving provider declares configured price/lead-time/cancellation capabilities; until its contract tests pass, products render informative unsupported purchase states. No promise of every third-party plugin working follows from this extension point.

## Acceptance and handoff

TEST-U033 imports one package into two sites with identical local product IDs and verifies independent settings, caches, customer sessions, offers and emails. Mutating brand or taxonomy on one site changes nothing on the other. Invalid/missing media or modules produce a complete dry-run report; interrupted apply retains previous revision. Store Administrator receives local configuration inventory, decision gates, training record and rollback/forward-recovery plan. No shared-cart or cross-site seller organization is approved by this brief.
