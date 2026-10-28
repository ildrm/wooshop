# Future engineering standards and repository layout

Document ID: DOC-G-ENGINEERING | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Solution/Software Architect | Reviewers: Product Owner; Solution/Software Architect; QA/Test Engineer; Release/Maintenance Owner
Scope: Future engineering standards and repository layout | Requirements: FR-G001; FR-G005; FR-G007; FR-G015; FR-G019; FR-G020 | ADRs: ADR-02; ADR-06; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [decisions](../00-governance/decisions.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Future structure, not created implementation

The eventual source repository is proposed to contain two separately released packages: a block theme with templates/template parts/patterns/style variations/assets, and a modular companion plugin with kernel, domain modules, native integrations, provider adapters and presentation fallbacks. Test fixtures/contracts, build packaging and release automation belong to a later implementation assignment. None of these application files, dependency manifests or CI definitions are created here.

Shared package versions can be installed independently on each site. Site branding/content/secrets/catalogs remain deployment configuration/data, outside shared distributable logic. Secrets are never shipped in packages or settings exports. Future local/staging environments use synthetic or properly anonymized data, with no unrestricted copy of customer data.

## Design and code review rules

Keep native editorial/user/media/commerce APIs authoritative. WooCommerce order access uses supported CRUD/data stores with the stated HPOS policy. Cart/Checkout extensions use supported Store API/block mechanisms; DOM manipulation is not a commerce contract. Product adapters publish explicit capabilities and fallback states. Persistent calculations and permissions belong to the plugin, not the theme. A module can depend on another module only through a versioned published contract; avoid cyclic dependencies.

Public contracts document types, errors, resource authorization, concurrency, idempotency and deprecation. Monetary arithmetic uses explicit integer minor units/exponent, deterministic allocation and immutable audit records. Sensitive operations require capability plus object ownership plus lifecycle checks; nonces are not identity or authorization. Input schemas are allowlists and output escapes match the rendering context. No unreviewed executable customization or seller-supplied template/CSS/JS is allowed.

## Quality gate expectations

Each future change links requirement/test/ADR IDs and states customer impact, migration consequences and failure behavior. Reviewers inspect native duplication, cross-site/seller cache boundaries, financial conservation, accessible/RTL interactions, data minimization, schema/feed ownership and disabled-module obligations. Significant changes update user guides and monitoring. Automated tests target behavior and adverse paths, not superficial implementation shape. Manual accessibility/domain/finance checks are retained.

Release artifacts need an exact dependency/version manifest, license inventory/SBOM, provenance and integrity verification. Compatibility testing precedes support declarations. A declaration in metadata does not prove HPOS/blocks/provider compatibility. Expand/contract migration and forward recovery precede destructive changes; order-producing periods cannot be blindly rolled back.

## Extension contribution handoff

An adapter proposal includes vendor evidence, supported type/mode matrix, ownership mappings, webhook/auth design, example payloads, test fixtures, observability, privacy/retention and exit plan. A component contribution includes tokens/settings, semantics/keyboard/focus, responsive/RTL state specifications and data/fallback contracts. The [developer guide](../16-guides/developer-extension-guide.md) is the task-level entry point. Engineers implement only after the relevant decisions and review gates, in a separate authorized assignment.
