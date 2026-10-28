# Implementation records

Delivery status: **implementation incomplete**. This directory records the software built under the current implementation request. The original documentation baseline remains under `docs/`; its historical documentation-only and NOT RUN statements describe that earlier assignment.

The running development targets are two independent, synthetic WordPress installations at `http://localhost:18880` and `http://localhost:18881`. Their databases, users, uploads, settings and identities are separate. A third package-verification installation uses port18882. All ports bind loopback; mail and external application HTTP are intercepted by the local development guard.

Start with [environment commands](environment-and-command-reference.md), [release readiness](release-readiness.md), [requirement status](requirements-implementation-matrix.csv), [work packages](work-package-status.csv) and [resume instructions](RESUME.md). `IMPLEMENTATION_STATE.json` is the machine-readable checkpoint. Worker evidence reports describe bounded executed slices; none is specialist human approval.

Implementation and verification are separate. A partial requirement can contain passing checks while its complete acceptance remains NOT RUN. The registries preserve every original requirement and work-package ID. They do not report a completion percentage. Run `rtk proxy python3 tools/traceability.py` after evidence changes to refresh the derived views.

The distributables are one native block theme and one modular companion plugin. ZIPs and their checksums are in `artifacts/releases/`, excluded from Git. They are development artifacts, not a verified release candidate. No commit, push, publication, production deployment, real seller onboarding, customer communication or money transfer has been performed.
