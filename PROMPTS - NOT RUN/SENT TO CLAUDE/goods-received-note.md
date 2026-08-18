GOODS RECEIVED NOTE (GRN) MODULE — FULL IMPLEMENTATION SPEC (LIST / SELECT-PO / CREATE /
DETAIL / REPORTS). Rebuild the GRN module as the receiving control point in the chain
Purchase Requisition → RFQ → Purchase Order → GRN → Supplier Invoice → Payment. A GRN
CONFIRMS RECEIPT — it never creates a supplier payment. ALL VALUES INLINE; no mockup
dependency. The system-wide pinnable rails feature (rails.html) stays EXACTLY as
implemented — each GRN page renders its rail per the registry in §9; global pin applies.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: PO handlers, posting handler, inventory
movement creation, three-way-match logic, returns/reversal handlers, export/print
handlers, search/filter/sort/pagination params, auth/permissions and all routes remain
EXACTLY as-is. Every pre-existing button keeps its handler; this spec re-styles/re-
arranges UI only. Fix the current mislabeled list heading ("Create GRN") to
"Goods Received Notes".

==================== 0 · DISCOVERY ====================
0.1 Inventory GRN routes/pages + handlers: index/create/edit/show/post, select-PO,
returns, reverse, create-supplier-invoice, inventory movement view, audit trail.
0.2 List CURRENT controls + handlers per page (drives §13 audit), incl. the existing
row "View" and Post button behaviours.
0.3 Locate: PO registry (ordered/previously received/remaining per PO LINE), suppliers,
warehouses + stock locations, serialized/batch-tracked items, inventory movements,
supplier invoice create, payments.
0.4 Locate user-preference storage + header Favorites (rails) — reference only.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES ====================
Pill + dot component: Draft rgba(17,69,75,.07)/.2/#11454b; Pending Inspection
rgba(217,119,6,.10)/.35/#b45309 (dot #d97706); Pending Approval rgba(180,83,9,.12)/.4/
#92400e; Posted = mint gradient (#ecfdf3→#dcf5e7, rgba(22,163,74,.28), #15803d, dot
#22c55e); Partially Received rgba(70,112,140,.10)/.4/#46708C; Fully Received = mint;
Rejected rgba(185,28,28,.08)/.3/#b91c1c; Cancelled + Returned = gray
rgba(138,165,167,.15)/.5/#5f7476.

