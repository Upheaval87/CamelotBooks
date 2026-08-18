FINANCIAL REPORTS CENTRE + PDF ENGINE — FULL IMPLEMENTATION SPEC
(FIVE WEB REPORTS: INCOME STATEMENT / STATEMENT OF FINANCIAL POSITION / CASH FLOW /
A/R AGING / A/P AGING  +  A SHARED CLEAN PDF EXPORT ENGINE FOR ALL FIVE).
Build exactly as designed in the consolidated mockup. ALL VALUES INLINE.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every report page per the registry in §13: resting slim icon
rail with teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites
"Pin rails to right side bar" global toggle unchanged; drawer hidden whenever the full
rail is not displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: GL posting handler, AR/AP subledgers,
period locking, COA mappings, banking/payroll/inventory sources, notification/export
handlers, search/filter/pagination params, auth/permissions and all routes remain
EXACTLY as-is. Reports are READ-ONLY consumers of GL/subledger data; drill-down links to
existing ledger/transaction pages; receiving/payment/scheduling actions call EXISTING
handlers.

==================== 0 · DISCOVERY ====================
0.1 Locate GL balance aggregation by account class, AR/AP aging sources, cash-book
(receipts/payments) for cash flow, period/fiscal-year model, currency rates,
branch/department/cost-centre dimensions.
0.2 Locate existing PDF generator (dompdf/snappy/wkhtmltopdf) + invoice/receipt PDF
template (brand reference) + email/scheduled-report infrastructure.
0.3 List CURRENT controls + handlers per page (drives §16 audit).
0.4 Locate user-preference storage (rail prefs) + header Favorites menu.

==================== 1 · TOKENS ====================
WEB tokens: --deep-1:#17565d; --deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897;
--ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476; --faint:#8aa5a7;
--red-2:#b91c1c; --green:#15803d.
PDF tokens: deep #0c3539; teal #128F8E; ink #111827; body #374151; mut #6b7280;
faint #9ca3af; hair #e5e7eb; hair2 #f3f4f6; red #b91c1c. Font: Inter/system sans
(same family as the Invoice PDF). html,body{overflow-x:clip}. prefers-reduced-motion.

==================== 2 · WEB BADGES / CHIPS ====================
Status: Current/Posted green-tint; Due-Soon amber; Overdue/90+ red; aging bucket chips.
Variance: positive green, negative red (web); PDF shows negatives as grey parentheses.

==================== 3 · PAGE INVENTORY — BUILD ALL (no skips) ====================
fin.income     Income Statement (web).
fin.position   Statement of Financial Position (web).
fin.cashflow   Cash Flow Statement (web).
fin.ar-aging   Accounts Receivable Aging (web).
fin.ap-aging   Accounts Payable Aging (web).
fin.pdf        PDF Engine (server-side; renders all five reports; also Print/Excel/Email).

==================== 4 · INCOME STATEMENT (fin.income) ====================
4.1 Head: h1 + sub "Current Period vs Previous Period · MWK"; actions [Refresh][Compare
sec][Drill Down][Print][PDF][Excel].
4.2 FILTERS (6): Current Period / Previous Period / Branch / Department / Cost Centre /
Currency.
4.3 TABLE (flat, expand/collapse): columns Description | Current Period | Previous
Period | Variance | %. Sections: REVENUE (Sales/Service/Other → TOTAL REVENUE) ·
COST OF SALES (COGS → TOTAL) · GROSS PROFIT + "Gross Profit Margin" italic row ·
OPERATING EXPENSES (Salaries/Rent&Utilities/Transport/Marketing/Depreciation/Other →
TOTAL) · OPERATING PROFIT · Finance Income / Finance Costs · PROFIT BEFORE TAX ·
Income Tax · NET PROFIT (double-rule). Selective bolding: line items regular; grp 700;
sub 700; net 800.
4.4 Monthly net-profit trend bar chart; click bar → drill into period ledger.

