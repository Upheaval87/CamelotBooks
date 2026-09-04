# Taxation Module — Rebuild Blueprint

Read-only analysis of the existing Taxation ("Taxation Centre") module — verified against the
controller, services, all 14 models, the schema migration, the seeder, config, and every blade
view + all 3 test files. Produced as a markdown-ready blueprint for a future rebuild. All file
references are relative to the repo root.

---

## 1. Purpose & Overview

The Taxation module is a self-contained, tenant-scoped tax engine + management centre that:

- **Configures** tax types (VAT, WHT, PAYE, FBT, CORPORATE, PRESUMPTIVE, OTHER), tax codes,
  versioned rates, exemptions, jurisdictions, apportionment rules, recognition rules, and GL
  account mappings.
- **Calculates & records** tax on document lines via an engine (`TaxEngine`) and persists a
  `tax_transactions` row per calculation.
- **Posts** tax to the General Ledger through `JournalPostingEngine` inside the caller's existing
  transaction (atomicity contract).
- **Manages returns** (generate → approve → file), **payments**, **WHT certificates**,
  **adjustments**, and **tax periods** (open/close/lock).
- **Reconciles** calculated vs. expected vs. posted vs. reported tax, exposes a working paper,
  a control-account ledger drill, and a running tax position.
- **Audits** every mutation in a dedicated `tax_audit_trail` table.

It is a **calculation + compliance controller**, not a revenue/expense ledger itself — the actual
money movement lives in the General Ledger via `journal_entries`, with the tax control accounts
(`tax_payable`, `tax_receivable`) as the reconciliation anchor.

---

## 2. Tax Config / Master Data (Schema)

All tables are tenant-scoped (`company_id` on every table) and created by a single guarded
migration `database/migrations/tenant/2026_08_23_100001_create_tax_module_tables.php`
(returns early if `tax_types` exists). **13 tables** (not 14 — EIS is a separate POS-compliance
module, see §12.16).

| Table | Purpose | Key fields |
|---|---|---|
| `tax_types` | Tax type catalogue | `code`(unique/company), `name`, `category`(VAT\|WHT\|PAYE\|FBT\|CORPORATE\|PRESUMPTIVE\|OTHER), `active` |
| `tax_jurisdictions` | Authority jurisdictions | `code`(unique/company), `name`, `country`, `authority`, `active` |
| `tax_codes` | Tax code (treatment + GL wiring) | `code`(unique/company), `tax_type_id`, `jurisdiction_id`, `treatment`, `price_basis`, `rounding_mode`, `rounding_level`, `gl_output_acct`, `gl_input_acct`, `gl_payable_acct`, `effective_from/to`, `active` |
| `tax_code_rates` | Versioned rates | `tax_code_id`, `rate_pct`(8,4), `effective_from`(unique with code), `effective_to` |
| `tax_exemptions` | Exemption reasons | `code`(unique/company), `name`, `reason`, `scope`(SALES\|PURCHASES\|BOTH), `tax_type_id`, effective dates, `active` |
| `tax_registrations` | Entity↔type↔jurisdiction registration | `entity_kind`(COMPANY\|CUSTOMER\|SUPPLIER), `entity_id`, `jurisdiction_id`, `tax_type_id`, `reg_number`, effective dates, `status`(default `active`) |
| `tax_recognition_rules` | Recognition basis per tax type | `tax_type_id`(unique/company), `basis`(INVOICE\|CASH\|PAYMENT\|ACCRUAL), `note` |
| `tax_apportionment_rules` | Input-tax recoverability | `tax_type_id`, `jurisdiction_id`(nullable), `method`(TURNOVER_RATIO\|DIRECT_ATTRIBUTION), `recoverable_pct`(6,3), effective dates, `note` |
| `tax_periods` | Reporting periods per type | `tax_type_id`, `label`(unique/company/type), `start/end_date`, `status`(OPEN\|IN_PREPARATION\|SUBMITTED\|CLOSED\|AMENDED), `filing_due_date`, `filed_date`, `payment_date`, `reference`, `locked`, `version` |
| `tax_transactions` | The calculation ledger | `period_id`, `tax_code_id`, `rate_pct`(8,4), `side`(OUTPUT\|INPUT\|WHT\|PAYE\|ADJUST\|REVERSE_CHARGE_OUT\|REVERSE_CHARGE_IN), `source_kind`(SALES_INVOICE\|PURCHASE_BILL\|EXPENSE\|PAYROLL_RUN\|BANK_TXN\|ADJUSTMENT\|MANUAL), `source_id`, base/tax/gross/net amounts, exemption, apportionment, jurisdiction, `gl_account_id`, `recognition_basis`(snapshot), `recognized_at`, `is_reversal`, `reverses_transaction_id`, `status`(POSTED\|UNPOSTED) |
| `tax_adjustments` | Manual adjustments | `period_id`, `tax_type_id`, `amount`, `direction`(ADD\|REDUCE), `reason`, `status`(PENDING\|APPROVED\|REJECTED), `created_by`, `approved_by` |
| `tax_payments` | Payments vs. authority | `tax_type_id`, `period_id`, `amount`, `payment_date`, `bank_account_id`(nullable), `payment_ref`(unique), `receipt_number`, `authority`, `recorded_by`, `status`(PENDING\|PAID) |
| `wht_certificates` | Withholding certificates | `cert_number`(unique/company), `supplier_id`(accounts), `tax_code_id`, `period_id`, `gross`(15,2), `wht_amount`(15,2), `rate_pct`(8,4), `status`(DRAFT\|ISSUED), `issued_date` |
| `tax_returns` | Filed returns | `tax_type_id`, `period_id`(unique/company/type), `status`(default `DRAFT`), `output_tax`, `input_tax`, `adjustments`, `net_payable`, `filed_date`, `reference`, `prepared_by`, `approved_by`, `version` |
| `tax_return_lines` | Return line items (`drill_query` = JSON filter) | `return_id`, `section`(OUTPUT\|INPUT\|ADJUST\|TOTAL), `label`, `amount`, `drill_query` |
| `tax_audit_trail` | Audit log | `user_id`, `acted_at`, `entity_kind`, `entity_id`, `field`, `old_value`, `new_value`, `reason`, `approval`, `ip` |

