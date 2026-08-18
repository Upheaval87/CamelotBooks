ACCOUNTING CONTROL CENTRE — FULL CRUD IMPLEMENTATION SPEC
(JOURNAL ENTRIES list/create/view/edit · GENERAL LEDGER view · TRIAL BALANCE view ·
FISCAL YEARS list/create/view-edit · ACCOUNTING PERIODS list · COST CENTRES list/create/
view-edit · EXCHANGE RATES list/create-edit · ACCOUNT CLASSIFICATION list/create-edit).
Build every page exactly as designed in the consolidated CRUD mockup. ALL VALUES INLINE.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every page per the registry in §16: resting slim icon rail with
teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites "Pin rails
to right side bar" global toggle unchanged; drawer hidden whenever the full rail is not
displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: existing journal posting handler, GL posting,
period locking, fiscal-year close/carry-forward, COA mappings, tax/pension sources,
notification/export/print handlers, search/filter/pagination params, auth/permissions and
all routes remain EXACTLY as-is. Posting reaches the ledger ONLY through the existing
journal posting handler. Budgeting / Recurring / Payroll / Banking / COA modules are
LINKED, never rebuilt.

==================== 0 · DISCOVERY ====================
0.1 Inventory accounting routes/pages + handlers: journals index/create/show/edit, ledger,
trial balance, fiscal years, periods, cost centres, exchange rates, classification.
0.2 List CURRENT controls + handlers per page (drives §19 audit).
0.3 Locate: chart_of_accounts (dashed display, dash-less stored), periods.basis,
fiscal_years, cost_centres, currencies/exchange_rates, classifications, user roles.
0.4 Locate user-preference storage (rail prefs) + header Favorites menu.

==================== 1 · TOKENS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897;
--ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476; --faint:#8aa5a7;
--red-2:#b91c1c; --green:#15803d; --amber-2:#b45309; --steel:#46708C.
html,body{overflow-x:clip}. Text rem per app rule. prefers-reduced-motion respected.

==================== 2 · BADGES / CHIPS ====================
Status: Open/Posted/Active/Balanced green; Closed/Inactive gray; Locked/Out red;
Pending/Adjustment-due amber; Draft ink. Journal-type chips (General/Adjustment/Closing/
Opening/Reversal). "✓ Balanced · Dr = Cr" okchip (red variant when out).

==================== 3 · PAGE INVENTORY — BUILD ALL (no skips) ====================
acc.journals          Journal Entries · List.
acc.journals.create   Journal · Create.
acc.journals.show     Journal · View.
acc.journals.edit     Journal · Edit (draft-only).
acc.ledger            General Ledger · View.
acc.trialbalance      Trial Balance · View.
acc.fiscalyears       Fiscal Years · List.
acc.fiscalyears.create Fiscal Year · Create.
acc.fiscalyears.show  Fiscal Year · View/Edit.
acc.periods           Accounting Periods · List.
acc.costcentres       Cost Centres · List.
acc.costcentres.create Cost Centre · Create.
acc.costcentres.show  Cost Centre · View/Edit.
acc.rates             Exchange Rates · List.
acc.rates.create      Exchange Rate · Create/Edit.
acc.classification    Account Classification · List.
acc.classification.create Classification · Create/Edit.

==================== 4 · JOURNAL ENTRIES · LIST (acc.journals) ====================
4.1 Head: h1 + [⋯ Import/Export][➕ New Journal Entry CTA].
4.2 TABLE: Journal № (mono) / Date / Type chip / Description / Amount (bold) / Status
badge / actions (👁 view · ✎ edit (draft only) · ⋯: Post/Reverse/Duplicate/Delete/Print).
Pagination. Row ⋯ varies by status (draft: edit/delete/submit; pending: approve/reject;
posted: reverse/print/duplicate).

==================== 5 · JOURNAL · CREATE (acc.journals.create) ====================
5.1 Head: breadcrumb + [Cancel][Save Draft][Submit Approval CTA].
5.2 HEADER (g4): Journal № (auto, disabled) / Transaction Date / Journal Type
(General/Adjustment/Closing/Opening/Reversal) / Reference / Description (full) /
Currency / Exchange Rate (disabled unless foreign) / Attachment.
5.3 LINES editor (.jtable): Account (search) / Description / Debit / Credit / Cost
Centre / delete; [＋ Add Line][Validate Balance]; live totals + balanced chip
(recompute on input; red "✗ Out" when ≠). Block Submit unless balanced.
5.4 Dimensions per line (Department/Cost Centre/Project). Tax calculation hook.

