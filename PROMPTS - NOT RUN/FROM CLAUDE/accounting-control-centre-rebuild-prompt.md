# Accounting Control Centre — PARTIAL REMOVAL (16 named pages only) then FULL REBUILD to exact design

## Objective
There is already an Accounting Control Centre feature in the app today, but it does not
cover the full CRUD surface required. You are to **remove only the parts of the existing
implementation that correspond to the 16 pages listed in §1 below** (§0), then **rebuild those
16 pages from scratch** to the exact design in §3, wired to real data and the existing
accounting engine (§2).

**This is a partial removal, not a module-wide teardown.** Anything in the current
Accounting/Accounts area that is *not* one of the 16 named pages — most importantly
**Budgeting, Recurring Journals, Payroll, Banking, and Chart of Accounts (COA)** — must not be
touched, removed, or modified in any way. Those are separate, already-built features that this
rebuild only **links to** (e.g. "Recurring Journals" appears as a rail Quick Nav item, "Chart
of Accounts" is referenced by account pickers) — never rebuild their pages or logic.

## 0 · FIRST: remove only the 16 named pages' existing implementation
Before building anything new, go page by page through the exact list in §1:
- For each of the 16 pages, locate its current route, controller/handler, and view (if one
  currently exists under a different or similar name — check for it, don't assume it's
  missing).
- Locate the data model each page reads/writes: `chart_of_accounts`, `periods` (with a
  `basis` field), `fiscal_years`, `cost_centres`, `currencies`/`exchange_rates`,
  `classifications`, and the journal/ledger tables. **These are almost certainly shared with
  Budgeting, Recurring Journals, Payroll, Banking, and COA** — do not delete or alter any of
  these tables' schemas. Only remove the *page/view/controller* code specific to the 16 named
  pages, and only where it doesn't also serve a page outside this list.
- **Do not touch, search for removal candidates in, or modify anything belonging to Budgeting,
  Recurring Journals, Payroll, Banking, or Chart of Accounts management pages.** If a route or
  file is ambiguous — e.g. it looks like it might serve both a Control Centre page and one of
  these excluded features — leave it in place and instead add/adjust only the Control-Centre
  specific parts, rather than deleting anything shared.
- Remove old nav entries only for the 16 pages being rebuilt (the rebuilt versions replace
  them) — do not touch nav entries for Budgeting/Recurring/Payroll/Banking/COA.
- Confirm no other page, report, or feature links to the old versions of these 16 pages'
  routes before changing them; if something does, update that reference to the new route.
- Clean removal for the in-scope pages only — no dead code, unused routes, or orphaned views
  left behind for the parts you do touch.

Only proceed to the rebuild (§2 onward) once this partial removal is complete and you've
confirmed Budgeting, Recurring Journals, Payroll, Banking, and COA are completely unaffected.

## 1 · The 16 pages — build every one, skip none
```
acc.journals             Journal Entries · List
acc.journals.create      Journal · Create
acc.journals.show        Journal · View
acc.journals.edit        Journal · Edit (draft-only)
acc.ledger                General Ledger · View
acc.trialbalance          Trial Balance · View
acc.fiscalyears           Fiscal Years · List
acc.fiscalyears.create    Fiscal Year · Create
acc.fiscalyears.show      Fiscal Year · View/Edit
acc.periods                Accounting Periods · List
acc.costcentres            Cost Centres · List
acc.costcentres.create     Cost Centre · Create
acc.costcentres.show       Cost Centre · View/Edit
acc.rates                  Exchange Rates · List
acc.rates.create           Exchange Rate · Create/Edit
acc.classification         Account Classification · List
acc.classification.create  Classification · Create/Edit
```
(That's 17 route names for 16 conceptual pages — Exchange Rates and Classification each use
one route for both create and edit.) Do not skip any of these.

## 2 · Scope guard and business rules
- No changes to the existing journal posting handler, GL posting, period locking,
  fiscal-year close/carry-forward, COA mappings, tax/pension sources, or any
  notification/export/print handler — this rebuild is presentation + navigation only, calling
  the same engine.
- **Posting reaches the ledger only through the existing journal posting handler** — never
  write to the ledger directly from any of these 16 pages.
- **Journal workflow**: Draft → Review → Approve → Post to Ledger → Reports updated. Balancing
  (Debits = Credits) is enforced before submit/post — block Submit when unbalanced, don't just
  warn. Approval follows the existing workflow/amount thresholds.
- **Edit rule**: only `Draft` journals are editable. Posted journals are never edited — they
  must be Reversed. Surface this as a notice + disabled state on the edit page and a Reverse
  action elsewhere, not a silent block.
- **Period lock**: posting into a Closed or Locked period is blocked (override only with the
  existing override permission) — implemented as a check inside the existing posting handler,
  not a UI-only restriction.
- **Fiscal close**: Close Year locks all its periods. Carry Forward posts next-year opening
  balances and computes retained earnings automatically via the existing handler. Reopen is
  restricted to admin + writes an audit entry.
- **Revaluation**: Revalue Accounts computes FX gain/loss via the existing handler;
  multi-currency reporting reads from the rates table.
- **Classification**: accounts map to financial-statement sections; "Preview Statement" renders
  the Balance Sheet / Income Statement from this mapping; classifications and their display
  order are fully custom/reorderable.
- **Account references are by `account_id` everywhere** — the dashed display format (e.g.
  `1-01-002`) is presentation only; storage stays dash-less. Don't change this convention.
- **No hardcoded sample data** anywhere — every table/summary on every page renders from live
  data.

## 3 · Exact target design
Everything below is extracted directly from the approved HTML mockup — implement it exactly,
don't restyle or invent alternate layouts. Every static example value (`JV-009822`,
`486,500`, `CC-01`, etc.) is a placeholder — bind every one to real data.

