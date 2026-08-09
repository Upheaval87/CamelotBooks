# Quotations Suite — Executive Teal (Aug 8, 2026)

Self-contained restyle of the entire quotation lifecycle — list, create, edit, show/detail, and the A4 print/PDF template — plus two new sales reports (Quotation Register, Sales Pipeline) and draft-deletion support. No routes, permissions, validation, search, send/accept/decline/convert/void, or PDF-pipeline behavior was changed except where a concrete bug or a missing feature was required by the mockup (enumerated below). Every `quot-*`/`bill-*` id, name, data-attribute and JS hook is preserved.

Scope: 12 files, +2246 / −419 (controller, service, routes, ReportRegistry, `app.css` +1159, `index`/`_form`/`show`/`print` blades, 2 report controllers, 2 report services, 2 report views, 2 test files, `server.php`).

## Pages

### Index (`index.blade.php`)
- Non-sticky `.q-head--list` head: H1 (`text-2xl font-extrabold tracking-[-0.02em] text-gray-900`) + subtitle, right-aligned Export (ghost) + "＋ Create Quotation" (teal cta) buttons.
- Inline `.scoped-search-field` (Customer / number / reference, 350ms debounce into `#quot-list-form`) + sort `select` (Newest/Oldest first, Total high→low / low→high, Status).
- `.q-fbox-grid` — 7 clickable status filter boxes (Total / Draft / Sent / Accepted / Declined / Converted / Void), each a 24px icon tile + label + count, `.on` = teal-active, `@if($activeStatus === $box['key'])`. Status links pass `status`; "Open" treated via `status=open` (draft+sent) in the controller.
- `.q-shell` main + `.q-shell--rail`: list `q-card q-card--list` with `.q-table` (mono nº link, customer name, date, valid-until, right-aligned `q-amt` totals, `.q-badge` status pills, icon-only `q-ibtn` actions: View / Edit+Send (draft) / Void with `fbConfirmButton(..., {type:'danger'})`), `.q-pag` pager (info line + prev/next + numbered `.is-current`/`.is-disabled`). Empty state `q-empty`.
- Rail `.q-rail-sec` × 2: **Views** (All Quotations, Open (Draft + Sent), Accepted, Converted — `.is-active` when matched) and **Reports** (Quotation Register, Sales Pipeline).
- `#quot-list-form` hidden inputs keep `status`/`search`/`sort` so filter + sort + pagination compose cleanly via `appends(request()->query())`.

### Create / Edit (`_form.blade.php`, shared)
- Sticky `.q-head.q-head--sticky` (`--nav-h` top, z-40): `.q-badge-title` (H1 + `.q-chip` mono quotation nº on edit + `.q-badge` status). Sub: edit = "{Customer} · quoted {date} · valid until {date}", create = "Quote products & services with a live summary."
- Actions: create = Cancel · Save & New (`action=save_and_new`) · seg [Save Draft (`save_draft`), Save (`save`), **Submit for Approval** (`submit_for_approval`, teal cta)]; edit = Cancel · **Delete** (danger-o, non-creators only — see §Bugs) · seg [Save Changes (`save`), **Save & Email** (`save_and_email`, teal cta)].
- Form: `id="quotation-form"`, `.q-form`, `enctype="multipart/form-data"`, `data-customer-name`, `@csrf` + `@method('PUT')` on edit, `novalidate`; head buttons use `form="quotation-form"`.
- `.q-shell` 2-col grid (`minmax(0,1fr) 21.25rem` → 18.75rem rail on the form):
  - **(a) Customer Information** — `customer_id` `x-scoped-search-field` sp2 (`on-select="quotCustomerSelected"`), Valid Until, Currency (central `Currency::active()->ordered()`, MWK-first when present), readonly Contact Person / Email / Phone / Payment Terms (`#quot-contact/#quot-email/#quot-phone/#quot-terms`), `.q-field-note`.
  - **(b) Quotation Information** — readonly № ("Auto-assigned on save"), Date, Reference №, Branch select, Cost Centre scoped search, readonly Department / Prepared By / Project (display-only), `.q-field-note`.
  - **(c) Line Items** — `#quot-add-line` ghost-sm; `#quot-lines-table` `.q-table` in `.q-wrap.round-thead-clip` (sticky mist thead, `q-col-c/n/d/q/p/g/a/x`), `#quot-lines-body`. Rows built by `quotLineRow()` using the runtime `scopedSearchFieldHtml({name, entity:'product', searchUrl, value, label, placeholder})` helper; hidden `lines[i][tax_rate]` (`.quot-tax-rate`), `lines[i][discount]` (`.quot-flat-discount`), `lines[i][income_account_id]` (`.quot-income-account`); qty/price/disc% + Amount (`bill-line-total q-amt`); duplicate ⧉ / delete 🗑 `q-ibtn`. `item-selected` listener on the body autofills SKU / description / sales price / tax rate / income account. Never leaves the table empty.
  - **(d) Notes** — Customer Notes (`memo`, printed on PDF) + Internal Notes (display-only).
  - **(e) Attachments** — `#quot-existing-files` (existing, with per-file `quotRemoveExistingFile` → `delete_documents[]`), `.q-dropzone#quot-dropzone` (drag/drop + click, `.q-drop-chips` PDF/IMG/XLSX/DOCX, drag state `.bill-drop--drag`), hidden `#quot-files` (`name="files[]"`, `accept` = allowed mimes), `#quot-new-files` `.bill-file-list` with sizes via `quotFmtSize()`.
