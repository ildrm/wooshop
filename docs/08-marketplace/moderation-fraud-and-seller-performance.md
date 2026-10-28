# Moderation, fraud and seller performance

Document ID: DOC-D-MODERATION | Title: Moderation, fraud and seller performance | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D060; FR-D063; FR-D064; FR-D073; FR-D074 | ADRs: ADR-05; ADR-09; ADR-13
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Signals and review

Trust and Safety monitors unusual offer-price changes, repeated stock failures, counterfeit/unsafe claim reports, shared credential abuse, review manipulation, duplicate submissions, payout-destination changes, suspicious refund patterns and chargeback rates. Signals are indicators, not proof. Proposed thresholds are tunable and require baseline review: three stock cancellations in seven days trigger review; payout-destination changes always trigger review; a price reduction above 50% from the seller's previous published price requires confirmation/moderation for sensitive categories. Thresholds do not bypass legal notice/appeal obligations.

No protected-trait inference or unsupported identity scoring is proposed. Automated action can temporarily hold risky publication or payout within approved policy; permanent termination and contested financial adjustments require qualified human review. Decisions record rule version, evidence, reviewer, action, expiry and appeal outcome. Sellers receive meaningful reasons without disclosing sensitive detection details or other customers' data.

## Reviews and reputation

WooCommerce product reviews remain product-content authority; enhancement modules attach moderation/verification metadata. Seller-service ratings are separate and reference a verified allocation/interaction with limits against repeat voting. Product ratings are not reused as seller reputation. Removed abusive reviews retain an audit reason; legitimate negative reviews are not suppressed to inflate ratings. Public metrics show denominator/window where meaningful and avoid false precision for low-volume sellers.

## Performance and ranking

Delivery punctuality, cancellation, response time, return resolution and verified complaint rates feed a transparent seller-service assessment. Marketplace Operations reviews thresholds and bias from small samples. Preferred-offer ranking uses published criteria, and a commercial sponsorship cannot masquerade as a quality score. Penalties have expiry/remediation rules. Safety recall or credible dangerous-product evidence can require immediate sales hold and customer notification under the approved incident plan.

## Authorization and privacy

Moderators can review submissions and case evidence within assigned scope; they cannot edit commission ledger or approve their own seller content. Fraud investigations use restricted case access with audited exports. Public storefronts expose only approved business disclosures, not identity documents, internal risk flags, private contacts or full order history. Search indexes and notification templates follow the same classification.

## Acceptance

FR-D073 exercises a malicious seller description/upload, duplicate claim and manipulated review, expecting quarantine, safe rendering and reasoned moderation. FR-D074 exercises a false-positive appeal, small-sample reputation display, manual override expiry and suspension with open obligations. The expected evidence includes decision audit, accessible seller notice, retained customer support path and no undocumented money movement. No real fraud probing is performed in this documentation assignment.
