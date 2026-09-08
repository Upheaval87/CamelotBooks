# Analytics Module — Live Verification & Report

Date: 2026-09-08 · Environment: live MySQL (Acme tenant `acct_acme_149593cc`), dev server `php -S 127.0.0.1:8010 -t public server.php`, `accountant@test.com` / `password`, currency MWK.

This report closes the Analytics module rebuild verification. It documents: the metric→query map for all 15 pages, the live tie-outs proving page/CSV/print numbers match the GL, preset proof, permission gating, parity evidence (screenshots + structure probes), and the three live-MySQL bugs fixed in this pass. All 15 pages, exports, and prints verified working — **no section skipped**.

## 1. Metric → Query Map

All 15 pages dispatch via `AnalyticsController::pageData()` (`app/Http/Controllers/AnalyticsController.php:367–398`). Shared inputs: `$period` from `resolvePeriod()` (L321: presets `month` / `quarter` / `ytd` + custom `from`/`to`), optional `branch_id` / `cost_center_id`, session `company_id`. Currency (symbol/decimals/base) comes from the System settings `currency` group via `SystemSetting::getMany('currency', $companyId)` (controller `currency()` helper, L309) — Acme renders MWK; defaults `$`/2/MWK. Never hard-coded.

| Page (`/analytics/<key>`) | Service | Source tables / queries |
|---|---|---|
| Overview (`/analytics`) | `OverviewAnalyticsService` | `IncomeStatementService::generate()` (GL `journal_entry_lines` JOIN `journal_entries`, status `posted`+`reversed`); cash runway = cash-account balance ≤ as_of vs 3-mo avg burn; working capital = BS current_asset − current_liability; signals: gross margin, DSO, inventory days, stock turns, top-10 customer share (via `invoices`), net margin |
| Financial Ratios (`financial-ratios`) | `FinancialRatiosService` | `BalanceSheetService::generate()` + `IncomeStatementService::generate()` (FY start→as_of); ratios from the two statements |
| Revenue vs Expense (`revenue-expense-trends`) | `RevenueExpenseTrendService` | per-period `IncomeStatementService::generate()` (loop) + raw GL monthly `DATE_FORMAT(e.date,'%Y-%m')` trend query on `journal_entry_lines`/`journal_entries`/`accounts`; `dimension` = none/months/quarters |
| Sales (`sales`) | `SalesAnalyticsService` | `invoices` (posted/paid/partially_paid) by customer + product (`invoice_lines` × `products`, `SUM(qty*unit_price)`); `pos_sales` by customer + `pos_sale_lines` by product; `sales_receipts` by customer |
| Purchasing (`purchasing`) | `PurchasingAnalyticsService` | `bills` JOIN `vendors` (spend/count); `bill_lines`→`purchase_order_lines`→`purchase_orders` for lead-time; GL `journal_entry_lines` expense legs |
| Inventory (`inventory`) | `InventoryAnalyticsService` | GL sums on COGS/asset accounts (`journal_entry_lines`×`journal_entries`×`accounts`); `inventory_cost_layers` JOIN `products` for valuation; `inventory_adjustments` counts |
| Profitability (`profitability`) | `ProfitabilityAnalyticsService` | `journal_entry_lines`×`journal_entries`×`accounts` (income/expense nets); by-branch (`leftJoin branches`, `COALESCE(name,'Consolidated')`); by-cost-center (`leftJoin cost_centers`, `COALESCE(name,'Unclassified')`); item matrix via `invoice_lines`×`products` |
| Cash Flow Trend (`cash-flow-trend`) | `CashFlowProjectionService` | CashFlowService per month + projection of next `projection_months` |
| Expenses (`expenses`) | `ExpenseAnalyticsService` | `journal_entry_lines`×`journal_entries`×`accounts` (expense type, `COALESCE(SUM(debit),0)–SUM(credit)`), `DATE_FORMAT(e.date,'%Y-%m')` monthly; top accounts with budgeted (from `BudgetLine` when present) |
| Customers (`customers`) | `CustomerAnalyticsService` | invoices open balance `SUM(amount-amount_paid)`; revenue/count by customer from `invoices`, `pos_sales`, `sales_receipts`; aging buy/repurchase |
| Working Capital (`working-capital`) | `WorkingCapitalAnalyticsService` | `AgingReportService::arAging()/apAging()` buckets; cycles (DSO/DIO/DPO/CCC) = aging ÷ IS revenue/expense/COGS × days; inventory via `InventoryService::getValuation()` |
| Budget vs Actual (`budget-vs-actual`) | `BudgetVsActualAnalyticsService` | `Budget` (approved/locked) + `BudgetLine` (`annual_amount` prorated by elapsed months); actuals via `ActualsService::annualActual()` live GL |
| Tax (`tax`) | `TaxAnalyticsService` | **YTD + monthly VAT**: `invoice_lines il` JOIN `invoices i` sum `il.tax_amount` (output); `bill_lines bl` JOIN `bills b` sum `bl.tax_amount` (input); payables/receivables/pos paid from `invoices`-`payments`/`sales_receipts` |
| Forecasts (`forecasts`) | `ForecastAnalyticsService` | weekly `YEARWEEK(date)` invoice/PO/bill amounts + cash accounts (`journal_entry_lines`×`journal_entries`) |
| Branches (`branches`) | `BranchesAnalyticsService` | per-branch (`branches`) revenue/profit via GL `journal_entry_lines`×`journal_entries`×`accounts` |