==================== 5 · STATEMENT OF FINANCIAL POSITION (fin.position) =========
5.1 Head: h1 + sub "As at 31 August 2026 · MWK · comparative"; actions [Expand All]
[Collapse All][Print][PDF sec][Excel].
5.2 KPI DASHBOARD row1: Total Assets (hero) / Total Liabilities / Total Equity /
Working Capital. row2: Current Ratio / Debt-to-Equity / Equity Ratio / Net Assets.
5.3 FILTERS (5): Period / Branch / Department / Currency + "✓ Balanced" chip.
5.4 STATEMENT (flat, expand/collapse) columns Description | Current | Previous |
Variance | %: ASSETS (NON-CURRENT: Land&Buildings/Motor Vehicles/Office Equipment/
Accumulated Depreciation → Total PPE; Intangibles → TOTAL NCA; CURRENT: Inventory/Trade
Receivables/Other Receivables/Cash&Bank → TOTAL CA; TOTAL ASSETS) · LIABILITIES
(NON-CURRENT: Long-Term Loans/Lease → TOTAL; CURRENT: Trade Payables/Short-Term Loans/
Accrued/Tax Payable → TOTAL; TOTAL LIABILITIES) · EQUITY (Share Capital/Retained
Earnings/Current Year Profit → TOTAL EQUITY) · TOTAL LIABILITIES & EQUITY (double-rule).
5.5 BALANCE CHECK banner: "✓ BALANCED — Total Assets = Total Liabilities + Total Equity".
NEVER silently render an imbalance: if Assets ≠ Liab+Equity, show a red warning and block
PDF/Excel export.
5.6 DRILL-DOWN panel: click any line (e.g. Trade Receivables) → customer breakdown →
invoices → payments → transactions (audit trail to source).

==================== 6 · CASH FLOW (fin.cashflow) ====================
6.1 Head: h1 + sub; actions [Forecast sec][Drill Down][Print][PDF][Excel].
6.2 TABLE: Operating (receipts/supplier/payroll/operating) · Investing · Financing with
In/Out/Net columns; Net Cash Movement; Opening Cash; Closing Cash (double-rule).
6.3 CHECK: Closing = Opening + Net Movement; bank breakdown note; Forecast opens
cash-forecast view (daily/weekly/monthly).

