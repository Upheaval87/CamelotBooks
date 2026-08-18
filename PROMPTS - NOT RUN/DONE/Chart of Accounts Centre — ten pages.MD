CHART OF ACCOUNTS (COA) — CONSOLIDATED HIERARCHICAL + STRUCTURE SETUP IMPLEMENTATION SPEC
(10 PAGES: DASHBOARD / STRUCTURE SETUP / ACCOUNT LIST / ADD / EDIT / TREE+TYPES+VALIDATION /
MAPPING / OPENING+REGISTER / BUDGETS+JOURNALS / IMPORT-EXPORT+AUDIT+REPORTS+SETTINGS).
Hierarchical X-XX-XXX codes, method INHERITED from the company (never asked here).
ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every COA page per the registry in §17: resting slim icon rail
with teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites
"Pin rails to right side bar" global toggle unchanged; drawer hidden whenever the full
rail is not displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: journal posting handlers, GL mappings of
built modules, period locking, approval engine, tax/pension sources, notification/
export/print handlers, search/filter/pagination params, auth/permissions and all routes
remain EXACTLY as-is. Posting reaches the ledger ONLY through the existing journal
posting handler. Budgeting / Recurring Journals / Payroll / Banking are LINKED, never
rebuilt. Company-level accounting method (companies.accounting_method) is already set at
Company Creation — this module READS it, never re-asks it.

==================== 0 · DISCOVERY ====================
0.1 Inventory COA routes/pages + handlers: dashboard, structure setup, accounts index/
create/edit, tree, mapping, opening, register, budgets, journals, import/export, audit,
reports, settings.
0.2 List CURRENT controls + handlers per page (drives §20 audit).
0.3 Locate: companies.accounting_method + reporting_preference, accounting_settings
(gl_code_format, separator, segment_*_length, auto_generate_codes), chart_of_accounts
(account_code dash-less, parent_id, level, is_group, allow_posting, is_system_account,
normal_balance, posting_behaviour, allow_adjustments + dimensions), method_conversions,
user roles (admin).
0.4 Locate user-preference storage (rail prefs) + header Favorites menu.

==================== 1 · TOKENS =====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897;
--ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476; --faint:#8aa5a7;
--red-2:#b91c1c; --green:#15803d; --amber-2:#b45309; --steel:#46708C.
html,body{overflow-x:clip}. Text rem per app rule. prefers-reduced-motion respected.

==================== 2 · BADGES / CHIPS ====================
Type chips: Asset teal / Liability steel / Equity gray / Income green / Expense amber.
Level chips L1/L2/L3. Posting chips Group (steel, non-posting) / Posting (green) /
System (red, locked). Behaviour Dr only (amber) / Cr only (steel) / Dr·Cr (teal).
Status Active green / Inactive gray. Inherited-method chip (teal outline).

==================== 3 · PAGE INVENTORY — BUILD ALL TEN (no skips) ==============
coa.dashboard  Dashboard (§4).
coa.setup      COA Structure Setup — method inherited (§5).
coa.index      Account List (§6).
coa.create     Add Account (§7).
coa.edit       Edit Account (§8).
coa.tree       Tree + Types + Validation (§9).
coa.mapping    Mapping + module linkages (§10).
coa.opening    Opening Balances + Register (§11).
coa.budgets    Account Budgets + Journals (§12).
coa.reports    Import/Export + Audit + Reports + Settings (§13–14).

==================== 4 · DASHBOARD (coa.dashboard) ====================
4.1 Head: h1 "Chart of Accounts" + sub; right [📁 Manage Groups][📥 Import][📤 Export
COA][📊 Reports ghost][➕ Add Account CTA].
4.2 KPI ROW 1 (4): hero Total Accounts · Assets (1xxx·debit) · Liabilities (2xxx·credit)
· Equity (3xxx·credit). ROW 2 (4): Income (4xxx) · Expenses (5xxx) · Without Mapping
(amber, "Fix mappings →") · Groups non-posting (L1&2).
4.3 RECENT CHANGES card: When/User/Account(dashed)/Change/Type/Action; "Audit trail →".

==================== 5 · STRUCTURE SETUP (coa.setup) — METHOD INHERITED ==========
5.1 Head: breadcrumb + right [Customize][Activate & Lock Format CTA].
5.2 METHOD card: chip "Inherited · {Accrual|Cash} (from company)" + [Change at company
level] (admin-only, links to company edit). NOTE line: method set once at Company
Creation; coding structure below is independent of it. NO method selector here.
5.3 Stepper chips: 1 Code structure · 2 Levels & segments · 3 Generation · 4 Generate &
activate.
5.4 FORMAT opt-cards (3): Simple Numeric (4-digit) / Hierarchical Numeric / Hierarchical
With Separators, each with example codes; selectable.
5.5 LEVELS + SEGMENTS: levels select (2/3/4); segment-length inputs (Class/Group/Detail);
separator input; live "Result" code; level examples (2-level 10-01, 3-level 1-01-001,
4-level 1-01-001-001).
5.6 GENERATION opt-cards: Automatic (recommended) / Manual (admin only) / Hybrid.
5.7 DEFAULT COA preview table (Code dashed / Account / Type / Level) that RE-FORMATS live
on format/segment change; accounting_settings chips (gl_code_format, separator,
segment_*_length, auto_generate_codes).
5.8 FORMAT LOCK: warn "once transactions exist the format can no longer be changed
freely; controlled migration required" + locked-state card with [Start Controlled
Migration] when transactions exist.