### Seed defaults (`database/seeders/TaxModuleSeeder.php`)
- **Tax types**: VAT, WHT, PAYE, FBT (FBT created inside the code seed).
- **Jurisdiction**: `MWI` — Malawi / Malawi Revenue Authority.
- **Tax codes**: `VAT_STD`, `VAT_ZERO`, `VAT_EXEMPT`, `VAT_INC`, `VAT_RC` (reverse charge),
  `WHT_SUP` (10%), `WHT_SERV` (15%), `FBT_STD` (30%), all `effective_from 2024-01-01`.
- **Recognition rules**: VAT→INVOICE, WHT→PAYMENT, PAYE→ACCRUAL.
- Uses `session('current_company_id') ?? 1`; NOT a tenant-loop seeder.

---

## 3. Core Functionality (Routes → Actions → Services)

Routes live at `routes/web.php:742-775`, all under `accounting.` prefix + `taxation.` name +
`middleware('permission:taxation.view|taxation.edit')`. The **whole group is inside the
`feature:tax` gate** (see §9). Permission module: `config/permissions.php:122`
`'taxation' => ['view','create','edit','void','approve']`.

### Services (`app/Services/Tax/`)
| Service | Contract |
|---|---|
| `TaxEngine` | `calculateAndPostTax(array)` — core calc (§4), `postTaxJournal(array)` — JE post, `validateNoOverlappingRates()`, `validateNoOverlappingRegistrations()`, `computeTax()`, `roundTax()`, `validateRoundTrip()`, `handleReverseCharge()` |
| `TaxReturnService` | `generateReturn()`, `approve()`, `file()`, `reject()`, `buildReturnLines()` |
| `TaxPaymentService` | `recordPayment()`, `getPaymentsForPeriod()`, `getTotalPaidForReturn()`, `voidPayment()` |
| `WhtCertificateService` | `generate()`, `revoke()`, `generateBatch()`, `createFromForm()`, `nextCertNumber()` |
| `TaxRegistrationService` | `checkRegistration()`, `register()`, `deregister()` |

