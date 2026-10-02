# Journals Register, Journal Modal, Reversal Chain & Print Voucher — Implementation Report

Implementation of `docs/JOURNALS-REGISTER+JOURNAL-MODAL+REVERSAL-CHAIN-PRINT-VOUCHER.txt`
(Appendix A, 806 lines, self-contained and authoritative — `docs/journal-register.html`
was **not** used as a design source).

**Branch:** `feature/accounting-method-inheritance`
**Guard:** no posting/calculation/permission logic, request payloads, database
behaviour or schemas changed. No new packages, no new routes, no
`backdrop-filter`. Frontend markup/CSS + wiring to endpoints that already
existed.

The only endpoints involved were added earlier in this task because §0.3/§5.1
require them: `GET journal-entries/{journalEntry}/print` (`printVoucher`) and
`GET journal-entries/{journalEntry}/print/pdf` (`downloadVoucherPdf`), both gated
by `permission:journal-entries.view`. Nothing else in `routes/web.php` changed.

---

## 1. Architecture

Per §2.7 / §7.2 the three surfaces are **server-rendered**; JavaScript only
progressive-enhances (open/close, edit recalculation, confirm orchestration,
printing). No client code builds rows, modals or vouchers from arrays.

| Surface | File | Rendering |
|---|---|---|
| Register (rows, tabs, filters) | `resources/views/accounting/journal-entries/index.blade.php` | Blade loop over `$journalEntries` / `$payload` |
| Journal + lifecycle modals | `.../journal-entries/_jr-modals.blade.php` | One `#jr-journal-{id}` per entry, plus one shared delete / reopen / confirm modal |
| In-page voucher previews (§5.1) | `.../journal-entries/_jr-print-overlays.blade.php` (new) | One `.jr-print` A4 sheet per printable row, including `_voucher-sheet.blade.php` |
| Print / PDF | `print.blade.php`, `pdf.blade.php`, `_voucher-sheet.blade.php` | Standalone page + DomPDF parity (from the earlier voucher phase) |
| Enhancement only | `resources/js/journal-register.js` (rewritten) | Alpine `journalRegister(config)` |

### Server-side model
- `JournalEntryController::index()` builds `$payload` (one entry object per row
  including linked reversals), `$vouchers` (payload per **posted/reversed** row),
  `$can` (`finalize` / `post` / `reverse` / `delete`), `currencyCode`, `decimals`,
  `cs`, `$dateError` (From ≤ To), `defaultAccount`, `preserved`.
- `voucherContext($companyId)` resolves currency + decimals from **system
  settings once** so per-row rendering issues no extra setting queries;
  `voucherPayloadFor(JournalEntry, array $ctx)` produces the `_voucher-sheet`
  data (`amountWords` via `amount_to_words(..., $minorUnit)`).
- R6: currency code, symbol, decimals and unit labels all come from settings —
  nothing hardcodes `K` / `$` / `MWK`.

## 2. Changes in this pass

### Register (`index.blade.php`)
- Server-rendered rows with the reference Table (journal №, date, type,
  description, source, lines, total, status pill, actions).
- Status tabs (All / Unfinalized / Unposted · Finalized / Posted / Reversed)
  with server counts; period + type + branch + search filters; From ≤ To guard.
- **Actions column is view + print only (§2.5 / R5).** Every lifecycle action
  (`Check & Edit`, `Delete`, `Reopen`, `Reverse`, `Post to ledger`, `Finalize`)
  lives in the modal footer only; reversed rows are open-only (no reverse icon,
  no print icon).
- Empty-state copy is exactly `No journals match the current filters.`
- Voucher stylesheet link is emitted inline (see §5 gotcha).

### Reversal modal (`_jr-modals.blade.php`)
- Server-rendered mirrored-line preview (every debit reappears as a credit and
  vice-versa) rendered from the payload lines.
- `Reference` is a readonly monospace "New reference"; the reason is a required
  memo field; the note is an amber warning panel.
- Delete / reopen / confirm are **shared** single modals; the delete and reopen
  forms bind their action directly (`:action="deleteId !== null && get(deleteId)
  ? get(deleteId).urls.destroy : '#'"`) so they always POST to the received row's
  endpoint (an earlier `x-effect` setAttribute approach was removed).

### In-page print overlay (`_jr-print-overlays.blade.php`, new)
- §5.1: fixing the row's Print action opens a `z-200` overlay whose body is the
  shared A4 `_voucher-sheet`. Toolbar: **Open standalone**, **Print**
  (`window.print()`), **Close**. Backdrop click closes; Esc closes the top-most
  layer only.
- `@media print` in `app.css` isolates `.jr-print.on` so only the voucher prints.

### JS (`journal-register.js`, rewritten)
`init, get, currentEntry, isMine, totalDr/totalCr/balanced, fmt, cloneLines,
open, closeJournal, startEdit, cancelEdit, openDelete/closeDelete,
openReopen/closeReopen, openReverse/closeReverse, openPrint/closePrint, doPrint,
askConfirm, confirmYes, askFinalize, askPost, askReverse, setMode, syncBounds,
validateDates, toast, closeTop`. `closeTop()` order:
confirm → print → delete → reopen → reverse → journal.