==================== 7 · A/R AGING (fin.ar-aging) ====================
7.1 Head: h1 + sub (K'000); actions [Send Statements sec][Drill Down][Print][PDF][Excel].
7.2 SUMMARY by customer: buckets Current/1–30/31–60/61–90/90+ + Total; 90+ red badge.
7.3 OUTSTANDING INVOICES: Customer/Invoice №/Due Date/Balance/Aging + row actions
(👁 View ·  Receive Payment · ✉ Send Reminder) → EXISTING handlers.
7.4 Advanced: credit-limit warnings, collection notes, expected payment dates, bad-debt
analysis on drill-down; automatic payment reminders.

==================== 8 · A/P AGING (fin.ap-aging) ====================
8.1 Head: h1 + sub (K'000); actions [Schedule Payments sec][Drill Down][Print][PDF][Excel].
8.2 SUMMARY by vendor: buckets + Total; due-soon amber badge.
8.3 OUTSTANDING BILLS: Vendor/Bill №/Due Date/Outstanding/Aging + row actions
(👁 View · 💳 Make Payment · 📅 Schedule Payment) → EXISTING handlers.
8.4 Advanced: payment priority ranking, cash-requirement forecast, vendor terms
analysis, early-payment discount tracking, duplicate-invoice detection.

==================== 9 · PDF ENGINE (fin.pdf) — CLEAN EDITORIAL TEMPLATE ==========
Applies to ALL FIVE reports; A4 portrait; white; border-top 3px #0c3539; padding ~30px.
9.1 HEADER: left = 30px deep-teal "C" tile + "CamelotBooks" (700) + tagline
"Enterprise Accounting & Advisory Services" (7.5px grey); right = report title
UPPERCASE letter-spaced .22em (11px ink) + period line (8.5px grey) + 26×3px teal accent
bar. Header separated by 1px #e5e7eb. NO META BLOCK (no Prepared By/Basis/Current/
Previous boxes).
9.2 COMPARATIVE HEADERS = ACTUAL YEARS: compute current & previous fiscal years from the
selected period and print them as column headers (e.g. "2026" / "2025") — NEVER the words
"Current"/"Previous". Aging reports keep "Current" only as the 0–30 bucket label.
9.3 TABLES: header 7.5px uppercase #6b7280, 1px #e5e7eb underline; rows 9.5px, 1px
#f3f4f6 rules; section headings 9px uppercase ink with 2px teal left tick; subtotals
600 with 1px #e5e7eb top rule; grand totals 700 with 2px #111827 top rule and #0c3539
figures; right-aligned tabular figures; negatives as grey parentheses (red ONLY for the
90+ overdue bucket).
9.4 SFP includes balance-check line: teal "✓" + "Balances — Total Assets equal Total
Liabilities plus Equity (K…)".
9.5 SIGN-OFF: two thin signature lines "Prepared By" / "Authorised By — Signature & Date"
(7.5px uppercase #9ca3af).
9.6 FOOTER (identical to Invoice PDF): "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567" left · "Page 1 of 1" right; 1px #e5e7eb top rule; 8px #9ca3af.
9.7 COLOUR BALANCE: ink/grey dominant; teal only for top rule, logo tile, accent bar,
section ticks, balance check. No heavy bands, no boxed meta, no dotted clutter.
9.8 OUTPUTS: PDF download, Print view, Excel export, Email attachment, scheduled
reports; filenames {report}-{period}.pdf; audit-log every generation/export.

==================== 10 · FORMULAS / DATA SOURCES ====================
10.1 IS: Gross Profit = Revenue − COGS; margin = GP/Revenue; Operating Profit = GP −
OpEx; PBT = OP ± finance; Net = PBT − tax; Variance = Cur − Prev; % = Variance/Prev.
10.2 SFP: Working Capital = CA − CL; Current Ratio = CA/CL; Debt-to-Equity = TL/Equity;
Equity Ratio = Equity/Assets; Net Assets = Equity. Balance check Assets = Liab + Equity.
10.3 CF: Net = Operating + Investing + Financing; Closing = Opening + Net.
10.4 Aging: bucket by days-overdue from due date (0–30/31–60/61–90/90+).
10.5 Sources: GL balances by class; AR/AP subledgers; cash book; FX rates for
multi-currency; branch/department/cost-centre filters applied throughout.

==================== 11 · PERMISSIONS / SECURITY ====================
Role-based report access; report approval where configured; data-visibility restrictions
by branch/company; audit logging of view/generate/export/email.

==================== 12 · COMMON REPORT CONTROLS ====================
Date/period filtering; multi-company; multi-currency; branch/department/cost-centre
filters; saved report templates; drill-down summary→transaction→source document.

==================== 13 · RAILS REGISTRY (per page; rails unchanged) ============
fin.income → Quick Nav [Generate, Compare, Export] + Reports [Trial Balance, GL].
fin.position → Quick Nav [Generate, PDF, Drill Down] + Reports [Trial Balance].
fin.cashflow → Quick Nav [Generate, Forecast] + Reports [Bank Transactions].
fin.ar-aging → Quick Nav [Generate, Send Statements] + Reports [Customer Statements].
fin.ap-aging → Quick Nav [Generate, Schedule Payments] + Reports [Vendor Statements].

==================== 14 · ACCESSIBILITY / RESPONSIVE ====================
14.1 aria: expand/collapse groups aria-expanded; balanced chip aria-live; tables th
scope; focus rings #94a3b8.
14.2 ≤1000px filters 3-col + kpis 2-col; ≤768px slim rail hidden, tables horizontal-
scroll, pdfgrid 1-col; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 15 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/period/lock handler changes;
reports read-only; ONE shared PDF template component for all five reports; no hardcoded
sample data in production (live GL/subledger only); DO NOT SKIP ANY PAGE (§3).

==================== 16 · VERIFY (EVERY PAGE + PDF) ====================
16.1 ROUTE CHECK: all five web routes + PDF engine exist, render, reachable.
16.2 ACTION AUDIT: every button (refresh/compare/drill/print/pdf/excel, expand/collapse,
send statements, schedule payments, receive/make payment, view) triggers the SAME
handler/route as pre-implementation where it existed (spot-click each).
16.3 MATH: IS/SFP/CF formulas (§10) correct; variance/% correct; balance check passes;
imbalance blocks export with red warning; aging buckets sum to totals; closing = opening
+ net.
16.4 PDF: renders all five reports with §9 template; NO meta block; year headers show
actual years (2026/2025); footer identical to Invoice PDF; negatives grey parentheses;
red only for 90+; selective bolding; signatures + balance check present; page 1 of 1.
16.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as rails.html
on these and all other pages; pages render §13 registries.
16.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all, confirmed built); action-mapping table;
PDF template spec confirmation (no meta, real years, invoice-matched footer); rail
registry per page; confirmation rails + all existing functionality unchanged and
NO PAGE SKIPPED.