# Content, international and marketplace SEO

Document ID: DOC-S-SEO04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: SEO/Content Strategist | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Content, international and marketplace SEO | Requirements: SEO-S007; SEO-S008
ADRs: ADR-13 | Dependencies: url-indexation-canonical-matrix.csv
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: User brief section10; SRC-S005; SRC-S007; localized-versions fetch unavailable; [source register](../00-governance/sources.csv)

## Editorial workflow

SEO-S007: Category editors define a distinct buying intent, required decision attributes and at least one useful buying-guide relationship before publishing an indexable category/brand/collection. Product content combines manufacturer evidence with original editorial explanation; safety and interoperability assertions require specialist review and dated provenance. Sellers can propose claims but cannot publish certification, compatibility or review statements directly. Product reviews remain customer opinions visibly moderated under one policy. Editorial reviewers disclose author role and evidence; no invented credentials or copied vendor prose presented as independent testing.

Guides link to relevant categories and products using descriptive anchors, while PDPs link to size, installation, care and compatibility guides. Do not generate doorway pages for every seller/attribute/city. Product photos show the actual item and variation; missing images use an accessible placeholder excluded from product-rich imagery. Editorial images retain license and attribution requirements in media metadata.

## Marketplace canonical rules

One shared canonical product collects eligible seller offers; offer selectors do not create independently indexed copies. A vendor-owned listing with genuinely distinct product/condition/bundle and original content is self-canonical. A proven duplicate is merged only after identity review; preserve order snapshots, redirect the former URL to the corresponding canonical product and retain mapping evidence. Seller storefronts become indexable only after approval, meaningful public information and at least one eligible offer; unapproved/empty stores are noindex. Suspension removes new offers but does not erase customer support or historical orders. Permanent closure displays a neutral notice and eventually returns410 if no useful public function remains; never redirect every closed seller to the homepage.

## Locale and region

SEO-S008: English and Persian are design examples; neither implies country, tax, currency or legal regime. Two stores are independent and never automatically hreflang alternates. If a store later publishes actual translations, use stable locale paths (`/en/`, `/fa/`), translated titles and body content, self canonicals per locale and reciprocal hreflang only between truly equivalent published URLs. Locale-only selection does not force a currency change. Preserve user choice; no forced IP redirects. Exclude missing/draft translations and avoid routing them to unrelated homepages. Region variants require DEC-01/03 and provider/feed eligibility review before publication.

The Google localized-versions seed URL could not be fetched in this research session; hreflang details are proposed design and require official-source revalidation at phase3 and phase8. This gap does not block the base single-locale architecture. Native directionality, mixed product IDs and localized search are specified in the design family. Translation review checks intent and technical units as well as grammar. A language switch preserves the corresponding product when it exists and clearly states when a translation is unavailable.
