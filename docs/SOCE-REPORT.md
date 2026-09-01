# Statement of Changes in Equity — Rebuild (APPENDIX A)

## 1. Objective

Rebuild the Statement of Changes in Equity (SOCE) page per the APPENDIX A spec:
a clean on-screen statement, a branded PDF export, settings-driven currency/company
data (never hard-coded), server-computed preset ranges, a zero-balance toggle that
applies at CSV/PDF generation, a CSV with a "Code" column and currency-qualified
headers, audit logging, and branch scoping (§4).

Routes (`routes/web.php:674–676`) and the accounting-method inheritance decisions
from earlier phases were untouched.

## 2. HTML structure (single view)

`resources/views/accounting/equity-statement/index.blade.php` renders one view
that doubles as screen + print source:

- `.fr-wrap` → `.fr-head.soc-phead` — page title + subtitle line
  "For the period {from} – {to} · Currency ({cs}) · Basis" plus a gold
  "Ties to the General Ledger" `.tie` banner when the tie-out check passes.
- `.fr-filters.soc-filters` — preset chips (This Month / This Quarter / This
  Year To Date / Last Year / Custom) carrying `branch_id` and `zero` through a
  hidden input, From/To date inputs, a client-mode `x-scoped-search-field`
  branch picker, and Generate/Clear controls.
- `.soc-sheet` — print-only brand chrome (`.soc-doc-h` logo + company +
  `.soc-c-title/.soc-c-period`), `.soc-meta` four-cell statement header
  (Company / Period / Currency / Basis), the `.soc-tools` action row, the
  statement table, and `.soc-co-foot` with the page-number script block.
- `.soc-table` — five columns: Code / Account / Opening / Movement / Closing
  (currency symbol in the column headers only), parent-style rows (Opening
  Balance, Changes in Equity, Retained Earnings) grouped by classification,
  `.soc-subtotal` Net Income for the Period row, `.soc-total` Total Equity row,
  `.soc-zero` rows (accounts with zero balance) hidden unless the zero toggle is on.
- `.soc-hide-zero` class on the table toggles zero rows.

## 3. Visibility matrix (screen vs print vs PDF)

Implemented in the view + app.css `@media print`:

| Element            | Screen | Print | PDF (print-export shell) |
| ------------------ | ------ | ----- | ------------------------ |
| `.soc-doc-h` logo  | hidden | shown | —                        |
| `.soc-meta` header | hidden | shown | `fs-meta` (shell)        |
| `.soc-co-foot`     | hidden | shown | `fs-foot` (shell)        |
| `.soc-phead`       | shown  | hidden | —                        |
| `.soc-filters`     | shown  | hidden | —                        |
| `.soc-tools`       | shown  | hidden | —                        |

PDF export renders `print-export.blade.php` with `$meta['rows']` populated from
the same service; the pdf-attribution and brand fields come from
`StatementPdfMeta::statementPdfMeta()`.

## 4. Controller

`EquityStatementController` (rewritten, 249-line diff):

- `resolveContext()` — builds the account list, resolves the company/branch
  context, computes the statement via `EquityStatementService`, computes the
  closing/opening totals, applies the zero-balance filter, computes the
  period label and the "ties out" check, and returns the full context array.
- Presets are computed from the accounting periods (start/end of the fiscal
  year), not hard-coded dates:
  - This Month — first/last day of the current month
  - This Quarter — quarter boundaries of the current month
  - YTD — year start to today
  - Last Year — previous year start/end
  - Custom — accepts `date_from`/`date_to`
  - Default when no dates are supplied: **This Year To Date**, with an empty
    dateFrom/dateTo falling back to the YTD preset (`filled()` tweak).
- Preset chips are page links carrying `branch_id` and `zero` through the query
  string so a scoped branch survives a preset change.
- `exportCsv()` / `exportPdf()` reuse `resolveContext()`'s filter application
  so CSV/PDF always agree with the on-screen statement; `formatCsv()` adds a
  "Code" column and a header cell showing the currency code.
- Branch scope (§4): only the user's assigned branches (via
  UserCompanyAssignment `branch_ids`) are offered in the picker; requesting an
  out-of-scope branch forces the branch back to null. This is a policy
  boundary, not just UI.

## 5. Branch scope (§4)

`SearchCatalog::branch()` is not user-branch-scoped, so the picker is built in
**client mode**: `x-scoped-search-field` with `mode="client"`, `:items` fed from
`$branches->map(...)->values()` (id/label/subtitle), and a hidden
`name="branch_id"` input carrying the selected id.

## 6. CS/Settings parity

Currency symbol and format are sourced from SystemSettings
(`currency_symbol` default `$`, `decimal_places` default `2`) with the currency
code falling back to `Company::base_currency` (or `MWK`). Company name, address,
and registered number come from the company model — nothing is hard-coded.

## 7. Testing

`tests/Feature/Accounting/EquityStatementTest.php` — 10 tests, 60 assertions:

1. Screen chrome renders without the brand identity.
2. Visibility-matrix CSS presence (`.soc-doc-h`/`.soc-meta`/`.soc-co-foot`
   display rules + `.soc-phead`/`.soc-filters`/`.soc-tools` print-hide).
3. Presets default to YTD; empty date fields fall back to YTD.
4. Tie chip visible when net income is zero.
5. Tie chip hidden when net income is non-zero.
6. CSV uses settings currency and includes the Code column.
7. CSV zero filter drops zero-balance accounts.
8. PDF export renders the branded shell with fs-* chrome.
9. Index logs a report-audit entry (VIEW action).
10. Branch scope limits the picker to the scoped branch.

Sibling regressions green: FinancialStatementsTest, ReportAuditScheduleTest
(26). CSV output notes: fputcsv quotes any field containing a space, and
amounts are plain (no thousands separators) — assertion needles must match that
form.

## 8. Also committed in this push

`CashFlowController::exportPdf`/`exportCsv` — allow the cash-flow export while
still emitting a warning (PDF) / WARNING rows (CSV) for mid-year periods,
mirroring the balance-sheet guard policy. (Balance-sheet's "not balanced"
export guard is intentionally untouched.)