### 3.0 — Mockup chrome vs. real page content
The mockup includes its own header (`<header class="tealbar">` — logo, brand, user chip, and
a top module nav row for Sales/Purchasing/Accounts/Banking/Reports) purely so it could preview
standalone. **Do not implement or replace the app's real global header/nav with that markup.**
Keep the app's actual existing global header exactly as it is. The mockup's rail markup
(`.slim-rail`) represents the app's already-built shared rails component — reuse that real
component with this module's content (§6), don't reimplement rail CSS/behavior from this file.
Everything else in each screen below is real page content to implement.

### CSS (shared page styles — implement once, scope to these 16 pages)
Reuse the app's existing tokens where they already exist (`--deep-1`, `--sec`, `--ink`,
`--border`, `--muted`, etc.) — don't create duplicates. Add any not already defined:

```css
:root{
  --deep-1:#17565d;--deep-2:#0c3539;--sec:#128F8E;--sec-2:#149897;--ink:#0B2A2D;
  --border:#dceaea;--line:#e2ecec;--muted:#5f7476;--faint:#8aa5a7;--focus:#94a3b8;
  --red-2:#b91c1c;--green:#15803d;--amber-2:#b45309;--steel:#46708C;
  --shadow-card:0 1px 2px rgba(10,42,46,.04),0 10px 30px -10px rgba(10,42,46,.10),0 30px 60px -30px rgba(8,40,44,.12);
  --shadow-cta:0 1px 2px rgba(6,32,35,.30),0 10px 20px -10px rgba(12,53,57,.60),inset 0 1px 0 rgba(255,255,255,.12);
  --shadow-teal:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
}
html,body{overflow-x:clip}
.wrap{max-width:1440px;margin:0 auto;padding:0 28px 80px}
.crumbs{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--muted)}
.crumbs a{color:var(--muted);text-decoration:none}.crumbs a:hover{color:var(--ink)}.crumbs .here{color:var(--ink);font-weight:800}
.page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:24px 0 14px}
.page-head h1{font-size:21px;font-weight:800;color:var(--ink)}
.page-head .sub{font-size:12.5px;color:var(--muted);margin-top:4px}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 20px;border-radius:13px;
  font-weight:600;font-size:13.5px;border:1px solid transparent;cursor:pointer;transition:all .18s;white-space:nowrap;font-family:inherit}
.btn:hover{transform:translateY(-1px)}
.btn-ghost{background:rgba(255,255,255,.9);border-color:var(--border);color:#374151}
.btn-ghost:hover{border-color:rgba(17,69,75,.3);color:var(--ink)}
.btn-sec{color:#fff;background:linear-gradient(180deg,var(--sec-2),var(--sec));border-color:rgba(255,255,255,.25);box-shadow:var(--shadow-teal)}
.btn-cta{color:#eaffff;background:linear-gradient(180deg,var(--deep-1),var(--deep-2) 55%,#0a2e32);border-color:rgba(255,255,255,.14);box-shadow:var(--shadow-cta);font-weight:700}
.btn-danger-o{background:#fff;border-color:rgba(220,38,38,.35);color:#dc2626}
.btn-sm{height:38px;padding:0 15px;font-size:12.5px;border-radius:11px}
.btn-xs{height:30px;padding:0 11px;font-size:11.5px;border-radius:9px}

.card{background:rgba(255,255,255,.85);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-card);overflow:hidden}
.card-h{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
.card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.pad{padding:22px 26px}
.mono{font-family:ui-monospace,Menlo,monospace}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px 22px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:16px 22px}
@media (max-width:1000px){.g4{grid-template-columns:1fr 1fr}.g2{grid-template-columns:1fr}}
@media (max-width:640px){.g4{grid-template-columns:1fr}}
.f label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.f .in{width:100%;height:44px;border-radius:12px;border:1px solid var(--border);background:#fff;padding:0 14px;font-size:13.5px;color:var(--ink);font-family:inherit}
.f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.14)}
.f .in:disabled{background:rgba(238,244,244,.7);color:var(--muted)}
.f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 14px center;padding-right:34px}

.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.05em}
.badge .bdot{width:6px;height:6px;border-radius:50%}
.b-open{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}.b-open .bdot{background:#22c55e}
.b-closed{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-closed .bdot{background:var(--muted)}
.b-locked{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-locked .bdot{background:var(--red-2)}
.b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:var(--amber)}
.b-post{background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.35);color:var(--sec)}.b-post .bdot{background:var(--sec)}
.b-draft{background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);color:#11454b}.b-draft .bdot{background:#11454b}
.tchip{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);color:var(--muted)}
.okchip{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:800;background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.35);color:var(--green)}
.okchip.bad{background:rgba(185,28,28,.08);border-color:rgba(185,28,28,.3);color:var(--red-2)}

.li-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;min-width:820px}
thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
thead th.num{text-align:right}
tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle}
tbody tr:hover td{background:rgba(17,69,75,.04)}
tbody tr:last-child td{border-bottom:none}
tfoot td{padding:11px 12px;border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink);background:rgba(17,69,75,.03)}
.em{color:var(--muted)}.dash{color:var(--faint)}
.numr{text-align:right;font-variant-numeric:tabular-nums;font-weight:500;color:var(--ink)}
.numr.bold{font-weight:800}.numr.red{color:var(--red-2);font-weight:700}.numr.green{color:var(--green);font-weight:700}
.row-act{display:flex;gap:4px;justify-content:flex-end}
.ibtn{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;border:none;background:transparent;color:var(--faint);cursor:pointer}
.ibtn:hover{background:rgba(17,69,75,.06);color:var(--deep-1)}
.ci{height:36px;border-radius:7px;border:1px solid var(--border);background:#fff;padding:0 9px;font-size:12.5px;width:100%;font-family:inherit;color:var(--ink)}
td.num .ci{text-align:right}

.tree{list-style:none}
.trow{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:12px;cursor:pointer}
.trow:hover{background:rgba(17,69,75,.05)}
.tname{font-size:13px;font-weight:600;color:var(--ink)}
.tree ul{list-style:none;padding-left:26px;border-left:1.5px solid var(--line);margin-left:17px}

.pgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
@media (max-width:1000px){.pgrid{grid-template-columns:repeat(3,1fr)}}
@media (max-width:700px){.pgrid{grid-template-columns:repeat(2,1fr)}}
.pcell{border:1px solid var(--border);border-radius:14px;padding:12px 14px;background:rgba(255,255,255,.85)}
.pcell .m{font-size:12.5px;font-weight:800;color:var(--ink)}
.pcell .s{margin-top:6px}
.pcell .a{display:flex;gap:6px;margin-top:10px;flex-wrap:wrap}

@media (max-width:768px){.stage-body{margin-right:0}.slim-rail{display:none!important}}
```