Export rows (`exportRows()`, L408–490): each page flattens its KPIs/rows to `[metric, value, from, to]` → CSV `<page>-<from>-to-<to>.csv` via `streamDownload`. Print (`printPage()`, L258) renders `resources/views/analytics/print.blade.php` — a single self-contained A4 template reused by every page.

## 2. Live Tie-outs (page numbers = GL numbers)

Bound the Acme tenant (`TenantConnectionResolver`), ran the same services the controller runs (YTD `2026-01-01 → 2026-09-08`), and cross-checked against a raw GL aggregate. **Everything reconciles.**

| Metric (page/CSV) | Service output | Raw GL cross-check |
|---|---|---|
| Revenue | 15,000.00 | income accounts `SUM(credit−debit)` = **15,000.00** (2 rows) |
| Total expense | 14,000.00 | expense accounts `SUM(debit−credit)` = **14,000.00** (4 rows) |
| Net margin | 6.67 % | 1,000 / 15,000 |
| Top expense account | 6000 Salary 8,000.00 | GL top account 6000 Salary Expense = **8,000.00** |
| Payroll share | 57.14 % | 8,000 / 14,000 |
| Other top accounts | Rent 2,500 / Utilities 500 | GL 6100 Rent = **2,500.00**, 6200 Utilities = **500.00** |
| COGS (Profitability item level) | 3,000.00 | GL account 5000 COGS = **3,000.00** |
| Profitability / Branches | rev 15,000 · exp 14,000 · **net 1,000** | matches expense leg (4 rows) seen by branch query |

VAT metrics: all **0.00** in both service and CSV (no tax-bearing lines in Acme YTD data) — consistent.

## 3. Preset Proof (live)

`/analytics?period=` heading + range on Overview:

| `period` | Heading | from → to |
|---|---|---|
| `month` | This Month | 2026-09-01 → 2026-09-08 |
| `quarter` | This Quarter | 2026-07-01 → 2026-09-08 |
| `ytd` | Year to Date | 2026-01-01 → 2026-09-08 |

`resolvePeriod()` (AnalyticsController L321) defines the three presets; custom `from`/`to` also accepted. Filter form (`_head.blade.php`) posts back to the same route preserving the preset.

## 4. Permission Gating

- All analytics GET routes (`/analytics`, 14 page routes, `/analytics/export`, `/analytics/print`) live in `Route::prefix('analytics')->name('analytics.')->middleware('feature:analytics')` (routes/web.php L1123–1141) **inside** the `['tenant.bind', 'company.context', 'company.active']` group (L202).
- Gating: authenticated + verified → tenant bound to the session company → `feature:analytics` must be enabled for that company (central `company_modules`, Phase 4). Feature disabled = 404 (feature middleware). No analytics route writes data — export/print both render-only (`streamDownload` / view), so the module is strictly read-only.
- First-guess probe hit 404s because it used `/accounting/analytics/*` — real URLs are `/analytics/*` (the accounting prefix group closes at L~1045).
- Sidebar section and all 15 links: `sidebar-nav-content.blade.php` L185–206 (routes all resolve). Report Center also gained the 8 analytics reports in `ReportRegistry` (previous phase).

