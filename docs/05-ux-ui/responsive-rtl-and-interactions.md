# Responsive, RTL and interaction contracts

Document ID: DOC-U-04 | Revision: 1.0 | Date: 2026-10-28
Status: drafted; pending human approval | Owner: Design System Designer
Reviewers: Accessibility Specialist; WordPress Theme Developer; Product Owner
Scope: proposed shared storefront design for independent sites, LTR and RTL
Requirements: FR-U011; FR-U012 | ADRs: ADR-01; ADR-12; ADR-15
Dependencies: [requirements](../02-requirements/requirements.csv); [settings inventory](settings-inventory.csv)
Assumptions: ASM-01; ASM-02 | Sources: USER-BRIEF sections 6 and 9; [source register](../00-governance/sources.csv)

## FR-U011 responsive rules

Breakpoints are content-based proposals at480,768,1024,1280px. Desktop is not a fixed user identity: every feature remains reachable under zoom and at320CSSpx. Use logical inline/block spacing. At200% zoom adapt to the effective layout width; at400% zoom offer linear reading and avoid two-direction page scrolling except a labelled essential data table. Sticky header collapses to one row when vertical room falls below600px; sticky purchase controls disable when the keyboard opens, a dialog opens, or their footprint would cover an error or focused control. Provide a jump to the primary purchase area.

RTL reorders inline layout according to reading direction, not the underlying transaction sequence. Back/forward chevrons mirror; play, check, product photography, brand logos and currency glyphs do not. Mixed title example: “حسگر درب SH-220، نسخه 2”. Isolate `SH-220` and the number/currency unit; preserve exact SKU in copying and search. Numeral presentation may be localized, but incoming numeric values are normalized before validation and stored independently of display. Dates carry locale/calendar and timezone labels; order deadlines remain unambiguous. English/Persian are examples, not jurisdiction selection.

## FR-U012 shared interaction table

| Interaction | Input/focus contract | States and recovery |
|---|---|---|
| Menu disclosure | Button has expanded/controls state; click/Enter/Space toggle; Escape closes; Tab visits links | Closing returns to opener; absent category children remain direct link |
| Modal/drawer | Named dialog, initial focus on heading or first useful control; trap focus; inert background; Escape unless a destructive operation is committing | Restore opener, or logical heading if opener disappeared; errors stay in dialog |
| Search suggestions | Combobox with listbox and active descendant; Up/Down navigate; Escape dismisses; Enter selects active suggestion or searches | Slow request retains submitted query; cancelled requests cannot overwrite newer results |
| Carousel | Manual advance by default; named previous/next controls; optional rotation has visible pause and stops on focus/hover | Reduced motion disables animation; cards remain reachable as ordinary links |
| Gallery/zoom | Thumbnail buttons announce image position; zoom opens titled dialog; close control always visible | No swipe-only action; failed image retains alt/caption; video captions and pause |
| Facet filter | Checkbox/range form; explicit Apply on small screens; active chips are remove buttons with values | Keep focus near changed filter; announce result count once after settled response |
| Variant swatch | Radio-group semantics with visible value labels and unavailable explanation | Changes announce resulting availability/price once; selected unavailable option is never silently replaced |
| Offer selector | Radio or labelled selection action; seller/condition/warranty/delivery visible together | Expired offer remains identified with reselect action; no default seller substitution |
| Async form | Busy indication only on affected region; prevent duplicate submission via server idempotency | Summary links to inline errors; after success focus next task, never clear unsaved fields on error |
| Toast | Informational status region, no focus theft | Financial/permission errors use persistent inline message instead of disappearing toast |

## Tables and long content

Comparison/offer tables keep row headers and meaningful column headers. Under768px default to stacked product/offer cards with a selected comparison criterion, preserving a link to the full horizontally scrollable table. The table region is keyboard scrollable, named and shows an overflow hint. Pinning one header must not hide content at400% zoom. Product titles wrap; truncation on a card is at most3lines and full text remains in the accessible product link, never only a tooltip. Full PDP titles are not clamped. Values longer than80characters wrap or receive a safe breaking opportunity; tables never force the whole viewport wider.

## Connectivity and recovery

Offline/interrupted add-to-cart cannot display confirmed success; retain selection locally and label “Not added—retry when connected”. Do not cache payment submissions for automatic replay. Cart refresh requests can retry safely but server responses remain authoritative. A page returning from an external gateway polls/refreshes known order state, not a URL query assertion of payment. Analytics count committed selections/actions rather than every intermediate screen-reader event. TEST-U011 and TEST-U012 include keyboard-only checkout, RTL long titles, missing assets and all above overlays.
