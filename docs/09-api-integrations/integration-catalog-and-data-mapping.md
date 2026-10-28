# Integration catalog and data mapping

Document ID: DOC-G-INTEGRATIONS | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending external verification and human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Solution Specialist; Application Security Engineer; Payments/Settlement Engineer; QA/Test Engineer
Scope: proposed contracts; no operating endpoints or selected commercial providers | Requirements: FR-G016; FR-D011; FR-D012; FR-D014; FR-D015
ADRs: ADR-03; ADR-04; ADR-06; ADR-09; ADR-16 | Dependencies: [OpenAPI](openapi.yaml); [operation catalog](api-operation-catalog.csv); [events](event-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; SRC-G011; SRC-G012; SRC-G013; [source register](../00-governance/sources.csv)

## Dependency decision record

The two custom distributables are the theme and companion plugin. Dependencies below are not assumed bundled or purchased. The [version baseline](../00-governance/version-baseline.csv) and [capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) control support. Each catalog row is a proposed dependency family; an exact version,entitlement,cost and operations/exit owner must be captured at DEC-04/09/12 before activation.

| Dependency/candidate | Ownership and data mapping | License/cost category | Responsibility,limit and exit |
|---|---|---|---|
| WordPress native | Users/content/media/editor IDs remain native;site scope external to numeric ID | Open-source core;hosting/maintenance;distribution-license review | Store Administrator+Release Owner;export content/assets/config;core baseline released but project untested |
| WooCommerce native | Product/variation/customer order/tax/coupon authority via supported APIs;HPOS policy | Open-source core;integration/support cost | WooCommerce Specialist;CRUD exports and stable IDs;no direct order-post coupling |
| Custom marketplace core | Organization/offer/pool/allocation/journal maps native IDs to site-local UUIDs | Custom engineering/security/maintenance;repository GPLv2 context subject license review | Marketplace architect;bounded canonical-offer model;export complete snapshots/ledger/mappings |
| Dokan candidate | Provider seller/listing IDs require reviewed mapping;clone listing is not inherently canonical offer | Edition/site/renewal/support and adapter costs;exact license unverified | API Engineer;capability evidence in provider evaluation;retain clone→canonical map;never assume universal blocks/type support |
| WooCommerce Product Vendors candidate | Provider vendor/product/commission representations mapped through adapter | Paid extension/renewal/support;exact entitlement unverified | Marketplace architect;verify shared-catalog/finance gaps;export obligations before replacement |
| WooCommerce Product Bundles / Composite Products candidates | Configuration graph,component identities,price/stock/refund allocation | Paid site license/renewal plus adapter testing;terms pending | WooCommerce Extension Developer;exact mode combinations gated;export configuration snapshot |
| WooCommerce Subscriptions candidate | Contract ID→signup/renewal orders,payment tokens references,status/obligation dates | Paid license/renewal;gateway eligibility and retry costs | Commerce Operations;no automatic token portability;retain old provider service lane until migrated |
| WooCommerce Bookings candidate | Resource/timezone/interval/persons/provider reservation map | Paid license/renewal;integration and rescheduling support | Commerce Operations;not generic stock;export resource/calendar obligations and prevent double booking |
| Payment/payout provider;Stripe only an unselected example | External account/payment/capture/refund/transfer IDs→operations/journals;provider truth for transfers | Transaction/refund/dispute/payout/reserve/FX fees;country/entity terms unverified | Finance+Legal+API;approved funds flow required;export settlements/references;tokens may not port |
| Shipping/fulfillment provider;unselected | Origin/profile/rate/tracking IDs→allocation and purchased delivery snapshot | Label/rate/API/subscription/support fees | Commerce Operations;failed quote cannot invent delivery promise;export tracking and open shipments |
| Search provider;optional unselected | Published product/specification/eligible-offer projection only | Query/index/storage/egress costs;license/service terms pending | Search Engineer;no transaction authority;rebuild native projection and revoke credentials on exit |
| Email/SMS provider;unselected | Recipient reference,template version,consent scope,delivery receipt→notification operation | Per-message/number/subscription costs;data processing/location terms pending | API Engineer+Privacy;deliverability/suppression export;preserve unsubscribe state |
| Malware scanning/media storage;unselected | Quarantined asset hash/scan verdict→WordPress attachment and rights metadata | Per-scan/storage/egress/support cost;terms pending | Security+SRE;no public unsafe upload before scan;export safe objects/manifests |
| SEO extension;optional unselected | One configured renderer consumes coordinator's canonical/schema/metadata projections | Free/paid extension license,renewal,feed support | SEO strategist;disable duplicate emitters;export overrides/redirects and preserve URLs |
| Analytics/Search Console/Merchant Center integrations | Purpose-approved event/aggregate export and canonical catalog/feed mapping | Service/connector/support costs and account/terms review | Analytics/SEO+Privacy;no accounts created;export allowed reports and revoke tokens |
| Fonts/media assets | Licensed originals,derivatives,alt/rights/provenance→site profile | Font redistribution/site license or commissioned media cost | Graphic/Product Media Specialist;fallback system fonts;portable rights records |

Official platform release/recommendation evidence is in SRC-G001–005. Provider facts are limited to the claims and gaps in [provider evaluation](../03-architecture/marketplace-provider-evaluation.md) and the source register. Every price,license entitlement,regional availability and runtime compatibility not established there remains unverified. No procurement,account or vendor contact occurred.

## Mapping rules

A mapping key includes site,provider namespace,external entity kind and external ID. Canonical target,source version,verified timestamp,reviewer and digest form provenance. Ambiguous product matches stay pending;similar titles never auto-merge safety-critical technical identities. Provider-owned credentials/KYC/token material remains referenced,not copied into public metadata. Native IDs/URLs are preserved where appropriate;retired mappings become tombstones so later external-ID reuse cannot rewrite history.

Connector imports stage,validate,preview and apply version-matched batches with rejected-row reports. Exports are scoped,redacted,expiry-bound and audited;no cross-seller joins through report/download URLs. A provider outage disables affected new capability or serves a documented safe fallback while pending money/fulfillment obligations remain reconciled. Mapping/reconciliation evidence is required for both onboarding and exit.
