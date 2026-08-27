FINANCIAL REPORTS CENTRE + PDF ENGINE — FULL IMPLEMENTATION SPEC (SELF-CONTAINED)
(FIVE WEB REPORTS: INCOME STATEMENT / STATEMENT OF FINANCIAL POSITION / CASH FLOW /
A/R AGING / A/P AGING  +  A SHARED CLEAN PDF EXPORT ENGINE FOR ALL FIVE).
Build exactly as designed. ALL VALUES INLINE; the complete reference mockup is embedded in
APPENDIX A — no external file required.

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
UI/UX REFERENCE: APPENDIX A (embedded mockup) — replicate layout, tables, KPIs, badges,
drill-downs and PDF samples exactly.

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
(👁 View · Receive Payment · ✉ Send Reminder) → EXISTING handlers.
7.4 Advanced: credit-limit warnings, collection notes, expected payment dates, bad-debt
analysis on drill-down; automatic payment reminders.

==================== 8 · A/P AGING (fin.ap-aging) ====================
8.1 Head: h1 + sub (K'000); actions [Schedule Payments sec][Drill Down][Print][PDF][Excel].
8.2 SUMMARY by vendor: buckets + Total; due-soon amber badge.
8.3 OUTSTANDING BILLS: Vendor/Bill №/Due Date/Outstanding/Aging + row actions
(👁 View ·  Make Payment ·  Schedule Payment) → EXISTING handlers.
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
"Current" / "Previous". Aging reports keep "Current" only as the 0–30 bucket label.
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
sample data in production (live GL/subledger only); DO NOT SKIP ANY PAGE (§3);
pixel/UX parity with APPENDIX A.

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

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (verbatim) ====================
Replicate exactly. Shell: topbar "CBCamelotBooks · ES · Elvis Seyama" + nav
[Sales][Purchasing][Banking][Reports]; rails per §13.

--- 1 · Income Statement (fin.income) ---
H1 "Income Statement" · sub "Current Period vs Previous Period · MWK".
Actions: [Refresh][Compare sec][Drill Down][Print][PDF][Excel].
FILTERS: Current Period Jan–Aug 2026 · Previous Period Jan–Aug 2025 · Branch All ·
Department All · Cost Centre All · Currency MWK.
TABLE Description | Current | Previous | Variance | %:
REVENUE: Sales Revenue 125,000,000 / 110,000,000 / 15,000,000 / 13.64% · Service Revenue
25,000,000 / 20,000,000 / 5,000,000 / 25.00% · Other Operating Revenue 5,000,000 /
4,000,000 / 1,000,000 / 25.00% · TOTAL REVENUE 155,000,000 / 134,000,000 / 21,000,000 /
15.67%.
COST OF SALES: Cost of Goods Sold (65,000,000) / (58,000,000) / (7,000,000) / 12.07% ·
TOTAL COST OF SALES same.
GROSS PROFIT 90,000,000 / 76,000,000 / 14,000,000 / 18.42% · Gross Profit Margin 58.06% /
56.72%.
OPERATING EXPENSES: Salaries & Wages (30,000,000)/(27,000,000)/(3,000,000)/11.11% · Rent
& Utilities (8,000,000)/(7,500,000)/(500,000)/6.67% · Transport (4,000,000)/(3,000,000)/
(1,000,000)/33.33% · Marketing (3,000,000)/(2,500,000)/(500,000)/20.00% · Depreciation
(2,500,000)/(2,000,000)/(500,000)/25.00% · Other (4,000,000)/(3,500,000)/(500,000)/14.29%
· TOTAL OPERATING EXPENSES (51,500,000)/(45,500,000)/(6,000,000)/13.19%.
OPERATING PROFIT 38,500,000 / 30,500,000 / 8,000,000 / 26.23% · Finance Income 2,000,000 /
1,500,000 / 500,000 / 33.33% · Finance Costs (5,000,000)/(4,000,000)/(1,000,000)/25.00% ·
PROFIT BEFORE TAX 35,500,000 / 28,000,000 / 7,500,000 / 26.79% · Income Tax Expense
(8,875,000)/(7,000,000)/(1,875,000)/26.79% · NET PROFIT 26,625,000 / 21,000,000 /
5,625,000 / 26.79% (double-rule).
CHART: Jan–Aug monthly net-profit trend bars; click bar → drill into period. Rail ⚡⇄⇩›.

--- 2 · Statement of Financial Position (fin.position) ---
H1 + sub "As at 31 August 2026 · MWK · comparative". Actions: [Expand All][Collapse All]
[Print][PDF sec][Excel].
KPIs row1: Total Assets MWK 632.0M (hero) · Total Liabilities 330.0M · Total Equity
302.0M · Working Capital 187.0M. row2: Current Ratio 2.56x · Debt-to-Equity 1.09x ·
Equity Ratio 47.78% · Net Assets 302.0M.
FILTERS: Period As at 31 Aug 2026 · Branch All · Department All · Currency MWK ·
✓ Balanced chip.
TABLE (same 5 cols): ASSETS → NON-CURRENT: Land & Buildings 250,000,000/250,000,000/—/
0.00% · Motor Vehicles 80,000,000/70,000,000/10,000,000/14.29% · Office Equipment
25,000,000/22,000,000/3,000,000/13.64% · Accumulated Depreciation (45,000,000)/
(38,000,000)/(7,000,000)/18.42% · Total PPE 310,000,000/304,000,000/6,000,000/1.97% ·
Intangible Assets 15,000,000/12,000,000/3,000,000/25.00% · TOTAL NON-CURRENT 325,000,000/
316,000,000/9,000,000/2.85%. CURRENT: Inventory 95,000,000/82,000,000/13,000,000/15.85% ·
Trade Receivables 75,000,000/68,000,000/7,000,000/10.29% · Other Receivables 12,000,000/
10,000,000/2,000,000/20.00% · Cash & Bank 125,000,000/105,000,000/20,000,000/19.05% ·
TOTAL CURRENT 307,000,000/265,000,000/42,000,000/15.85% · TOTAL ASSETS 632,000,000/
581,000,000/51,000,000/8.78%.
LIABILITIES → NON-CURRENT: Long-Term Loans 180,000,000/195,000,000/(15,000,000)/(7.69%) ·
Lease Liabilities 30,000,000/32,000,000/(2,000,000)/(6.25%) · TOTAL 210,000,000/
227,000,000/(17,000,000)/(7.49%). CURRENT: Trade Payables 55,000,000/48,000,000/
7,000,000/14.58% · Short-Term Loans 35,000,000/30,000,000/5,000,000/16.67% · Accrued
18,000,000/15,000,000/3,000,000/20.00% · Tax Payable 12,000,000/9,000,000/3,000,000/
33.33% · TOTAL CURRENT 120,000,000/102,000,000/18,000,000/17.65% · TOTAL LIABILITIES
330,000,000/329,000,000/1,000,000/0.30%.
EQUITY: Share Capital 200,000,000/200,000,000/—/0.00% · Retained Earnings 75,000,000/
52,000,000/23,000,000/44.23% · Current Year Profit 27,000,000/—/27,000,000/— · TOTAL
EQUITY 302,000,000/252,000,000/50,000,000/19.84% · TOTAL LIABILITIES & EQUITY
632,000,000/581,000,000/51,000,000/8.78% (double-rule).
BALANCE banner: "✓ BALANCED — Total Assets (632,000,000) = Total Liabilities (330,000,000)
+ Total Equity (302,000,000)".
DRILL-DOWN — Trade Receivables K75,000,000: ABC Limited 25,000,000 · XYZ Limited
18,000,000 · Other 32,000,000 · TOTAL 75,000,000. Rail ⚡📕⤵›.

--- 3 · Cash Flow Statement (fin.cashflow) ---
H1 + sub "01 Jan – 31 Aug 2026 · MWK · Opening + Net Movement = Closing".
Actions: [Forecast sec][Drill Down][Print][PDF][Excel].
TABLE Activity | In (K) | Out (K) | Net (K): Operating 186,000,000 / (158,000,000) /
28,000,000 (Customer receipts 186,000,000 · Supplier payments (104,000,000) · Payroll
(24,000,000) · Operating expenses (30,000,000)) · Investing 500,000 / (8,000,000) /
(7,500,000) · Financing 5,000,000 / (7,000,000) / (2,000,000) · Net Cash Movement
191,500,000 / (173,000,000) / 18,500,000 · Opening Cash 93,300,000 · Closing Cash
111,800,000 (double-rule).
CHECK note: Closing = Opening 93,300,000 + Net 18,500,000 = 111,800,000 (Cash 1,550,000 +
Bank 110,250,000). Rail ⚡📈›.

--- 4 · Accounts Receivable Aging (fin.ar-aging) ---
H1 + sub "Money owed by customers · overdue invoices · credit exposure · K'000".
Actions: [Send Statements sec][Drill Down][Print][PDF][Excel].
SUMMARY by Customer (90+ = 1,200 red badge): Beta Industries 4,000/2,000/—/—/—/6,000 ·
Alpha Traders 1,500/500/800/—/—/2,800 · Metro Supplies —/—/600/—/1,200/1,800 · Total
5,500/2,500/1,400/—/1,200/10,600.
OUTSTANDING INVOICES: Beta Industries INV-001255 due 13 Sep 2,300 Current (👁 💰 ) ·
Metro Supplies INV-001101 due 09 Jun 1,200 90+ (👁 💰 ✉). Rail ⚡✉›.

--- 5 · Accounts Payable Aging (fin.ap-aging) ---
H1 + sub "Money owed to suppliers · payment priority · cash requirement · K'000".
Actions: [Schedule Payments sec][Drill Down][Print][PDF][Excel].
SUMMARY by Vendor (due soon = 8,000 amber): Kamuzu Estates 8,000/—/—/—/—/8,000 · AHL Group
1,000/850/—/—/—/1,850 · Office Supplies Co —/—/320/—/—/320 · Total 9,000/850/320/—/—/
10,170.
OUTSTANDING BILLS: Kamuzu Estates BILL-0830 due 01 Sep 8,000 Current (👁 💳 📅) · Office
Supplies Co BILL-0861 due 08 Aug 320 31–60 (👁 💳 📅). Rail ⚡📅›.

--- 6 · PDF Engine samples (fin.pdf) — clean editorial template ---
Shared header: "C" deep-teal tile + "CamelotBooks" + "Enterprise Accounting & Advisory
Services"; right = report title uppercase + period line + teal accent bar. NO meta block.
Column headers = actual years (2026 / 2025). Footer: "www.camelotbooks.com ·
info@camelotbooks.com · +265 1 234 567" left · "Page 1 of 1" right. Sign-off:
"Prepared By" / "Authorised By — Signature & Date".
PDF-1 Income Statement 01 Jan – 31 Aug 2026 · MWK: Revenue (Sales 125,000,000/
110,000,000/13.64 · Service 25,000,000/20,000,000/25.00 · Total 155,000,000/134,000,000/
15.67) · Gross Profit 90,000,000/76,000,000/18.42 · Operating Profit 38,500,000/
30,500,000/26.23 · Income Tax (8,875,000)/(7,000,000)/26.79 · Net Profit 26,625,000/
21,000,000/26.79.
PDF-2 SFP As at 31 August 2026: Non-current Assets 325,000,000/316,000,000 · Current
Assets 307,000,000/265,000,000 · Total Assets 632,000,000/581,000,000 · Total Liabilities
330,000,000/329,000,000 · Total Equity 302,000,000/252,000,000 · Total Liab & Equity
632,000,000/581,000,000 + "✓ Balances — Total Assets equal Total Liabilities plus Equity
(K632,000,000)".
PDF-3 Cash Flow: Operating 28,000,000 · Investing (7,500,000) · Financing (2,000,000) ·
Net Increase 18,500,000 · Opening 93,300,000 · Closing 111,800,000.
PDF-4 Receivables Aging: Beta Industries 4,000/2,000/—/—/6,000 · Metro Supplies —/600/—/
1,200/1,800 · Total 5,500/3,400/—/1,200/10,100.
PDF-5 Payables Aging: Kamuzu Estates 8,000/—/—/—/8,000 · AHL Group 1,000/850/—/—/1,850 ·
Total 9,000/1,170/—/—/10,170. Rail 📕›.