### CSS (`app.css`)
- Replaced the old `#jr-journal` print block with `.jr-print.on` isolation rules.
- Appended the Appendix A deltas: z-map overrides (`.jr-wrap .jr-modal` 90,
  `.jr-modal--rev` 120, `.jr-modal.jr-cf` 140, `.jr-print` 200), deep-teal modal
  header band, empty/fail states, creator chip, print overlay + toolbar, and a
  ≤900px responsive fallback. No global rule was touched.

### `_voucher-sheet.blade.php`
Footer third cell is now the literal `PAGE 1 OF 1` (single-A4-page rationale;
number and type already appear in the head).

### Controller / helper
- `voucherContext()`, `voucherPayloadFor()`, `minorUnitLabel()`,
  `voucherStatusLabel()`, `$vouchers`, `$can`, `$dateError` added to `index()`;
  fixed a `$companyId` source bug.
- `app/helpers.php`: `amount_to_words(..., ?string $minorUnit = null)` and a
  generic `_minor_unit_label(int $decimals = 2)`.

## 3. Spec conflicts — how they were resolved

1. **§5.1 in-page preview overlay — IMPLEMENTED** (z-200, own toolbar). The
   standalone `/print` page is retained as the "Open standalone" escape hatch.
2. **§5.1 Email voucher — OMITTED.** No journal-voucher email
   route/Mailable exists and R6's guard forbids adding one; a `mailto:` ghost
   action is offered instead. Blocked and documented, not silently invented.
3. **`Memo` vs `Description` labels** — `Description` is used everywhere (R4 is
   explicit and overrides the abbreviated Appendix markup).
4. **Reversed register rows** — View only (Appendix row markup is authoritative
   over the mockup thumbnail).
5. **Creator metadata chip** — kept even though the abbreviated mockup omits it
   (required field).
6. **No `Open reversal` button** — a reversed entry's offsetting reference is
   plain monospace text in the footer note.

## 4. Verification

**Automated (branch suites, all green)**
```
JournalRegisterTest         19 passed
JournalReversalTest         18 passed
JournalVoucherPrintTest      7 passed
PostingEngineTest            6 passed
SalesModuleTest              7 passed
ScopedSearchRenderSmokeTest  3 passed  (covers journal-entries index/create/
                                        edit/show posted + pending)
```
`php artisan view:cache` clean; `npm run build` produced
`app--WV4lmq5.css` (929.31 kB), `app-DGE39jX0.js` (338.62 kB),
`journal-voucher-pJyh3y7x.css` (9.43 kB). Compiled bundle verified to contain the
new `.jr-*` tokens (phase-43 stale-CSS check).

**Live browser (headless Chrome, `accountant@test.com`, Acme tenant, 1440px)**
- Register renders 12 rows → 23 server-rendered `.jr-modal`, 8 `.jr-modal--rev`,
  9 `.jr-print` sheets (8 posted + 1 reversed), 8 row Print buttons; the
  `journal-voucher` stylesheet is loaded (`hasVoucherCss: true`).
- Open a row → `.jr-modal.on` = `jr-journal-12`.
- Open **Reverse** → reversal overlay layers over the journal modal
  (`revOn: 1`, `journalOn: 1`); **Esc** closes only the reversal
  (`revOn: 0`, `journalOn: 1`); a second Esc closes the journal.
- Print → `.jr-print.on` visible with `.glj-sheet` at `z-index: 200`, then
  closes cleanly.
- Responsive: `.jr-wrap` does not overflow at 1440/1024/768/375; the journal
  modal fits at 375px (327px wide, fully on-screen).
- No JS page errors; only the pre-existing `/favourites` 401.

## 5. Gotchas hit

- `layouts/app.blade.php` renders `@stack('styles')` in `<head>` **before**
  `{{ $slot }}`, so a `@push('styles')` from a child view arrives too late. The
  register emits `@vite('resources/css/journal-voucher.css')` inline inside the
  slot instead; a regression assertion locks it in.
- `page.evaluate` runs in an isolated world, so `window.Alpine` is unreachable —
  the payload must be read from the `x-data` attribute and JSON-decoded.
- `Js::from()` renders the payload as `JSON.parse('<json>')`, so `/` arrives as
  `\\\/` and keys as `\u0022key\u0022`; `JournalRegisterTest::encodedJson()`
  double-encodes test values. Prefer decoding the payload for non-trivial checks.
- The shared DIALOG SYSTEM block pins `.jr-modal` to `z-index: var(--z-dialog)`
  with `!important`; the register overrides it with more-specific `.jr-wrap`
  selectors.

## 6. Not done

- §8.5 Email voucher — blocked (no endpoint; R6 forbids adding one).
- Detail-page (`show.blade.php`) reversal modal is outside the spec's three
  surfaces and was left as built by the earlier voucher phase.

## 7. Unrelated work in the tree

`git status` may carry pre-existing changes from earlier phases (dashboard,
favourites, todo, feedback, POS cashier PIN migrations, various list views).
None belong to this task; none were touched or reverted here.
