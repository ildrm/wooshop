# Supply chain, secure development and updates

Document ID: DOC-S-SEC06 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; implementation verification NOT RUN
Owner: Release/Maintenance Owner | Reviewers: Application Security Engineer; Privacy Specialist; QA/Test Engineer
Scope: Supply chain, secure development and updates | Requirements: SEC-S015
ADRs: ADR-03; ADR-09; ADR-14; ADR-16 | Dependencies: [authorization matrix](roles-capabilities-and-object-authorization.csv); [control matrix](controls-and-verification-matrix.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section11; SRC-S001; SRC-S011–SRC-S013; [source register](../00-governance/sources.csv)

## Procurement and engineering contract

SEC-S015: Every dependency has a named owner, source, license, version range, vulnerability/support policy, cost category and exit route in the integration catalog. Before adding a package, review whether WordPress/WooCommerce already owns the function; additional code increases upgrade and security obligations. Theme/plugin releases have separately versioned artifacts, checksums, release notes and an SBOM. No repository configuration, package, build or workflow is created by this documentation assignment.

Future development uses protected review, minimal build credentials, reproducible dependency resolution where supported, static analysis, secret scanning and isolated tests on synthetic fixtures. Third-party code and generated changes receive human review of authorization, money, upload and migration paths. Secrets belong to an approved secret store/environment boundary, not source, export bundles, screenshots or diagnostic archives. Production source editing is disabled by operating policy; operators deploy only reviewed release artifacts.

## Update distribution

The future update client accepts only the approved HTTPS origin and verifies release integrity according to the chosen distribution mechanism. A valid network connection alone is not artifact provenance. Signing, key custody/rotation, compromise revocation and recovery need a reviewed implementation contract before first release. An emergency key change uses a separately authenticated channel under Release/Security ownership, not a remotely supplied arbitrary update URL. Store administrators cannot point production updater at a seller-supplied endpoint.

Each release gate checks the canonical [version baseline](../00-governance/version-baseline.csv), required provider adapter evidence, data migration preflight, reversibility class and operational obligations. A dependency's declared compatibility is recorded separately from our future test result. Do not enable HPOS/blocks/provider claims simply because an interface exists.

## Vulnerability handling

Proposed remediation targets after confirmed assessment: actively exploited/critical exposure mitigate within24hours and patch target72hours; high within7days; medium30days; low90days. These are project targets requiring support staffing, not promises about upstream fixes. Critical/high exploitable findings block normal release; emergency mitigation may disable the affected capability while retaining refunds, settlements and evidence. An exception requires documented residual risk, compensating controls, expiry and named accountable owner; no silent acceptance by timeout.

License review DEC-12 covers GPL obligations, paid extensions, fonts/media, hosted services and redistribution. Procurement may purchase a license only in future authorized work; none is assumed bundled. EOL policy provides export and obligation servicing before retiring an adapter. A compromised dependency triggers quarantine, scope assessment, credential rotation, artifact replacement and reconciliation of affected business events, followed by an incident record.