- Rail `.q-rail-sec`: **Summary** (create) / **Breakdown** (edit) — `.q-srow-lite` rows `#p-cust/#p-contact/#p-date/#p-valid`, Subtotal `#v-sub`, hidden Discount `#r-disc/#v-disc`, hidden Tax `#r-tax/#v-tax`, `.q-strip` Grand Total `#v-gt`, live preview lines `#p-lines` (`.quot-p-line`), memo footnote `#p-foot`. **Quick Nav** — edit: Print / PDF, Email (POST `quotations.email`, only when the customer has an address), Attach File (`#attachments`), Back; create: Quotations List, New Customer (`accounting.customers.create`), Day Book.
- Delete: `#quotation-delete-form` (sibling form after the main one; `@method('DELETE')`, `fbConfirmSubmit(..., {type:'danger'})`), rendered only when `isEdit && created_by !== auth()->id()` — see §Bugs.
- JS: `PRODUCT_SEARCH_URL`, `QUOT_CS`, `QUOT_DEFAULT_INCOME_ACCOUNT_ID`, `QUOT_LINES` seed; `fmt/parse/esc/fmtDate`; `quotLineRow/quotRowData/quotAddLine/quotRemoveLine/quotDuplicateRow/quotUpdateTotals/quotSync/quotCustomerSelected/quotRenderNewFiles/quotFmtSize/quotAddFiles/quotRemoveNewFile/quotRemoveExistingFile/quotWireDropzone/quotSyncFileInput`; `quotUpdateTotals` recomputes on submit too.

### Show (`show.blade.php`)
- Sticky `.q-head.q-head--sticky` + `.q-badge-title` (title + `.q-badge`) + `.q-toolbar` grouped actions: Edit (draft), Email to Customer (draft + has address), Mark as Sent (draft, `@can('quotations.send')`), Convert to Invoice (`sent`/`accepted`, `@can('quotations.convert')`), Void (`fbPromptForm` reason, `@can('quotations.void')`), Print, Back.
- `.q-view` shell (`minmax(0,1fr) 21.25rem`): detail `.q-field` grid (nº, customer, date, valid-until, reference, created-by, memo); **Review & Decide** card (`sent`) with decline/accept `x-review.btn` forms; `x-review.outcome` strips for accepted / declined / converted; line-items `q-card q-card--list` + `.q-totals-box` (Subtotal / Tax / Total with `format_number`, `$cs` in column headers); attachments card with public-disk links + `format_bytes()`.
- Right `.q-view--rail` `.q-rail-card`: Print / PDF, Edit Quotation (draft), Back to Quotations, divider, Quotation Status Report link.

### Print / PDF (`print.blade.php`)
- Self-contained §10 A4 stationery (not the PdfController pipeline — served by `QuotationController@print` on `quotations.print`): `@page { size: A4 portrait; margin: 0 }`, `.canvas` white sheet, `#F0F5F5` header band with CB logo block + "QUOTATION" + number, Prepared For box + meta grid, ledger table, Subtotal/Tax/Grand Total, notes + terms, two signature blocks, teal footer. Tokens `--acc: #0E7473` (PDF accent, distinct from the `#128F8E` app accent). `print-color-adjust: exact`, `onload="window.print()"`. Replaced the legacy Arial inline-CSS page; the broken `\App\Helpers\MoneyHelper::toWords` call was dropped (helper does not exist).

## Reports

Two new sales reports wired into `ReportRegistry` (`quotation_register`, `sales_pipeline`) + routes `accounting.reports.quotation-register` / `accounting.reports.sales-pipeline`:

