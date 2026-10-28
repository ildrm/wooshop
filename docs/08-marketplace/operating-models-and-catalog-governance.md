# Operating models and catalog governance

Document ID: DOC-D-MKT-MODELS | Title: Operating models and catalog governance | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending business/legal approval and future verification
Owner: Marketplace Solution Architect | Reviewers: Marketplace QA Specialist; Payments/Settlement Engineer; Legal/Compliance Specialist
Scope: future single-merchant and marketplace operations | Requirements: FR-D014; FR-D018; FR-D019; FR-D059; FR-D060 | ADRs: ADR-05; ADR-06; ADR-09
Dependencies: [domain model](../04-data/domain-model-and-erd.md); [requirements](../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections 6–8; [source register](../00-governance/sources.csv)

## Operating modes

| Mode | Catalog/offer behavior | Commercial boundary |
|---|---|---|
| Native single merchant | WC products; first-party seller facade; native price/stock | Existing native order/payment responsibilities |
| Marketplace-ready single merchant | Explicit seller/offer/snapshot identities; migration tested before enabling alternate authority | Foundation includes future allocations without rewriting history |
| Vendor-owned listings | Each distinct vendor-authored product has native identity and seller offer | Seller owns approved listing proposals; canonical claims moderated |
| Shared canonical catalog | One reviewed product/variation and many eligible offers | Customer chooses a seller; offer-specific terms shown |
| Mixed first/third party | First-party organization follows same offer and disclosure contracts | No hidden priority or accounting exemptions |

Marketplaces do not inherently share ownership, tax liability or fund custody. DEC-01/02/03/04 approve jurisdictions, merchant/seller of record, invoicing, money flow and provider eligibility before activation. The proposed plugin accounting subledger is not a customer wallet. Customer documents identify the responsible seller, platform role and return contact using approved policy text.

## Catalog governance workflow

Seller submits product identity, manufacturer identifier, model/revision, condition, origin and evidence. Matching suggests candidates using exact identifiers and reviewed attributes; it does not auto-merge by title or image similarity. Catalog administrator chooses match/create/reject/request evidence. Safety/technical claims route to the domain specialist. Seller then publishes an offer against the approved canonical product or exact variation. The commercial offer contains seller SKU, price, stock, condition, warranty, delivery and policy references; it cannot mutate shared specifications.

Duplicate conflicts freeze automatic matching, preserve both records and queue a reviewer. A confirmed merge preserves old IDs as aliases and redirects with SEO review; purchased snapshots remain unchanged. A mistaken merge is reversed by restoring identities and mapping history, not editing old transactions. Provider clone listings have explicit external-to-canonical mappings and a read-only projection relationship. [Provider evaluation](../03-architecture/marketplace-provider-evaluation.md) explains evidence and limitations.

## Moderation and disclosure rules

Publication states are draft → submitted → review → approved/published or rejected; published → suspended/retired. Every rejection has a reason code and appeal path. Price/stock updates may skip content moderation only inside approved ranges and valid seller state; changed identity, condition, safety claims or warranty material terms require review. Moderators cannot approve their own seller submissions. Sponsored placement is labeled and separate from preferred-offer ranking.

FR-D059 tests duplicate conflict resolution with preserved order IDs and URL redirects. FR-D060 tests marketplace activation without approved legal/provider configuration and expects a blocked gate with actionable reasons, while ordinary permitted single-merchant browsing continues. No legal text here is finalized legal advice.
