# PAYROLL CENTRE — FULL REBUILD SPEC (design + functionality, "as designed in the mockup")

Build the Payroll Centre as one consolidated module: employees, guided
onboarding, payroll runs, payslips, statutory setup, people operations
(loans/attendance/leave), and reporting — replacing the existing,
less-comprehensive Payroll module. ALL DESIGN VALUES BELOW ARE INLINE,
EXTRACTED DIRECTLY FROM THE MOCKUP FILE. **Do not open, parse, or infer from
any mockup/HTML file — everything needed is already extracted into this
document.** No mockup dependency at build time.

---

## -1 · SCOPE DISCIPLINE (read first — this governs every phase below)

- Touch ONLY: the Payroll module (its routes, controllers, views/components,
  its own migrations/tables, its module-menu entry, and the rails registry
  entries listed in §18 for its own pages).
- Do NOT touch, refactor, or "improve while you're in there": other modules
  (Sales, Purchasing, Banking, general Reports), the rails feature's core
  implementation, the app-wide header/nav chrome, global CSS tokens/
  typography (already applied system-wide — reuse them, don't redefine
  them), the journal posting engine, GL account mappings, bank payment/
  bank-file handlers, period locking, the approval workflow engine, or
  auth/permissions.
- Payroll posts to the ledger ONLY through the EXISTING journal posting
  handler; salary payments ONLY through EXISTING banking handlers (§15.6).
  This spec adds payroll UI + calculation orchestration on top of those —
  it never reimplements them.
- If implementing this module reveals a genuine need to change something
  outside this boundary, STOP and report it rather than modifying the shared
  component unilaterally.
- Create a dedicated branch (e.g. `feature/payroll-centre-rebuild`) before
  starting. Do not work on `main`/`master`.
- Work in phases in the order given. Commit after each phase with a message
  naming the phase. If a test suite exists, capture a baseline before §1
  (removal) and re-run after every subsequent phase — a new failure means
  STOP and report, not "fix around it."

---

## 0 · DISCOVERY — OLD MODULE (before removing anything)

0.1 Inventory the CURRENT Payroll module in full: every route, controller,
    view/component, model, migration/table, menu entry, and any place other
    modules reference it (dashboard widgets, cross-links from HR/Expenses,
    etc.).
0.2 Determine which of its database tables are **payroll-specific** (safe to
    drop/replace — employees, runs, payslips, statutory config, loans,
    attendance, leave records held inside payroll) vs which are **shared
    tables** it merely reads from (chart of accounts, GL, bank accounts,
    departments/branches/cost centres — never touch these). If employee
    master data is shared with HR or another module, treat it as shared —
    do not drop it, only rebuild payroll's own views/logic around it.
0.3 If payroll-specific tables already hold real data (existing employees,
    payslips, loan balances, leave history), do NOT silently drop it —
    either write a migration that preserves/reshapes that data into the new
    schema, or clearly flag in your report what data would be lost and stop
    for confirmation before deleting. Employee financial history in
    particular must never be silently discarded.
0.4 Record this inventory in your final report (§21) as the "before"
    picture.

## 1 · REMOVAL — delete the old module

1.1 Remove the old module's routes, controllers, views/components, and menu
    entry.
1.2 Remove or migrate its payroll-specific tables per §0.3 — never the
    shared tables identified in §0.2.
1.3 Remove any now-orphaned assets (old CSS/JS/blade partials) only used by
    the old module. Leave anything shared with other modules alone.
1.4 Confirm nothing else in the app still links to or depends on the removed
    routes/views before moving on — grep for the old route names.

## 2 · DISCOVERY — SHARED ENGINE (what the new module plugs into)

2.1 Locate the chart of accounts entries this module posts against: salary
    expense, PAYE payable, pension payable, loans receivable, net-pay
    payable, and department/cost-centre dimensions.
2.2 Locate bank accounts and the existing banking export/payment handler
    (needed for §9's bank file and §15.6's payment posting).
2.3 Locate departments, branches, cost centres, any existing employee loan
    module, leave module, attendance/biometric import mechanism, currency
    rates, and any existing payslip template infrastructure.
2.4 Locate user-preference storage (rail pin/expand prefs live there) and
    the header Favorites menu (rails feature — already implemented
    system-wide, per §-1 do not modify it).
2.5 Locate the existing journal posting handler and existing approval
    workflow engine — this module calls both, never reimplements them
    (§15.6).

---

## 3 · DESIGN SYSTEM — extracted verbatim from the mockup

This is the **complete** CSS from the mockup file. Reuse the app's existing
global tokens/typography where they already match (font stack, base sizing)
— this block is provided so component-level classes below (badges, chips,
stepper, tabs, payslip paper, cards, rails) can be implemented exactly
without needing to open the mockup. Do not re-derive colors/spacing from
scratch; use these values.