==================== 6 · JOURNAL · VIEW (acc.journals.show) ====================
6.1 Head: breadcrumb + [✎ Edit (draft only)][🖨 Print Voucher][↩ Reverse][⧉ Duplicate].
6.2 Header chips (type/date/currency) + status badge; lines table with totals +
balanced chip; footer: posted-by / source module / attachments.

==================== 7 · JOURNAL · EDIT (acc.journals.edit) — DRAFT-ONLY ==========
7.1 Gate: only status=Draft editable; posted journals show notice "must be reversed" and
disable editing (offer Reverse).
7.2 Same editor as create, pre-filled and enabled; [Cancel][Save Changes CTA]; live
balance; helper note "Only Draft journals are editable."

==================== 8 · GENERAL LEDGER · VIEW (acc.ledger) ====================
8.1 Head: h1 "General Ledger — {account}" + [🖨 Print][⇩ Export].
8.2 Filters: Account / From / To / Group / Cost Centre / User.
8.3 TABLE: Date/Reference/Description/Debit/Credit/Balance(running bold) + opening row +
closing tfoot; 👁 drill-down per row (Account → Transaction → Source Document breadcrumb).

==================== 9 · TRIAL BALANCE · VIEW (acc.trialbalance) ====================
9.1 Head: h1 "Trial Balance — as at {date}" + [Comparative][Generate CTA].
9.2 Options chips (include inactive / zero balance / monthly comparison) + balanced
okchip (Dr=Cr total).
9.3 TABLE: Code/Account/Debit/Credit + totals tfoot. Drill-down to ledger.

==================== 10 · FISCAL YEARS ====================
10.1 LIST: table Fiscal Year/Start/End/Status/actions (👁/✎/Close/Lock/Carry Fwd/Reopen).
[➕ New Fiscal Year CTA].
10.2 CREATE: Name/Start/End/Status + options (☑ generate 12 monthly periods, ☐ add
adjustment period); [Create & Generate Periods CTA].
10.3 VIEW/EDIT: fields (disabled view; ✎ Edit enables); actions Close Year / Lock /
Reopen / Carry Forward Balances. Carry-forward posts opening balances to next year and
computes retained earnings automatically (existing handler). Note periods count/status.

==================== 11 · ACCOUNTING PERIODS · LIST (acc.periods) ====================
11.1 Head: h1 "Accounting Periods — {year}" + chips (closing checklist / user override)
+ [Generate Year CTA].
11.2 Month grid (.pgrid): each cell month + status badge (Open/Closed/Locked) + actions
(Close/Lock/Reopen/View). Prevent posting into Closed/Locked (enforced at posting).

==================== 12 · COST CENTRES ====================
12.1 LIST: table Code/Name/Manager/Department/Status/actions (👁/✎/📊). [＋ Add CTA].
12.2 CREATE: Code/Name/Manager/Department/Parent/Status; [Save CTA].
12.3 VIEW/EDIT: fields (disabled view; ✎ enables) + Budget YTD / Actual YTD / Variance;
actions View Transactions / Budget vs Actual.

==================== 13 · EXCHANGE RATES ====================
13.1 LIST: table Currency/Rate Date/Buying/Selling/Base/actions (✎/ history/Revalue).
[📥 Import][＋ Add Currency][＋ Add Exchange Rate CTA]. Base = MWK.
13.2 CREATE/EDIT: Currency/Rate Date/Buying/Selling/Base(disabled); [Save Rate CTA].
Revalue Accounts posts FX gain/loss (existing handler); multi-currency reporting.

==================== 14 · ACCOUNT CLASSIFICATION ====================
14.1 LIST: two trees (Balance Sheet: Assets/Liabilities/Equity; Income Statement:
Income/Expenses) with section chips; [Preview Statement][＋ Add Classification CTA].
14.2 CREATE/EDIT: Name / Financial Statement / Section / Display Order / Assign Accounts
(search multi); [Save CTA]. Drives financial-statement presentation + reporting hierarchy.

