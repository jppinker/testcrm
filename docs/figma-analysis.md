# Figma design analysis

Source: [Figma design](https://www.figma.com/design/uFWn1n15D6PXhQf1HgORA1/?node-id=0-1), Page 1. Inspection used `get_metadata`, `get_design_context` and `get_screenshot` through Figma MCP. Main application frame: **Page**, node `2:5034`, 1700×1024; outer canvas frame `2:5033` is also 1700×1024. The design context generated detailed structure for `2:5034`. Screenshot confirms a CRM schedule editor: persistent left navigation, schedule heading/actions, a white schedule surface with search, month/week control, and a calendar grid. A right-side blank region is visible in the overall frame; intended contents are not specified.

## Main screen structure

- Sidebar `2:5035`: 280×1024, CRM label, collapse action, Home and Shifts navigation, bottom collapse menu. Some menu groups (user menu, admin, project selection) are present but hidden in this frame.
- Main content `2:5056`: x=310, width=1360. Breadcrumb/back row, title “График”, Publish and Save actions.
- Schedule panel `2:5068`: white rounded surface; search field, Month/Week segmented control, then the schedule.
- Schedule grid: header 48 px high; employee column 220 px; seven visible weekday columns at approximately 110.43 px each; rows 72 px high. Metadata shows 552 px list height (seven rows). Content context and screenshot show actual instances. Week/month view is depicted; there is no responsive or overflow specification.

## Visual tokens exposed in context

Values below are literal values or token fallbacks returned in design context, not inferred values. Token names are retained where the token may override its fallback.

| Category | Values available |
|---|---|
| Colors | background quaternary `#f2f3f7`; primary white; text primary `#101012`; secondary `#737378`; subtle border `#dedee3`; brand primary button `#5b7dec` with white text; brand secondary `#e8edfc` with `#1c4ae5` text; neutral hover background `#3c3c43`. Component-board names also include Cornflower, Mint-100, Lavanda-100, Brand, blue and neutral. Exact hex for every variant not returned in inspected context. |
| Typography | Manrope; examples: regular, medium, semibold. Font sizes: S 16 px, M 20 px, XL 32 px. Line heights: M 24 px, L 24 px, 2XL 32 px. Tracking: M 0 px, L −0.3 px, 2XL −1 px. Other styles exist in components; no complete type ramp verified. |
| Spacing | padding XS 2, M 8, L 12, XL 16, 2XL 24 px; screen-specific gaps include 2, 4, 8, 10, 12, 20, 24, 30 px. Sidebar padding 14/16 px. |
| Dimensions | screen 1700×1024; sidebar 280 px; content 1360 px; search 398×48; tabs 301×48; employee column 220 px; day cells ~110 px; grid header 48 px; schedule rows 72 px; avatar 40×40; icon 24×24; title 32 px. |
| Radii | Searched context explicitly gives XS not established; M 8 px, search L 12 px, tab 10 px, panel 2XL 20 px, back icon button 99 px. |
| Borders | sidebar and grid use subtle neutral `#dedee3`; grid header has 1 px bottom border; sidebar has 1 px right border. No other border values established. |
| Shadows | No shadow value confirmed in the inspected context. |

## Schedule and assignment

Each row pairs an `AgentCell` (avatar, employee name, role/availability description) with seven `CellShedule` instances. Header labels weekday/date. Component boards show schedule cells for Week (110×72) and Month (64×72) types; examples include Default, Active, and Shedule (spelled that way in Figma). Shift blocks use color variants and templates. Employee selection and a `SelectionBar` are represented in component boards.

The written spec text in the canvas explicitly calls for employee name search; date navigation; week/month switching; assigning a shift by clicking a date to open quick selection or selecting an employee/date cell to choose a template; immediate display and later editing; reusable Morning/Day/Evening templates with start/end times; own shift with arbitrary start/end time, multi-cell selection, apply-to-selection; and coverage indicator state changing blue when any shift exists, updating on add/remove without reload. Screenshot/state boards depict visual states; the file does not establish implementation details such as persistence, validation, conflict handling, keyboard behavior, or exact date navigation controls.

## Uncertainties

Design context and metadata describe visual nodes, not a running prototype. Interaction behavior above is included only when stated in the canvas spec. No unsupported hover or pressed treatment is inferred. Exact colors for all named palette variants, full typography styles, shadows, and some borders/radii remain unverified from retrieved context. The calendar’s month view cell geometry is shown separately and is not the same width as the visible week grid.
