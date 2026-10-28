# Product specifications and compatibility

Document ID: DOC-D-SPECS | Title: Product specifications and compatibility | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future verification
Owner: Database/Data Architect | Reviewers: Marketplace Solution Architect; WooCommerce Extension Developer; QA/Test Engineer
Scope: logical data and future implementation contracts | Requirements: FR-D049; FR-D050; FR-D051 | ADRs: ADR-04; ADR-10; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [architecture](../03-architecture/architecture-overview.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 7–8; [source register](../00-governance/sources.csv)

## Typed schema model

A category profile references a versioned schema with stable field keys, translated label/help, primitive type, unit dimension, canonical unit, allowed units/range, requiredness, facet/compare visibility, provenance requirement and editorial permission. Product values carry schema version, normalized value and original source value/unit. Unit conversions are dimension-safe and round only at display; a weight cannot be imported into volume. Missing values display “Not supplied” and do not imply zero or incompatibility.

Native WooCommerce attributes remain the owner of variation options and ordinary filter terms. The specification module adds typed technical evidence; it does not create a second variation identity engine. A schema update that changes meaning creates a new field key or explicit migration. Removed fields retain historical provenance and leave approved redirects for filter URLs where needed.

## Outdoor examples — configuration, not hardcoded branches

Proposed keys include mass_g, dimensions_mm, capacity_l, material_terms, size_system, fit_notes, intended_activity, conditions, temperature_rating_c with test standard, waterproof_rating_mm with evidence, certification_claims, manual_ref, care_text and accessory_relation. A sleeping-bag “comfort” rating is not interchangeable with “limit”; the type and cited manufacturer test method are required. Climbing safety combinations require reviewed manufacturer/certification evidence; matching connector labels alone cannot certify a safe system. Unsupported claims are withheld from filters and recommendations until reviewed.

## Smart Home examples

Separate connectivity_protocol, transport, ecosystem, hub_requirement, model_revision, electrical_voltage_v, frequency_hz, plug_region, power_w, installation_qualification, firmware_min/max, support_end_date, manual_ref and accessories. Matter, Thread, Zigbee and Wi-Fi are distinct attributes. A protocol match does not prove ecosystem support. Compatibility claims are directional and may be conditional: Device A revision 2 + Hub B firmware >=3.4 in a documented region. Unknown/expired evidence shows “Not verified,” never “Compatible.”

## Compatibility evaluation

The evaluator receives exact product/variation/model revision, target ecosystem/hub, firmware and region. It returns compatible/conditional/incompatible/unknown, reasons, evidence IDs, evaluated_at and expiry. Explicit negative evidence overrides a positive generic claim; newer reviewed evidence supersedes older evidence only within its scope. Conflicts enter editorial review and return unknown until resolved. Evidence records retain publisher/document hash, date, reviewer and provenance. Revocation invalidates caches and affected recommendations and queues operational review of open orders where safety or functional suitability may be affected.

## Permissions, imports and acceptance

Catalog administrators edit schema definitions. Sellers propose values/evidence on their own submissions; they cannot approve safety claims or change shared canonical data. Product specialists approve domain claims with a second reviewer for safety-sensitive categories. Imports stage and validate units, allowed keys, evidence and relationships before atomic publication per product. FR-D049–FR-D051 verify dimensional conversion, invalid ranges, seller approval denial, firmware/region conflict handling and revoked evidence propagation. No automated text generation is an authoritative product safety source.