### Controller (`app/Http/Controllers/Accounting/TaxController.php`, 1,364 lines)
| Method | Route (→name) | Action |
|---|---|---|
| `dashboard` | `GET /` | KPIs (net_payable/output/input/outstanding/paid/current period) + upcoming deadlines + unfiled count + exceptions (rate overlap, unfiled, missing rate) + 12 recent periods |
| `config` | `GET /config` | Tabbed single-card read-only screen (incl. `_tabs`), `$activeTab` from `tab` query |
| `codes` / `types` / `rates` / `exemptions` / `jurisdictions` | GET | Standalone list screens (reuse `_tabs` partial) |
| `accounts` | GET | `$taxPayableAccount`/`$taxReceivableAccount` + `$otherMappings` (from `DefaultAccountMapping`); reads are read-only |
| `periods` | GET | Paginated periods + per-period output/input stats (`attachPeriodStats`; KPIs open/awaiting/filed) |
| `returnWorkingPaper` | `GET /returns/{period}/working-paper` | Grouped by code+side: expected vs calculated vs variance + summary (`workingPaperSummary`) + GL movement |
| `reconciliation` | GET | Per period×side: expected/calculated/posted/variance + reported/report_variance vs filed return + GL payable movement |
| `certificates` | GET | WHT cert list |
| `reports` | GET | Candidate report directory (Route::has + FeatureManagement gated) |
| `auditTrail` | GET | Audit log w/ entity-kind + from/to date filters |
| `currentPosition` | GET `position` | Per tax-type: collected/recoverable/adjustments/paid/outstanding + totals |
| `controlAccount` | GET | Running balance ledger of configured tax control account (`$account`) |
| `payments` / `storePayment` | GET/POST | Payment list + record (via `TaxPaymentService`) |
| `payeWht` | GET | PAYE & WHT transaction lists + totals + recent certs |
| `calendar` | GET | Filing obligations w/ completed/overdue/urgent/upcoming/future status |
| `recognitionRules` | GET | Recognition rules + types without a rule |
| `storeAdjustment` / `approveAdjustment` / `rejectAdjustment` | POST | PENDING→APPROVED/REJECTED w/ audit |
| `generateReturn` / `approveReturn` / `rejectReturn` / `fileReturn` | POST | Return lifecycle |
| `generateCertificate` / `revokeCertificate` | POST | WHT cert create/revoke |
| `voidPayment` | POST | Payment void |
| `closePeriod` | POST | OPEN→CLOSED |

The controller relies on base `Controller` helpers: `requirePermission()`, `companyId()`
(= `session('current_company_id')`), `cs()` (currency symbol from `SystemSetting`).

**Route count**: 34 named routes; the render test exercises 23 GET routes + 1 working paper.
Confirmed route names: `taxation.dashboard`, `.config`, `.codes`, `.types`, `.rates`,
`.exemptions`, `.jurisdictions`, `.accounts`, `.periods`, `.reconciliation`, `.certificates`,
`.reports`, `.audit-trail`, `.position`, `.control-account`, `.payments` (+`.store`, `.void`),
`.recognition-rules`, `.paye-wht`, `.calendar`, `.returns.working-paper` (+`.generate`,
`.approve`, `.reject`, `.file`), `.adjustments.store/.approve/.reject`, `.certificates.generate/
.revoke`, `.periods.close`.

---

## 4. Calculation Rules

### 4.1 `TaxEngine::calculateAndPostTax(context)` contract
Minimal context: `company_id`, `tax_code_id`, `base_amount`, `side`
(`OUTPUT|INPUT|WHT|PAYE`), `user_id`. Optional: `source_kind/source_id`, `source_module`,
`reference`, `memo`, `account_id`, `tax_account_id`, `period_id`, `exemption_id`,
`apportionment_pct` (INPUT only), `jurisdiction_id`, `date`. **NOTE: the E2E test passes
`account_id` + `tax_account_id` as the explicit GL wiring** (not via `gl_output_acct` — see §12).

Flow:
1. `validateContext()` — required keys present; side in allowed set; `base_amount >= 0`.
2. `activeRate($date)` — resolves the single applicable `TaxCodeRate` by effective window
   (`TaxCode::activeRate()`); throws if none.
3. `computeTax()` — treatment switch (see 4.2).
4. Exemption → zero tax + `exemption_reason`.
5. Apportionment (INPUT only) → `recoverable_tax_amount = tax × pct / 100`.
6. Reverse-charge → splits into an OUTPUT and INPUT `tax_transactions` pair (4.4).
7. Rounding (4.3).
8. GL account from explicit context accounts or `gl_output_acct` / `gl_input_acct` /
   `gl_payable_acct` fallback by side/treatment.
