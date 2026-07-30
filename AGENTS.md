# AGENTS.md

## Commands
- Build: `npm run build` (compile Vite assets)

## State
### Completed redesign phases:
1. **CSS tokens** — `ink`/`line`/`panel`/`gold`/`brick`/`forest` color scales, serif/mono fonts, `max-w-8xl`
2. **App CSS** — component-layer: cards, buttons (4 variants), inputs, datasheets, modals, status pills, KPI cards, page titles
3. **Button component** — unified `x-button`, all old button components delegate to it
4. **Header overhaul** — all pages (~180) stripped old `<h2 class="font-semibold...">` wrapper from `<x-slot name="header">`; titles now plain text (rendered below topbar)
5. **Datasheets** — all tables (~178) converted to `datasheet` class
6. **Form layouts** — all forms (~160) use `card p-6`, `form-section-label`, `input mt-1` for selects
7. **Content widths** — all pages now use consistent `max-w-8xl mx-auto sm:px-6 lg:px-8` (was a mix of max-w-7xl/5xl/4xl/3xl/2xl/full)
8. **Show pages** — all 24 show pages converted: `card p-6`, `detail-grid`/`x-detail-field`, `kpi-card`/`kpi-label`/`kpi-value`, `x-record-toolbar`, `status-pill`, `datasheet`, `x-button`, entity names appended where appropriate (Customer Detail - Name, Employee Detail: Name)
9. **Invoice-form input styling** — shared `.input` CSS changed to bottom-border-only (no box/bg/radius), `.input-label` → 11px, `.btn-primary` → gold accent, `.btn-ghost` → permanent border, `.btn-remove` added
10. **Record page redesign** — all 35 show pages converted to new `x-record-toolbar` (#F3ECDC bg, grouped actions), `detail-grid` (3-column), `balance-grid`/`balance-total-row`, `x-detail-field` component
11. **Two-row topbar navigation** — replaced sidebar + single-row topnav with horizontal two-row bar. Row 1 (60px `bg-ink`): gold "L" brand mark + "CamelotBooks" + divider + company/branch (session-derived) + user avatar/name/role + logout. Row 2 (46px `#212B37`): Dashboard, Payments, Invoices, Customers, Journal, Reports + "···" overflow dropdown with all other section links (Inventory, Banking, Chart of Accounts, General Ledger, Trial Balance, Budgets, Fixed Assets, Payroll, Analytics, BI, POS, Settings, Users). Old sidebar CSS removed. Dark mode sidebar CSS removed.
12. **System Settings redesign** — left sidebar nav (3 groups, gold accent), bottom-border-only inputs, pill toggles, callouts, numbered eyebrow headers, status pills, inline audit-log diffs. Components: `resources/views/components/settings/` (sidebar, field, toggle, callout, table). CSS: `.settings-*` styles in `app.css`. 9 tab partials (`_tab-*.blade.php`) plus backups and audit-log pages rewritten.
13. **List page redesign** — applied customer-list design to all 7 entity list pages (Customers, Vendors, Products, Employees, Invoices, Bills, Sales Receipts). Shared components: `resources/views/components/list/` (quick-links, icon-button, avatar-initials, filter-bar, header). CSS: `.list-*` styles in `app.css` (sidebar layout, warm-header table, avatar initials, icon buttons with tooltips, mobile card rows below 720px). Pattern: italic serif title + Create button header, sticky 240px quick-links sidebar, underline-style filter bar, warm-header table with avatar initials for person entities, right-aligned bold numbers, status pills, icon-only action buttons with hover tooltips, mobile card rows.
14. **Currency symbol moved to column headers** — all 8 core financial report views (~28 files): replaced `format_money()` → `format_number()` in every amount cell; added `({{ $cs }})` suffix to column headers where missing (Inventory Valuation, AR/AP Aging); stripped redundant `{{ $cs }}` prefix from subtotals/totals in print views (Income Statement, Balance Sheet, Cash Flow). Symbol now appears ONLY in column headers, never on individual amounts.

### Completed performance audit (Jul 30, 2026):
- **N+1 fixes (7 locations)**: CreditNoteController (added `'invoice'`), VendorCreditController (added `'bill'`), ItemCategoryController (added accounts), TrialBalanceController (replaced per-account SUM loop with single grouped query), BalanceSheetService (replaced per-account computeBalanceAsOf with single grouped query), InventoryValuationController (single cost-layer query for all products), VendorCentreService (getVendorSummary: 5 queries/vendor → 4 grouped; getVendorStats: 8 queries → 4 grouped)
- **Indexes migration**: `2026_07_30_200423_add_performance_indexes.php` — added indexes on purchase_orders, purchase_requisitions, goods_received_notes, landed_cost_vouchers, inventory_adjustments, inventory_transfers, inventory_cost_layers, stock_counts, budgets, cheques, journal_entry_lines
- **Unbounded queries**: Added `->limit(100)` to CustomerPaymentController::create() and VendorPaymentController::create() open invoice/bill queries
- **Async mail queueing**: Added `implements ShouldQueue` to all 4 mailables (SalesReceiptMail, QuotationMail, PayslipMail, ExecutiveDigestMail); changed `->send()` → `->queue()` in controllers/commands
- **Other findings**: EncryptedPayslipService generates PDF synchronously with mPDF (blocking); QUEUE_CONNECTION=sync (needs redis/beanstalkd in production); SESSION_DRIVER=database, CACHE_STORE=database (both can switch to file/redis); jobs table migration already exists; `optimize-autoloader: true` in composer.json

### Key patterns:
- Page titles: `<x-slot name="header">{{ __('Title') }}</x-slot>` — renders as `<h1>` below the topbar (italic serif, `text-ink`)
- Show pages with entity: `{{ __('Customer Detail') }} - {{ $customer->name }}`
- Show pages with ref#: `{{ __('Invoice') }} #{{ $invoice->invoice_number }}`
- Record toolbar: `<x-record-toolbar>` with `.tr-group`/`.tr-group-label`/`.tr-item`/`.tr-save`/`.tr-archive`/`.tr-more` classes
- Detail grid: `<div class="detail-grid">` with `<x-detail-field label="...">value</x-detail-field>`
- Balance panel: `<div class="balance-grid">` + `<div class="balance-total-row">`
- Form inputs: `.input` class (bottom-border only), `<x-input-label>` (11px uppercase), `<x-text-input>`
- List pages: `<x-list-header>`, `<x-list-filter-bar>` (underline-style inputs/selects in a form), `<x-list-quick-links>` (sidebar with groups/links/icons), `<x-list-avatar-initials>` (initials in circle), `list-table-wrap` + `list-table` (warm-header `#EDE8DA`), `icon-btn` (28px icon buttons with tooltip on hover), `list-mobile-cards` (card view below 720px)
- Settings pages: `<x-settings.sidebar>` (sticky 220px nav with divider groups), `<x-settings.field>` (bottom-border label+value), `<x-settings.toggle>` (pill switch), `<x-settings.callout>` (info/warning block), `<x-settings.table>` (compact with mapping statuses)
