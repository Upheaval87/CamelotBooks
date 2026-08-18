# ACCOUNTING METHOD (COMPANY-LEVEL) + COA INHERITANCE + SWITCH-TO-ACCRUAL — IMPLEMENTATION SPEC

Three surfaces, one consistent rule running through all of them — the
accounting method (accrual/cash) is chosen exactly once, at Company
Creation, and every other surface either inherits it silently or offers a
controlled, journaled, one-way conversion:

- **(A)** Company Creation gains an "Accounting method" step.
- **(B)** Chart of Accounts Structure Setup INHERITS the method — it never
  chooses it.
- **(C)** A new, gated "Switch to Accrual" controlled-conversion flow for
  cash-basis companies.

ALL DESIGN VALUES BELOW ARE INLINE, EXTRACTED DIRECTLY FROM THE MOCKUP FILE.
**Do not open, parse, or infer from any mockup/HTML file — everything needed
is already extracted into this document.** No mockup dependency at build
time.

**This is an additive/modifying spec, not a module replacement** — unlike
other specs in this project, there is no old module to remove here. (A)
extends the existing company-creation wizard with one new step; (B) modifies
the existing COA Structure Setup page (`coa.setup`); (C) is one new page.

---

## -1 · SCOPE DISCIPLINE (read first — this governs every phase below)

- Touch ONLY: the company-creation wizard (to add the method step), the
  `coa.setup` page (to enforce inheritance and remove any method selector
  that may exist there), and the new Switch-to-Accrual page plus its own
  routes/controllers/migrations, and the rails registry entries listed in
  §8 for these three surfaces.
- Do NOT touch, refactor, or "improve while you're in there": any other
  Chart of Accounts page (account list, add/edit, tree, mapping, opening,
  budgets, reports — these belong to the separate COA Centre spec and are
  out of scope here), any other module, the rails feature's core
  implementation, the app-wide header/nav chrome, global CSS tokens/
  typography (already applied system-wide — reuse them, don't redefine
  them), the journal posting engine, GL mappings, period locking, the
  approval engine, or auth/permissions.
- The conversion in (C) posts its journal ONLY through the EXISTING journal
  posting handler. Budgeting, Recurring Journals, and any other built
  module stay linked, never rebuilt.
- **If `coa.setup` has already been rebuilt per a separate Chart of Accounts
  Centre spec**, that page's "Accounting method" card (inherited chip +
  "Change at company level") already satisfies (B) below — treat §5 here as
  a confirmation checklist for that existing card, not an instruction to
  build a second one. Only build the card from scratch if `coa.setup`
  genuinely doesn't have it yet.
- If implementing this reveals a genuine need to change something outside
  this boundary, STOP and report it rather than modifying the shared
  component unilaterally.
- Create a dedicated branch (e.g. `feature/accounting-method-inheritance`)
  before starting. Do not work on `main`/`master`.
- Work in phases in the order given. Commit after each phase with a message
  naming the phase. If a test suite exists, capture a baseline before
  starting and re-run after every subsequent phase — a new failure means
  STOP and report, not "fix around it."

---

## 0 · DISCOVERY (before changing anything)

0.1 Locate the company model and the existing company-creation wizard (its
    steps, routes, and save handler).
0.2 Locate the existing `coa.setup` page (Chart of Accounts Structure
    Setup) — confirm whether it already has an inherited-method card (see
    the note in §-1) or still presents a method choice that needs removing.
0.3 Locate: settings pages/routes (for where Switch-to-Accrual should live
    in the menu), the opening-balances page, the existing journal posting
    handler, the period model, module-activation flags (AR/AP/inventory),
    and user-role data (admin check).
0.4 List the CURRENT controls + handlers on all three surfaces — this
    drives the action audit in §11.
0.5 Locate user-preference storage (rail pin/expand prefs live there) and
    the header Favorites menu (rails feature — already implemented
    system-wide, per §-1 do not modify it).
0.6 Record this inventory in your final report (§12).

---

## 1 · DATA MODEL

Add/confirm the following — these are schema additions, not replacements of
anything existing:

1.1 `companies.accounting_method` ENUM('accrual','cash') DEFAULT 'accrual',
    set once at company creation.