9. Recognition basis from `TaxRecognitionRule` (`{'invoice' => recognized_at=now}`, default INVOICE).
10. `resolveOrCreatePeriod()` — find open unlocked period for type+date, else auto-create a
    monthly `OPEN` period (`filing_due_date = end_of_month + 25 days`).
11. Persist `tax_transactions` (status POSTED) + audit trail.

### 4.2 Treatment computation
| Treatment | Effect |
|---|---|
| `ZERO_RATED` / `EXEMPT` / rate 0 | tax = 0, gross = net = base |
| `STANDARD` / `CHARGED` / `DEDUCTED` (non-special), `price_basis=EXCLUSIVE` | net = base; tax = base×rate; gross = net+tax |
| `price_basis=INCLUSIVE` | gross = base; net = base/(1+rate); tax = gross−net |
| `REVERSE_CHARGE` | both OUTPUT & INPUT sides created (§4.4) |

### 4.3 Rounding
- `rounding_mode`: `HALF_UP` (default), `HALF_DOWN`, `HALF_EVEN` via `roundTax()`.
- `rounding_level`: `LINE` (default) vs `DOCUMENT` — field exists but the engine always rounds
  line-level; document-level is a **gap** (see §12).

### 4.4 Reverse charge
`handleReverseCharge()` creates a matched OUTPUT + INPUT `tax_transactions` pair, both
`recognition_basis=INVOICE`/`recognized_at=now`, gl accounts `gl_output_acct` + `gl_input_acct`.

### 4.5 JE posting (`postTaxJournal`)
- OUTPUT (tax>0): DR tax_receivable, CR tax_payable (`txn.tax_amount`).
- INPUT (tax>0): DR tax_payable, CR tax_receivable.
- Memo `"{reference} — {taxCode->code}"`; entity_type/id + branch attached where supplied.
- Accounts resolve via `DefaultAccountMapping::getAccount(company, key)` with fallback code
  (`tax_receivable`→1150, `tax_payable`→2100 or the context's `tax_account_id`) — throws if none.
- Returns a `JournalEntry` via `JournalPostingEngine::post([...])`, `source_module='tax'`.

**E2E verification of post-tax balances**: test `full_vat_lifecycle` asserts `tax_amount`
equals `base × rate / (base + rate)` for INCLUSIVE treatment (1000 → 165.00; 500 → 82.50),
confirming the `Inclusive` math `tax = base − base/(1+rate)`.

---

## 5. GL Integration

- Tax posts ride **inside the caller's transaction** (BillService/InvoiceService call
  `calculateAndPostTax`/`postTaxJournal` within their own `DB::transaction`).
- Control accounts are configured via `DefaultAccountMapping` keys `tax_payable` (default code
  2300 in `defaultCodes()`, but the engine fallback is **2100**) and `tax_receivable` (1150).
- **Note the mismatch**: `DefaultAccountMapping::defaultCodes()` maps `tax_payable => '2300'`,
  but `TaxEngine::resolveAccount(..., 'tax_payable', '2100')` fallback is `2100`. If a company
  relies on the 2300 seeded account and has no explicit mapping, the engine will throw.
  **BUT** the E2E test bypasses this entirely by passing `tax_account_id` in the context — the
  explicit context account takes precedence (see §12).
- `reconciliation`, `controlAccount`, `workingPaperSummary`, and `glMovementForWindow` all
  reconcile against `journal_entry_lines` joined to `journal_entries` filtered to
  `STATUS_POSTED` + `STATUS_REVERSED`.
- Control-account drill uses `account->isDebitNormal()` for running balance direction.

---

## 6. Workflows

**Rates**: code → `TaxCodeRate` with unique `(tax_code_id, effective_from)`; overlap enforced by
`validateNoOverlappingRates()` and the dashboard exception scan (`windowsOverlap` using
`9999-12-31` as open end).

**Registration**: `TaxRegistrationService::checkRegistration()` → `register()` (overlap
validated by `validateNoOverlappingRegistrations`) → `deregister()` (inactive + effective_to today).

**Adjustment**: store (PENDING, audit) → approve (APPROVED, `approved_by`+`approved_at`, audit)
↔ reject (REJECTED, audit). Only PENDING can be acted on (422 otherwise). Counts into return
net via `approvedAdjustmentsNet(company, period)` = ADD − REDUCE. **E2E asserts 2 audit rows**
(create + approve) for a `TAX_ADJUSTMENT`.