```css
:root{--rw:300px;--sw:48px;--deep-1:#17565d;--deep-2:#0c3539;--deep-3:#0a2e32;--sec:#128F8E;--sec-2:#149897;
  --ink:#0B2A2D;--border:#dceaea;--line:#e2ecec;--muted:#5f7476;--faint:#8aa5a7;--focus:#94a3b8;
  --red:#dc2626;--red-2:#b91c1c;--green:#15803d;--amber:#d97706;--amber-2:#b45309;--steel:#46708C;
  --shadow-card:0 1px 2px rgba(10,42,46,.04),0 10px 30px -10px rgba(10,42,46,.10),0 30px 60px -30px rgba(8,40,44,.12);
  --shadow-cta:0 1px 2px rgba(6,32,35,.30),0 10px 20px -10px rgba(12,53,57,.60),inset 0 1px 0 rgba(255,255,255,.12);
  --shadow-teal:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);}
*{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:#eef4f4;color:#374151;-webkit-font-smoothing:antialiased}
svg{flex-none}:focus-visible{outline:2px solid var(--focus);outline-offset:2px}
.wrap{max-width:1440px;margin:0 auto;padding:0 28px 80px}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:44px;padding:0 20px;border-radius:13px;font-weight:600;font-size:13.5px;border:1px solid transparent;cursor:pointer;transition:all .18s;white-space:nowrap;font-family:inherit}
.btn:hover{transform:translateY(-1px)}
.btn-ghost{background:rgba(255,255,255,.9);border-color:var(--border);color:#374151}
.btn-ghost:hover{border-color:rgba(17,69,75,.3);color:var(--ink)}
.btn-sec{color:#fff;background:linear-gradient(180deg,var(--sec-2),var(--sec));border-color:rgba(255,255,255,.25);box-shadow:var(--shadow-teal)}
.btn-cta{color:#eaffff;background:linear-gradient(180deg,var(--deep-1),var(--deep-2) 55%,var(--deep-3));border-color:rgba(255,255,255,.14);box-shadow:var(--shadow-cta);font-weight:700}
.btn-danger-o{background:#fff;border-color:rgba(220,38,38,.35);color:var(--red)}
.btn-sm{height:38px;padding:0 15px;font-size:12.5px;border-radius:11px}
.btn-xs{height:30px;padding:0 11px;font-size:11.5px;border-radius:9px}
.crumbs{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--muted)}
.crumbs a{color:var(--muted);text-decoration:none}.crumbs a:hover{color:var(--ink)}.crumbs .here{color:var(--ink);font-weight:800}
.page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:26px 0 16px;border-bottom:1px solid var(--line);margin-bottom:22px}
.page-head h1{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.01em}
.page-head .sub{font-size:12.5px;color:var(--muted);margin-top:4px}

/* Status badges */
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.05em}
.badge .bdot{width:6px;height:6px;border-radius:50%}
.b-act{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}.b-act .bdot{background:#22c55e}
.b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:var(--amber)}
.b-leave{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-leave .bdot{background:var(--amber)}
.b-contract{background:rgba(70,112,140,.10);border:1px solid rgba(70,112,140,.4);color:var(--steel)}.b-contract .bdot{background:var(--steel)}
.b-term{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-term .bdot{background:var(--red-2)}
/* Draft (ink) and Locked (gray) variants, not in the mockup's literal CSS but same family:
   Draft: background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);color:#11454b
   Locked: background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted) */
.tchip{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);color:var(--muted)}

/* KPIs */
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.kpi{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
.kpi .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.kpi .v{margin-top:6px;font-size:1.25rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.kpi .n{margin-top:3px;font-size:10.5px;color:var(--faint)}
.kpi.hero{border:none;background:linear-gradient(135deg,var(--sec-2),var(--sec) 60%,#0c7a79)}.kpi.hero .l{color:#dff7f6}.kpi.hero .v{color:#fff}
.kpi.warn .v{color:var(--amber-2)}
@media (max-width:1000px){.kpis{grid-template-columns:1fr 1fr}}

/* Status filter boxes (5-wide) */
.statgrid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.fbox{display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:14px;padding:10px 12px;background:rgba(255,255,255,.85);cursor:pointer;text-align:left}
.fbox.on{border-color:rgba(18,143,142,.55);box-shadow:0 0 0 3px rgba(18,143,142,.12)}
.fbox .t{width:2rem;height:2rem;border-radius:.625rem;display:grid;place-items:center;color:#fff;flex:none}
.t-ink{background:linear-gradient(180deg,#17565d,#0a2e32)}.t-mint{background:#7FD1C0;color:#0c3539}.t-amber{background:#d97706}.t-steel{background:#46708C}.t-red{background:#dc2626}
.fbox .l{font-size:9px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fbox .v{font-size:15px;font-weight:800;color:var(--ink)}
@media (max-width:1100px){.statgrid{grid-template-columns:repeat(3,1fr)}}

/* Controls / inputs */
.controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px}
.search{position:relative;flex:1;min-width:220px;max-width:420px}
.search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint)}
.input{width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 12px;font-size:13px;color:var(--ink);font-family:inherit}
.search .input{padding-left:36px}
.input:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.14)}
select.input{width:auto;padding-right:30px;appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 11px center}

/* Cards */
.card{background:rgba(255,255,255,.85);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:20px;box-shadow:var(--shadow-card);overflow:hidden}
.card-h{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line);flex-wrap:wrap}
.card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
.card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.pad{padding:20px 24px}

/* Tables */
.li-wrap{margin-top:0;border:none;border-radius:0;overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;min-width:820px}
thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
thead th.num{text-align:right}
tbody td{padding:12px;border-bottom:1px solid var(--line);vertical-align:middle}
tbody tr:hover td{background:rgba(17,69,75,.04)}
tbody tr:last-child td{border-bottom:none}
tfoot td{padding:12px;border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink);background:rgba(17,69,75,.03)}
.mono{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:500;color:var(--ink)}
.em{color:var(--muted)}.dash{color:var(--faint)}
.numr{text-align:right;font-variant-numeric:tabular-nums;font-weight:500;color:var(--ink)}
.numr.bold{font-weight:800}.numr.red{color:var(--red-2);font-weight:700}.numr.green{color:var(--green);font-weight:700}
.row-act{display:flex;gap:4px;justify-content:flex-end}
.ibtn{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;border:none;background:transparent;color:var(--faint);cursor:pointer}
.ibtn:hover{background:rgba(17,69,75,.06);color:var(--deep-1)}
.pagi{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--line)}
.pagi .t{font-size:12px;color:var(--muted)}

/* Onboarding wizard */
.hsteps{display:flex;align-items:flex-start;margin:6px 0 22px;overflow-x:auto;padding-bottom:4px}
.hs{display:flex;align-items:flex-start;gap:10px;min-width:max-content}
.hs .dot{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:800;border:2px solid var(--border);background:#fff;color:var(--faint);z-index:1}
.hs .t{font-size:12px;font-weight:800;color:var(--muted)}.hs .d{font-size:10px;color:var(--faint);margin-top:2px}
.hs .bar{width:56px;height:2px;background:var(--line);margin:16px 10px 0}
.hs.done .dot{background:linear-gradient(180deg,var(--sec-2),var(--sec));border-color:transparent;color:#fff}
.hs.done .bar{background:var(--sec)}.hs.done .t{color:var(--ink)}
.hs.cur .dot{border-color:var(--sec);color:var(--sec);box-shadow:0 0 0 5px rgba(18,143,142,.15)}.hs.cur .t{color:var(--deep-1)}
.formcard{background:rgba(255,255,255,.94);backdrop-filter:blur(14px);border:1px solid var(--border);border-radius:26px;box-shadow:var(--shadow-card);overflow:hidden}
.fc-hd{padding:26px 30px 0}
.fc-hd .kick{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--sec)}
.fc-hd h1{font-size:22px;font-weight:800;color:var(--ink);margin-top:6px}
.fc-hd .sub{font-size:13px;color:var(--muted);margin-top:6px}
.fc-bd{padding:26px 30px}
.fgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px 26px}
@media (max-width:1000px){.fgrid{grid-template-columns:1fr 1fr}}@media (max-width:640px){.fgrid{grid-template-columns:1fr}}
.f label{display:flex;justify-content:space-between;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
.f label .req{color:var(--red)}.f label .opt{color:var(--faint)}
.f .in{width:100%;height:48px;border-radius:14px;border:1px solid var(--border);background:#fff;padding:0 16px;font-size:14px;color:var(--ink);font-family:inherit}
.f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.14)}
.f .hint{margin-top:7px;font-size:10.5px;color:var(--faint)}.f .hint b{color:var(--green)}
.sug{display:inline-flex;align-items:center;gap:6px;margin-top:8px;height:26px;padding:0 10px;border-radius:999px;background:rgba(18,143,142,.08);border:1px dashed rgba(18,143,142,.5);color:var(--sec);font-size:10.5px;font-weight:800;cursor:pointer}
/* Action bar: sticky bottom, TRANSPARENT — no background, no blur, no shadow. This is deliberate, not an oversight. */
.fc-bar{position:sticky;bottom:12px;margin:0 30px 26px;display:flex;align-items:center;gap:10px;padding:14px 0 0}
.fc-bar .lbl{color:var(--muted);font-size:11.5px;font-weight:800;margin-right:auto}
.fc-bar .btn{height:40px}
.fc-bar .btn-light{background:rgba(255,255,255,.9);border:1px solid var(--border);color:var(--deep-1);font-weight:700}

/* Employee profile */
.prof{display:flex;align-items:center;gap:16px;padding:20px 24px;flex-wrap:wrap}
.ava-xl{width:3.5rem;height:3.5rem;border-radius:1rem;display:grid;place-items:center;flex:none;font-size:15px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--sec-2),var(--sec));box-shadow:var(--shadow-teal)}
.prof .n{font-size:17px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.prof .c{margin-top:5px;display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:var(--muted)}
.mono-chip{font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;color:var(--deep-1);background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);border-radius:8px;padding:4px 10px}
.sumbar{display:grid;grid-template-columns:1fr 1fr 1fr 1.25fr;border:1px solid var(--border);border-radius:16px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
.sumbar .cell{padding:14px 18px;border-right:1px solid var(--line)}
.sumbar .cell .l{font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.sumbar .cell .v{margin-top:4px;font-size:1.125rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.sumbar .cell .n{margin-top:2px;font-size:10px;color:var(--faint)}
.sumbar .cell.hero{border-right:none;background:linear-gradient(90deg,var(--sec-2),var(--sec) 60%,#107c7b);display:flex;flex-direction:column;justify-content:center}
.sumbar .cell.hero .l{color:#dff7f6}.sumbar .cell.hero .v{color:#fff;font-size:1.25rem}
@media (max-width:900px){.sumbar{grid-template-columns:1fr 1fr}.sumbar .cell.hero{grid-column:1/-1}}

/* Tabs / panes */
.tabs{display:flex;gap:2px;border-bottom:1px solid var(--line);padding:0 18px;background:rgba(255,255,255,.6);overflow-x:auto}
.tab{padding:12px 14px;border:none;background:none;cursor:pointer;position:relative;font-family:inherit;font-size:12.5px;font-weight:700;color:var(--muted);white-space:nowrap}
.tab::after{content:"";position:absolute;left:12px;right:12px;bottom:-1px;height:2.5px;border-radius:3px;background:transparent}
.tab.on{color:var(--ink)}.tab.on::after{background:var(--sec)}
.pane{display:none}.pane.on{display:block}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
@media (max-width:900px){.g3{grid-template-columns:1fr 1fr}}
.fld .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fld .v{margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)}
.attchips{display:flex;gap:8px;flex-wrap:wrap}
.att{display:inline-flex;align-items:center;gap:7px;height:32px;padding:0 12px;border-radius:10px;background:rgba(255,255,255,.9);border:1px solid var(--border);font-size:11.5px;font-weight:600;color:#374151}

/* Payroll run workflow chips */
.wflow{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:4px 0 16px}
.wf{display:inline-flex;align-items:center;gap:7px;height:30px;padding:0 12px;border-radius:999px;font-size:11px;font-weight:800}
.wf.done{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.35);color:var(--green)}
.wf.cur{background:rgba(217,119,6,.12);border:1px solid rgba(217,119,6,.4);color:var(--amber-2)}
.wf.todo{background:rgba(138,165,167,.12);border:1px solid rgba(138,165,167,.4);color:var(--muted)}
.wf-arr{color:var(--faint)}

/* Payslip */
.segp{display:inline-flex;background:rgba(255,255,255,.9);border:1px solid var(--border);border-radius:12px;padding:4px;gap:2px}
.segp button{height:32px;padding:0 12px;border:none;border-radius:9px;background:none;font-family:inherit;font-size:12px;font-weight:700;color:var(--muted);cursor:pointer}
.segp button.on{background:linear-gradient(180deg,var(--sec-2),var(--sec));color:#fff}
.paper{background:#fff;border:1px solid var(--border);border-radius:26px;box-shadow:var(--shadow-card);overflow:hidden;position:relative}
.paper .wm{position:absolute;right:28px;bottom:104px;font-size:40px;font-weight:800;color:rgba(17,69,75,.06);letter-spacing:-.02em;pointer-events:none}
.pp-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:28px 32px;border-bottom:1px solid var(--line)}
.pp-logo{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;font-weight:800;font-size:15px;color:#0e3a3e;background:linear-gradient(180deg,#f4fbfb,#d9edee)}
.pp-title{text-align:right}
.pp-title .big{font-size:20px;font-weight:800;letter-spacing:.18em;color:var(--deep-1)}
.pp-title .ref{font-family:ui-monospace,Menlo,monospace;font-size:11.5px;color:var(--muted);margin-top:4px}
.tag{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
.tag.paid{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}
.tag.conf{background:rgba(138,165,167,.12);border:1px solid rgba(138,165,167,.4);color:var(--muted)}
.pp-emp{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:20px 32px;border-bottom:1px solid var(--line);background:rgba(238,244,244,.4)}
.pp-ava{width:52px;height:52px;border-radius:16px;display:grid;place-items:center;font-weight:800;font-size:16px;color:#fff;background:linear-gradient(135deg,var(--sec-2),var(--sec))}
.pp-facts{margin-left:auto;display:grid;grid-template-columns:repeat(4,auto);gap:8px 26px}
@media (max-width:900px){.pp-facts{margin-left:0;grid-template-columns:repeat(2,auto)}}
.pf .l{font-size:9.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint)}
.pf .v{font-size:12px;font-weight:700;color:var(--ink);margin-top:3px}
.pp-cols{display:grid;grid-template-columns:1fr 1fr}
@media (max-width:860px){.pp-cols{grid-template-columns:1fr}}
.pp-col{padding:22px 32px}
.pp-col + .pp-col{border-left:1px solid var(--line)}
.pp-col h4{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--faint);margin-bottom:12px}
.pt{width:100%;border-collapse:collapse;font-size:13px;min-width:0;table-layout:auto}
.pt th{font-size:9.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint);text-align:left;padding:0 0 8px}
.pt th.num{text-align:right}
.pt td{padding:9px 0;border-top:1px dashed var(--line);color:var(--muted)}
.pt td.b{color:var(--ink);font-weight:600}
.pt td.num{text-align:right;font-variant-numeric:tabular-nums;color:var(--ink);font-weight:600}
.pt tr.tot td{border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink);padding-top:11px}
/* Net-pay card: full-width, placed directly ABOVE the three info cards */
.net-card{margin:6px 32px 14px;border-radius:18px;padding:22px 26px;color:#fff;background:linear-gradient(100deg,var(--deep-2) 0%,var(--deep-1) 45%,var(--sec) 115%);box-shadow:var(--shadow-cta);display:flex;align-items:center;gap:26px;flex-wrap:wrap}
.nc-net .l{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#dff7f6}
.nc-net .v{font-size:2.2rem;font-weight:800;letter-spacing:-.02em;font-variant-numeric:tabular-nums;margin-top:4px}
.nc-net .s{font-size:11.5px;color:#cfe8e8;margin-top:4px}
.nc-stats{margin-left:auto;display:flex;gap:30px;flex-wrap:wrap}
.nc .l{font-size:9.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.72)}
.nc .v{font-size:15px;font-weight:800;margin-top:4px;font-variant-numeric:tabular-nums}
.nc .v.soft{color:#ffd9d9}
.pp-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:0 32px 24px}
@media (max-width:900px){.pp-cards{grid-template-columns:1fr}}
.pcard{border:1px solid var(--border);border-radius:16px;padding:16px 18px;background:rgba(238,244,244,.5)}
.pcard .t{font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--faint);margin-bottom:10px}
.pcard .r{display:flex;justify-content:space-between;font-size:12px;color:var(--muted);padding:4px 0}
.pcard .r b{color:var(--ink);font-variant-numeric:tabular-nums}
.pp-foot{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:18px 32px;border-top:1px solid var(--line);background:rgba(238,244,244,.5);font-size:11.5px;color:var(--muted)}
.pp-foot a{color:var(--sec);font-weight:800;text-decoration:none}
.qr{width:44px;height:44px;border-radius:8px;border:1px solid var(--border);background:repeating-linear-gradient(0deg,var(--ink) 0 3px,transparent 3px 6px),repeating-linear-gradient(90deg,var(--ink) 0 3px,transparent 3px 6px);opacity:.8}

/* Statutory / report cards */
.mcards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media (max-width:1000px){.mcards{grid-template-columns:1fr}}
.mcard{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.85);display:flex;flex-direction:column;gap:8px}
.mcard .t{font-size:13.5px;font-weight:800;color:var(--ink)}
.mcard .d{font-size:11.5px;color:var(--muted);line-height:1.5}
.mcard .foot{margin-top:auto;display:flex;gap:6px;flex-wrap:wrap}
.repcards{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
@media (max-width:1100px){.repcards{grid-template-columns:1fr 1fr}}@media (max-width:700px){.repcards{grid-template-columns:1fr}}
.repcard{border:1px solid var(--border);border-radius:14px;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card);padding:16px;display:flex;flex-direction:column;gap:8px}
.repcard .t{font-size:13.5px;font-weight:800;color:var(--ink)}
.repcard .d{font-size:11.5px;color:var(--muted);line-height:1.5}
.repcard .foot{margin-top:auto;display:flex;gap:6px;align-items:center}
.fmt{font-size:9.5px;font-weight:800;letter-spacing:.06em;color:var(--deep-1);background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);border-radius:999px;padding:2px 8px}
.open-l{margin-left:auto;font-size:11px;font-weight:800;color:var(--sec);text-decoration:none}

@media (max-width:768px){.stage-body{margin-right:0}.slim-rail{display:none!important}}
```

