# Back-in-stock and price-drop alerts

Document ID: DOC-U-M006 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Product Catalog/Merchandising Specialist; Application Security Engineer; QA/Test Engineer
Scope: MOD-U006, both independent stores and generic profiles
Requirements: FR-U046 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Notify explicitly subscribed recipients when eligible product conditions change, with send-time revalidation. Fulfillment approach: custom plugin module using the native/provider boundaries below; theme owns presentation only. Dependencies/conflicts: notification/preferences; product/offer public projection; no inventory writes. No second stock, review, coupon or schema authority is introduced.

## Authoritative records, settings and permissions

Module-owned extension fields: subscription UUID; recipient reference; product/variation; seller offer optional; trigger enum stock/price; target_minor integer string/currency; state pending/verified/active/suppressed/expired; consent revision; last_trigger_revision. Every extension record also carries site-local immutable UUID, schema version, creation/update UTC instants, audit actor reference and optimistic revision; native references retain native IDs. Required fields are non-null unless stated optional; arrays cannot contain duplicate IDs. Public projections exclude private identifiers. This logical model does not prescribe one physical table per entity.

Business settings (key:type:default:valid range): `alerts.max_per_recipient:integer:50:1..100;alerts.expiry_days:integer:90:1..180;alerts.cooldown_hours:integer:24:1..168`. Defaults are planning proposals; [settings inventory](../../05-ux-ui/settings-inventory.csv) defines edit/preview/export rules. Permissions: verified authenticated customer for baseline subscription; guest is prompted to sign in and verify; signed unsubscribe token only suppresses its scope; no seller list export. Every request/job checks object ownership on the server; client-supplied owner/site/approval fields are rejected.

## Workflows and rules

Guest request → sign in → verify authenticated account recipient → activate → detect unavailable-to-available or threshold crossing → recheck current consent/eligibility/price before send → dedup → deliver/suppress. Seller-specific alert never silently changes seller. Price target cannot be negative or foreign currency.

UI contract: PDP subscription form states pending verification/active; account manages scope; no guarantee inventory remains at click. Inherit the [component state/RTL/focus rules](../../05-ux-ui/component-specifications.md). Loading reserves layout; error is persistent with safe retry; success follows authoritative commit; disabled action states a reason. Long/missing/invalid values are rendered according to typed schema, never guessed.

## APIs, events, jobs and consistency

preferences/alerts contract; offer.changed producer; notification.requested;5min batch evaluation and expiry jobs. Named endpoints refer to the [proposed OpenAPI](../../09-api-integrations/openapi.yaml); internal commands are not public endpoint promises. Events use the shared versioned envelope and at-least-once delivery. Dedup(subscription,trigger_revision,channel);24h cooldown proposal; no send after expiry or opt-out; provider timeout reconcile before retry. Jobs inherit lease, retry, expiry, poison queue and replay policy in [job contracts](../jobs-notifications-and-observability.md). Permanent validation/permission failures do not retry blindly.

## Privacy, SEO, failures and lifecycle

Recipient reference private; proposed90day expiry; verification tokens hashed/short-lived; unsubscribe suppresses queued sends immediately; retain minimal suppression record under approved policy. Noindex management routes; descriptive labels/live confirmation. Disable stops new alerts/cancels optional queued sends; keep opt-outs; no abandoned promised transactional notice. Enable validates dependencies/schema and builds any reconstructable indexes before advertising availability. Provider errors expose a safe status and correlation reference; they do not grant permission or fabricate data. Migration keeps native IDs and source revisions; uninstall follows approved export/retention checks, never deletes unrelated WP media or commerce records. Retention values are proposals pending DEC-07.

Observability: counts of accepted/rejected commands, conflict/error codes, stale projections, job age and suppressed effects; no raw private payloads in logs. Alert on repeated provider errors or inconsistent source revisions; owner is module maintainer with SRE escalation. Financial/fulfillment obligations remain serviceable under kernel service mode.

## Acceptance and limitations

TEST-U046: Duplicate stock events cause at most one intended notification; unsubscribe between enqueue/send suppresses; stock falls again before send suppresses; foreign list read denied. Verify a second site's same numeric product IDs cannot access or contaminate the first site's data. Required evidence: API/UI results, role-denial evidence, before/after record counts and correlation-linked job outcomes, using synthetic fixtures. Status: NOT EXECUTED; documentation only.

Known limitation: Alert is opportunity notice, not price reservation or stock guarantee.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
