# Product media and evidence assets

Document ID: DOC-U-M025 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending human approval | Owner: WordPress Plugin/PHP Developer
Reviewers: Marketplace Backend Engineer; Application Security Engineer; QA/Test Engineer
Scope: MOD-U025, both independent stores and generic profiles
Requirements: FR-U065 | ADRs: ADR-02; ADR-03; ADR-04; ADR-16
Dependencies: [kernel](../plugin-kernel-and-module-lifecycle.md); [module catalog](../module-catalog.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.3; [evidence register](../../00-governance/sources.csv)

## Purpose, users and boundaries

Extend WP media with typed product media roles, safe optional360display and source/licensing governance. Fulfillment approach: custom plugin module integrating the native/provider owner below; the theme owns presentation only. Dependencies/conflicts: WP media authoritative; approved video/360adapter; scanning and content moderation. No duplicate stock, coupon, review, schema or analytics authority is introduced.

## Authoritative data, settings and authorization

Module extension fields: attachment_id WP; owner org/user; role gallery/manual/size-guide/video/spin/evidence; MIME/bytes/dimensions/duration; alt/caption/transcript; license/source; review state; focal point; product association. All extension records also have site-local UUID, schema version, immutable creation instant, update instant, actor audit reference and optimistic revision. Required fields are non-null unless optional; native IDs retain native identity; public projections omit private references. This logical model is not a table-per-entity implementation prescription.

Business settings (key:type:default:valid range): `media.max_image_mb:integer:10:1..20;media.max_document_mb:integer:20:1..50;media.spin_enabled:boolean:false:true-after-adapter-review`. The [settings inventory](../../05-ux-ui/settings-inventory.csv) owns validation/edit/preview/export rules. Permission: native upload permission plus org ownership/content limits; reviewer approves public attachment; protected manuals/assets use entitlement policy. Server mutation and queued execution check current resource scope; caller-supplied owner, approval, finance and site fields cannot bypass policy.

## Workflow and UI rules

Upload quarantine → verify actual content/MIME/dimensions and malware policy → strip unnecessary metadata → review rights/claims → publish role-specific renditions. RawSVG/custom font/archive uploads disallowed unless approved sanitization path.360requires keyboard controls and still-image fallback; manual links labelled filetype/size.

UI: CMP-U012/U015; size guide dialog/table text equivalent; missingfile neutral fallback; captions for instructions. All states inherit [component](../../05-ux-ui/component-specifications.md) semantics, responsive/RTL/focus rules and persistent actionable errors. Loading retains safe data; success requires committed server state; unknown/missing values are labelled; disabled actions expose the reason.

## Integration, concurrency, caches and jobs

WP media upload contract plus scoped moderation extension; media processing/scan jobs; no external URL proxy without allowlist. The [OpenAPI](../../09-api-integrations/openapi.yaml) is authoritative for exposed routes; internal domain commands are not promises of additional REST endpoints. Events use versioned shared envelopes and at-least-once delivery. Attachment ownership and version lock; renditions reconstructable; replacing media invalidates public derivatives; delete referenced asset only with affected-use preview. Background work follows [job contracts](../jobs-notifications-and-observability.md): bounded leases, dedup, transient backoff, expiry policy, poison queue, replay/reconciliation and correlation metrics. Permanent authorization/validation errors need correction, not blind retry.

## Privacy, SEO, failure and lifecycle

Avoid EXIF location and privatepersondata; evidence rights respected; attachment erasure checks order/legal obligations; no blanket sharedmedia deletion. Informative alt/captions govern SEO; 360does not conceal product details from nonvisual users. Disable enhancement keeps native images/manual references; optionalprovider failure still images. Enable checks provider/schema gates before availability is advertised. Migration preserves native IDs and immutable transaction/evidence history. Uninstall exports or retains obligations under approved policy; it never silently deletes shared media or unrelated records. Retention values are provisional pending DEC-07. Provider errors produce degraded status and safe fallback, never fabricated successful outcomes.

Observe accepted/rejected commands, conflicts, stale projections, provider failures, suppressed messages and job age using correlation IDs; redact private values. Module maintainer owns incident triage with SRE/domain escalation. Financial/fulfillment duties remain in service mode until verified transfer/discharge.

## Acceptance and limits

TEST-U065: MIME spoof/archive traversal rejected; foreignseller media inaccessible; missing image layout stable; video captions present;360keyboard equivalent and fallback. Include the same IDs in a second independent site to prove isolation. Capture synthetic before/after records, role-denial responses, UI states and deduplicated job/provider results. Verification status: NOT EXECUTED; documentation only.

Limitation: No media generation/assets/Figma delivered; paid360/video provider compatibility remains gated.

Typed input/output, authorization, concurrency and error contracts are in [module commands](../module-command-contracts.md); only routes listed in the core OpenAPI are externally exposed.