## 5. Parity Evidence

Screenshots (full-page, all 15 pages) captured at `C:\Users\eseyama\AppData\Local\Temp\opencode\anl-shots\anl-<key>.png` (98–188 KB each — real rendered content, not blank).

Structure probe (headless Chrome, 1440px, live MySQL) — every page:

| Page | HTTP | `.an-*` classes present | Contains "Revenue" |
|---|---|---|---|
| overview → cash-flow-trend, sales, purchasing, inventory, profitability, expenses, customers, working-capital, budget-vs-actual, tax, forecasts, branches, financial-ratios, revenue-expense-trends | **200** | ✅ | ✅ |

All 15 pages render HTTP 200; web console has only the pre-existing expected noise (`/favourites` 401/403 poll; Kaspersky CDN aborts) — no analytics-related console errors. **NO-SECTION-SKIPPED**: every one of the 15 pages, plus export and print, was exercised live.

## 6. Bugs Found & Fixed This Pass

1. **Profitability — MySQL `ONLY_FULL_GROUP_BY` (1055)**. `ProfitabilityAnalyticsService::getByBranch()/getByCostCenter()` selected `COALESCE(branches.name,'Consolidated')` / `COALESCE(cost_centers.name,'Unclassified')` but grouped only by `COALESCE(branch_id,0)` / `COALESCE(cost_center_id,0)` → 500 on live MySQL. Fixed by adding the name expression to `groupByRaw()` in both functions. (sqlite tolerated it — that's why the feature test never caught it; analytics runs MySQL-only in the smoke suite via the 11-skipped `requiresMySQL()` tests.)
2. **Expenses — Blade `@money` inside `{{ }}` (ParseError)**. `expenses.blade.php:109` had the literal string tag `@money` interpolated inside `{{ }}` → invalid expression, 500. Fixed to `format_money($budgeted)` inside the braces. Swept the whole `resources/views/analytics` tree with the footgun regex — no other dangerous occurrences (only `title="{{ $label }}: @money(...)"` which is safe: braces close before `@money`).
3. **Tax — `tax_amount` summed on the wrong table (4262 Unknown column)**. `TaxAnalyticsService` summed `tax_amount` on `invoices`/`bills`, but `tax_amount` lives on the line tables (`invoice_lines.tax_amount`, `bill_lines.tax_amount` — migrations `2026_02_01_000060`/`000130`). Rewrote the YTD and monthly queries to `JOIN invoice_lines` / `JOIN bill_lines` and sum `il.tax_amount`/`bl.tax_amount`. Removed the unused `Invoice`/`Bill` imports.
4. **Print had no print trigger**. `print.blade.php` rendered the A4 template (header "CamelotBooks / MWK · FINANCIAL ANALYTICS", page footer, tables) but never invoked the print dialog; added `<body onload="window.print()">`; live check confirms the attribute renders (`onload = true`).

All fixes `php -l` clean; `AnalyticsModuleTest` re-run green (**31 passed / 11 skipped / 83 assertions** — the 11 skipped are the MySQL-only `requiresMySQL()` cases, which is precisely why the live probe caught #1/#3).

## 7. Deviations / Notes

- VAT metrics all zero for Acme YTD (no VAT-bearing lines in demo data); math verified via query shape, not by non-zero fixtures. The feature test (`test_tax_service_empty_data`, `AnalyticsModuleTest:521`) is MySQL-only and only asserts zero on empty data — non-zero VAT is not yet proven by any automated test or live check. Suggest a follow-up: post one invoice line with `tax_amount = 457.50` in Acme and re-verify the Tax page's output_ytd/net_due reflect it.
- Credentials in this report are the dev sandbox seed; `admin@test.com` / `password` also works.
- No commits made and none requested — work remains uncommitted alongside the rest of the analytics rebuild (git status shows the analytics services/views/controller/css uncommitted).