### 3.1 — Journal Entries · List (`acc.journals`)
```html
<div class="page-head"><div><h1>Journal Entries</h1><div class="sub">All manual postings with status and actions.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">⋯ Import / Export</button><button class="btn btn-cta btn-sm">➕ New Journal Entry</button></div></div>
<div class="card"><div class="li-wrap"><table>
  <thead><tr><th style="width:12%">Journal №</th><th style="width:11%">Date</th><th style="width:13%">Type</th>
    <th style="width:26%">Description</th><th class="num" style="width:12%">Amount (K)</th><th style="width:12%">Status</th><th style="width:16%"></th></tr></thead>
  <tbody>
    <!-- one row per journal; row ⋯ actions vary by status: Draft → edit/delete/submit; Pending → approve/reject; Posted → reverse/print/duplicate -->
    <tr><td class="mono">{journal #}</td><td class="em">{date}</td><td><span class="tchip">{type}</span></td>
      <td class="em">{description}</td><td class="numr bold">{amount}</td>
      <td><span class="badge b-pend"><span class="bdot"></span>{status}</span></td>
      <td class="row-act"><button class="ibtn">👁</button><button class="ibtn">✎</button><button class="ibtn">⋯</button></td></tr>
  </tbody></table></div>
  <div style="display:flex;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--line)">
    <span style="font-size:12px;color:var(--muted)">Showing {n} of {total}</span>
    <div style="display:flex;gap:8px"><button class="btn btn-ghost btn-xs">← Prev</button><button class="btn btn-ghost btn-xs">Next →</button></div></div>
</div>
```
Edit icon (`✎`) only appears for `Draft` rows.

### 3.2 — Journal · Create (`acc.journals.create`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Journals</a> › <span class="here">New</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-ghost btn-sm">Save Draft</button><button class="btn btn-cta btn-sm">Submit Approval</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Journal №</label><input class="in" value="{auto-generated}" disabled></div>
  <div class="f"><label>Transaction Date</label><input class="in" type="date"></div>
  <div class="f"><label>Journal Type</label><select class="in"><option>General Journal</option><option>Adjustment</option><option>Closing</option><option>Opening</option><option>Reversal</option></select></div>
  <div class="f"><label>Reference</label><input class="in" placeholder="Ref"></div>
  <div class="f" style="grid-column:1/-1"><label>Description</label><input class="in" placeholder="Description"></div>
  <div class="f"><label>Currency</label><select class="in"><!-- real currency list, MWK first --></select></div>
  <!-- disabled unless a foreign currency is selected -->
  <div class="f"><label>Exchange Rate</label><input class="in" value="1.0000" disabled></div>
  <div class="f"><label>Attachment</label><input class="in" type="file"></div>
