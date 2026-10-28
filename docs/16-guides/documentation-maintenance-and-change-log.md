# Documentation maintenance and change log

Document ID: DOC-S-GUIDE06 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; future workflow guide; pending human approval
Owner: Technical Writer/Trainer | Reviewers: Technical Writer/Trainer; QA/Test Engineer; Solution/Software Architect
Scope: Documentation maintenance and change log | Requirements: FR-S011
ADRs: ADR-03; ADR-04; ADR-12; ADR-14; ADR-16
Dependencies: [requirements](../02-requirements/requirements.csv); [test cases](../12-quality/test-cases.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections13–17; [source register](../00-governance/sources.csv)

## Authority and change workflow

FR-S011: Documentation is versioned with stable IDs and one canonical register per fact. Start at [README](../README.md) and [manifest](../DOCUMENTATION_MANIFEST.csv). Requirements/test IDs are never silently renumbered; retired IDs remain identifiable through change records. Sources distinguish verified external fact, user requirement, proposal, assumption and unresolved decision. Generated role reviews are analytical aids, not stakeholder approvals.

1. Open a change record describing trigger, affected requirement/ADR/module/screen/data/API IDs and accountable owner. Classify correction, additive specification, breaking contract or decision reversal.
2. Update authoritative contract first, then related examples, settings, permissions, tests, guides, work packages and trace edges. A new policy cannot be buried only in a training paragraph.
3. Revalidate material external facts against primary sources and record access date/version/limitations. An inaccessible source is a verification task, not a fabricated quote. Preserve prior evidence history when a claim changes.
4. Run documentation-only checks for local paths/anchors, unique IDs, parseable registers/contracts, reference resolution, manifest coverage and normative test/owner/phase coverage.
5. Conduct the affected analytical slice and obtain required future human decisions. Update status as drafted, internally checked, blocked by decision, pending external verification or pending human approval; never label generated material approved.
6. Record revision/date and publish the appropriate release notes/handover only in later authorized delivery work.

## Cadence and review ownership

Review versions/provider/security sources at each planned release and monthly during active implementation. Review SEO guidance and accessibility assumptions each major design/renderer change; legal applicability whenever territories, entity, fund flow, privacy purpose or product categories change. Runbook/contact/restore evidence is reviewed quarterly and after incidents. Source dates are not silently refreshed without reading the source. Release Owner assigns a substitute if an owner is unavailable; lack of owner blocks the affected gate.

## Initial change log

| Date | Revision / change | Evidence state |
|---|---|---|
|2026-10-28|1.0 proposed SEO, security, quality, operations and training specifications created from the documentation-only brief|Primary sources read where registered; no application implementation, runtime tests, user research, human approval or training performed|
|2026-10-28|SEO selector policy aligned to crawlable noindex with self canonical for offer/sort selectors; variation consolidation retains clean group canonical without noindex|Analytical consistency repair; future crawler/render tests remain NOT RUN|
|2026-10-28|Capacity/cache authority aligned across architecture and operations; usability sample aligned to8of10 per persona|Planning assumptions, not measured baselines|

Future change entries include author/reviewer role, exact affected IDs, reason, approval evidence where real, source update and compatibility impact. Do not overwrite this initial historical context with a claim that the system was delivered. Completion of this documentation package is separate from launch readiness; decision gates and future execution evidence remain explicit.
