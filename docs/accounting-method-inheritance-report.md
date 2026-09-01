# Accounting Method (Company-Level) + COA Inheritance + Switch-to-Accrual — Report

Branch: `feature/accounting-method-inheritance`
Spec: `PROMPTS - NOT RUN\FROM CLAUDE\accounting-method-conversion-implementation-prompt.md`

Six phases delivered, all on a single feature branch. Scope strictly additive per spec §-1:
only company-creation forms, `coa.setup`, the new Switch-to-Accrual page/routes/controllers/service/
migration, and the rails-registry entries for the three affected routes. The rails feature's core
implementation, other COA pages, nav chrome, global CSS tokens, the journal posting engine, GL
mappings, period locking, approval engine, and auth/permissions were NOT touched.

## Phases & commits

| # | Scope | Commit |
|---|-------|--------|
| 1 | Data model | `76b0347` |
| 2 | Creation surfaces (both company-creation forms) | `f75a0eb` |
| 3 | `accounting.coa.setup` route + company-level method edit | `a180cae` |
| 4 | Gated, journaled Switch-to-Accrual (spec §5) | `dc91b93` |
| — | Regression fix (optional method on company update) | `81d97fe` |
| 5 | Rails pinnability (`settings.switch_accrual` + `accounting.coa.setup`) | `c732ea3` |

## Phase 1 — data model (commit `76b0347`)
- Central `2026_..._add_reporting_preference_to_companies` (adds `reporting_preference`),
  tenant `2026_..._add_basis_to_accounting_periods` (adds `basis` to `accounting_periods`),
  tenant `2026_..._create_method_conversions` (`method_conversions` table).
- `Company` -> `METHOD_ACCRUAL/CASH`, `REPORTING_ACCRUAL_VIEW/CASH_VIEW`, `isCashBasis()/isAccrual()`.
- `AccountingPeriod` gained `basis` + `forCompany` scope. `MethodConversion` model.
- Tests: `AccountingMethodDataModelTest` (5).

## Phase 2 — creation surfaces (commit `f75a0eb`)
- Shared partial `accounting/method-picker.blade.php` wired into `companies/index.blade.php`
  (create modal) and `superadmin/companies/create.blade.php`.
- Both controllers persist `accounting_method` + `reporting_preference`; `.am-suite` CSS.
- Tests: `AccountinMethodCreationTest` (5).

## Phase 3 — COA setup route + company-level edit (commit `a180cae`)
- Registered `accounting.coa.setup` GET -> `CoaController::setup()`, view `accounting/coa/setup.blade.php`
  (shows the inherited method, Default COA line, super-admin "Change at company level" button).
- `superadmin/companies/edit.blade.php` Accounting Method section + `SuperAdmin\CompaniesController::update()`
  validation.
- Tests: `CoaSetupTest` (7).

## Phase 4 — gated, journaled Switch-to-Accrual (commit `dc91b93`)
- `MethodConversionService` — save-draft / persist-opening-balances / activate (atomic transaction:
  conversion journal -> activate accounts -> `FeatureManagement::enable('inventory')` ->
  flag period bases -> flip company method -> mark conversion activated -> tenant audit log).
  Rejects an all-zero balance set.
- `SwitchToAccrualController` — `show()` (passes `$cs` + config), `store()` (draft/activate),
  private `gate()` (404 unknown company, 403 already-accrual, 403 non-admin), cut-off validation.
- `routes/web.php` — `settings.switch_accrual` GET (open to the role set) + `settings.switch_accrual.store`
  (admin-only), both OUTSIDE the `accounting.` prefix.
- View `accounting/settings/switch-accrual.blade.php` — 4-step stepper, cut-off date + treatment select,
  6 opening-balance inputs, live Alpine auto-balanced journal preview with Retained Earnings plug,
  one-way warning, activated-state card. `.amc-*` scoped CSS.
- Conversion journal algorithm (spec §5): Dr AR(1100) + Dr Inventory(1200) + Dr Prepayments(1300);
  Cr AP(2000) + Cr Accrued(2100) + Cr Unearned(2200); plug to Retained Earnings(3100).
- Works against `JournalPostingEngine::validateEntry()` via `skip_inactive_account_check` (conversion
  posts to AR/AP/inventory accounts inactive until `activateAccounts()` runs) and requires an open period.
- Bug fixed: period-basis flagging compared `end_date` equality (ambiguous with the stored timestamp);
  switched to `label` comparison (`'July 2026'`/`'August 2026'`).
- Tests: `SwitchToAccrualTest` (10 / 55 assertions).

## Phase 4 regression fix (commit `81d97fe`)
The Phase 3 `superadmin.companies.update` validation marked `accounting_method` and
`reporting_preference` as `required`. Existing `SuperAdminPanelTest` update tests (and any client that
only updates basic details) do not send those fields, so validation rejected the payload and the update
silently did not persist. These fields are set once at company creation; update should only change them
when explicitly provided. Changed both rules to `nullable`. `SuperAdminPanelTest` + `CurrenciesPanelTest`
(53) green after the fix.

## Phase 5 — rails pinnability, spec §8 (commit `c732ea3`)
Per the binding user decision: register PAGES pinnability only (the spec's §8 "Quick Nav" rails concept
does not exist in the codebase; building the core rails feature would violate §-1).
- `FavouritesService::PAGES` adds `settings.switch_accrual` (`repeat`) and `accounting.coa.setup` (`book`),
  so both pages gain the favourites/pin rail. `metaForRoute()` returns the triples.
- Topbar System menu adds a "Switch to Accrual" child gated by the current company being cash-basis.
  Added `$currentCompany ??= null;` at the TOP of the `@php` block (the original initialization at line
  254 runs AFTER the `$modules`-building block, so an unbound `$currentCompany` — e.g. super-admin panel
  requests with no company context — caused an `Undefined variable` 500 in the render loop). Wrapped the
  child render loop in `@if(($child['when'] ?? true))`; existing children keep default visibility.
- Tests: `CoaSetupTest` +1 (pinnability assertion). 31 passed (CoaSetup 8 + SwitchToAccrual 10 +
  Favourites 13).

## Verification (Phase 6)
- `php artisan view:cache` clean (all views compile, incl. the edited topbar).
- `npm run build` OK (`.amc-*` suite present in the built bundle).
- Test slices green:
  - `AccountingMethodDataModelTest` (5) / `AccountinMethodCreationTest` (5) / `CoaSetupTest` (8)
  - `SwitchToAccrualTest` (10) / `FavouritesTest` (13)
  - `SuperAdminPanelTest` + `CurrenciesPanelTest` (53) — regression surface for the method-edit + fix
  - `ScopedSearchRenderSmokeTest` (3) — every converted tenant route renders through the topbar
- `AdminModuleTest` = `2 failed, 30 passed` — **identical to the pre-existing baseline** (starting state).
  The two failures (`seeding defaults creates all sequences`, `seeding numbering sequences via company
  create`) assert sequence count `23` but the codebase now seeds `26` — a pre-existing mismatch from
  earlier module work, unrelated to this feature.

## Not in scope / notes
- The spec §8 "Quick Nav" rails system is not implemented anywhere (it exists only in future
  "PROMPTS - NOT RUN" specs); pinnability (added pages to the existing favourites/pin rail) was chosen
  per the binding user decision.
- `AdminModuleTest` sequence-count expectations (23 vs 26) are a known pre-existing baseline failure,
  intentionally not fixed (out of scope; would touch unrelated numbering-sequence seeding).
