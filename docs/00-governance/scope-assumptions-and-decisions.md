# Scope, assumptions and decisions

Document ID: DOC-G-SCOPE | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Business Analyst | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Scope, assumptions and decisions | Requirements: FR-G002; FR-G005; FR-G010 | ADRs: ADR-06; ADR-09; ADR-14
Dependencies: [documentation conventions](documentation-conventions.md) | Assumptions: ASM-01; ASM-02; ASM-03
Sources: USER-BRIEF; [source register](sources.csv). All proposals await the designated human gate; runtime validation is not executed.

## Scope accounting

The mandatory scope includes storefront/editor, technical catalog, discovery, customer retention, advanced merchandising, all described marketplace processes, contracts, SEO, security/privacy, quality, operations, delivery and training. Later implementation phases still have detailed specifications. Single-seller and marketplace are operational modes of the same products; complex product types are bounded by a capability matrix rather than promised universal compatibility.

[Assumptions](assumptions.csv) and [decisions](decisions.csv) are authoritative. A provisional proposal is usable for design work but cannot pass its activation gate without the stated evidence. No location, legal jurisdiction, launch date, budget, seller volume or currency is inferred from Persian language, timezone or Digikala.

## How decisions become binding

The accountable decision owner obtains evidence from the responsible specialists, records alternatives and impact, and supplies a dated human disposition. Any approval must identify exact provider/version/configuration, funds flow, geographic scope and applicable product capabilities. Silence and elapsed time do not count as approval. A changed assumption updates requirements, contracts, risk, test fixtures, training and dependencies through FR-G015.

The provisional deployment choice is independent WordPress installations with shared released packages (ADR-14). Merchant-of-record and fund-flow arrangements remain unresolved (DEC-02); UI language such as seller, platform and payout is a proposed domain model, not a legal conclusion. Currency examples in the finance docs are arithmetic illustrations. Tax and retention defaults are not legal policy.

## Safe paths while gates are open

Design and document all features, but keep external payout actions, optional provider-specific purchase flows and unsupported capability combinations inactive. Use fake fixtures for future tests. Retain read access to historical transactions and job drains where new sales are disabled. Procurement asks for proof, sandbox verification and export rights; no vendor is contracted in this assignment. Marketplace building has a separate budget gate even though its contracts are required now.

## Definition of known and unknown

A released version can be verified while its compatibility remains unverified. A chosen default can be provisional without being absent. An inaccessible reference interaction is not observed. A specified test is NOT RUN. A documentation defect must be repaired; it cannot be relabeled as a business decision to hide missing detail.