- **Quotation Register** (`QuotationRegisterService` + `QuotationRegisterController` + `accounting/reports/quotation-register.blade.php`) — every quotation in a date range with customer, valid-until, total, status pill, and a running grand total.
- **Sales Pipeline** (`SalesPipelineService` + `SalesPipelineController` + `accounting/reports/sales-pipeline.blade.php`) — per-status funnel cards (Draft/Sent/Accepted/Converted/Declined/Void with count, value, % of total), win-rate (accepted+converted ÷ decided), open-quote aging buckets (0–7 / 8–30 / 31–60 / 61+ days with relative bar widths), open count + value.

## Controller / service / routes

- `QuotationController::index` — now computes `$stats` (per-status count + `SUM(total)` via one grouped query) + `$statsTotal` for the fboxes, honours `status=open` (draft+sent), customer, and search, and a `sort` param through `orderByFor()`.
- `QuotationController::export` (new route `quotations.export`) — CSV of the exact filtered list (quotation #, customer, date, valid-until, total, status), same query as the index, `streamDownload`.
- `QuotationController::destroy` (new route, `middleware(['permission:quotations.edit', 'sod:quotation'])`) — `requirePermission('quotations.edit')`, cross-company 403, draft-only (non-draft redirects to show with an error), deletes attachment disk files + rows, then `QuotationService::destroy`.
- `QuotationController::store/update` — both handle `action=save_and_email` via `redirectAfterEmail()` (distinguishes "saved but no customer email" warning from a successful send); `handlePostSaveAction()` handles `submit_for_approval` → `QuotationService::send()`.
- `QuotationService::destroy(Quotation)` — DB transaction, deletes line items then the quotation; rejects non-draft with `InvalidArgumentException`.

## Bugs fixed (found during verification)

1. **`orderBy(...$this->orderByFor($sort))` named-parameter crash (pre-existing, broke `index` + `export`)** — spreading the associative sort array `['quotation_date' => 'desc', 'id' => 'desc']` invokes `orderBy(quotation_date: 'desc', id: 'desc')`, but `orderBy`'s first param is `$column` → "Unknown named parameter $quotation_date" on every `quotations.index` load (default sort). Caught by `ScopedSearchRenderSmokeTest`. Fixed with a `foreach ($this->orderByFor($sort) as $column => $direction) { $q->orderBy($column, $direction); }` loop at both call sites.
2. **Delete button 403 for record creators (SOD)** — `quotations.destroy` is gated by `sod:quotation`, which aborts 403 when the acting user is the record's `created_by`. The redesign's Delete button (edit head) was shown unconditionally, so a creator clicking it hit a 403. Gated the button **and** the `#quotation-delete-form` to `$quotation->created_by !== auth()->id()` so only users who can actually pass SOD see it; tests create via a second user to exercise the allowed path.

## Tests

- `QuotationFormTest` (14 tests / 69 assertions, all green) — create/edit render the mockup markup (create asserts "Summary", edit asserts "Breakdown" — the former "Live Document Preview" card title is gone per §8.2), store persists fields + exact flat-discount/tax math, `submit_for_approval` → `sent`, `save_and_new` redirect, missing income account validation, attachment upload/delete/oversize, update persists currency + recalculates, non-draft update rejected, show attachments card, **new**: `destroy` deletes a draft (second user as creator), `destroy` rejects non-draft with an error flash.
- `ScopedSearchRenderSmokeTest` — now covers `quotations.index/create/edit/show/print` (edit uses a **draft** fixture because `edit()` redirects non-drafts; show uses a sent fixture).
- Regression green: `SalesModuleTest` (quotation create/lifecycle/convert/recalc), `ListPageRenderTest`, full `view:cache`.

## Verification notes

- `view:cache` compiles all ~330 views; `php -l` clean on the controller and compiled `_form`.
- Compiled CSS contains all `.q-*` tokens; `.q-form table .bill-ci` and `.q-form table .scoped-search-field .scoped-search-open` combos are defined so inline item search and preview-mode line inputs render correctly inside the form.
- Headless verification (1440/1280/1024/768/375): create page grid `980px 340px`, sticky head at 106px z-40, live-preview ids, dropzone; index shows fboxes/filters/table/badges/create. Live show-page E2E remains blocked by the pre-existing tenancy route-binding bug (Phase 41 note: implicit bindings resolve on the central connection, so tenant-only records 404 on `show`/`edit`) — covered by the smoke test instead.
