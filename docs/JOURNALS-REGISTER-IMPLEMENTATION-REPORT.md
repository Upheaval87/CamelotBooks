# Journals Register, Journal Modal, Reversal Chain & Print Voucher — Implementation Report

Implementation of `docs/JOURNALS-REGISTER+JOURNAL-MODAL+REVERSAL-CHAIN-PRINT-VOUCHER.txt`
(Appendix A, 806 lines, self-contained).

**Branch:** `feature/accounting-method-inheritance`
**Guard:** no posting/calculation/permission logic, request payloads, or database
behaviour was changed. No new packages. No `backdrop-filter`.

Two read-only endpoints **were** added earlier in this task, because §0.3/§5.1
require them: `GET journal-entries/{journalEntry}/print` (`printVoucher`) and
`GET journal-entries/{journalEntry}/print/pdf` (`downloadVoucherPdf`), both
gated by `permission:journal-entries.view`. Nothing else in `routes/web.php`
changed, and no schema migration belongs to this task.

---

## 1. Discovery (§0) — what already existed

Most of the specification was already implemented by the preceding phases, so
this pass was a delta port rather than a build:

| Spec area | Existing implementation | Status before |
|---|---|---|
| §2 Register page | `.jr-*` suite, `resources/views/accounting/journal-entries/index.blade.php` | complete |
| §3 Journal modal | `#jr-journal` in `_jr-modals.blade.php` | complete |
| §4 Reversal chain | `#jr-reverse` + `#jr-confirm`, `JournalReversalService` | complete, missing preview |
| §5 Print voucher | `_voucher-sheet.blade.php`, `print.blade.php`, `pdf.blade.php`, `journal-voucher.css` | complete |
| §0.3 endpoints | finalize / post / reopen / delete / reverse / post-reversal / discard-reversal / print / print-pdf / export | complete |

## 2. Deviations (decided with the user)

Two spec requirements conflicted with work already approved. Both were resolved
explicitly before editing:

1. **§5.1 preview overlay — KEPT the standalone page.**
   The spec asks for a fixed `z-200` in-page overlay with its own toolbar. The
   standalone `/print` new-tab page (Back / Download PDF / Print) was the
   architecture just built and verified. The overlay was **not** added; the
   register Print button now opens that page instead of calling `window.print()`
   on the modal, which would have printed the entire app.
2. **§5.1 Email voucher button — OMITTED.**
   No journal-voucher email endpoint exists, and the spec's own R6 guard forbids
   adding routes. Toolbar is Back / Download PDF / Print. **Blocked, documented
   here rather than silently invented.**

## 3. Changes made

### §2.5 / R5 — Actions column is view + print only
`index.blade.php` — removed the per-row edit, delete, reopen, reverse and
"open reversal" buttons. A row now renders only `Open journal`, plus
`Print voucher` when the status is `posted` or `reversed`. Every lifecycle
action remains reachable from the modal footer, so no capability was lost.

### §4.1 — Reversal modal mirrored preview
`_jr-modals.blade.php` + `journal-register.js`:
- added a mirrored-line preview: every debit reappears as a credit and vice
  versa, rendered from the `lines` already present in the payload;
- `Reference` relabelled **New reference**, now `readonly` and monospace;
- the reason `textarea` became a single-line required input, matching the
  mockup and the detail-page modal;
- the note moved into an amber warning panel.

`JournalEntryController::entryPayload()` gained `'print' => route(...print)`
so the register can open the standalone preview. This hands the client the URL
of the **existing** route added earlier in this task; no route, endpoint or
request contract changed in this pass.

### CSS
Appended a scoped `.jr-*` block: amber warning panel, mirrored preview
(header / side chips / mono codes / tabular amounts), readonly mono field, and
a 720px fallback. No global rule touched.

## 4. Verification (§7)

**Automated — all green**
```
JournalRegisterTest        19 passed  (118 assertions)   [2 new]
JournalReversalTest        18 passed  ( 84 assertions)
JournalVoucherPrintTest     7 passed  ( 66 assertions)
PostingEngineTest           6 passed  ( 27 assertions)
SalesModuleTest             7 passed  ( 40 assertions)
ScopedSearchRenderSmokeTest 3 passed  ( 15 assertions)
```
Plus `npm run build` (79 modules) and `php artisan view:cache` clean. All four
new `.jr-*` tokens confirmed present in the compiled bundle (phase-43 stale-CSS
check).

**Live browser (1440px, `accountant@test.com`)**
- Every row action cell contains exactly `Open journal` + `Print voucher`;
  no lifecycle handler appears in any row.
- Opened posted `JE-2026-0012` → **Reverse**. Mirrored preview rendered 2 lines
  and the swap is provably correct: `5000 CR 300.25 → DR 300.25`,
  `1000 DR 300.25 → CR 300.25`, amounts preserved. Warning panel visible,
  reference readonly + mono, both post modes present.
- Register Print opened `http://127.0.0.1:8010/accounting/journal-entries/12/print`
  — title `JE-2026-0012 — Journal Voucher`, `.glj-sheet` rendered, `POSTED`
  watermark, no Email button.

**Probe gotcha:** `page.evaluate` runs in an isolated world, so `window.Alpine`
is unreachable — the payload must be read from the `x-data` attribute and decoded
with `JSON.parse(JSON.parse('"' + m[1] + '"'))`. An early probe read the modal's
wrong table columns and wrongly reported the mirror as unswapped; decoding the
payload proved the implementation correct.

**Known unrelated:** `/favourites` returns 401 in the browser (pre-existing).

## 5. Test notes

`Js::from()` renders the payload as `JSON.parse('<json>')`, so every `/` reaches
the HTML as `\\\/` and keys as `\u0022key\u0022`. Substring assertions on payload
values are therefore impossible to write readably — `encodedJson()` in
`JournalRegisterTest` double-encodes a value the way `Js::from` does. Prefer
decoding the payload for anything non-trivial.

## 6. Not done

- §5.1 in-page preview overlay — superseded by the standalone page.
- §5.1 / §8.5 Email voucher — blocked, no endpoint, R6 forbids adding one.
- Detail-page (`show.blade.php`) reversal modal left as simplified last turn; it
  is outside the spec's three surfaces.

## 7. Unrelated work in the tree

`git status` also carries pre-existing changes from earlier phases (dashboard,
favourites, todo, feedback, POS cashier PIN migrations, various list views).
None belong to this task; none were touched or reverted here.