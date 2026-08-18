INVENTORY CENTRE MODULE — FULL IMPLEMENTATION SPEC (DASHBOARD / ITEMS LIST / ADD-EDIT
ITEM / ITEM PROFILE / STOCK MANAGEMENT / CONFIGURATION / REPORTS). Rebuild Inventory as
the stock control hub: products, stock items and services with pricing, costing,
purchasing, sales and movements — tightly connected to Purchasing (PO/GRN), Sales,
Accounts Payable, Accounts Receivable and the General Ledger so every stock movement
automatically affects financial records. ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every inventory page per the registry in §11: resting
slim icon rail with teal Expand; drawer with pin (true toggle, remembered per page) +
X at top-right; Favorites "Pin rails to right side bar" global toggle unchanged.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: GRN receipt posting, sales-invoice issue
posting, transfer approve/receive, adjustment approval, valuation engine (FIFO /
Average / Standard), COGS/inventory GL account mappings, tax settings, export/print/
barcode handlers, search/filter/sort/pagination params, auth/permissions and all routes
remain EXACTLY as-is. Stock quantities and values are ALWAYS computed from posted
movements — never stored or edited directly except via the approved movement flows
(§8). Every pre-existing button keeps its handler; this spec re-styles/re-arranges UI
only.

==================== 0 · DISCOVERY ====================
0.1 Inventory routes/pages + handlers: dashboard, items index/create/edit/show, stock
adjustments, stock transfers, categories, brands, units of measure, price lists,
bundles, serial/lot tracking, barcodes, reports, settings.
0.2 List CURRENT controls + handlers per page (drives §15 audit), incl. row ⋯ menus
(Edit/Duplicate/Adjust/Transfer/Print Barcode/Deactivate), adjustment approve,
transfer approve/receive.
0.3 Locate: warehouses + bin locations, units + conversions, category hierarchy,
brands, price levels, valuation method per item, serial/batch stores, reservation
data, movement ledger, reorder rules, barcode settings.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + MOVEMENT CHIPS ====================
2.1 Item badges (pill + dot): Active = mint gradient (#ecfdf3→#dcf5e7,
rgba(22,163,74,.28), #15803d, dot #22c55e); Low Stock rgba(217,119,6,.10)/.35/#b45309
(dot #d97706); Out of Stock rgba(185,28,28,.08)/.3/#b91c1c; Service steel
rgba(70,112,140,.10)/.4/#46708C; Inactive gray rgba(138,165,167,.15)/.5/#5f7476.
2.2 Movement type chips (.tchip): Receipt green tint; Issue red tint; Transfer steel
tint; Adjustment gray tint; status chips Pending/Approved/In Transit/Received reuse
badge palette.
2.3 Quantity coloring in tables: 0 = red 700; ≤ reorder = amber 700; else ink; totals
bold; tfoot rows border-top 1.5px #17565d.

==================== 3 · DASHBOARD (inventory.dashboard) ====================
3.1 NON-sticky page head: h1 "Inventory Centre" + sub "Products, stock, pricing,
costing and movements — connected to Sales, Purchasing and the GL."; right:
[📊 View Reports][📤 Export Items][📥 Import Items][➕ Add Item CTA].
3.2 KPI ROW 1 (4): hero Total Inventory Value (teal gradient; sub "{method} valuation ·
{n} warehouses") · Total Items (sub "{active} active · {services} services") · Low
Stock (amber; "Reorder suggestions →") · Out of Stock (red; "View →").
3.3 KPI ROW 2 (4): Fast Moving (top turnover 30d) · Slow Moving (no movement 90+d) ·
Expiring (30d) (amber; batches + value) · Stock Movements (7d) ("{in} in · {out} out").
3.4 RECENT STOCK MOVEMENTS card: table Date / Reference (mono, links) / Item / Type
chip / Qty (+green / −red) / Warehouse / Value; "View all →".

==================== 4 · ITEMS LIST (inventory.items) ====================
4.1 Page head: h1 "Inventory Items" + sub; right [⋯ More: Import Items · Export Items ·
Print Barcodes · Inventory Settings] + [＋ Add New Item CTA].
4.2 CLICKABLE STATUS BOXES (5): All(t-ink) / Active(t-mint) / Low Stock(t-amber) /
Out of Stock(t-red) / Services(t-steel); live counts; active teal ring; click sets
EXISTING filter param.
4.3 CONTROLS: search (item name, SKU or barcode; EXISTING param) + Category +
Supplier + Warehouse selects.
4.4 TABLE (mist thead, min-width scroll): [SKU (mono) + Item Name (700) stacked] /
Category · Brand / Type chip (Inventory/Non-inventory/Service) / Unit / Purchase (K) /
Selling (K) / Available (colored per §2.3) / Reorder / Warehouse / Status badge /
actions (View icon + ⋯ menu: Edit · Duplicate · Adjust Stock · Transfer Stock ·
Print Barcode · Deactivate — existing handlers). Pagination existing.

==================== 5 · ITEM CREATE / EDIT (inventory.create / edit) =============
5.1 Sticky head: h1 "Add New Inventory Item" / "Edit Item {code}" + sub; right Cancel
ghost + seg [Save & Add Another ghost | Save Item CTA] (edit adds Deactivate with
confirm; delete blocked when movements exist — surface message).
5.2 SECTION "Basic Information" (g4): Item Name* / Item Code–SKU (auto, disabled) /
Barcode (input + [▮|| Generate] + scan support where hardware exists) / Item Type
select (Inventory / Non-inventory / Service) / Category / Brand / Image upload /
Description.
5.3 SECTION "Pricing Information": Purchase Cost / Selling Price / Wholesale Price /
Minimum Selling Price / Tax Settings select / Discount Rules select + [＋ Add Price
Level] (price-level table: level, price, margin, tax).
5.4 SECTION "Inventory Information": Opening Quantity / Reorder Level / Maximum Stock
Level / Stock Valuation Method (FIFO / Average Cost / Standard Cost) / Warehouse
Location / Bin Location / Track Serial Numbers (Yes/No) / Track Batch–Expiry (Yes/No).
5.5 SECTION "Supplier Information": Preferred Supplier combo (search) / Supplier Code
(auto) / Supplier Price / Last Purchase (auto) + [＋ Add Supplier][View Supplier].

==================== 6 · ITEM PROFILE (inventory.show) — HEADER STANDARD ===========
6.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Inventory › Items › {ITM-code} (here
mono). RIGHT cluster: [✎ Edit][⇄ Adjust Stock][⇄ Transfer][🖨 Print Barcode] +
[⋯ More: ⧉ Duplicate · 📄 New Purchase Order · 🖨 Print Labels · ⌨ Scan Item ·
⏸ Deactivate (danger)].
6.2 PROFILE CARD (identity only, NO buttons): box-icon tile; name + mono chip + status
badge; meta chips: category · brand, unit (+conversion), valuation method, warehouse ·
bin, barcode.
6.3 SUMMARY BAR: Available (sub "on hand {n} · reserved {n}") / Reorder Level (sub
"max {n}") / Avg Cost (sub "FIFO layers: {n}") / hero Stock Value (teal).
6.4 TABBED ITEM CARD (10 tabs; client-side panes):
 Overview → g3 read-only: code, type, category/brand, unit (+conversion), valuation,
 tax, preferred supplier, last purchase, margin %.
 Stock Information → per-warehouse table: Warehouse / Bin / On Hand / Reserved /
 Available / Value + tfoot totals.
 Sales History → table Date / Reference / Customer / Qty / Price / Total (links).
 Purchase History → table Date / Reference / Supplier / Qty / Cost / Total (links).
 Stock Movements → table Date / Reference / Type chip / In / Out / Balance / Warehouse
 (running balance bold).
 Suppliers → table Supplier (★ preferred) / Code / Last Price / Last Purchase /
 Lead Time.
 Pricing → price-level table: Level / Price / Margin (green) / Tax.
 Serial Numbers → serial chips with state chips (in stock green / reserved amber /
 sold red + ref) + [＋ Add Serial Numbers][⌨ Scan]; batch chips with expiry (warn
 within 30d, bad expired) + [View Batch History].
 Documents → attachment chips + Upload.
 Audit Trail → rows incl. price changes ("Selling price changed 1,100,000 → 1,150,000")
 and GRN receipts with user + timestamp.

==================== 7 · STOCK MANAGEMENT (inventory.stock) =======================
7.1 Page head: h1 "Stock Management" + sub; right [⇄ New Transfer secondary]
[⇄ Adjust Quantity CTA].
7.2 STOCK ADJUSTMENTS card: table Adj № / Date / Item / Reason (Damage / Correction /
Expired / Missing) / Qty (−red) / Value / Status (Pending/Approved) / actions
[Approve][Print Adjustment Note] — existing approval handler; approved adjustments
post movement + GL via existing engine.
7.3 STOCK TRANSFERS card: table TRF № / Date / Item / From → To / Qty / Status
(Pending / In Transit / Received) / actions [Approve][Receive][View] — existing
approve/receive handlers; receive posts the counterpart movement.

==================== 8 · CONFIGURATION (inventory.config) =========================
8.1 ITEM CATEGORIES card: hierarchy tree (parent → children with item counts) +
[＋ Add Category][Edit][Delete] (existing handlers).
8.2 UNITS OF MEASURE card: table Unit / Base / Conversion (e.g. 1 Carton = 24 pcs) +
[＋ Add Unit][Configure Conversion].
8.3 ITEM BUNDLES / KITS card: bundle name + bundle price chip + component chips
(item × qty); selling a bundle AUTO-DEDUCTS each component (existing bundle handler);
[Create Bundle][Manage Components].
8.4 SERIAL / BATCH TRACKING card: batch chips (batch № · item · expiry chip warn/ok) +
serial-tracked categories + warranty note; [View Batch History].
8.5 BARCODE MANAGEMENT: [Generate Barcode][Print Labels][Scan Item] wired to existing
barcode handlers (settings: format, label layout).

==================== 9 · REPORTS (inventory.reports) ==============================
Report cards (grid 3 cols): Stock Valuation · Stock Movement · Inventory Summary ·
Stock Aging · Low Stock (with suggested reorder qty) · Out of Stock · Item Sales ·
Item Purchase · Profit Margin. Each: icon tile, description, PDF + Excel chips,
Open →. Existing report pages if present; else MINIMAL report pages using the system
report pattern; [Generate][Print][Export PDF][Export Excel].

==================== 10 · SETTINGS (inventory.settings) ===========================
Cards (existing settings handlers; create minimal pages only where absent): SKU
numbering format · Barcode settings · Stock valuation method default · Reorder rules ·
Tax rules · Warehouse settings · User permissions.

==================== 11 · RAILS REGISTRY (per page; rails feature unchanged) =======
inventory.dashboard → Quick Nav [Items List, Stock Levels, Add Item,
Inventory Reports].
inventory.items → Views [All Items(active), Active, Low Stock, Out of Stock, Services]
+ Reports [Stock Valuation, Low Stock].
inventory.create/edit → Quick Nav [Items List, Item Categories, Suppliers].
inventory.show → Quick Nav [Adjust Stock, New Purchase Order, Print Barcode,
Items List].
inventory.stock → Quick Nav [New Adjustment, New Transfer, Items List].
inventory.config → Quick Nav [Items List, Categories, Inventory Settings].
inventory.reports → Quick Nav [Items List, Stock Valuation, Low Stock].

==================== 12 · INTEGRATION & VALUATION RULES (UI SURFACES ONLY) ========
12.1 Movement sources (existing handlers only): GRN post → Receipt; sales invoice post
→ Issue; transfer receive → Transfer pair; approved adjustment → Adjustment; bundle
sale → component Issues. Reservations for sales orders reduce Available, not On Hand.
12.2 Valuation: FIFO layers / weighted average / standard per item setting; Stock
Value = qty × method value; COGS postings use existing mappings.
12.3 Reorder suggestions: items at/below reorder level → suggested qty = max − on hand
(+ lead-time factor where configured); links to "New Purchase Order" prefilled.
12.4 Multi-warehouse: every movement carries warehouse + bin; item profile shows
per-warehouse rows; dashboard value = sum across warehouses.
12.5 Deactivate ≠ Delete: deactivation preserves history; delete blocked when
movements exist (existing rule) — surface the message.

==================== 13 · ACCESSIBILITY / RESPONSIVE ====================
13.1 aria: status boxes aria-pressed; tabs role=tablist; ⋯ menus aria-haspopup;
breadcrumb nav; focus rings #94a3b8; tables th scope; qty colors backed by text.
13.2 ≤1100px statgrid 3-col; ≤1000px KPI 2-col + cfg grid stack + repcards 2-col;
≤768px slim rail hidden, statgrid 2-col, g4 → 1fr 1fr, tables horizontal-scroll inside
cards; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 14 · CONSTRAINTS ====================
No changes to rails feature or other modules; no movement/valuation/posting handler
changes; no new packages; ONE shared component/CSS per pattern; no hardcoded sample
data (live ledger only); no direct quantity edits outside approved flows.

==================== 15 · VERIFY (EVERY PAGE) ====================
15.1 ACTION AUDIT: every button (Add/Import/Export/Reports, status boxes, row ⋯ menus
Edit/Duplicate/Adjust/Transfer/Print Barcode/Deactivate, Save/Save & Add Another,
barcode generate/print/scan, adjustment Approve/Print Note, transfer Approve/Receive,
category/unit/bundle CRUD, report Opens, settings edits, profile cluster + tab
actions) triggers the SAME handler/route as pre-implementation (spot-click each).
15.2 MATH: Available = on hand − reserved; Remaining/Reorder coloring per §2.3;
per-warehouse tfoot totals = item totals; Stock Value = Σ(qty × method value);
bundle sale deducts components; expiry chips warn ≤30d / bad expired.
15.3 CONTROLS: delete blocked with movements; deactivation preserves history;
adjustments/transfers post only via approval; reservations reduce Available only.
15.4 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; inventory pages render §11 registries.
15.5 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/chip table; rail registry per page; config/report/settings
pages created (if any); confirmation rails + all existing functionality unchanged and
quantities/values always derived from posted movements.