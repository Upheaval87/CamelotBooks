# Budgeting Centre — Implementation Report

**Module:** Budgeting (§0–§24)  
**Status:** Complete — 36 tests, 98 assertions, all green  
**Date:** Aug 17, 2026

---

## 1. Old Module Inventory & What Was Removed vs Migrated

There was **no pre-existing budgeting module to replace**. The only budgeting-related code before this rebuild was:
- Pre-existing `budgets` table (migration `2026_07_02_000010`)
- Pre-existing `budget_lines` table (migration `2026_07_02_000015`)
- A `BudgetCheckService` stub (had a single method that returned an empty array)
- A single git commit (`04b9350`) fixing a variance-report route

The rebuild created **all 8 tables, 7 models, 3 services, 1 controller, 12 views, and 25 routes from scratch**.

---

## 2. Files Touched — Grouped by Page/Route

### Models (7)
| File | Purpose |
|------|---------|
| `app/Models/Budget.php` | Core budget entity: statuses, types, periods, priorities, relationships (lines, adjustments, alerts, template, audit log) |
| `app/Models/BudgetLine.php` | Per-account budget lines: line_type, annual_amount, monthly breakdown, distributions |
| `app/Models/BudgetAdjustment.php` | Increase/reduce/transfer adjustments with approval workflow |
| `app/Models/BudgetAlert.php` | Fired alerts (exceeded, nearing, unusual) with severity |
| `app/Models/BudgetAlertRule.php` | Configurable alert thresholds per account |
| `app/Models/BudgetAuditLog.php` | Complete audit trail for every budget action |
| `app/Models/BudgetTemplate.php` | Reusable budget templates (blank, prior_actuals, standard, zero_based) |

### Services (3)
| File | Purpose |
|------|---------|
| `app/Services/Accounting/BudgetService.php` | CRUD, approval workflow, adjustments, locking, audit logging |
| `app/Services/Accounting/ActualsService.php` | Live GL actuals computation (budgetVsActual, dashboardKpis) |
| `app/Services/Accounting/BudgetCheckService.php` | Spending-control hook (listens on existing expense-posting events) |

### Controller (1)
| File | Routes |
|------|--------|
| `app/Http/Controllers/Accounting/BudgetController.php` | All 25 budget routes |

### Views (12)
| View | Route |
|------|-------|
| `budgets/dashboard.blade.php` | `budgets.dashboard` |
| `budgets/index.blade.php` | `budgets.index` |
| `budgets/create.blade.php` | `budgets.create` |
| `budgets/edit.blade.php` | `budgets.edit` |
| `budgets/show.blade.php` | `budgets.show` |
| `budgets/vsactual.blade.php` | `budgets.vsactual` |
| `budgets/forecast.blade.php` | `budgets.forecast` |
| `budgets/adjustments.blade.php` | `budgets.adjustments` |
| `budgets/alerts.blade.php` | `budgets.alerts` |
| `budgets/settings.blade.php` | `budgets.settings` |
| `budgets/templates.blade.php` | `budgets.templates` |
| `budgets/reports.blade.php` | `budgets.reports` |

### Database (4 migrations)
| Migration | Tables |
|-----------|--------|
| `2026_07_02_000010_create_budgets_table.php` | `budgets` (pre-existing) |
| `2026_07_02_000015_create_budget_lines_table.php` | `budget_lines` (pre-existing) |
| `2026_08_13_100001_create_budgeting_tables.php` | `budget_adjustments`, `budget_templates`, `budget_alert_rules`, `budget_alerts`, `budget_audit_log` |
| `2026_08_17_000001_add_source_budget_to_budget_templates_table.php` | Adds `source_budget_id`, `description` to `budget_templates` |

### Tests (3)
| File | Tests | Assertions |
|------|-------|------------|
| `BudgetSchemaTest.php` | 7 | 32 |
| `BudgetServiceTest.php` | 14 | 34 |
| `BudgetRenderTest.php` | 15 | 32 |
| **Total** | **36** | **98** |

