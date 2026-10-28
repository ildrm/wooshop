# Product requirements

Document ID: DOC-G-PRD | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Product Owner | Reviewers: Business Analyst; Solution/Software Architect; QA/Test Engineer
Scope: Product requirements | Requirements: FR-G002; FR-G004; FR-G005; FR-G006; FR-G007; FR-G012; FR-G020 | ADRs: ADR-03; ADR-14
Dependencies: [conventions](../00-governance/documentation-conventions.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [source register](../00-governance/sources.csv)

## Customer and operator outcomes

Customers can discover relevant products, understand evidence and technical differences, select a compatible variant and seller, see a complete price/fulfillment promise, purchase through a supported flow, and obtain shipment, refund and support outcomes. Outdoor buyers need trustworthy specifications and fit guidance without inferred safety promises. Smart-home buyers need region, firmware, ecosystem and hub evidence without false interoperability guarantees. The system communicates unknowns before purchase.

Merchants can change identity/content/layout within controlled settings, curate catalogs, manage campaigns and serve customers. Sellers can manage their organization-scoped listings/offers, inventory, orders and financial visibility without gaining access to another seller. Platform operators can moderate catalog contributions, explain preferred-offer selection, reconcile payments and handle disputes. Developers can reuse the same products on an independent third site.

## Product composition and operating modes

The custom theme provides templates, patterns, three style presets, responsive interactions and appropriate commerce presentation. The custom companion plugin provides justified persistent features, product-information schemas, customer modules, marketplace abstractions and provider adapters. Native WordPress/WooCommerce owns native content/commerce operations. External services and extensions are separately procured dependencies.

Single-merchant mode uses native WooCommerce pricing and inventory. Marketplace mode uses seller offers with an explicit authority switch and distinct cart-line identities. Vendor-owned listings can coexist with a moderated shared canonical catalog. Historical orders remain immutable snapshots. No provider is assumed to natively support canonical offers merely because it can duplicate a product listing.

## Functional scope and acceptance

[Feature catalog](feature-catalog.csv) links every requirement family to its specification and acceptance case. [Requirements](../02-requirements/requirements.csv) are normative; [capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) bounds simple/variable/grouped/external, virtual/downloadable and proposed bundle/composite/subscription/booking/service flows. Capabilities determine price, shipping, selection and purchase controls. Unsupported combinations have browse-only or explanatory disabled states, not a misleading add-to-cart.

Core storefront, editor, accounts and native commerce form P0. P1 adds advanced discovery, technical relationships, alerts, loyalty and incentives through individually gated modules. P2 activates sellers, shared offers, fulfillment and settlements only after legal/provider/finance approval. P3 scales measured bottlenecks and maintains compatibility. All have detailed documentation now.

## Product principles and tradeoffs

Original dense discovery can coexist with readable content and keyboard access. A promotion uses real evidence and expiry, with no fake countdown, scarcity or review. Better-offer ranking is explainable and never silently changes a customer's seller. Configuration is comprehensive but validated, not arbitrary executable code. Native capabilities are reused before duplicated. Additional services follow measured load or a concrete capability need, not an abstract desire to be distributed.

## Outside the approved assumptions

Shared carts/customers across independent stores, marketplace fund custody, cross-currency settlement, every possible extension, bespoke legal terms, real design prototypes and implementation/deployment are not assumed. Requiring any of these prompts a scoped decision/change record. The future business budget, date, jurisdictions and size remain unknown, with gates in [decisions](../00-governance/decisions.csv).
