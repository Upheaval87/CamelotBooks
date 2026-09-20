# Journals Register — Implementation Report

**Page rebuilt:** `accounting/journal-entries/index.blade.php` (+ lifecycle state machine behind it)
**Spec:** `docs/journal-register.html`
**Status:** Complete — 17 tests / 86 assertions (`JournalRegisterTest`), phase-61 reversal suite intact (17/17), smoke test covers register + edit routes
**Date:** Sep 17, 2026 · commit `a1fa0ab` (branch `feature/accounting-method-inheritance`)

---

## 1. Overview

The Journals Ledger register was rebuilt from a plain table to a full working register with:

- **Status tabs** (All / Unfinalized / Unposted · Finalized / Posted / Reversed) with live counts
- **Server-side filters** — date range *or* accounting period (Period wins), Type, Branch, Search, plus status tabs that preserve every other filter
- **Context-aware row actions** per status, with creator-only guards on draft edit/delete
- **A wide journal viewer/editor modal** with confirm-gated lifecycle transitions:
  `draft → (finalize) → pending_approval → (post) → posted → (reverse) → reversed`, plus `pending_approval → (reopen) → draft`
- **Reversal** reuses the phase-61 `JournalReversalService`; posted journals stay immutable

The rebuild is additive to the existing posting engine. The old `reverse()` path and all existing journal create/edit/post/approve/reject routes are untouched.

---

## 2. Files Touched

### New files
| File | Purpose |
|------|---------|
| `app/Services/Accounting/JournalTypeClassifier.php` | Coarse Type buckets (General/Payments/Payroll/Depreciation/Inventory/Reversal) driving both the Type column and the Type filter |
| `resources/views/accounting/journal-entries/_jr-modals.blade.php` | All register modals: `#jr-journal`, `#jr-delete`, `#jr-reopen`, `#jr-reverse`, `#jr-confirm` + hidden lifecycle forms |
| `resources/js/journal-register.js` | Alpine `journalRegister` component (imported in `app.js`) |
| `tests/Feature/Accounting/JournalRegisterTest.php` | 17 tests / 86 assertions |

### Modified files
| File | Change |
|------|--------|
| `app/Http/Controllers/Accounting/JournalEntryController.php` | `index()` rewritten (filters + payload); new actions `finalize` (693), `postFinalized` (738), `reopen` (755), `destroy` (776), `exportCsv` (805); helpers `resolveFilters/baseQuery/applyTypeFilter/resolveDateRange/periodOptionMap/periodOptions/statusCounts/buildPayload/entryPayload/currencySymbol/registerRedirect` |
| `app/Services/Accounting/JournalPostingEngine.php` | New `finalize` (270), `postFinalized` (310), `reopen` (336), `deleteDraft` (365); `logAction()` gained `?string $notes = null` |
| `resources/views/accounting/journal-entries/index.blade.php` | Full `.jr-*` rebuild |
| `resources/css/app.css` | `.jr-*` block appended at end of file |
| `resources/js/app.js` | `import './journal-register';` |
| `routes/web.php` | 5 new journal-entries routes (+ export) |
| `resources/views/accounting/accounts/index.blade.php` | `function_exists()` guard on `_coaFlattenAccounts()` (pre-existing fatal fix) |
| `tests/Feature/Accounting/ScopedSearchRenderSmokeTest.php` | Added `journal-entries.edit` (draft fixture) |
| `AGENTS.md` | Phase 61 + 62 entries |

---

## 3. Status model & lifecycle

| Register tab | Engine status | Meaning | Row actions |
|--------------|---------------|---------|-------------|
| Unfinalized | `draft` | Not yet submitted for approval | open · edit (creator) · delete (creator) |
| Unposted · Finalized | `pending_approval` | Finalized; `post()` is the only ledger write | open · reopen |
| Posted | `posted` | Immutable; posted_by/posted_at stamped | open · print · reverse (view-gated) |
| Reversed | `reversed` | Original of a posted reversal | open · open reversal |

Transitions:
- `draft --finalize(creator, balanced)--> pending_approval` — **additive only, never a ledger write**
- `pending_approval --post--> posted` — the only ledger write; **SOD `sod:journalEntry`** (creator 403 = maker-checker)
- `pending_approval --reopen--> draft` — reason kept in audit `notes`
- `posted --reverse--> reversed` — via phase-61 `JournalReversalService`; mirror posted and cross-linked
- `draft --delete (creator only)--> destroyed` — rows + lines removed, audited

Engine `finalize()` optionally replaces the line set (description/debit/credit editable from the modal) and validates the journal stays balanced; the same balance check re-runs on `post()`. Posted journals unchanged — `reverse()` at `JournalPostingEngine.php:197–262` is FROZEN (locked by `PostingEngineTest:223`).

