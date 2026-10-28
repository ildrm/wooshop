# Unresolved decisions and readiness gates

Document ID: DOC-G-READINESS | Revision: 1.0 | Date: 2026-10-28
Status: blocked by decision; pending human approval | Owner: Product Owner
Reviewers: Legal/Compliance Specialist; Finance/Tax Specialist; Solution/Software Architect; QA/Test Engineer
Scope: business, provider, human and runtime readiness | Requirements: FR-G010; FR-G019
ADRs: ADR-06; ADR-09; ADR-14; ADR-16 | Dependencies: [decisions](../00-governance/decisions.csv); [release gates](../14-delivery/quality-gates-and-handover.md)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; [sources](../00-governance/sources.csv)

## Decisions that remain open

The [decision register](../00-governance/decisions.csv) is authoritative for recommendations, alternatives, owner, phase gate, consequences and status. None is represented as a human approval. The following is an operational reading of their effects, not a duplicate decision registry.

| Gate | Evidence needed to close | Activity constrained while open |
|---|---|---|
| DEC-01 entity/jurisdiction | Qualified entity/country and applicable-obligation assessment | Real sales/terms and legal-policy activation |
| DEC-02 funds and merchant/seller of record | Approved commercial contract,provider funds diagram,liability and custody assessment | Marketplace money collection and payouts |
| DEC-03 currency/tax/invoicing | Finance-approved currency/exponent/tax/rounding/invoice/refund rules | Live financial calculation policy |
| DEC-04 providers | Eligibility,commercial terms,exact API/webhook/export proof and sandbox contracts | Provider activation |
| DEC-05 marketplace build budget | Approved cost/capacity and capability comparison disposition | Irreversible marketplace procurement/build commitment |
| DEC-06 size/capacity | Confirmed catalog/offer/seller/load estimates and measured target scenario | Capacity purchasing and performance promises |
| DEC-07 retention/privacy | Purpose/legal-hold/erasure/consent schedule with qualified review | Production collection/retention defaults |
| DEC-08 product claims | Product-specific manufacturer/authority evidence reviewed by domain specialists | Unsupported safety/certification/interoperability claims |
| DEC-09 compatibility | Exact supported builds and executed native/provider/capability tests | Compatibility declaration and unsupported purchase flows |
| DEC-10 original design | Human approval of frames,presets,rights and accessible tasks | Final identity/visual baseline |
| DEC-11 launch | All applicable gates,test/restore/training/on-call evidence and final sign-off | Production go-live |
| DEC-12 licensing | Distribution/extension/media/font entitlement and compatibility review | Procurement/distribution of unresolved assets |

## External verification queue

Provider clones and canonical offers require deliberate identity mapping; a provider marketing page cannot establish field semantics or concurrency guarantees. Every named extension needs its exact product/mode/blocks/HPOS combination verified. Browser/database patch pins and native platform versions must be refreshed at implementation gates. Inaccessible reference interactions and inaccessible source details remain explicitly unobserved/unverified in their owning documents. Future architecture changes require updating both the adapter contract and acceptance fixtures.

## Human assurance queue

Product, architecture, design/accessibility, engineering, SEO, security/privacy, finance/marketplace, QA and operations reviews are required. This assignment's role-based analysis is AI-generated and is not independent assurance. Qualified legal/tax/payment-security advice and product-domain claim review are conditional on the actual entity,countries,providers and goods. No document here claims a certification, audit, regulatory conclusion or approved legal text.

## Runtime evidence queue

All application tests, load measurements, usability sessions, provider sandbox flows, security scans/penetration tests, accessibility conformance evaluations, recovery drills,migrations,deployments and production analytics are NOT RUN. Test cases specify future evidence and release blockers. Documentation validation checks only the artifacts that exist. A complete written contract may remain pending human decision; a missing behavior specification may not be hidden behind this queue.

Proceed with future implementation only under a separate authorized assignment and within the accepted phase gates. Preserve existing obligations whenever a feature is disabled,provider replaced or site retired. Closing a launch gate is a human decision supported by evidence, not an automatic consequence of passing local document checks.