</div></div>
<div class="li-wrap"><table class="jtable">
  <thead><tr><th style="width:12%">Account</th><th style="width:26%">Description</th><th class="num" style="width:13%">Debit (K)</th>
    <th class="num" style="width:13%">Credit (K)</th><th style="width:11%">Cost Centre</th><th style="width:6%"></th></tr></thead>
  <tbody>
    <!-- one editable row per line; Account field is a searchable account picker (dashed display, dash-less storage per §2); Cost Centre/Department/Project dimension pickers per line -->
    <tr><td class="mono"><!-- account search --></td><td class="em"><input class="ci" placeholder="Line description"></td>
      <td class="num"><input class="ci jd" value=""></td><td class="num"><input class="ci jc" value=""></td>
      <td><!-- cost centre select --></td><td class="row-act"><button class="ibtn">🗑</button></td></tr>
  </tbody>
  <!-- totals recompute live on input; okchip shows "✓ Balanced" or, when unequal, "✗ Out {difference}" in the .bad red variant -->
  <tfoot><tr><td colspan="2">Totals</td><td class="numr sum-d">0</td><td class="numr sum-c">0</td><td colspan="2"><span class="okchip bal">✓ Balanced</span></td></tr></tfoot>
</table></div>
<div class="pad" style="border-top:1px solid var(--line)"><button class="btn btn-ghost btn-xs">＋ Add Line</button><button class="btn btn-ghost btn-xs">Validate Balance</button></div>
</div>
```
**Submit Approval is blocked (disabled or rejected server-side) whenever the lines are not
balanced** — recompute totals live as any debit/credit input changes (reuse the mockup's
`<script>` recompute logic as a reference for the client-side live-total behavior, adapted to
this app's stack).

### 3.3 — Journal · View (`acc.journals.show`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Journals</a> › <span class="here">{journal #}</span></nav>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <!-- Edit hidden/disabled unless status = Draft -->
    <button class="btn btn-ghost btn-sm">✎ Edit</button>
    <button class="btn btn-ghost btn-sm">🖨 Print Voucher</button>
    <button class="btn btn-ghost btn-sm">↩ Reverse</button>
    <button class="btn btn-ghost btn-sm">⧉ Duplicate</button></div></div>
<div class="card"><div class="card-h"><h2>Journal — {journal #}</h2><span class="badge b-post" style="margin-left:8px"><span class="bdot"></span>{status}</span>
  <div class="right"><span class="tchip">{type}</span><span class="tchip">{date}</span><span class="tchip">{currency}</span></div></div>
  <div class="li-wrap"><table>
    <thead><tr><th style="width:12%">Account</th><th style="width:30%">Description</th><th class="num" style="width:14%">Debit (K)</th>
      <th class="num" style="width:14%">Credit (K)</th><th style="width:12%">Cost Centre</th></tr></thead>
    <tbody>
      <!-- one read-only row per posted line -->
      <tr><td class="mono">{account code}</td><td class="em">{line description}</td><td class="numr">{debit or —}</td>
        <td class="dash">{credit or —}</td><td class="em">{cost centre or —}</td></tr>
    </tbody>
    <tfoot><tr><td colspan="2">Totals</td><td class="numr">{total debit}</td><td class="numr">{total credit}</td><td><span class="okchip">✓ Balanced</span></td></tr></tfoot>
  </table></div>
  <div class="pad" style="border-top:1px solid var(--line);font-size:12px;color:var(--muted)">Posted by {user} · {timestamp} · Source: {source module/document, or "Manual entry"} · {attachment chips if any}</div>
</div>
```

### 3.4 — Journal · Edit — draft-only (`acc.journals.edit`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Journals</a> › <a href="#">{journal #}</a> › <span class="here">Edit</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-cta btn-sm">Save Changes</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Journal №</label><input class="in" value="{journal #}" disabled></div>
  <div class="f"><label>Transaction Date</label><input class="in" type="date" value="{date}"></div>
  <div class="f"><label>Journal Type</label><select class="in"><!-- current type selected --></select></div>
  <div class="f"><label>Status</label><input class="in" value="Draft" disabled></div>
</div></div>
<div class="li-wrap"><table class="jtable">
  <thead><tr><th style="width:12%">Account</th><th style="width:26%">Description</th><th class="num" style="width:13%">Debit (K)</th>
    <th class="num" style="width:13%">Credit (K)</th><th style="width:6%"></th></tr></thead>
  <tbody>
    <!-- pre-filled editable rows -->
    <tr><td class="mono">{account}</td><td class="em">{description}</td><td class="num"><input class="ci jd" value="{debit}"></td>
      <td class="num"><input class="ci jc" value="{credit}"></td><td class="row-act"><button class="ibtn">🗑</button></td></tr>
  </tbody>
  <tfoot><tr><td colspan="2">Totals</td><td class="numr sum-d">{total debit}</td><td class="numr sum-c">{total credit}</td><td colspan="2"><span class="okchip bal">✓ Balanced</span></td></tr></tfoot>