**Rails feature CSS (slim rail + drawer)** is already implemented system-wide
per rails.html — reuse the existing classes (`.stage`, `.stage-body`,
`.slim-rail`, `.ql-drawer`, `.rail-block`, `.rail-x`, `.rail-sec`, `.vlist`,
`.vitem`, `.s-ic`) rather than redefining them, including the rule that the
drawer stays hidden whenever the full rail is not displayed. §18 tells you
which entries each page needs, not how the rail mechanism itself works.

**App-wide header/nav** (the teal top bar with Sales/Purchasing/Payroll/
Banking/Reports tabs) is existing app chrome — do not rebuild it. It's shown
in the mockup only for context; just ensure the "Payroll" tab highlights
correctly when any payroll page is active.

**Note on onboarding coverage:** the mockup renders the six-step stepper in
full but shows only Step 2 (Employment) as a complete `.formcard` example.
§8 below gives the exact field list for all six steps — build every step
using the same `.formcard`/`.fgrid`/`.fc-bar` structure shown in the
reference markup, just with each step's own fields substituted in.

---

## 4 · STATUS BADGES + CHIPS — semantics

4.1 Employee badges: Active = `.b-act` (mint); On Leave = `.b-leave` (amber);
    Contract = `.b-contract` (steel); Terminated = `.b-term` (red); Pending
    Approval = amber (same family as `.b-pend`); Posted/Paid = mint
    (`.b-act`); Draft = ink tint (see §3 CSS comment); Locked = gray tint
    (see §3 CSS comment).
4.2 Type chips (`.tchip`): Permanent = base gray; Casual = amber tint;
    Contract = steel tint; Taxable = green tint — these mark employment
    type and taxable flags respectively.
4.3 Amounts in run tables: deductions/PAYE/pension/loan render `.numr.red`;
    net pay renders `.numr.green`; totals `.numr.bold`; `tfoot` border-top
    1.5px `--deep-1`.

---

## 5 · PAGE INVENTORY — BUILD ALL NINE, EACH A SEPARATE ROUTE

DO NOT SKIP ANY PAGE. Dashboard is mandatory.

