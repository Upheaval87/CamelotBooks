# Inventory Setup & Control Centre — FULL REMOVAL of existing feature, then FULL REBUILD to exact design

## Objective
There is already an Inventory Setup & Control Centre feature in the app today, but it is not
comprehensive enough. You are to **remove that existing feature entirely first** (§0), then
**rebuild it from scratch** as the inventory manager's workspace: inventory structure, costing,
controlled movement and monitoring — tightly integrated with Purchasing, Sales, Vendors,
General Ledger and Manufacturing. Match the exact markup/CSS given in §3 below, wired to real
data and real workflow logic (§2).

This is not a re-skin of the current pages; treat it as tearing out the old implementation and
building a new one that happens to reuse the same underlying accounting/inventory concepts
(valuation engine, GRN/invoice posting, COGS mappings).

## 0 · FIRST: remove the existing Inventory Setup & Control Centre feature entirely
Before building anything new:
- Locate every route, controller/handler, view/page, component, and nav entry for the current
  feature: categories, assemblies/kits, stock transfers, stock adjustments, stock count
  sessions, UOM conversions, landed costs, valuation, low stock.
- Locate its data model: does it have its own dedicated tables, or does it only reference
  shared data?
- **Important**: the server-side **category table already exists** in this app (used by
  Inventory Centre and possibly Purchasing/Sales) — verify this before assuming you need to
  build one. Do not create a duplicate category table; reuse the existing one and only rebuild
  the Setup & Control UI/handlers around it.
- **Dedicated-to-this-feature code and tables** (assembly/kit definitions, stock transfer
  records, stock adjustment records, stock count session records, UOM conversion tables not
  already shared, landed-cost documents): remove/replace as part of this rebuild.
- **Shared services or data used by other modules** — GRN receipt posting, sales-invoice issue
  posting, the valuation engine (FIFO / Average / Standard), COGS/inventory GL account
  mappings, tax settings, the movement ledger itself, warehouses + bin locations, the category
  table (see above), and the app's existing rails feature — do **not** delete these. Only
  remove the feature-specific code that *calls* them, then re-wire the new module to call the
  same shared services. If unsure whether something is dedicated or shared, check for other
  callers before deleting anything.
- Remove the old nav entry — the rebuilt module's nav entry replaces it, not sits alongside it.
- Confirm no other page, report, or feature links to the old feature's specific routes/views
  before deleting them; if something does, update that reference to point at the new module's
  equivalent screen.
- Clean removal, not a soft-hide — no dead code, unused routes, or orphaned views left behind.

Only proceed to the rebuild (§1 onward) once the old feature is fully removed and you've
confirmed nothing else in the app references it.

## 1 · Scope guard — do not touch anything else
- This affects only the Inventory Setup & Control Centre pages, styles, routes, and its own
  data. Do not touch any other module (including Inventory Centre's item list/profile pages),
  except to re-wire calls to shared services as described in §0.
- Every button, drill-link, and report destination must be wired to a real, functioning
  route/handler — no dead links, no decorative buttons.
- No changes to GRN receipt posting, sales-invoice issue posting, the valuation engine,
  COGS/inventory GL account mappings, tax settings, or export/print/barcode handlers — this
  module surfaces and triggers these, it doesn't reimplement them.
- **This module keeps the app's existing system-wide pinnable rails feature exactly as
  implemented elsewhere in the app** (a per-page registry is given in §6) — reuse the real,
  already-built rails component; don't reimplement rail CSS/behavior from the mockup file. The
  drawer stays hidden whenever the full rail isn't displayed, matching existing behavior.

## 2 · Functional/workflow rules to rebuild
- **Nothing is edited directly outside the approved flows below** — all quantities and values
  are derived from posted movements.
- **Categories**: CRUD + Move/Import/Export via existing handlers where they exist; create
  minimal handlers only where genuinely absent. Category defaults (Inventory Asset Account,
  COGS Account, Sales Revenue Account, Default Tax Rule, Default Warehouse, Reorder Rule) feed
  new-item creation as defaults, always overridable per item. Delete is blocked when the
  category has items — surface that message.
- **Assemblies/Kits**: Build posts component Issues + a finished-goods Receipt at total
  assembly cost (components + labour + overhead); Reverse posts the opposite; both are
  audited. **Build is blocked when any component's on-hand quantity is less than required**
  (negative-stock prevention, §2 Advanced Controls below) — this must actually block, not just
  warn, unless settings say otherwise.
- **Stock Transfers**: Draft → [Approve][Send]; Sent → [Receive][Cancel]; Received/Completed →
  [Print Note]/[View]. Receive posts the counterpart movement; Cancel releases reserved
  quantity. Approval workflow follows existing settings.
- **Stock Adjustments**: type is one of Increase / Decrease / Write-off / Correction, with a
  reason (Damaged goods / Lost stock / Expired items / Opening balances / Stock corrections).
  Pending → [Approve][Print Voucher]; Approved → [Reverse][Print Voucher]; Reversed → [View].
  Approved adjustments post DR/CR inventory asset ↔ write-off expense / loss / correction-gain
  per the existing type→GL mapping; reversal posts the opposite entry. Don't reimplement this
  GL engine — call it.
- **Stock Count**: count scope is by Warehouse / Category / Item range. Opening a count session
  **freezes** postings (GRN/invoice/transfer/adjustment/build) for every in-scope item while
  the session is open — implement this as a lock check inside the existing movement handlers,
  not a UI-only restriction. "Post Adjustments" creates approved adjustment lines from the
  variances using the existing adjustment handler, and releases the freeze. Barcode/mobile
  count entry accepts scanned SKUs where hardware exists.
- **UOM Conversions**: auto-convert everywhere — purchasing in cartons posts stock in base
  units (e.g. 10 cartons → 240 pcs); sales pick the sales unit; reports show the stock unit.
  Don't hardcode conversion math per screen; use one shared conversion utility.
- **Landed Costs**: Post capitalises the entered expenses into item cost — DR Inventory Asset,
  CR landed-cost clearing/AP — via the existing GL engine, recalculates valuation per the
  item's method, and writes a cost-history entry. Allocation method (By Value / By Quantity /
  By Weight / By Volume) determines how the total is spread across items on the linked bill.
