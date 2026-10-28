# Accessibility, localization and visual tests

Document ID: DOC-S-QA03 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval; runtime verification NOT RUN
Owner: Accessibility Specialist | Reviewers: QA/Test Engineer; Solution/Software Architect; DevOps/SRE Engineer
Scope: Accessibility, localization and visual tests | Requirements: NFR-S001; NFR-S006
ADRs: ADR-04; ADR-07; ADR-09; ADR-11; ADR-14; ADR-16 | Dependencies: [test cases](../12-quality/test-cases.csv); [version baseline](../00-governance/version-baseline.csv)
Assumptions: ASM-01; ASM-02; ASM-03 | Sources: USER-BRIEF sections9 and12; SRC-S002; [source register](../00-governance/sources.csv)

## Target and test procedure

NFR-S001 proposes WCAG2.2AA for storefront, account, seller and administrator custom interfaces. [WCAG2.2 Recommendation](https://www.w3.org/TR/2024/REC-WCAG22-20241212/) (SRC-S002) is the verified reference. Scanners find some defects but cannot establish conformance. Test complete tasks with keyboard and representative screen-reader/browser combinations from the release baseline; users with assistive technology participate in future validation.

| Task | Observable acceptance and evidence |
|---|---|
| Header/mega menu/mobile drawer | Tab order matches reading order; enter/space opens; escape closes and returns focus; no hover dependency; names/states announced |
| Product variant and seller offer | Availability change announced once politely; unavailable options explained; selected seller price/warranty accessible; no silent switch |
| Cart/checkout/errors | Persistent labels and autocomplete where suitable; error summary links to fields; entered values preserved; payment retry doesn't submit twice |
| Modal/quick view/gallery | Named dialog; focus contained only while modal; close restores trigger; thumbnails keyboard accessible; useful image alt |
| Promotions/carousels | Pause offered for moving content; no forced rotation on focus; reduced-motion respected; countdown truthful and not per-second screen-reader noise |
| Sticky bars and zoom | At200% and400% no essential content/focus obscured; reflow at320CSSpx; horizontal scroll only genuinely tabular content with labels |
| Offers/compare table | Headers associated; narrow view retains label/value relationship; selection independent of color; reading order preserved |
| Captcha/authentication | Accessible alternative, password manager/paste allowed; recovery doesn't require inaccessible cognitive test |

Design targets include44px touch controls where practical; WCAG minimum exceptions are evaluated by Accessibility Specialist, not used to justify tiny purchase controls. Contrast, focus visibility, target spacing, names/roles/values, status messages and form identification are manually reviewed against relevant criteria. Visual snapshots use approved original design frames; none exist yet, so no pixel fidelity is asserted.

NFR-S006: Run every critical task in English and Persian with a long title such as «چادر کوهنوردی چهارفصل مدل Alpine-2 ظرفیت ۲ نفر», LTR SKU `SH-M2-230V`, local/Latin numerals and a price+currency string. Isolate identifiers directionally, retain minus/decimal meaning, mirror directional navigation but not logos/product photographs. Calendar choice and digit display are independent of stored UTC timestamps/currency. Pseudo-localize by50% expansion, remove a translation and verify readable fallback. User-supplied content cannot break document direction.

Evidence includes viewport, browser/AT versions, input mode, zoom, chosen locale, screenshots only of synthetic data, findings and human reviewer. Critical checkout obstruction blocks release even if an automated score is high. No runtime visual or accessibility test has been performed here.