| Route | Title | Mockup source | Spec |
|---|---|---|---|
| `payroll.dashboard` | Payroll Centre (dashboard) | Stage 1 | §6 |
| `payroll.employees` | Employees | Stage 2 | §7 |
| `payroll.create` (+ `payroll.edit` reuses the steps) | Onboard Employee wizard | Stage 3 | §8 |
| `payroll.show` | Employee Profile (12 tabs) | Stage 4 | §9 |
| `payroll.runs` | Payroll Runs | Stage 5 | §10 |
| `payroll.payslips` | Payslips | Stage 6 | §11 |
| `payroll.statutory` | Earnings · Deductions · PAYE · Pension · Benefits | Stage 7 | §12 |
| `payroll.people` | People Ops (Loans · Attendance · Leave) | Stage 8 | §13 |
| `payroll.reports` | Reports + Settings | Stage 9 | §14 |

Module menu lists all nine in this order.

> Note: the source spec also mentions an optional **Employee Groups** menu
> item (groups like "Monthly — Permanent", "Weekly — Casuals" with default
> earnings/deductions and pay frequency, used by runs to load employees) with
> no dedicated mockup design and no fixed route name. Build it only if the
> old module already has an equivalent, or if `payroll.runs` genuinely needs
> it to load employees by group — reuse the `payroll.employees` list pattern
> (§7) rather than inventing a new layout. Don't add it speculatively if
> nothing in the existing system depends on the concept.

---

## 6 · DASHBOARD (`payroll.dashboard`)

**Structure reference** (Stage 1 — wire every value to live data):

```html
<div class="page-head">
  <div><h1>Payroll Centre</h1><div class="sub">Employees, runs, statutory deductions, benefits, payslips and reporting.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">⚙ Settings</button>
    <button class="btn btn-ghost btn-sm">📊 Reports</button>
    <button class="btn btn-ghost btn-sm">📄 Payslips</button>
    <button class="btn btn-sec btn-sm">▶ Run Payroll</button>
    <button class="btn btn-cta btn-sm">➕ Add Employee</button>
  </div>
</div>
<div class="kpis" style="margin-bottom:12px">
  <div class="kpi hero"><div class="l">Total Gross Pay</div><div class="v">{amount}</div><div class="n" style="color:#dff7f6">{period} run · {n} employees</div></div>
  <div class="kpi"><div class="l">Total Net Pay</div><div class="v">{amount}</div><div class="n">after PAYE, pension, loans</div></div>
  <div class="kpi warn"><div class="l">PAYE Payable</div><div class="v">{amount}</div><div class="n">due {date}</div></div>
  <div class="kpi"><div class="l">Pension</div><div class="v">{amount}</div><div class="n">+ employer {amount}</div></div>
</div>
<div class="kpis" style="margin-bottom:16px">
  <div class="kpi"><div class="l">Employees</div><div class="v">{n}</div><div class="n">{active} active · {leave} on leave</div></div>
  <div class="kpi"><div class="l">Active</div><div class="v">{n}</div><div class="n">{perm} perm · {contract} contract</div></div>
  <div class="kpi"><div class="l">On Leave</div><div class="v">{n}</div><div class="n">{paid} paid · {unpaid} unpaid</div></div>
  <div class="kpi warn"><div class="l">Pending Approval</div><div class="v">{n}</div><div class="n">review run →</div></div>
</div>
<div class="card"><div class="card-h"><h2>Last Run — {PR-№}</h2><span class="badge b-act" style="margin-left:8px"><span class="bdot"></span>Posted</span>
  <div class="right"><button class="btn btn-ghost btn-xs">📄 Payslips</button><button class="btn btn-ghost btn-xs">🏦 Bank File</button><button class="btn btn-ghost btn-xs">Audit</button></div></div>
  <div class="pad" style="display:flex;gap:22px;flex-wrap:wrap;font-size:12.5px">
    <div class="fld"><div class="l">Period</div><div class="v">{start}–{end}</div></div>
    <div class="fld"><div class="l">Paid</div><div class="v">{n} employees</div></div>
    <div class="fld"><div class="l">Gross</div><div class="v">{amount}</div></div>
    <div class="fld"><div class="l">Net</div><div class="v">{amount}</div></div>
    <div class="fld"><div class="l">Posted To</div><div class="v">{JV-ref}</div></div>
  </div>
</div>
```

Functional spec: non-sticky page head; right actions [⚙ Settings ghost →
`payroll.reports` settings section][📊 Reports ghost → `payroll.reports`]
[📄 Payslips ghost → `payroll.payslips`][▶ Run Payroll secondary →
`payroll.runs`][➕ Add Employee CTA → `payroll.create`]. KPI row 1: hero
Total Gross Pay, Total Net Pay, PAYE Tax Payable (amber), Pension
Contributions. KPI row 2: Total Employees, Active, Employees on Leave,
Pending Approvals (amber, links to the relevant run in `payroll.runs`). Last
Run card: header with run number + Posted badge, right shortcuts [Payslips]
[Bank File][Audit], body facts row (Period / Paid n employees / Gross / Net
/ Posted To {JV ref}).

---

## 7 · EMPLOYEES (`payroll.employees`)

**Structure reference** (Stage 2):

```html
<div class="page-head">
  <div><h1>Employees</h1><div class="sub">All employee records with salary, status and payment method.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">⋯ More</button><button class="btn btn-cta btn-sm">➕ Add Employee</button></div>
</div>
<div class="card"><div class="pad" style="padding-bottom:0">
  <div class="statgrid">
    <button class="fbox on"><span class="t t-ink">[icon]</span><span><span class="l">All</span><span class="v" style="display:block">{n}</span></span></button>
    <button class="fbox"><span class="t t-mint">[icon]</span><span><span class="l">Active</span><span class="v" style="display:block">{n}</span></span></button>
    <button class="fbox"><span class="t t-amber">[icon]</span><span><span class="l">On Leave</span><span class="v" style="display:block">{n}</span></span></button>
    <button class="fbox"><span class="t t-steel">[icon]</span><span><span class="l">Contract</span><span class="v" style="display:block">{n}</span></span></button>
    <button class="fbox"><span class="t t-red">[icon]</span><span><span class="l">Terminated</span><span class="v" style="display:block">{n}</span></span></button>
  </div>
  <div class="controls">
    <div class="search">[search icon]<input class="input" placeholder="Employee №, name, NRC…"></div>
    <select class="input"><option>All Departments</option></select>
    <select class="input"><option>All Branches</option></select>
    <select class="input"><option>All Payroll Groups</option></select>
  </div>
</div>
<div class="li-wrap"><table>
  <thead><tr><th>Emp №</th><th>Name</th><th>Department</th><th>Position</th><th>Type</th><th>Joined</th><th class="num">Basic</th><th>Status</th><th></th></tr></thead>
  <tbody>
    <tr><td class="mono">{EMP-№}</td><td style="font-weight:700;color:var(--ink)">{name}</td><td class="em">{department}</td><td class="em">{position}</td>
      <td><span class="tchip">{Permanent|Casual|Contract}</span></td><td class="em">{joined}</td><td class="numr bold">{basic}</td>
      <td><span class="badge {b-act|b-leave|b-contract|b-term}"><span class="bdot"></span>{status}</span></td>
      <td class="row-act"><button class="ibtn">👁</button><span class="more"><button class="ibtn">⋯</button>
        <div class="more-menu"><button class="more-item">✎ Edit</button><button class="more-item">📤 Upload Documents</button><button class="more-item">💰 Update Salary</button><button class="more-item">🌴 Record Leave</button><button class="more-item">💳 Issue Loan</button><button class="more-item">Deactivate</button><button class="more-item danger">⛔ Terminate</button></div></span></td></tr>
  </tbody>
</table></div>
<div class="pagi"><span class="t">Showing {n} of {total}</span><div style="display:flex;gap:8px"><button class="btn btn-ghost btn-xs">← Prev</button><button class="btn btn-ghost btn-xs">Next →</button></div></div>
</div>
```

Functional spec: right-side [⋯ More: Import Employees · Export Employees] +
[➕ Add Employee CTA → `payroll.create`]. 5 status boxes, live counts, click
sets the existing filter param. Search by №/name/NRC + Department + Branch +
Payroll Group selects. Row actions: [👁 view → `payroll.show`] + ⋯ menu
[Edit · Upload Documents · Update Salary · Record Leave · Issue Loan ·
Deactivate · Terminate (danger, confirm + final-settlement rule — a
terminated employee must go through final settlement, not a bare status
flip)]. Pagination uses the existing mechanism.

---

## 8 · ONBOARDING (`payroll.create`) — guided wizard

**Structure reference** (Stage 3 — stepper + Step 2 shown in full as the
formcard pattern; apply the same structure to all six steps with the field
sets given below):