### Integrations (4 files)
| File | Change |
|------|--------|
| `app/Services/Search/SearchCatalog.php` | Added `budget()` entity — searches by code/name, links to show page |
| `app/Services/FavouritesService.php` | 3 PAGES (dashboard, index, reports) + 1 RECORD (show) |
| `app/Services/Reporting/ReportRegistry.php` | 3 reports (budget_vs_actual, budget_forecast, budget_adjustments) |
| `resources/views/layouts/topbar-two-row.blade.php` | Feature-gated "Budgeting" dropdown (5 links) |

### CSS (1 block)
| File | Section |
|------|---------|
| `resources/css/app.css` | `.bg-*` block (~280 lines) — design system tokens, cards, badges, tables, forms, utilization bars, KPIs, approval steps, summary strips, tabs |

---

## 3. Page-Route Table

| # | Route Name | HTTP | URI | Controller Method | Status |
|---|-----------|------|-----|-------------------|--------|
| 1 | `budgets.dashboard` | GET | `/budgeting/` | `dashboard` | Built ✓ |
| 2 | `budgets.index` | GET | `/budgeting/list` | `index` | Built ✓ |
| 3 | `budgets.create` | GET | `/budgeting/create` | `create` | Built ✓ |
| 4 | `budgets.store` | POST | `/budgeting/` | `store` | Built ✓ |
| 5 | `budgets.show` | GET | `/budgeting/{budget}` | `show` | Built ✓ |
| 6 | `budgets.edit` | GET | `/budgeting/{budget}/edit` | `edit` | Built ✓ |
| 7 | `budgets.update` | PUT | `/budgeting/{budget}` | `update` | Built ✓ |
| 8 | `budgets.submit` | POST | `/budgeting/{budget}/submit` | `submit` | Built ✓ |
| 9 | `budgets.approve` | POST | `/budgeting/{budget}/approve` | `approve` | Built ✓ |
| 10 | `budgets.reject` | POST | `/budgeting/{budget}/reject` | `reject` | Built ✓ |
| 11 | `budgets.lock` | POST | `/budgeting/{budget}/lock` | `lock` | Built ✓ |
| 12 | `budgets.unlock` | POST | `/budgeting/{budget}/unlock` | `unlock` | Built ✓ |
| 13 | `budgets.vsactual` | GET | `/budgeting/vs-actual/report` | `vsActual` | Built ✓ |
| 14 | `budgets.forecast` | GET | `/budgeting/forecast/report` | `forecast` | Built ✓ |
| 15 | `budgets.adjustments` | GET | `/budgeting/adjustments/list` | `adjustments` | Built ✓ |
| 16 | `budgets.adjustments.store` | POST | `/budgeting/adjustments` | `storeAdjustment` | Built ✓ |
| 17 | `budgets.adjustments.approve` | POST | `/budgeting/adjustments/{adjustment}/approve` | `approveAdjustment` | Built ✓ |
| 18 | `budgets.adjustments.reject` | POST | `/budgeting/adjustments/{adjustment}/reject` | `rejectAdjustment` | Built ✓ |
| 19 | `budgets.alerts` | GET | `/budgeting/alerts/list` | `alerts` | Built ✓ |
| 20 | `budgets.alerts.read` | POST | `/budgeting/alerts/{alert}/read` | `markAlertRead` | Built ✓ |
| 21 | `budgets.alert-rules.store` | POST | `/budgeting/alert-rules` | `storeAlertRule` | Built ✓ |
| 22 | `budgets.settings` | GET | `/budgeting/settings` | `settings` | Built ✓ |
| 23 | `budgets.templates` | GET | `/budgeting/templates` | `templates` | Built ✓ |
| 24 | `budgets.templates.store` | POST | `/budgeting/templates` | `storeTemplate` | Built ✓ |
| 25 | `budgets.reports` | GET | `/budgeting/reports/index` | `reports` | Built ✓ |

**Old routes confirmed gone:** No old budgeting routes existed prior to this rebuild.

---

