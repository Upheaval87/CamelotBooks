# RECURRING JOURNALS CENTRE — FULL REBUILD SPEC (design + functionality, "as designed in the mockup")

Build the financial automation engine: journal templates, schedules, automatic
generation, approval, posting, audit — replacing the existing, less-comprehensive
Recurring Journals module. ALL DESIGN VALUES BELOW ARE INLINE, EXTRACTED DIRECTLY
FROM THE MOCKUP FILE. **Do not open, parse, or infer from any mockup/HTML file —
everything needed is already extracted into this document.** No mockup dependency
at build time.

---

## -1 · SCOPE DISCIPLINE (read first — this governs every phase below)

- Touch ONLY: the Recurring Journals module (its routes, controllers, views/
  components, its own migrations/tables, its module-menu entry, and the rails
  registry entries listed in §16 for its own pages).
- Do NOT touch, refactor, or "improve while you're in there": other modules
  (Sales, Purchasing, Banking, Payroll, Fixed Assets, Loans), the rails feature's
  core implementation, the app-wide header/nav chrome, global CSS tokens/typography
  (already applied system-wide — reuse them, don't redefine them), the journal
  posting/reversal engine, period-locking engine, approval-workflow engine,
  notification engine, or auth/permissions.
- If implementing this module reveals a genuine need to change something outside
  that boundary (e.g. a shared handler is missing a hook this module needs),
  STOP and report it rather than modifying the shared component unilaterally.
- Create a dedicated branch (e.g. `feature/recurring-journals-rebuild`) before
  starting. Do not work on `main`/`master`.
- Work in phases in the order given (§0 → §1 → §2 → §3 → ... ). Commit after each
  phase with a message naming the phase. If a test suite exists, capture a
  baseline before §1 (removal) and re-run after every subsequent phase — a new
  failure means STOP and report, not "fix around it."

---

## 0 · DISCOVERY — OLD MODULE (before removing anything)

0.1 Inventory the CURRENT Recurring Journals module in full: every route,
    controller, view/component, model, migration/table, menu entry, and any
    place other modules reference it (e.g. a dashboard widget linking to it, a
    foreign key from another table into its tables).
0.2 Determine which of its database tables are **recurring-journal-specific**
    (safe to drop/replace) vs which are **shared engine tables** it merely reads
    from (journal postings, chart of accounts, GL, period locks — never touch
    these).
0.3 If recurring-journal-specific tables already hold real data (existing
    schedules, generated journals, audit history), do NOT silently drop it —
    either write a migration that preserves/reshapes that data into the new
    schema, or clearly flag in your report what data would be lost and stop for
    confirmation before deleting.
0.4 Record this inventory in your final report (§22) as the "before" picture.

## 1 · REMOVAL — delete the old module

1.1 Remove the old module's routes, controllers, views/components, and menu
    entry.
1.2 Remove or migrate its recurring-journal-specific tables per §0.3 — never
    the shared engine tables identified in §0.2.
1.3 Remove any now-orphaned assets (old CSS/JS/blade partials) that were only
    used by the old module. Leave anything shared with other modules alone.
1.4 Confirm nothing else in the app still links to or depends on the removed
    routes/views before moving on — grep for the old route names.

## 2 · DISCOVERY — SHARED ENGINE (what the new module plugs into)

2.1 Inventory existing journal engine routes/handlers: journal create/post/
    reverse, period locks, approval workflow, numbering, notification channels.
2.2 Locate: chart of accounts, departments/cost centres/projects, fixed-assets
    depreciation source, payroll source, loan schedules, subscription/prepayment
    registers (integration points for templates), currency rates.
2.3 Locate user-preference storage (rail pin/expand prefs live there) and the
    header Favorites menu (rails feature — already implemented system-wide, per
    §-1 do not modify it).
2.4 Confirm scheduler mechanism (cron/queue) availability for §17.

---

## 3 · DESIGN SYSTEM — extracted verbatim from the mockup

This is the **complete** CSS from the mockup file. Reuse the app's existing
global tokens/typography where they already match (font stack, base sizing) —
this block is provided so component-level classes below (badges, chips, kpis,
tables, cards, rails) can be implemented exactly without needing to open the
mockup. Do not re-derive colors/spacing from scratch; use these values.

```css
:root{
  --rw:300px;--sw:48px;--gap:20px;--gap-slim:12px;
  --deep-1:#17565d;--deep-2:#0c3539;--deep-3:#0a2e32;
  --sec:#128F8E;--sec-2:#149897;
  --ink:#0B2A2D;--border:#dceaea;--line:#e2ecec;
  --muted:#5f7476;--faint:#8aa5a7;--focus:#94a3b8;
  --red:#dc2626;--red-2:#b91c1c;--green:#15803d;--amber:#d97706;--amber-2:#b45309;--steel:#46708C;
  --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 32px 64px -24px rgba(8,40,44,.14);
  --shadow-cta:0 1px 2px rgba(6,32,35,.30),0 10px 20px -10px rgba(12,53,57,.60),inset 0 1px 0 rgba(255,255,255,.12);
  --shadow-teal:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
}
*{box-sizing:border-box;margin:0;padding:0}
html,body{overflow-x:clip}
body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:#eef4f4;color:#374151;-webkit-font-smoothing:antialiased}
svg{flex:none}:focus-visible{outline:2px solid var(--focus);outline-offset:2px}
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

/* Status badges */
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.05em}
.badge .bdot{width:6px;height:6px;border-radius:50%}
.b-act{background:linear-gradient(180deg,#ecfdf3,#dcf5e7);border:1px solid rgba(22,163,74,.28);color:var(--green)}.b-act .bdot{background:#22c55e}
.b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:var(--amber)}
.b-out{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-out .bdot{background:var(--red-2)}
.b-sub{background:rgba(70,112,140,.10);border:1px solid rgba(70,112,140,.4);color:var(--steel)}.b-sub .bdot{background:var(--steel)}
.b-draft{background:rgba(17,69,75,.07);border:1px solid rgba(17,69,75,.2);color:#11454b}.b-draft .bdot{background:#11454b}
.b-inact{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-inact .bdot{background:var(--muted)}

/* Type / mode chips */
.tchip{display:inline-flex;padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;
  background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);color:var(--muted)}
/* Accrual variant: background:rgba(217,119,6,.10);border-color:rgba(217,119,6,.35);color:var(--amber-2) */
/* Depreciation variant: background:rgba(70,112,140,.10);border-color:rgba(70,112,140,.4);color:var(--steel) */
/* Prepayment variant: background:rgba(18,143,142,.10);border-color:rgba(18,143,142,.35);color:var(--sec) */
/* Auto-post mode variant (green): background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.35);color:var(--green) */

.okchip{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 14px;font-size:12px;font-weight:800}
.okchip.ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.35);color:var(--green)}
.okchip.bad{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}

/* Buttons */
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
.vdiv{width:1px;height:22px;background:var(--border)}
.more{position:relative}
.more-menu{position:absolute;right:0;top:calc(100% + 6px);width:230px;background:#fff;border:1px solid var(--border);
  border-radius:12px;box-shadow:0 16px 40px -12px rgba(8,40,44,.3);padding:6px;display:none;z-index:60}
.more.open .more-menu{display:block}
.more-item{display:flex;align-items:center;gap:9px;width:100%;padding:8px 10px;border:none;border-radius:9px;background:none;
  text-align:left;font-family:inherit;font-size:12.5px;font-weight:600;color:#374151;cursor:pointer}
.more-item:hover{background:rgba(17,69,75,.06)}
.more-item.danger{color:var(--red)}

/* Cards */
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

/* KPIs */
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

/* Status filter boxes */
.statgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
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

/* Controls / inputs */
.controls{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px}
.search{position:relative;flex:1;min-width:220px;max-width:420px}
.search svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint)}
.input{width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 12px;
  font-size:13px;color:var(--ink);font-family:inherit;transition:all .15s}
.search .input{padding-left:36px}
.input:focus{outline:none;border-color:var(--focus);box-shadow:0 0 0 3px rgba(148,163,184,.18)}
.input:disabled{background:rgba(238,244,244,.7);color:var(--muted)}
select.input{width:auto;padding-right:30px;appearance:none;
  background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 11px center}
.input.h44{height:44px}
textarea.input{height:auto;min-height:4.5rem;padding:.75rem;border-radius:10px;resize:vertical}

/* Tables */
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
.numr.green{color:var(--green);font-weight:700}
.row-act{display:flex;gap:4px;justify-content:flex-end}
.ibtn{width:30px;height:30px;border-radius:8px;display:grid;place-items:center;border:none;background:transparent;color:var(--faint);cursor:pointer}
.ibtn:hover{background:rgba(17,69,75,.06);color:var(--deep-1)}
.pagi{display:flex;align-items:center;justify-content:space-between;padding:14px 24px;border-top:1px solid var(--line)}
.pagi .t{font-size:12px;color:var(--muted)}
.ci{height:36px;border-radius:7px;border:1px solid var(--border);background:#fff;padding:0 9px;font-size:12.5px;width:100%;font-family:inherit;color:var(--ink)}
td.num .ci{text-align:right}

/* Form grids */
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:18px 20px;margin-top:16px}
.sp2{grid-column:span 2}
.field label{display:block;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
@media (max-width:900px){.g3{grid-template-columns:1fr 1fr}}
.fld .l{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)}
.fld .v{margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)}

/* Template / report cards */
.mcards{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media (max-width:1000px){.mcards{grid-template-columns:1fr 1fr}}
@media (max-width:700px){.mcards{grid-template-columns:1fr}}
.mcard{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.85);display:flex;flex-direction:column;gap:8px}
.mcard .t{font-size:13.5px;font-weight:800;color:var(--ink)}
.mcard .d{font-size:11.5px;color:var(--muted);line-height:1.5}
.mcard .foot{margin-top:auto;display:flex;gap:6px;align-items:center;padding-top:6px;flex-wrap:wrap}

.attchips{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.att{display:inline-flex;align-items:center;gap:7px;height:32px;padding:0 12px;border-radius:10px;background:rgba(255,255,255,.9);
  border:1px solid var(--border);font-size:11.5px;font-weight:600;color:#374151}

/* Audit rows */
.audit{display:flex;flex-direction:column}
.arow{display:flex;gap:12px;padding:9px 2px;border-bottom:1px solid var(--line);font-size:12px}
.arow:last-child{border-bottom:none}
.arow .when{width:110px;flex:none;color:var(--faint);font-family:ui-monospace,Menlo,monospace;font-size:10.5px}
.arow .who{width:80px;flex:none;font-weight:700;color:var(--ink)}
.arow .what{color:var(--muted)}.arow .what b{color:var(--ink)}

/* Report cards */
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

@media (max-width:1100px){.statgrid{grid-template-columns:repeat(2,1fr)}}
@media (max-width:768px){.g4{grid-template-columns:1fr 1fr}.sp2{grid-column:1/-1}}
```

**Rails feature CSS (slim rail + drawer)** is already implemented system-wide
per rails.html — reuse the existing classes (`.stage`, `.stage-body`, `.slim-rail`,
`.ql-drawer`, `.rail-block`, `.rail-x`, `.rail-pin`, `.rail-sec`, `.vlist`,
`.vitem`, `.s-ic`) rather than redefining them. Do not copy the rail CSS from
this mockup over the existing implementation — §16 tells you which entries each
page needs, not how the rail mechanism itself works.

**App-wide header/nav** (the teal top bar with Sales/Purchasing/Banking/Journals
tabs) is existing app chrome — do not rebuild it. It's shown in the mockup only
for context; just ensure the "Journals" tab highlights correctly when any
recurring-journals page is active.

---

## 4 · STATUS BADGES + CHIPS — semantics

4.1 Schedule badges (pill + dot, classes above): Active = `.b-act`; Paused =
    `.b-pend`; Expired/Reversed = `.b-inact`; Scheduled = `.b-act`; Pending
    Approval = `.b-pend`; Draft = `.b-draft`; Posted = `.b-act`.
4.2 Type chips (`.tchip`): Standard = base gray; Accrual = amber tint;
    Depreciation = steel tint; Prepayment = teal tint; Adjustment = steel tint
    (exact rgba values in §3 comments above each variant).
4.3 Mode chips: Auto-post = green tint; Approval first = base gray; Draft only
    = base gray.
4.4 Balance chip: `.okchip.ok` "✓ Balanced — debits equal credits" (green);
    failure = `.okchip.bad` (red). Amounts: debits/credits right-aligned
    tabular; totals bold; `tfoot` border-top 1.5px `--deep-1`.

---

## 5 · PAGE INVENTORY — BUILD ALL TEN, EACH A SEPARATE ROUTE

DO NOT SKIP ANY PAGE. The mockup groups these into six visual "stages" for
presentation — **you must split grouped stages into their own separate,
menu-reachable routes.** A combined mockup screen maps to separate routes; no
page may be omitted, merged away, or left as a stub. Dashboard is mandatory.

| Route | Title | Mockup source | Spec |
|---|---|---|---|
| `rj.dashboard` | Recurring Journals (dashboard) | Stage 1 | §6 |
| `rj.index` | Recurring Journal List | Stage 2 | §7 |
| `rj.create` (+ `rj.edit` reuses it, pre-filled) | Create/Edit Recurring Journal | Stage 3 | §8 |
| `rj.templates` | Journal Templates | Stage 4 (top half) | §9 |
| `rj.scheduled` | Scheduled Journals | Stage 4 (bottom half) | §10 |
| `rj.generated` | Generated Journals | Stage 5 (top half) | §11 |
| `rj.approvals` | Approval Queue | Stage 5 (bottom half) | §12 |
| `rj.history` | Journal History / Audit Trail | Stage 6 (top) | §13 |
| `rj.reports` | Recurring Journal Reports | Stage 6 (middle) | §14 |
| `rj.settings` | Recurring Journals Settings | Stage 6 (bottom) | §15 |

Module menu (sidebar/nav) lists all ten in the order above.

---

## 6 · DASHBOARD (`rj.dashboard`) — MANDATORY

**Structure reference** (from mockup Stage 1 — wire every value to live data;
nothing below is hardcoded content, it's the exact layout/markup pattern to
follow):

```html
<div class="page-head">
  <div><h1>Recurring Journals</h1><div class="sub">Automate rent, salaries, depreciation, interest, subscriptions and accruals — with full control and audit.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">⚙ Settings</button>
    <button class="btn btn-ghost btn-sm">📊 View Reports</button>
    <button class="btn btn-sec btn-sm">▶ Run Scheduled Journals</button>
    <button class="btn btn-cta btn-sm">➕ Create Recurring Journal</button>
  </div>
</div>

<div class="kpis" style="margin-bottom:12px">
  <div class="kpi hero"><div class="l">Total Recurring Journals</div><div class="v">{total}</div><div class="n">{active} active · {paused} paused · {expired} expired</div></div>
  <div class="kpi"><div class="l">Active Schedules</div><div class="v">{active}</div><div class="n">next run in {n} days</div></div>
  <div class="kpi warn"><div class="l">Pending Journals</div><div class="v">{pending}</div><div class="n"><a class="open-l" href="{rj.approvals}">Approval queue →</a></div></div>
  <div class="kpi red"><div class="l">Failed Generations</div><div class="v">{failed}</div><div class="n"><a class="open-l" href="{rj.generated}?status=failed">View failure →</a></div></div>
</div>
<div class="kpis" style="margin-bottom:16px">
  <div class="kpi"><div class="l">Generated This Month</div><div class="v">{n}</div><div class="n">{posted} posted · {pending} pending</div></div>
  <div class="kpi"><div class="l">Total Amount Posted</div><div class="v">{amount}</div><div class="n">FY{yyyy} to date</div></div>
  <div class="kpi"><div class="l">Upcoming Runs (7d)</div><div class="v">{n}</div><div class="n">{amount} scheduled</div></div>
  <div class="kpi"><div class="l">Auto-post Enabled</div><div class="v">{n}</div><div class="n">of {m} active schedules</div></div>
</div>

<section class="card">
  <div class="card-h"><h2>Upcoming Journal Runs</h2><a class="open-l" href="{rj.scheduled}" style="margin-left:auto">Scheduled journals →</a></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
    <table>
      <thead><tr><th>Next Run</th><th>Journal</th><th>Type</th><th>Frequency</th><th class="num">Amount</th><th>Mode</th><th></th></tr></thead>
      <tbody>
        <tr><td class="em">{date}</td><td style="font-weight:700;color:var(--ink)">{journal_name}</td><td><span class="tchip">{type}</span></td>
          <td class="em">{frequency}</td><td class="numr bold">{amount}</td><td><span class="tchip">{mode}</span></td>
          <td><div class="row-act"><button class="btn btn-sec btn-xs">Run Now</button></div></td></tr>
        <!-- repeat per upcoming run row -->
      </tbody>
    </table>
  </div>
</section>
```

Functional spec: non-sticky page head; right-side actions as above (Settings
ghost → `rj.settings`, View Reports ghost → `rj.reports`, Run Scheduled
Journals secondary → triggers §17 scheduler run, Create Recurring Journal CTA
→ `rj.create`). KPI row 1: hero Total Recurring Journals, Active Schedules,
Pending Journals (amber), Failed Generations (red). KPI row 2: Generated This
Month, Total Amount Posted, Upcoming Runs (7d), Auto-post Enabled. Upcoming
Journal Runs table per structure above, with a working [Run Now] per row (§17).

---

## 7 · LIST (`rj.index`)

**Structure reference** (Stage 2):

```html
<div class="page-head">
  <div><h1>Recurring Journals</h1><div class="sub">All journal templates with frequency, next run and generation history.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">⇩ Export</button>
    <button class="btn btn-cta btn-sm">➕ New Recurring Journal</button>
  </div>
</div>
<section class="card">
  <div class="card-sec">
    <div class="statgrid">
      <button class="fbox on"><span class="t t-ink">[icon]</span><span><span class="l">All</span><span class="v" style="display:block">{n}</span></span></button>
      <button class="fbox"><span class="t t-mint">[icon]</span><span><span class="l">Active</span><span class="v" style="display:block">{n}</span></span></button>
      <button class="fbox"><span class="t t-amber">[icon]</span><span><span class="l">Paused</span><span class="v" style="display:block">{n}</span></span></button>
      <button class="fbox"><span class="t t-red">[icon]</span><span><span class="l">Expired</span><span class="v" style="display:block">{n}</span></span></button>
    </div>
    <div class="controls">
      <div class="search">[search icon]<input class="input" placeholder="Search by journal name…"></div>
      <select class="input"><option>All Statuses</option><option>Active</option><option>Paused</option><option>Expired</option></select>
      <select class="input"><option>All Frequencies</option><option>Daily</option><option>Weekly</option><option>Monthly</option><option>Quarterly</option><option>Yearly</option></select>
    </div>
  </div>
  <div class="card-sec" style="padding-top:6px">
    <div class="li-wrap" style="margin-top:0">
      <table>
        <thead><tr><th>Journal Name</th><th>Reference</th><th>Type</th><th>Frequency</th><th>Next Run</th><th>Last Generated</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <tr><td style="font-weight:700;color:var(--ink)">{name}</td><td class="mono">{RJ-№}</td>
            <td><span class="tchip">{type}</span></td><td class="em">{frequency}</td><td class="em">{next_run}</td><td class="em">{last_generated}</td>
            <td class="numr bold">{amount}</td><td><span class="badge b-act"><span class="bdot"></span>Active</span></td>
            <td><div class="row-act"><button class="ibtn">▶</button><button class="ibtn">👁</button><span class="more"><button class="ibtn">⋯</button>
              <div class="more-menu"><button class="more-item">✎ Edit</button><button class="more-item">⧉ Duplicate</button><button class="more-item">⏸ Pause / ▶ Resume</button><button class="more-item">🕘 View History</button><button class="more-item danger">🗑 Delete</button></div></span></div></td></tr>
          <!-- Expired rows show a [Renew] button instead of run/view/⋯ -->
        </tbody>
      </table>
    </div>
  </div>
  <div class="pagi"><span class="t">Showing {n} of {total} journals</span>
    <div style="display:flex;gap:8px"><button class="btn btn-ghost btn-sm">← Prev</button><button class="btn btn-ghost btn-sm">Next →</button></div></div>
</section>
```

Functional spec: status boxes are live counts, click sets the EXISTING filter
param (do not invent a new filter mechanism if one exists); search by name +
Status select + Frequency select. Table actions: [▶ run][👁 view] + ⋯ menu
[✎ Edit · ⧉ Duplicate · ⏸ Pause / ▶ Resume · 🕘 View History · 🗑 Delete
(confirm; blocked when generated journals exist — surface a message explaining
why)]; Expired rows show [Renew] instead. Pagination uses the existing
mechanism.

---

## 8 · CREATE / EDIT (`rj.create`, `rj.edit`)

**Structure reference** (Stage 3):

```html
<div class="sticky-head">
  <div><h1>Create Recurring Journal</h1><div class="sub">Define the entry once — the engine generates, approves and posts on schedule.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">Cancel</button>
    <div class="seg"><button class="btn btn-ghost">Save Draft</button><button class="btn btn-cta">Activate Schedule ⚡</button></div>
  </div>
</div>

<section class="card">
  <div class="card-sec">
    <div class="sec-head">[icon]<h2>Basic Information</h2><span class="rule"></span>
      <button class="btn btn-ghost btn-xs" style="margin-left:auto">🗂 Use Template</button></div>
    <div class="g4">
      <div class="field sp2"><label>Journal Name *</label><input class="input h44"></div>
      <div class="field"><label>Reference Number</label><input class="input h44" disabled></div>
      <div class="field"><label>Journal Type</label><select class="input h44"><option>Standard Journal</option><option>Accrual</option><option>Depreciation</option><option>Prepayment</option><option>Adjustment</option></select></div>
      <div class="field sp2"><label>Description</label><input class="input h44"></div>
      <div class="field"><label>Start Date</label><input class="input h44" type="date"></div>
      <div class="field"><label>End Date</label><input class="input h44" type="date"></div>
      <div class="field"><label>Currency</label><select class="input h44"><option>MWK</option><option>USD</option></select></div>
    </div>
  </div>

  <div class="card-sec">
    <div class="sec-head">[icon]<h2>Journal Lines</h2><span class="rule"></span>
      <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-ghost btn-xs">⧉ Copy Previous Lines</button>
        <button class="btn btn-ghost btn-xs">＋ Add Line</button>
      </div></div>
    <div class="li-wrap">
      <table>
        <thead><tr><th>Account</th><th>Description</th><th class="num">Debit</th><th class="num">Credit</th><th>Department</th><th>Cost Centre</th><th></th></tr></thead>
        <tbody>
          <tr><td><select class="ci"><option>{account}</option></select></td><td><input class="ci"></td>
            <td class="num"><input class="ci"></td><td class="num"><input class="ci"></td>
            <td><select class="ci"><option>{department}</option></select></td><td><select class="ci"><option>{cost_centre}</option></select></td>
            <td><div class="row-act"><button class="ibtn del">🗑</button></div></td></tr>
          <!-- repeat per line, multi-debit/multi-credit -->
        </tbody>
        <tfoot><tr><td colspan="2">Totals</td><td class="numr">{total_debit}</td><td class="numr">{total_credit}</td><td colspan="3"></td></tr></tfoot>
      </table>
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin-top:12px;flex-wrap:wrap">
      <span class="okchip ok">✓ Balanced — debits equal credits</span>
      <button class="btn btn-ghost btn-xs" style="margin-left:auto">Validate Balance</button>
      <span class="tchip">Tax: {tax_note}</span>
    </div>
  </div>

  <div class="card-sec">
    <div class="sec-head">[icon]<h2>Scheduling</h2><span class="rule"></span>
      <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn btn-ghost btn-xs">👁 Preview Schedule</button>
        <button class="btn btn-ghost btn-xs">⚗ Test Run</button>
      </div></div>
    <div class="g4">
      <div class="field"><label>Frequency</label><select class="input h44"><option>Monthly</option><option>Daily</option><option>Weekly</option><option>Quarterly</option><option>Semi-annually</option><option>Annually</option><option>Custom</option></select></div>
      <div class="field"><label>Day of Month</label><select class="input h44"></select></div>
      <div class="field"><label>Occurrences</label><input class="input h44"></div>
      <div class="field"><label>Next Execution</label><input class="input h44" disabled></div>
      <div class="field"><label>Generation Mode</label><select class="input h44"><option>Auto-post</option><option>Draft only</option><option>Approval first</option></select></div>
      <div class="field"><label>Email Notification</label><select class="input h44"><option>Before posting</option><option>After posting</option><option>None</option></select></div>
    </div>
    <div class="attchips">
      <span style="font-size:11px;font-weight:800;color:var(--faint);align-self:center">PREVIEW:</span>
      <span class="att">{date1}</span><span class="att">{date2}</span><span class="att">{date3}</span><span class="att">{date4}</span><span class="att">+{n} more</span>
    </div>
  </div>
</section>
```

Functional spec: sticky head, Cancel + [Save Draft | Activate Schedule ⚡] seg
control. "Use Template" prefills from §9. Journal Lines: multi-debit/
multi-credit, balancing enforced before Activate, tax calculated per item's
existing tax settings, multi-currency via existing rate tables, [Copy Previous
Lines] and [Add Line]. Scheduling: [Preview Schedule] renders next-run date
chips ("+n more"); [Test Run] generates a non-persisted draft and shows the
result (never posts — §17.7). Edit mode reuses this exact screen pre-filled;
schedule changes get audited (§13).

---

## 9 · TEMPLATES (`rj.templates`)

**Structure reference** (Stage 4, top half):

```html
<div class="page-head">
  <div><h1>Journal Templates</h1><div class="sub">Reusable formats for common recurring entries.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-cta btn-sm">＋ New Template</button></div>
</div>
<div class="mcards">
  <div class="mcard"><span class="t">{template_name}</span><span class="d">DR {account} · CR {account} · {amount} · {frequency}{integration_note}</span>
    <div class="foot">
      <button class="btn btn-sec btn-xs">Use Template</button>
      <button class="btn btn-ghost btn-xs">✎ Edit</button>
      <button class="btn btn-ghost btn-xs">⧉</button>
      <button class="btn btn-danger-o btn-xs">🗑</button>
      <!-- [Share] appended here where permissions allow -->
    </div></div>
  <!-- grid 3→2→1 responsive per .mcards -->
</div>
```

Functional spec: templates are seeded from/reflect existing live data (Monthly
Rent, Depreciation — Vehicles, Loan Interest, Insurance Premium Amortisation,
Software Subscriptions, Salaries Accrual are the example set — actual templates
come from what exists in the system); integration note shown when a template
links to Fixed Assets / Payroll / Loans. "Use Template" → `rj.create` prefilled
with lines + schedule defaults. [Share] only where permissions allow.

---

## 10 · SCHEDULED (`rj.scheduled`)

**Structure reference** (Stage 4, bottom half):

```html
<section class="card">
  <div class="card-h"><h2>Scheduled Journals — next 30 days</h2><span class="fmt" style="margin-left:auto">{n} runs</span></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
    <table>
      <thead><tr><th>Next Run</th><th>Journal</th><th>Frequency</th><th class="num">Amount</th><th>Mode</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr><td class="em">{date}</td><td style="font-weight:700;color:var(--ink)">{journal_name}</td><td class="em">{frequency}</td>
          <td class="numr bold">{amount}</td><td><span class="tchip">{mode}</span></td>
          <td><span class="badge b-act"><span class="bdot"></span>Scheduled</span></td>
          <td><div class="row-act"><button class="btn btn-sec btn-xs">Run Now</button><span class="more"><button class="ibtn">⋯</button>
            <div class="more-menu"><button class="more-item">Skip Run</button><button class="more-item">📅 Reschedule</button><button class="more-item">⏸ Pause</button><button class="more-item">✎ Edit Schedule</button></div></span></div></td></tr>
        <!-- Paused rows: status badge b-pend "Paused", action becomes [Resume] -->
      </tbody>
    </table>
  </div>
</section>
```

Functional spec: page head "Scheduled Journals" + chip "{n} runs next 30
days". [Run Now] triggers immediate generation (§17). Skip Run marks the
occurrence skipped (audited). Reschedule opens a date picker. Pause/Resume
toggles status.

---

## 11 · GENERATED (`rj.generated`)

**Structure reference** (Stage 5, top half):

```html
<div class="page-head">
  <div><h1>Generated Journals</h1><div class="sub">Everything the automation created — review, approve, post, reverse, print.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap"><button class="btn btn-ghost btn-sm">⇩ Export</button></div>
</div>
<section class="card">
  <div class="card-h"><h2>Generated Journals</h2>
    <div style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap">
      <span class="tchip">Draft {n}</span><span class="tchip">Pending {n}</span><span class="tchip">Posted {n}</span><span class="tchip">Reversed {n}</span>
    </div></div>
  <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
    <table>
      <thead><tr><th>Journal №</th><th>Date</th><th>Reference / Source</th><th class="num">Amount</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <tr><td class="mono">{RJV-№}</td><td class="em">{date}</td><td class="em">{reference} · {RJ-№}</td>
          <td class="numr bold">{amount}</td><td><span class="badge b-pend"><span class="bdot"></span>Pending Approval</span></td>
          <td><div class="row-act"><button class="btn btn-sec btn-xs">Approve</button><button class="btn btn-danger-o btn-xs">Reject</button></div></td></tr>
        <!-- Posted → [👁][🖨] + ⋯ [Reverse · Audit]; Draft → [Post][✎ Edit if allowed]; Reversed → [👁] only -->
      </tbody>
    </table>
  </div>
</section>
```

Functional spec: header chips are live Draft/Pending/Posted/Reversed counts.
Approve/Post/Reverse call the EXISTING journal engine handlers (§2) — this
module never re-implements posting logic. "Edit before posting" only shown
when settings allow it and status is Draft.

---

## 12 · APPROVAL QUEUE (`rj.approvals`)

**Structure reference** (Stage 5, bottom half):

```html
<section class="card">
  <div class="card-h"><h2>Approval Queue</h2><span class="fmt" style="margin-left:auto">comments tracked</span></div>
  <div class="card-sec" style="display:flex;flex-direction:column;gap:12px">
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
      <span class="mono">{RJV-№}</span><span class="em" style="font-size:12.5px">{description} · {amount} · submitted by {engine_or_user}</span>
      <span style="margin-left:auto;display:flex;gap:8px">
        <button class="btn btn-sec btn-xs">Approve</button><button class="btn btn-danger-o btn-xs">Reject</button><button class="btn btn-ghost btn-xs">Request Changes</button>
      </span>
    </div>
    <input class="input" placeholder="Add approval comment (mandatory on reject / changes)…" style="max-width:560px">
    <!-- repeat per pending journal card; approval history (who/when/comment) shown per item, immutable -->
  </div>
</section>
```

Functional spec: comment mandatory on Reject / Request Changes, stored in
approval history immutably.

---

## 13 · HISTORY / AUDIT (`rj.history`)

**Structure reference** (Stage 6, top):

```html
<div class="page-head">
  <div><h1>Journal History / Audit Trail</h1><div class="sub">Full audit trail of the automation engine.</div></div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost btn-sm">⇩ Export History</button>
    <button class="btn btn-ghost btn-sm">🕘 View Audit Log</button>
  </div>
</div>
<section class="card">
  <div class="card-h"><h2>Journal History / Audit Trail</h2><span class="fmt" style="margin-left:auto">immutable</span></div>
  <div class="card-sec">
    <div class="audit">
      <div class="arow"><span class="when">{timestamp}</span><span class="who">{actor}</span><span class="what">{action text with <b>bold refs</b>}</span></div>
      <!-- repeat: created, modified, generated, auto-posted, failed (+reason+retry), reversed (+reason), approved/rejected (+comment), schedule changes -->
    </div>
  </div>
</section>
```

Functional spec: rows immutable; actor is "Engine" or a named user; filters:
journal ref search + event type + date range; export Excel/PDF.

---

## 14 · REPORTS (`rj.reports`)

**Structure reference** (Stage 6, middle):

```html
<div class="repcards">
  <div class="repcard"><span class="t">Recurring Journal Summary</span><span class="d">All templates with status, frequency and totals.</span>
    <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="#">Open →</a></div></div>
  <!-- Scheduled Journal Report / Generated Journal Report / Journal Posting History /
       Failed Journal Runs / Expired-Upcoming Control — same card pattern -->
</div>
```

Functional spec: use existing report pages/handlers if present for each of the
six reports; otherwise build minimal pages using the system's existing report
pattern. Buttons: [Generate][Print][Export PDF][Export Excel].

---

## 15 · SETTINGS (`rj.settings`)

**Structure reference** (Stage 6, bottom):

```html
<section class="card">
  <div class="card-h"><h2>Recurring Journals Settings</h2><span class="fmt" style="margin-left:auto">admin</span></div>
  <div class="card-sec">
    <div class="g3">
      <div class="fld"><div class="l">Journal Numbering</div><div class="v">{pattern}</div></div>
      <div class="fld"><div class="l">Approval Workflow</div><div class="v">{thresholds}</div></div>
      <div class="fld"><div class="l">Auto-posting Rules</div><div class="v">{rules}</div></div>
      <div class="fld"><div class="l">Notifications</div><div class="v">{notification_settings}</div></div>
      <div class="fld"><div class="l">Period Locking</div><div class="v">{lock_behavior}</div></div>
      <div class="fld"><div class="l">Default Accounts</div><div class="v">{defaults}</div></div>
    </div>
  </div>
</section>
```

Functional spec: read/edit card, six fields as above (numbering pattern e.g.
`RJV-{yyyy}-{seq:6}`; approval thresholds e.g. "> 1M → Finance Manager";
auto-posting rules e.g. "auto-post unless type = Accrual"; notifications —
email before run + failure alerts + channels; period locking — block
generation into locked periods; default accounts — suspense for unmatched +
per-type defaults). Use existing settings handlers; create a minimal page only
if none exists.

---

## 16 · AUTOMATION ENGINE RULES

16.1 Scheduler runs daily; for each Active schedule due: generate journal
     (DR/CR lines from template with department/cost-centre), reference
     `RJ-№`, number `RJV-{yyyy}-{seq:6}`.
16.2 Generation Mode: Auto-post → post via the EXISTING journal posting
     handler; Draft only → save as Draft; Approval first → route to
     `rj.approvals`.
16.3 Period lock: generation into a locked period = Failed run (reason
     "period locked"), no posting; retried next unlock or manual Run Now.
16.4 Failures logged with reason + retry count; dashboard Failed Generations
     KPI counts them; [Run Now] retries manually.
16.5 Occurrences/expiry: decrement occurrences per run; when 0 or End Date
     passed → status Expired (Renew action re-activates with new dates).
16.6 Notifications: email per schedule setting (before/after posting) +
     failure alerts via the EXISTING notification handlers.
16.7 Test Run produces a non-persisted preview journal (marked "test") —
     never posts.
16.8 Multi-company/multi-currency: per-schedule company + currency,
     conversions via existing rate tables.

---

## 17 · RAILS REGISTRY (per page — rails feature itself unchanged)

- `rj.dashboard` → Quick Nav: Recurring Journals List, Run Scheduled,
  Generated Journals, Reports.
- `rj.index` → Views: All Journals (active), Active, Paused, Expired · Reports:
  Scheduled Journal Report, Failed Runs.
- `rj.create` / `rj.edit` → Quick Nav: Journal Templates, Recurring Journals
  List, Settings.
- `rj.templates` → Quick Nav: New Template, Recurring Journals List, Create
  Journal.
- `rj.scheduled` → Views: Scheduled (active), Paused · Quick Nav: Run Now,
  List.
- `rj.generated` → Views: All Generated (active), Draft, Pending Approval,
  Posted, Reversed · Quick Nav: Approval Queue, List.
- `rj.approvals` → Quick Nav: Generated Journals, List.
- `rj.history` → Quick Nav: Generated Journals, Scheduled, List.
- `rj.reports` → Quick Nav: List, Scheduled, History.
- `rj.settings` → Quick Nav: List, Templates.

---

## 18 · ACCESSIBILITY / RESPONSIVE

18.1 ARIA: status boxes `aria-pressed`; ⋯ menus `aria-haspopup`; focus rings
     `#94a3b8`; table `th` uses `scope`; balanced/failure chips `aria-live`.
18.2 ≤1100px: statgrid 2-col. ≤1000px: KPI 2-col + mcards/repcards 2-col.
     ≤768px: slim rail hidden, `g4` → 1fr 1fr, tables horizontal-scroll
     inside cards, no horizontal PAGE scrollbar at 1280/1024/768.

---

## 19 · CONSTRAINTS (recap of §-1, don't lose these under load)

- No changes to the rails feature itself or to any other module.
- No changes to posting/reversal/approval/period-lock handler internals — this
  module calls them, never reimplements them.
- No new packages unless something here is genuinely impossible without one —
  flag it and ask first.
- ONE shared component/CSS per pattern (one badge partial, one table partial,
  one card partial reused across all ten pages — not ten copies).
- No hardcoded sample data anywhere — live ledger only. Every `{placeholder}`
  above is a real data binding, not a literal string to ship.
- Audit trail is immutable — no edit/delete path on `rj.history` rows, ever.
- Old module fully removed per §1 before new pages are wired into the menu.

---

## 20 · VERIFY (every page — all ten)

20.1 **Route check:** all ten routes exist, render, and are reachable from the
     module menu; old module's routes return 404/redirect, not a ghost page.
20.2 **Action audit:** every button listed in §6–§15 triggers the SAME
     handler/route as the equivalent old-module control did where one
     existed (spot-click each) — dashboard's four header actions + Run Now,
     list's run/view/edit/duplicate/pause/resume/history/delete/renew/export,
     create's Save Draft/Activate/Use Template/Add Line/Copy Previous/
     Validate/Preview/Test Run, templates' use/edit/duplicate/delete,
     scheduled's run/skip/reschedule/pause/resume/edit, generated's
     approve/reject/post/edit/view/print/reverse/audit, approvals'
     approve/reject/request-changes + comments, history's export, reports'
     opens, settings' edits.
20.3 **Engine:** schedule generates on due date per mode; period-lock produces
     a Failed run; occurrences/expiry flip to Expired; test run never
     persists; reversals go through the existing handler; approvals require a
     comment on reject.
20.4 **Math:** journal lines balance is enforced; `tfoot` totals = sums; KPI
     counts = actual table row counts.
20.5 **Rails:** slim rail + drawer + per-page pins + global pin behave exactly
     as the existing rails implementation on these and every other page;
     pages render the §17 registries.
20.6 **Design fidelity:** spot-check each page against §3's classes — badges,
     chips, kpis, tables, cards all use the extracted classes verbatim, not
     re-approximated styles.
20.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.

## 21 · REPORT

Produce, in this order:
1. Old-module inventory (§0) and what was removed vs migrated (§1).
2. Files touched for the new module, grouped by page/route.
3. Page-route table — all ten, confirmed built, old routes confirmed gone.
4. Action-mapping table: old control → new location → handler confirmed same.
5. Status/chip table (badge/chip class used per state, matching §4).
6. Rails registry per page (§17), confirmed rendered.
7. Automation engine event-log samples (a generated run, a failed run, a
   test run).
8. Explicit confirmation: rails feature, other modules, and all existing
   journal-engine functionality unchanged; no page skipped; nothing touched
   outside the §-1 scope boundary.
