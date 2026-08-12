# Cash Position — DESIGN 2 (`.cp2`) Delivery Report

Aug 12, 2026 — Full replacement of the phase-52 `.cp-*` Cash Position page with the teal executive Target Design (`.cp2` suite). Presentation/markup only; all routes, controller math, posting, reconciliation, ledger, and export semantics are unchanged from phase 52.

## 1. Files removed / added / changed

**Removed**
- `resources/css/app.css` lines 8518–8950: the entire phase-52 `.cp-*` block (`.cp-suite`, `.cp-head`, `.cp-hero`, `.cp-chips`, `.cp-pills`, `.cp-stat*`, `.cp-badge*`, `.cp-shell`, `.cp-rail`, `.cp-bar*`, `.cp-cat*`, `.cp-more*`). Verified zero `.cp-*` selectors remain in the source and the compiled bundle.
- `app/Services/FavouritesService.php`: the `'accounting.cash-position.index'` entry in `PAGES` (rails opt-out, see §5).

**Added**
- `resources/css/app.css`: `:root` tokens `--amber: #D97706` and `--shadow-cta` (aliases of existing `--warn` / `--cta-btn-shadow`); new `.cp2` block appended at the end of the file (~lines 8520–8940). Everything is namespaced under `.cp2` (verified no bare global `.btn/.pill/.card/.wrap/.bar/.f/.seg` rules existed, so scoping is required). Source kept at `C:\Users\eseyama\AppData\Local\Temp\opencode\cp2-block.css`.
- `resources/views/accounting/cash-position/print.blade.php` — rewritten (was part of phase 52; now matches the new row schema).
- Probe script `C:\Users\eseyama\AppData\Local\Temp\opencode\cb-probe\cp2-probe.js` + `cp2-probe-result.txt`.

**Changed**
- `resources/views/accounting/cash-position/index.blade.php` — rewritten to the §4 structure (see §3/§4).
- `tests/Feature/Accounting/CashPositionTest.php` — rewritten to the new markup (8 tests / 81 assertions).

**Untouched** (explicitly out of scope): `app/Http/Controllers/Accounting/CashPositionController.php` logic (data model from phase 52), `routes/web.php`, `app/Services/Accounting/BankService.php`, `BankTransaction`/`Account`/`JournalEntryLine` models, PDF pipeline.

## 2. Drill-mapping table (link → route + params)

| UI element | Route | Params |
|---|---|---|
| Cluster · Reconcile | `accounting.bank-reconciliation.index` | first active bank account id (fallback `bank-accounts.index`) |
| Cluster · Transfer | `accounting.bank-accounts.transfer-form` | — |
| Cluster · New Cash Transaction | `accounting.bank-accounts.manual-form` | first active bank account id (fallback `bank-accounts.index`) |
| More · Record Receipt | `accounting.sales-receipts.create` | — |
| More · Record Payment | `accounting.expenses.create` | — |
| More · Refresh / Export Excel / Export PDF / Print | `cash-position.{index,export-csv,export-pdf,print}` | `$exportParams = request()->except('page')` |
| Pill · Cash Receipts | `accounting.sales-receipts.index` | `$exportParams` |
| Pill · Cash Payments | `accounting.expenses.index` | `$exportParams` |
| Pill · Bank Accounts | `accounting.bank-accounts.index` | `$exportParams` |
| Pill · Cash Accounts | `accounting.petty-cash.index` | `$exportParams` |
| Pill · Transfers | `accounting.bank-accounts.transfer-form` | — |
| Pill · Bank Reconciliation | `accounting.bank-reconciliation.index` | first active bank account id |
| Pill · Cash Forecast | `analytics.cash-flow-trend` | — |
| Pill · Cash Flow Statement | `accounting.cash-flow.index` | — |
| Pill · General Ledger | `accounting.general-ledger.index` | `$ledgerParams` (exportParams + date_from/date_to) |
| Hero · + Receipts "View receipts →" | `accounting.sales-receipts.index` | `$exportParams` |
| Hero · − Payments "View payments →" | `accounting.expenses.index` | `$exportParams` |
| Chip · "View unreconciled →" | `accounting.bank-reconciliation.index` | first active bank account id |
| Account table name + "View ledger →" | `accounting.general-ledger.account` | `[accountId]` + `$ledgerParams` |
| Recent · description | `accounting.journal-entries.show` | `[journal_entry_id]` (when set) |
| Recent · account | `accounting.general-ledger.account` | `[bank_account_id]` + `$ledgerParams` |
| Recent · View | `$txn->source_url` | JE show for `journal` source type |
| Recent · "View all →" | `accounting.general-ledger.index` | `$ledgerParams` |

