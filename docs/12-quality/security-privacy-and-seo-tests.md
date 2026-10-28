# Security, privacy and SEO tests

Document ID: DOC-S-QA05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: QA/Test Engineer | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Security, privacy and SEO tests | Requirements: SEC-S002; SEC-S007; SEC-S010; SEC-S012; SEC-S013; SEO-S001–SEO-S011
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections12–13; ASM-03; [source register](../00-governance/sources.csv)

## Authorization matrix execution

Execute every denial in the [authorization matrix](../11-security-privacy/roles-capabilities-and-object-authorization.csv) through server-rendered forms, REST, bulk export and queued work. Change the resource ID, seller ID, site scope, writable field, membership and object state independently. A403/404 passes only if there was no data leakage, mutation, file generation, search hit, notification or metric revealing another tenant. Revoke membership after request admission and before worker/download; verify scope again. Logs are inspected with least privilege and contain no raw credentials.

## Content and privacy

Use inert synthetic payloads only in future isolated environments: script-like product text, malformed UTF-8, unsafe URL protocol, renamed executable, oversized image, infected-test-file policy fixture, PDF scanner outage, CSV formula, duplicate import identity and prohibited settings key. Expect validation or quarantine with no partial publication. Test API mass assignment on seller_id, commission rate and paid state. Review contextual escaping and prepared data access manually rather than relying solely on an attack list.

For privacy, start with no optional consent, opt in, queue recommendation/marketing events, withdraw and verify send-time enforcement. Export/erase account A while account B and a retained legal ledger record exist; output includes only A's permitted fields, erasure keeps minimal lawful economic evidence, and provider failure remains visibly pending. Restore an older backup and apply tombstones before enabling access. Test guest merge without matching accounts by guessed email.

## SEO consistency

For every [URL policy row](../10-seo/url-indexation-canonical-matrix.csv), capture status, canonical, robots header/meta, rendered H1/content, sitemap and schema owner. Verify page2 self canonical, invalid facets404, selector canonical, nonindexable account and unapproved seller, no duplicate commerce graph, and current chosen offer price/currency/availability. Feed sample parity uses0/2/3-decimal currency fixtures and a seller suspension. Search feature eligibility is reviewed against the current official source, not inferred from schema parsing alone.

## Evidence and gate

TEST-S001–TEST-S027 define atomic acceptance assertions; TEST-S100 onward contain cross-cutting critical slices. Evidence bundles include sanitized requests/responses, before/after invariant snapshots, provider event IDs hashed where necessary and reviewer disposition. Do not retain secret tokens or live customer addresses for proof. Critical/high authorization, financial or injection defects block phase8. A privacy legal decision unresolved blocks the affected launch function. SEO warnings are triaged by impact; leaked private URLs and price/schema mismatches block release, cosmetic metadata improvements can be scheduled with ownership.
