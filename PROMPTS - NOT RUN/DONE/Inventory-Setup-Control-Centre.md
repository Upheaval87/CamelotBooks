INVENTORY SETUP & CONTROL CENTRE — FULL IMPLEMENTATION SPEC (ITEM CATEGORIES /
ASSEMBLIES-KITS / STOCK TRANSFERS / STOCK ADJUSTMENTS / STOCK COUNT / UOM CONVERSIONS /
LANDED COSTS / INVENTORY VALUATION / LOW STOCK). Rebuild the Setup & Control section as
the inventory manager's workspace: inventory structure, costing, controlled movement and
monitoring — tightly integrated with Purchasing, Sales, Vendors, General Ledger and
Manufacturing. ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every Setup & Control page per the registry in §13:
resting slim icon rail with teal Expand; drawer with pin (true toggle, remembered per
page) + X at top-right; Favorites "Pin rails to right side bar" global toggle
unchanged; drawer hidden whenever the full rail is not displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: GRN receipt posting, sales-invoice issue
posting, valuation engine (FIFO / Average / Standard), COGS/inventory GL account
mappings, tax settings, export/print/barcode handlers, search/filter/pagination params,
auth/permissions and all routes remain EXACTLY as-is. All quantities and values are
derived from posted movements; nothing is edited directly outside the approved flows
below. Every pre-existing button keeps its handler; this spec adds/re-arranges UI only.

