BUDGETING MODULE — FULL IMPLEMENTATION SPEC (DASHBOARD / SETUP / TEMPLATES / CREATE /
APPROVAL / MONITORING / BUDGET PROFILE / VS-ACTUAL / FORECASTING / ADJUSTMENTS / ALERTS /
REPORTS / SETTINGS). Rebuild Budgeting as the plan-vs-actual control centre: plan income
and spending by department/branch/project/cost-centre, approve and lock budgets, then
compare live actuals against them with variances, forecasts, adjustments and alerts.
ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every budget page per the registry in §16: resting slim
icon rail with teal Expand; drawer with pin (true toggle, remembered per page) + X at
top-right; Favorites "Pin rails to right side bar" global toggle unchanged.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: expense/payment/sales/payroll/purchasing
posting handlers, GL journals, chart of accounts, tax, export/print/email handlers,
search/filter/pagination params, auth/permissions and all routes remain EXACTLY as-is.
Actuals are ALWAYS computed live from posted GL transactions — never stored or edited
inside the budgeting module. Budgets never block posting by default; spending controls
act only as configurable warn/block hooks (§17) implemented as listeners on existing
posting events, without modifying other modules' code.

==================== 0 · DISCOVERY ====================
0.1 Inventory budget routes/pages + handlers (if any exist): dashboard, setup/settings,
templates, create/edit/show, approvals, monitoring, vs-actual, forecasting, adjustments,
alerts, reports.
0.2 List CURRENT controls + handlers per page (drives §20 audit).
0.3 Locate: chart of accounts + account→category mapping, departments, branches, cost
centres, projects, fiscal-year settings, GL posting events, expense module posting
event, notification channels (email/SMS/system), currency rates.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + UTILIZATION THRESHOLDS ====================
2.1 Badges (pill + dot): Draft rgba(17,69,75,.07)/.2/#11454b; Pending Approval
rgba(217,119,6,.10)/.35/#b45309 (dot #d97706); Approved = mint gradient (#ecfdf3→
#dcf5e7, rgba(22,163,74,.28), #15803d, dot #22c55e); Locked gray
rgba(138,165,167,.15)/.5/#5f7476; Over Budget rgba(185,28,28,.08)/.3/#b91c1c;
Changes Requested = steel rgba(70,112,140,.10)/.4/#46708C.
2.2 Utilization bar (.ubar) thresholds (expense lines): ≤84% teal (u-ok), 85–99% amber
(u-warn), ≥100% red (u-bad); income lines invert meaning (below target = amber/red).
Variance chips: .vchip ok/warn/bad same palette.

