# Inventory Centre module — FULL REMOVAL of existing feature, then FULL REBUILD to exact design

## Objective
There is already an Inventory Centre feature in the app today, but it is not comprehensive
enough. You are to **remove that existing feature entirely first** (§0), then **rebuild it
from scratch** as the stock control hub: products, stock items and services with pricing,
costing, purchasing, sales and movements — tightly connected to Purchasing (PO/GRN), Sales,
Accounts Payable, Accounts Receivable and the General Ledger so every stock movement
automatically affects financial records. Match the exact markup/CSS given in §3 below, wired
to real data and real workflow logic (§2).

This is not a re-skin of the current pages; treat it as tearing out the old implementation and
building a new one that happens to reuse the same underlying accounting/inventory concepts
(valuation engine, GRN/invoice posting, COGS mappings, approvals).

## 0 · FIRST: remove the existing Inventory Centre feature entirely
Before building anything new:
- Locate every route, controller/handler, view/page, component, and nav entry for the current
  inventory feature: dashboard, items index/create/edit/show, stock adjustments, stock
  transfers, categories, brands, units of measure, price lists, bundles, serial/lot tracking,
  barcodes, reports, settings.
- Locate its data model: does it have its own dedicated tables, or does it only reference
  shared data?
- **Dedicated-to-this-feature code and tables** (item master records, category/brand/unit
  configuration, bundle definitions, serial/batch records): remove/replace as part of this
  rebuild.
- **Shared services or data used by other modules** — GRN receipt posting, sales-invoice issue
  posting, the valuation engine (FIFO / Average / Standard), COGS/inventory GL account
  mappings, tax settings, the movement ledger itself, the approval workflow engine, warehouses
  + bin locations, and the app's existing rails feature — do **not** delete these. Only remove
  the inventory-specific code that *calls* them, then re-wire the new module to call the same
  shared services. If unsure whether something is dedicated or shared, check for other callers
  before deleting anything.
- Remove the old nav entry — the rebuilt module's nav entry replaces it, not sits alongside it.
- Confirm no other page, report, or feature links to the old feature's specific routes/views
  before deleting them; if something does, update that reference to point at the new module's
  equivalent screen.
- Clean removal, not a soft-hide — no dead code, unused routes, or orphaned views left behind.

Only proceed to the rebuild (§1 onward) once the old feature is fully removed and you've
confirmed nothing else in the app references it.

## 1 · Scope guard — do not touch anything else
- This affects only the Inventory Centre module's pages, styles, routes, and its own data. Do
  not touch any other module, except to re-wire calls to shared services as described in §0.
- Every button, drill-link, and report destination must be wired to a real, functioning
  route/handler — no dead links, no decorative buttons.
- No changes to GRN receipt posting, sales-invoice issue posting, transfer approve/receive,
  adjustment approval, the valuation engine, COGS/inventory GL account mappings, tax settings,
  or export/print/barcode handlers — this module surfaces and triggers these, it doesn't
  reimplement them.
- **This module keeps the app's existing system-wide pinnable rails feature exactly as
  implemented elsewhere in the app** (a per-page registry is given in §7) — reuse the real,
  already-built rails component; don't reimplement rail CSS/behavior from the mockup file.

## 2 · Functional/workflow rules to rebuild
- **Stock quantities and values are always computed from posted movements — never stored or
  edited directly**, except via the approved movement flows below. No direct quantity edits
  anywhere in the UI outside those flows.