==================== 0 · DISCOVERY ====================
0.1 Inventory Setup & Control routes/pages + handlers: categories, assemblies/kits,
stock transfers, stock adjustments, stock count sessions, UOM conversions, landed
costs, valuation, low stock.
0.2 List CURRENT controls + handlers per page (drives §17 audit): transfer
approve/send/receive/cancel/print, adjustment approve/reverse/voucher, count session
steps, assembly build/reverse, landed-cost allocate/post, valuation run/recalculate.
0.3 Locate: category hierarchy + default account fields, UOM + conversions, warehouses
+ bins, valuation method per item, landed-cost expense types, count-session lock
mechanism (or absence — §8.4), reorder rules, notification channels.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + CHIPS ====================
2.1 Badges (pill + dot): Draft rgba(17,69,75,.07)/.2/#11454b; Sent steel
rgba(70,112,140,.10)/.4/#46708C; Received teal rgba(18,143,142,.10)/.35/#128F8E;
Completed/Approved/Matched = mint gradient (#ecfdf3→#dcf5e7, rgba(22,163,74,.28),
#15803d, dot #22c55e); Pending/Variance rgba(217,119,6,.10)/.35/#b45309 (dot
#d97706) — Variance rows use red rgba(185,28,28,.08)/.3/#b91c1c when negative;
Reversed/Cancelled/Frozen gray rgba(138,165,167,.15)/.5/#5f7476.
2.2 Chips (.tchip): adjustment types Increase (green tint) / Decrease (red tint) /
Write-off (gray) / Correction (steel); transfer status counts; workflow chips (.wf)
done = green tint, cur = amber tint, todo = gray tint with "→" separators.
2.3 Qty/value coloring: negative red 700; positive variance green 700; shortage red;
totals bold; tfoot rows border-top 1.5px #17565d.

==================== 3 · ITEM CATEGORIES (invsetup.categories) ====================
3.1 Page head: h1 "Item Categories" + sub; right [⋯ More: ⇄ Move Category · 📥 Import
Categories · 📤 Export Categories] + [➕ Add Category CTA].
3.2 TWO-COLUMN LAYOUT (≤1000px stack): LEFT card "Category Hierarchy" — tree (parent →
children with item counts; e.g. Electronics → Computers / Mobile Phones / Accessories;
Supplies → Paper / Toner & Ink; Services); node click loads RIGHT card.
3.3 RIGHT card "Category Details — {name}": header buttons [✎ Edit][ Move]
[ Delete (danger; blocked when items exist — surface message)]; g3 read/edit grid:
Inventory Asset Account (e.g. 1300) / Cost of Goods Sold Account (5000) / Sales Revenue
Account (4000) / Default Tax Rule / Default Warehouse / Reorder Rule (min + reorder
point). Note: new items inherit these defaults; overridable per item.
3.4 Category CRUD + move/import/export via EXISTING handlers; create minimal handlers
only where absent (server-side category table already exists — verify).

==================== 4 · ASSEMBLIES / ITEM KITS (invsetup.assemblies) =============
4.1 Page head: h1 "Assemblies / Item Kits" + sub; right [🕘 View Assembly History]
[🖨 Print Assembly Sheet] + [➕ Create Assembly CTA].
4.2 ASSEMBLY WORKSPACE card: header "Assembly — {finished item}" + mono chip {ASM-№} +
status badge (Built/Draft/Reversed) + actions [＋ Add Components][⚙ Build Assembly
secondary][↩ Reverse Assembly danger-o].
4.3 COMPONENTS TABLE: Component / Qty Required / Unit Cost (K) / Total (K) / Stock
After; tfoot "Components total". Cost inputs for Labour + Overhead.
4.4 COST SUMMARY BAR (4 cells): Components / Labour / Overhead / hero "Total Assembly
Cost" (teal). Total = components + labour + overhead (live).
4.5 BEHAVIOUR (existing or new engine wired to existing movement handlers): Build posts
component Issues + finished-goods Receipt at total assembly cost; Reverse posts the
opposite; both audited; Build blocked when any component on-hand < required (negative-
stock prevention §14.1); history table (ASM № / date / warehouse / components used /
labour / overhead / total / user).

==================== 5 · STOCK TRANSFERS (invsetup.transfers) =====================
5.1 Page head: h1 "Stock Transfers & Adjustments" OR "Stock Transfers" + sub; right
[⇄ New Transfer secondary][⇄ New Adjustment CTA] (if combined page; else transfers-only
buttons).
5.2 TRANSFERS CARD: header status count chips (Draft / Sent / Received / Completed);
table TRF № (mono) / Date / From → To / Items / Qty / User / Status badge / actions:
Draft → [Approve][Send]; Sent → [Receive][Cancel]; Received/Completed → [Print Note]/
[View]. All EXISTING handlers; approval workflow per settings.
5.3 TRANSFER DETAILS captured: Transfer №, From Location, To Location, Items, Quantity,
Date, User. Receive posts the counterpart movement; Cancel releases reserved qty.

==================== 6 · STOCK ADJUSTMENTS (invsetup.adjustments) =================
6.1 ADJUSTMENTS CARD: header type chips (Increase / Decrease / Write-off / Correction);
[＋ New Adjustment] opens modal/page: item, warehouse/bin, type, reason select (Damaged
goods / Lost stock / Expired items / Opening balances / Stock corrections), qty ±,
value (auto from method cost), note.
6.2 TABLE: Adj № (mono) / Date / Item / Type chip / Reason / Qty (±colored) / Value /
Status (Pending/Approved/Reversed) / actions: Pending → [Approve][Print Voucher];
Approved → [Reverse][Print Voucher]; Reversed → [View].
6.3 ACCOUNTING (existing GL engine): approved adjustments post DR/CR inventory asset ↔
write-off expense / loss / correction gain-expense per type mapping; reversal posts
opposite; voucher printable.

==================== 7 · STOCK COUNT (invsetup.stockcount) =======================
7.1 Page head: h1 "Stock Count / Physical Inventory" + sub; right [▶ Start Stock Count
CTA].
7.2 WORKFLOW CHIP ROW (.wflow): ✓ Create Count → ✓ Print Count Sheet → ✓ Physical
Counting → ● Enter Results → Review Variance → Approve Count → Post Adjustments
(states reflect the active session).
7.3 SESSION CARD: "Count Session — {warehouse} · {scope}" + mono chip {SC-№} + badge
"Inventory frozen" (gray) + actions [🖨 Print Count Sheet][⌨ Enter Count secondary]
[Review Variance][Approve Count][Post Adjustments CTA].
7.4 VARIANCE TABLE: Item / System Qty / Counted Qty / Variance (±colored) / Variance
Value (K) / Status (Matched / Variance); tfoot net variance.
7.5 BEHAVIOUR: count scope by Warehouse / Category / Item range; FREEZE blocks postings
(GRN/invoice/transfer/adjustment/build) for in-scope items while session open (§14.2);
Post Adjustments creates approved adjustment lines from variances (existing adjustment
handler) and unfreezes; barcode-based count + mobile stock-taking inputs supported
where hardware exists (count entry accepts scanned SKUs).

==================== 8 · UOM CONVERSIONS (invsetup.uom) ===========================
8.1 CARD: table Unit / Conversion (e.g. 1 Carton = 24 pcs; 1 Box = 12; 1 Ton = 1,000
kg; 1 Dozen = 12) / Purchase unit ✓ / Sales unit ✓ / Stock unit ✓; buttons [＋ Add
Unit][Create Conversion][Edit][Delete].
8.2 Auto-conversion everywhere: purchasing in cartons posts stock in base units
(10 cartons → 240 pcs); sales pick sales unit; reports show stock unit.

==================== 9 · LANDED COSTS (invsetup.landed) ===========================
9.1 CARD per landed-cost document: header "Landed Cost — {LC-№} · {PO-№}" + actions
[＋ Add Expenses][Allocate Cost][Post Cost secondary][View History].
9.2 WORKSHEET: Allocation Method select (By Value / By Quantity / By Weight / By
Volume); Linked Purchase Bill (locked input); cost table: Purchase cost / Shipping /
Import duty / Insurance + clearing / Transportation / Handling; tfoot "Total landed
cost".
9.3 SUMMARY BAR (3 cells): Cost uplift % / New avg cost per item / hero "Inventory
value update" (+value teal).
9.4 BEHAVIOUR: Post capitalises expenses into item cost (DR Inventory Asset ↔ CR
landed-cost clearing/AP via existing GL engine), recalculates valuation per item
method, writes cost-history entry; history table (LC № / PO / date / method / uplift /
posted by).

==================== 10 · INVENTORY VALUATION (invsetup.valuation) ================
10.1 METHOD CARDS (.mcards, 3): FIFO (on by default per item setting) / Average Cost /
Standard Cost; active card teal ring; method is PER ITEM (settings), cards explain.
10.2 SUMMARY BAR (4 cells): Inventory Value (sub "{method} · {n} warehouses") / Item
Cost Lines / Cost Movement (30d COGS) / hero Stock Profitability %.
10.3 ACTIONS: [⚡ Run Valuation secondary][Recalculate Cost][⇩ Export Report][🕘 View
History]. Reports: Inventory Value Report · Item Cost Report · Cost Movement Report ·
Stock Profitability Report (existing report pattern).
10.4 Real-time: values recompute from movement ledger on demand; no stored values.

==================== 11 · LOW STOCK (invsetup.lowstock) ===========================
11.1 CARD: header "Low Stock — Reorder Recommendations" + actions [⇩ Export Report]
[🔔 Send Alert][📄 Create Purchase Order secondary].
11.2 TABLE: Item Code (mono) / Item Name / Category / Current Qty (red 0 / amber low) /
Min Level / Shortage (red) / Preferred Supplier / Last Purchase Price.
11.3 BEHAVIOUR: shortage = max(0, min − current); "Create Purchase Order" prefills PO
with preferred supplier + suggested qty (max − current, rounded by purchase-unit
conversion); alerts (Email/SMS/System) fire when current ≤ reorder point (existing
notification handlers); auto-reorder PO option per settings (off by default).

==================== 12 · ADVANCED CONTROLS (settings-driven) =====================
12.1 Negative stock prevention: block (default) or warn on any issue/transfer/build
that would take on-hand < 0 — implemented as listener on existing movement handlers.
12.2 Batch/expiry + serial tracking surfaces reuse Inventory Centre item profile;
count sessions include batch/serial lines where tracked.
12.3 AI demand forecasting: optional forecast chip on low-stock table (suggested qty
from 6-month usage) when enabled in settings; never auto-posts.
12.4 Inventory audit trail: every flow above writes audit rows (user, timestamp, ref,
old→new) — visible in item profile Audit tab.

==================== 13 · RAILS REGISTRY (per page; rails feature unchanged) =======
invsetup.categories → Quick Nav [Items List, UOM Conversions, Inventory Settings].
invsetup.assemblies → Quick Nav [Build Assembly, Assembly History, Items List].
invsetup.transfers → Views [All Transfers(active), Draft, Sent, Completed] +
Quick Nav [New Transfer, New Adjustment, Items List].
invsetup.adjustments → Views [All Adjustments(active), Pending, Approved] +
Quick Nav [New Adjustment, Stock Count, Items List].
invsetup.stockcount → Quick Nav [Start Stock Count, Print Count Sheet, Adjustments].
invsetup.uom → Quick Nav [Categories, Items List].
invsetup.landed → Quick Nav [Purchase Bills, Valuation Report, Items List].
invsetup.valuation → Quick Nav [Run Valuation, Cost Movement, Low Stock].
invsetup.lowstock → Quick Nav [Create Purchase Order, Send Alert, Items List] +
Reports [Low Stock, Out of Stock].

==================== 14 · INTEGRATION & ACCOUNTING RULES (UI SURFACES ONLY) =======
14.1 All flows post via EXISTING movement + GL handlers: transfers = warehouse pair,
no value change; adjustments = inventory ↔ expense/gain per type; assemblies =
component issues + finished receipt at total cost; landed costs = capitalisation;
count posts = generated adjustments.
14.2 Count freeze: session lock flag checked by movement handlers for in-scope items;
released on Post/Cancel.
14.3 Category defaults (§3.3) feed item create; UOM conversions (§8.2) applied in
purchasing/sales/reports; valuation methods per item; low-stock suggestions respect
purchase-unit rounding.
14.4 Deactivate ≠ Delete everywhere; deletes blocked when referenced (categories with
items, units in use, posted documents).

==================== 15 · ACCESSIBILITY / RESPONSIVE ====================
15.1 aria: tree role=tree; workflow chips aria-current; ⋯ menus aria-haspopup; tabs/
cards focusable; focus rings #94a3b8; tables th scope; qty colors backed by text.
15.2 ≤1000px two-column layouts stack; mcards 1-col; ≤768px slim rail hidden; tables
horizontal-scroll inside cards; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 16 · CONSTRAINTS ====================
No changes to rails feature or other modules; no movement/valuation/GL handler changes;
no new packages; ONE shared component/CSS per pattern; no hardcoded sample data (live
ledger only); no direct quantity/value edits outside approved flows.

==================== 17 · VERIFY (EVERY PAGE) ====================
17.1 ACTION AUDIT: every button (category Add/Edit/Delete/Move/Import/Export, assembly
Add Components/Build/Reverse/History/Print Sheet, transfer Approve/Send/Receive/Cancel/
Print Note, adjustment New/Approve/Reverse/Voucher, count Start/Print/Enter/Review/
Approve/Post, UOM Add/Conversion CRUD, landed Add Expenses/Allocate/Post/History,
valuation Run/Recalculate/Export/History, low-stock Export/Send Alert/Create PO)
triggers the SAME handler/route as pre-implementation where it existed (spot-click each).
17.2 MATH: assembly total = components + labour + overhead; landed uplift % and new avg
cost correct per allocation method; variance = counted − system; shortage = max(0,
min − current); conversions (10 cartons → 240 pcs); tfoot totals = sums.
17.3 CONTROLS: build blocked on insufficient components; count freeze blocks postings;
negative stock blocked/warned per settings; approvals gate transfers/adjustments/count
posts; deletes blocked when referenced.
17.4 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; pages render §13 registries.
17.5 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/chip table; rail registry per page; accounting-entry mapping
per flow; pages/handlers created (if any); confirmation rails + all existing
functionality unchanged and quantities/values always derived from posted movements.