---

## 4. Type classifier

`JournalTypeClassifier` maps `source_module` (+ adjusting flag) onto six coarse buckets:

| Bucket | `source_module` values |
|--------|------------------------|
| **Reversal** | `reversal` |
| **Payroll** | `payroll` |
| **Depreciation** | `fixed_assets` |
| **Inventory** | `stock_count`, `assembly_build`, `assembly_unbuild`, `landed_cost`, `grn` |
| **Payments** | `customer_payment`, `vendor_payment`, `expense_payment`, `sales_receipt`, `pos`, `bill`, `invoice`, `cheque`, `cheque_void`, `make_deposit`, `petty_cash_establish`, `petty_cash_expense`, `petty_cash_replenish`, `bank_transfer`, `bank_manual`, `bank_reconciliation`, `credit_note`, `vendor_credit`, `expense` |
| **General** | everything else (the complement — `modulesFor('General')` returns `null`) |

`allGroupedModules()` feeds the General filter as `whereNull(source_module)->orWhereNotIn(allKnownModules)`. Purely presentational — no ledger impact — so the Type column and Type filter can never disagree (`options()` is the single source).

---

## 5. Controller

`index()` (line 35) builds `$jrConfig = ['entries' => $payload, 'can' => $can, 'me' => auth id, 'defaultAccount' => ...]` consumed by the Alpine bootstrap `x-data="journalRegister({{ Js::from($jrConfig) }})"`.

- **`resolveFilters`** — validates period/status against allowed maps, coerces branch/type/search, `page`.
- **`applyTypeFilter`** — General uses the complement (`whereNull` + `orWhereNotIn`), others `whereIn(source_module, modulesFor)`.
- **`resolveDateRange`** — period preference beats a raw from/to range; `periodOptionMap` returns a **keyed** map (This Month / Last Month / This Quarter / YTD / FY) preserved through `array_map`.
- **`statusCounts`** — status-agnostic counts that respect the non-status filters, so tab numbers stay meaningful next to a search.
- **`entryPayload`** — per-row `urls` map (`show/finalize/post/reopen/destroy/reverse`) bound by the modal `:action`.
- Mutations (`finalize`/`postFinalized`/`reopen`/`destroy`) all redirect back through `registerRedirect()` preserving the active query string (status/search/type/branch/date/period/page), so the register returns to the exact filtered view.

`exportCsv` streams the same `baseQuery` as `text/csv`.

---

## 6. Routes (`routes/web.php`, journal-entries group)

| Route | Controller/middleware |
|-------|----------------------|
| `GET journal-entries/export` | `exportCsv` · `permission:journal-entries.view` — declared **before** `{journalEntry}/edit` |
| `POST journal-entries/{journalEntry}/finalize` | `finalize` · `permission:journal-entries.edit` (creator-only in controller — no SOD) |
| `POST journal-entries/{journalEntry}/post` | `postFinalized` · `permission:journal-entries.post` + **`sod:journalEntry`** |
| `POST journal-entries/{journalEntry}/reopen` | `reopen` · `permission:journal-entries.edit` |
| `DELETE journal-entries/{journalEntry}` | `destroy` · `permission:journal-entries.edit` (draft + creator-only in controller) |

SOD is deliberately applied **only** to `post` — finalize/reopen/delete are self-actions and enforce creator-only server-side (403/redirect).

---

## 7. View (`index.blade.php`)

- `.jr-wrap` carries the single Alpine instance; `.jr-phead` has title + signed-in label + Export + New Journal.
- **GET filter form** — Date-range/Period mode toggle, From/To, Period, Type, Branch, Search, Apply/Clear. `Apply` first runs `validateDates()` (To < From marks both `.bad`, toasts, blocks submit); `syncBounds()` pins the `<input>` min/max.
- `.jr-tabs` are server-side `<a href="?status=...">` links preserving other filters; counts come from `statusCounts`.
- `.jr-card`/`.jr-table` — Journal № / Date / Type / Description / Source / Lines / Total / Status / Actions (`colspan=9` empty row).
- Row actions per the status matrix; draft edit/delete buttons are creator-gated with the exact tooltip "Only the creator (…) can edit" when foreign.
- Posted-row reversal is hidden (`reverse` action) until the entry has been **opened this session** — implemented with a reactive `viewed[id]` set in `journal-register.js`.
- `@include('accounting.journal-entries._jr-modals', ['preserved' => $preserved])`.

---

## 8. Modals (`_jr-modals.blade.php`)

