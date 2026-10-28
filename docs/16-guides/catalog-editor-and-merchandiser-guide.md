# Catalog editor and merchandiser guide

Document ID: DOC-S-GUIDE03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: Product Catalog/Merchandising Specialist | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Catalog editor and merchandiser guide | Requirements: FR-S008; SEO-S007
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Product creation and evidence

FR-S008: Choose the actual WooCommerce product type and supported capabilities before adding price or purchase controls. A variable product requires explicit variation dimensions and valid combinations; a grouped product references independent children; an external product has an approved external action; virtual/downloadable characteristics change fulfillment/access. Extension types require a verified adapter and supported marketplace combination.

1. Search canonical catalog using evidenced identifiers, model, variation and manufacturer, not title alone. If an exact product exists, submit an offer proposal; if identity is uncertain, send to catalog review rather than merging automatically.
2. Enter original title/description, category/brand, identifiers with provenance, media rights/alt text, specifications and normalized units. Unknown is not zero. Preserve source value/unit and conversion provenance.
3. Outdoor: record weight/dimensions/materials/capacity/fit/conditions and safety-related ratings only from authoritative product evidence. Do not infer safe combinations from shared category names.
4. Smart Home: record protocol/ecosystem/region/electrical requirements/hub/firmware/support dates as separate attributes. A matching Matter/Thread/Zigbee/Wi-Fi label does not prove interoperability; include model/version/region/evidence/date/confidence.
5. Preview populated/long/missing content and variant/offer states, submit for moderation, then publish only approved claims.

## Offers and merchandising

Canonical descriptions belong to the catalog owner; seller offers carry seller-specific SKU, price, stock, condition, warranty, fulfillment origin and approved policy. Changing a seller price does not rewrite product identity. Shared product reviews and seller service ratings are separate. Moderators record reason and appeal route; sellers cannot approve their own safety or compatibility claims.

Campaigns define real start/end time, eligible products/stock, truthful reference price, audience and timezone display. Preview expired/empty states. Countdown never restarts per visitor or suggests scarcity unsupported by current stock. Prefer native product collections for new/bestselling/featured sets; custom merchandising stores only justified ranking/eligibility rules. Sponsored placement is labeled and does not override unavailable offer checks.

Curated facets/categories need distinct buying intent and useful editorial content; not every filter becomes indexable. Review titles, canonical URL, image rights, manuals and guide links under the [SEO policy](../10-seo/seo-strategy-and-responsibility-boundaries.md). Feed export waits for commercial-data parity and market approval.

## Bulk work and corrections

Use schema-versioned CSV preview with bounded batches, error report, duplicate identity checks and explicit unit mapping. Fix invalid rows before apply; imported seller ID cannot widen scope. For a mistaken canonical merge, preserve mappings/history and escalate to Data Architect; do not delete historical orders or republish another seller's content. Corrections create new evidence/version and invalidate search/cache/feed projections. Test the resulting customer view and checkout eligibility. Training acceptance is a synthetic outdoor variation and smart-home compatibility claim, including one held unsupported claim and a safely rejected import row.
