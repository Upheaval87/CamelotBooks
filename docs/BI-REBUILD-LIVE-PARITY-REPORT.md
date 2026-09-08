# BI Rebuild — Live MySQL Parity Verification (Sep 9, 2026)

All 14 BI pages now render HTTP 200 against the real ACME tenant DB
(`acct_acme_149593cc`, MySQL on 127.0.0.1:33063) under `feature:bi`, in addition to
the green sqlite test suite. The rebuild has **no skipped/dangling sections** —
every page (`overview`, `branch`, `clv`, `employee`, `truecost`, `product`,
`supplier`, `workcap`, `scenarios`, `variance`, `pvm`, `breakeven`, `cohorts`,
`board`) renders its full markup with the `.an-*` teal design-system classes.

## MySQL-only defects fixed (masked by the sqlite test override)

The feature suite runs on `:memory:` sqlite via `TENANT_ROUTING_OVERRIDE`. Six
distinct failures only surfaced when the same code ran against the live MySQL
tenant:

1. **`Product::reorderPoint()` does not exist** — `OverviewInsightService::buildAlerts()`
   called a method that was never on `App\Models\Product`. It now reads the
   existing `effective_reorder_point` accessor (falls back to the product
   category default) with a `?? 0` guard. `app/Services/BI/OverviewInsightService.php:103`.

2. **`vendor_credits.posted` invalid column** — `SupplierScorecardService` used
   `->where(VendorCredit::STATUS_POSTED)` which compiled to `where posted is null`
   (the constant was consumed as the column name). Reworked to
   `->where('status', VendorCredit::STATUS_POSTED)`. `app/Services/BI/SupplierScorecardService.php:36`.

3. **`only_full_group_by` violation** — the open-PO commitment query selected
   `purchase_order_id`, `quantity`, `unit_price` (never consumed) while grouping
   by `vendor_id`, which MySQL rejects under `sql_mode=only_full_group_by`
   (sqlite tolerates it). Dropped the non-aggregated select columns, keeping
   only the `SUM(...)`/`COUNT(DISTINCT ...)` aggregates. `app/Services/BI/SupplierScorecardService.php:79-88`.

4. **`products.managed_inventory` / `costing_method` don't exist** —
   `ProductAbcService` selected two non-existent columns that were never read.
   Trimmed the column list to what the ABC loop actually uses.
   `app/Services/BI/ProductAbcService.php:19`.

5. **`employees.name` doesn't exist** — the tenant schema stores
   `first_name`/`middle_name`/`last_name` with a `full_name` accessor; the
   service selected a literal `name` column. Now selects the name parts and uses
   `$employee->full_name`. `app/Services/BI/EmployeeProductivityService.php:86,108`.

6. **`bi_actions.status` missing (schema drift)** —
   `BoardPackService::compile()` and `BiController::saveAction()` both read/write
   a `status` column that `2026_12_03_000100_create_bi_tables.php` never created.
   Fixed with an additive, column-guarded migration
   `database/migrations/tenant/2026_12_03_000101_add_status_to_bi_actions_table.php`
   and `status` added to `BiAction::$fillable`.
   - **Migration-ordering gotcha**: a first attempt as `2026_09_08_000001_...`
     sorted *before* the create migration, so its `!Schema::hasTable('bi_actions')`
     guard made it a silent no-op on fresh DBs (sqlite tests) while it happened to
     fix the already-created live tenants. `2026_12_03_000101_...` sorts after the
     create (`add` would sort before `create` at the same number — `'a' < 'c'`),
     and the `hasColumn` guard makes it a safe no-op where the column already exists.

## Live infrastructure steps performed

- **`php artisan tenant:migrate`** against all 4 provisioned tenants — applied the
  BI rebuild tables (`bi_settings`, `bi_expense_classes`, `bi_actions`,
  `bi_audit_log` in `2026_12_03_000100`).
- **Probe role grant** (probe setup only): the data-parity probe logs in as
  `accountant@test.com` (central user id 2). The live tenant DBs never had their
  stub `users`/`model_has_roles` populated, so every permission-gated route was
  returning 403 for non-seeded users — a known pre-existing gap (AGENTS §38).
  Inserted `model_has_roles(role_id=1 /*system_admin*/, model_type='App\Models\User',
  model_id=2, company_id=1)` into `acct_acme_149593cc` so the probe could render
  the pages. This is not required for normal seeded users (e.g. `probe@camelot.test`).

## Verification

- Headless-Chrome probe (browser.mjs + `anl-probe.mjs`, `accountant@test.com`):
  login → company 1 select → all 14 `/bi*` routes HTTP 200, correct `<h1>`,
  non-zero `.an-*` counts; `non200: []`. Screenshots:
  `C:\Users\eseyama\AppData\Local\Temp\opencode\anl-shots\{clv,overview}.png`
  (plus the report-center suite shots captured earlier).
- Suites green: `BiModuleRebuildTest` (12) + `BiPosCoverageTest` + `ReportRenderSmokeTest`
  + `ReportCenterTest` → **29 passed / 276 assertions**, no risky.
- `php artisan view:cache` clean.