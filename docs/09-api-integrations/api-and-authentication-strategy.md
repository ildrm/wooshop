# API and authentication strategy

Document ID: DOC-G-API-AUTH | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending external verification and human approval | Owner: API/Integration Engineer
Reviewers: WooCommerce Solution Specialist; Application Security Engineer; Payments/Settlement Engineer; QA/Test Engineer
Scope: proposed contracts; no operating endpoints or selected commercial providers | Requirements: FR-G007; FR-G008; FR-D077; FR-D083; FR-D084; FR-D095
ADRs: ADR-03; ADR-04; ADR-06; ADR-09; ADR-16 | Dependencies: [OpenAPI](openapi.yaml); [operation catalog](api-operation-catalog.csv); [events](event-catalog.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF; SRC-G011; SRC-G012; SRC-G013; [source register](../00-governance/sources.csv)

## Native and custom surfaces

The verified [Store API](https://developer.woocommerce.com/docs/apis/store-api/) supports customer-facing product,cart and checkout use under `wc/store/v1`, bound to the current customer/session rather than arbitrary private customer/order lookups (SRC-G012). Its [extension guidance](https://developer.woocommerce.com/docs/apis/store-api/extending-store-api/) provides endpoint-data and server-cart-update mechanisms (SRC-G013). The proposed plugin uses those native mechanisms for `extensions.commerce` offer/variation/configuration references, not a replacement checkout or fragile DOM patches. Exact chosen WooCommerce build and extension behavior are future contract tests.

The proposed custom namespace is `/wp-json/commerce/v1`. [OpenAPI](openapi.yaml) contains only proposed custom routes and uses `example.invalid` to make non-deployment explicit. [Operation catalog](api-operation-catalog.csv) binds API-D IDs to methods,paths,purpose,authorization,requirements and response types. It is not a claim that WooCommerce supplies these custom endpoints. Administrative settings, seller organization operations, financial approvals and private projections never move into the public Store API for convenience.

## Authentication and effective authorization

Cookie-authenticated custom WordPress REST requests send `X-WP-Nonce` for the `wp_rest` action on reads as well as writes. Without a nonce native cookie REST authentication treats the user as unauthenticated; a nonce alone is not identity or permission. [Official authentication guidance](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/) (SRC-G011). Application Passwords are revocable HTTPS integration credentials inheriting the integration user's capabilities; per-route/resource limits are an additional project policy, not an assumed native token-scoping feature.

Effective authorization requires active site, authenticated principal or narrowly defined guest session, current capability, active organization membership, resource ownership, permitted field set and lifecycle precondition. Public product/offer reads return publication-approved projections only. Seller lists,details,exports,media,logs,search and jobs have the same scope boundary. UUID obscurity and button hiding are not authorization. Use uniform404 for invisible resources;403 may describe a denied action on an object already visible to that principal.

Guest wishlists,comparisons and permitted preferences use server-bound random guest sessions and session-specific CSRF protection. Never accept a user_id/seller_id from the browser as authority. Guest merge rotates or consumes the merge credential, preserves explicit opt-outs, rejects another session's token and deduplicates records. Alerts use the authenticated verified account baseline unless a separately specified verified guest subscription contract is enabled; a nullable customer reference does not grant anonymous list access.

## Privileged finance actions

Refund/payout approval requires a different human from the preparer, exact prepared resource version/digest and recent step-up proof bound to that principal,site,action and amount. Ordinary Application Password access is insufficient for human approval. The chosen identity/MFA integration must supply verifiable evidence with bounded expiry and replay protection; missing or unverified step-up denies approval. Jobs may submit an already approved command but cannot manufacture approval or inherit administrator status. Destination changes trigger a fresh review and hold. Contract evidence must demonstrate failed self-approval,stale digest,revoked membership,expired step-up and integration-token bypass attempts.

## Request/response boundary

The server calculates transactional money and validates selected variation,offer,stock,shipping,tax,coupon and commission. Native IDs are decimal strings; extension IDs are opaque UUIDs; Money is exact `amount_minor` string plus currency/exponent. Context-valid nonnegative prices and the configured currency registry are additional domain checks. Unknown input properties reject mass assignment. Private reads are no-store; public caching never authorizes a purchase. Async202 returns an operation reference,not a successful payout/refund. Consumers poll only their visible operation or use an authorized notification.

[Version/errors/limits](versioning-errors-idempotency-and-limits.md), [jobs/webhooks](webhook-and-job-contracts.md) and [module contracts](../07-plugin/extension-contracts-and-provider-adapters.md) govern the remaining request lifecycle. Each API-D route has a future TEST-D acceptance record; native Store API paths additionally require exact-version blocks,HPOS,session and direct-request verification.

## Signed unsubscribe contract (FR-G021, API-D038)

Email/SMS opt-out links resolve to a noindex confirmation landing; GET has no mutation. POST `/notifications/unsubscribe` accepts only an opaque token bound to site,recipient reference,purpose,channel,expiry and an unguessable token identity. Store only a hash/reference; use a verified signed-token service and rotation. The proposed token validity is180days or the earlier subscription lifetime; expired links offer an accessible request-new-link/support path without disclosing recipient state. A new link goes only to the already stored recipient after abuse checks,never a caller-supplied replacement address. Native/provider unsubscribe mechanisms must map to this same suppression authority.

A valid token grants only suppression of its signed optional purpose/channel; it cannot read preferences,resubscribe,change addresses or suppress mandatory operational obligations. No account login or broad credential is required. Replays are harmless and success/invalid/expired responses use generic204 without exposing subscription state;storage failure returns503 rather than falsely recording success. Commit suppression before acknowledgement and recheck it at send time. Remove tokens from access/application/analytics logs,disable referrer leakage on landing,and rate-limit by edge plus token hash. TEST-G021 covers scope alteration,foreign site,replay,expiry and unsubscribe between enqueue/send. This is a proposed custom endpoint,not a claimed native WordPress feature.