</table></div>
<div class="pad" style="border-top:1px solid var(--line);font-size:11.5px;color:var(--faint)">Only Draft journals are editable; posted journals must be reversed.</div>
</div>
```
**Gate this page in the router/controller, not just visually**: if the journal's status isn't
`Draft` when this route is hit, render a locked notice ("must be reversed") with editing
disabled and a Reverse action offered instead, rather than showing an editable form for a
posted journal.

### 3.5 — General Ledger · View (`acc.ledger`)
```html
<div class="page-head"><div><h1>General Ledger — {account code} · {account name}</h1><div class="sub">Running balance with drill-down to source.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">🖨 Print</button><button class="btn btn-ghost btn-sm">⇩ Export</button></div></div>
<!-- filter row: Account / From / To / Group / Cost Centre / User, using the app's existing filter-bar/select components -->
<div class="card"><div class="li-wrap"><table>
  <thead><tr><th style="width:10%">Date</th><th style="width:14%">Reference</th><th style="width:32%">Description</th>
    <th class="num" style="width:12%">Debit</th><th class="num" style="width:12%">Credit</th><th class="num" style="width:14%">Balance</th><th style="width:8%"></th></tr></thead>
  <tbody>
    <!-- first row is always the opening balance; running balance recomputed live, never stored; 👁 drills down: Account → Transaction → Source Document breadcrumb -->
    <tr><td class="em">{period start}</td><td class="mono">OPEN</td><td class="em">Opening balance</td><td class="dash">—</td><td class="dash">—</td>
      <td class="numr bold">{opening balance}</td><td class="row-act"><button class="ibtn">👁</button></td></tr>
    <tr><td class="em">{date}</td><td class="mono">{reference}</td><td class="em">{description}</td>
      <td class="numr">{debit or —}</td><td class="dash">{credit or —}</td><td class="numr bold">{running balance}</td>
      <td class="row-act"><button class="ibtn">👁</button></td></tr>
  </tbody>
  <tfoot><tr><td colspan="5">Closing balance</td><td class="numr">{closing balance}</td><td></td></tr></tfoot>
</table></div></div>
```
This page is **view-only** — no create/edit affordance anywhere on it.

### 3.6 — Trial Balance · View (`acc.trialbalance`)
```html
<div class="page-head"><div><h1>Trial Balance — as at {date}</h1><div class="sub">Dr = Cr verification before statements.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Comparative</button><button class="btn btn-cta btn-sm">Generate</button></div></div>
<!-- option chips: include inactive / zero balance / monthly comparison -->
<div class="card"><div class="pad" style="padding-bottom:10px;display:flex;justify-content:flex-end">
  <!-- .okchip.bad red variant when Dr ≠ Cr -->
  <span class="okchip">✓ Balanced · Dr = Cr = {total}</span></div>
  <div class="li-wrap"><table>
    <thead><tr><th style="width:14%">Code</th><th style="width:40%">Account</th><th class="num" style="width:23%">Debit (K)</th><th class="num" style="width:23%">Credit (K)</th></tr></thead>
    <tbody>
      <!-- one row per account with a balance; 👁 drills to that account's General Ledger page -->
      <tr><td class="mono">{account code}</td><td class="em">{account name}</td><td class="numr">{debit or —}</td><td class="dash">{credit or —}</td></tr>
    </tbody>
    <tfoot><tr><td colspan="2">Totals</td><td class="numr">{total debit}</td><td class="numr">{total credit}</td></tr></tfoot>
  </table></div></div>
```
View-only, same as Ledger.

### 3.7 — Fiscal Years · List (`acc.fiscalyears`)
```html
<div class="page-head"><div><h1>Fiscal Years</h1><div class="sub">Year cycles with closing, locking and carry-forward.</div></div>
  <button class="btn btn-cta btn-sm">➕ New Fiscal Year</button></div>
<div class="card"><div class="li-wrap"><table>
  <thead><tr><th style="width:20%">Fiscal Year</th><th style="width:14%">Start</th><th style="width:14%">End</th><th style="width:12%">Status</th><th style="width:36%"></th></tr></thead>
  <tbody>
    <!-- actions vary by status: Open → 👁/✎/Close/Carry Fwd; Closed → 👁/Reopen; Locked → 👁 only -->
    <tr><td style="font-weight:800;color:var(--ink)">{year label}</td><td class="em">{start date}</td><td class="em">{end date}</td>
      <td><span class="badge b-open"><span class="bdot"></span>{status}</span></td>
      <td class="row-act"><button class="ibtn">👁</button><button class="ibtn">✎</button><button class="btn btn-ghost btn-xs">Close</button><button class="btn btn-ghost btn-xs">Carry Fwd</button></td></tr>
  </tbody></table></div></div>
```

### 3.8 — Fiscal Year · Create (`acc.fiscalyears.create`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Fiscal Years</a> › <span class="here">New</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-cta btn-sm">Create &amp; Generate Periods</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Fiscal Year Name</label><input class="in" placeholder="e.g. 2027 Financial Year"></div>
  <div class="f"><label>Start Date</label><input class="in" type="date"></div>
  <div class="f"><label>End Date</label><input class="in" type="date"></div>
  <div class="f"><label>Status</label><select class="in"><option>Open</option><option>Closed</option></select></div>
  <div class="f" style="grid-column:1/-1"><label>Options</label>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12.5px;font-weight:700;color:var(--ink)">
      <label><input type="checkbox" checked> Generate 12 monthly periods</label>
      <label><input type="checkbox"> Add adjustment period</label>
    </div></div>
</div></div></div>
```
"Create & Generate Periods" creates the fiscal year and, when the checkbox is checked,
generates its 12 (or 13, with adjustment period) accounting periods via the existing handler.

