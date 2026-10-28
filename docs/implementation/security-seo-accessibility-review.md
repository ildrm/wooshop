# Bounded review and open findings

This is a model engineering review with actual local checks, not a human security audit, penetration test, finance signoff or accessibility conformance report.

| Finding | Correction/evidence | Remaining acceptance |
|---|---|---|
| Ambient transaction commits leaked synthetic fixtures in early tests | Root database wrapper now preserves ambient transactions using savepoints. Exact positively identified probes were hidden/drafted; no original seed products or ledger/order history was deleted. | Broad MySQL/version/crash matrix pending. |
| Atomic stock predicate could underflow unsigned MariaDB arithmetic | Signed comparison fixed; actual20overlapping SHORTINIT database workers yield one hold and19denials. | Complete checkout/payment/provider concurrency and recovery pending. |
| Stale Woo cart product metadata could miss authority changes | Fresh supported WC CRUD re-read in marketplace native guard; direct/old-cart/Store API tests. | Abrupt plugin loss still requires independent operational sales stop/recovery. |
| Queue replay could change intent; lease-loss completion could misreport success | Canonical actor/payload/class conflict rejected; completion counters follow lease-conditioned writes. | Provider ambiguity, outbox, replay UI and complete poison/reconciliation acceptance unfinished. |
| Seller read capability implication and GET null-body REST defect | Narrow capability implications corrected; optional body cast fixed; real WP regression. | Full adversarial authorization/API contract matrix pending. |
| A module flag could have reported disabled while callbacks still ran | Other module transitions explicitly return501. Active wishlist/comparison/history slice adds real service/UI/API gates and preserves cleanup rights. | Full lifecycle for all26modules, dependencies and obligation drain remains incomplete. |
| Native Woo template produced nested main landmark | Neutral theme wrapper templates preserve one main on tested product/cart/checkout/archive surfaces. | Complete semantic/manual assistive technology review pending. |
| Mobile icon links lacked accessible names; hero route stale | Sign in/Cart labels added; owned default hero resolves actual native shop page while preserving edits. Browser keyboard/mobile checks executed. | Full WCAG2.2AA, contrast/state,200–400%zoom, fluent Persian and screen reader tasks NOT RUN. |
| Login failure artifact contained synthetic fixture secret | Sensitive artifact removed; credentials rotated; sessions invalidated; login waits for native scripts and verifies username. | Comprehensive secret tooling beyond exact values/key patterns remains pending. |
| HPOS declared but initial native tests used legacy data store | Evidence corrected; later supported native migration and HPOS continuity passed on all three installations. | Full provider/product/version compatibility remains unverified; see assurance-followup.json. |
| Dependency advisory query failed | Official npm audit returned ECONNRESET; retained as BLOCKED. | No zero-vulnerability claim. WP/WC/container/Chrome vulnerability and support review incomplete. |

Secrets and file paths are not returned by custom diagnostics. Privileged native REST tests use actual cookie login and `X-WP-Nonce`; missing nonce fails. Team invitations and privileged membership changes require a trusted HumanVerifier, absent in the normal production construction; tests inject doubles only. Finance approval remains unavailable without independent authenticated step-up/provider evidence. TLS/MFA-provider, production edge/CORS and full privacy-retention/legal behavior remain unverified or blocked.

SEO currently has bounded plugin behavior, native ownership and incomplete indexation policy. No broad single-renderer/canonical/facet/pagination/schema/feed/hreflang acceptance is claimed. Field traffic, Search Console, Merchant Center, remote feeds, rankings and conversion uplift are NOT RUN. Local `blog_public=0` is search discouragement; loopback binding is the actual local network boundary.

Original theme artwork and synthetic media are labeled. Captured screenshots are observations, not an approved visual baseline. Human visual approval, real domain claim review and actual research participants remain pending. The requirements matrix retains those gaps; passing a finite keyboard test does not make every screen accessible.