==================== 6 · ACCOUNT LIST (coa.index) ====================
6.1 Head: h1 "Accounts" + sub; right [⇩ Export][ New Account CTA].
6.2 TYPE BOXES (6): All / 1 Assets / 2 Liabilities / 3 Equity / 4 Income / 5 Expenses.
6.3 CONTROLS: search (name or dashed code) + Type + Level + Active/Inactive.
6.4 TABLE: Code (dashed mono) / Name (700) / Type / Level / Posting / Behaviour /
Balance (bold) / Status / actions (👁 · ✎ · ⋯). Group rows Posting=Group, Behaviour "—".
System accounts hide ✎/delete. Pagination.

==================== 7 · ADD ACCOUNT (coa.create) ====================
7.1 Head: breadcrumb + [Cancel][Save & New][Save Account CTA].
7.2 MODE segc: [✅ Auto Generate (Recommended)] [🔒 Manual Entry (Admin only)] (manual
disabled unless admin). Helper: X-XX-XXX · stored dash-less · L1/L2 non-posting · L3 posting.
7.3 FIELDS (g4): Account Number (dashed, disabled in Auto, live msg stored+display+level) ·
Type (Class)* · Parent (Category, filtered)* · Name* · Level (disabled "Level 3 ·
Posting") · Normal Balance · Posting Behaviour · Currency · Branch · Department ·
Cost Centre · Project · Tax Category.
7.4 TOGGLES (preserve exactly): Tax applicable · Reconciliation required ·
Allow journal adjustments (hint "Off = manual adjustment journals blocked. Bank
reconciliation postings always allowed.") · Cost centre required.
7.5 AUTO-GEN on class/parent change → next detail under prefix; msg "✓ Auto-generated ·
stored {6} · displays {dashed} · Level 3 posting."

==================== 8 · EDIT ACCOUNT (coa.edit) ====================
8.1 Head: breadcrumb Accounts › {dashed} › Edit + [Cancel][Save Changes CTA].
8.2 Account Number (display) EDITABLE with LIVE validation: 6-digit/dashed format,
uniqueness, class+parent prefix; current code accepted as "unchanged"; ok/err/neutral msg.
8.3 Remaining fields + the four toggles preserved with bank-reconciliation exemption hint.
8.4 Helper strip with example tries (exists→blocked / parent mismatch→blocked / available).

==================== 9 · TREE + TYPES + VALIDATION (coa.tree) ====================
9.1 TREE card: collapsible nested list; group rows (L1/L2) "Group" chip; posting rows
"Posting·Dr/Cr"; system rows "System·locked"; dashed codes; [Expand][＋ Add].
9.2 VALIDATION cards (3 ❌): post to L1/L2 blocked; delete system blocked; class≠parent
blocked.
9.3 ACCOUNT TYPES card: Type/Class/Statement/Normal for the five types; [＋ Add Type].

==================== 10 · MAPPING + LINKAGES (coa.mapping) ====================
10.1 Head: [✓ Validate Mapping][🔗 Map Account CTA].
10.2 MAPPING cards (dashed): Sales / Purchasing / Inventory / Payroll[built] /
Banking[built].
10.3 LINKAGE cards (teal): Budgeting (expense↔budget lines, variance → Open Budgeting) ·
Recurring Journals (rent/depreciation auto-post → Open Recurring). NO rebuild.

==================== 11 · OPENING + REGISTER (coa.opening) ====================
11.1 OPENING card: [✓ Balanced chip][📥 Import][✓ Verify][Post Opening secondary]; table
Code/Account/Class/Debit/Credit; tfoot must balance.
11.2 REGISTER card: account selector + [🖨 Print][⇩ Export]; Date/Reference/Description/
Debit/Credit/Balance (running bold); includes cross-module postings.

==================== 12 · BUDGETS + JOURNALS (coa.budgets) ====================
12.1 BUDGETS card: chip "links to built Budgeting"; [Set Budget][Open Budgeting →]; table
Code/Account/Budget/Actual YTD/Variance/Util.
12.2 JOURNALS card: [↩ Reverse][＋ New Journal secondary]; table Journal/Date/Description
(dashed codes in DR/CR)/Debit/Credit/Status; recurring + payroll journals included.

==================== 13 · IMPORT/EXPORT + AUDIT (coa.reports top) ================
13.1 IMPORT/EXPORT: [📥 Import COA][📥 Opening Balances][📥 Mappings][ Export COA]
[ Balances].
13.2 AUDIT card: rows date·user—action incl. create/rename/auto-link; Export →.

==================== 14 · REPORTS + SETTINGS (coa.reports) ====================
14.1 REPORT cards: Chart of Accounts · Trial Balance · General Ledger · Account Balance
(+ Account Activity). PDF+Excel, Open →. Existing pages if present else MINIMAL.
14.2 SETTINGS card (g3): Numbering "X-XX-XXX · stored dash-less" · Method "Inherited from
company" · Depth "3 default · unlimited via parent_id" · Manual codes "Admin only" ·
Period locking · Approvals "Journals > 1M → FinMgr".

==================== 15 · CODE MODEL + GENERATION + VALIDATION (ENGINE) ==========
15.1 STORAGE: account_code 6 digits NO dashes; display_code computed dashed X-XX-XXX.
digit1=class; 2-3=category(parent); 4-6=detail.
15.2 LEVELS: L1/2 is_group=true allow_posting=false; L3 allow_posting=true;
is_system_account protects critical accounts.
15.3 AUTO-GEN: next detail = max(under class+category)+1 pad3; regenerate on class/parent
change in Auto mode.
15.4 MANUAL: admin only; HYBRID suggests + editable.
15.5 LIVE VALIDATION: format/uniqueness/class-parent; block save on error; edit accepts own
code.
15.6 POSTING RULES: block post to groups; enforce posting_behaviour side; if
allow_adjustments=false block MANUAL adjustments but ALWAYS permit Bank-Reconciliation
postings.
15.7 REFERENCES by account_id (NOT code); keep legacy_code for migration.
15.8 FORMAT LOCK: after transactions exist, format change only via controlled migration
(map old→new, re-post references, preserve audit); [Start Controlled Migration].

==================== 16 · METHOD INHERITANCE ====================
16.1 coa.setup reads companies.accounting_method; shows inherited chip; default COA
template + active accounts/modules reflect the method (accrual: AR/AP/inventory active;
cash: inactive). "Change at company level" admin-only.
16.2 Reporting honours companies.reporting_preference + periods.basis labels.

==================== 17 · RAILS REGISTRY (per page; rails unchanged) ============
coa.dashboard → Quick Nav [Account List, Add Account, Structure Setup, Reports].
coa.setup → Quick Nav [Company Setup, Chart of Accounts, Account List].
coa.index → Views [All(active), Assets, Liabilities, Equity, Income, Expenses] +
Reports [Trial Balance, General Ledger].
coa.create → Quick Nav [Account List, Account Tree, Structure Setup].
coa.edit → Quick Nav [Account List, Register, Audit].
coa.tree → Quick Nav [Account Tree, Account Types, Add Account].
coa.mapping → Quick Nav [Validate Mapping, Account List, Settings].
coa.opening → Quick Nav [Verify Balance, Post Opening, Account List].
coa.budgets → Quick Nav [New Journal, Set Budget, Open Budgeting].
coa.reports → Quick Nav [Trial Balance, General Ledger, Chart of Accounts].

==================== 18 · ACCESSIBILITY / RESPONSIVE ====================
18.1 aria: opt-cards role=radio aria-checked; type boxes aria-pressed; tree role=tree
aria-expanded; ⋯ menus aria-haspopup; balanced/live msgs aria-live; focus rings #94a3b8;
tables th scope.
18.2 ≤1100px statgrid 3-col; ≤1000px kpis/g4/grid2/optcards collapse; ≤768px slim rail
hidden; tables horizontal-scroll; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 19 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/approval/period-lock handler
changes; no new packages; ONE shared component/CSS per pattern; no hardcoded sample data;
references by account_id; DO NOT re-ask method in COA; DO NOT SKIP ANY PAGE (§3).

==================== 20 · VERIFY (EVERY PAGE — ALL TEN) ====================
20.1 ROUTE CHECK: all ten routes exist, render, reachable; dashboard + structure setup
present; structure setup shows inherited chip and NO method selector.
20.2 ACTION AUDIT: every button (dashboard actions, setup customize/activate/migration/
change-at-company, list view/edit/export, add save/save&new/mode, edit save + live check,
tree expand/add, mapping validate/map + open budgeting/recurring, opening import/verify/
post, register print/export, budgets set/open, journals new/reverse, import/export,
audit export, reports Open, settings) triggers the SAME handler/route as pre-
implementation where it existed (spot-click each).
20.3 CODE MATH: auto-gen next detail per prefix; dashed display = stored dash-less; live
validation blocks dup/out-of-prefix/bad-format; edit accepts own code; structure preview
re-formats on format/segment change.
20.4 POSTING RULES: post-to-group blocked; delete-system blocked; class≠parent blocked;
posting_behaviour enforced; allow_adjustments=false blocks manual adjustments but bank-
reconciliation postings still post.
20.5 METHOD: inherited chip correct; default COA template matches company method; format
lock + controlled migration enforced after transactions.
20.6 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as rails.html
on these and all other pages; pages render §17 registries.
20.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all ten, confirmed built); action-mapping
table; rail registry per page; confirmation rails + all existing functionality unchanged,
method asked only at Company Creation, and NO PAGE SKIPPED.