1.2 `companies.reporting_preference` ENUM('accrual_view','cash_view')
    DEFAULT 'accrual_view' — report-display-only, never changes the books
    themselves.
1.3 `periods.basis` ENUM('cash','accrual') per period — used to label
    reports and comparatives, and set during conversion activation (§4.6).
1.4 `method_conversions` table: `id`, `company_id`, `from_method`,
    `to_method`, `cut_off_date`, `treatment`
    ENUM('prospective','retrospective') DEFAULT 'prospective',
    `conversion_journal_id` (FK to the journal posted via the existing
    handler), `status` ENUM('draft','activated'), `created_by`,
    `activated_at`. Exactly one **active** conversion per company; after
    activation, `companies.accounting_method` is set to `to_method`.
1.5 Default COA template is keyed by method: the **accrual** template
    activates AR (`1-02-xxx`), AP (`2-01-xxx`), accruals, prepayments, and
    inventory/COGS accounts; the **cash** template keeps those inactive and
    uses a smaller chart. The method drives which modules/workflows are
    emphasized (invoices & bills on accrual; receipts/payments-only on
    cash) — modules stay present either way, they're never removed, only
    de-emphasized.

---

## 2 · DESIGN SYSTEM — extracted verbatim from the mockup

This is the **complete** CSS from the mockup file. Reuse the app's existing
global tokens/typography where they already match — this block is provided
so component-level classes below (option cards, stepper, conversion table,
callouts) can be implemented exactly without needing to open the mockup.

```css
:root{--sw:48px;--deep-1:#17565d;--deep-2:#0c3539;--sec:#128F8E;--sec-2:#149897;
  --ink:#0B2A2D;--border:#dceaea;--line:#e2ecec;--muted:#5f7476;--faint:#8aa5a7;--focus:#94a3b8;
  --red-2:#b91c1c;--green:#15803d;--amber-2:#b45309;
  --shadow-card:0 1px 2px rgba(10,42,46,.04),0 10px 30px -10px rgba(10,42,46,.10),0 30px 60px -30px rgba(8,40,44,.12);
  --shadow-cta:0 1px 2px rgba(6,32,35,.30),0 10px 20px -10px rgba(12,53,57,.60),inset 0 1px 0 rgba(255,255,255,.12);
  --shadow-teal:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);}
*{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:#eef4f4;color:#374151;-webkit-font-smoothing:antialiased}
svg{flex:none}:focus-visible{outline:2px solid var(--focus);outline-offset:2px}
.wrap{max-width:1200px;margin:0 auto;padding:0 28px 80px}
.crumbs{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--muted);padding:24px 0 8px}
.crumbs .here{color:var(--ink);font-weight:800}
h1{font-size:22px;font-weight:800;color:var(--ink)}
.sub{font-size:12.5px;color:var(--muted);margin-top:4px}
.card{background:rgba(255,255,255,.85);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-card);overflow:hidden;margin-top:16px}
.card-h{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
.stepno{width:26px;height:26px;border-radius:8px;display:grid;place-items:center;font-size:12px;font-weight:800;color:#fff;background:linear-gradient(180deg,var(--sec-2),var(--sec));flex:none}
.pad{padding:20px 24px}
.mono{font-family:ui-monospace,Menlo,monospace}

/* Stepper (both the company-creation wizard and the conversion flow use this) */
.stepper{display:flex;gap:6px;flex-wrap:wrap;margin:14px 0 4px}
.st{display:inline-flex;align-items:center;gap:7px;height:30px;padding:0 12px;border-radius:999px;font-size:11px;font-weight:800;background:rgba(138,165,167,.12);border:1px solid rgba(138,165,167,.4);color:var(--muted)}
.st.on{background:rgba(18,143,142,.12);border-color:rgba(18,143,142,.5);color:var(--sec)}

/* Option cards (accrual vs cash choice) */
.optcards{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:900px){.optcards{grid-template-columns:1fr}}
.optcard{border:1.5px solid var(--border);border-radius:16px;padding:16px;cursor:pointer;background:#fff;position:relative}
.optcard:hover{border-color:rgba(17,69,75,.35)}
.optcard.sel{border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.14)}
.optcard .rd{position:absolute;top:14px;right:14px;width:18px;height:18px;border-radius:50%;border:2px solid var(--border)}
.optcard.sel .rd{border-color:var(--sec);background:var(--sec);box-shadow:inset 0 0 0 3px #fff}
.optcard .t{font-size:13.5px;font-weight:800;color:var(--ink)}
.optcard .d{font-size:11.5px;color:var(--muted);margin-top:4px;line-height:1.5}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media (max-width:900px){.grid2{grid-template-columns:1fr}}

/* Conversion journal table */
table{width:100%;border-collapse:collapse;font-size:13px}
thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:10px 12px;color:#111827}
thead th.num{text-align:right}
tbody td{padding:11px 12px;border-bottom:1px solid var(--line)}
tbody tr:last-child td{border-bottom:none}
td.num{text-align:right;font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
td.num.red{color:var(--red-2)}
.codecell{font-family:ui-monospace,Menlo,monospace;font-weight:700;color:var(--deep-1)}

/* Labeled input rows (opening balances, structure summary) */
.inrow{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px dashed var(--line);font-size:12.5px}
.inrow:last-child{border-bottom:none}
.inrow .nm{flex:1;font-weight:600;color:var(--ink)}
.inrow input{width:130px;height:38px;border-radius:10px;border:1px solid var(--border);text-align:right;font-size:13px;font-family:inherit}
.inrow input:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.14)}

.btn{display:inline-flex;align-items:center;gap:8px;height:44px;padding:0 20px;border-radius:13px;font-weight:600;font-size:13.5px;border:1px solid transparent;cursor:pointer;font-family:inherit}
.btn-cta{color:#eaffff;background:linear-gradient(180deg,var(--deep-1),var(--deep-2) 55%,var(--deep-3));box-shadow:var(--shadow-cta);font-weight:700}
.btn-ghost{background:rgba(255,255,255,.9);border-color:var(--border);color:#374151}
.btn-sm{height:38px;padding:0 15px;font-size:12.5px;border-radius:11px}

/* Status callouts */
.okchip{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:800;background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.35);color:var(--green)}
.warn{display:flex;gap:10px;align-items:flex-start;border:1px solid rgba(217,119,6,.4);background:rgba(217,119,6,.07);border-radius:12px;padding:12px 14px;font-size:12px;color:var(--amber-2);font-weight:600;line-height:1.5}
.chip{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 12px;border-radius:999px;font-size:10.5px;font-weight:800;background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.3);color:var(--sec)}

@media (max-width:768px){.stage-body{margin-right:0}.slim-rail{display:none!important}}
```