### 3.9 — Fiscal Year · View/Edit (`acc.fiscalyears.show`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Fiscal Years</a> › <span class="here">{year label}</span></nav>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">✎ Edit</button>
    <button class="btn btn-ghost btn-sm">Close Year</button>
    <button class="btn btn-ghost btn-sm">Lock</button>
    <button class="btn btn-sec btn-sm">Carry Forward Balances</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <!-- disabled/read-only until "Edit" is clicked -->
  <div class="f"><label>Name</label><input class="in" value="{name}" disabled></div>
  <div class="f"><label>Start</label><input class="in" value="{start date}" disabled></div>
  <div class="f"><label>End</label><input class="in" value="{end date}" disabled></div>
  <div class="f"><label>Status</label><input class="in" value="{status}" disabled></div>
</div>
<div class="pad" style="border-top:1px solid var(--line);font-size:12px;color:var(--muted)">{n} monthly periods · {open count} open · {closed count} closed · carry-forward posts opening balances to {next year} and computes retained earnings automatically.</div></div>
```
Carry Forward and Close/Lock/Reopen all call the existing handlers — don't reimplement any of
this logic.

### 3.10 — Accounting Periods · List (`acc.periods`)
```html
<div class="page-head"><div><h1>Accounting Periods — {year}</h1><div class="sub">Open / close / lock monthly posting periods.</div></div>
  <div style="display:flex;gap:10px"><span class="tchip">Closing checklist</span><span class="tchip">User override</span><button class="btn btn-cta btn-sm">Generate Year</button></div></div>
<div class="pgrid">
  <!-- one .pcell per period; actions per status: Open → [Close][Lock][View]; Closed → [Reopen]; Locked → [View] only -->
  <div class="pcell"><div class="m">{month} {year}</div><div class="s"><span class="badge b-open"><span class="bdot"></span>{status}</span></div>
    <div class="a"><button class="btn btn-ghost btn-xs">Close</button><button class="btn btn-ghost btn-xs">Lock</button><button class="btn btn-ghost btn-xs">View</button></div></div>
</div>
```
**Posting into a Closed or Locked period is enforced by the posting handler (§2)** — this page
is the control surface, not where the rule actually lives.

### 3.11 — Cost Centres · List (`acc.costcentres`)
```html
<div class="page-head"><div><h1>Cost Centres</h1><div class="sub">Track income &amp; expenses by department, branch or project.</div></div>
  <button class="btn btn-cta btn-sm">＋ Add Cost Centre</button></div>
<div class="card"><div class="li-wrap"><table>
  <thead><tr><th style="width:12%">Code</th><th style="width:24%">Name</th><th style="width:16%">Manager</th><th style="width:16%">Department</th><th style="width:11%">Status</th><th style="width:18%"></th></tr></thead>
  <tbody>
    <tr><td class="mono">{code}</td><td class="em">{name}</td><td class="em">{manager}</td><td class="em">{department}</td>
      <td><span class="badge b-open"><span class="bdot"></span>{status}</span></td>
      <td class="row-act"><button class="ibtn">👁</button><button class="ibtn">✎</button><button class="ibtn">📊</button></td></tr>
  </tbody></table></div></div>
```

### 3.12 — Cost Centre · Create (`acc.costcentres.create`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Cost Centres</a> › <span class="here">New</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-cta btn-sm">Save Cost Centre</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Code</label><input class="in" value="{next auto code}"></div>
  <div class="f"><label>Name</label><input class="in" placeholder="e.g. Operations"></div>
  <div class="f"><label>Manager</label><input class="in" placeholder="Manager"></div>
  <div class="f"><label>Department</label><select class="in"><!-- real departments --></select></div>
  <div class="f"><label>Parent</label><select class="in"><!-- real cost centre hierarchy --></select></div>
  <div class="f"><label>Status</label><select class="in"><option>Active</option><option>Inactive</option></select></div>
</div></div></div>
```

### 3.13 — Cost Centre · View/Edit (`acc.costcentres.show`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Cost Centres</a> › <span class="here">{code}</span></nav>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">✎ Edit</button>
    <button class="btn btn-ghost btn-sm">View Transactions</button>
    <button class="btn btn-ghost btn-sm">Budget vs Actual</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Code</label><input class="in" value="{code}" disabled></div>
  <div class="f"><label>Name</label><input class="in" value="{name}" disabled></div>
  <div class="f"><label>Manager</label><input class="in" value="{manager}" disabled></div>
  <!-- Budget YTD / Actual YTD come from the existing Budgeting module's data — read-only display here, don't rebuild budgeting -->
  <div class="f"><label>Budget YTD</label><input class="in" value="{budget ytd}" disabled></div>
  <div class="f"><label>Actual YTD</label><input class="in" value="{actual ytd}" disabled></div>
  <div class="f"><label>Variance</label><input class="in" value="{variance} ({favourable/unfavourable})" disabled></div>
