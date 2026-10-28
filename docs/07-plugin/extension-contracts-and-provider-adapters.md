# Extension contracts and provider adapters

Document ID: DOC-U-17 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Extension Developer; Application Security Engineer; QA/Test Engineer
Scope: proposed reusable modular commerce plugin, independent sites
Requirements: FR-U028 | ADRs: ADR-02; ADR-03; ADR-04; ADR-07; ADR-16
Dependencies: [module catalog](module-catalog.csv); [domain model](../04-data/domain-model-and-erd.md); [API contract](../09-api-integrations/openapi.yaml)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 5–8; [source register](../00-governance/sources.csv)

## Capability-based provider boundary (FR-U028)

A provider adapter declares product capabilities, supported combinations, version range, required session/API hooks, stock/price authority, order snapshot needs, refund/renewal behavior and dependency costs. Registration alone does not prove compatibility. An unknown combination is unsupported until contract fixtures pass. [Capability matrix](../02-requirements/capability-and-compatibility-matrix.csv) and ADR-04 are authoritative.

| Extension contract | Input | Output/invariants |
|---|---|---|
| Product capability resolver | product/variation IDs, selected configuration, installed provider versions | purchasable flag; price shape; shipping/download/recurring/booking flags; allowed quantity; required fields; reason codes |
| Offer eligibility/quote | product/variation/offer/version, region, quantity, configuration digest | authoritative seller terms and integer-minor money; quote revision/expiry; no silent seller switch |
| Specifications projection | product ID, profile/schema revision, locale, public/private context | typed values/units/provenance; permission-filtered public claims |
| Compatibility evaluation | model/region/firmware/context plus evidence revision | verified/conditional/incompatible/unknown with conditions and evidence references |
| Customer collection command | session/customer scope, list purpose, items and revision | owner-scoped list and deterministic merge/change result |
| Notification request | template/purpose/recipient reference, business event/version | queued/suppressed/failed result; no direct promise of delivered status |
| Module lifecycle command | module key, desired state, expected revision, actor | transition or block reason; obligation count; audit correlation |
| Profile/configuration import | schema version, typed settings, asset references and maps | dry-run conflicts and validated staged revision; no credentials or customer data |

These are proposed service interfaces, not executable signatures. Wire endpoints live in the [OpenAPI](../09-api-integrations/openapi.yaml); any module command not exposed there remains an internal domain interface requiring a separate approved adapter before external use. Client theme blocks cannot invoke unregistered arbitrary methods. Public Store API additions use `extensions.commerce` as the conceptual namespace and supported WooCommerce extension mechanisms (SRC-U004); authenticated administrative APIs are separate.

## Adapter lifecycle and compatibility

Proposed named integrations: WC native simple/variable/grouped/external; WooCommerce Product Bundles and Composite Products for configured products; WooCommerce Subscriptions for renewals; WooCommerce Bookings for capacity/time; marketplace provider mapping only after explicit verification. These extension names are candidates, not an installed or tested dependency claim. Their versions/license/costs and blocks/HPOS behavior are gated in provider verification. Single-seller candidates may be evaluated first; complex multi-seller recurring/booking/composite checkout is disabled until allocation/refund/callback semantics pass.

Adapters must preserve immutable purchased configuration, map provider IDs one-to-one within site/provider namespace, and export enough state for exit. Provider-owned data remains authoritative where specified; a local projection carries source version/time and cannot overwrite it. Degraded behavior is declared per operation: public read fallback may work while purchase/renewal mutations block. Pending callbacks are queued/reconciled, never discarded because the frontend changed theme.

TEST-U028 submits an unsupported type, stale quote, foreign seller ID, missing provider, duplicate callback and unsupported schema version. It also verifies a third website can add an adapter without editing theme logic and that an adapter upgrade cannot silently expand support claims.

Typed input/output, authorization, concurrency and error contracts are in [module commands](module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