**Return**: `generateReturn` (draft; 409 if a non-rejected return already exists for the
period/type) → submit/approve (service `approve` requires `submitted`; period → CLOSED + locked)
→ file (requires `approved`; sets `reference`). **Status literal BUT confirmed broken at UI**
(see §12.5).

**Payment**: record (status `confirmed`/`CONFIRMED`) → void (status `voided`). Reconciled by
`LOWER(status) IN ('confirmed','paid')`.

**WHT certificate**: `generate` from an INPUT posted WHT `tax_transaction` (400 otherwise;
source_id ↔ supplier), or `createFromForm` (gross + tax supplied, rate_pct stored as 0),
`generateBatch` per period, `revoke`. Number `WHT-%06d` by max id.

**Period close**: `closePeriod` OPEN→CLOSED only; return approve also closes+locked.

---

## 7. Inputs

- **Document lines** via `TaxEngine` context (from Invoicing / Purchasing / Expenses / Payroll /
  Bank txn callers — largely a future/partial integration, see §10).
- **Form POSTs**: adjustments, payments, certificate generate/revoke, return generate/approve/
  reject/file, period close. All start with `requirePermission` + `validated` input with
  explicit `abort_unless` company-scope guards (404/403/422).
- **Filters** (GET): audit entity_kind/from/to, pagination appends.

## 8. Outputs

- `tax_transactions` rows (the calc ledger), `TaxReturn`+`TaxReturnLine`, `TaxPayment`,
  `WhtCertificate`, `TaxAdjustment`, `TaxAuditTrail` rows.
- Journal entries in the GL for posted tax.
- Screens: dashboard KPIs + exceptions + chart, working paper, reconciliation, position, control
  account, period list, calendar, certificates, audit log, report directory, PAYE/WHT registers.
- Browser-table "Export" via `window.txExportTable(this, 'table-id')` (position/control-account/
  audit-trail/certificates) — client-side CSV, no server CSV endpoint.

## 9. Dependencies & Permissions

- **Feature flag**: the whole `taxation.*` route group sits inside `feature:tax` (route layer).
- **Permissions**: `config/permissions.php` module `taxation` = `view|create|edit|void|approve`.
  Group middleware `permission:taxation.view|taxation.edit`; individual handlers
  `requirePermission(request, 'taxation.view'|'taxation.edit'|'taxation.approve')`.
- **Role grants**: seeded via `RolePermissionSeeder` (company roles map to these actions).
- **Tenancy**: all 14+ models use `TenantScoped`; queries consistently filter `company_id`.
  Company scoping is manual (`where('company_id', …)` + `abort_unless`) rather than the global
  `TenantScoped` scope.
- **GL dependency**: `DefaultAccountMapping` (model) + `Account` + `JournalPostingEngine`.
- **Shared helpers**: base `Controller::requirePermission()`, `session('current_company_id')`,
  `SystemSetting::getValue('currency','currency_symbol',…)`.

## 10. UI / Layout

Views `resources/views/accounting/taxation/*.blade.php` (26 files): `dashboard`, `config`
(+ `_tabs` + `_tab-types/-rates/-codes/-exemptions/-jurisdictions/-accounts`), `codes`, `types`,
`rates`, `exemptions`, `jurisdictions`, `accounts`, `periods`, `working-paper`, `reconciliation`,
`certificates`, `reports`, `audit-trail`, `position`, `control-account`, `payments`, `paye-wht`,
`calendar`, `recognition-rules`.

**Dedicated `.tx-*` CSS suite** (NOT the global classes — the module has its own language):
- Page head: `.tx-page-head` (h1 + `.sub`), `.tx-opt-tag` (numbered breadcrumb tag).
- Buttons: `.tx-btn` variants `-cta` (teal), `-ghost`, `-sec`, `-sm`; `.tx-ibtn` icon button.
- Cards: `.tx-card`, `.tx-card-h` (icon `.ic` + h2), `.tx-pad`, `.tx-grid2`/`.tx-g3`.
- Tables: `.tx-li-wrap` (scroll wrapper) + `.tx-table` (`num` right-align, `.lbl` tfoot,
  `.tx-mono`, `.tx-em`, `.tx-name`, `.tx-row-act`, `.tx-jl` journal link).