- **Inventory Valuation**: values are always recomputed from the movement ledger on demand —
  **never stored**. Valuation method (FIFO / Average Cost / Standard Cost) is set per item, not
  globally; the method cards on this screen are informational/selectable-as-default, not a
  single global switch that overrides item-level settings.
- **Low Stock**: shortage = max(0, min level − current qty). "Create Purchase Order" prefills a
  PO with the item's preferred supplier and a suggested quantity (max − current, rounded by
  purchase-unit conversion). Alerts (Email/SMS/System) fire when current ≤ reorder point via
  existing notification handlers. Auto-reorder PO is a settings toggle, **off by default**.
- **Advanced controls** (settings-driven, apply across all flows above):
  - Negative stock prevention: block by default, or warn, per settings — implemented as a
    listener on the existing movement handlers, not duplicated per-flow logic.
  - Batch/expiry + serial tracking surfaces reuse the Inventory Centre item profile; count
    sessions include batch/serial lines where an item is tracked.
  - AI demand forecasting: an optional forecast chip on the low-stock table (suggested qty from
    6-month usage) shown only when enabled in settings — it **never auto-posts** anything.
  - Every flow above writes audit rows (user, timestamp, reference, old→new) visible in the
    item profile's Audit tab (the one already built in Inventory Centre — don't duplicate it).
- **Deactivate ≠ Delete everywhere**; deletes are blocked when referenced (categories with
  items, units in use, posted documents) — surface the blocking message.

## 3 · Exact target design
Everything below is extracted directly from the approved HTML mockup — implement it exactly,
don't restyle or invent alternate layouts. Every static example value (`ASM-00012`,
`Electronics`, `730,000`, etc.) is a placeholder — bind every one to real data.

### 3.0 — Mockup chrome vs. real page content
The mockup includes its own header (`<header class="tealbar">` — logo, brand, user chip, and
a top module nav row for Sales/Purchasing/Inventory/Banking/Reports) purely so it could preview
standalone. **Do not implement or replace the app's real global header/nav with that markup.**
Keep the app's actual existing global header exactly as it is. The mockup's rail markup
(`.slim-rail`/`.ql-drawer`/`.rail-block`) represents the app's already-built shared rails
component — per §1, reuse that real component with this module's content (§6), don't
reimplement rail CSS/behavior from this file. Everything else in each screen below is real page
content to implement.

The mockup groups nine spec routes (§13's rails registry lists `invsetup.categories`,
`.assemblies`, `.transfers`, `.adjustments`, `.stockcount`, `.uom`, `.landed`, `.valuation`,
`.lowstock` as distinct pages) into **six visual screens**, because Transfers+Adjustments,
UOM+Landed Costs, and Valuation+Low Stock are each shown side-by-side on one screen. Follow
whatever this app's **existing routing already does** for these pairs (the original spec
explicitly allows either a combined page or separate pages for Transfers/Adjustments — check
which the app already has and match it; apply the same "match existing routing" approach to the
UOM/Landed and Valuation/Low-Stock pairs, since the mockup presents all three pairs the same
way). If you're building fresh routes with no existing precedent to follow, default to the
mockup's combined-page structure for all three pairs — it's simpler to navigate and matches
what's shown.

### CSS (shared module styles — implement once, scope to this module; exclude the rail-specific rules noted below, which belong to the existing shared component)
Reuse the app's existing tokens where they already exist (`--deep-1`, `--sec`, `--ink`,
`--border`, `--muted`, etc.) — don't create duplicates. Add any not already defined:

```css
:root{
  --deep-1:#17565d;--deep-2:#0c3539;--deep-3:#0a2e32;
  --sec:#128F8E;--sec-2:#149897;
  --ink:#0B2A2D;--border:#dceaea;--line:#e2ecec;
  --muted:#5f7476;--faint:#8aa5a7;--focus:#94a3b8;
  --red:#dc2626;--red-2:#b91c1c;--green:#15803d;--amber:#d97706;--amber-2:#b45309;--steel:#46708C;
  --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 32px 64px -24px rgba(8,40,44,.14);
  --shadow-cta:0 1px 2px rgba(6,32,35,.30),0 10px 20px -10px rgba(12,53,57,.60),inset 0 1px 0 rgba(255,255,255,.12);
  --shadow-teal:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
}
html,body{overflow-x:clip}
.wrap{max-width:1440px;margin:0 auto;padding:0 28px 72px}

.page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
  padding:26px 0 16px;border-bottom:1px solid var(--line);margin-bottom:22px}
h1{font-size:21px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:11px;flex-wrap:wrap}
.sub{font-size:12.5px;color:var(--muted);margin-top:4px}
.mono-chip{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;color:var(--deep-1);
  background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);border-radius:8px;padding:4px 10px}
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.05em}
.badge .bdot{width:6px;height:6px;border-radius:50%}
.b-act{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}.b-act .bdot{background:#22c55e}
.b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:var(--amber)}
.b-out{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-out .bdot{background:var(--red-2)}
.b-sub{background:rgba(70,112,140,.10);border:1px solid rgba(70,112,140,.4);color:var(--steel)}.b-sub .bdot{background:var(--steel)}
.b-draft{background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);color:#11454b}.b-draft .bdot{background:#11454b}
.b-lock{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-lock .bdot{background:var(--muted)}
.tchip{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;
  background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);color:var(--muted)}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:40px;padding:0 16px;border-radius:12px;
  font-weight:600;font-size:13.5px;border:1px solid transparent;cursor:pointer;transition:all .16s;white-space:nowrap;font-family:inherit}
.btn:hover{transform:translateY(-1px)}
.btn-ghost{background:rgba(255,255,255,.85);border-color:var(--border);color:#374151}
.btn-ghost:hover{background:rgba(17,69,75,.06);border-color:rgba(17,69,75,.3);color:var(--ink)}
.btn-sec{color:#fff;background:linear-gradient(180deg,var(--sec-2),var(--sec));border-color:rgba(255,255,255,.25);box-shadow:var(--shadow-teal)}
.btn-cta{color:#eaffff;background:linear-gradient(180deg,var(--deep-1),var(--deep-2) 55%,var(--deep-3));border-color:rgba(255,255,255,.14);box-shadow:var(--shadow-cta);font-weight:700}
.btn-danger-o{background:#fff;border-color:rgba(220,38,38,.35);color:var(--red)}
.btn-sm{height:34px;padding:0 13px;font-size:12.5px;border-radius:10px}
.btn-xs{height:30px;padding:0 11px;font-size:11.5px;border-radius:9px}
.icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:rgba(255,255,255,.85);
  display:grid;place-items:center;color:var(--muted);cursor:pointer;flex:none}
.vdiv{width:1px;height:22px;background:var(--border)}
.more{position:relative}
.more-menu{position:absolute;right:0;top:calc(100% + 6px);width:230px;background:#fff;border:1px solid var(--border);
  border-radius:12px;box-shadow:0 16px 40px -12px rgba(8,40,44,.3);padding:6px;display:none;z-index:60}
.more.open .more-menu{display:block}
.more-item{display:flex;align-items:center;gap:9px;width:100%;padding:8px 10px;border:none;border-radius:9px;background:none;
  text-align:left;font-family:inherit;font-size:12.5px;font-weight:600;color:#374151;cursor:pointer}
.more-item:hover{background:rgba(17,69,75,.06)}
.more-item.danger{color:var(--red)}

.card{background:rgba(255,255,255,.75);backdrop-filter:blur(14px);border-radius:20px;box-shadow:var(--shadow-card);overflow:hidden}
.card-sec{padding:20px 24px}
.card-sec + .card-sec{border-top:1px solid var(--line)}
.card-h{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
.sec-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.sec-ic{width:28px;height:28px;border-radius:9px;display:grid;place-items:center;flex:none;color:#fff;background:var(--sec);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.18),0 3px 8px -3px rgba(10,80,80,.4)}
.sec-head h2{font-size:14px;font-weight:600;color:var(--muted)}
.sec-head .rule{flex:1;height:1px;background:var(--line)}

.li-wrap{margin-top:16px;border:1px solid var(--border);border-radius:14px;background:#fafdfd;overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;min-width:760px}
thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;
  letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
thead th:first-child{border-radius:13px 0 0 0}thead th:last-child{border-radius:0 13px 0 0}
thead th.num{text-align:right}
tbody td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
tbody tr:hover td{background:rgba(17,69,75,.04)}
tbody tr:last-child td{border-bottom:none}
tfoot td{padding:12px;border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink);background:rgba(17,69,75,.03)}
.mono{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:500;color:var(--ink)}
.em{color:var(--muted)}.dash{color:var(--faint)}
.numr{text-align:right;font-variant-numeric:tabular-nums;font-weight:500;color:var(--ink)}
.numr.bold{font-weight:800}
.numr.red{color:var(--red-2);font-weight:700}
.numr.amber{color:var(--amber-2);font-weight:700}
.numr.green{color:var(--green);font-weight:700}
.row-act{display:flex;gap:4px;justify-content:flex-end}
.ibtn{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;border:none;background:transparent;color:var(--faint);cursor:pointer}
.ibtn:hover{background:rgba(17,69,75,.06);color:var(--deep-1)}

.tree{list-style:none;margin-top:6px}
.tree li{position:relative;padding:6px 0 6px 22px;font-size:12.5px;font-weight:600;color:#374151}
.tree li::before{content:"";position:absolute;left:6px;top:0;bottom:0;width:1.5px;background:var(--line)}
.tree li::after{content:"";position:absolute;left:6px;top:16px;width:10px;height:1.5px;background:var(--line)}
.tree > li{padding-left:0}.tree > li::before,.tree > li::after{display:none}
.tree .cnt{font-size:10px;font-weight:800;color:var(--sec);background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.35);border-radius:999px;padding:1px 8px;margin-left:6px}
.tree .sel{color:var(--sec)}

.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
@media (max-width:900px){.g3{grid-template-columns:1fr 1fr}}
.fld .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fld .v{margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)}
.field label{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.input{width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 12px;
  font-size:13px;color:var(--ink);font-family:inherit;transition:all .15s}
.input:focus{outline:none;border-color:var(--focus);box-shadow:0 0 0 3px rgba(148,163,184,.18)}
select.input{width:auto;padding-right:30px;appearance:none;
  background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 11px center}

.wflow{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:4px 0 16px}
.wf{display:inline-flex;align-items:center;gap:7px;height:30px;padding:0 12px;border-radius:999px;font-size:11px;font-weight:800}
.wf.done{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.35);color:var(--green)}
.wf.cur{background:rgba(217,119,6,.12);border:1px solid rgba(217,119,6,.4);color:var(--amber-2)}
.wf.todo{background:rgba(138,165,167,.12);border:1px solid rgba(138,165,167,.4);color:var(--muted)}
.wf-arr{color:var(--faint)}

.mcards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media (max-width:900px){.mcards{grid-template-columns:1fr}}
.mcard{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.85)}
.mcard.on{border-color:rgba(18,143,142,.55);box-shadow:0 0 0 3px rgba(18,143,142,.12)}
.mcard .t{font-size:13.5px;font-weight:800;color:var(--ink)}
.mcard .d{font-size:11.5px;color:var(--muted);margin-top:4px;line-height:1.5}

.sumbar{display:grid;grid-template-columns:1fr 1fr 1fr 1.25fr;border:1px solid var(--border);border-radius:16px;
  overflow:hidden;background:rgba(255,255,255,.85);backdrop-filter:blur(14px);box-shadow:var(--shadow-card)}
.sumbar .cell{padding:14px 18px;border-right:1px solid var(--line);min-width:0}
.sumbar .cell .l{font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.sumbar .cell .v{margin-top:4px;font-size:1.125rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.sumbar .cell .n{margin-top:2px;font-size:10px;color:var(--faint)}
.sumbar .cell.hero{border-right:none;background:linear-gradient(90deg,var(--sec-2),var(--sec) 60%,#107c7b);
  display:flex;flex-direction:column;justify-content:center}
.sumbar .cell.hero .l{color:#dff7f6}.sumbar .cell.hero .v{color:#fff;font-size:1.25rem}
@media (max-width:900px){.sumbar{grid-template-columns:1fr 1fr}.sumbar .cell.hero{grid-column:1/-1;border-top:1px solid var(--line)}}

@media (max-width:768px){.stage-body{margin-right:0}.slim-rail{display:none!important}}
```

