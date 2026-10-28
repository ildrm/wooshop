# Module specifications

Document ID: DOC-U-MINDEX | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: complete module navigation | Requirements: FR-U026; FR-U041–FR-U066
ADRs: ADR-02; ADR-03; ADR-16 | Dependencies: ../module-catalog.csv
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3

Each specification defines a proposed module contract, not an installed extension. Native ownership remains explicit. All modules inherit the [kernel](../plugin-kernel-and-module-lifecycle.md), [job policy](../jobs-notifications-and-observability.md) and [deactivation policy](../activation-migration-deactivation-uninstall.md). Read [catalog](../module-catalog.csv) for phase and dependencies. No module is runtime verified.

- MOD-U001: [Product specifications and units](product-specifications.md) — FR-U041; TEST-U041.
- MOD-U002: [Search, facets, synonyms and ranking](search-and-facets.md) — FR-U042; TEST-U042.
- MOD-U003: [Wishlists and guest-account merge](wishlists.md) — FR-U043; TEST-U043.
- MOD-U004: [Product comparison](comparisons.md) — FR-U044; TEST-U044.
- MOD-U005: [Recently viewed history](recent-history.md) — FR-U045; TEST-U045.
- MOD-U006: [Back-in-stock and price-drop alerts](product-alerts.md) — FR-U046; TEST-U046.
- MOD-U007: [Product questions and answers](questions-and-answers.md) — FR-U047; TEST-U047.
- MOD-U008: [Review enhancements and seller reputation separation](review-enhancements.md) — FR-U048; TEST-U048.
- MOD-U009: [Scheduled campaigns, promotional badges and countdowns](campaigns-and-badges.md) — FR-U049; TEST-U049.
- MOD-U010: [Merchandising and recommendations](merchandising-recommendations.md) — FR-U050; TEST-U050.
- MOD-U011: [Product relationships and accessories](product-relationships.md) — FR-U051; TEST-U051.
- MOD-U012: [Compatibility rules and evidence](compatibility-evidence.md) — FR-U052; TEST-U052.
- MOD-U013: [Bundles, kits and composite adapters](bundles-and-kits.md) — FR-U053; TEST-U053.
- MOD-U014: [Installation and service add-ons](installation-and-services.md) — FR-U054; TEST-U054.
- MOD-U015: [Delivery messaging and eligibility](delivery-messaging.md) — FR-U055; TEST-U055.
- MOD-U016: [Notifications, providers, templates and preferences](notifications-and-preferences.md) — FR-U056; TEST-U056.
- MOD-U017: [Price history and prior-price transparency](price-history.md) — FR-U057; TEST-U057.
- MOD-U018: [Loyalty and rewards](loyalty-and-rewards.md) — FR-U058; TEST-U058.
- MOD-U019: [Advanced cart incentives](cart-incentives.md) — FR-U059; TEST-U059.
- MOD-U020: [Consent-aware analytics and conversion](analytics.md) — FR-U060; TEST-U060.
- MOD-U021: [Bulk imports and exports](imports-and-exports.md) — FR-U061; TEST-U061.
- MOD-U022: [Diagnostics and background-job administration](diagnostics-and-jobs.md) — FR-U062; TEST-U062.
- MOD-U023: [Webhooks and external connectors](webhooks-and-connectors.md) — FR-U063; TEST-U063.
- MOD-U024: [Marketplace capability and portal adapter](marketplace-adapters.md) — FR-U064; TEST-U064.
- MOD-U025: [Product media and evidence assets](product-media.md) — FR-U065; TEST-U065.
- MOD-U026: [Configuration profiles and safe portability](configuration-profiles.md) — FR-U066; TEST-U066.
