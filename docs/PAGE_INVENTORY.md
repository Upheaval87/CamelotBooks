# Page Migration Inventory (Step 2 deliverable)

Scope: every page that deviates from the reference design system (docs/DESIGN_SYSTEM.md).
Reference pages (already conforming): all `resources/views/superadmin/*` + `components/review/*`
(AGENTS.md item 32).

Status legend: `[ ]` = pending, `[~]` = in progress, `[x]` = done.

---

## Priority A — Core entity CRUD (list / show / create / edit) — highest visibility

Each row = 1 module; the 4 standard pages are listed inline.

### Sales & customers
- [ ] Customers — `accounting/customers/{index,show,create,edit}`
- [ ] Invoices — `accounting/invoices/{index,show,create,edit}` (+ `create` line rows Alpine)
- [ ] Quotations — `accounting/quotations/{index,show,create,edit}` (print excluded)
- [ ] Credit Notes — `accounting/credit-notes/{index,show,create}`
- [ ] Sales Receipts — `accounting/sales-receipts/{index,show,create}` (print excluded)
- [ ] Sales Register — `accounting/sales-register/index`

### Purchasing & vendors
- [ ] Vendors — `accounting/vendors/{index,show,create,edit}`
- [ ] Bills — `accounting/bills/{index,show,create,edit}`
- [ ] Purchase Orders — `accounting/purchase-orders/{index,show,create,edit}`
- [ ] Purchase Requisitions — `accounting/purchase-requisitions/{index,show,create,edit}`
- [ ] Goods Received Notes — `accounting/goods-received-notes/{index,show,create}`
- [ ] Vendor Credits — `accounting/vendor-credits/{index,show,create}`
- [ ] Vendor Centre — `accounting/vendor-centre/{index,show}`
- [ ] Vendor Payments — `accounting/vendor-payments/{index,show,create}` (index is the list)

### Money & banking
- [ ] Bank Accounts — `accounting/bank-accounts/{index,register,transfer,manual-transaction}`
- [ ] Cheques — `accounting/cheques/{index,register,show,create}`
- [ ] Deposits — `accounting/deposits/{index,create}`
- [ ] Expenses — `accounting/expenses/{index,show,create,edit}`
- [ ] Petty Cash — `accounting/petty-cash/{index,show,create-fund}`
- [ ] Customer Payments — `accounting/customer-payments/{index,show,create}`
- [ ] Bank Reconciliation — `accounting/bank-reconciliation/{index,show,import}`

### Inventory & products
- [ ] Products — `accounting/products/{index,show,create,edit}`
- [ ] Item Categories — `accounting/item-categories/{index,show,create,edit}`
- [ ] Assemblies — `accounting/assemblies/{index,show,boms,create,create-bom,history,unbuild}`
- [ ] Inventory Items — `accounting/inventory-items/{index,show}`
- [ ] Stock Counts — `accounting/stock-counts/{index,show,create,edit}`
- [ ] Stock Adjustments — `accounting/stock-adjustments/{index,show,create}`
- [ ] Stock Transfers — `accounting/stock-transfers/{index,show,create}`
- [ ] Landed Costs — `accounting/landed-costs/{index,show,create}`
- [ ] UOM Conversions — `accounting/uom-conversions/{index,edit}`
- [ ] Cost Centers — `accounting/cost-centers/index`
- [ ] Exchange Rates — `accounting/exchange-rates/index`
- [ ] Low Stock — `accounting/low-stock/index`
- [ ] Inventory Valuation — `accounting/inventory-valuation/{index,by-category}` (print excluded)

### Chart of accounts & GL
- [ ] Accounts — `accounting/accounts/{index,show,create,edit}`
- [ ] Journal Entries — `accounting/journal-entries/{index,show,create}` (review-style show already migrated)
- [ ] Recurring Journals — `accounting/recurring-journals/{index,show,create,edit}`
- [ ] General Ledger — `accounting/general-ledger/{index,account}` (print excluded)
- [ ] Trial Balance — `accounting/trial-balance/index` (print excluded)
- [ ] Balance Sheet — `accounting/balance-sheet/index` (print excluded)
- [ ] Income Statement — `accounting/income-statement/index` (print excluded)
- [ ] Cash Flow — `accounting/cash-flow/index` (print excluded)
- [ ] Equity Statement — `accounting/equity-statement/index` (print excluded)
- [ ] Aging — `accounting/aging/{ar-summary,ap-summary,ar-detail,ap-detail}`
- [ ] Tax Return — `accounting/tax-return/index`
- [ ] Cash Position — `accounting/cash-position/index`
- [ ] Account Classification — `accounting/account-classification/index`