| Modal | Use |
|-------|-----|
| `#jr-journal` | Wide viewer/editor — meta chips, memo, lines table; edit mode edits only Description/Debit/Credit (Account/Cost centre stay text), add/remove lines via `defaultAccount`; Totals row with `✓ Balanced / ⚠ Unbalanced` chip; footer view/edit actions |
| `#jr-finalize-form` | Hidden POST; serializes edited lines as `lines[idx][account_id|memo|debit|credit]` via Alpine `<template x-for>` |
| `#jr-post-form` | Hidden POST to `currentEntry().urls.post` — **fix in this phase**: `askPost()`/`confirmYes()` referenced this form before it existed, so posting silently no-opped |
| `#jr-delete` / `#jr-reopen` / `#jr-reverse` | Confirm-gated forms with `@method('DELETE')` / reason textarea / date+reference+reason+post_mode |
| `#jr-confirm` | Generic z-140 confirmation box (`cf.open`); `confirmYes()` submits the referenced form by id |

Every form carries `@csrf` plus hidden `$preserved` inputs (status/search/type/branch_id/date_from/date_to/period/mode/page) and `:action` bound to `currentEntry().urls.*`.

---

## 9. JS (`resources/js/journal-register.js`)

One Alpine `journalRegister` component: `get/currentEntry/isMine/totalDr/totalCr/balanced/fmt/pillClass/pillLabel/open/startEdit/cancelEdit/addLine/removeLine/openDelete/openReopen/openReverse/openReversal/printEntry/askConfirm/confirmYes/askFinalize/askPost/askReverse/footerNote/setMode/syncBounds/validateDates/toast/closeAll`. `open()` marks `viewed[id]` reactively (the reversal gate). Client-only `.jr-toast` for print/date-range/missing-reason feedback.

---

## 10. CSS (`.jr-*` block, appended to `app.css`)

Fully namespaced to avoid clobbering global `.card/.tab/.pill/.btn`. Covers the page shell, tabs with counts, filter inputs, table + pread badge styles, the wide modal (`.jr-modal` z-90, `.jr-cf` z-140), edit-mode line inputs, totals/Balanced chip, confirm box, `@media print` isolation of the open journal (visibility trick + hidden `.jr-mbox-f`/`.jr-x`), and responsive rules.

---

## 11. Bug fixed while testing

`#jr-post-form` was missing from the partial even though `askPost()` → `confirmYes()` submits by `id` — Post to Ledger would silently no-op. Added the hidden form with `:action`, `@csrf`, and preserved inputs.

---

## 12. Tests & verification

- **`JournalRegisterTest` (17 / 86)** — index markup (tabs/payload/type options); status-tab filtering; Type complement (`type=Payroll` vs `type=General`); search + empty state (`No journals match the current filters.`); period-mode range resolution (`this_month` / negative `last_month`); finalize → `pending_approval` + audit `finalized` + `posted_at` null (no ledger write); finalize line replacement; unbalanced rejection; finalize blocked for non-creator; post writes to the ledger (by a second user; audit `posted`); post 403 (SOD) for the creator; reopen with audit `notes`; delete own draft (entry + lines gone); delete blocked for non-creator; delete blocked once finalized; CSV export stream; creator-only tooltip.
- **`ScopedSearchRenderSmokeTest`** — added `journal-entries.edit` (draft fixture) → 3 passed / 15 assertions. The export route is intentionally absent from the smoke harness (it returns a raw `StreamedResponse` that has no `->status()`); its coverage lives in `JournalRegisterTest`.
- **Regression locks** — `PostingEngineTest|SalesModuleTest|AccountingControlCentreTest|JournalReversalTest` = 51 passed / **3 failed**, where the 3 are the pre-existing stale COA assertions in `AccountingControlCentreTest` (`test_chart_of_accounts_index_renders/filter_by_type/filter_by_status` assert legacy `coa-kpis/coa-toolbar/coa-table` but the view was redesigned to `coa2-*` at HEAD) — **not regressions**; `ScopedSearchRenderSmokeTest` still covers the route.
- `php -l` clean; `php artisan view:cache` clean; `npm run build` emits the `.jr-*` tokens (`app-C0s2PL_F.css` / `app-CEFBL5qQ.js`).

---

## 13. Notes & known constraints

- Key decision: **finalize is additive and never auto-posts** — `post()` is the single ledger write; existing `submitForApproval`/`approve` routes are untouched.
- Mode ✓: `unposted` tab collapses `pending_approval` (the engine never sets a separate `approved` status).
- Reversal duplicates now traverse the phase-61 flow (mirrored draft or immediate posted reversal, original↔reversal cross-link, reason in audit notes) — no separate reverse logic was introduced.
- Record creator cannot `post`/`reverse` their own entry (SOD); finalize/delete/reopen enforce creator-only in the controller.