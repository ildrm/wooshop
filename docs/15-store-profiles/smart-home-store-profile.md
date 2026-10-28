# Smart home automation store profile

Document ID: DOC-U-21 | Revision:1.0 | Date:2026-10-28
Status: drafted; pending domain/business approval | Owner: Smart Home Product Specialist
Reviewers: Product Catalog/Merchandising Specialist; Solution/Software Architect; QA/Test Engineer
Scope: configurable site profile without shared application logic fork
Requirements: FR-U032; FR-U034 | ADRs: ADR-03; ADR-04; ADR-14; ADR-15
Dependencies: [specifications module](../07-plugin/modules/product-specifications.md); [presets](../06-theme/presets-and-store-branding.md)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF section6.5; [source register](../00-governance/sources.csv)

## Profile composition (FR-U032)

Profile key `smart-home`, schema revision1. Use Smart Home preset with independent catalog/customers/order/provider configuration. Typed specifications, facets, compatibility evidence, relationships, manuals and comparison are core. Native types remain available; solution bundles and installation use named adapters only after capability/territory/payment tests. Marketplace complex bundles/bookings are disabled until their contracts pass. There is no hardcoded smart-home branch in shared theme/plugin.

## Technical schema

Every technical assertion includes exact manufacturer/model/hardware revision, source URI/document digest, source date, reviewed date, reviewer role, applicable region and confidence status. Evidence validity is scoped; one matching label does not establish interoperability. Source URI retrieval is not an unrestricted server fetch.

| Stable field | Type/constraint | Required scope and display |
|---|---|---|
| smart.model | manufacturer/model strings≤120, hardware_revision≤80 | all devices; exact identifiers, bidirectionally isolated |
| smart.protocols | typed list protocol/version/radio-band/security mode where supplied | connectivity products; Matter, Thread, Zigbee and Wi-Fi stored separately |
| smart.ecosystems | list ecosystem/integration_method/evidence_ref/conditions | only claimed platform support; no inference from protocol |
| smart.region | controlled country/region identifiers plus certification scope | electrical/radio variants; business operating regions separately chosen |
| smart.electrical | voltage min/max decimalsV; frequencyHz; rated powerW/currentA; plug/base/neutral-wire requirement | mains products; display exact source context, unknown blocks claim |
| smart.power | mains/battery/USB/other; battery type/count; supply included flag | device power facts; no assumed supply compatibility |
| smart.hub | required bool/unknown; compatible controller models; border-router requirement separate | context-specific; unknown never false; required accessory prominently disclosed |
| smart.installation | skill/qualified installer requirement; mounting/wiring/environment; territory service rule | relevant product/service; reviewed guidance, not invented electrical procedure |
| smart.firmware | installed range if known; minimum compatible version; update mechanism | claims depending on firmware; version comparator defined by manufacturer |
| smart.support | support end date nullable; manufacturer policy URI/review date | date unknown shown unknown; no perpetual support promise |
| smart.compatibility | evidence-backed rule ID, condition set, result enum | system matching; exact model/region/firmware required before verified result |
| smart.manuals | approved attachments with language/model/revision/access role | installation/operation guides; source licensing preserved |
| smart.accessories | typed relations required/optional/complement | hub/power/controller accessories require evidence to label required-compatible |
| smart.solution_bundle | provider config components, dependency graph, region and return terms | supported configured products only; no label-only automatic kit generation |
| smart.service | installation provider, territory, prerequisites, slot/timezone/cancel terms | optional unselected service; provider gate |
| smart.warranty | seller offer policy/version/duration/exclusions | seller-specific commercial terms separate from hardware facts |

Decimal measurements allow6fraction digits; electrical ranges require min≤max, nonnegative physical values and applicable units. Firmware ranges cannot be compared lexicographically unless that provider's version scheme permits it. Source/protocol fields are not marketing tags. Facets offer protocols, evidence-backed ecosystem, power source, supported region and device category. Filtering by Matter never silently selects Thread or a particular platform.

## Worked synthetic compatibility display

Synthetic “SH-220-A sensor”, hardware2, requires hubH-10 in regionR1 with firmware≥2.4 under evidenceE-17, reviewed on a stated date. Result: conditional until those fields are supplied; if evidence is revoked, result becomes unknown and related confirmed-compatibility claims disappear. This is fixture data, not an actual device endorsement. UI labels “Requires a compatible hub” and links the evidence scope. An optional installation service starts unselected; location and slot are checked before a committed quote.

## Editorial and operational review

Smart Home Product Specialist checks exact version/region dependencies and manufacturer support policy; qualified legal/technical reviewers approve electrical/safety claims under DEC-08. A seller may submit evidence, not publish canonical certification or global compatibility directly. Firmware/source changes enqueue review of affected product pairs; conflicting evidence quarantines the result rather than selecting the convenient answer. Support can inspect the purchased model/configuration/evidence snapshot without seeing unrelated seller/customer data.

TEST-U032 verifies distinct protocol/ecosystem facets, required-hub warning, unknown firmware handling, wrong-region denial, incompatible electrical configuration blocking, component/installation price visibility and independent site export. TEST-U034 verifies evidence expiry invalidates cache and does not rewrite purchased snapshot.