**Rails feature CSS (slim rail + drawer)** is already implemented
system-wide per rails.html — reuse the existing classes rather than
redefining them, including the rule that the drawer stays hidden whenever
the full rail isn't displayed. §8 tells you which entries each page needs,
not how the rail mechanism itself works.

**App-wide header/nav** is existing app chrome — do not rebuild it. The
mockup omits it for these three pages entirely (they're shown without the
tealbar's nav row) — that's a mockup simplification, not an instruction to
strip navigation from these pages; keep the app's normal chrome.

---

## 3 · (A) COMPANY CREATION — Accounting Method step

**Structure reference** (Stage 1):

```html
<nav class="crumbs">Company Setup › <span class="here">Create Company</span></nav>
<h1>Create Company</h1>
<div class="sub">Every company gets an operating basis — chosen once, at creation.</div>
<div class="stepper">
  <span class="st {on if reached}">1 · Company details</span>
  <span class="st {on}">2 · Accounting method</span>
  <span class="st {on}">3 · Fiscal year &amp; currency</span>
  <span class="st {on}">4 · Finish</span>
</div>
<div class="card"><div class="card-h"><span class="stepno">2</span><h2>Accounting method — how this company keeps its books</h2></div>
  <div class="pad">
    <div class="optcards" role="radiogroup">
      <div class="optcard {sel if selected}" role="radio" aria-checked="{true|false}"><span class="rd"></span>
        <div class="t">Accrual (Recommended)</div>
        <div class="d">Record income when earned &amp; expenses when incurred. Tracks receivables, payables, inventory, accruals &amp; prepayments. Full Balance Sheet + Income Statement. Best for credit, inventory, loans, external reporting.</div></div>
      <div class="optcard {sel if selected}" role="radio" aria-checked="{true|false}"><span class="rd"></span>
        <div class="t">Cash</div>
        <div class="d">Record only when cash moves. Simpler, smaller chart. Best for tiny cash-only businesses. You can switch to accrual later via a controlled conversion.</div></div>
    </div>
    <div class="grid2" style="margin-top:14px">
      <div class="warn">⚠ <span><b>What changes in your books:</b> accrual adds AR / AP / inventory / accrual / prepayment accounts and invoice &amp; bill modules; cash omits them and reports cash in/out. The method is stored per company and inherited by the Chart of Accounts.</span></div>
      <div class="okchip" style="align-self:start">Reporting preference: <b>Accrual view</b> · cash-basis view available as a report toggle</div>
    </div>
  </div>
</div>
```

Functional spec: this is Step 2 of the existing 4-step wizard (Company
details → **Accounting method** → Fiscal year & currency → Finish) — add it
as a new step without altering the other three steps' fields or the
wizard's save/navigation mechanics. Two selectable option cards, single-
choice, Accrual pre-selected as the recommended default. Below them: a
"What changes in your books" warning explaining the practical difference,
and a reporting-preference chip confirming the default view (accrual view,
with cash-basis available later as a report toggle — this chip is
informational here, not yet an editable control; `reporting_preference`
itself can be changed later in settings, out of scope for this step). On
save: persist `companies.accounting_method` and
`companies.reporting_preference` (§1.1–1.2). `coa.setup` and the default
COA template read from this value going forward and never ask again.

---

## 4 · (B) COA STRUCTURE SETUP (`coa.setup`) — INHERITS

**Structure reference** (Stage 2):

```html
<nav class="crumbs">Company Setup › <span class="here">Chart of Accounts Structure</span></nav>
<h1>Chart of Accounts Structure</h1>
<div class="sub">Method is inherited from the company — not chosen here.</div>
<div class="card"><div class="card-h"><h2>Accounting method</h2>
  <span class="chip" style="margin-left:8px">Inherited · {Accrual|Cash} (from company)</span>
  <div style="margin-left:auto"><button class="btn btn-ghost btn-sm">Change at company level</button></div>
</div>
  <div class="pad"><div class="warn">ⓘ <span>The coding structure below (format, levels, segments, generation) is independent of the accounting method. The method (accrual/cash) is set once at <b>Company Creation</b> and drives which accounts/modules are active.</span></div></div>
</div>
<div class="card"><div class="card-h"><h2>Code structure · segments · generation</h2></div>
  <div class="pad"><div class="grid2">
    <div><div class="inrow"><span class="nm">Format</span><span class="mono" style="font-weight:700">{format_summary}</span></div>
      <div class="inrow"><span class="nm">Levels</span><span class="mono" style="font-weight:700">{n}</span></div>
      <div class="inrow"><span class="nm">Segments</span><span class="mono" style="font-weight:700">{seg1} · {seg2} · {seg3}</span></div></div>
    <div><div class="inrow"><span class="nm">Generation</span><span class="mono" style="font-weight:700">{Automatic|Manual|Hybrid} (admin manual override)</span></div>
      <div class="inrow"><span class="nm">Default COA</span><span class="mono" style="font-weight:700">{Accrual template (AR/AP/inventory active)|Cash template (AR/AP/inventory inactive)}</span></div></div>
  </div></div>
</div>
```

Functional spec: **REMOVE any method-choice control on this page** — the
only method-related UI here is the read-only "Accounting method" card:
chip "Inherited · {Accrual|Cash} (from company)" + [Change at company
level] (admin-only, links to the company edit page — this page does not
implement that edit itself). The note line makes explicit that coding
structure (format/levels/segments/generation) is independent of the
method. Keep the existing format/segments/generation/default-COA controls
exactly as they are — the only change this spec makes to them is that the
**default COA preview must reflect the inherited method**: an accrual-
method company shows AR/AP/inventory accounts active in its default
template; a cash-method company shows them inactive. If this page has
already been rebuilt with a fuller Structure Setup flow elsewhere (see the
note in §-1), confirm that flow's method card matches this behavior rather
than building a second, conflicting one.

---

## 5 · (C) SWITCH TO ACCRUAL (`settings.switch_accrual`) — GATED

**Entry gate**: this page/route is reachable and its entry point enabled
ONLY when `companies.accounting_method = 'cash'` AND the current user is an
admin; otherwise it's hidden or disabled with an explanatory note wherever
it would normally be linked from (e.g. Settings menu). This is **one-way**:
once activated, the company is accrual and a reverse switch is never
offered — the note directs people to use reports for a cash-basis view
instead (§6.3).

**Structure reference** (Stage 3):

```html
<nav class="crumbs">Settings › <span class="here">Switch to Accrual</span></nav>
<h1>Switch to Accrual — controlled conversion</h1>
<div class="sub">For a company currently on cash basis. A dated, journaled conversion — never a free toggle.</div>
<div class="stepper">
  <span class="st on">1 · Cut-off</span><span class="st on">2 · Opening balances</span>
  <span class="st on">3 · Conversion journal</span><span class="st on">4 · Activate</span>
</div>

<div class="card"><div class="card-h"><span class="stepno">1</span><h2>Cut-off date</h2></div>
  <div class="pad">
    <div class="inrow"><span class="nm">Accrual applies from</span><input type="date" style="text-align:left"></div>
    <div class="inrow"><span class="nm">Prior periods</span><span class="mono" style="font-weight:700">{Prospective (recommended) — history stays cash basis|Retrospective}</span></div>
  </div>
</div>

<div class="card"><div class="card-h"><span class="stepno">2</span><h2>Capture opening balances at cut-off</h2></div>
  <div class="pad">
    <div class="inrow"><span class="nm">Accounts Receivable (earned, not collected)</span><input id="ar"></div>
    <div class="inrow"><span class="nm">Inventory on hand</span><input id="inv"></div>
    <div class="inrow"><span class="nm">Prepayments (paid, not yet used)</span><input id="pre"></div>
    <div class="inrow"><span class="nm">Accounts Payable (incurred, not paid)</span><input id="ap"></div>
    <div class="inrow"><span class="nm">Accrued expenses</span><input id="acc"></div>
    <div class="inrow"><span class="nm">Unearned revenue (received, not earned)</span><input id="une"></div>
  </div>
</div>

<div class="card"><div class="card-h"><span class="stepno">3</span><h2>Conversion journal — auto-balanced to opening equity</h2>
  <div style="margin-left:auto"><span class="okchip" id="bal">✓ Balanced · Dr = Cr</span></div>
</div>
  <div class="pad"><table><thead><tr><th>Account</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
    <tbody id="cj">
      <tr><td>Accounts Receivable</td><td class="num" id="d-ar">{ar}</td><td class="num"></td></tr>
      <tr><td>Inventory</td><td class="num" id="d-inv">{inv}</td><td class="num"></td></tr>
      <tr><td>Prepayments</td><td class="num" id="d-pre">{pre}</td><td class="num"></td></tr>
      <tr><td>Accounts Payable</td><td class="num"></td><td class="num" id="c-ap">{ap}</td></tr>
      <tr><td>Accrued Expenses</td><td class="num"></td><td class="num" id="c-acc">{acc}</td></tr>
      <tr><td>Unearned Revenue</td><td class="num"></td><td class="num" id="c-une">{une}</td></tr>
      <tr><td class="codecell">Retained Earnings — opening adjustment</td><td class="num" id="d-re">{plug if debit}</td><td class="num" id="c-re">{plug if credit}</td></tr>
      <tr><td class="codecell">Totals</td><td class="num" id="t-d">{total_debit}</td><td class="num" id="t-c">{total_credit}</td></tr>
    </tbody></table>
    <div class="warn" style="margin-top:12px">⚠ <span>Non-cash conversion — cash does not move. From the cut-off, invoice at delivery, expense at bill, track stock/COGS and run period-end accruals.</span></div>
  </div>
</div>

<div class="card"><div class="card-h"><span class="stepno">4</span><h2>Activate conversion</h2>
  <div style="margin-left:auto;display:flex;gap:8px"><button class="btn btn-ghost btn-sm">Save draft</button><button class="btn btn-cta btn-sm">Activate Conversion</button></div>
</div>
  <div class="pad"><div class="warn">ⓘ <span>On activation the system: posts the conversion journal, activates AR / AP / inventory modules, flags periods before cut-off as <b>cash</b> and after as <b>accrual</b>, and keeps the cash-basis view available as a report. This is recorded in the audit trail and cannot be silently undone.</span></div></div>
</div>
```

**Conversion-journal recalculation algorithm** (extracted from the
mockup's script — implement this exact logic, not a re-derived version;
recomputes live on every opening-balance input change):

```js
function fmt(v) { return (+v || 0).toLocaleString('en-US'); }

function recalc(ar, inv, pre, ap, acc, une) {
  var totalDebitSide = ar + inv + pre;   // AR + Inventory + Prepayments
  var totalCreditSide = ap + acc + une;  // AP + Accrued + Unearned
  var plug = totalDebitSide - totalCreditSide;

  // plug posts to "Retained Earnings — opening adjustment":
  //   if plug >= 0 → credit side gets the plug (RE credited)
  //   if plug <  0 → debit side gets the plug (RE debited), shown as its absolute value
  var reIsCredit = plug >= 0;
  var reDebit  = reIsCredit ? 0 : -plug;
  var reCredit = reIsCredit ? plug : 0;

  var totalDebit  = totalDebitSide + reDebit;
  var totalCredit = totalCreditSide + reCredit;

  return {
    lines: { ar: ar, inv: inv, pre: pre, ap: ap, acc: acc, une: une, reDebit: reDebit, reCredit: reCredit },
    totalDebit: totalDebit,
    totalCredit: totalCredit,
    balanced: totalDebit === totalCredit  // always true by construction — the plug guarantees this
  };
}
// UI: Dr AR / Dr Inventory / Dr Prepayments; Cr AP / Cr Accrued / Cr Unearned;
// the plug line always makes totals equal, so the "✓ Balanced · Dr = Cr" chip
// should always read balanced in practice — it exists to make the mechanism
// visible and auditable, not because failure is expected.
```

Functional spec: **Step 1 — Cut-off**: date input (accrual applies from) +
prior-period treatment (Prospective recommended / Retrospective).
Validation: the cut-off date must be ≥ the last day of the last posted
period and ≥ the company's start date; if any posted transactions exist on
or after the chosen cut-off, block with an error explaining why. **Step
2 — Opening balances at cut-off**: the six inputs shown above, driving the
live recalculation. **Step 3 — Conversion journal**: auto-balanced per the
algorithm above, always showing a "✓ Balanced · Dr = Cr" chip that
recomputes on every input change; the non-cash-conversion warning is
static copy, not data-driven. **Step 4 — Activate**: [Save draft] persists
a `method_conversions` row with `status='draft'`; [Activate Conversion CTA]
(admin only) does all of the following atomically, via the EXISTING
journal posting handler for the journal itself:
1. Posts the conversion journal.
2. Activates the AR/AP/inventory modules for this company.
3. Sets `periods.basis = 'cash'` for periods before the cut-off and
   `'accrual'` for periods from the cut-off onward.
4. Sets `companies.accounting_method = 'accrual'`.
5. Writes the `method_conversions` row with `status='activated'`,
   `activated_at`, and the resulting `conversion_journal_id`.
6. Writes an audit-trail entry.

This cannot be silently undone — there is no "deactivate" path; a company
that later wants a cash view uses the reporting toggle (§6.3), not a
reversal of this conversion.

---

## 6 · METHOD-DRIVEN BEHAVIOUR (system-wide, informational — not new UI)

These already-existing behaviors should remain consistent with what (A)
and (C) set up; this section is context for correct wiring, not a request
to build new screens.

6.1 **Accrual**: revenue recognized at invoice/delivery, expense at
    bill/receipt; AR/AP/inventory/COGS active; period-end accruals and
    prepayment amortization active; Income Statement + Balance Sheet are
    the primary reports.
6.2 **Cash**: revenue/expense recognized at cash movement; AR/AP/inventory
    accounts exist but stay inactive; cash-flow view is primary; a
    "Simple mode" may auto-post invoices/bills at the moment of payment
    rather than at issue.
6.3 Reports honour the period `basis` labels and the
    `reporting_preference` toggle — a cash-basis P&L / cash-flow view can
    be computed FROM accrual-basis data when the company is on accrual and
    the user wants that view. Comparatives are labeled by their basis so a
    report spanning a conversion's cut-off date doesn't silently blend
    cash and accrual figures without saying so.

---

## 7 · PERMISSIONS

- Method selection at Company Creation (A): available to any setup role,
  same as the rest of the creation wizard.
- "Change at company level" (B) and Switch-to-Accrual activation (C):
  ADMIN only.
- A conversion draft (C, Step 4's [Save draft]) is editable by admin;
  activation writes the audit trail entry and cannot be undone by anyone.

---

## 8 · RAILS REGISTRY (per page — rails feature itself unchanged)

- `companies.create` → Quick Nav: Companies, Chart of Accounts, Settings.
- `coa.setup` → Quick Nav: Company Setup, Chart of Accounts, Account List.
- `settings.switch_accrual` → Quick Nav: Settings, Chart of Accounts,
  Opening Balances.

The drawer stays hidden whenever the full rail isn't displayed, on every
page — a global rails behavior, not per-page configuration.

---

## 9 · ACCESSIBILITY / RESPONSIVE

9.1 ARIA: option cards use `role=radio` with `aria-checked`; the stepper
    uses `aria-current` for the active step; every conversion input is
    labeled; the balanced chip is `aria-live`; focus rings `#94a3b8`.
9.2 ≤900px: `.optcards` and `.grid2` collapse to 1 column. ≤768px: slim
    rail hidden; no horizontal PAGE scrollbar at 1280/1024/768.

---

## 10 · CONSTRAINTS (recap of §-1, don't lose these under load)

- No changes to the rails feature itself or to any other module.
- No changes to journal posting, approval-engine, or period-lock handler
  internals — the conversion calls the existing journal posting handler,
  never reimplements it.
- No new packages unless something here is genuinely impossible without
  one — flag it and ask first.
- ONE shared component/CSS per pattern (one option-card partial, one
  stepper partial reused across both the company-creation wizard and the
  conversion flow — not separate copies).
- No hardcoded sample data anywhere — every `{placeholder}` above is a real
  data binding.
- The conversion is one-way and always journaled — never a free toggle
  anywhere in the system.
- DO NOT re-ask the accounting method in COA setup, or anywhere else
  outside the Company Creation wizard.

---

## 11 · VERIFY

11.1 **Company creation**: the method step persists both
     `accounting_method` and `reporting_preference`; the default COA
     template matches the chosen method; `coa.setup` shows the inherited
     chip and no method selector; "Change at company level" is admin-only.
11.2 **Switch gate**: the entry point is hidden/disabled unless
     `accounting_method='cash'` and the user is admin; cut-off validation
     blocks a cut-off date that has posted transactions on or after it;
     the conversion journal is always balanced via the plug (§5's
     algorithm); activation posts via the existing journal handler, flips
     `accounting_method`, sets `periods.basis` correctly on both sides of
     the cut-off, activates AR/AP/inventory modules, and writes both the
     `method_conversions` row and an audit-trail entry; no reverse-switch
     path exists anywhere.
11.3 **Reports**: basis labels and the `reporting_preference` toggle behave
     per §6.3 — unaffected reports/pages outside this spec's scope should
     be untouched, but any report page already showing basis labels should
     correctly reflect a company that has just converted.
11.4 **Action audit**: every button — the company wizard's step
     navigation and save, `coa.setup`'s "Change at company level", the
     switch flow's Save draft / Activate Conversion, and the live-
     recalculating opening-balance inputs — triggers the SAME handler/
     route as pre-implementation where one existed (spot-click each).
11.5 **Rails**: slim rail + drawer + per-page pins + global pin behave
     exactly as the existing rails implementation on these and every other
     page; pages render the §8 registries.
11.6 Text-size matrix 90/100/110/125: no clipping; no console/build
     errors.

## 12 · REPORT

Produce, in this order:
1. Discovery inventory (§0) — including whether `coa.setup` already had an
   inherited-method card before this work started.
2. Files touched, grouped by surface (A/B/C).
3. Schema additions confirmed: `companies.accounting_method`,
   `companies.reporting_preference`, `periods.basis`,
   `method_conversions`.
4. Page-route table for all three surfaces.
5. Action-mapping table: old control → new location → handler confirmed
   same, where an old control existed.
6. Rails registry per page (§8), confirmed rendered.
7. Explicit confirmation: rails feature and all other modules unchanged,
   the method is asked only at Company Creation, the conversion is
   one-way and fully journaled/audited, and no free method toggle exists
   anywhere in the system.
