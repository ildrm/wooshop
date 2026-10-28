# Support, upgrades and end of life

Document ID: DOC-S-OPS05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; operational validation NOT RUN
Owner: Release/Maintenance Owner | Reviewers: DevOps/SRE Engineer; QA/Test Engineer; Release/Maintenance Owner
Scope: Support, upgrades and end of life | Requirements: FR-S005; NFR-S007; NFR-S012
ADRs: ADR-07; ADR-09; ADR-11; ADR-14; ADR-16
Dependencies: [version baseline](../00-governance/version-baseline.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; [source register](../00-governance/sources.csv)

## Support boundary

FR-S005: Publish a versioned support matrix for theme, companion plugin, WordPress/WooCommerce/PHP/database, browsers and every approved provider adapter. The [baseline](../00-governance/version-baseline.csv) is proposed; no runtime test is marked passed here. Support initially covers the current minor and preceding minor of the custom products for90days after replacement, contingent on upstream security support. Security fixes can require a faster minimum upgrade with a documented migration path; do not promise unsupported upstream versions remain safe.

Support intake records site, release/config versions, role, product capability, event/correlation ID and minimal reproducible actions. Redact personal data, tokens, payment details and private seller documents. First-line Support uses documented diagnosis and escalation; only authorized engineers access production. Customer-facing response targets are provisional: service-wide purchase failure15minute operational acknowledgement; urgent order/payment issue4business hours; ordinary catalog/account issue1business day. A staffed coverage plan must approve these before launch.

## Upgrade and contract lifecycle

Semantic versioning is proposed for custom artifacts and public API schemas. Additive optional fields are compatible within v1; removing/renaming semantics, changing money interpretation, authorization scope or required fields requires a new major contract. Announce deprecation in release notes and diagnostics with minimum90day migration period unless critical security risk requires immediate disablement. Provider changes can force shorter windows; capture the evidence and customer impact rather than assuming vendor stability.

Before upgrading, administrators read impact notes, validate dependency gates, backup/rehearse, confirm outstanding obligations and schedule the window. Theme presets do not overwrite saved user customizations. Configuration migrations preserve old versions for audit and provide preview; invalid settings use safe defaults with visible diagnostics. Historical order snapshots remain readable even when the originating module is disabled.

## End of life and exit

An adapter/module retirement plan lists data owner/export schema, replacement, outstanding orders/returns/subscriptions/bookings, notification commitments, financial reconciliation, credentials, retention and support end date. Stop new activity first, drain or transfer obligations through verified mapping, reconcile and independently approve closure, revoke provider credentials, then archive/delete only under retention policy. Disable does not cancel subscriptions or refund customers automatically. Users are shown available support and status, not misleading success.

For two sites, rollout and support states are independent; one site's failure does not force untested upgrades on the other. Shared package defects trigger impact assessment for both, using separate logs and customer data. Documentation maintenance follows [guide](../16-guides/documentation-maintenance-and-change-log.md); every release updates compatibility, known limitations, migration and runbooks with actual evidence. Paid dependency renewal failures produce advance diagnostics and safe degraded behavior, not surprise abandonment of obligations.
