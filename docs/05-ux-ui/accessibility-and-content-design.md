# Accessibility and content design

Document ID: DOC-U-05 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: Accessibility Specialist
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U012; FR-U013 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 9 and 12; assurance source register for WCAG2.2; [source register](../00-governance/sources.csv)

## Target and responsibility

FR-U012 and FR-U013 propose WCAG2.2AA conformance as an implementation objective, subject to the assurance strategy. A design checklist or automated scan does not establish conformance. Accessibility Specialist owns manual evidence for navigation, checkout, account recovery, seller finance and error states. Each component spec names semantics, focus and state behavior; third-party widgets must pass the same task checks before activation.

## Language and content contract

Use plain action labels: “Add to cart”, “Choose size”, “View supplier”, “Request return”; do not call an outbound affiliate link an add-to-cart action. Show price ranges only when a supported purchasable range exists; “Price unavailable” is not0. “From” identifies the actual eligible minimum and selection conditions. Shipping estimates state destination assumptions and are not guarantees. Warranty text names the responsible seller/provider and links the applicable terms snapshot. Product and seller ratings have separate labels, denominators and moderation policies.

Error structure: what happened, what remains unchanged, how to recover. Example: “This seller’s price changed. Review the new total before continuing.” Persian example: “قیمت این فروشنده تغییر کرده است. پیش از ادامه، مبلغ جدید را بررسی کنید.” No blame, raw exception, unexplained code or secret appears. A correlation reference may be copied for support. Form errors refer to visible labels, identify the invalid value without repeating sensitive data, and retain safe input. Required fields are identified with text as well as an asterisk.

## Detailed acceptance rules

| Concern | Proposed rule | Evidence required |
|---|---|---|
| Reading structure | One page-level title; hierarchical headings; landmarks and skip link | Screen-reader outline and keyboard recording |
| Controls | Text or accessible name agrees with visible label; icons labelled; unavailable reason programmatically associated | Voice-control and screen-reader checks |
| Contrast | Text4.5:1 normal/3:1large; essential UI3:1; check all theme/editor state pairs | Recorded contrast calculations and rendered inspection |
| Motion | No forced parallax; manually controlled carousel; reduced motion honored | OS preference test and keyboard check |
| Errors | Error summary + field descriptions; focus summary after failed submit, not every keystroke | Failed checkout/registration recordings |
| Live updates | Polite settled result/variation announcement; avoid repeated countdown announcements | Screen-reader test at slow and fast input speeds |
| Media | Purposeful alt text; captions/transcripts for product instruction video; media rights/provenance | Editorial checklist, caption accuracy review |
| Authentication | Allow password managers and paste; recovery not dependent on puzzles alone | Login/recovery task trial with assistive technology |
| Dense layout | Target goal44×44; essential information not hidden in hover; table alternatives |320px/400%zoom inspection and touch task trial |

The numeric contrast requirements are a target mapping, to be verified against the current W3C normative reference by the assurance owner. Decorative seller badges cannot look like independent certifications. Safety/compatibility claims require source, scope, revision and review date; a missing source removes the claim instead of generating wording. Countdown copy derives from the real campaign end instant and becomes an ordinary “Offer ended” state after expiry.

## Editorial workflow

Catalog editor drafts → domain specialist reviews technical facts → moderator reviews prohibited claims → publisher approves version. Translations preserve model identifiers, quantity units and policy meaning; a second fluent reviewer checks Persian layouts and mixed direction. Alt text describes the actual product and image purpose, not keyword lists. Seller submissions do not automatically change canonical descriptions. Long, missing and invalid content examples are specified in screen/component catalogs. TEST-U013 covers approved/rejected safety claims, untranslated strings, malformed values and error-message clarity.
