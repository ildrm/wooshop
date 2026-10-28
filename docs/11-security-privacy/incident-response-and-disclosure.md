# Incident response and disclosure

Document ID: DOC-S-SEC07 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; implementation verification NOT RUN
Owner: Application Security Engineer | Reviewers: Application Security Engineer; Privacy Specialist; QA/Test Engineer
Scope: Incident response and disclosure | Requirements: SEC-S016
ADRs: ADR-03; ADR-09; ADR-14; ADR-16 | Dependencies: [authorization matrix](roles-capabilities-and-object-authorization.csv); [control matrix](controls-and-verification-matrix.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section11; SRC-S001; SRC-S011–SRC-S013; [source register](../00-governance/sources.csv)

## Triage and command

SEC-S016: The future incident commander is the on-call SRE for availability incidents and Security Engineer for suspected compromise, with one commander recorded per incident. Finance leads monetary reconciliation; Privacy/Legal determine notification duties and approved wording. Support is the communication owner but does not invent cause or legal deadlines. Contact roster and backup coverage are a phase9 prerequisite; this assignment sends no messages.

| Severity | Example | Proposed response |
|---|---|---|
| SEV1 | Cross-seller data leak, unauthorized payout, compromised release, checkout broadly down | Page immediately; acknowledge15minutes; commander and finance/security bridge; updates every30minutes |
| SEV2 | Single provider unavailable, queue threatens settlement deadlines, isolated account takeover | Acknowledge1hour; scope and mitigate; hourly internal status |
| SEV3 | Contained low-impact bug with workaround | Next business-day triage and assigned fix |

## Response sequence

1. Record first observation, correlation/release/site IDs and affected capability. Preserve original timestamps; avoid copying personal data into chat/tickets.
2. Contain narrowly: revoke compromised sessions/credentials, disable new seller sales or payout submission, preserve read access and lawful refund/fulfillment servicing. Never delete ledger or logs to hide an incident.
3. Preserve evidence in restricted storage with hash, access trail, collector and retention/hold basis. Snapshot configuration and event IDs; do not place secrets in the incident summary.
4. Determine exposure and monetary effects from authoritative sources. Reconcile provider transactions and local allocations before any replay. Treat unknown provider outcomes as pending investigation.
5. Repair with reviewed artifact or configuration. Restore from backup only under the recovery runbook; first isolate and replay deletion tombstones and missing transaction evidence. Avoid repeating captured payments or erasing newer orders.
6. Verify containment, authorization boundaries, ledger conservation and operational backlog with independent reviewer. Commander approves recovery with Finance for monetary impact.
7. Legal/Privacy decide required notifications based on actual territories and facts, retain decisions and timing. Support publishes only approved customer-facing facts.
8. Within5business days, review cause, timeline, response gaps and corrective work packages; remove temporary privileges and track remediation to closure.

## Disclosure

Publish a monitored security contact and supported release policy at future launch. Accept reproducible reports with minimal proof and no real customer data. Acknowledge target2business days; provide initial triage target5days; agree coordinated disclosure based on severity and remediation. Do not promise blanket legal safe harbor without counsel approval. Reports remain restricted; credit is optional and consensual. Escalate critical evidence immediately even if incomplete. All targets are proposed and require staffing acceptance.

Future exercises include leaked seller export, duplicated payout callback, malicious theme update and loss of primary database. Evidence required: timeline, containment commands reviewed in advance, scope metrics, restored invariants and signed operational review. No attack or incident exercise was executed here.