```html
<div class="fc-hd" style="padding:26px 0 0">
  <nav class="crumbs" style="margin-bottom:8px"><a href="{payroll.employees}">Payroll</a> › <a href="{payroll.employees}">Employees</a> › <span class="here">Onboard</span></nav>
  <h1 style="font-size:22px;font-weight:800;color:var(--ink)">Onboard a new employee</h1>
  <div class="sub" style="font-size:13px;color:var(--muted);margin-top:6px">Six short steps with smart defaults — everything saves as you go. <a href="#" style="color:var(--sec);font-weight:800;text-decoration:none">Finish later</a></div>
</div>
<div class="hsteps">
  <div class="hs {done|cur|todo}"><span class="dot">{✓|n}</span><span><span class="t">{step_name}</span><div class="d">{step_subtitle}</div></span><span class="bar"></span></div>
  <!-- repeat ×6: Personal (Identity & contact) / Employment (Role & placement) / Tax & Pension (Statutory) /
       Bank & Pay (Payout) / Compensation (Salary & benefits) / Review (Docs & confirm) — steps are clickable to any visited step -->
</div>
<section class="formcard">
  <div class="fc-hd"><div class="kick">Step {n} · {step_name}</div><h1>{conversational_h1}</h1><div class="sub">{smart_defaults_note}</div></div>
  <div class="fc-bd"><div class="fgrid">
    <div class="f"><label>{field_label} <span class="req">*</span></label><input class="in"></div>
    <!-- one .f per field, 3-col grid → 2-col ≤1000px → 1-col ≤640px; optional fields use <span class="opt">optional</span> instead of .req;
         smart-suggestion chips render as <span class="sug">✦ {suggestion} · apply</span> under the relevant field;
         auto-derived fields show a hint: <div class="hint"><b>✓</b> {derived note}</div> -->
  </div></div>
  <div class="fc-bar"><span class="lbl">Step {n} of 6 · {pct}% complete · draft saved just now</span><button class="btn btn-light">← Back</button><button class="btn btn-sec">Save &amp; continue →</button></div>
</section>
```

**Per-step field list** (build all six using the structure above):

- **Step 1 — Personal**: Employee № (auto), First*, Middle, Last*, Email,
  Phone, DOB, Gender, National ID, Emergency Contact, Address (full).
- **Step 2 — Employment**: Position*, Department* (+ suggestion chip "✦
  Suggested cost centre · apply"), Branch, Employment type, Hire date* (✓
  payroll group auto-set hint), Probation (auto), Supervisor, Cost centre,
  Payroll group.
- **Step 3 — Tax & Pension**: Tax ID, PAYE Table (active one, read-only),
  Pension Scheme, Pension Member №.
- **Step 4 — Bank & Pay**: Bank Name, Account №, Account Name, Branch Code,
  Payment Method.
- **Step 5 — Compensation**: Basic Pay*, Salary Frequency, Housing,
  Transport, Payslip Password (auto if blank).
- **Step 6 — Review & Documents**: upload chips (NRC, contract, photo) +
  a per-section summary with Edit links back to each step + consent
  checkbox.

Functional spec: field affordances — 48px inputs, 14px radius, teal 4px
focus ring, inline validation ticks, helper microcopy, smart-suggestion
chips (`.sug`) that apply a suggested value on click. Action bar (`.fc-bar`)
is **sticky bottom, transparent — no background color, no blur, no
shadow**; this is a deliberate design choice, not something to "fix" by
adding a background. Left label shows live step/percent/autosave status;
right [← Back light-bordered][Save & continue → teal]. Drafts persist via
autosave — reloading the page or returning later restores progress.
`payroll.edit` reuses these same six steps pre-filled.

---

## 9 · EMPLOYEE PROFILE (`payroll.show`) — HEADER STANDARD, 12 tabs

**Structure reference** (Stage 4):

```html
<div class="page-head" style="padding-top:10px">
  <div style="display:flex;align-items:center;gap:10px">
    <button class="ibtn" style="border:1px solid var(--border);background:#fff">←</button>
    <nav class="crumbs"><a href="{payroll.employees}">Payroll</a> › <a href="{payroll.employees}">Employees</a> › <span class="here">{EMP-№}</span></nav>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">✎ Edit</button>
    <button class="btn btn-ghost btn-sm">💰 Update Salary</button>
    <button class="btn btn-ghost btn-sm">📄 Payslip</button>
    <span class="more"><button class="btn btn-ghost btn-sm">⋯ More</button>
      <div class="more-menu"><button class="more-item">📤 Upload Documents</button><button class="more-item">🌴 Record Leave</button><button class="more-item">💳 Issue Loan</button><button class="more-item danger">⛔ Terminate</button></div></span>
  </div>
</div>
<div class="card" style="margin-bottom:20px"><div class="prof"><span class="ava-xl">{initials}</span>
  <div><div class="n">{name} <span class="mono-chip">{EMP-№}</span> <span class="badge {b-act|...}"><span class="bdot"></span>{status}</span></div>
    <div class="c"><span>{position} · {department}</span><span>{type} · {frequency}</span><span>Joined {date}</span><span>Bank · {bank} ****{last4}</span></div></div></div></div>
<div class="sumbar" style="margin-bottom:20px">
  <div class="cell"><div class="l">Basic</div><div class="v">{amount}</div><div class="n">per month</div></div>
  <div class="cell"><div class="l">Gross</div><div class="v">{amount}</div><div class="n">+ housing + transport</div></div>
  <div class="cell"><div class="l">Net ({period})</div><div class="v">{amount}</div><div class="n">after PAYE + pension</div></div>
  <div class="cell hero"><div class="l">YTD Gross</div><div class="v">{amount}</div></div>
</div>
<div class="card"><div class="tabs" id="etabs" role="tablist">
  <button class="tab on" data-pane="p1" role="tab">Personal</button>
  <button class="tab" data-pane="p2" role="tab">Employment</button>
  <button class="tab" data-pane="p3" role="tab">Salary</button>
  <button class="tab" data-pane="p4" role="tab">Payroll History</button>
  <button class="tab" data-pane="p5" role="tab">Earnings</button>
  <button class="tab" data-pane="p6" role="tab">Deductions</button>
  <button class="tab" data-pane="p7" role="tab">Benefits</button>
  <button class="tab" data-pane="p8" role="tab">Loans</button>
  <button class="tab" data-pane="p9" role="tab">Leave</button>
  <button class="tab" data-pane="p10" role="tab">Attendance</button>
  <button class="tab" data-pane="p11" role="tab">Documents</button>
  <button class="tab" data-pane="p12" role="tab">Audit</button>
</div>
  <div class="pane on" id="p1"><div class="pad"><div class="g3">
    <div class="fld"><div class="l">Full Name</div><div class="v">{name}</div></div>
    <div class="fld"><div class="l">Gender / DOB</div><div class="v">{gender} · {dob}</div></div>
    <div class="fld"><div class="l">National ID</div><div class="v">{id}</div></div>
    <div class="fld"><div class="l">Phone / Email</div><div class="v">{phone}</div></div>
    <div class="fld"><div class="l">Address</div><div class="v">{address}</div></div>
    <div class="fld"><div class="l">Emergency</div><div class="v">{contact}</div></div>
  </div></div></div>
  <div class="pane" id="p2"><div class="pad"><div class="g3">
    <div class="fld"><div class="l">Dept / Position</div><div class="v">{department} · {position}</div></div>
    <div class="fld"><div class="l">Branch</div><div class="v">{branch}</div></div>
    <div class="fld"><div class="l">Type</div><div class="v">{type}</div></div>
    <div class="fld"><div class="l">Joined</div><div class="v">{date}</div></div>
    <div class="fld"><div class="l">Supervisor</div><div class="v">{supervisor}</div></div>
    <div class="fld"><div class="l">Status</div><div class="v">{status}</div></div>
  </div></div></div>
  <div class="pane" id="p3"><div class="pad"><div class="g3">
    <div class="fld"><div class="l">Basic</div><div class="v">{amount} / mo</div></div>
    <div class="fld"><div class="l">Frequency</div><div class="v">{frequency}</div></div>
    <div class="fld"><div class="l">Method</div><div class="v">{method}</div></div>
  </div></div></div>
  <div class="pane" id="p4"><div class="pad"><div class="li-wrap"><table style="min-width:0">
    <thead><tr><th>Period</th><th>Run</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">Net</th><th>Status</th></tr></thead>
    <tbody><tr><td class="em">{period}</td><td class="mono">{PR-№}</td><td class="numr">{gross}</td><td class="numr">{paye}</td><td class="numr bold">{net}</td><td><span class="badge b-act"><span class="bdot"></span>Paid</span></td></tr></tbody>
  </table></div></div></div>
  <div class="pane" id="p5"><div class="pad"><div class="li-wrap"><table style="min-width:0">
    <thead><tr><th>Earning</th><th>Basis</th><th>Taxable</th><th class="num">Amount</th></tr></thead>
    <tbody><tr><td class="em">{earning}</td><td class="em">{basis}</td><td><span class="tchip" style="color:var(--green)">Taxable</span></td><td class="numr">{amount}</td></tr></tbody>
  </table></div></div></div>
  <div class="pane" id="p6"><div class="pad"><div class="li-wrap"><table style="min-width:0">
    <thead><tr><th>Deduction</th><th>Basis</th><th class="num">Amount</th></tr></thead>
    <tbody><tr><td class="em">{deduction}</td><td class="em">{basis}</td><td class="numr">{amount}</td></tr></tbody>
  </table></div></div></div>
  <div class="pane" id="p7"><div class="pad"><div class="attchips"><span class="att">{benefit_icon} {benefit_label}</span></div></div></div>
  <div class="pane" id="p8"><div class="pad"><div class="li-wrap"><table style="min-width:0">
    <thead><tr><th>Loan</th><th class="num">Principal</th><th class="num">Instalment</th><th class="num">Remaining</th><th>Status</th></tr></thead>
    <tbody><tr><td class="mono">{LN-№}</td><td class="numr">{principal}</td><td class="numr">{instalment}</td><td class="numr bold">{remaining}</td><td><span class="badge b-act"><span class="bdot"></span>Active</span></td></tr></tbody>
  </table></div></div></div>
  <div class="pane" id="p9"><div class="pad"><div class="g3">
    <div class="fld"><div class="l">Entitlement</div><div class="v">{days}</div></div>
    <div class="fld"><div class="l">Taken</div><div class="v">{n}</div></div>
    <div class="fld"><div class="l">Balance</div><div class="v" style="color:var(--green)">{n}</div></div>
  </div></div></div>
  <div class="pane" id="p10"><div class="pad"><div class="g3">
    <div class="fld"><div class="l">OT ({month})</div><div class="v">{hours}h @1.5×</div></div>
    <div class="fld"><div class="l">Late</div><div class="v">{n}</div></div>
    <div class="fld"><div class="l">Absences</div><div class="v">{n}</div></div>
  </div></div></div>
  <div class="pane" id="p11"><div class="pad"><div class="attchips"><span class="att">📎 {filename}</span></div></div></div>
  <div class="pane" id="p12"><div class="pad"><div class="fld" style="font-size:12px;color:var(--muted)">
    <div class="v">{date} · {actor} · {field} {old} → {new} · {reason}</div>
  </div></div></div>
</div>
```