- **Movement sources** (existing handlers only — don't rebuild any of these): GRN post →
  Receipt; sales invoice post → Issue; transfer receive → Transfer pair; approved adjustment →
  Adjustment; bundle sale → component Issues.
- **Reservations** for sales orders reduce **Available**, never On Hand. On Hand only changes
  through an actual posted movement.
- **Valuation**: FIFO layers / weighted average / standard cost, per item setting. Stock Value
  = qty × method value. COGS postings use the existing GL mappings — don't reimplement.
- **Reorder suggestions**: items at or below reorder level → suggested qty = max − on hand
  (plus a lead-time factor where configured) — link to "New Purchase Order" prefilled with this
  suggestion.
- **Multi-warehouse**: every movement carries warehouse + bin. Item profile shows per-warehouse
  rows. Dashboard total value = sum across all warehouses.
- **Deactivate ≠ Delete**: deactivation preserves history (existing rule); delete is blocked
  when movements exist (existing rule) — surface that blocking message to the user.
- **Bundles/kits**: selling a bundle auto-deducts each component from stock via the existing
  bundle handler — don't reimplement this logic, just surface it.
- **Status badges/type chips** (exact colors given in §3 CSS): Active (mint), Low Stock
  (amber), Out of Stock (red), Service (steel), Inactive (gray). Movement chips: Receipt (green
  tint), Issue (red tint), Transfer (steel tint), Adjustment (gray tint); status chips
  Pending/Approved/In Transit/Received reuse the badge palette.
- **Quantity coloring in tables**: 0 = red 700; ≤ reorder level = amber 700; otherwise ink;
  totals bold; table footers use the 1.5px `--deep-1` top border already in §3's CSS.
- **Search / filters / sort / pagination**: reuse the same query parameters and behavior the
  app already uses for list pages — keep state in URL params.

## 3 · Exact target design
Everything below is extracted directly from the approved HTML mockup — implement it exactly,
don't restyle or invent alternate layouts. Every static example value (`ITM-0001`, `Laptop HP
250 G8`, `850,000`, etc.) is a placeholder — bind every one to real data.

### 3.0 — Mockup chrome vs. real page content
The mockup includes its own header (`<header class="tealbar">` — logo, brand, user chip, and
a top module nav row for Sales/Purchasing/Inventory/Banking/Reports) purely so it could preview
standalone. **Do not implement or replace the app's real global header/nav with that markup.**
Keep the app's actual existing global header exactly as it is. The mockup's rail markup
(`.slim-rail`/`.ql-drawer`/`.rail-block`) represents the app's already-built shared rails
component — per §1, reuse that real component with this module's content (§7), don't
reimplement rail CSS/behavior from this file. Everything else in each screen below is real page
content to implement.

The mockup concatenates seven workflow screens one after another (separated by `<span
class="opt-tag">` labels) purely for presentation in one file — these are **seven separate
screens/routes**, plus one more (Inventory Settings — §6) that has **no mockup screen** and
must be built from the functional description in §6 using the CSS components already defined
in §3 (`.g3`, `.card`, `.fld`) so it stays visually consistent with the rest of the module.

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
.sticky-head{position:sticky;top:0;z-index:30;background:rgba(238,244,244,.88);backdrop-filter:blur(12px);
  padding:12px 0;border-bottom:1px solid var(--line);margin:22px 0;
  display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
h1{font-size:21px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:11px;flex-wrap:wrap}
.sub{font-size:12.5px;color:var(--muted);margin-top:4px}
.mono-chip{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;color:var(--deep-1);
  background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);border-radius:8px;padding:4px 10px}
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.05em}
.badge .bdot{width:6px;height:6px;border-radius:50%}
.b-act{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}.b-act .bdot{background:#22c55e}
.b-low{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-low .bdot{background:var(--amber)}
.b-out{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-out .bdot{background:var(--red-2)}
.b-srv{background:rgba(70,112,140,.10);border:1px solid rgba(70,112,140,.4);color:var(--steel)}.b-srv .bdot{background:var(--steel)}
.b-inact{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-inact .bdot{background:var(--muted)}
.b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:var(--amber)}
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
.seg{display:inline-flex;gap:4px;padding:4px;border-radius:14px;background:rgba(255,255,255,.7);border:1px solid var(--border)}
.seg .btn{height:34px;border-radius:10px;box-shadow:none}
.icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border);background:rgba(255,255,255,.85);
  display:grid;place-items:center;color:var(--muted);cursor:pointer;flex:none}
.crumbs{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--muted)}
.crumbs a{color:var(--muted);text-decoration:none}.crumbs a:hover{color:var(--ink)}
.crumbs .here{color:var(--ink);font-weight:800;font-family:ui-monospace,Menlo,monospace;font-size:11.5px}
.cluster{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
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
.sec-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.sec-ic{width:28px;height:28px;border-radius:9px;display:grid;place-items:center;flex:none;color:#fff;background:var(--sec);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.18),0 3px 8px -3px rgba(10,80,80,.4)}
.sec-head h2{font-size:14px;font-weight:600;color:var(--muted)}
.sec-head .rule{flex:1;height:1px;background:var(--line)}

.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.kpi{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
.kpi .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.kpi .v{margin-top:6px;font-size:1.25rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.kpi .n{margin-top:3px;font-size:10.5px;color:var(--faint)}
.kpi.hero{border:none;background:linear-gradient(135deg,var(--sec-2),var(--sec) 60%,#0c7a79)}
.kpi.hero .l{color:#dff7f6}.kpi.hero .v{color:#fff}
.kpi.warn .v{color:var(--amber-2)}
.kpi.red .v{color:var(--red-2)}
@media (max-width:1000px){.kpis{grid-template-columns:1fr 1fr}}

.statgrid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.fbox{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:14px;padding:10px 12px;
  background:rgba(255,255,255,.85);cursor:pointer;text-align:left;transition:all .14s}
.fbox:hover{border-color:rgba(17,69,75,.3)}
.fbox.on{border-color:rgba(18,143,142,.55);box-shadow:0 0 0 3px rgba(18,143,142,.12)}
.fbox .t{width:2rem;height:2rem;border-radius:.625rem;display:grid;place-items:center;color:#fff;flex:none}
.t-ink{background:linear-gradient(180deg,#17565d,#0a2e32)}
.t-teal{background:linear-gradient(180deg,#149897,#128F8E)}
.t-mint{background:#7FD1C0;color:#0c3539}
.t-red{background:#dc2626}
.t-amber{background:#d97706}
.t-steel{background:#46708C}
.fbox .l{font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fbox .v{font-size:15px;font-weight:800;color:var(--ink)}

.controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px}
.search{position:relative;flex:1;min-width:220px;max-width:420px}
.search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint)}
.input{width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 12px;
  font-size:13px;color:var(--ink);font-family:inherit;transition:all .15s}
.search .input{padding-left:36px}
.input:focus{outline:none;border-color:var(--focus);box-shadow:0 0 0 3px rgba(148,163,184,.18)}
select.input{width:auto;padding-right:30px;appearance:none;
  background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 11px center}
.input.h44{height:44px}
textarea.input{height:auto;min-height:4.5rem;padding:.75rem;border-radius:10px;resize:vertical}

.li-wrap{margin-top:16px;border:1px solid var(--border);border-radius:14px;background:#fafdfd;overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;min-width:860px}
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
.pagi{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--line)}
.pagi .t{font-size:12px;color:var(--muted)}
.ci{height:36px;border-radius:7px;border:1px solid var(--border);background:#fff;padding:0 9px;font-size:12.5px;width:100%;font-family:inherit;color:var(--ink)}
td.num .ci{text-align:right}

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

.prof{display:flex;align-items:center;gap:16px;padding:20px 24px}
.ava-xl{width:3.5rem;height:3.5rem;border-radius:1rem;display:grid;place-items:center;flex:none;
  background:linear-gradient(180deg,rgba(18,143,142,.16),rgba(18,143,142,.08));border:1px solid rgba(18,143,142,.3);color:var(--sec)}
.prof .n{font-size:17px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.prof .c{margin-top:5px;display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:var(--muted)}
.prof .c span{display:inline-flex;align-items:center;gap:6px}

.tabs{display:flex;gap:2px;border-bottom:1px solid var(--line);padding:0 18px;background:rgba(255,255,255,.6);overflow-x:auto}
.tab{padding:12px 14px;border:none;background:none;cursor:pointer;position:relative;font-family:inherit;font-size:12.5px;font-weight:700;color:var(--muted);white-space:nowrap}
.tab::after{content:"";position:absolute;left:12px;right:12px;bottom:-1px;height:2.5px;border-radius:3px;background:transparent}
.tab.on{color:var(--ink)}.tab.on::after{background:var(--sec)}
.pane{display:none}.pane.on{display:block}

.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px 20px;margin-top:16px}
.sp2{grid-column:span 2}
.field label{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.field .hint{margin-top:6px;font-size:10.5px;color:var(--faint)}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
@media (max-width:900px){.g3{grid-template-columns:1fr 1fr}}
.fld .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fld .v{margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)}

.tree{list-style:none;margin-top:10px}
.tree li{position:relative;padding:6px 0 6px 22px;font-size:12.5px;font-weight:600;color:#374151}
.tree li::before{content:"";position:absolute;left:6px;top:0;bottom:0;width:1.5px;background:var(--line)}
.tree li::after{content:"";position:absolute;left:6px;top:16px;width:10px;height:1.5px;background:var(--line)}
.tree > li{padding-left:0}.tree > li::before,.tree > li::after{display:none}
.tree .cnt{font-size:10px;font-weight:800;color:var(--sec);background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.35);border-radius:999px;padding:1px 8px;margin-left:6px}

.attchips{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.att{display:inline-flex;align-items:center;gap:7px;height:32px;padding:0 12px;border-radius:10px;background:rgba(255,255,255,.9);
  border:1px solid var(--border);font-size:11.5px;font-weight:600;color:#374151}
.att .exp{font-size:9.5px;font-weight:800;border-radius:999px;padding:2px 8px}
.exp.ok{background:rgba(22,163,74,.10);color:var(--green)}
.exp.warn{background:rgba(217,119,6,.12);color:var(--amber-2)}
.exp.bad{background:rgba(185,28,28,.08);color:var(--red-2)}
.audit{display:flex;flex-direction:column}
.arow{display:flex;gap:12px;padding:9px 2px;border-bottom:1px solid var(--line);font-size:12px}
.arow:last-child{border-bottom:none}
.arow .when{width:110px;flex:none;color:var(--faint);font-family:ui-monospace,Menlo,monospace;font-size:10.5px}
.arow .who{width:80px;flex:none;font-weight:700;color:var(--ink)}
.arow .what{color:var(--muted)}.arow .what b{color:var(--ink)}

.repcards{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
@media (max-width:1000px){.repcards{grid-template-columns:1fr 1fr}}
@media (max-width:700px){.repcards{grid-template-columns:1fr}}
.repcard{border:1px solid var(--border);border-radius:14px;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card);padding:16px;display:flex;flex-direction:column;gap:8px}
.repcard .t{font-size:14px;font-weight:800;color:var(--ink)}
.repcard .d{font-size:11.5px;color:var(--muted);line-height:1.5}
.repcard .foot{margin-top:auto;display:flex;gap:6px;align-items:center;padding-top:6px}
.fmt{font-size:9.5px;font-weight:800;letter-spacing:.06em;color:var(--deep-1);background:rgba(17,69,75,.06);
  border:1px solid rgba(17,69,75,.16);border-radius:999px;padding:2px 8px}
.open-l{margin-left:auto;font-size:11px;font-weight:800;color:var(--sec);text-decoration:none}
.open-l:hover{text-decoration:underline}

@media (max-width:1100px){.statgrid{grid-template-columns:repeat(3,1fr)}}
@media (max-width:768px){.statgrid{grid-template-columns:repeat(2,1fr)}.g4{grid-template-columns:1fr 1fr}.sp2{grid-column:1/-1}}
```

### 3.1 — Screen 1: Dashboard (route: `inventory.dashboard`)
```html
<div class="page-head">
  <div><h1>Inventory Centre</h1><div class="sub">Products, stock, pricing, costing and movements — connected to Sales, Purchasing and the GL.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">📊 View Reports</button>
    <button class="btn btn-ghost btn-sm">📤 Export Items</button>
    <button class="btn btn-ghost btn-sm">📥 Import Items</button>
    <button class="btn btn-cta btn-sm">➕ Add Item</button>
  </div>
</div>

<div class="kpis" style="margin-bottom:12px">
  <div class="kpi hero"><div class="l">Total Inventory Value</div><div class="v">{currency} {total value}</div><div class="n" style="color:#dff7f6">{valuation method} valuation · {n} warehouses</div></div>
  <div class="kpi"><div class="l">Total Items</div><div class="v">{count}</div><div class="n">{active} active · {services} services</div></div>
  <div class="kpi warn"><div class="l">Low Stock</div><div class="v">{count}</div><div class="n"><a class="open-l" href="#">Reorder suggestions →</a></div></div>
  <div class="kpi red"><div class="l">Out of Stock</div><div class="v">{count}</div><div class="n"><a class="open-l" href="#">View →</a></div></div>
</div>
<div class="kpis" style="margin-bottom:16px">
  <div class="kpi"><div class="l">Fast Moving</div><div class="v">{count}</div><div class="n">top turnover, 30 days</div></div>
  <div class="kpi"><div class="l">Slow Moving</div><div class="v">{count}</div><div class="n">no movement 90+ days</div></div>
  <div class="kpi warn"><div class="l">Expiring (30d)</div><div class="v">{count}</div><div class="n">batches · {value} value</div></div>
  <div class="kpi"><div class="l">Stock Movements (7d)</div><div class="v">{count}</div><div class="n">{in} in · {out} out</div></div>
</div>

<section class="card">
  <div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Recent Stock Movements</h2>
    <a class="open-l" href="#" style="margin-left:auto">View all →</a></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:11%">Date</th><th style="width:13%">Reference</th><th style="width:26%">Item</th>
      <th style="width:14%">Type</th><th class="num" style="width:10%">Qty</th><th style="width:14%">Warehouse</th><th class="num" style="width:12%">Value (K)</th></tr></thead>
    <tbody>
      <!-- one row per recent movement; type chip colored per §2 (Receipt green, Issue red, Transfer steel, Adjustment gray); qty +green / −red -->
      <tr><td class="em">{date}</td><td class="mono">{reference}</td><td style="font-weight:600;color:var(--ink)">{item}</td>
        <td><span class="tchip" style="background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.35);color:var(--green)">{type}</span></td>
        <td class="numr green">+{qty}</td><td class="em">{warehouse}</td><td class="numr">{value}</td></tr>
    </tbody></table></div>
</section>
```

### 3.2 — Screen 2: Items List (route: `inventory.items`)
```html
<div class="page-head">
  <div><h1>Inventory Items</h1><div class="sub">All products, stock items and services with pricing, costing and stock status.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <div class="more"><button class="btn btn-ghost btn-sm">⋯ More</button>
      <div class="more-menu"><button class="more-item">📥 Import Items</button><button class="more-item">📤 Export Items</button><button class="more-item">🖨 Print Barcodes</button><button class="more-item">⚙ Inventory Settings</button></div></div>
    <button class="btn btn-cta btn-sm">＋ Add New Item</button>
  </div>
</div>

<section class="card">
  <div class="card-sec">
    <div class="statgrid">
      <!-- fbox.on marks active status filter; click sets the existing filter param; live counts -->
      <button class="fbox on"><span class="t t-ink"><!-- box icon --></span><span><span class="l">All</span><span class="v" style="display:block">{count}</span></span></button>
      <button class="fbox"><span class="t t-mint"><!-- check icon --></span><span><span class="l">Active</span><span class="v" style="display:block">{count}</span></span></button>
      <button class="fbox"><span class="t t-amber"><!-- warn icon --></span><span><span class="l">Low Stock</span><span class="v" style="display:block">{count}</span></span></button>
      <button class="fbox"><span class="t t-red"><!-- x icon --></span><span><span class="l">Out of Stock</span><span class="v" style="display:block">{count}</span></span></button>
      <button class="fbox"><span class="t t-steel"><!-- service icon --></span><span><span class="l">Services</span><span class="v" style="display:block">{count}</span></span></button>
    </div>
    <div class="controls">
      <div class="search"><!-- search icon --><input class="input" placeholder="Item name, SKU or barcode…" value="{current search param}"></div>
      <select class="input"><option>All Categories</option><!-- real categories --></select>
      <select class="input"><option>All Suppliers</option><!-- real suppliers --></select>
      <select class="input"><option>All Warehouses</option><!-- real warehouses --></select>
    </div>
  </div>
  <div class="card-sec" style="padding-top:6px">
    <div class="li-wrap"><table>
      <thead><tr><th style="width:19%">SKU / Item</th><th style="width:12%">Category · Brand</th><th style="width:8%">Type</th>
        <th style="width:6%">Unit</th><th class="num" style="width:10%">Purchase (K)</th><th class="num" style="width:10%">Selling (K)</th>
        <th class="num" style="width:8%">Avail.</th><th class="num" style="width:8%">Reorder</th><th style="width:11%">Warehouse</th>
        <th style="width:10%">Status</th><th style="width:7%"></th></tr></thead>
      <tbody>
        <!-- one row per item; Avail. numr class per §2.3: "red" when 0, "amber" when ≤ reorder, else plain; service rows show em-dashes for stock-related columns -->
        <tr><td><span class="mono">{sku}</span><div style="font-weight:700;color:var(--ink)">{item name}</div></td>
          <td class="em">{category} · {brand}</td><td><span class="tchip">{type}</span></td><td class="em">{unit}</td>
          <td class="numr">{purchase cost}</td><td class="numr">{selling price}</td>
          <td class="numr bold">{available}</td><td class="numr">{reorder level}</td><td class="em">{warehouse}</td>
          <td><span class="badge b-act"><span class="bdot"></span>{status}</span></td>
          <td><div class="row-act"><button class="ibtn">👁</button>
            <span class="more"><button class="ibtn">⋯</button>
              <div class="more-menu"><button class="more-item">✎ Edit</button><button class="more-item">⧉ Duplicate</button>
                <button class="more-item">⇄ Adjust Stock</button><button class="more-item">⇄ Transfer Stock</button>
                <button class="more-item">🖨 Print Barcode</button><button class="more-item danger">⏸ Deactivate</button></div></span></div></td></tr>
      </tbody></table></div>
  </div>
  <div class="pagi"><span class="t">Showing {n} of {total} items</span>
    <div style="display:flex;gap:8px"><button class="btn btn-ghost btn-sm">← Prev</button><button class="btn btn-ghost btn-sm">Next →</button></div></div>
</section>
```

### 3.3 — Screen 3: Add New Item / Edit Item (routes: `inventory.create` / `inventory.edit`)
```html
<div class="sticky-head">
  <div><h1>Add New Inventory Item</h1><div class="sub">Products, stock items and services with pricing, costing and stock control.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">Cancel</button>
    <div class="seg"><button class="btn btn-ghost">Save &amp; Add Another</button><button class="btn btn-cta">Save Item</button></div>
  </div>
</div>

<section class="card">
  <div class="card-sec">
    <div class="sec-head"><span class="sec-ic"><!-- box icon --></span><h2>Basic Information</h2><span class="rule"></span></div>
    <div class="g4">
      <div class="field sp2"><label>Item Name *</label><input class="input h44" placeholder="e.g. Laptop HP 250 G8"></div>
      <div class="field"><label>Item Code / SKU</label><input class="input h44" value="Auto generated" disabled></div>
      <div class="field"><label>Barcode</label>
        <div class="combo"><div class="in-wrap"><input class="input h44" placeholder="Scan or generate"></div>
          <button title="Generate barcode">▮||</button></div></div>
      <div class="field"><label>Item Type</label><select class="input h44"><option>Inventory Item</option><option>Non-inventory Item</option><option>Service</option></select></div>
      <div class="field"><label>Category</label><select class="input h44"><!-- real categories --></select></div>
      <div class="field"><label>Brand</label><input class="input h44" placeholder="Optional"></div>
      <div class="field"><label>Image</label><button class="btn btn-ghost btn-sm" style="height:44px">🖼 Upload image</button></div>
      <div class="field sp2"><label>Description</label><textarea class="input" placeholder="Optional description"></textarea></div>
    </div>
  </div>

  <div class="card-sec">
    <div class="sec-head"><span class="sec-ic"><!-- price tag icon --></span><h2>Pricing Information</h2><span class="rule"></span>
      <button class="btn btn-ghost btn-xs" style="margin-left:auto">＋ Add Price Level</button></div>
    <div class="g4">
      <div class="field"><label>Purchase Cost (K)</label><input class="input h44" value="0.00"></div>
      <div class="field"><label>Selling Price (K)</label><input class="input h44" value="0.00"></div>
      <div class="field"><label>Wholesale Price (K)</label><input class="input h44" value="0.00"></div>
      <div class="field"><label>Minimum Selling Price</label><input class="input h44" value="0.00"></div>
      <div class="field"><label>Tax Settings</label><select class="input h44"><!-- real tax settings --></select></div>
      <div class="field"><label>Discount Rules</label><select class="input h44"><option>None</option><!-- real discount rules --></select></div>
    </div>
    <!-- price-level table (level, price, margin, tax) appears here when "＋ Add Price Level" rows exist, styled with the .li-wrap/table pattern -->
  </div>

  <div class="card-sec">
    <div class="sec-head"><span class="sec-ic"><!-- warehouse icon --></span><h2>Inventory Information</h2><span class="rule"></span></div>
    <div class="g4">
      <div class="field"><label>Opening Quantity</label><input class="input h44" value="0"></div>
      <div class="field"><label>Reorder Level</label><input class="input h44" value="0"></div>
      <div class="field"><label>Maximum Stock Level</label><input class="input h44" value="0"></div>
      <div class="field"><label>Stock Valuation Method</label><select class="input h44"><option>Average Cost</option><option>FIFO</option><option>Standard Cost</option></select></div>
      <div class="field"><label>Warehouse Location</label><select class="input h44"><!-- real warehouses --></select></div>
      <div class="field"><label>Bin Location</label><input class="input h44" placeholder="Aisle / Rack / Shelf"></div>
      <div class="field"><label>Track Serial Numbers</label><select class="input h44"><option>No</option><option>Yes</option></select></div>
      <div class="field"><label>Track Batch / Expiry</label><select class="input h44"><option>No</option><option>Yes</option></select></div>
    </div>
  </div>

  <div class="card-sec">
    <div class="sec-head"><span class="sec-ic"><!-- supplier icon --></span><h2>Supplier Information</h2><span class="rule"></span>
      <div style="margin-left:auto;display:flex;gap:8px"><button class="btn btn-ghost btn-xs">＋ Add Supplier</button><button class="btn btn-ghost btn-xs">View Supplier</button></div></div>
    <div class="g4">
      <div class="field sp2"><label>Preferred Supplier</label>
        <div class="combo"><div class="in-wrap"><!-- search icon --><input class="input h44" placeholder="Search suppliers…"></div><button>🔍</button></div></div>
      <div class="field"><label>Supplier Code</label><input class="input h44" disabled value="—"></div>
      <div class="field"><label>Supplier Price (K)</label><input class="input h44" value="0.00"></div>
    </div>
  </div>
</section>
```
Edit uses the identical layout, pre-filled with saved values; the header becomes "Edit Item
{code}" and adds Deactivate (with confirm). Delete is blocked when movements exist — surface
that message rather than failing silently.

### 3.4 — Screen 4: Item Profile (route: `inventory.show`) — header standard, 10-tab card
```html
<div class="page-head" style="padding-top:10px">
  <div style="display:flex;align-items:center;gap:10px">
    <button class="icon-btn" aria-label="Back to items"><!-- chevron-left icon --></button>
    <nav class="crumbs"><a href="#">Inventory</a> › <a href="#">Items</a> › <span class="here">{item code}</span></nav>
  </div>
  <div class="cluster">
    <button class="btn btn-ghost btn-sm">✎ Edit</button>
    <button class="btn btn-ghost btn-sm">⇄ Adjust Stock</button>
    <button class="btn btn-ghost btn-sm">⇄ Transfer</button>
    <button class="btn btn-ghost btn-sm">🖨 Print Barcode</button>
    <div class="more"><button class="btn btn-ghost btn-sm">⋯ More</button>
      <div class="more-menu"><button class="more-item">⧉ Duplicate</button><button class="more-item">📄 New Purchase Order</button>
        <button class="more-item">🖨 Print Labels</button><button class="more-item">⌨ Scan Item</button>
        <button class="more-item danger">⏸ Deactivate</button></div></div>
  </div>
</div>

<div style="display:flex;flex-direction:column;gap:20px">
  <section class="card">
    <div class="prof">
      <span class="ava-xl"><!-- box icon --></span>
      <div>
        <div class="n">{item name} <span class="mono-chip">{code}</span> <span class="badge b-act"><span class="bdot"></span>{status}</span></div>
        <div class="c"><span>{category} · {brand}</span><span>{unit}</span><span>{valuation method}</span><span>{warehouse} · {bin}</span><span>Barcode {barcode}</span></div>
      </div>
    </div>
  </section>

  <div class="sumbar" aria-label="Stock summary">
    <div class="cell"><div class="l">Available</div><div class="v">{available}</div><div class="n">on hand {on hand} · reserved {reserved}</div></div>
    <div class="cell"><div class="l">Reorder Level</div><div class="v">{reorder level}</div><div class="n">max {max stock level}</div></div>
    <div class="cell"><div class="l">Avg Cost</div><div class="v">{avg cost}</div><div class="n">FIFO layers: {n}</div></div>
    <div class="cell hero"><div class="l">Stock Value</div><div class="v">K{stock value}</div></div>
  </div>

  <section class="card">
    <div class="tabs" id="itabs" role="tablist">
      <button class="tab on" data-pane="i-over" role="tab">Overview</button>
      <button class="tab" data-pane="i-stock" role="tab">Stock Information</button>
      <button class="tab" data-pane="i-sales" role="tab">Sales History</button>
      <button class="tab" data-pane="i-pur" role="tab">Purchase History</button>
      <button class="tab" data-pane="i-mov" role="tab">Stock Movements</button>
      <button class="tab" data-pane="i-sup" role="tab">Suppliers</button>
      <button class="tab" data-pane="i-price" role="tab">Pricing</button>
      <button class="tab" data-pane="i-serial" role="tab">Serial Numbers</button>
      <button class="tab" data-pane="i-docs" role="tab">Documents</button>
      <button class="tab" data-pane="i-aud" role="tab">Audit Trail</button>
    </div>

    <!-- Overview: read-only g3 grid -->
    <div class="pane on" id="i-over"><div class="card-sec"><div class="g3">
      <div class="fld"><div class="l">Item Code</div><div class="v">{code}</div></div>
      <div class="fld"><div class="l">Type</div><div class="v">{type}</div></div>
      <div class="fld"><div class="l">Category / Brand</div><div class="v">{category} / {brand}</div></div>
      <div class="fld"><div class="l">Unit</div><div class="v">{unit} ({conversion note})</div></div>
      <div class="fld"><div class="l">Valuation</div><div class="v">{method}</div></div>
      <div class="fld"><div class="l">Tax</div><div class="v">{tax setting}</div></div>
      <div class="fld"><div class="l">Preferred Supplier</div><div class="v">{supplier} · {supplier code}</div></div>
      <div class="fld"><div class="l">Last Purchase</div><div class="v">{date} · {cost}</div></div>
      <!-- green when margin is healthy per app convention -->
      <div class="fld"><div class="l">Margin</div><div class="v" style="color:var(--green)">{margin}%</div></div>
    </div></div></div>

    <!-- Stock Information: per-warehouse table with tfoot totals -->
    <div class="pane" id="i-stock"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:26%">Warehouse</th><th style="width:20%">Bin</th><th class="num" style="width:13%">On Hand</th>
        <th class="num" style="width:13%">Reserved</th><th class="num" style="width:14%">Available</th><th class="num" style="width:14%">Value (K)</th></tr></thead>
      <tbody><!-- one row per warehouse this item has stock in -->
        <tr><td style="font-weight:600;color:var(--ink)">{warehouse}</td><td class="em">{bin}</td><td class="numr">{on hand}</td>
          <td class="numr">{reserved}</td><td class="numr bold">{available}</td><td class="numr">{value}</td></tr>
      </tbody>
      <tfoot><tr><td colspan="2">Total</td><td class="numr">{sum on hand}</td><td class="numr">{sum reserved}</td><td class="numr">{sum available}</td><td class="numr">{sum value}</td></tr></tfoot>
    </table></div></div></div>

    <!-- Sales History -->
    <div class="pane" id="i-sales"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:13%">Date</th><th style="width:16%">Reference</th><th style="width:27%">Customer</th>
        <th class="num" style="width:11%">Qty</th><th class="num" style="width:16%">Price (K)</th><th class="num" style="width:17%">Total (K)</th></tr></thead>
      <tbody><!-- reference links to the real sales invoice -->
        <tr><td class="em">{date}</td><td class="mono">{reference}</td><td class="em">{customer}</td>
          <td class="numr">{qty}</td><td class="numr">{price}</td><td class="numr">{total}</td></tr>
      </tbody></table></div></div></div>

    <!-- Purchase History -->
    <div class="pane" id="i-pur"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:13%">Date</th><th style="width:16%">Reference</th><th style="width:27%">Supplier</th>
        <th class="num" style="width:11%">Qty</th><th class="num" style="width:16%">Cost (K)</th><th class="num" style="width:17%">Total (K)</th></tr></thead>
      <tbody><!-- reference links to the real PO/GRN -->
        <tr><td class="em">{date}</td><td class="mono">{reference}</td><td class="em">{supplier}</td>
          <td class="numr">{qty}</td><td class="numr">{cost}</td><td class="numr">{total}</td></tr>
      </tbody></table></div></div></div>

    <!-- Stock Movements: running balance bold -->
    <div class="pane" id="i-mov"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:12%">Date</th><th style="width:15%">Reference</th><th style="width:15%">Type</th>
        <th class="num" style="width:10%">In</th><th class="num" style="width:10%">Out</th><th class="num" style="width:12%">Balance</th><th style="width:20%">Warehouse</th></tr></thead>
      <tbody><!-- type chip per §2 colors; running balance computed live, never stored -->
        <tr><td class="em">{date}</td><td class="mono">{reference}</td>
          <td><span class="tchip" style="background:rgba(185,28,28,.08);border-color:rgba(185,28,28,.3);color:var(--red-2)">{type}</span></td>
          <td class="dash">{in or —}</td><td class="numr red">{out or —}</td><td class="numr bold">{running balance}</td><td class="em">{warehouse}</td></tr>
      </tbody></table></div></div></div>

    <!-- Suppliers: ★ marks the preferred supplier -->
    <div class="pane" id="i-sup"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:30%">Supplier</th><th style="width:16%">Code</th><th class="num" style="width:16%">Last Price (K)</th><th style="width:16%">Last Purchase</th><th style="width:14%">Lead Time</th></tr></thead>
      <tbody><tr><td style="font-weight:600;color:var(--ink)">{supplier} {★ preferred if applicable}</td><td class="mono">{code}</td>
        <td class="numr">{last price}</td><td class="em">{last purchase date}</td><td class="em">{lead time}</td></tr>
      </tbody></table></div></div></div>

    <!-- Pricing: level table -->
    <div class="pane" id="i-price"><div class="card-sec"><div class="li-wrap" style="margin-top:0"><table>
      <thead><tr><th style="width:30%">Price Level</th><th class="num" style="width:20%">Price (K)</th><th class="num" style="width:20%">Margin</th><th style="width:20%">Tax</th></tr></thead>
      <tbody><tr><td style="font-weight:600;color:var(--ink)">{level}</td><td class="numr">{price}</td>
        <td class="numr green">{margin}%</td><td class="em">{tax}</td></tr>
      </tbody></table></div></div></div>

    <!-- Serial Numbers: state chips -->
    <div class="pane" id="i-serial"><div class="card-sec"><div class="attchips" style="margin-top:0">
      <!-- exp class ok (in stock, green) / warn (reserved, amber) / bad (sold, red + ref) -->
      <span class="att"># {serial number} <span class="exp ok">in stock</span></span>
      <button class="btn btn-ghost btn-xs">＋ Add Serial Numbers</button>
      <button class="btn btn-ghost btn-xs">⌨ Scan</button>
    </div></div></div>

    <!-- Documents -->
    <div class="pane" id="i-docs"><div class="card-sec"><div class="attchips" style="margin-top:0">
      <span class="att">📎 {filename}</span>
      <button class="btn btn-ghost btn-xs">📤 Upload</button>
    </div></div></div>

    <!-- Audit Trail: includes price changes and GRN receipts with user + timestamp -->
    <div class="pane" id="i-aud"><div class="card-sec"><div class="audit">
      <div class="arow"><span class="when">{date}</span><span class="who">{actor}</span><span class="what">{human-readable action, e.g. "Selling price changed <b>1,100,000 → 1,150,000</b>"}</span></div>
    </div></div></div>
  </section>
</div>
```
Tabs switch panes client-side without a page reload (`role="tablist"` on the tab row,
`role="tab"` on each button).

### 3.5 — Screen 5: Stock Management (route: `inventory.stock`)
```html
<div class="page-head">
  <div><h1>Stock Management</h1><div class="sub">Corrections, damage, expiry write-offs and warehouse-to-warehouse movements.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-sec btn-sm">⇄ New Transfer</button>
    <button class="btn btn-cta btn-sm">⇄ Adjust Quantity</button>
  </div>
</div>

<section class="card" style="margin-bottom:16px">
  <div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Stock Adjustments</h2><span class="fmt" style="margin-left:auto">approval required</span></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:12%">Adj №</th><th style="width:11%">Date</th><th style="width:22%">Item</th>
      <th style="width:14%">Reason</th><th class="num" style="width:9%">Qty</th><th class="num" style="width:11%">Value (K)</th>
      <th style="width:11%">Status</th><th style="width:12%"></th></tr></thead>
    <tbody>
      <!-- reason: Damage / Correction / Expired / Missing; row actions: Pending → [Approve][Print Adjustment Note]; Approved → [Print Adjustment Note] only -->
      <tr><td class="mono">{adj #}</td><td class="em">{date}</td><td style="font-weight:600;color:var(--ink)">{item}</td>
        <td class="em">{reason}</td><td class="numr red">−{qty}</td><td class="numr">{value}</td>
        <td><span class="badge b-pend"><span class="bdot"></span>{status}</span></td>
        <td><div class="row-act"><button class="btn btn-sec btn-xs">Approve</button><button class="btn btn-ghost btn-xs">Note</button></div></td></tr>
    </tbody></table></div>
</section>

<section class="card">
  <div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Stock Transfers</h2><span class="fmt" style="margin-left:auto">approve → receive</span></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table>
    <thead><tr><th style="width:12%">TRF №</th><th style="width:11%">Date</th><th style="width:22%">Item</th>
      <th style="width:20%">From → To</th><th class="num" style="width:8%">Qty</th><th style="width:12%">Status</th><th style="width:15%"></th></tr></thead>
    <tbody>
      <!-- row actions per status: Pending → [Approve]; In Transit → [Receive]; Received → [View] -->
      <tr><td class="mono">{trf #}</td><td class="em">{date}</td><td style="font-weight:600;color:var(--ink)">{item}</td>
        <td class="em">{from} → {to}</td><td class="numr">{qty}</td>
        <td><span class="badge b-pend"><span class="bdot"></span>{status}</span></td>
        <td><div class="row-act"><button class="btn btn-sec btn-xs">Receive</button></div></td></tr>
    </tbody></table></div>
</section>
```
Approving an adjustment or receiving a transfer posts the movement (and GL entry, for
adjustments) via the existing approval/receive handlers — don't reimplement this posting logic.

### 3.6 — Screen 6: Configuration (route: `inventory.config`)
```html
<div class="page-head">
  <div><h1>Inventory Configuration</h1><div class="sub">Categories, units of measure, bundles and batch control.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">＋ Add Unit</button>
    <button class="btn btn-ghost btn-sm">＋ Add Category</button>
    <button class="btn btn-cta btn-sm">📦 Create Bundle</button>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="cfggrid">
  <style>@media (max-width:1000px){.cfggrid{grid-template-columns:1fr!important}}</style>

  <div class="card"><div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Item Categories</h2><span class="fmt" style="margin-left:auto">hierarchy</span></div>
    <div class="card-sec">
      <!-- real category hierarchy tree; .cnt shows live item count per node -->
      <ul class="tree">
        <li><b>{parent category}</b><span class="cnt">{count}</span>
          <ul class="tree"><li>{child category}<span class="cnt">{count}</span></li></ul></li>
      </ul>
    </div></div>

  <div class="card"><div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Units of Measure</h2><button class="btn btn-ghost btn-xs" style="margin-left:auto">Configure Conversion</button></div>
    <div class="li-wrap" style="margin-top:0;border:none;border-radius:0"><table style="min-width:0">
      <thead><tr><th>Unit</th><th>Base</th><th class="num">Conversion</th></tr></thead>
      <tbody><tr><td style="font-weight:600;color:var(--ink)">{unit}</td><td class="em">{base unit or —}</td><td class="numr">{conversion factor}</td></tr></tbody>
    </table></div></div>

  <div class="card"><div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Item Bundles / Kits</h2><button class="btn btn-ghost btn-xs" style="margin-left:auto">Manage Components</button></div>
    <div class="card-sec">
      <!-- one bundle block per bundle -->
      <div style="font-weight:800;color:var(--ink);font-size:13.5px">{bundle name} <span class="tchip" style="margin-left:6px">bundle price K{price}</span></div>
      <div class="attchips"><!-- one .att chip per component --><span class="att">{component name} ×{qty}</span></div>
      <div class="field" style="margin-top:10px"><span class="hint" style="font-size:10.5px;color:var(--faint)">Selling the bundle auto-deducts each component from stock.</span></div>
    </div></div>

  <div class="card"><div class="card-h" style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)">
    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Serial / Batch Tracking</h2><button class="btn btn-ghost btn-xs" style="margin-left:auto">View Batch History</button></div>
    <div class="card-sec">
      <!-- exp class warn (≤30d, amber) / ok / bad (expired, red) -->
      <div class="attchips" style="margin-top:0">
        <span class="att">{batch #} · {item} <span class="exp warn">exp {date}</span></span>
        <span class="att"># Serial-tracked: {category list} <span class="exp ok">warranty {n}m</span></span>
      </div>
    </div></div>
</div>
```

### 3.7 — Screen 7: Reports (route: `inventory.reports`)
```html
<div class="page-head">
  <div><h1>Inventory Reports</h1><div class="sub">Stock, valuation, movement, aging and item performance reporting.</div></div>
  <button class="btn btn-ghost btn-sm">⇩ Export All</button>
</div>

<div class="repcards">
  <div class="repcard"><span class="t">Stock Valuation Report</span><span class="d">Value per item/warehouse by FIFO / average / standard cost.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Stock Movement Report</span><span class="d">All receipts, issues, transfers and adjustments for a period.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Inventory Summary</span><span class="d">On hand, reserved, available and value by category/warehouse.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Stock Aging Report</span><span class="d">Stock by age band; flags slow and obsolete lines.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Low Stock Report</span><span class="d">Items at or below reorder level with suggested reorder qty.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Out of Stock Report</span><span class="d">Zero-stock items with last movement and supplier.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Item Sales Report</span><span class="d">Quantities and revenue sold per item; most-sold ranking.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Item Purchase Report</span><span class="d">Purchase volumes and cost trends per item/supplier.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <div class="repcard"><span class="t">Profit Margin Report</span><span class="d">Cost vs selling price margins by item and category.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
</div>
```
Each card opens its existing report page if present, else a minimal page using the app's
standard report pattern (filter bar + table + totals footer + exports; Generate/Print/Export
PDF/Export Excel buttons) — computed from the live movement ledger only, never hardcoded data.

## 4 · Accessibility & responsive
- Status boxes (`.fbox`) use `aria-pressed`.
- Profile tabs use `role="tablist"`/`role="tab"` semantics.
- `⋯` more-menus use `aria-haspopup`.
- Breadcrumb uses proper `nav`/`a` semantics; focus rings use `--focus` (#94a3b8); table headers use `<th scope="col">`.
- Quantity colors (red/amber per §2.3) are always backed by the numeric value itself, not color alone.
- Breakpoints (already encoded in the CSS in §3): `.statgrid` goes 3-col ≤1100px, 2-col
  ≤768px; KPI rows go 2-col ≤1000px; the configuration grid stacks ≤1000px; report cards go
  2-then-1 column at 1000px/700px; `.g4` goes 1fr 1fr with `.sp2` spanning ≤768px; tables
  scroll horizontally inside their cards. No horizontal page scrollbar at 1280/1024/768.

## 5 · Integration & valuation rules (UI surfaces only — reuse existing handlers, don't rebuild)
- Movement sources, valuation method, reorder suggestions, multi-warehouse behavior, and
  deactivate/delete rules are all as described in §2 — implement the UI to surface and trigger
  these, using the app's existing engines.

## 6 · Screen with no mockup — build from the functional spec using §3's components
**Inventory Settings** (route `inventory.settings`): a page of cards (using the `.card`
pattern from §3) for SKU numbering format, barcode settings, default stock valuation method,
reorder rules, tax rules, warehouse settings, and user permissions — each opens/edits via the
app's existing settings handlers; create a minimal page only where a given settings screen
doesn't already exist elsewhere in the app.

## 7 · Rails registry — wire this module into the existing shared rails component
Implement per-page rail content exactly as follows (reuse the real, already-built rails
component — don't hand-roll this markup):

| Page | Quick Nav / Views / Reports content |
|---|---|
| `inventory.dashboard` | Quick Nav: Items List, Stock Levels, Add Item, Inventory Reports |
| `inventory.items` | Views: All Items (active), Active, Low Stock, Out of Stock, Services — Reports: Stock Valuation, Low Stock |
| `inventory.create` / `inventory.edit` | Quick Nav: Items List, Item Categories, Suppliers |
| `inventory.show` | Quick Nav: Adjust Stock, New Purchase Order, Print Barcode, Items List |
| `inventory.stock` | Quick Nav: New Adjustment, New Transfer, Items List |
| `inventory.config` | Quick Nav: Items List, Categories, Inventory Settings |
| `inventory.reports` | Quick Nav: Items List, Stock Valuation, Low Stock |

The global "pin rails" preference and slim-rail/drawer behavior must work identically to how
they already work on every other page — this module doesn't change that feature's code at all.

## 8 · Constraints
- No changes to any other module.
- No changes to the rails feature's implementation itself — only its per-page content wiring (§7).
- No new frontend packages/frameworks.
- One shared, module-scoped CSS block reusing the app's existing tokens where they exist.
- No hardcoded sample data anywhere — dashboard, item profile, and all reports render from the live movement ledger only.
- No direct quantity edits anywhere outside the approved movement flows (§2).

---

## Verify before declaring done
- [ ] The old, less-comprehensive Inventory Centre feature is fully removed — no leftover routes, dead views, orphaned tables, or stale nav entries — and nothing else in the app links to its old routes/views. Shared services (valuation engine, GRN/invoice posting, COGS/GL mappings, approval workflow, warehouses/bins, rails) were preserved and re-wired, not deleted.
- [ ] All seven screens (Dashboard, Items List, Add/Edit Item, Item Profile with all 10 tabs, Stock Management, Configuration, Reports) plus the no-mockup Settings page (§6) match §3/§6 exactly.
- [ ] Rails render via the app's existing shared component on every page in this module, with the exact per-page content from §7.
- [ ] The app's real global header/nav was not replaced with the mockup's preview chrome.
- [ ] Items List: status boxes set the real filter param with live counts; quantity coloring (red at 0, amber at/below reorder) is correct; status-dependent `⋯` menu matches §3.2 exactly.
- [ ] Add/Edit: opening quantity, reorder level, valuation method and all other fields save correctly; Deactivate/Delete follow the existing rules (§2), with the blocking message surfaced when movements exist.
- [ ] Item Profile: tabs switch without a page reload; Stock Information tfoot totals equal the sum of per-warehouse rows; Stock Movements running balance is computed live, never stored; Serial Numbers state chips (in stock/reserved/sold) and batch expiry chips (ok/warn/bad) use the correct thresholds; Audit Trail includes price changes and GRN receipts with user + timestamp.
- [ ] Stock Management: adjustment approval and transfer receive post the real movement (and GL entry for adjustments) via the existing handlers; row actions match the exact per-status sets in §3.5.
- [ ] Configuration: category tree counts are live; unit conversions are correct; selling a bundle auto-deducts each component (verify against the existing bundle handler, don't just check the UI); batch/serial expiry chips use the correct warn/bad thresholds.
- [ ] All nine report cards open real, working destinations (minimal pages built for any that didn't already exist — report which ones) and render from live data only.
- [ ] Inventory Settings page exists and works per §6, using the app's existing settings handlers.
- [ ] Every button and action described in this document is wired to a real handler — spot-test each one individually.
- [ ] Responsive behavior matches §4; no horizontal scrollbar at 1280/1024/768.
- [ ] No console or build errors; text-size matrix 90/100/110/125% shows no clipping.

## Deliverable report
1. What was removed from the old Inventory Centre feature (routes, views, controllers, tables — and whether tables were dropped or the data was shared/preserved) and confirmation nothing else in the app still references it.
2. New files/routes created for the rebuilt module, and the nav entry (replacing the old one).
3. Action-mapping table: every button/control → the real handler/route it triggers, confirming valuation, posting, and approval calls all go through the app's existing shared services.
4. Status/badge/movement-chip mapping table used across all screens.
5. Rail registry as actually wired per page (§7).
6. Which report/settings pages were newly created as minimal pages vs. already existed.
7. Confirmation that stock quantities/values are always computed from posted movements, with no direct-edit paths outside the approved flows.
8. Confirmation that no other module/page/business logic outside this feature was changed.