### 3.1 — Screen 1: Item Categories (route: `invsetup.categories`)
```html
<div class="page-head">
  <div><h1>Item Categories</h1><div class="sub">Organize items into logical groups with default accounts, tax, warehouse and reorder rules.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <div class="more"><button class="btn btn-ghost btn-sm">⋯ More</button>
      <div class="more-menu"><button class="more-item">⇄ Move Category</button><button class="more-item">📥 Import Categories</button><button class="more-item">📤 Export Categories</button></div></div>
    <button class="btn btn-cta btn-sm">➕ Add Category</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:16px" class="catgrid">
  <style>@media (max-width:1000px){.catgrid{grid-template-columns:1fr!important}}</style>
  <div class="card"><div class="card-h"><h2>Category Hierarchy</h2><span class="fmt" style="margin-left:auto">{n} levels</span></div>
    <div class="card-sec">
      <!-- real category tree from the existing category table (§0); .cnt shows live item count; .sel marks the currently selected node -->
      <ul class="tree" role="tree">
        <li><b class="sel">{parent category}</b><span class="cnt">{count}</span>
          <ul class="tree"><li>{child category}<span class="cnt">{count}</span></li></ul></li>
      </ul>
    </div></div>

  <div class="card"><div class="card-h"><h2>Category Details — {selected category name}</h2>
    <div style="margin-left:auto;display:flex;gap:8px"><button class="btn btn-ghost btn-xs">✎ Edit</button><button class="btn btn-ghost btn-xs">⇄ Move</button><button class="btn btn-danger-o btn-xs">🗑 Delete</button></div></div>
    <div class="card-sec">
      <div class="g3">
        <div class="fld"><div class="l">Inventory Asset Account</div><div class="v">{gl account} · {gl name}</div></div>
        <div class="fld"><div class="l">Cost of Goods Sold Account</div><div class="v">{gl account} · {gl name}</div></div>
        <div class="fld"><div class="l">Sales Revenue Account</div><div class="v">{gl account} · {gl name}</div></div>
        <div class="fld"><div class="l">Default Tax Rule</div><div class="v">{tax rule}</div></div>
        <div class="fld"><div class="l">Default Warehouse</div><div class="v">{warehouse}</div></div>
        <div class="fld"><div class="l">Reorder Rule</div><div class="v">Min {min} · reorder point {point}</div></div>
      </div>
      <div class="field" style="margin-top:14px"><span style="font-size:10.5px;color:var(--faint)">New items created under this category inherit accounts, tax, warehouse and reorder defaults — all overridable per item.</span></div>
    </div></div>
</div>
```
Delete is blocked with a surfaced message when the category has items.