- Status: `.tx-badge` variants `-ok/-pend/-post/-rev/-off` each w/ `.bdot` dot; tax-type chips
  `.tx-tchip` variants `-vat/-wht/-paye/-fbt/-cash`; `.tx-kpi` (+`.hero`, `.warn`, `.tx-neg`,
  `.tx-green`, `.tx-red`).
- Detail rows `.tx-dl-simple` (label/value); form fields `.tx-f .in`, `.tx-ddl`, `.tx-inp-sm`,
  errors `.tx-exc`; `.tx-note` info; `.tx-pag` pager; `.tx-chart`/`.tx-cg`/`.tx-cb`/`.tx-bar in/out`/
  `.tx-cl`/`.tx-legend`; KPI grid `.tx-chips`/`.tx-chipbox`; filter `.tx-tiles`/`.tx-tile`; empty
  states are inline `td`/`div` paragraphs.
- JS: `window.txExportTable(tableBtn, tableId)` — client-side CSV export used on
  position/control-account/audit-trail/certificates.

Conventions: `x-app-layout`, every page wrapped in `.max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap`;
`$cs` currency symbol passed to every view; many pages carry a `.tx-opt-tag` numbered header
("2 · PAYE & Withholding Registers", "4 · …", "11 · Tax Payments", "7 · Tax Reports").

---

## 11. Tests

- `tests/Feature/Accounting/TaxRouteRenderTest.php` — 2 tests: `test_all_18_tax_routes_render`
  (23 routes incl. 6 `config?tab=*` variants) + `test_working_paper_renders`. Fixtures seed
  VAT/WHT types, MWI jurisdiction, codes, versioned rates, and tax accounts 2300/1150.
- `tests/Feature/Accounting/TaxWorkflowTest.php` — 7 tests: adjustment approve/reject,
  cannot-approve-non-pending (422), period close + already-closed (422), payment void, and audit
  trail on approve + on close. Uses `status='CONFIRMED'` then asserts `voided`.
- `tests/Feature/Accounting/TaxEndToEndTest.php` — 2 tests:
  - `test_full_vat_lifecycle`: config → E2E registration (`register`/`checkRegistration`) →
    engine OUTPUT 1000→165 & INPUT 500→82.5 (INCLUSIVE) → return (draft, 5 lines, net 82.50) →
    manual `status='submitted'` → `approve` (period CLOSED) → `file` (ref MRA-2026-001) → payment
    confirm+void → WHT cert create/revoke → 15 routes 200.
  - `test_adjustment_lifecycle`: controller POST store → PENDING; approve → APPROVED; 2 audit rows.

---

## 12. Known Quirks / Bugs / Gaps (verified)

1. **Status literal drift (BROKEN at UI)** — migration default `tax_returns.status` =
   `DRAFT`/`APPROVED`/`FILED` (uppercase); services write lowercase `draft`/`submitted`/`approved`/
   `filed`/`rejected`. The controller `approveReturn` gate allows DRAFT|FILED while the service
   `approve` requires `submitted` (400). The UI working-paper "Approve & File" button is disabled;
   the controller's `generateReturn` creates a lowercase-`draft` row, so the allowed DRAFT gate
   never matches (`'DRAFT' !== 'draft'`). **Returns cannot be approved via the UI at all** —
   the E2E manually forces `status='submitted'` to side-step it. This is a real, confirmed defect.
2. **`tax_payable` fallback code mismatch** — engine fallback 2100 vs `defaultCodes()` 2300.
   Masked in E2E because the engine honours a context-supplied `tax_account_id`.
3. **`rounding_level = DOCUMENT` is unimplemented** — only LINE rounding is applied.
4. **Reverse-charge input-side `tax_amount` not reduced by apportionment** — `$calc['tax_amount']`
   is written raw on both sides in `handleReverseCharge()`.
5. **`TaxReturnService::approve()` mutates period to CLOSED+locked** without checking the period's
   current status first; `reject()` can act on `draft` too (UI intent unclear).
6. **`TaxPayment.status` literal set is messy** — migration `PENDING|PAID`; service writes
   `confirmed` (lowercase) and `voided`; the workflow test seeds `CONFIRMED` and asserts `voided`.
   The view's status map handles `paid|confirmed` (→Paid) and `pending` (→Payable) via
   `strtolower()`, so `PENDING` (uppercase from a fresh record) maps to `Payable` but a seeded
   upper-case `PAID` would also match `paid`. Status semantics are not a single constant source.
