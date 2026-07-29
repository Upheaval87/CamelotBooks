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

### Key patterns:
- Page titles: `<x-slot name="header">{{ __('Title') }}</x-slot>` — renders as `<h1>` below the topbar (italic serif, `text-ink`)
- Show pages with entity: `{{ __('Customer Detail') }} - {{ $customer->name }}`
- Show pages with ref#: `{{ __('Invoice') }} #{{ $invoice->invoice_number }}`
- Record toolbar: `<x-record-toolbar>` with `.tr-group`/`.tr-group-label`/`.tr-item`/`.tr-save`/`.tr-archive`/`.tr-more` classes
- Detail grid: `<div class="detail-grid">` with `<x-detail-field label="...">value</x-detail-field>`
- Balance panel: `<div class="balance-grid">` + `<div class="balance-total-row">`
- Form inputs: `.input` class (bottom-border only), `<x-input-label>` (11px uppercase), `<x-text-input>`
