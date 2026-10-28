# ADR-01: Native block theme

Document ID: DOC-D-ADR-01 | Title: Native block theme | Revision: 1.0 | Date: 2026-10-28
Status: proposed; pending human approval
Owner: Solution/Software Architect | Reviewers: WooCommerce Solution Specialist; Marketplace Solution Architect; QA/Test Engineer
Scope: future architecture decision | Requirements: FR-D002; FR-D013 | ADRs: ADR-01
Dependencies: [architecture overview](../architecture-overview.md); [requirements](../../02-requirements/requirements.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: SRC-D004; DEC-10; [source register](../../00-governance/sources.csv)

## Context

Store operators need reusable presets and controllable templates without a proprietary editor.

## Decision

Use a native block theme with Site Editor, template parts, patterns, style variations and a controlled token catalog. Persistent workflows remain in the plugin. Native Styles storage and user changes retain their platform behavior.

## Alternatives

Classic theme improves compatibility with some older extensions but weakens native whole-site editing. A proprietary builder increases lock-in and duplicates platform behavior.

## Consequences and reversibility

Some complex extension layouts require accessible plugin fallback surfaces and specific block adapters. The project accepts a bounded control catalog instead of arbitrary code injection.

The decision is provisional. Changing it requires an impact review of linked requirements, data ownership, adapters, migration and tests. Historical order or ledger records are never rewritten merely to match a new architectural preference.

## Verification and decision gate

Design System Designer and WordPress Theme Developer validate saved user styles, reset and upgrade behavior; Accessibility Specialist reviews editor/front-end parity.

Acceptance tests are specified in the [test register](../../12-quality/test-cases.csv); none have been executed against an implementation. Approval requires the named human owners and a recorded decision in the governance register. Documentation consistency is not runtime evidence.