Functional spec: sticky-standard header — back icon-button + breadcrumb
`Payroll › Employees › {EMP-№}` (mono); right cluster [✎ Edit][💰 Update
Salary][📄 Payslip → §11] + [⋯ More: Upload Documents · 🌴 Record Leave ·
💳 Issue Loan · ⛔ Terminate (danger)]. Profile card is identity only:
initials tile, name + mono chip + status badge, meta chips (position ·
department, type · frequency, joined, masked bank). Salary summary bar:
Basic / Gross (+ allowances note) / Net (last run) / hero YTD Gross. Twelve
client-side tabs per the reference markup — tab switch is a plain show/hide,
no page reload. [💰 Update Salary] writes a dated, audited adjustment
(shows up in the Audit tab exactly as the field-level change example shows).
[📄 Payslip] opens the employee's latest payslip.

---

## 10 · PAYROLL RUNS (`payroll.runs`)

**Structure reference** (Stage 5):

```html
<div class="page-head"><div><h1>Payroll Runs</h1><div class="sub">Calculate, approve, post and generate payslips.</div></div><button class="btn btn-cta btn-sm">➕ New Run</button></div>
<div class="wflow">
  <span class="wf {done|cur|todo}">{✓|●} Period</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Load</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Earnings</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Deductions</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">PAYE</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Review</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Approve</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Post</span><span class="wf-arr">→</span>
  <span class="wf {done|cur|todo}">Payslips</span>
</div>
<div class="card"><div class="card-h"><h2>{PR-№} · {period}</h2><span class="badge {b-draft|b-pend|b-act|b-lock}" style="margin-left:8px"><span class="bdot"></span>{status}</span>
  <div class="right">
    <button class="btn btn-ghost btn-xs">⚗ Calculate</button>
    <button class="btn btn-sec btn-xs">✓ Approve</button>
    <button class="btn btn-ghost btn-xs">🔒 Lock</button>
    <button class="btn btn-ghost btn-xs">📓 Post</button>
    <button class="btn btn-ghost btn-xs">📄 Payslips</button>
    <button class="btn btn-ghost btn-xs">🏦 Bank File</button>
  </div></div>
  <div class="li-wrap"><table>
    <thead><tr><th>Emp</th><th>Employee</th><th class="num">Basic</th><th class="num">Allow.</th><th class="num">Gross</th><th class="num">PAYE</th><th class="num">Pension</th><th class="num">Loan</th><th class="num">Net</th></tr></thead>
    <tbody><tr><td class="mono">{EMP-№}</td><td style="font-weight:700;color:var(--ink)">{name}{ (unpaid leave) if applicable}</td>
      <td class="numr">{basic}</td><td class="numr">{allowances}</td><td class="numr bold">{gross}</td>
      <td class="numr red">{paye}</td><td class="numr red">{pension}</td><td class="numr {red|dash}">{loan}</td><td class="numr green">{net}</td></tr></tbody>
    <tfoot><tr><td colspan="2">Totals — {n} employees</td><td class="numr">{basic}</td><td class="numr">{allow}</td><td class="numr">{gross}</td><td class="numr">{paye}</td><td class="numr">{pension}</td><td class="numr">{loan}</td><td class="numr">{net}</td></tr></tfoot>
  </table></div>
</div>
```

Functional spec: workflow chips (`.wflow`) reflect real run progress — done/
current/todo states, not decorative. Run card header shows the status badge
matching the run's actual state (Draft/Pending Approval/Posted/Locked).
Buttons: [⚗ Calculate] applies the §15 engine; [✓ Approve secondary] per
workflow threshold; [🔒 Lock] blocks further edits; [📓 Post to Accounts]
creates a journal via the EXISTING posting handler (§15.6); [📄 Generate
Payslips] creates payslips once Posted (or per settings); [🏦 Export Bank
File] produces a bank-compatible CSV via the existing banking handler. Run
table: per-employee row, leave note appended to name where relevant (e.g.
"unpaid leave"), `tfoot` totals row. Per-employee row click drills to
`payroll.show` for that employee.

---

## 11 · PAYSLIPS (`payroll.payslips`)

**Structure reference** (Stage 6):

```html
<div class="page-head" style="border:none;margin-bottom:12px">
  <nav class="crumbs"><a href="{payroll.employees}">Payroll</a> › <a href="{payroll.payslips}">Payslips</a> › <span class="here">{EMP-№} · {month} {year}</span></nav>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <div class="segp"><button>{month-3}</button><button class="on">{month-2}</button><button>{month-1}</button></div>
    <button class="btn btn-ghost btn-sm">✉ Email</button><button class="btn btn-ghost btn-sm">🖨 Print</button><button class="btn btn-sec btn-sm">📕 PDF</button>
  </div>
</div>
<div class="paper"><div class="wm">PAID</div>
  <div class="pp-head">
    <div style="display:flex;gap:12px;align-items:center"><span class="pp-logo">{initials}</span>
      <div><div style="font-size:16px;font-weight:800;color:var(--ink)">{company_name}</div><div style="font-size:10.5px;color:var(--muted);margin-top:2px">{address} · PAYE PIN {pin}</div></div></div>
    <div class="pp-title"><div class="big">PAYSLIP</div><div class="ref">{PS-ref}</div>
      <div style="display:flex;gap:6px;justify-content:flex-end;margin-top:8px"><span class="tag paid">● Paid · {date}</span><span class="tag conf">Confidential</span></div></div>
  </div>
  <div class="pp-emp"><span class="pp-ava">{initials}</span>
    <div><div style="font-size:16px;font-weight:800;color:var(--ink)">{name}</div><div style="font-size:11.5px;color:var(--muted);margin-top:2px">{position} · {department} · {EMP-№} · {type}</div></div>
    <div class="pp-facts">
      <div class="pf"><div class="l">Period</div><div class="v">{start}–{end}</div></div>
      <div class="pf"><div class="l">Method</div><div class="v">{bank} ****{last4}</div></div>
      <div class="pf"><div class="l">PAYE PIN</div><div class="v">{pin}</div></div>
      <div class="pf"><div class="l">Pension №</div><div class="v">{member_no}</div></div>
    </div>
  </div>
  <div class="pp-cols">
    <div class="pp-col"><h4>Earnings</h4><table class="pt"><thead><tr><th>Item</th><th>Basis</th><th class="num">Amount</th></tr></thead>
      <tbody><tr><td class="b">{earning}</td><td>{basis}</td><td class="num">{amount}</td></tr>
      <tr class="tot"><td>Gross Pay</td><td></td><td class="num">{gross}</td></tr></tbody></table></div>
    <div class="pp-col"><h4>Deductions</h4><table class="pt"><thead><tr><th>Item</th><th>Basis</th><th class="num">Amount</th></tr></thead>
      <tbody><tr><td class="b">{deduction}</td><td>{basis}</td><td class="num">{amount}</td></tr>
      <tr class="tot"><td>Total Deductions</td><td></td><td class="num">{total}</td></tr></tbody></table></div>
  </div>
  <div class="net-card">
    <div class="nc-net"><div class="l">Net Pay</div><div class="v">{net}</div><div class="s">{pct}% of gross reaches the employee · paid {date}</div></div>
    <div class="nc-stats">
      <div class="nc"><div class="l">Gross</div><div class="v">{gross}</div></div>
      <div class="nc"><div class="l">Deductions</div><div class="v soft">{total_deductions}</div></div>
      <div class="nc"><div class="l">Employer cost</div><div class="v">{employer_cost}</div></div>
    </div>
  </div>
  <div class="pp-cards">
    <div class="pcard"><div class="t">Employer contributions · memo</div><div class="r"><span>{contribution}</span><b>{amount}</b></div><div class="r"><span>Total employer cost</span><b>{amount}</b></div></div>
    <div class="pcard"><div class="t">Year to date</div><div class="r"><span>YTD Gross</span><b>{amount}</b></div><div class="r"><span>YTD PAYE</span><b>{amount}</b></div><div class="r"><span>YTD Pension</span><b>{amount}</b></div></div>
    <div class="pcard"><div class="t">Leave &amp; attendance</div><div class="r"><span>Leave balance</span><b>{days}</b></div><div class="r"><span>Overtime</span><b>{hours}</b></div><div class="r"><span>Absences</span><b>{n}</b></div></div>
  </div>
  <div class="pp-foot"><span class="qr"></span><span>Paid to {bank} ····{last4} on {date} · Ref {ref} · System-generated, no signature required.</span><span style="margin-left:auto">Questions? <a href="#">Contact HR →</a></span></div>
</div>
```