### 3.2 — Screen 2: Assemblies / Item Kits (route: `invsetup.assemblies`)
```html
<div class="page-head">
  <div><h1>Assemblies / Item Kits</h1><div class="sub">Create finished products from components; build deducts components and adds finished stock.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">🕘 View Assembly History</button>
    <button class="btn btn-ghost btn-sm">🖨 Print Assembly Sheet</button>
    <button class="btn btn-cta btn-sm">➕ Create Assembly</button>
  </div>
</div>

<section class="card">
  <div class="card-h"><h2>Assembly — {finished item name}</h2><span class="mono-chip">{asm #}</span>
    <span class="badge b-act"><span class="bdot"></span>{status}</span>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-ghost btn-xs">＋ Add Components</button>
      <button class="btn btn-sec btn-xs">⚙ Build Assembly</button>
      <button class="btn btn-danger-o btn-xs">↩ Reverse Assembly</button>
    </div></div>
  <div class="card-sec" style="padding-top:6px">
    <div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:30%">Component</th><th class="num" style="width:12%">Qty Required</th>
        <th class="num" style="width:16%">Unit Cost (K)</th><th class="num" style="width:16%">Total (K)</th><th style="width:14%">Stock After</th></tr></thead>
      <tbody>
        <!-- one row per component; "Stock After" shows on-hand remaining after this build would run -->
        <tr><td style="font-weight:600;color:var(--ink)">{component}</td><td class="numr">{qty}</td>
          <td class="numr">{unit cost}</td><td class="numr">{total}</td><td class="em">{stock after} {unit}</td></tr>
      </tbody>
      <tfoot><tr><td>Components total</td><td></td><td></td><td class="numr">{components total}</td><td></td></tr></tfoot>
    </table></div>
    <div class="sumbar" style="margin-top:16px">
      <div class="cell"><div class="l">Components</div><div class="v">{components total}</div></div>
      <div class="cell"><div class="l">Labour</div><div class="v">{labour cost}</div></div>
      <div class="cell"><div class="l">Overhead</div><div class="v">{overhead cost}</div></div>
      <div class="cell hero"><div class="l">Total Assembly Cost</div><div class="v">K{total = components + labour + overhead}</div></div>
    </div>
  </div>
</section>
```
"Build Assembly" is disabled/blocked (with an explanatory message) when any component's
on-hand quantity is below what's required — don't just let the post fail silently. Include an
Assembly History table (ASM № / date / warehouse / components used / labour / overhead / total
/ user) reachable from "View Assembly History", using the same table styling as the rest of the
module.

