# Outdoor and mountaineering store profile

Document ID: DOC-U-20 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending domain/business approval | Owner: Mountaineering Product Specialist
Reviewers: Product Catalog/Merchandising Specialist; Solution/Software Architect; QA/Test Engineer
Scope: configurable site profile without shared application logic fork
Requirements: FR-U031; FR-U034 | ADRs: ADR-03; ADR-04; ADR-14; ADR-15
Dependencies: [specifications module](../07-plugin/modules/product-specifications.md); [presets](../06-theme/presets-and-store-branding.md)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.5; [source register](../00-governance/sources.csv)

## Profile composition (FR-U031)

Profile key `outdoor`, schema revision1. Proposed Outdoor theme preset, independent site/currency/customer/catalog configuration, and native simple/variable/grouped/external plus virtual/downloadable flags. Enable typed specs, facets, comparison, relationships and media; advanced kits remain provider-gated. Marketplace foundation supports own/shared products and variation offers but seller activation requires business gates. No code branches on the word outdoor; the profile is configuration.

## Configurable schema

Every field inherits schema ID/revision, WC product/variation reference, source/provenance, review status and public-access classification. Measurements store original unit/value and normalized dimension; decimals have maximum6fraction digits, declared rounding for display and no floating-money treatment. Required means required before the applicable claim/category publishes, not all fields on all products.

| Stable field | Type/constraints and unit | Applies/required | Provenance and display |
|---|---|---|---|
| outdoor.weight | decimal>0; g normalized, original g/kg/lb allowed with approved factor | physical equipment; category-required | Manufacturer exact model/revision; distinguish packed/minimum weight |
| outdoor.dimensions | object length/width/height positive decimal; mm | packs/tents/gear | Measurement context assembled/packed and tolerance; unknown not0 |
| outdoor.materials | ordered material enum/string≤80 plus part | physical goods | Manufacturer composition; no inferred recycled/environmental claim |
| outdoor.capacity | decimal>0; L normalized | packs/containers | State nominal usable capacity and source |
| outdoor.size_fit | structured size labels, measurement range and unit; variation attribute reference | clothing/footwear/fit-sensitive packs | Manufacturer size chart revision; gender/fit labels only as supplied |
| outdoor.activity | controlled multi-enum hiking/camping/climbing/travel/other | relevant category | Intended activity statement; merchandising not safety certification |
| outdoor.conditions | controlled tags plus sourced description | weather-sensitive equipment | Avoid unconditional safe-for-all-weather claim |
| outdoor.temperature | object comfort/limit/extreme decimals°C + cited test standard/version/context | sleeping systems only when supported | Missing test basis suppresses rating claim; no inference from one value to another |
| outdoor.waterproof | numeric water column mm and/or rating text, test method and scope | relevant apparel/tents | Seam/material/product distinction; evidence required |
| outdoor.certification | list issuer/standard/version/certificate/ref/date/scope | safety claims | Domain specialist verifies document and exact product/region; expired/uncertain claim withheld |
| outdoor.manuals | approved WP attachment references with language/revision | safety or complex equipment | Public/protected role declared; source license retained |
| outdoor.care | reviewed text and manual reference | care-sensitive category | No invented cleaning chemical/repair recommendation |
| outdoor.accessories | typed relation to product; required/optional | relevant equipment | Required accessory needs evidence; complement relation alone not safety |
| outdoor.kit | provider-backed component config and return rules | approved kits | Component compatibility and source reviewed; no inferred climbing system |
| outdoor.warranty | seller offer policy reference, duration/unit, exclusions | purchasable offers where applicable | Seller responsibility shown; not canonical guarantee across all sellers |

All fields can be revised by schema configuration; a third store may reuse weight without using this profile. Schema enum values have localizable labels and stable keys. Product/variation fields distinguish shared product facts from size/color-specific measurements. Offer-specific condition/warranty/availability stays on offer. Facets default to activity, size, weight band, capacity, material and evidence-backed ratings; missing values excluded from filter counts with an explicit unknown filter only if useful.

## Example record and merchandising

Synthetic “Trail Pack35L”, variation medium, weight980g, capacity35L, dimensions520×280×220mm; material and waterproof claim unknown until sourced. These are fixture values, not claims about a real product. PDP foregrounds fit/size guide, weight/capacity and manuals before accessories. Comparison converts0.98kg/980g equivalently and never treats unknown waterproof rating as zero. Safety equipment claims are reviewed by the Mountaineering Product Specialist; editor cannot infer safe equipment combinations from size or connector labels.

## Governance, readiness and acceptance

DEC-08 requires product-information provenance and qualified safety review. Editor drafts → specialist verifies source/model/scope → moderator checks claims → catalog administrator publishes. Recall workflow marks affected model/lot scope, stops eligible sales and hands existing purchases to operations; it does not silently delete order history. Manuals/labels are versioned; source changes invalidate affected claims/compatibility projections.

TEST-U031 checks valid unit conversion, variable fit chart, missing certification suppression, expired manual links, seller warranty differences, rejected unsupported safe-kit claim, long Persian labels and export/import into independent catalog. TEST-U034 checks evidence revision revocation. No actual product safety certification or runtime test is claimed.