==================== 15 · ENGINE / BUSINESS RULES ====================
15.1 JOURNAL WORKFLOW: Draft → Review → Approve → Post to Ledger → Reports updated.
Balancing enforced before submit/post. Approval per workflow (amount thresholds).
15.2 EDIT RULE: only Draft editable; posted must be Reversed (never edit posted).
15.3 PERIOD LOCK: posting into Closed/Locked period blocked (override permission only).
15.4 FISCAL CLOSE: Close Year locks all its periods; Carry Forward posts next-year
openings + auto retained earnings; Reopen restricted (admin + audit).
15.5 REVALUATION: FX gain/loss computed on revalue; multi-currency via rates table.
15.6 CLASSIFICATION: accounts map to statement sections; Preview renders BS/IS from
mapping; custom classifications + reorder supported.
15.7 REFERENCES by account_id; dashed display / dash-less storage preserved.

==================== 16 · RAILS REGISTRY (per page; rails unchanged) ============
acc.journals → Views [All(active), Draft, Pending, Posted] + Quick Nav [New Journal,
Recurring Journals, Trial Balance].
acc.journals.create → Quick Nav [Journals List, Trial Balance, Chart of Accounts].
acc.journals.show → Quick Nav [Edit, Reverse, Print Voucher, Journals List].
acc.journals.edit → Quick Nav [Journals List, View, Chart of Accounts].
acc.ledger → Quick Nav [Trial Balance, Journal Entries, Chart of Accounts].
acc.trialbalance → Quick Nav [Generate, General Ledger, Financial Statements].
acc.fiscalyears → Quick Nav [New Fiscal Year, Accounting Periods, Carry Forward].
acc.fiscalyears.create → Quick Nav [Fiscal Years, Accounting Periods].
acc.fiscalyears.show → Quick Nav [Close Year, Carry Forward, Accounting Periods].
acc.periods → Quick Nav [Generate Year, Fiscal Years, Journal Entries].
acc.costcentres → Views [All(active), Active, Inactive] + Quick Nav [Add Cost Centre,
Budget vs Actual].
acc.costcentres.create → Quick Nav [Cost Centres, Account Budgets].
acc.costcentres.show → Quick Nav [Edit, View Transactions, Budget vs Actual].
acc.rates → Quick Nav [Add Rate, Import Rates, Revalue Accounts].
acc.rates.create → Quick Nav [Rates List, Currencies].
acc.classification → Quick Nav [Add Classification, Preview Statement, Chart of Accounts].
acc.classification.create → Quick Nav [Classification List, Chart of Accounts].

==================== 17 · ACCESSIBILITY / RESPONSIVE ====================
17.1 aria: status boxes/trees aria; journal balanced chip aria-live; ⋯ menus aria-
haspopup; focus rings #94a3b8; tables th scope.
17.2 ≤1000px g4/g2 collapse; ≤700px pgrid 2-col; ≤768px slim rail hidden; tables
horizontal-scroll; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 18 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/period/close handler changes;
no new packages; ONE shared component/CSS per pattern; no hardcoded sample data;
references by account_id; posted journals never editable; DO NOT SKIP ANY PAGE (§3).

==================== 19 · VERIFY (EVERY PAGE) ====================
19.1 ROUTE CHECK: all routes in §3 exist, render, reachable; GL/TB view-only (no create/
edit); JE edit gated to Draft.
19.2 ACTION AUDIT: every button (journals list/view/create/edit actions, ledger print/
export/drill, TB generate/comparative, FY create/close/lock/reopen/carry, periods close/
lock/reopen/view/generate, CC add/edit/view/reports, rates add/import/revalue/edit,
classification add/preview/assign) triggers the SAME handler/route as pre-implementation
where it existed (spot-click each).
19.3 MATH: journal totals balance live; TB Dr=Cr; ledger running balance correct;
carry-forward openings = prior closing; revalue FX gain/loss computed.
19.4 RULES: submit blocked when unbalanced; edit blocked when posted; posting blocked in
closed/locked periods; close year locks periods; classification preview renders from
mapping.
19.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as rails.html
on these and all other pages; pages render §16 registries.
19.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all, confirmed built); action-mapping table;
rail registry per page; confirmation rails + all existing functionality unchanged and
NO PAGE SKIPPED.