==================== 3 · DASHBOARD (budgets.dashboard) ====================
3.1 NON-sticky page head: h1 "Budgeting Centre" + sub "Plan income and spending, then
compare actual performance against approved budgets."; right: [🔔 View Alerts ghost +
count chip][📤 Export Dashboard][📊 View Reports][➕ Create Budget CTA].
3.2 KPI ROW 1 (4): hero Total Annual Budget (teal gradient; sub "{FY} · approved &
locked") · Actual Spending (sub "to {today}") · Remaining Budget (sub "{pct}% of
annual") · Over Budget (red; "N lines over →" link).
3.3 KPI ROW 2 (4): Budget Utilization % · Revenue vs Budget % (green when on track) ·
Under Budget (green) · Active Alerts (amber; "1 exceeded · 2 nearing").
3.4 ANALYTICS ROW (2 cards ≤1000px stack): Department Performance (label + threshold-
colored utilization bar + %) · Budget Alerts preview (severity chips Exceeded/Nearing/
Unusual + message + channel chips Email/SMS/System + Configure Alerts).

==================== 4 · BUDGET SETUP / SETTINGS (budgets.settings) =================
4.1 Cards (existing settings handlers; create minimal pages only where absent):
 Budget Periods (Monthly / Quarterly / Annual / Custom; add/edit periods).
 Budget Types (Operating / Capital / Project / Department / Cash Flow).
 Categories & Account Mapping (expense/income categories each mapped to GL accounts;
 tree editor).
 Approval Levels (define chain e.g. Prepared → Finance Review → Board; enable/disable).
 Fiscal Year Settings · Budget Numbering · User Permissions.

==================== 5 · BUDGET TEMPLATES (budgets.templates) ====================
5.1 Table/cards of templates: Name, Basis (Blank / Prior Actuals / Standard /
Zero-Based), Lines count, Last used; actions Edit · Duplicate · Delete (confirm) ·
Use → prefills create.
5.2 [Create Template] saves current budget lines as template; [Copy previous year's
budget] generator from prior FY actuals (rounded, configurable uplift %).

==================== 6 · CREATE BUDGET (budgets.create) ====================
6.1 Sticky head: h1 "Create Budget" + sub; right Cancel ghost + seg [Save Draft ghost |
Submit for Approval ⤴ CTA].
6.2 TEMPLATE ROW: "START FROM:" chips [Blank][FY{prior} Actuals][Standard Operating]
[Zero-Based] + [⧉ Copy Previous Budget][📥 Import from Excel] (existing import handler
if present; else parse xlsx/csv columns Category/Account/Annual).
6.3 SECTION "Budget Details" (g4): Budget Name* / Budget Type select / Fiscal Year /
Period (Annual monthly spread / Quarterly / Monthly / Custom) / Department / Branch /
Project–Cost Centre (optional) / Currency.
6.4 SECTION "Budget Lines": table Type chip (Income ok / Expense warn) / Category–
Account select (from mapping) / Annual Amount input / Monthly (auto = annual ÷ periods;
editable when Custom distribution) / Distribution note (Even spread / Seasonal ±n%) /
delete; [＋ Add Line]; tfoot totals: Income total · Expense total · Net surplus/deficit.
Validate: totals > 0; deficit shows amber note (allowed unless settings require balance).
6.5 EDIT MODE: same screen pre-filled; editing rules per §10.

==================== 7 · APPROVAL WORKFLOW ====================
7.1 Chain from settings (§4). Submit → status Pending Approval; each level: Approve /
Reject / Request Changes with mandatory comment on reject/changes; approval history
stored (who/when/comment).
7.2 On final approval: status Approved then Locked (chip); budget becomes immutable
(§10); notification sent to preparer.
7.3 Mobile approval: approvers can approve from rail/alert link on any device
(existing auth).

==================== 8 · MONITORING (budgets.index) ====================
8.1 Page head: h1 "Budgets & Monitoring" + sub; right [⇩ Export Data][＋ New Budget CTA].
8.2 STATUS BOXES (5): All(t-ink) / Draft / Pending / Approved / Over Budget; live
counts; click sets filter.
8.3 VIEW TOGGLE seg2 [Department | Account | Project | Branch] regroups rows (existing
dimensions); search (budget, department, account).
8.4 TABLE: Budget/Dimension / Period / Budget (K) / Actual (K) (live from GL) /
Remaining (K) (red when negative) / Utilization (.ubar + %) / Status (badge or
On track / Nearing limit / Over budget chip) / actions (View + ⋯: View Transactions,
Drill Down, Export). Row click → budget profile (or dimension drill page).

==================== 9 · BUDGET PROFILE (budgets.show) — HEADER STANDARD ===========
9.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Budgeting › Budgets › {BUD-code}
(here mono). RIGHT cluster: [✎ Edit (only when not locked)][⇄ Request Adjustment]
[🖨 Print] + [⋯ More: Export PDF/Excel · ⇄ Transfer Budget · 🕘 View History ·
🔒 Lock/Unlock (per permission)].
9.2 PROFILE CARD (identity only, NO buttons): tile letter; name + mono chip + status
badge + Locked chip; meta chips: period, scope (departments/branches), prepared by,
approved by/date.
9.3 SUMMARY BAR: Budget / Actual (live) / Remaining / hero Utilization %.
9.4 TABBED BUDGET CARD (10 tabs; client-side panes):
 Overview → g3 read-only: code, type, fiscal year, period, scope, currency, prepared
 by, approved by, status.
 Budget Lines → table Type / Category / Budget / Actual / Remaining / Utilization bar.
 Income → table line / budget / actual / variance (revenue below budget = red).
 Expenses → table line / budget / actual / variance (over = red, under = green) +
 tfoot totals.
 Actual Transactions → link "View Transactions in Expenses module →" + mini table
 (Date / Reference / Description / Budget Line / Amount) from GL (live, paginated).
 Variance Analysis → threshold-colored bars per line + [View Details][Generate Chart]
 [Export Report].
 Approvals → workflow steps (done/current/todo with who + timestamps + comments).
 Adjustments → [⇄ Request Adjustment][⇄ Transfer Budget] + table Adj № / Date / Type
 (Increase/Reduce/Transfer) / From → To / Amount / Status (Pending/Approved).
 Attachments → chips + Upload.
 Audit Trail → rows incl. field-level changes ("Marketing line changed 18,000,000 →
 15,000,000 · reason …") with user + timestamp; lock/unlock events.

==================== 10 · EDITING RULES (ENFORCED IN UI) ====================
Draft = fully editable. Pending = limited (or Request Changes returns to Draft).
Approved/Locked = NO direct edits: only Adjustments/Transfers (each with own approval
per settings) — preserve audit trail. Surface lock notices on Edit attempts.

==================== 11 · BUDGET VS ACTUAL (budgets.vsactual) ====================
11.1 Filters: budget select + period + dimension (department/account/branch/project).
11.2 Tables: Expense Analysis (Budget / Actual / Variance / Variance % + chip) and
Revenue Analysis (Expected / Actual / Difference); tfoot totals; variance % =
(actual−budget)/budget with sign semantics per type.
11.3 [View Details][Generate Chart][Export Report] (chart = grouped bar budget vs
actual per line; existing chart lib or simple CSS bars).

==================== 12 · FORECASTING (budgets.forecast) ====================
12.1 [⚡ Generate Forecast] computes rolling forecast per line from historical postings
(12-month weighted average; method + confidence chip shown).
12.2 SCENARIOS seg2 [Base | Best case | Worst case] (configurable multipliers, e.g.
±5/±10%).
12.3 Forecast bars for remaining periods; warnings when a forecast period exceeds
remaining budget (amber chip "⚠ {month} exceeds remaining budget by {amount}").
12.4 [✎ Adjust Forecast] (manual overrides stored per scenario, audited) ·
[⇄ Compare Forecasts] (table base vs best vs worst vs budget).

==================== 13 · ADJUSTMENTS (budgets.adjustments) ====================
13.1 [⇄ Request Adjustment]: type Increase / Reduce / Transfer; from/to line or
department; amount; reason mandatory; approval per settings; history table
(Adj № / Date / Type / From → To / Amount / Status / Approved by).
13.2 Approved adjustments update budget lines (versioned; original retained); audit
trail entry; locked budgets change ONLY via this path.

==================== 14 · ALERTS (budgets.alerts) ====================
14.1 RULES config: thresholds (default warn 85%, exceed 100%), unusual spending
(actual > 3-month average × 1.25), low remaining balance (< 10%); per rule: enabled,
scope (budget/department/line), channels (Email / SMS / System), recipients.
14.2 ALERTS LIST: severity chips (Exceeded red / Nearing amber / Unusual amber) +
message + link to line + [Send Notification][Configure Alerts]; history of sent
notifications.
14.3 Alerts recompute on posting events (listener) + nightly; never block posting
unless §17 block mode enabled.

==================== 15 · REPORTS (budgets.reports) ====================
Report cards (grid 4→2→1): Annual Budget Report · Monthly Budget Report · Budget vs
Actual · Expense Variance · Revenue Variance · Department Budget Report · Project
Budget Report · Cash Flow Budget Report. Each: icon tile, description, PDF + Excel
chips, Open →. Existing report pages if present; else MINIMAL report pages using the
system report pattern; [Generate][Print][Export PDF][Export Excel] buttons.

==================== 16 · RAILS REGISTRY (per page; rails feature unchanged) =======
budgets.dashboard → Quick Nav [All Budgets, Budget vs Actual, Create Budget,
Budget Reports].
budgets.index → Views [All Budgets(active), Draft, Pending Approval, Approved,
Over Budget] + Reports [Budget vs Actual, Expense Variance].
budgets.templates → Quick Nav [Create Budget, All Budgets].
budgets.create/edit → Quick Nav [Budget Templates, Copy Previous Year, All Budgets].
budgets.show → Quick Nav [Request Adjustment, Budget vs Actual, Print, All Budgets].
budgets.forecast → Quick Nav [All Budgets, Compare Forecasts, Budget Reports].
budgets.alerts → Quick Nav [All Budgets, Configure Alerts, Budget Reports].
budgets.reports → Quick Nav [All Budgets, Budget Alerts, Budget Settings].
budgets.settings → Quick Nav [All Budgets, Chart of Accounts, General Ledger].

==================== 17 · INTEGRATION & SPENDING CONTROLS ====================
17.1 Actuals = live GL sums per mapped account/department/cost-centre/branch/project
for the budget period; recompute on demand; no stored actuals anywhere.
17.2 Multi-currency: budget stores currency; cross-currency actuals convert at existing
rate tables; report shows conversion note.
17.3 SPENDING CONTROL HOOK (optional, per settings): listener on the existing expense
posting event; when a post would push a mapped line past warn/exceed threshold: surface
dialog (warn: reason required to proceed; block: prevent with message + "Request
Adjustment" shortcut). Implemented WITHOUT editing expense module code.
17.4 Integrations read-only elsewhere: Payroll/Inventory/Sales/Purchasing postings flow
into actuals automatically via GL mapping.

==================== 18 · ACCESSIBILITY / RESPONSIVE ====================
18.1 aria: status boxes aria-pressed; tabs role=tablist; ⋯ menus aria-haspopup;
breadcrumb nav; ubars aria-hidden with % in text; focus rings #94a3b8; tables th scope.
18.2 ≤1100px statgrid 3-col; ≤1000px KPI 2-col + analytics stack + repcards 2-col;
≤768px slim rail hidden, statgrid 2-col, g4 → 1fr 1fr, tables horizontal-scroll inside
cards; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 19 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting handler changes; no new
packages; ONE shared component/CSS per pattern; no hardcoded sample data (live registry
only); locking + adjustment-only edits enforced; audit trail on every budget change.

==================== 20 · VERIFY (EVERY PAGE) ====================
20.1 ACTION AUDIT: every button (Create/View Reports/Export/Alerts, status boxes, view
toggle, templates Create/Edit/Duplicate/Delete/Use, Copy Previous/Import, Save Draft/
Submit, Approve/Reject/Request Changes, Edit/Adjustment/Transfer/Print/Lock/History,
forecast Generate/Adjust/Compare, alerts Configure/Send, report Opens, settings edits)
triggers the SAME handler/route as pre-implementation where it existed (spot-click each).
20.2 MATH: utilization % = actual/budget per line and overall; variance signs correct
for income vs expense; aging of alerts; forecast = weighted average ± scenario
multipliers; tfoot totals = sums.
20.3 CONTROLS: locked budget rejects direct edits (UI + server rule); adjustments
versioned; spending-control hook warns/blocks per settings without touching expense
handlers; alerts fire at 85%/100%/unusual and log sends.
20.4 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; budget pages render §16 registries.
20.5 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/threshold table; rail registry per page; settings/templates/
report pages created (if any); confirmation rails + all existing functionality
unchanged and actuals always live from GL.