</div></div></div>
```
"Budget vs Actual" links to the existing Budgeting module's report for this cost centre — link
only, never rebuild Budgeting itself.

### 3.14 — Exchange Rates · List (`acc.rates`)
```html
<div class="page-head"><div><h1>Exchange Rates</h1><div class="sub">Base MWK · buying/selling · historical · revaluation.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">📥 Import</button><button class="btn btn-ghost btn-sm">＋ Add Currency</button><button class="btn btn-cta btn-sm">＋ Add Exchange Rate</button></div></div>
<div class="card"><div class="li-wrap"><table>
  <thead><tr><th style="width:13%">Currency</th><th style="width:14%">Rate Date</th><th class="num" style="width:15%">Buying</th><th class="num" style="width:15%">Selling</th><th style="width:12%">Base</th><th style="width:25%"></th></tr></thead>
  <tbody>
    <tr><td style="font-weight:700;color:var(--ink)">{currency}</td><td class="em">{rate date}</td><td class="numr">{buying}</td><td class="numr">{selling}</td>
      <td class="em">MWK</td><td class="row-act"><button class="ibtn">✎</button><button class="ibtn">🕘</button><button class="btn btn-ghost btn-xs">Revalue</button></td></tr>
  </tbody></table></div></div>
```
"Revalue" posts FX gain/loss via the existing handler — don't rebuild revaluation math. "🕘"
opens rate history for that currency.

### 3.15 — Exchange Rate · Create/Edit (`acc.rates.create`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Exchange Rates</a> › <span class="here">New</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-cta btn-sm">Save Rate</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Currency</label><select class="in"><!-- real currency list, excluding base --></select></div>
  <div class="f"><label>Rate Date</label><input class="in" type="date"></div>
  <div class="f"><label>Buying Rate</label><input class="in"></div>
  <div class="f"><label>Selling Rate</label><input class="in"></div>
  <div class="f"><label>Base Currency</label><input class="in" value="MWK" disabled></div>
</div></div></div>
```
The same route handles both create and edit — pre-fill when editing an existing rate entry.

### 3.16 — Account Classification · List (`acc.classification`)
```html
<div class="page-head"><div><h1>Account Classification</h1><div class="sub">Financial-statement mapping &amp; reporting hierarchy.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Preview Statement</button><button class="btn btn-cta btn-sm">＋ Add Classification</button></div></div>
<div class="g2">
  <div class="card"><div class="card-h"><h2>Balance Sheet</h2></div>
    <div class="pad"><ul class="tree">
      <!-- one .trow per classification under Balance Sheet, from live classification data -->
      <li><div class="trow"><span class="tname">{classification}</span><span class="tchip">{sub-sections summary}</span></div></li>
    </ul></div></div>
  <div class="card"><div class="card-h"><h2>Income Statement</h2></div>
    <div class="pad"><ul class="tree">
      <li><div class="trow"><span class="tname">{classification}</span><span class="tchip">{sub-sections summary}</span></div></li>
    </ul></div></div>
</div>
```
"Preview Statement" renders the actual Balance Sheet / Income Statement from this
classification mapping — verify it reflects real account balances, not placeholder structure.

### 3.17 — Classification · Create/Edit (`acc.classification.create`)
```html
<div class="page-head"><nav class="crumbs"><a href="#">Classification</a> › <span class="here">New</span></nav>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">Cancel</button><button class="btn btn-cta btn-sm">Save Classification</button></div></div>
<div class="card"><div class="pad"><div class="g4">
  <div class="f"><label>Classification Name</label><input class="in" placeholder="e.g. Current Assets"></div>
  <div class="f"><label>Financial Statement</label><select class="in"><option>Balance Sheet</option><option>Income Statement</option></select></div>
  <div class="f"><label>Section</label><select class="in"><option>Assets</option><option>Liabilities</option><option>Equity</option><option>Income</option><option>Expenses</option></select></div>
  <div class="f"><label>Display Order</label><input class="in" value="10"></div>
  <div class="f" style="grid-column:1/-1"><label>Assign Accounts</label><input class="in" placeholder="Search accounts to assign (dashed codes, e.g. 1-01-001, 1-01-002…)"></div>
</div></div></div>
```
The same route handles both create and edit.

## 4 · Accessibility & responsive
- Status boxes/trees use appropriate `aria` roles where present.
- The journal balanced chip (`.okchip`) uses `aria-live` so screen readers announce balance
  changes as lines are edited.
