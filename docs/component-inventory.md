# Component inventory

Inventory from Page 1 metadata, design context for main frame `2:5034`, screenshot and named component boards. Repeated instances are grouped by reusable component.

| Component / element | Evidence and dimensions | Variants / notes |
|---|---|---|
| Page shell | `Page` `2:5034`, 1700×1024 | Sidebar + content; 20 px outer corner radius. |
| Sidebar / MenuItem | Sidebar 280 px; menu items 250×40 | CRM, collapse icon, Home, Shifts; admin/project/user blocks are hidden in main screen. Active appearance visible on Shifts. |
| Button | Publish 144×48; Save 118×48; sidebar icon action 40×40 | Button board names Primary, Secondary, Tertiary, Quaternary, Quinary; Brand/Neutral; Default, Hover, Active, Disable. Board frame lists these explicitly. Pressed state is not named; Active should not be assumed equivalent to pressed. |
| ButtonIcon / icons | Back control 40×40 with 24×24 chevron; icon assets 24×24 | Back control is white and pill-rounded in context. |
| Search | 398×48 | Placeholder “Поиск по ФИО”; component board: Gray/White colors and Default/Hover/Active states. No explicit disabled variant found. |
| Tab / tab-control | 301×48; two equal controls | Month/Week; main screen shows one dark selected-looking segment. Component board context should be checked for whether this is named active/selected. |
| Schedule grid | Header 993×48; list 993×552 | 220 px employee column + seven ~110 px day columns; seven repeated 72 px rows visible. Subtle bottom rules. |
| AgentCell | Week instance 220×72; avatar 40×40 | Employee name and description slot; separate board has Default, CopyButton=False variant. |
| CellShedule | Week instance 110×72; Month board example 64×72 | Component board explicitly names Default, Active, Shedule states and Week/Day/Month types. “Shedule” is the Figma spelling. |
| Shift block / ConinerShedule | Week examples 98×64; Month 64×64; Template ~185×64 | Color variants Cornflower, Mint-100, Lavanda-100, Brand; shift template board includes template types. |
| SelectionBar | 997×52 | “Quick actions with information” board; exact controls/conditions require detailed state design or prototype. |
| Badge | example 53×26 | blue/neutral, Fill, Disabled=False. |
| Avatar | 40×40 in employee row | Asset/behavior not specified. |

## State coverage

| Component | Default | Hover | Pressed | Active/selected | Disabled |
|---|---|---|---|---|---|
| Button | Explicit | Explicit | Not found | Explicit | Explicit |
| Search | Explicit | Explicit | Not found | Explicit | Not found |
| Schedule cell | Explicit | Not found | Not found | Explicit | Not found |
| Shift block | Not clearly named | Not found | Not found | Not established | Not found |
| Tabs | Visual default/selected-like example | Not established | Not found | Selection implied visually; explicit variant not confirmed | Not found |
| Badge | Fill example | Not found | Not applicable | Not found | Disabled=False only |

“Not found” means no corresponding state was confirmed in inspected Figma output; it does not assert that the design cannot support it.