### 3.3 — Screen 3: Stock Transfers + Stock Adjustments (routes: `invsetup.transfers` / `invsetup.adjustments` — combined or separate per §3.0)
```html
<div class="page-head">
  <div><h1>Stock Transfers &amp; Adjustments</h1><div class="sub">Move stock between locations; correct differences with approved, accounted adjustments.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-sec btn-sm">⇄ New Transfer</button>
    <button class="btn btn-cta btn-sm">⇄ New Adjustment</button>
  </div>
</div>

<section class="card" style="margin-bottom:16px">
  <div class="card-h"><h2>Stock Transfers</h2>
    <div style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
      <!-- live status counts -->
      <span class="tchip">Draft {n}</span><span class="tchip" style="background:rgba(70,112,140,.10);border-color:rgba(70,112,140,.4);color:var(--steel)">Sent {n}</span>
      <span class="tchip" style="background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.35);color:var(--green)">Completed {n}</span>
    </div></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:11%">TRF №</th><th style="width:10%">Date</th><th style="width:20%">From → To</th>
      <th style="width:18%">Items</th><th class="num" style="width:8%">Qty</th><th style="width:11%">User</th>
      <th style="width:10%">Status</th><th style="width:14%"></th></tr></thead>
    <tbody>
      <!-- row actions per status: Draft → [Approve][Send]; Sent → [Receive][Cancel]; Received/Completed → [Print Note]/[View] -->
      <tr><td class="mono">{trf #}</td><td class="em">{date}</td><td class="em">{from} → {to}</td><td class="em">{items summary}</td>
        <td class="numr">{qty}</td><td class="em">{user}</td><td><span class="badge b-draft"><span class="bdot"></span>{status}</span></td>
        <td><div class="row-act"><button class="btn btn-sec btn-xs">Approve</button><button class="btn btn-ghost btn-xs">Send</button></div></td></tr>
    </tbody></table></div>
</section>

<section class="card">
  <div class="card-h"><h2>Stock Adjustments</h2>
    <div style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
      <span class="tchip" style="background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.35);color:var(--green)">Increase</span>
      <span class="tchip" style="background:rgba(185,28,28,.08);border-color:rgba(185,28,28,.3);color:var(--red-2)">Decrease</span>
      <span class="tchip">Write-off</span><span class="tchip">Correction</span>
    </div></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:11%">Adj №</th><th style="width:10%">Date</th><th style="width:20%">Item</th>
      <th style="width:12%">Type</th><th style="width:15%">Reason</th><th class="num" style="width:8%">Qty</th>
      <th class="num" style="width:10%">Value (K)</th><th style="width:14%"></th></tr></thead>
    <tbody>
      <!-- type chip colored per §2 (Increase green, Decrease red, Write-off/Correction gray/steel); row actions per status: Pending → [Approve][Voucher]; Approved → [Reverse][Voucher]; Reversed → [View] -->
      <tr><td class="mono">{adj #}</td><td class="em">{date}</td><td style="font-weight:600;color:var(--ink)">{item}</td>
        <td><span class="tchip" style="background:rgba(185,28,28,.08);border-color:rgba(185,28,28,.3);color:var(--red-2)">{type}</span></td>
        <td class="em">{reason}</td><td class="numr red">{signed qty}</td><td class="numr">{value}</td>
        <td><div class="row-act"><button class="btn btn-sec btn-xs">Approve</button><button class="btn btn-ghost btn-xs">Voucher</button></div></td></tr>
    </tbody></table></div>
</section>
```
"New Adjustment" opens a modal/page capturing: item, warehouse/bin, type, reason select, qty
(±), value (auto-computed from the item's valuation method cost, not typed), note.

### 3.4 — Screen 4: Stock Count / Physical Inventory (route: `invsetup.stockcount`)
```html
<div class="page-head">
  <div><h1>Stock Count / Physical Inventory</h1><div class="sub">Compare system stock with physical count; approve and post variances as adjustments.</div></div>
  <button class="btn btn-cta btn-sm">▶ Start Stock Count</button>
</div>

<div class="wflow">
  <!-- .wf class reflects each step's real state for THIS session: done / cur / todo -->
  <span class="wf done">✓ Create Count</span><span class="wf-arr">→</span>
  <span class="wf done">✓ Print Count Sheet</span><span class="wf-arr">→</span>
  <span class="wf done">✓ Physical Counting</span><span class="wf-arr">→</span>
  <span class="wf cur">● Enter Results</span><span class="wf-arr">→</span>
  <span class="wf todo">Review Variance</span><span class="wf-arr">→</span>
  <span class="wf todo">Approve Count</span><span class="wf-arr">→</span>
  <span class="wf todo">Post Adjustments</span>
</div>

<section class="card">
  <div class="card-h"><h2>Count Session — {warehouse} · {scope}</h2><span class="mono-chip">{sc #}</span>
    <!-- badge shown only while the session is open/frozen -->
    <span class="badge b-lock"><span class="bdot"></span>Inventory frozen</span>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-ghost btn-xs">🖨 Print Count Sheet</button>
      <button class="btn btn-sec btn-xs">⌨ Enter Count</button>
      <button class="btn btn-ghost btn-xs">Review Variance</button>
      <button class="btn btn-ghost btn-xs">Approve Count</button>
      <button class="btn btn-cta btn-xs">Post Adjustments</button>
    </div></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:24%">Item</th><th class="num" style="width:12%">System Qty</th>
      <th class="num" style="width:12%">Counted Qty</th><th class="num" style="width:12%">Variance</th>
      <th class="num" style="width:14%">Variance Value (K)</th><th style="width:14%">Status</th></tr></thead>
    <tbody>
      <!-- variance = counted − system; positive = green, negative = red; status Matched when variance = 0, else Variance (amber/red badge) -->
      <tr><td style="font-weight:600;color:var(--ink)">{item}</td><td class="numr">{system qty}</td><td class="numr">{counted qty}</td>
        <td class="numr">{variance}</td><td class="numr">{variance value}</td>
        <td><span class="badge b-act"><span class="bdot"></span>{status}</span></td></tr>
    </tbody>
    <tfoot><tr><td>Net variance</td><td></td><td></td><td class="numr">{net variance qty}</td><td class="numr">{net variance value}</td><td></td></tr></tfoot>
  </table></div>
</section>
```
Freeze must actually block postings for in-scope items across GRN/invoice/transfer/
adjustment/build while this session is open (§2) — check this against the real movement
handlers, not just the badge display. "Post Adjustments" creates approved adjustment lines from
the variance rows via the existing adjustment handler and releases the freeze.

### 3.5 — Screen 5: UOM Conversions + Landed Costs (routes: `invsetup.uom` / `invsetup.landed` — combined or separate per §3.0)
```html
<div class="page-head">
  <div><h1>Units of Measure &amp; Landed Costs</h1><div class="sub">Conversions across purchase/sales/stock units; allocate freight, duty and clearing into item cost.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">＋ Add Unit</button>
    <button class="btn btn-cta btn-sm">＋ Create Landed Cost</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:16px" class="uomgrid">
  <style>@media (max-width:1000px){.uomgrid{grid-template-columns:1fr!important}}</style>
  <div class="card"><div class="card-h"><h2>UOM Conversions</h2><button class="btn btn-ghost btn-xs" style="margin-left:auto">Create Conversion</button></div>
    <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table style="min-width:0">
      <thead><tr><th>Unit</th><th>Conversion</th><th>Purchase</th><th>Sales</th><th>Stock</th></tr></thead>
      <tbody>
        <!-- ✓ / — per column depending on which contexts this unit is usable in -->
        <tr><td style="font-weight:600;color:var(--ink)">{unit}</td><td class="numr">{conversion, e.g. "1 = 24 pcs" or "base"}</td>
          <td class="em">{✓ or —}</td><td class="em">{✓ or —}</td><td class="em">{✓ or —}</td></tr>
      </tbody></table></div>
    <div class="card-sec" style="padding-top:12px"><span style="font-size:10.5px;color:var(--faint)">Example: purchase 10 cartons → stock 240 pieces automatically.</span></div>
  </div>

  <div class="card"><div class="card-h"><h2>Landed Cost — {lc #} · {po #}</h2>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-ghost btn-xs">＋ Add Expenses</button>
      <button class="btn btn-ghost btn-xs">Allocate Cost</button>
      <button class="btn btn-sec btn-xs">Post Cost</button>
      <button class="btn btn-ghost btn-xs">View History</button>
    </div></div>
    <div class="card-sec">
      <div class="g3" style="grid-template-columns:1fr 1fr">
        <div class="field"><label>Allocation Method</label><select class="input"><option>By Value</option><option>By Quantity</option><option>By Weight</option><option>By Volume</option></select></div>
        <div class="field"><label>Linked Purchase Bill</label><input class="input" value="{bill #} · {po #}" disabled></div>
      </div>
      <div class="li-wrap"><table style="min-width:0">
        <thead><tr><th>Cost Element</th><th class="num">Amount (K)</th></tr></thead>
        <tbody>
          <!-- one row per cost element: Purchase cost, Shipping, Import duty, Insurance + clearing, Transportation, Handling (only the ones entered) -->
          <tr><td style="font-weight:600;color:var(--ink)">{element}</td><td class="numr">{amount}</td></tr>
        </tbody>
        <tfoot><tr><td>Total landed cost</td><td class="numr">{total}</td></tr></tfoot>
      </table></div>
      <div class="sumbar" style="margin-top:16px;grid-template-columns:1fr 1fr 1.25fr">
        <div class="cell"><div class="l">Cost uplift</div><div class="v">+{uplift}%</div></div>
        <div class="cell"><div class="l">New avg cost · {item}</div><div class="v">{new avg cost}</div></div>
        <div class="cell hero"><div class="l">Inventory value update</div><div class="v">+{value change}</div></div>
      </div>
    </div>
  </div>
</div>
```
Posting a landed cost must actually recalculate the affected items' valuation per their method
and write a cost-history entry — verify this happens, not just that the summary bar shows a
plausible number.

### 3.6 — Screen 6: Inventory Valuation + Low Stock (routes: `invsetup.valuation` / `invsetup.lowstock` — combined or separate per §3.0)
```html
<div class="page-head">
  <div><h1>Valuation &amp; Replenishment</h1><div class="sub">Real-time value by costing method; reorder points with supplier recommendations.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">🕘 View History</button>
    <button class="btn btn-ghost btn-sm">⇩ Export Report</button>
    <button class="btn btn-sec btn-sm">⚡ Run Valuation</button>
  </div>
</div>

<div class="mcards" style="margin-bottom:16px">
  <!-- informational cards, not a single global switch — valuation method is per item -->
  <div class="mcard on"><div class="t">FIFO</div><div class="d">First purchased items sold first. Layered cost tracking per receipt.</div></div>
  <div class="mcard"><div class="t">Average Cost</div><div class="d">Weighted average purchase cost, recalculated on each receipt.</div></div>
  <div class="mcard"><div class="t">Standard Cost</div><div class="d">Fixed predetermined cost with variance reporting.</div></div>
</div>

<div class="sumbar" style="margin-bottom:16px">
  <div class="cell"><div class="l">Inventory Value</div><div class="v">{total value}</div><div class="n">{prevailing method} · {n} warehouses</div></div>
  <div class="cell"><div class="l">Item Cost Lines</div><div class="v">{count}</div><div class="n">{services count} services excluded</div></div>
  <div class="cell"><div class="l">Cost Movement (30d)</div><div class="v">{value}</div><div class="n">COGS posted</div></div>
  <div class="cell hero"><div class="l">Stock Profitability</div><div class="v">{pct}%</div></div>
</div>

<section class="card">
  <div class="card-h"><h2>Low Stock — Reorder Recommendations</h2>
    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
      <button class="btn btn-ghost btn-xs">⇩ Export Report</button>
      <button class="btn btn-ghost btn-xs">🔔 Send Alert</button>
      <button class="btn btn-sec btn-xs">📄 Create Purchase Order</button>
    </div></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:11%">Item Code</th><th style="width:22%">Item Name</th><th style="width:12%">Category</th>
      <th class="num" style="width:9%">Current</th><th class="num" style="width:9%">Min Level</th><th class="num" style="width:10%">Shortage</th>
      <th style="width:15%">Preferred Supplier</th><th class="num" style="width:12%">Last Price (K)</th></tr></thead>
    <tbody>
      <!-- Current: red at 0, amber when > 0 but below min; Shortage = max(0, min − current), always red when > 0; add an optional forecast chip next to Shortage when AI demand forecasting is enabled in settings -->
      <tr><td class="mono">{code}</td><td style="font-weight:600;color:var(--ink)">{item}</td><td class="em">{category}</td>
        <td class="numr red">{current}</td><td class="numr">{min level}</td><td class="numr red">{shortage}</td>
        <td class="em">{preferred supplier}</td><td class="numr">{last price}</td></tr>
    </tbody></table></div>
</section>
```
"Create Purchase Order" prefills preferred supplier + suggested qty (max − current, rounded by
purchase-unit conversion) — verify the prefill logic actually runs, not just that the button
navigates somewhere. "Send Alert" fires through existing notification handlers (Email/SMS/
System) — don't build new notification channels.

## 4 · Accessibility & responsive
- Category tree uses `role="tree"`.
- Workflow chips (`.wf`) use `aria-current` on the current step.
- `⋯` more-menus use `aria-haspopup`.
- Tabs/cards remain keyboard-focusable; focus rings use `--focus` (#94a3b8); table headers use `<th scope="col">`.
- Quantity/value colors (red/amber/green) are always backed by the numeric value itself, not color alone.
- Breakpoints (already encoded in the CSS in §3): the two-column layouts (Categories,
  UOM+Landed) stack ≤1000px; `.mcards` goes 1-col ≤900px; ≤768px slim rail hidden (existing
  shared behavior); tables scroll horizontally inside their cards. No horizontal page
  scrollbar at 1280/1024/768.

## 5 · Constraints
- No changes to any other module, including Inventory Centre's own pages.
- No changes to the rails feature's implementation itself — only its per-page content wiring (§6).
- No new frontend packages/frameworks.
- One shared, module-scoped CSS block reusing the app's existing tokens where they exist.
- No hardcoded sample data anywhere — every table/summary bar renders from the live movement ledger, category table, or settings only.
- No direct quantity/value edits anywhere outside the approved flows in §2.

## 6 · Rails registry — wire this module into the existing shared rails component
Implement per-page rail content exactly as follows (reuse the real, already-built rails
component — don't hand-roll this markup):

| Page | Quick Nav / Views content |
|---|---|
| `invsetup.categories` | Quick Nav: Items List, UOM Conversions, Inventory Settings |
| `invsetup.assemblies` | Quick Nav: Build Assembly, Assembly History, Items List |
| `invsetup.transfers` | Views: All Transfers (active), Draft, Sent, Completed — Quick Nav: New Transfer, New Adjustment, Items List |
| `invsetup.adjustments` | Views: All Adjustments (active), Pending, Approved — Quick Nav: New Adjustment, Stock Count, Items List |
| `invsetup.stockcount` | Quick Nav: Start Stock Count, Print Count Sheet, Adjustments |
| `invsetup.uom` | Quick Nav: Categories, Items List |
| `invsetup.landed` | Quick Nav: Purchase Bills, Valuation Report, Items List |
| `invsetup.valuation` | Quick Nav: Run Valuation, Cost Movement, Low Stock |
| `invsetup.lowstock` | Views/Reports: Low Stock, Out of Stock — Quick Nav: Create Purchase Order, Send Alert, Items List |

If Transfers/Adjustments, UOM/Landed, or Valuation/Low-Stock end up combined into single pages
per §3.0, combine their rail content sensibly on that one page rather than dropping either
side's entries. The global "pin rails" preference and slim-rail/drawer behavior must work
identically to how they already work on every other page — this module doesn't change that
feature's code at all.

---

## Verify before declaring done
- [ ] The old, less-comprehensive Inventory Setup & Control Centre feature is fully removed — no leftover routes, dead views, orphaned tables, or stale nav entries — and nothing else in the app links to its old routes/views. The existing category table was reused, not duplicated. Shared services (valuation engine, GRN/invoice posting, COGS/GL mappings, warehouses/bins, rails) were preserved and re-wired, not deleted.
- [ ] All six screens (Categories, Assemblies, Transfers+Adjustments, Stock Count, UOM+Landed Costs, Valuation+Low Stock) match §3 exactly, and the combined-vs-separate routing decision for the three paired screens matches this app's existing routing precedent (or the mockup's combined default if no precedent exists).
- [ ] Rails render via the app's existing shared component on every page in this module, with the content from §6.
- [ ] The app's real global header/nav was not replaced with the mockup's preview chrome.
- [ ] Category delete is blocked with a surfaced message when the category has items; category defaults correctly feed new-item creation and remain overridable per item.
- [ ] Assembly Build is actually blocked (not just visually discouraged) when any component's on-hand is below what's required; Build/Reverse post the correct component Issues + finished Receipt / opposite, both audited.
- [ ] Transfer and adjustment row actions match the exact per-status sets in §3.3; approved adjustments post through the existing type→GL mapping; reversal posts the opposite entry.
- [ ] Stock count freeze actually blocks GRN/invoice/transfer/adjustment/build postings for in-scope items while a session is open, verified against the real movement handlers — not just a badge; Post Adjustments creates approved adjustment lines from variances and releases the freeze.
- [ ] UOM conversions apply automatically in purchasing/sales/reports via one shared conversion utility, not duplicated per-screen math.
- [ ] Landed cost posting actually recalculates affected items' valuation and writes a cost-history entry.
- [ ] Valuation values are computed on demand from the movement ledger, never stored; the FIFO/Average/Standard cards are informational, not a single global override of per-item settings.
- [ ] Low Stock shortage math is correct (max(0, min − current)); Create Purchase Order prefills real supplier + suggested qty; Send Alert uses existing notification handlers; auto-reorder is off by default unless changed in settings.
- [ ] Negative-stock prevention, deactivate≠delete, and delete-blocked-when-referenced rules are enforced consistently across every flow in this module.
- [ ] Every button and action described in this document is wired to a real handler — spot-test each one individually.
- [ ] Responsive behavior matches §4; no horizontal scrollbar at 1280/1024/768.
- [ ] No console or build errors; text-size matrix 90/100/110/125% shows no clipping.

## Deliverable report
1. What was removed from the old feature (routes, views, controllers, tables — and whether tables were dropped or the data was shared/preserved) and confirmation nothing else in the app still references it. Confirm the existing category table was reused, not duplicated.
2. New files/routes created for the rebuilt module, and the nav entry (replacing the old one).
3. Which combined-vs-separate routing choice was made for Transfers/Adjustments, UOM/Landed, and Valuation/Low-Stock, and why (existing precedent vs. mockup default).
4. Action-mapping table: every button/control → the real handler/route it triggers, confirming valuation, posting, GL, and notification calls all go through the app's existing shared services.
5. Accounting-entry mapping per flow (adjustment type→GL, assembly build/reverse, landed cost capitalisation, count-post-to-adjustment) confirming it matches §2 exactly.
6. Status/badge/chip mapping table used across all screens.
7. Rail registry as actually wired per page (§6).
8. Confirmation that stock-count freeze is enforced inside the real movement handlers (not UI-only), with how you verified it.
9. Confirmation that no other module/page/business logic outside this feature was changed, and quantities/values are always derived from posted movements.