- `⋯` more-menus use `aria-haspopup`.
- Focus rings use `--focus` (#94a3b8); table headers use `<th scope="col">`.
- Breakpoints (already encoded in the CSS in §3): `.g4`/`.g2` collapse at 1000px/640px;
  `.pgrid` goes 2-col ≤700px; slim rail hidden ≤768px (existing shared behavior); tables scroll
  horizontally inside their cards. No horizontal page scrollbar at 1280/1024/768.

## 5 · Rails registry — wire these 16 pages into the existing shared rails component
Implement per-page rail content exactly as follows (reuse the real, already-built rails
component — don't hand-roll this markup):

| Page | Quick Nav / Views content |
|---|---|
| `acc.journals` | Views: All (active), Draft, Pending, Posted — Quick Nav: New Journal, Recurring Journals, Trial Balance |
| `acc.journals.create` | Quick Nav: Journals List, Trial Balance, Chart of Accounts |
| `acc.journals.show` | Quick Nav: Edit, Reverse, Print Voucher, Journals List |
| `acc.journals.edit` | Quick Nav: Journals List, View, Chart of Accounts |
| `acc.ledger` | Quick Nav: Trial Balance, Journal Entries, Chart of Accounts |
| `acc.trialbalance` | Quick Nav: Generate, General Ledger, Financial Statements |
| `acc.fiscalyears` | Quick Nav: New Fiscal Year, Accounting Periods, Carry Forward |
| `acc.fiscalyears.create` | Quick Nav: Fiscal Years, Accounting Periods |
| `acc.fiscalyears.show` | Quick Nav: Close Year, Carry Forward, Accounting Periods |
| `acc.periods` | Quick Nav: Generate Year, Fiscal Years, Journal Entries |
| `acc.costcentres` | Views: All (active), Active, Inactive — Quick Nav: Add Cost Centre, Budget vs Actual |
| `acc.costcentres.create` | Quick Nav: Cost Centres, Account Budgets |
| `acc.costcentres.show` | Quick Nav: Edit, View Transactions, Budget vs Actual |
| `acc.rates` | Quick Nav: Add Rate, Import Rates, Revalue Accounts |
| `acc.rates.create` | Quick Nav: Rates List, Currencies |
| `acc.classification` | Quick Nav: Add Classification, Preview Statement, Chart of Accounts |
| `acc.classification.create` | Quick Nav: Classification List, Chart of Accounts |

Note several Quick Nav items ("Recurring Journals", "Chart of Accounts", "Account Budgets")
point at features explicitly excluded from this rebuild (§0) — these are **links only**, using
those features' real existing routes. Don't build placeholder pages for them. The global "pin
rails" preference and slim-rail/drawer behavior must work identically to how they already work
on every other page — this rebuild doesn't change that feature's code at all.

## 6 · Constraints
- Touch only the 16 named pages (§1) and their routes/views/controllers. Budgeting, Recurring
  Journals, Payroll, Banking, and Chart of Accounts are never modified — only linked to.
- No changes to the rails feature's implementation itself — only its per-page content wiring (§5).
- No changes to the existing journal posting handler, GL posting, period locking, fiscal-year
  close/carry-forward, COA mappings, or tax/pension sources.
- No new frontend packages/frameworks.
- One shared, page-scoped CSS block reusing the app's existing tokens where they exist.
- No hardcoded sample data anywhere.
- Account references are by `account_id`; dashed display / dash-less storage is preserved.
- Do not skip any of the 16 pages.

---

## Verify before declaring done
- [ ] Only the 16 named pages' old implementation was removed — Budgeting, Recurring Journals, Payroll, Banking, and Chart of Accounts pages, routes, and data are completely unaffected (diff or list their files before/after to confirm).
- [ ] All 16 pages exist, render, and are reachable, matching §3 exactly — none skipped.
- [ ] General Ledger and Trial Balance are strictly view-only — no create/edit affordance anywhere on them.
- [ ] Journal Edit is gated to `Draft` status at the route/controller level, not just visually — a posted journal hitting this route shows the locked notice and offers Reverse instead.
- [ ] Journal Submit/Post is blocked whenever debits ≠ credits — verify this is enforced server-side, not just as a client-side chip.
- [ ] Rails render via the app's existing shared component on every page in this scope, with the exact per-page content from §5.
- [ ] The app's real global header/nav was not replaced with the mockup's preview chrome.
- [ ] Row `⋯`/action sets on Journals List, Fiscal Years List, and Accounting Periods vary correctly by status, matching §3.1/§3.7/§3.10.
- [ ] Posting into a Closed or Locked accounting period is blocked by the posting handler itself (confirm this, don't just check the UI badge).
- [ ] Close Year locks its periods; Carry Forward posts real opening balances and computes retained earnings via the existing handler; Reopen is admin-only and audited.
- [ ] Revalue Accounts posts real FX gain/loss via the existing handler.
- [ ] Classification "Preview Statement" renders the real Balance Sheet / Income Statement from live account balances via the classification mapping.
- [ ] Cost Centre Budget YTD/Actual YTD/Variance and the "Budget vs Actual" link correctly source from the existing Budgeting module without duplicating its logic.
- [ ] Account references throughout use `account_id`, with dashed display and dash-less storage preserved.
- [ ] Every button and action described in this document is wired to a real handler — spot-test each one individually.
- [ ] Responsive behavior matches §4; no horizontal scrollbar at 1280/1024/768.
- [ ] No console or build errors; text-size matrix 90/100/110/125% shows no clipping.

## Deliverable report
1. Confirmation that Budgeting, Recurring Journals, Payroll, Banking, and Chart of Accounts were left completely untouched — list their key files/routes as a before/after check.
2. Page-route table: all 16 (17 route names) pages, confirmed built and reachable.
3. What was removed from the old implementation of just these 16 pages (routes, views, controllers) and confirmation nothing else in the app still references the old versions.
4. Action-mapping table: every button/control → the real handler/route it triggers, confirming posting, period-lock, fiscal-close, revaluation, and classification-preview calls all go through the app's existing engine.
5. Rail registry as actually wired per page (§5).
6. Confirmation that account references are by `account_id` with dashed display / dash-less storage preserved.
7. Confirmation that no page was skipped and no functionality outside these 16 pages was changed.
