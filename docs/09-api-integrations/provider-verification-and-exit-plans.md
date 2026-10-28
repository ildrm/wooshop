# Provider verification and exit plans

Document ID: DOC-G-PROVIDER-EXIT | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending external verification and human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Solution Specialist; Application Security Engineer; Payments/Settlement Engineer; QA/Test Engineer
Scope: proposed contracts; no operating endpoints or selected commercial providers | Requirements: FR-D014; FR-D015; FR-G016; FR-D076
ADRs: ADR-03; ADR-04; ADR-06; ADR-09; ADR-16 | Dependencies: [OpenAPI](openapi.yaml); [operation catalog](api-operation-catalog.csv); [events](event-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; SRC-G011; SRC-G012; SRC-G013; [source register](../00-governance/sources.csv)

## Recommended provisional choice

Use a bounded custom marketplace core for canonical product/variation/seller-offer semantics and integrate approved payment,shipping and optional product-type providers behind adapters. The scored [build-versus-integrate evaluation](../03-architecture/marketplace-provider-evaluation.md) is the architectural rationale. It does not authorize a budget,contract or custody model. Dokan/Product Vendors remain alternatives if their exact-version behavior and economics meet the stated gates. DEC-05 is the human commercial decision.

## Qualification package per adapter

| Evidence area | Required proof before enabled support | Accountable reviewer |
|---|---|---|
| Eligibility/commercial/legal | Entity/country/currency/product availability,fees,reserves,liability,terms,data processing and exit rights | Legal/Compliance with Finance |
| Version/platform | Exact stable provider/WP/WC/PHP versions,blocks/HPOS mode and named product capability combinations | WooCommerce Solution Specialist |
| Identity/permissions | Seller organization/membership/native product/offer mappings;cross-seller denial through API,exports,media,jobs | Application Security Engineer |
| Transaction correctness | Server price/tax/coupon/stock authority,one decrement,duplicate/late callback,partial refund and payout reversal | Payments/Settlement Engineer |
| Complex product behavior | Configuration/renewal/resource-time/reschedule/component-return invariants;explicit unsupported combinations | WooCommerce Extension Developer |
| Operational behavior | Quotas,latency/error/timeout,signature rotation,replay,outage,queue drain,reconciliation,backup and support escalation | DevOps/SRE Engineer |
| Data/licensing/exit | Complete scoped export,license/redistribution review,retention/deletion proof,mapping portability and outstanding obligations | Commerce Operations Specialist |

For every row keep source URL/date,provider release/configuration,synthetic fixture,observed result and reviewer disposition. Marketing claims or an adapter interface do not count as passed tests. A failed critical capability either prevents adoption or narrows the enabled matrix with a truthful fallback. Human reviewers approve exact scope;later provider upgrades repeat affected contract tests.

## Exit runbook

1. Inventory active obligations and authoritative fields,including pending captures/refunds,payouts,disputes,reserves,subscriptions/bookings,shipments,consents and legal holds.
2. Export native/canonical/provider mappings,immutable snapshots,balanced ledger/statement opening balances,consent/suppression and pending operation references through authorized channels. Hash/verify counts and totals by currency/site/seller.
3. Qualify successor and rehearse import on synthetic or approved sanitized staging. Do not infer payment-token or verification portability;retain a provider lane for contracts that cannot migrate yet.
4. Freeze affected new work,drain/reconcile uncertain operations,cut over scoped authority at an explicit epoch/checkpoint and verify no dual writers. Keep callbacks routed/deduplicated until old events are settled.
5. Reconcile successor balances,inventory,open obligations,URLs,feeds and support tasks. After new transactions,repair forward instead of erasing them with a stale database restore.
6. Revoke old credentials only after no legitimate callback/obligation depends on them;retain minimum legally required records,obtain deletion/retention evidence and update guides/monitoring/licenses.

## Provider-specific constraints and acceptance

Payment exit may require funds/reserve release and cannot assume an overdraft or transferable token. Subscription exit preserves renewal agreement and customer authorization. Booking exit checks resource/time exclusivity. Shipping exit preserves purchased promises and tracking evidence. Search exit is a projection rebuild with safe native fallback. Messaging exit preserves unsubscribe and receipt/dedup state;switching providers is not permission to resend a campaign.

FR-D015 and TEST-D015 require an export/reimport and obligation-reconciliation design exercise. Actual sandbox tests and exits are NOT RUN. Activation stays blocked for unknown lawful fund flow,unreconciled financial effects,cross-seller leakage,unsupported critical product combinations or absent reliable dependency-loss maintenance. See [readiness gates](../17-validation/unresolved-decisions-and-readiness-gates.md) for remaining human/provider decisions.
