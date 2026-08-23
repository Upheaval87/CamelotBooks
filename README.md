# CamelotBooks

A comprehensive, multi-tenant accounting and business management platform built with Laravel 12. CamelotBooks handles everything from day-to-day bookkeeping to advanced financial reporting, payroll, inventory, and point-of-sale operations — all within a single, unified interface.

---

## Overview

CamelotBooks is designed for small-to-medium businesses that need a professional, cloud-ready accounting system. It features a role-and-permission-based architecture with database-per-tenant isolation, a modern teal-and-navy design system, and modules covering the full financial lifecycle.

### Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Tailwind CSS 4, Alpine.js 3, Vite 7 |
| Auth | Laravel Breeze + Spatie Permissions (teams) |
| PDF | DomPDF, mPDF |
| Excel | Maatwebsite Excel |
| Testing | PHPUnit 11 |

---

## Modules

### Core Accounting
- **Chart of Accounts** — full hierarchy with tree/list views, drag-to-reparent, bulk activate/deactivate, COA audit trail, copy-last-year balances
- **Journal Entries** — create, post, reverse, approve, recurring journals with template management
- **General Ledger** — period-aware account statements with opening/closing balances
- **Trial Balance** — posted and adjusted views, comparison between periods
- **Bank Reconciliation** — interactive CSV import with column mapping, matching engine, approval workflow, exception reports
- **Petty Cash** — fund establishment, expenses, replenishments

### Sales
- **Customers** — full CRUD with balance tracking, statements, CSV export
- **Quotations** — create, send, accept/decline, convert to invoice, live document preview, PDF generation, CSV export
- **Invoices** — line-item management with tax/discount, copy-from-quotation, attachments, posting
- **Sales Receipts** — draft/posted lifecycle, journal entry preview before posting, thermal print, daily summary & cashbook reports
- **Credit Notes** — issue against invoices

### Purchasing
- **Vendors** — full CRUD with balance tracking
- **Purchase Requisitions** — request/approve workflow
- **Purchase Orders** — create, receive, convert to bills
- **Goods Received Notes** — receive inventory against purchase orders
- **Bills** — create/edit with freight/insurance/customs charges, line-item management, attachments, submit-for-approval
- **Vendor Credits** — issue against bills
- **Landed Cost Vouchers** — allocate additional costs to received goods

### Inventory
- **Products** — items, assemblies, bill of materials
- **Inventory Adjustments** — stock corrections
- **Inventory Transfers** — branch-to-branch
- **Stock Counts** — physical count reconciliation
- **Item Categories** — category-based defaults (income/expense accounts)
- **Assemblies** — build and unbuild finished goods from components
- **Stock Movements** — movement history with running quantities

### Banking
- **Bank Accounts** — register, transactions, balances
- **Deposits** — record bank deposits with journal entries
- **Cheques** — issue, void, register with status tracking
- **Transfers** — inter-account transfers
- **Bank Reconciliation** — match bank statements against book entries

### Payroll
- **Employees** — profiles, departments, branches
- **Payroll Runs** — calculate, approve, post to GL, generate payslips
- **PAYE Tax Tables** — statutory tax brackets
- **Pension Schemes** — employer/employee contributions
- **Payslips** — secure encrypted PDF generation and email delivery
- **Employee Onboarding Wizard** — step-by-step new employee setup

### Fixed Assets
- **Asset Register** — track assets with depreciation schedules
- **Depreciation** — run depreciation, tax depreciation schedules
- **Asset Transfers, Revaluations, Impairments, Disposals**

### Budgeting
- **Budget Centre** — create operating/capital/project/department budgets
- **Budget Lines** — income/expense with seasonal/custom distributions
- **Actuals vs Budget** — live GL comparison
- **Forecast** — projection based on historical actuals
- **Budget Adjustments** — increase/reduce/transfer with approval
- **Budget Alerts** — threshold-based notifications

### Point of Sale
- **POS Checkout** — product search, customer selection, split payments
- **POS Settlements** — payment method reconciliation
- **POS Returns** — return against original sale
- **Cashier Login/Logout** — till session management

### Reporting (77 reports)
- **Financial Statements** — Balance Sheet, Income Statement, Cash Flow Statement, Trial Balance, General Ledger
- **Sales Reports** — by customer, by item, quotation register, sales pipeline, daily summary, cashbook
- **Purchasing Reports** — by vendor, by item, purchase register, unbilled receipts
- **Inventory Reports** — valuation, stock movements, count variance, item profitability
- **Payroll Reports** — PAYE schedule, pension schedule, employee cost by branch/cost centre
- **Bank Reports** — bank balances, cheque register, deposits in transit
- **Budget Reports** — budget vs actual, forecast, adjustments
- **Cash Position** — real-time cash dashboard with inflow/outflow bars, movement categories, period-aware ledger drill-down
- **Report Centre** — searchable directory with per-user favourites, A-Z sort, category filter

### Administration
- **Super Admin Panel** — company provisioning, user management, role assignments, currency management, central audit log
- **System Settings** — 12 configuration tabs (company profile, regional, currency, accounts, accounting, approval, notifications, data hub, import/export, features, backups, audit log)
- **Features Management** — enable/disable modules per company from the super admin panel
- **Database Backups** — per-tenant backup and restore via mysqldump
- **Role & Permission Console** — interactive matrix editor for 60+ roles across all modules

### Multi-Tenancy
- **Database-per-tenant** isolation with automatic provisioning
- **Runtime tenant routing** — connection resolved per request, never flips the default
- **Tenant migrations** — `tenant:migrate` command for schema updates
- **Tenant data migration** — copy company data from shared DB to isolated tenant DB
- **Branch limit enforcement** — per-company limits with concurrency locking
- **Branch requests** — request/approve/quotation/payment/fulfillment workflow

