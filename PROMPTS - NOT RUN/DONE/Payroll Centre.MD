PAYROLL CENTRE — CONSOLIDATED FULL IMPLEMENTATION SPEC (DASHBOARD / EMPLOYEES /
ONBOARDING / EMPLOYEE PROFILE / PAYROLL RUNS / PAYSLIPS / STATUTORY / PEOPLE OPS /
REPORTS + SETTINGS). Build ALL NINE pages as ONE consolidated module exactly as designed
in the consolidated mockup. ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every payroll page per the registry in §16: resting slim
icon rail with teal Expand; drawer with pin (true toggle, remembered per page) + X at
top-right; Favorites "Pin rails to right side bar" global toggle unchanged; drawer
hidden whenever the full rail is not displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: journal posting handlers, GL account
mappings, bank payment/bank-file handlers, period locking, approval workflow engine,
tax/pension calculation sources, notification handlers, export/print/email handlers,
search/filter/pagination params, auth/permissions and all routes remain EXACTLY as-is.
Payroll posts to the ledger ONLY through the existing journal posting handler; salary
payments ONLY through existing banking handlers. This spec adds the payroll UI +
calculation orchestration on top, without altering them.

==================== 0 · DISCOVERY ====================
0.1 Inventory payroll routes/pages + handlers (if any exist): dashboard, employees
index/create/edit/show, runs, payslips, statutory tables, loans, attendance, leave,
reports, settings.
0.2 List CURRENT controls + handlers per page (drives §19 audit).
0.3 Locate: chart of accounts (salary expense, PAYE payable, pension payable, loans
receivable, net-pay payable, department/cost-centre dimensions), bank accounts,
departments/branches/cost centres, employee loan module, leave module, attendance/
biometric import, currency rates, payslip templates.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + CHIPS ====================
2.1 Employee badges (pill + dot): Active = mint gradient (#ecfdf3→#dcf5e7,
rgba(22,163,74,.28), #15803d, dot #22c55e); On Leave amber rgba(217,119,6,.10)/.35/
#b45309; Contract steel rgba(70,112,140,.10)/.4/#46708C; Terminated red
rgba(185,28,28,.08)/.3/#b91c1c; Pending Approval amber; Posted/Paid mint; Draft ink
rgba(17,69,75,.07)/.2/#11454b; Locked gray rgba(138,165,167,.15)/.5/#5f7476.
2.2 Type chips (.tchip): Permanent gray; Casual amber-tint; Contract steel; Taxable
green-tint; employment-type and taxable flags.
2.3 Amounts: deductions/PAYE/pension/loan red in run tables; net pay green; totals
bold; tfoot border-top 1.5px #17565d.

==================== 3 · PAGE INVENTORY — BUILD ALL NINE (no skips) ==============
payroll.dashboard   Dashboard (§4) — MANDATORY.
payroll.employees   Employees list (§5).
payroll.create      Onboard Employee wizard (§6) + edit reuses steps.
payroll.show        Employee Profile 12-tab card (§7).
payroll.runs        Payroll Runs (§8).
payroll.payslips    Payslips (§9).
payroll.statutory   Earnings · Deductions · PAYE · Pension · Benefits (§10).
payroll.people      Loans · Attendance · Leave (§11).
payroll.reports     Reports + Settings (§12–13).
Module menu lists all nine in this order.

==================== 4 · DASHBOARD (payroll.dashboard) ====================
4.1 NON-sticky page head: h1 "Payroll Centre" + sub; right [⚙ Settings ghost]
[📊 Reports ghost][📄 Payslips ghost][▶ Run Payroll secondary][➕ Add Employee CTA].
4.2 KPI ROW 1 (4): hero Total Gross Pay (teal gradient; sub "{period} run · {n}
employees") · Total Net Pay (sub "after PAYE, pension, loans") · PAYE Tax Payable
(amber; sub "due {date}") · Pension Contributions (sub "+ employer {amount}").
4.3 KPI ROW 2 (4): Total Employees (sub "{active} active · {leave} on leave") · Active
(sub "{perm} permanent · {contract} contract") · Employees on Leave (sub "{paid} paid ·
{unpaid} unpaid") · Pending Approvals (amber; "review run →").
4.4 LAST RUN card: header "Last Run — {PR-№}" + Posted badge + right [📄 Payslips]
[🏦 Bank File][ Audit]; body facts row: Period / Paid {n} employees / Gross / Net /
Posted To {JV ref}.

==================== 5 · EMPLOYEES (payroll.employees) ====================
5.1 Page head: h1 "Employees" + sub; right [⋯ More: Import Employees · Export
Employees] + [➕ Add Employee CTA].
5.2 STATUS BOXES (5): All(t-ink) / Active(t-mint) / On Leave(t-amber) / Contract
(t-steel) / Terminated(t-red); live counts; active teal ring; click sets EXISTING
filter param.
5.3 CONTROLS: search (№, name, NRC) + Department + Branch + Payroll Group selects.
5.4 TABLE: Emp № (mono) / Employee Name (700) / Department / Position / Type chip /
Joined / Basic (bold) / Status badge / actions (👁 · ⋯: Edit · Upload Documents ·
Update Salary · Record Leave · Issue Loan · Deactivate · Terminate (danger, confirm +
final-settlement rule)). Pagination existing.

==================== 6 · ONBOARDING (payroll.create) — GUIDED WIZARD =============
6.1 Header: breadcrumb + h1 "Onboard a new employee" + sub + "Finish later" link.
6.2 HORIZONTAL STEPPER (.hsteps): 6 steps Personal → Employment → Tax & Pension →
Bank & Pay → Compensation → Review; states done (teal ✓ + filled connector) / cur
(ring) / todo (gray); scrollable on small screens; chips clickable to visited steps.
6.3 STEP CARD (.formcard): kick "Step {n} · {name}", conversational h1, sub with smart
defaults note; body = 3-COLUMN field grid (.fgrid repeat(3,1fr); 2-col ≤1000px; 1-col
≤640px); fields per step:
 Step 1 Personal: Employee №* (auto), First*, Middle, Last*, Email, Phone, DOB,
 Gender, National ID, Emergency Contact, Address (full).
 Step 2 Employment: Position*, Department* (+suggestion chip "✦ Suggested cost centre
 · apply"), Branch, Employment type, Hire date* (✓ payroll group auto-set), Probation
 (auto), Supervisor, Cost centre, Payroll group.
 Step 3 Tax & Pension: Tax ID, PAYE Table (active), Pension Scheme, Pension Member №.
 Step 4 Bank & Pay: Bank Name, Account №, Account Name, Branch Code, Payment Method.
 Step 5 Compensation: Basic Pay*, Salary Frequency, Housing, Transport, Payslip
 Password (auto if blank).
 Step 6 Review & Documents: upload chips (NRC, contract, photo) + per-section summary
 with Edit links + consent checkbox.
6.4 Field affordances: 48px inputs radius 14, focus ring teal 4px; inline validation
ticks; helper microcopy; smart-suggestion chips.
6.5 ACTION BAR (.fc-bar): STICKY bottom, TRANSPARENT (NO background colour, no blur,
no shadow); left label "Step {n} of 6 · {pct}% complete · draft saved just now"
(muted); right [← Back light-bordered][Save & continue → teal]. Autosave indicator
live; drafts persisted.

==================== 7 · EMPLOYEE PROFILE (payroll.show) — HEADER STANDARD =======
7.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Payroll › Employees › {EMP-№} (here
mono). RIGHT cluster: [✎ Edit][💰 Update Salary][📄 Payslip] + [⋯ More:  Upload
Documents · 🌴 Record Leave · 💳 Issue Loan · ⛔ Terminate (danger)].
7.2 PROFILE CARD (identity only): initials tile; name + mono chip + status badge; meta
chips: position · department, type · frequency, joined, bank masked.
7.3 SALARY SUMMARY BAR: Basic / Gross (+allowances note) / Net (last run) / hero YTD
Gross (teal).
7.4 TABBED CARD (12 tabs; client-side panes): Personal (g3 facts) · Employment (g3) ·
Salary (basic/frequency/method) · Payroll History (table Period/Run/Gross/PAYE/Net/
status) · Earnings (table earning/basis/taxable/amount) · Deductions (table PAYE/
pension/loan) · Benefits (chips) · Loans (table principal/instalment/remaining/status) ·
Leave (entitlement/taken/balance) · Attendance (OT/late/absences) · Documents (chips +
upload) · Audit (field-level changes, e.g. salary 1,100,000 → 1,200,000 + reason).
7.5 [💰 Update Salary] writes dated adjustment (audited); [📄 Payslip] opens latest
payslip (§9).

==================== 8 · PAYROLL RUNS (payroll.runs) ====================
8.1 Page head: h1 "Payroll Runs" + sub; right [➕ New Payroll Run CTA].
8.2 WORKFLOW CHIPS (.wflow): ✓ Period → ✓ Load → ✓ Earnings → ✓ Deductions → ✓ PAYE →
● Review → Approve → Post → Payslips (states per run progress).
8.3 RUN CARD: header "Run — {PR-№} · {period}" + status badge (Draft/Pending Approval/
Posted/Locked) + right buttons [⚗ Calculate][✓ Approve secondary][🔒 Lock]
[📓 Post to Accounts][📄 Generate Payslips][🏦 Export Bank File].
8.4 RUN TABLE: Emp (mono) / Employee (700; leave note e.g. "unpaid leave") / Basic /
Allowances / Gross (bold) / PAYE (red) / Pension (red) / Loan (red) / Net (green);
tfoot "Totals — {n} employees" row. Per-employee drill to profile.
8.5 BEHAVIOUR: Calculate applies §14 engine; Approve per workflow threshold; Lock blocks
edits; Post creates journal via EXISTING handler (§14.6); Export Bank File produces
bank-compatible CSV via existing banking handler; Generate Payslips creates payslips
(§9) once Posted (or per settings).

==================== 9 · PAYSLIPS (payroll.payslips) ====================
9.1 Toolbar: breadcrumb + period segmented control (Jul/Aug/Sep) + [✉ Email][🖨 Print]
[📕 Download PDF secondary].
9.2 PAPER DOCUMENT (.paper): letterhead (logo + company + PAYE PIN) left; right
letter-spaced "PAYSLIP" + mono ref + tags [● Paid · date][Confidential]; MINIMIZED
"PAID" WATERMARK: font-size 40px, color rgba(17,69,75,.06), right 28px bottom 104px.
9.3 EMPLOYEE STRIP: avatar + name/role + facts grid (Period / Payment method / PAYE
PIN / Pension №).
9.4 EARNINGS / DEDUCTIONS two-column tables (Item/Basis/Amount; iconified items; ruled
totals Gross / Total Deductions).
9.5 NET-PAY CARD (.net-card): FULL-WIDTH gradient card (deep-teal→teal) placed
DIRECTLY ABOVE the three info cards; left Net Pay (2.2rem) + sub "62% of gross reaches
the employee · paid {date}"; right stats Gross / Deductions (soft red) / Employer cost.
9.6 THREE INFO CARDS below net card: Employer contributions · memo (pension 7.5%,
medical, total employer cost) · Year to date (YTD Gross/PAYE/Pension) · Leave &
attendance (balance/OT/absences).
9.7 FOOTER: QR placeholder + "Paid to {bank} ····{acct} on {date} · Ref {…} ·
System-generated, no signature required" + right "Questions? Contact HR →".
9.8 Employee self-service: employees see own payslips (mobile-friendly) gated by
permissions + payslip password where set; email delivery via existing handler.

==================== 10 · STATUTORY (payroll.statutory) ====================
10.1 Page head: h1 "Statutory & Pay Items Setup" + right [＋ Deduction][＋ Earning]
[＋ Add Tax Table CTA].
10.2 EARNINGS card: Basic · Housing (10%) · Transport · Overtime (1.5×) · Bonus ·
Commission with taxable flags + [Assign Employees].
10.3 DEDUCTIONS card: PAYE (auto) · Pension employee % · Loan repayment · Medical Aid;
types Statutory/Auto/Voluntary + [Configure Rule].
10.4 PAYE TAX TABLE card: header + Active badge + [✎ Edit Bracket][📥 Import Rates];
bands (first 100,000 0%; next 50,000 20%; next 350,000 25%; next 500,000 30%; above
35%); effective-date note; multiple tables with Activate.
10.5 PENSION & BENEFITS card: scheme rows (provider, employee %/employer %, members,
[Assign]) + benefit rows (employer-paid / taxable flags) + [＋ Scheme]
[📊 Generate Pension Report].

==================== 11 · PEOPLE OPS (payroll.people) ====================
11.1 Page head: h1 "People Operations" + right [🌴 Approve Leave][ Approve OT]
[💳 Issue Loan CTA].
11.2 LOANS card: loan № / employee / principal / instalment / paid x of y / remaining /
ends / status; auto-deducts each run (existing loan handler); [Generate Schedule]
[Record Payment].
11.3 ATTENDANCE card: period summary (biometric import source, OT hours pending
approval, late/absence counts); [📥 Import Attendance][ Calculate Pay]; rules from
settings (OT 1.5×, late >3 = deduction, absence deduction).
11.4 LEAVE card: employee leave (type paid/unpaid, period, balance, payroll deduction
for unpaid days automatic); [View Balance][Adjust Leave][Approve Leave].

==================== 12 · REPORTS (payroll.reports) ====================
Report cards (grid 4→2→1): Employee Register · Salary History · Employee Cost Report ·
Payroll Summary · Gross-to-Net · Payslip Register · PAYE Report · Pension Contribution
Report · Payroll Journal · Department Payroll Cost · Payroll Variance Report (anomaly
flags) · Bank Salary Upload (CSV). Each: PDF + Excel chips (CSV for bank), Open →.
Existing report pages if present; else MINIMAL pages using system report pattern;
[Generate][Print][Export PDF][Export Excel].

==================== 13 · SETTINGS (payroll.settings) ====================
g3 read/edit card: Payroll Periods (monthly 1st–last) / Currency (MWK, multi-currency
ready) / Salary Calculation Rules (OT 1.5×, late/absence rules) / Tax Settings (active
table, auto-update) / Pension Settings (5%/7.5%, provider) / Approval Workflow (runs >
threshold → Finance Manager) / Payslip Templates (Standard/Executive PDF) / Payroll
Numbering (PR-{yyyy}-{mm}, PS-{…}-{seq}) / User Permissions (HR edit · Finance approve ·
MD view). Existing settings handlers; create minimal page only if absent.

==================== 14 · CALCULATION ENGINE + ACCOUNTING RULES ==================
14.1 Per employee per run: Gross = Basic + fixed allowances + % allowances + OT (1.5×) +
bonus/commission; unpaid-leave days prorate Basic/allowances down.
14.2 PAYE from ACTIVE tax table on taxable gross; Pension = employee % × basic; employer
contribution = employer % × basic (memo + employer cost report); Loan = active
instalment; other deductions per assignment.
14.3 Net = Gross − (PAYE + pension employee + loan + other).
14.4 Rounding per settings; variance report flags run-over-run anomalies (> threshold %).
14.5 Multi-company/multi-currency: per-run company + currency; conversions via existing
rate tables.
14.6 POSTING (existing journal handler): DR Salary Expense (gross + employer
contributions by department/cost centre) CR PAYE Payable, Pension Payable, Loans
Receivable, Other Deductions Payable, Net Pay Payable; on payment: DR Net Pay Payable
CR Bank (existing banking handler). Period lock blocks posting into locked periods.
14.7 Payslip generation per posted run; bank file via existing banking export.

==================== 15 · EMPLOYEE GROUPS ====================
Employee Groups page (menu item): groups (e.g. Monthly — Permanent, Weekly — Casuals)
with member counts, default earnings/deductions assignments, pay frequency; used by
runs to load employees. (If routed separately, reuse list pattern.)

==================== 16 · RAILS REGISTRY (per page; rails feature unchanged) =======
payroll.dashboard → Quick Nav [Employees, Run Payroll, Payslips, Payroll Reports].
payroll.employees → Views [All Employees(active), Active, On Leave, Contract,
Terminated] + Reports [Employee Register, Employee Cost].
payroll.create → Quick Nav [Employees List, Employee Groups, Payroll Settings].
payroll.show → Quick Nav [Update Salary, Run Payroll, Payslip, Employees List].
payroll.runs → Views [All Runs(active), Draft, Pending Approval, Posted, Locked] +
Quick Nav [New Run, Approve, Post to Accounts, Generate Payslips].
payroll.payslips → Quick Nav [Generate Payslips, Email Payslips, Payroll Runs].
payroll.statutory → Quick Nav [Earnings, Deductions, PAYE Tables, Pension Schemes].
payroll.people → Quick Nav [Issue Loan, Import Attendance, Approve Leave].
payroll.reports → Quick Nav [Payroll Summary, PAYE Report, Payroll Journal].
payroll.settings → Quick Nav [Payroll Periods, Payslip Templates, Numbering].

==================== 17 · ACCESSIBILITY / RESPONSIVE ====================
17.1 aria: stepper aria-current; status boxes aria-pressed; tabs role=tablist; ⋯ menus
aria-haspopup; focus rings #94a3b8; tables th scope; net/pay colors backed by text.
17.2 ≤1100px statgrid 3-col; ≤1000px KPI 2-col + fgrid 2-col + 2-col grids stack;
≤640px fgrid 1-col; ≤768px slim rail hidden, payslip columns stack, tables horizontal-
scroll inside cards; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 18 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/banking/approval handler
changes; no new packages; ONE shared component/CSS per pattern; no hardcoded sample
data (live ledger only); payslips immutable once generated (reversal via run reversal);
onboarding action bar stays TRANSPARENT (no background); PAID watermark stays 40px;
DO NOT SKIP ANY PAGE (§3).

==================== 19 · VERIFY (EVERY PAGE — ALL NINE) ====================
19.1 ROUTE CHECK: all nine routes exist, render, reachable from module menu; dashboard
present with KPIs + last-run card.
19.2 ACTION AUDIT: every button (dashboard five actions + last-run shortcuts, list
view/edit/upload/update-salary/leave/loan/deactivate/terminate/import/export,
onboarding stepper nav/suggestion apply/Back/Save & continue/Finish later, profile
cluster + 12 tabs + Update Salary, runs Calculate/Approve/Lock/Post/Generate Payslips/
Export Bank File, payslips Email/Print/PDF/period switch, statutory add earning/
deduction/tax table/edit bracket/import/assign/scheme/benefit actions, people Issue
Loan/Record Payment/Generate Schedule/Import Attendance/Calculate Pay/Approve OT/
Approve Leave/View Balance/Adjust, reports Opens, settings edits) triggers the SAME
handler/route as pre-implementation where it existed (spot-click each).
19.3 MATH: run table per-employee Gross/PAYE/Pension/Loan/Net per §14; tfoot totals =
sums; payslip = run row; PAYE matches active table; pension %s correct; unpaid leave
proration applied; bank file totals = net pay total; net-pay card figures = earnings −
deductions.
19.4 CONTROLS: approval threshold gates runs; lock blocks edits; period lock blocks
posting; terminations require final settlement; payslip access permission-gated;
onboarding autosave restores drafts.
19.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; pages render §16 registries.
19.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all nine, confirmed built); action-mapping
table (old control → new location → handler confirmed same); status/chip table; rail
registry per page; GL posting mapping; confirmation rails + all existing functionality
unchanged and NO PAGE SKIPPED.