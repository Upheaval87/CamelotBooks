ACCOUNTING METHOD (COMPANY-LEVEL) + COA INHERITANCE + SWITCH-TO-ACCRUAL CONVERSION —
CONSOLIDATED IMPLEMENTATION SPEC. Three surfaces: (A) Company Creation gains the
accounting-method step; (B) Chart-of-Accounts Structure Setup INHERITS the method (no
longer chooses it); (C) a gated "Switch to Accrual" controlled conversion for cash-basis
companies. ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to the pages in §8 per the registry: resting slim icon rail with
teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites "Pin rails
to right side bar" global toggle unchanged; drawer hidden whenever the full rail is not
displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: journal posting handlers, GL mappings,
period locking, approval engine, tax/pension sources, notification/export/print handlers,
search/filter/pagination params, auth/permissions and all routes remain EXACTLY as-is.
The conversion posts its journal ONLY through the existing journal posting handler.
Budgeting / Recurring Journals / other built modules are linked, never rebuilt.

==================== 0 · DISCOVERY ====================
0.1 Locate company model + company-creation wizard, COA structure setup page, settings,
opening-balances page, journal posting handler, period model, module activation flags
(AR/AP/inventory), user roles (admin).
0.2 List CURRENT controls + handlers on those pages (drives §11 audit).
0.3 Locate user-preference storage (rail prefs) + header Favorites menu.

==================== 1 · TOKENS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897;
--ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476; --faint:#8aa5a7;
--red-2:#b91c1c; --green:#15803d; --amber-2:#b45309. html,body{overflow-x:clip}.
Text rem per app rule. prefers-reduced-motion respected.

==================== 2 · DATA MODEL + METHOD SEMANTICS ====================
2.1 companies.accounting_method ENUM('accrual','cash') DEFAULT 'accrual' (set at creation).
2.2 companies.reporting_preference ENUM('accrual_view','cash_view') DEFAULT 'accrual_view'
(report-only; does not change books).
2.3 periods.basis ENUM('cash','accrual') per period, used to label reports/ comparatives.
2.4 method_conversions table: id, company_id, from_method, to_method, cut_off_date,
treatment ENUM('prospective','retrospective') default 'prospective', conversion_journal_id
FK, status ENUM('draft','activated'), created_by, activated_at. One active conversion per
company; after activation company.accounting_method = to_method.
2.5 Default COA template keyed by method: accrual template activates AR (1-02-xxx), AP
(2-01-xxx), accruals, prepayments, inventory/COGS; cash template keeps those inactive and
uses a smaller chart. Method drives which modules/workflows are emphasized (invoices &
bills on accrual; receipts/payments-only on cash) — modules remain present, not removed.

==================== 3 · COMPANY CREATION (companies.create) ====================
3.1 Wizard steps: 1 Company details · 2 Accounting method · 3 Fiscal year & currency ·
4 Finish. Stepper chips.
3.2 STEP 2 = two selectable opt-cards: [Accrual (Recommended)] [Cash], each with a short
description; below, a "What changes in your books" note (accrual adds AR/AP/inventory/
accrual/prepayment accounts + invoice & bill modules; cash omits them, reports cash in/out)
and a reporting-preference chip (Accrual view · cash-basis view available as report toggle).
3.3 Persist accounting_method + reporting_preference on save; COA setup and default COA
template read from the company (never ask again).

==================== 4 · COA STRUCTURE SETUP (coa.setup) — INHERITS ==============
4.1 REMOVE the method choice. Show an "Accounting method" card with chip
"Inherited · {Accrual|Cash} (from company)" + [Change at company level] (links to company
edit, admin only).
4.2 Note line: coding structure (format/levels/segments/generation) is independent of the
method; the method is set once at Company Creation and drives active accounts/modules.
4.3 Keep the existing format/segments/generation/default-COA controls unchanged, but the
default COA preview reflects the inherited method (accrual template shows AR/AP/inventory
active; cash template shows them inactive).