### Fixed assets
- [ ] Fixed Assets — `accounting/fixed-assets/{index,show,create,edit}`
- [ ] Asset Categories — `accounting/asset-categories/{index,show,create,edit}`
- [ ] Asset Transfers — `accounting/asset-transfers/{index,create}`
- [ ] Asset Revaluations — `accounting/asset-revaluations/{index,create}`
- [ ] Asset Impairments — `accounting/asset-impairments/{index,create}`
- [ ] Asset Disposals — `accounting/asset-disposals/{index,create}`
- [ ] Asset Usage — `accounting/asset-usage/index`
- [ ] Asset Depreciation — `accounting/asset-depreciation/{schedule,run-history}`

### Payroll & employees
- [ ] Employees — `accounting/employees/{index,show,create,edit}`
- [ ] Payroll Runs — `accounting/payroll-runs/{index,show,create}` (review-style show already migrated; paye/pension/print-payslips excluded)
- [ ] PAYE Tables — `accounting/paye-tables/{index,show,create,edit}`
- [ ] Pension Schemes — `accounting/pension-schemes/{index,show,create,edit}`

### Fiscal setup
- [ ] Fiscal Years — `accounting/fiscal-years/{index,show}`
- [ ] Periods — `accounting/periods/index`
- [ ] Budgets — `accounting/budgets/{index,show,create,edit,variance}`

---

## Priority B — Admin & company config

- [ ] Branches — `branches/index`
- [ ] Company picker — `companies/index`
- [ ] Admin Users — `admin/users/{index,show,create,edit}` (2 files + partials)
- [ ] Admin Permissions — `admin/permissions/index`
- [ ] Admin Audit Log — `admin/audit-log/index`
- [ ] Admin Backups — `admin/backups/index`
- [ ] Admin Notifications — `admin/notifications/*`
- [ ] Admin Numbering Sequences — `admin/numbering-sequences/*` (4)
- [ ] Admin Security — `admin/security/index`
- [ ] Admin Setup Wizard — `admin/setup-wizard/index`
- [ ] Admin System Health — `admin/system-health/index`

---

## Priority C — Settings, POS, analytics, misc

- [ ] System Settings — `system-settings/*` (17 files, settings design already modern but different style)
- [ ] POS — `pos/{sales,cashier,terminals,payment-methods,till-sessions,settlements,returns,eis,reports}/*`
- [ ] Analytics — `analytics/*` (8)
- [ ] BI — `bi/*` (5)
- [ ] Report Center — `report-center/*` (2)
- [ ] Branch Requests (tenant) — `branch-requests/{index,show}` (review-style already)
- [ ] My Tasks — `todo/{index,_deadline-chips,_task-row,_task-row-completed}`
- [ ] Profile — `profile/{index,partials/*}` (password card already restyled)
- [ ] Panel — `panel/index`

---

## Excluded (not CRUD/interactive lists — out of scope)

- Print/PDF views (financial statements `print`, quotations/sales-receipts `print`, payroll schedules,
  `accounting/print-export`, POS thermal receipts) — deliberate Arial/Courier print styling.
- Auth area (`auth/*`, `layouts/auth`) — already on `AuthLayout`, not part of app design system.
- `errors/*`, `welcome.blade.php`, `layouts/*` internals.

---

## Priority D — Financial report catalog (`accounting/reports/`, 39 files)

Each is a filter-bar + datasheet page (list-based → in scope for restyle; prints inside them stay).

- [ ] reports/{bank-balances, cheque-register, customer-credit-balance, customer-statement,
      deposits-in-transit, item-profitability, journal-report, po-status, purchase-register,
      purchases-by-item, purchases-by-vendor, quotation-status, sales-by-customer, sales-by-item,
      stock-movement, stock-count-variance, vendor-credit-balance, vendor-statement, chart-of-accounts,
      trial-balance-comparison, consolidated-balance-sheet, consolidated-income-statement,
      pending-approvals-aging, period-lock-status, payroll-register, payroll-summary, payslip-report,
      paye-remittance-report, pension-remittance-report, employee-cost-by-branch, eis-submission-status,
      assembly-build-history, asset-disposal-report, asset-impairment, asset-revaluation,
      bank-reconciliation-history, tax-depreciation-schedule, unbilled-deliveries, unbilled-receipts}

---

## Totals (approx)

- Priority A: ~60 modules / ~220 blade files (incl. sub-views)
- Priority B: ~12 files
- Priority C: ~60 files
- Priority D: 39 report views
- Excluded prints: ~25 files

~330 tenant view files total; roughly 180-200 interactive pages to restyle in Phases 1-3 (A + B + C),
plus 39 report pages (D).