### Auth & Security
- **Code-based password reset** — 6-digit verification code (no email links)
- **Password policy enforcement** — min length, mixed case, numbers, symbols with live checklist
- **Login attempt logging** — failed/success/deactivated/rate-limited
- **Support sessions** — super admin enters company context with audit trail
- **Company access audit** — login, switch, support actions logged
- **Brute-force protection** — rate limiting on login, company switch, password reset

---

## Design System

The UI uses a teal-and-navy executive design language with:
- **Glass-morphism cards** — `backdrop-filter: blur(14px)` with translucent white
- **Navy gradient tables** — `#24384f → #182a3e → #132234` with gold link hovers
- **Gold accent CTAs** — gradient buttons with inset highlight shadows
- **Inter font family** — full weight range (400–800)
- **Modular suite CSS** — scoped design blocks (`.q2-*`, `.sr-*`, `.cp2-*`, `.bk-*`, `.bg-*`, `.rj-*`, `.ss-*`, `.va-*`) prevent cross-module style leaks
- **Dialog system** — `CB.confirm`, `CB.toast`, `CB.prompt`, `CB.busy` replacing native browser dialogs
- **Text size control** — per-user A-/A/A+ scale persisted across sessions
- **Responsive** — breakpoints at 1180px, 1024px, 768px, 420px

---

## Getting Started

### Prerequisites
- PHP 8.2+
- MySQL 8.0+ (production) or SQLite (development)
- Node.js 18+ and npm
- Composer

### Installation

```bash
git clone https://github.com/Upheaval87/CamelotBooks.git
cd CamelotBooks
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

### Environment Variables

Key `.env` settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=camelot_books
DB_USERNAME=root
DB_PASSWORD=

# Tenancy provisioning connection (can be the same MySQL server)
TENANT_ROUTING_BASE_CONNECTION=mysql

# PDF binaries (Windows)
DB_MYSQLDUMP="C:\xampp\mysql\bin\mysqldump.exe"
DB_MYSQL="C:\xampp\mysql\bin\mysql.exe"
```

### Testing

```bash
php artisan test
```

---

## Project Structure

```
app/
├── Console/Commands/         # Artisan commands (tenant:migrate, tenant:sync-permissions, bi:refresh-data-mart, etc.)
├── Http/Controllers/
│   ├── Accounting/           # All accounting module controllers (~40)
│   ├── Admin/                # Permissions, backups, audit log
│   ├── Auth/                 # Login, registration, password reset
│   ├── SuperAdmin/           # Panel: companies, users, assignments, currencies, audit
│   └── Panel/                # Company picker for super admins
├── Models/                   # 163 Eloquent models (TenantScoped where applicable)
├── Services/
│   ├── Accounting/           # Business logic (InvoiceService, BillService, JournalPostingEngine, etc.)
│   ├── BI/                   # Data mart builders and consumers
│   ├── Tenancy/              # Provisioning, tenant routing, data migration
│   ├── SuperAdmin/           # Role catalog, branch reader
│   └── Auth/                 # Password policy, verification codes
resources/
├── views/                    # ~497 Blade templates
│   ├── accounting/           # Module views (invoices, bills, quotations, banking, etc.)
│   ├── superadmin/           # Panel views
│   ├── admin/                # Settings, permissions, backups
│   └── components/           # Shared Blade components (x-ui.*, x-review.*, x-list-*, etc.)
├── js/                       # Alpine.js components (permissions-console, feedback, verify-code, etc.)
└── css/app.css               # Main stylesheet (~9500 lines, modular suite blocks)
routes/web.php                # ~1000 lines of route definitions
config/permissions.php        # 622 unique permission names across 60+ roles
database/migrations/          # 195 migration files (central + tenant)
tests/                        # 83 test files
docs/                         # Design mockups and reports
```

---

## Roles & Permissions

CamelotBooks ships with a granular permission system built on Spatie Laravel Permission with team support. Roles include:

| Role | Scope |
|------|-------|
| `super_admin` | Platform-wide (Super Admin panel, company provisioning) |
| `company_admin` | Full access within a company |
| `accountant` | All accounting modules |
| `bookkeeper` | Day-to-day entries (journals, invoices, bills) |
| `cashier` | POS and petty cash only |
| `approver` | Approval workflows (invoices, bills, journals, budgets) |
| `viewer` | Read-only across all modules |
| `auditor` | Read-only + audit trail access |
| `billing` | Branch requests, quotations, invoices (read-only) |

The **Role & Permission Console** provides an interactive matrix editor where administrators can toggle permissions per module per role in real time.

---

## Multi-Tenancy Architecture

```
┌─────────────────────────────────┐
│         Central Database        │
│  companies, users, roles,       │
│  permissions, currencies,       │
│  super_admin_audit_logs         │
└────────────┬────────────────────┘
             │
    ┌────────┴────────┐
    │                 │
┌───▼──────┐  ┌──────▼───┐
│ Tenant A │  │ Tenant B │  ... (one DB per company)
│ accounts │  │ accounts │
│ invoices │  │ invoices │
│ journal_ │  │ journal_ │
│ entries  │  │ entries  │
└──────────┘  └──────────┘
```

- **Database-per-tenant** isolation: each company gets its own MySQL database (`acct_{slug}_{hash}`)
- **Runtime routing**: `TenantConnectionResolver` binds the tenant connection per request — the default connection is never flipped
- **Legacy mode**: unprovisioned companies pass through unbound (backwards compatible with pre-Phase-2 data)
- **Tenant migrations**: `php artisan tenant:migrate` applies schema updates across all provisioned tenants

---

## License

MIT

---

Built with Laravel, Tailwind CSS, Alpine.js, and a lot of attention to detail.
