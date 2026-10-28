# Deployment and site isolation

Document ID: DOC-D-DEPLOYMENT | Title: Deployment and site isolation | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval and future implementation verification
Owner: DevOps/SRE Engineer | Reviewers: WooCommerce Solution Specialist; QA/Test Engineer; Application Security Engineer
Scope: proposed system design only | Requirements: FR-D001; FR-D011; FR-D012 | ADRs: ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [source register](../00-governance/sources.csv)
Assumptions: ASM-01 independent sites; ASM-02 jurisdiction/providers undecided; ASM-03 planning scenarios
Sources: USER-BRIEF sections 2–8; SRC-D001–SRC-D009

## Baseline

Each store runs its own WordPress installation, WooCommerce database, media bucket/path, cache namespace, cron/job credentials, payment account configuration and backup set. Shared released theme/plugin artifacts are read-only inputs. There is no cross-site customer login, cart, seller organization, inventory, catalog identity or payout pool. Matching numeric WooCommerce IDs on two sites represent unrelated objects.

A site-local immutable site identifier accompanies external mapping keys, event IDs and administrative exports. Internal URLs are generated from each site's configured origin. Notifications use that site's branding and policies. A configuration export contains preset, schemas and non-secret module choices; it excludes customers, orders, seller verification files, credentials and environment endpoints unless explicitly selected in a separate controlled migration format.

## Deployment responsibilities

| Stage | Purpose and data | Entry/exit condition |
|---|---|---|
| Development | Synthetic fixtures, developer-specific credentials | Builds are reproducible in future engineering work |
| Staging per site | Sanitized representative data, sandbox providers, no real sends | Version matrix, migration, reconciliation and accessibility tests pass |
| Production per site | Real operations after legal/provider launch gates | Approved release and recoverable backup; monitoring staffed |
| Recovery environment | Isolated restore; outgoing jobs and providers disabled | Finance reconciliation before reopening writes |

Release coordination allows different rollout dates, but supported contracts must overlap. A profile export declaring a newer schema is rejected by an older plugin; partial application is not allowed. Preview renders against temporary settings, never production customer data.

## Multisite alternative

[ADR-14](adr/adr-14-independent-sites.md) evaluates lower shared administration against correlated outage/update risk and complicated identity isolation. Multisite is not the baseline. A future change requires explicit cross-site identity requirements, separate provider/account analysis, data-ownership ADR and migration tests. It cannot silently redefine these independent businesses as one tenant pool.

## Acceptance and recovery

FR-D011 attempts use of one site's session, webhook, export token and seller ID against the other site; all fail without revealing whether the target object exists. FR-D012 exports Outdoor configuration and imports it into a clean third installation: token/profile/schema choices preview successfully, while secrets and transaction records are absent and missing media receives placeholders. Restore drills check source-site identity, disable outgoing money/notification actions, reconcile provider transaction watermarks, then reopen traffic. Copying a database to staging without sanitization and send suppression fails the release gate.