Functional spec: toolbar has breadcrumb + period segmented control + [✉
Email][🖨 Print][📕 Download PDF secondary]. Paper document: letterhead
left, "PAYSLIP" letter-spaced + mono ref + status/confidential tags right;
minimized "PAID" watermark exactly as specced — 40px, `rgba(17,69,75,.06)`,
positioned `right:28px; bottom:104px`. Employee strip: avatar + name/role +
facts grid. Earnings/Deductions two-column tables with ruled totals. **Net
Pay card is full-width and sits directly above the three info cards** — this
ordering is deliberate, don't reflow it. Three info cards: Employer
contributions memo, Year to date, Leave & attendance. Footer: QR placeholder
+ payment reference line + "Contact HR" link. Employee self-service: an
employee can see their own payslips (mobile-friendly), gated by permissions
and by the payslip password where one is set; email delivery goes through
the existing notification handler. Payslips are immutable once generated —
correcting one means reversing the run (§15), never editing the payslip
record directly.

---

## 12 · STATUTORY (`payroll.statutory`)

**Structure reference** (Stage 7):

```html
<div class="page-head">
  <div><h1>Statutory &amp; Pay Items Setup</h1><div class="sub">Earnings, deductions, tax tables, pension and benefits.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">＋ Deduction</button><button class="btn btn-ghost btn-sm">＋ Earning</button><button class="btn btn-cta btn-sm">＋ Tax Table</button></div>
</div>
<div class="mcards" style="grid-template-columns:1fr 1fr">
  <div class="mcard"><span class="t">Earnings</span><div class="d">{earning_list} — all taxable flags + assignments.</div><div class="foot"><button class="btn btn-ghost btn-xs">Assign Employees</button></div></div>
  <div class="mcard"><span class="t">Deductions</span><div class="d">{deduction_list} — statutory/auto/voluntary.</div><div class="foot"><button class="btn btn-ghost btn-xs">Configure Rule</button></div></div>
  <div class="mcard"><span class="t">PAYE Table — {table_name} <span class="badge b-act" style="margin-left:6px"><span class="bdot"></span>Active</span></span>
    <div class="d">{bands_summary}.</div><div class="foot"><button class="btn btn-ghost btn-xs">✎ Edit Bracket</button><button class="btn btn-ghost btn-xs">📥 Import</button></div></div>
  <div class="mcard"><span class="t">Pension &amp; Benefits</span><div class="d">{scheme_summary}.</div>
    <div class="foot"><button class="btn btn-ghost btn-xs">＋ Scheme</button><button class="btn btn-ghost btn-xs">📊 Pension Report</button></div></div>
</div>
```

Functional spec: **Earnings** card — Basic · Housing (10%) · Transport ·
Overtime (1.5×) · Bonus · Commission with taxable flags + [Assign
Employees]. **Deductions** card — PAYE (auto) · Pension employee % · Loan
repayment · Medical Aid; types Statutory/Auto/Voluntary + [Configure Rule].
**PAYE Tax Table** card — header + Active badge + [✎ Edit Bracket][📥 Import
Rates]; bands (e.g. first 100,000 0%; next 50,000 20%; next 350,000 25%;
next 500,000 30%; above 35%); effective-date note; multiple tables
supported, with [Activate] to switch the active one. **Pension & Benefits**
card — scheme rows (provider, employee %/employer %, member count,
[Assign]) + benefit rows (employer-paid / taxable flags) + [＋ Scheme][📊
Generate Pension Report].

---

## 13 · PEOPLE OPS (`payroll.people`)

**Structure reference** (Stage 8):

```html
<div class="page-head">
  <div><h1>People Operations</h1><div class="sub">Loans with auto-deduction, attendance inputs and leave control.</div></div>
  <div style="display:flex;gap:10px"><button class="btn btn-ghost btn-sm">🌴 Approve Leave</button><button class="btn btn-ghost btn-sm">Approve OT</button><button class="btn btn-cta btn-sm">💳 Issue Loan</button></div>
</div>
<div class="mcards">
  <div class="mcard"><span class="t">Loans &amp; Advances</span><div class="d">{LN-№} · {employee} · {principal} · {instalment} instalment · {paid}/{total} paid · auto-deducts each run.</div>
    <div class="foot"><button class="btn btn-ghost btn-xs">Generate Schedule</button><button class="btn btn-ghost btn-xs">Record Payment</button></div></div>
  <div class="mcard"><span class="t">Attendance — {period}</span><div class="d">{source} · {hours}h OT pending · {n} late ({status}).</div>
    <div class="foot"><button class="btn btn-ghost btn-xs">📥 Import Attendance</button><button class="btn btn-ghost btn-xs">Calculate Pay</button></div></div>
  <div class="mcard"><span class="t">Leave — {employee}</span><div class="d">{type} {period} · balance {n} days · payroll deducts unpaid days.</div>
    <div class="foot"><button class="btn btn-ghost btn-xs">View Balance</button><button class="btn btn-ghost btn-xs">Adjust Leave</button></div></div>
</div>
```

Functional spec: **Loans card** — loan №, employee, principal, instalment,
"paid x of y", remaining, ends, status; loans auto-deduct each run via the
existing loan handler; [Generate Schedule][Record Payment]. **Attendance
card** — period summary sourced from biometric import, OT hours pending
approval, late/absence counts; [📥 Import Attendance][Calculate Pay]; rules
come from settings (OT 1.5×, late >3 = deduction, absence deduction).
**Leave card** — employee leave (type paid/unpaid, period, balance, and
automatic payroll deduction for unpaid days); [View Balance][Adjust
Leave][Approve Leave].

---

## 14 · REPORTS + SETTINGS (`payroll.reports`)

**Structure reference** (Stage 9):

```html
<div class="page-head"><div><h1>Payroll Reports &amp; Settings</h1><div class="sub">Employee, payroll, statutory and accounting reports.</div></div><button class="btn btn-ghost btn-sm">⇩ Export All</button></div>
<div class="repcards">
  <div class="repcard"><span class="t">Employee Register</span><span class="d">All employees with salary &amp; status.</span><div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <!-- Salary History / Employee Cost / Payroll Summary / Gross-to-Net / Payslip Register /
       PAYE Report / Pension Report / Payroll Journal / Dept Payroll Cost / Variance Report — same card pattern -->
  <div class="repcard"><span class="t">Bank Salary Upload</span><span class="d">Bank-compatible bulk file.</span><div class="foot"><span class="fmt">CSV</span><a class="open-l" href="#">Open →</a></div></div>
</div>
<div class="card"><div class="card-h"><h2>Payroll Settings</h2><span class="fmt" style="margin-left:auto">admin</span></div>
  <div class="pad"><div class="g3">
    <div class="fld"><div class="l">Periods</div><div class="v">{periods}</div></div>
    <div class="fld"><div class="l">Currency</div><div class="v">{currency}</div></div>
    <div class="fld"><div class="l">Calc rules</div><div class="v">{rules}</div></div>
    <div class="fld"><div class="l">Tax</div><div class="v">{tax_table} · auto</div></div>
    <div class="fld"><div class="l">Pension</div><div class="v">{rates}</div></div>
    <div class="fld"><div class="l">Approvals</div><div class="v">{threshold}</div></div>
    <div class="fld"><div class="l">Templates</div><div class="v">{templates}</div></div>
    <div class="fld"><div class="l">Numbering</div><div class="v">{pattern}</div></div>
    <div class="fld"><div class="l">Permissions</div><div class="v">{permissions}</div></div>
  </div></div>
</div>
```