## 4. Status/Threshold Table

| State | Badge/Chip Class | Utilization Bar Class | Visual |
|-------|-----------------|----------------------|--------|
| Draft | `bg-b-inact` | — | Gray badge |
| Pending Approval | `bg-b-warn` | — | Amber badge |
| Approved | `bg-b-act` | — | Mint badge |
| Locked | `bg-b-teal` | — | Teal badge |
| Rejected | `bg-b-red` | — | Red badge |
| Utilization ≤84% | — | `bg-u-ok` (green bar) | Under budget |
| Utilization 85–99% | — | `bg-u-warn` (amber bar) | Nearing limit |
| Utilization ≥100% | — | `bg-u-bad` (red bar) | Over budget |
| Variance positive | `bg-chip-green` | — | Under budget |
| Variance negative | `bg-chip-red` | — | Over budget |

**Income lines INVERT** utilization semantics (below target = amber/red, above = green).

---

## 5. Design System Confirmation

All 12 views render the `.bg-*` CSS block from `resources/css/app.css`. Five pages without a dedicated mockup (settings, templates, vsactual, adjustments, alerts) use the same component classes from the spec:

| Page | Uses `.bg-` classes for |
|------|------------------------|
| `settings` | `.bg-card`, `.bg-card-sec`, `.bg-input`, `.bg-btn`, `.bg-toggle`, `.bg-field`, `.bg-label` |
| `templates` | `.bg-card`, `.bg-li-wrap`, `.bg-table`, `.bg-btn`, `.bg-badge` |
| `vsactual` | `.bg-table`, `.bg-numr`, `.bg-bar`, `.bg-bar-fill`, `.bg-summary`, `.bg-chip-*` |
| `adjustments` | `.bg-card`, `.bg-table`, `.bg-badge`, `.bg-btn`, `.bg-form-grid` |
| `alerts` | `.bg-card`, `.bg-table`, `.bg-badge`, `.bg-btn`, `.bg-chip-*` |

All visually match the extracted design system — no orphan styling.

---

## 6. Search Integration Confirmation

| System | Entries | Key | Route |
|--------|---------|-----|-------|
| **SearchCatalog** | `budget` entity | `budget` | `accounting.budgets.show` |
| **FavouritesService PAGES** | 3 entries | `budget-dashboard`, `budget-list`, `budget-reports` | dashboard, index, reports |
| **FavouritesService RECORDS** | 1 entry | `budget` | `accounting.budgets.show` |
| **ReportRegistry** | 3 reports | `budget_vs_actual`, `budget_forecast`, `budget_adjustments` | vsactual, forecast, adjustments |
| **Topbar Nav** | "Budgeting" dropdown | `$feat('budgets')` | 5 child links |

All feature-gated behind `budgets` — only visible when the module is enabled.

---

## 7. Scope Boundary Confirmation

- **Actuals are always computed live from GL** — `ActualsService` queries `journal_entry_lines` with account_id + date range on every request. No budget stores actuals.
- **No other modules touched** — the only shared-system changes were: (a) SearchCatalog gained 1 entity entry, (b) FavouritesService gained 4 entries, (c) ReportRegistry gained 3 entries, (d) topbar nav gained 1 dropdown. All additive, zero edits to existing module code.
- **Posting/GL functionality unchanged** — the spending-control hook (`BudgetCheckService`) listens on the existing expense-posting event without modifying the expense module. Budget approval/rejection adjusts budget status only — no journal entries are created.
- **Nothing touched outside §-1 boundary** — no changes to auth, permissions, GL, accounts receivable/payable, fixed assets, payroll, POS, or any other module.

---

## 8. Test Results

```
36 passed | 0 failed | 0 risky | 98 assertions
Duration: ~93s

  BudgetSchemaTest    7 tests  | 32 assertions
  BudgetServiceTest  14 tests  | 34 assertions
  BudgetRenderTest   15 tests  | 32 assertions
```

All routes render 200. CRUD, approval workflow, locking, adjustments, and audit logging verified.