## 3. Newly created minimal pages for missing destinations

None required. Every pill, cluster action, and drill maps to an existing route; no Cash Position destinations needed placeholder pages.

## 4. Data-source queries per section

All queries live in `CashPositionController` (phase 52 data model, untouched this phase):

- **Account list / filter dropdown** — `cashBankAccounts()`: `Account` where `company_id` + `is_active` + (`is_bank_account` OR `is_petty_cash`) + optional `account_id` / `currency` / `q` (name/code LIKE). The filter-form dropdown uses an unfiltered variant (all active bank/cash accounts) so it can restrict by a specific account.
- **Hero / account table / totals** — `lineSums()` (grouped, one query): `JournalEntryLine` JOIN `journal_entries` where `company_id` + `account_id IN` + status (`posted`+`reversed` default) + date window + optional branch/cost-centre/source_module/q; groups by account → `SUM(debit)/SUM(credit)/COUNT`. `movementPerAccount()`: opening = pre-window lines + `opening_balance` (sign via `isDebitNormal()`); receipts/payments = period debit/credit minus transfer legs; transfers in/out from `source_type = 'transfer'` rows; closing = opening + receipts − payments + transfers in − out; per-bank `reconciled` via `BankService::getReconciledBalance()`.
- **Chips** — `$chips`: bank = Σ closing of bank accounts, cash = Σ closing of petty-cash accounts, unreconciled = Σ `BankTransaction.amount` where `is_reconciled = 0`.
- **Cash Movement bars** — `movementBars()`: top 5 accounts by (receipts + payments), `in` = receipts / `out` = payments, widths = value ÷ column max (0.01 denominator guard), rendered as relative `.fill` bars (in = teal, out = amber→red).
- **Recent Cash Transactions** — `recentTransactions()`: `BankTransaction` JOIN `accounts` + `journal_entries` where company + account ids + window (+ advanced filters), last 5; per-txn debit/credit split by amount sign; `source_label` from `transactionTypeLabel()`; `source_url` from `sourceDocFor()`.

## 5. Rails confirmation

The phase-52 open question is resolved: the rails opt-out hook is `FavouritesService::metaForRoute($routeName)` at `resources/views/layouts/app.blade.php:44`. It returns null for `accounting.cash-position.index` once the PAGES entry is removed (and there is no `metaForRecord` match), which means:
- `x-favourites.sidebar` renders nothing (no pinned-rail, regardless of the user's pinned/collapsed preferences), and
- the `fav-float-toggle` star is skipped.

The page therefore carries ZERO rails/star markup under every preference combination. `FavouritesTest` (13 tests / 178 assertions) stays green — its PAGES-driven render loop does not depend on cash-position.

## 6. Verification

- `php artisan view:cache` — clean (all views compile).
- `npm run build` → `public/build/assets/app-CsyyKq70.css` (137 `.cp2` occurrences; zero `.cp-suite/.cp-wrap/.cp-hero/.cp-btn` in the bundle).
- `php artisan test tests/Feature/Accounting/CashPositionTest.php` — **8 passed / 81 assertions**.
- `php artisan test tests/Feature/Accounting/FavouritesTest.php` — **13 passed / 178 assertions**.
- Headless Chrome (`cp2-probe.js`, viewports 1440/1280/1024/768/375): all §4 sections render with live data (hero net `+ K0.00 this period`, chips `K4,000.00 Bank Balance`, 3 account rows + TOTAL tfoot, 4 bar rows, 7-column recent table); flow grid 7-col ≥1024px → 2-col + arrows hidden + Closing full-width ≤1000px; advpanel 4→2→1-col; `.seg` overflow at 375px fixed via wrap+shrink; **page-level h-scroll false at every viewport** (remaining wide elements are only the pre-existing topbar row-2 nav overflow #41/#42 and the internally-scrolling `.tbl-wrap` table).