==================== 3 · LIST (grn.index) ====================
3.1 NON-sticky page head: h1 "Goods Received Notes" + sub "Record what was delivered,
inspect it, and confirm receipt against the Purchase Order."; right: [⋯ More ghost menu:
Import · Export · Print · Refresh] [📄 From Purchase Order secondary] [＋ New GRN CTA].
3.2 CLICKABLE STATUS BOXES (5): Draft(t-ink) / Pending Inspection(t-amber) /
Posted(t-mint) / Partial(t-steel) / Rejected(t-red); live counts; active teal ring;
click sets EXISTING status filter param.
3.3 METRIC CHIPS row: "Goods received this month {MWK value}" · "Pending inspection {n}
Review →" (amber when >0) · "Partial receipts {n} View →" — server-computed, linked.
3.4 CONTROLS: search (GRN #, PO #, supplier; EXISTING param) + Warehouse select +
sort select [Newest / Value high→low].
3.5 TABLE (mist thead): GRN № (mono) 11 / Date 9 / PO № (mono) 11 / Supplier 15 /
Warehouse 12 / Items num 5 / Value (K) num 10 / Status 13 / Actions 8. Actions column:
View icon + STATUS-DEPENDENT ⋯ menu (existing handlers):
 Draft → Edit, Submit, Delete, Print, View.
 Pending Inspection → View, Inspect, Accept, Reject, Return for Correction, Print.
 Posted → View, Print, Download PDF, View Purchase Order, View Supplier Invoice,
 View Inventory Movement, View Audit Trail, Create Return.
 Partially Received → View, Receive Remaining Items, Print, View PO, Create Return.
 Rejected/Returned/Cancelled → View, Print, View Audit Trail.
3.6 Pagination "Showing X of Y GRNs" + Prev/Next (existing).

==================== 4 · SELECT PURCHASE ORDER (grn.select-po) ====================
4.1 Card (max 980px): header "Select Purchase Order" + chip "open / partial only".
Search PO/supplier. Table: PO № (mono) / Supplier / Date / Ordered (K) / Received (K) /
Status (Open teal-tint / Partial steel) / [Select secondary-xs]. ONLY POs eligible for
receiving (open or partially received, not fully received/cancelled) — server-filtered.
4.2 Select → grn.create?po={id} with PO lines pre-loaded (§5).

==================== 5 · CREATE (grn.create) ====================
5.1 TWO ENTRY MODES: "＋ New GRN" = standalone (empty editable lines); "From Purchase
Order" = PO-linked (supplier + PO locked, lines pre-filled with Ordered/Prev Received).
5.2 Sticky head: h1 "Create Goods Received Note" + mono chip {PO-№ or "Standalone"} +
sub "{supplier} · ordered {value} · received {value}"; right: Cancel ghost + seg
[Save Draft ghost | Save & Submit ⤴ CTA] (existing handlers).
5.3 SUMMARY BAR (live): grid 1fr 1fr 1fr 1.25fr; cells Ordered (PO) (n = lines) /
This Receipt (live, teal) / Rejected (red, n = rejected note) / hero GRN Total.
≤900px 2 cols, hero spans.
5.4 SECTION "GRN Details" (g4): Supplier (locked when PO-linked) / Purchase Order
(locked, shows № + date) / Receipt Date / Warehouse select / Received By (default
current user) / Delivery Note № / Invoice № (optional) / Vehicle № (optional).
5.5 SECTION "Received Items" — THE HEART. Header buttons: ＋ Add Item · ⌨ Scan Barcode ·
# Serial Numbers · 🧪 Add Batch · 📎 Add Attachment (existing handlers where present).
Table columns: Item 20 / Ordered num 8 / Prev. Rec. num 9 / This Receipt (input) num 9 /
Total Rec. num 9 / Remaining num 8 / Condition select 13 / Acceptance select 14 /
Actions 10 (# serials + delete). SYSTEM-COMPUTED per line:
 Total Rec. = Prev + This Receipt; Remaining = Ordered − Total Rec. (red when negative).
 Condition options: Good / Damaged / Defective / Wrong Item / Expired / Short Delivered.
 Acceptance options: Accepted / Partially Accepted / Rejected (when Partially Accepted,
 reveal Accepted qty + Rejected qty + Reason inputs on the line).
5.6 OVER-RECEIPT CONTROL: when This Receipt > outstanding, inline warnbar:
"⚠ Received quantity exceeds outstanding quantity by {n} units." + reason input +
[Allow Over-Receipt] (requires reason; records authorization in audit trail) + [Block]
(reverts qty to outstanding). Honour existing system setting (block vs allow-with-auth).
5.7 SERIAL NUMBERS: per serialized line, modal/inline list with ＋ Add Serial Number;
VALIDATION: serial count must equal This Receipt qty (live counter chip "3 of 8").
5.8 BATCH/LOT: per batch-tracked line: Batch №, Lot №, Mfg Date, Expiry Date, Qty.
5.9 WAREHOUSE/LOCATION: per stock line, Warehouse select + Location select
(Aisle/Rack/Shelf); default from item master.
5.10 SECTION "Delivery Discrepancies": computed Ordered Qty / Received Qty / Shortage
(locked inputs) + Discrepancy Type select (Short delivery / Over delivery / Damaged
goods / Wrong item / Missing item / Wrong specification / Expired goods) + Action select
(Receive Remaining Later / Cancel Remaining / Return to Supplier) + Reason textarea.
5.11 SECTION "Attachments & Receiving Notes": attachment chips (delivery note, supplier
invoice, packing list, inspection report, photos, quality certificate) + ＋ Add
Attachment (existing upload handler); Receiving Notes textarea.
5.12 TOTALS BLOCK ALWAYS below items: right box 320px rows This Receipt Value /
Rejected Value / GRN Total (final row border-top 1.5px #17565d 800); 0.00 when zero.

==================== 6 · DETAIL (grn.show) — HEADER STANDARD ====================
6.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Purchasing › Goods Received Notes ›
{GRN-№} (here mono). RIGHT cluster: [🖨 Print] + [⋯ More: Download PDF · Create
Supplier Invoice · View Purchase Order · View Inventory Movement · View Audit Trail ·
Create Return · Reverse GRN (danger item)]. Visibility per status: posted shows all;
draft hides invoice/return; existing rules win.
6.2 PROFILE CARD (identity only, NO buttons): tile 3.5rem teal-tint box icon; name row
"Goods Received Note" + mono chip + status badge; meta chips: supplier, PO №, warehouse,
Received by, delivery note №.
6.3 SUMMARY BAR: Ordered (PO) / Received (teal, n = units) / Rejected (red) / hero
Posted Value (teal) or "Awaiting…" amber when not posted.
6.4 RECEIVED ITEMS (read-only): Item / Ordered / Received / Rejected / Condition note /
Acceptance badge / Status badge (Complete/Partial). Serial/batch chips under item when
present.
6.5 THREE-WAY MATCH CARD: three mcols (Purchase Order / GRN / Supplier Invoice) showing
qty + unit price + totals; verdict chip: "✓ MATCH —…" green when invoice agrees with
received; "⚠ THREE-WAY MATCH EXCEPTION — Ordered {a} / Received {b} / Invoiced {c} ·
Variance {n}" amber/red otherwise. EXCEPTION blocks silent invoice approval (existing
rule). Hide invoice column until invoice exists.
6.6 WORKFLOW CARD: steps Received → Inspected → Approved → Posted to Inventory with
who/timestamps (done teal / current amber pulse / todo gray); when Posted show chip
"locked · posted" and include inventory-movement note line.
6.7 RELATED DOCUMENTS CHAIN: clickable mono chips with arrows: {PR-№} → {PO-№} →
{GRN-№ here} → {INV-№} → {PMT-№}; missing links render as em-dash placeholders.
6.8 ATTACHMENTS + NOTES list (chips + notes text).
6.9 AUDIT TRAIL: rows {timestamp mono} {user} {action} incl. creation, inspection,
approval, posting, over-receipt authorizations, reversals, returns.

==================== 7 · POSTING / RETURNS / INVOICE RULES (UI SURFACES ONLY) ======
7.1 POST (existing handler): creates inventory movement RECEIPT for STOCK items only
(non-stock services skip); locks GRN; button surface = existing Post on draft/pending.
7.2 POSTED GRN is immutable: no Edit/Delete; corrections via [Create Return] (linked
return document) or [Reverse GRN] (reason + user + timestamp + approval if configured)
— both existing handlers.
7.3 CREATE SUPPLIER INVOICE (existing handler): pulls supplier, PO №, GRN №, items,
quantities (received, not ordered), purchase prices, taxes, discounts, totals; three-way
match variance raises exception (§6.5).

==================== 8 · REPORTS (grn.reports) ====================
8.1 Page head h1 "Goods Received Reports" + right [⇩ Export All]; filter bar: period
seg2 [This Month|This Quarter|This Year|Custom] + Supplier select + Warehouse select;
filters apply to all reports + exports; state in URL params.
8.2 REPORT CARDS (grid 3 cols): GRN Register · Goods Received by Supplier · Outstanding
Purchase Orders · Partial Receipts · Rejected Goods · GRN Aging. Each: icon tile, title,
description, PDF + CSV chips, Open →. Existing report pages if present; else create
MINIMAL report pages using the system report pattern.
8.3 SUPPLIER DELIVERY PERFORMANCE rendered table on this page: Supplier / Ordered /
Received / Short (red when >0) / Rejected (red when >0) / On Time % (green ≥90, amber
80–89, red <80) + tfoot totals; computed from live data.
8.4 GRN Aging buckets: waiting for Inspection / Approval / Invoice / Posting.

==================== 9 · RAILS REGISTRY (per page; rails feature unchanged) ========
grn.index → Views [All GRNs(active), Draft, Pending Inspection, Posted,
Partially Received, Rejected] + Reports [GRN Register, Supplier Delivery Performance].
grn.select-po → Quick Nav [GRN List, Purchase Orders, Suppliers].
grn.create → Quick Nav [GRN List, View PO, Supplier].
grn.show → Quick Nav [Create Supplier Invoice, View PO, Inventory Movement,
Create Return, Print].
grn.reports → Quick Nav [GRN List, Outstanding POs, GRN Register].

==================== 10 · ACCESSIBILITY / RESPONSIVE ====================
10.1 aria: status boxes aria-pressed; ⋯ menus aria-haspopup; breadcrumb nav; serial
counter aria-live; focus rings #94a3b8; tables th scope.
10.2 ≤1100px statgrid 3-col; ≤768px: slim rail hidden, statgrid 2-col, g4 → 1fr 1fr;
items table horizontal-scrolls inside card (min-width 860px); no horizontal PAGE
scrollbar at 1280/1024/768.

==================== 11 · CONSTRAINTS ====================
No changes to rails feature or other modules; no PO/posting/inventory/invoice/return
handler changes; no new packages; ONE shared component/CSS per pattern; totals block
always present; sumbar never replaces it; no hardcoded sample data (live registry only);
GRN never creates a payment.

==================== 12 · VERIFY (EVERY PAGE) ====================
12.1 ACTION AUDIT: every button (More menus, From PO, New GRN, Select, Save Draft,
Save & Submit, Add/Scan/Serial/Batch/Attachment, Allow Over-Receipt/Block, Post, Print,
Download PDF, Create Supplier Invoice, View PO/Inventory/Audit, Create Return, Reverse,
Inspect/Accept/Reject/Return-for-Correction, Receive Remaining) triggers the SAME
handler/route as pre-implementation (spot-click each).
12.2 LIST: heading "Goods Received Notes"; status boxes set existing filter; ⋯ menus
match §3.5 per status; counts/chips live.
12.3 CREATE: Remaining/Total computed per line; over-receipt warn + auth flow works and
is audit-logged; serial count validation blocks save on mismatch; discrepancy section
auto-computes; totals block always visible with all rows.
12.4 DETAIL: header breadcrumb + cluster only; profile identity-only; three-way match
verdict correct for match + exception cases; posted GRN immutable (no Edit/Delete);
chain links navigate; audit trail complete.
12.5 REPORTS: all render from live data; performance table percentages correct; filters
+ exports carry params.
12.6 RAILS REGRESSION: slim/full/pins/global pin behave exactly as rails.html on these
and all other pages.
12.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/badge table; rail registry per page; report pages created;
confirmation rails + all existing functionality unchanged and GRN never creates payments.