==================== 5 · SWITCH TO ACCRUAL (settings.switch_accrual) — GATED ======
5.0 ENTRY GATE: only reachable/enabled when company.accounting_method = 'cash' AND user is
admin; otherwise hidden/disabled with a note. One-way: after activation the company is
accrual; a reverse switch is NOT offered (note: use reports for cash views).
5.1 STEP 1 — CUT-OFF: date input (accrual applies from) + prior-period treatment
(Prospective recommended / Retrospective). VALIDATION: cut-off must be ≥ the last day of
the last posted period and ≥ company start; if posted transactions exist on/after the
chosen cut-off, block with an error.
5.2 STEP 2 — OPENING BALANCES at cut-off: inputs for Accounts Receivable, Inventory on
hand, Prepayments, Accounts Payable, Accrued expenses, Unearned revenue.
5.3 STEP 3 — CONVERSION JOURNAL (auto-balanced, live): Dr AR / Dr Inventory / Dr
Prepayments; Cr AP / Cr Accrued / Cr Unearned; plug = (AR+Inv+Pre) − (AP+Acc+Unearned)
posted to "Retained Earnings — opening adjustment" (credit if ≥0 else debit). Totals always
equal; show "✓ Balanced · Dr = Cr" chip (recompute on input). Note: non-cash conversion.
5.4 STEP 4 — ACTIVATE: [Save draft][Activate Conversion CTA]. On activation (admin, via
existing journal posting handler): post conversion journal; activate AR/AP/inventory
modules; set periods.basis = 'cash' before cut-off and 'accrual' from cut-off; set
company.accounting_method='accrual'; write method_conversions row (status activated) +
audit-trail entry; cannot be silently undone. Keep cash-basis view available as a report.

==================== 6 · METHOD-DRIVEN BEHAVIOUR (system-wide) ====================
6.1 Accrual: revenue at invoice/delivery, expense at bill/receipt; AR/AP/inventory/COGS,
period-end accruals + prepayment amortisation active; Income Statement + Balance Sheet
primary.
6.2 Cash: revenue/expense at cash movement; AR/AP/inventory accounts present but inactive;
cash-flow view primary; "Simple mode" may auto-post invoices/bills at payment.
6.3 Reports honour period basis labels and the reporting_preference toggle (cash-basis
P&L / cash-flow view computed FROM accrual data when applicable). Comparatives labelled
by basis.

==================== 7 · PERMISSIONS ====================
Method selection at creation: any setup role. Change-at-company-level + Switch-to-Accrual
activation: ADMIN only. Conversion draft editable by admin; activation writes audit.

==================== 8 · RAILS REGISTRY (per page; rails feature unchanged) =======
companies.create → Quick Nav [Companies, Chart of Accounts, Settings].
coa.setup → Quick Nav [Company Setup, Chart of Accounts, Account List].
settings.switch_accrual → Quick Nav [Settings, Chart of Accounts, Opening Balances].

==================== 9 · ACCESSIBILITY / RESPONSIVE ====================
9.1 aria: opt-cards role=radio with aria-checked; stepper aria-current; conversion inputs
labelled; balanced chip aria-live; focus rings #94a3b8.
9.2 ≤900px opt-cards/grid2 1-col; ≤768px slim rail hidden; no horizontal PAGE scrollbar at
1280/1024/768.

==================== 10 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/approval/period-lock handler
changes; no new packages; ONE shared component/CSS per pattern; no hardcoded sample data;
conversion is one-way and journaled; DO NOT re-ask the method in COA setup.

==================== 11 · VERIFY ====================
11.1 Company creation: method step persists; default COA template matches method; COA
setup shows inherited chip and no method selector; "Change at company level" admin-only.
11.2 Switch gate: hidden/disabled unless method=cash + admin; cut-off validation blocks
post-cut-off posted transactions; conversion journal always balanced (plug); activation
posts via existing handler, flips method, sets period basis, activates modules, writes
method_conversions + audit; reverse switch not offered.
11.3 Reports: basis labels + reporting_preference toggle behave per §6.3.
11.4 ACTION AUDIT: every button (company wizard nav/save, COA change-at-company, switch
steps save-draft/activate, inputs recompute) triggers the SAME handler/route as pre-
implementation where it existed (spot-click each).
11.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as rails.html
on these and all other pages; pages render §8 registries.
11.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; schema additions (companies.accounting_method,
reporting_preference, periods.basis, method_conversions); page-route table; action-
mapping table; rail registry per page; confirmation rails + all existing functionality
unchanged and method asked only at Company Creation.