7. **`WhtCertificate` rate_pct = 0** when created from an ad-hoc form (`createFromForm` doesn't
   derive rate from gross/tax), so certs show 0.00% rate in `certificates.blade.php` and
   `paye-wht.blade.php` even though `wht_amount` is correct.
8. **`tax_transactions.posting_date` is phantom** — `paye-wht.blade.php` calls
   `$txn->posting_date?->format('d M Y')` and `payeWht()` orders by `posting_date`, but the column
   is **absent** from the migration and `$fillable` → always NULL (dates render `&mdash;`, ordering
   is NULL-collated). Confirmed defect.
9. **`UNPOSTED` status is never written** — engine always persists POSTED; no code path sets
   UNPOSTED (referenced only conceptually).
10. **No doc-level aggregation hook** in the engine — tax is per-line (`source_kind/source_id`);
    document totals rely on the caller summing lines.
11. **Auto-created periods** default `filing_due_date = end_of_month + 25` always; no tax-type
    specific due dates.
12. **Seeder is company-singleton** (`session('current_company_id') ?? 1`), not a per-company loop
    → multi-tenant provisioning must call it per tenant or duplicate.
13. **`abort_unless($period->company_id === $companyId, 403)` — 403** instead of 404 for
    cross-company access (inconsistent with the module's 404 elsewhere).
14. **Test fixture field mismatch (`is_active` vs `active`)** — `TaxCode::create(['is_active' => true])`
    in all 3 test files **silently drops** `is_active` (the fillable/migration column is `active`,
    default `true`). Tests pass only by luck of the DB default; a later `active=false` state change
    in fixtures would not behave as intended. Likewise the workflow/E2E fixtures omit the `active`
    field entirely and rely on the DB default.
15. **`payments` view posts `Recorded By` from `auth()->user()?->name` in a disabled input** — the
    actual stored value is the controller's `user_id` (session), so display is consistent only if
    the recording user is the viewer. Minor.
16. **`EisTerminal`/`EisSubmission` are NOT tax-module models** — EIS is a separate POS/invoice
    compliance module (migration `2026_11_01_000004_create_eis_tables.php`, service
    `app/Services/EIS/EisSubmissionService.php`, controllers `POS/EisController.php`). It appears
    only as ONE candidate in `TaxController::reports()` (`eis_submission_status`). Do not bundle it
    into a tax rebuild.
17. **A "Tax Transaction" line is missing from the return's 5-return-line count** — the E2E asserts
    `count($return->lines) === 5` (sections A,B,C,D + breakdown); the `drill_query` JSON is stored
    but **no drill-down view consumes it** (routes for drill-down don't exist).
18. **Reports directory is hand-maintained** — `reports()` returns a static candidate array wired
    through `Route::has()` + `FeatureManagement::isEnabled()`; NOT `ReportRegistry`-driven. The
    `reports.blade.php` icon map is a 20-entry hardcoded `$reportMap`.

---

## 13. Suggested Rebuild Scope (recommended, not yet applied)

1. **Unify return status constants + fix the approve gate** (highest priority — the flow is
   currently unusable via UI): single source of truth + migration to reconcile lowercase vs
   uppercase values; rework `approveReturn` to transition from `draft`/`submitted`.
2. Honour `rounding_level` — add DOCUMENT-level accumulation.
3. Fix/harden `tax_payable` account resolution (prefer explicit mapping; document the precedence:
   context `tax_account_id` → mapping → fallback).
4. Remove the phantom `posting_date` ordering (add the column or order by `created_at`), and stop
   rendering it on `paye-wht`.
5. Drive the reports directory from `ReportRegistry` instead of a static array.
6. Make the seeder idempotent per-company and loop tenants during provisioning.
7. Unify cross-company guard status (404) module-wide.
8. Derive WHT cert `rate_pct` from gross/tax (or link to the code's active rate).
9. Add drill-down consumers for `tax_return_lines.drill_query`, or drop the field.
10. Consider a `PayeTable`/`PayeTableBand` and `TaxCode` link so WHT rates and PAYE bands share a
    single calculation path (currently `calculatePaye()` is a standalone band engine not wired to
    `TaxCodeRate`).
11. Fix test fixtures to use the real `active` flag instead of the silent-dropped `is_active`.