Functional spec: 12 report cards (grid 4→2→1) — Employee Register · Salary
History · Employee Cost Report · Payroll Summary · Gross-to-Net · Payslip
Register · PAYE Report · Pension Contribution Report · Payroll Journal ·
Department Payroll Cost · Payroll Variance Report (anomaly flags) · Bank
Salary Upload (CSV). Each has PDF + Excel chips (CSV only for Bank Salary
Upload) + Open →. Use existing report pages/handlers where present,
otherwise build minimal pages using the system's existing report pattern;
[Generate][Print][Export PDF][Export Excel]. Settings card: Payroll Periods
(monthly 1st–last) / Currency (multi-currency ready) / Salary Calculation
Rules (OT 1.5×, late/absence rules) / Tax Settings (active table,
auto-update) / Pension Settings (employee%/employer%, provider) / Approval
Workflow (runs > threshold → Finance Manager) / Payslip Templates
(Standard/Executive PDF) / Payroll Numbering (`PR-{yyyy}-{mm}`,
`PS-{…}-{seq}`) / User Permissions (HR edit · Finance approve · MD view).
Use existing settings handlers; create a minimal page only if none exists.

---

## 15 · CALCULATION ENGINE + ACCOUNTING RULES

15.1 Per employee per run: Gross = Basic + fixed allowances + % allowances +
     OT (1.5×) + bonus/commission; unpaid-leave days prorate Basic/
     allowances down.
15.2 PAYE comes from the ACTIVE tax table applied to taxable gross; Pension
     = employee % × basic; employer contribution = employer % × basic
     (memo-only + shown in the employer cost report); Loan = the active
     instalment amount; other deductions per assignment.
15.3 Net = Gross − (PAYE + pension employee + loan + other deductions).
15.4 Rounding per settings; the variance report flags run-over-run anomalies
     exceeding a configurable threshold %.
15.5 Multi-company/multi-currency: per-run company + currency, conversions
     via existing rate tables.
15.6 **Posting** (EXISTING journal handler, never reimplemented): DR Salary
     Expense (gross + employer contributions, by department/cost centre) CR
     PAYE Payable, Pension Payable, Loans Receivable, Other Deductions
     Payable, Net Pay Payable. On payment: DR Net Pay Payable CR Bank
     (EXISTING banking handler). Period lock blocks posting into locked
     periods.
15.7 Payslip generation happens per posted run; the bank file is produced
     via the existing banking export.

---

## 16 · RAILS REGISTRY (per page — rails feature itself unchanged)

- `payroll.dashboard` → Quick Nav: Employees, Run Payroll, Payslips, Payroll
  Reports.
- `payroll.employees` → Views: All Employees (active), Active, On Leave,
  Contract, Terminated · Reports: Employee Register, Employee Cost.
- `payroll.create` → Quick Nav: Employees List, Employee Groups, Payroll
  Settings.
- `payroll.show` → Quick Nav: Update Salary, Run Payroll, Payslip,
  Employees List.
- `payroll.runs` → Views: All Runs (active), Draft, Pending Approval,
  Posted, Locked · Quick Nav: New Run, Approve, Post to Accounts, Generate
  Payslips.
- `payroll.payslips` → Quick Nav: Generate Payslips, Email Payslips,
  Payroll Runs.
- `payroll.statutory` → Quick Nav: Earnings, Deductions, PAYE Tables,
  Pension Schemes.
- `payroll.people` → Quick Nav: Issue Loan, Import Attendance, Approve
  Leave.
- `payroll.reports` → Quick Nav: Payroll Summary, PAYE Report, Payroll
  Journal.

The drawer stays hidden whenever the full rail isn't displayed (§3), on
every page — this is a global rails behavior, not per-page configuration.

---

## 17 · ACCESSIBILITY / RESPONSIVE

17.1 ARIA: stepper uses `aria-current`; status boxes `aria-pressed`; tabs
     `role=tablist`; ⋯ menus `aria-haspopup`; focus rings `#94a3b8`; table
     `th` uses `scope`; net-pay/deduction colors are always backed by text,
     never color alone.
17.2 ≤1100px: `.statgrid` 3-col. ≤1000px: KPI 2-col, `.fgrid` 2-col, 2-col
     grids stack. ≤640px: `.fgrid` 1-col. ≤768px: slim rail hidden, payslip
     columns stack, tables horizontal-scroll inside cards, no horizontal
     PAGE scrollbar at 1280/1024/768.

---

## 18 · CONSTRAINTS (recap of §-1, don't lose these under load)

- No changes to the rails feature itself or to any other module.
- No changes to journal posting, banking, or approval-workflow handler
  internals — this module calls them, never reimplements them.
- No new packages unless something here is genuinely impossible without
  one — flag it and ask first.
- ONE shared component/CSS per pattern (one badge partial, one table
  partial, one card partial reused across all nine pages — not nine
  copies).
- No hardcoded sample data anywhere — live ledger only. Every
  `{placeholder}` above is a real data binding, not a literal string to
  ship.
- Payslips are immutable once generated — correction happens via run
  reversal, never a direct edit.
- Onboarding's action bar stays TRANSPARENT (no background) — do not add
  one for "consistency" with other sticky bars in the app.
- The payslip PAID watermark stays 40px at its specified position — don't
  resize or reposition it.
- Old module fully removed per §1 before new pages are wired into the menu.

---

## 19 · VERIFY (every page — all nine)

19.1 **Route check:** all nine routes exist, render, and are reachable from
     the module menu; dashboard present with KPIs + last-run card; old
     module's routes return 404/redirect, not a ghost page.
19.2 **Action audit:** every button listed in §6–§14 triggers the SAME
     handler/route as the equivalent old-module control did where one
     existed (spot-click each) — dashboard's five actions + last-run
     shortcuts, employees list's view/edit/upload/update-salary/leave/
     loan/deactivate/terminate/import/export, onboarding's stepper nav/
     suggestion-apply/Back/Save & continue/Finish later, profile's cluster
     + 12 tabs + Update Salary, runs' Calculate/Approve/Lock/Post/Generate
     Payslips/Export Bank File, payslips' Email/Print/PDF/period switch,
     statutory's add earning/deduction/tax table/edit bracket/import/
     assign/scheme/benefit actions, people ops' Issue Loan/Record
     Payment/Generate Schedule/Import Attendance/Calculate Pay/Approve
     OT/Approve Leave/View Balance/Adjust, reports' Opens, settings' edits.
19.3 **Math:** run table per-employee Gross/PAYE/Pension/Loan/Net matches
     §15; `tfoot` totals = sums; a payslip equals its run row; PAYE matches
     the active table; pension percentages correct; unpaid-leave proration
     applied; bank file totals = net pay total; net-pay card figures =
     earnings − deductions.
19.4 **Controls:** approval threshold gates runs; Lock blocks edits; period
     lock blocks posting; terminations require final settlement; payslip
     access is permission-gated; onboarding autosave restores drafts.
19.5 **Rails:** slim rail + drawer + per-page pins + global pin behave
     exactly as the existing rails implementation on these and every other
     page; pages render the §16 registries; drawer hidden whenever the
     rail isn't displayed.
19.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.

## 20 · REPORT

Produce, in this order:
1. Old-module inventory (§0) and what was removed vs migrated (§1).
2. Files touched for the new module, grouped by page/route.
3. Page-route table — all nine, confirmed built, old routes confirmed gone.
4. Action-mapping table: old control → new location → handler confirmed
   same.
5. Status/chip table (badge/chip class used per state, matching §4).
6. Rails registry per page (§16), confirmed rendered.
7. GL posting mapping (§15.6) — confirm every debit/credit line and that
   posting goes through the existing handler only.
8. Explicit confirmation: rails feature, other modules, and all existing
   posting/banking/approval functionality unchanged; no page skipped;
   nothing touched outside the §-1 scope boundary.
