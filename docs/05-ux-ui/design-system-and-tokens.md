# Design system and token catalog

Document ID: DOC-U-03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: Design System Designer
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U010; FR-U011; FR-U012 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: SRC-U001; SRC-U002; USER-BRIEF sections 6.2 and 9; [source register](../00-governance/sources.csv)

## Original provisional design

FR-U010 defines an original, calm retail system. All values below are design proposals awaiting human approval and contrast/rendering tests. The neutral preset is the baseline. Values are semantic; business logic never branches on a palette name. The [settings inventory](settings-inventory.csv) is the authoritative editable-control catalog; this document defines initial values and constraints.

| Token family | Proposed values | Rules |
|---|---|---|
| Neutral surfaces | canvas #F6F7F9; surface #FFFFFF; elevated #FFFFFF; subtle #EEF1F4 | Surface changes do not convey status alone |
| Text | primary #17212B; secondary #4A5968; inverse #FFFFFF; muted #596777 | Disabled content remains readable and carries state semantics |
| Brand/action | neutral #234D70; outdoor #245746; smart-home #284F9E | On-brand text white; verify chosen pair before publish |
| Status | success #17663A; warning #704C00; danger #A12622; info #174E83 | Pair icon/text with color; pale surface variants distinct from text |
| Border/focus | border #C7CFD8; strong #718091; focus #005FCC | Focus outline 3px and 2px offset; no user control to remove it |
| Type size | 12,14,16,18,20,24,32,40,48px equivalent rem values | Body 16/1.6; UI 14/1.5; h1 32–40/1.25; legal help never below14 |
| Type weights | regular400, medium500, semibold600, bold700 | Only weights supplied/licensed by chosen font are offered |
| Space | 0,4,8,12,16,24,32,48,64px equivalent rem | Logical margin/padding; vertical rhythm not arbitrary negatives |
| Radius | none0; small4; control8; card12; pill999px | Pill limited to badges/chips, not every panel |
| Border | 1px normal;2px selected | Selection also marked text/checked state |
| Shadow | none; subtle 0 2px 8px rgba(23,33,43,.08); dialog 0 12px 36px rgba(23,33,43,.20) | No shadow-only boundaries for essential controls |
| Motion | fast120ms; standard180ms; slow240ms; ease cubic-bezier(.2,0,0,1) | Reduced motion removes movement; instant state change retains meaning |
| Layers | content0; sticky20; drawer40; modal60; toast80 | One modal at a time; toasts never hide focused controls |
| Targets | design goal44×44px; gap8px | Dense presentation reduces whitespace, never interactive hit area |
| Container | content1280px; wide1440px; text720px; viewport gutters16/24/32px | No horizontal page scroll at320px; intentional table viewport labelled |

## Typography and media

Default Latin/Persian stack uses system fonts to avoid an unlicensed download dependency. A locally hosted Persian/Latin font may replace it only with a recorded license, glyph/weight coverage, file-size budget and fallback metrics. Font loading must not make text invisible. Model identifiers such as `SH-220-A` use a readable tabular numeral option where available; no currency amount is formatted through string reversal. All displayed prices come from a locale-aware formatter and are semantically isolated in RTL.

Product images default to a1:1 contained frame so product edges are not cut off. Editorial images allow4:3 or16:9 crop with focal point. Hero16:9desktop and4:3mobile are separate approved renditions, not a guarantee that arbitrary art survives crop. Missing images use a neutral labelled silhouette, not an unrelated stock photograph. Decorative textures have empty alternative text; informative charts receive textual equivalents.

## Density and responsive grid

Comfortable card padding16px, title16px and row gap24px; dense card padding12px, title14px and gap16px. At <480px use2cards only when each can remain at least144px wide; otherwise1. At480–767 use2,768–1023 use3,1024–1279 use4 and≥1280 use5 in the wide catalog without a sidebar; an open facet sidebar reduces columns by1. Merchant controls may select a lower column count, not a smaller minimum card width. PDP uses media/details/purchase columns above1200px; media/details above768px; linear media→title→options→offers→purchase below768px.

## Token scope and acceptance

Global tokens map into native styles where supported. Component tokens reference these values, so one accessible button contract survives all presets. Interactive-state controls are constrained to approved text/background pairs; custom arbitrary CSS/HTML/script is outside the control catalog. Contrast, zoom, localization and sticky-overlap checks are release gates (TEST-U010,U011,U012), not claims that the values were browser-tested.
