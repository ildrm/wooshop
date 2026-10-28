# Offers, preferred-offer selection and seller policies

Document ID: DOC-D-BUYBOX | Title: Offers, preferred-offer selection and seller policies | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D021; FR-D022; FR-D023; FR-D064; FR-D065 | ADRs: ADR-05; ADR-07; ADR-13
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Offer lifecycle and fields

An offer targets one canonical product or exact native variation and seller organization. Required active fields are seller SKU, condition, origin, price/currency, inventory pool, shipping profile, warranty/return terms version and publication eligibility. Available quantities and promised delivery derive from authoritative adapters. Offer updates use expected version; concurrent edits return conflict with current version and do not overwrite silently. A retired offer remains addressable in historical purchased snapshots.

## Transparent preferred-offer policy

Eligibility first removes suspended/unverified sellers, unpublished/unsupported offers, unavailable stock, invalid destination/currency, expired terms and disallowed conditions. Default ranking then sorts by total disclosed landed price for the selected destination, estimated delivery window end, verified seller-service score, and stable offer UUID as final tie-break. Where shipping/tax is unknown, label price incomplete and do not claim “lowest total.” Customers can inspect all eligible offers and sort on named criteria. Product rating and seller/service rating are separate measures.

A manual override requires marketplace administrator capability, reason, start/end time, public explanation if commercial, and audit record. It cannot bypass stock, safety, seller eligibility or destination rules. Sponsored placements are a separate clearly labeled surface and never secretly alter the default ranking formula. Scarcity badges use real available stock under defined freshness; countdowns have a real campaign end and do not reset per visitor.

## Cart continuity

Once selected, offer ID, seller and material terms remain bound to the cart line. A better ranked offer appearing later does not replace it. If the selected offer becomes unavailable, the customer receives an actionable conflict and may explicitly choose another seller. Reconfirming creates a new quote digest. Seller-specific warranty/condition/delivery differences remain visible before payment and in the order snapshot.

## Policies and governance

Seller policies use a platform-approved minimum framework with explicit variation by seller/product/region where lawful. The platform does not allow a seller-uploaded policy to bypass customer rights or security restrictions. Material policy changes are versioned and reviewed; old purchases retain the accepted version. Marketplace Operations owns ranking-policy changes, with Analytics and Trust/Safety review; tests compare reason codes rather than opaque score claims.

FR-D064 uses equal landed-price/delivery fixtures to verify deterministic ties, excluded sellers and visible sponsored labels. FR-D065 edits a warranty or price after cart creation and expects reconfirmation with no seller substitution; order records retain the accepted offer version. Preferred-offer policy is a proposed business decision requiring human review, not a measured conversion optimization.
