# Transaction Controls Module — Implementation Report

Spec: `docs/TRANSACTION-CONTROLS-MODULE.txt` (authoritative, Appendix A mockup embedded).
Deliverable: a unified **Transaction Controls** workspace with three panes — Capture Reversal,
Unposted Transactions, Authorization.

## Files touched

New:
- `app/Http/Controllers/Accounting/TransactionControlsController.php`
- `app/Policies/TransactionControlPolicy.php`
- `resources/views/accounting/transaction-controls/index.blade.php`
- `resources/views/accounting/transaction-controls/_pane-reversal.blade.php`
- `resources/views/accounting/transaction-controls/_pane-unposted.blade.php`
- `resources/views/accounting/transaction-controls/_pane-authorization.blade.php`
- `resources/views/accounting/transaction-controls/_modals.blade.php`
- `resources/views/accounting/transaction-controls/_type-chip.blade.php`
- `resources/js/transaction-controls.js`
- `tests/Feature/Accounting/TransactionControlsTest.php`

Modified:
- `routes/web.php` (route group below)
- `resources/js/app.js` (`import './transaction-controls';`)
- `resources/css/app.css` (scoped `.tc` mockup block — classes namespaced under the `.tc` wrapper so global `.btn`/`.card`/`.pill` are untouched)
- `resources/views/layouts/topbar-two-row.blade.php` (nav entry)
- `tests/Feature/Accounting/ScopedSearchRenderSmokeTest.php` (3 routes added)

## DB migrations

**None.** The spec permits reusing existing structures (HARD GUARD: frontend-only view layer).
Parity mapping (existing → spec §4):

| Spec §4 table | Reused structure |
| --- | --- |
| `transactions_reversals` | `transaction_reversal_requests` (+ `reversal_authorization_requests`) |
| `authorization_requests` | `reversal_authorization_requests` |

Existing `JournalReversalService`, `TransactionReversalService`, `JournalPostingEngine`
(write path), `JournalEntry` status machine, and `SystemSetting` currency were reused unchanged.

## Route definitions

```
Route::prefix('transaction-controls')->name('transaction-controls.')->group(function () {
    Route::get('/', [TransactionControlsController::class, 'index'])->name('index');
    Route::post('{id}/reverse', [TransactionControlsController::class, 'reverse'])->name('reverse')->where('id', '[0-9]+');
    Route::post('{id}/reopen', [TransactionControlsController::class, 'reopen'])->name('reopen')->where('id', '[0-9]+');
    Route::delete('{id}', [TransactionControlsController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    Route::post('authorization/{id}/approve', [TransactionControlsController::class, 'approve'])->name('approve')->where('id', '[0-9]+');
    Route::post('authorization/{id}/reject', [TransactionControlsController::class, 'reject'])->name('reject')->where('id', '[0-9]+');
});
```

Registered inside the `accounting.` prefix group → names `accounting.transaction-controls.*`.
`{id}` uses an explicit `[0-9]+` constraint and is resolved manually via
`JournalEntry::forCompany($companyId)->findOrFail($id)` (no implicit binding — sidesteps
the pre-existing tenant route-binding issue).

## Controller methods

`TransactionControlsController`:
- `index` — permission `transaction-reversals.view`; resolves tab, date gate, filters, panes.
- `reverse` — `transaction-reversals.request` + `captureReversal` policy; modes
  `immediate` (createAndPost), `authorization` (requestReversal), `draft` (createDraft);
  reason `min:10`.
- `reopen` — `journal-entries.edit` + `manageUnposted` policy; finalized → draft.
- `destroy` — `deleteUnposted` policy; draft-only hard delete.
- `approve` — `transaction-reversals.approve` + `dualControl`; posts the reversal.
- `reject` — `transaction-reversals.reject` + `dualControl`; stores reason.

## Policy classes

`TransactionControlPolicy` (plain class, manually instantiated):
- `viewReversals` → `transaction-reversals.view`
- `captureReversal` → `transaction-reversals.request`
- `manageUnposted` → `journal-entries.edit` OR owner
- `deleteUnposted` → owner OR `journal-entries.edit`
- `authorizeRequests` → `transaction-reversals.approve`
- `dualControl($actor, $requesterId)` → `$actor->id !== $requesterId`

## Appendix A redesign (view layer only)

The three panes and all modals were rebuilt to match the Appendix A mockup exactly
(columns, row markup, modal structure/copy). No controller logic, model, route,
validation or persistence changed.

- **Header** — clean light `.page-header` (icon tile + H1 "Transaction Controls") with a
  "Reversal register" ghost link; tabs `.mtabs` labeled **Capture Reversal / Unposted
  Transactions / Authorization** (singular, R3) with live counts.
- **Pane 1 (Capture Reversal)** — date-gate card (From/To, quick-range presets, "Load
  transactions") until a period is loaded, then a `.filtersrow` (loaded chip, search `q`,
  Type select) + table `Reference · Type · Date · Description/Party · Amount · Posted by ·
  Status · Actions` with `.ty` type chips and `.pill posted|rev`; rows carry **View only**
  (R5); custom `.tfoot`/`.pager`.
- **Pane 2 (Unposted Transactions)** — `Reference · Type · Saved · Description · Amount ·
  Created by · State · Actions`; state `.pill fin|draft`; row actions view + reopen/delete.
- **Pane 3 (Authorization)** — `Request · Type · Requested by · Amount · Submitted ·
  Waiting · Action` (Waiting = `diffForHumans`) + a "Recently decided" `.hist` table.
- **Modals** — View (`.jmodal`/`.mbox`, meta chips, lines table, totals/balance, footer
  Capture reversal), Reversal (`.revz`/`.mbox.narrow`, three submission modes; default
  **Post immediately**), Authorization (comment field + Reject/Authorize), and a shared
  Confirm (`.cbox`). Lifecycle actions live only in modals (R5). Icons resolved by
  `iconFor(type)` (`window.TC_ICONS`).
- **Modal header colours (mockup-exact)** — `.m-head` uses the dark teal header band
  (`radial-gradient(700px 200px at 10% -30%, rgba(255,255,255,.09), transparent 60%)` +
  `linear-gradient(180deg,#11454b,#0c3539 55%,#0a2e32)`) with a white `.m-ic` tile
  (`--tc-deep2` glyph), white `.m-title`, light `.m-ref`, light-on-dark status
  `.m-pill.posted|rev|fin|draft`, and a translucent `.m-close` (hover rotate). The Confirm
  `.cb-head` uses the light `linear-gradient(180deg,#fdfeff,#f6fbf9)` with a hairline
  bottom border; `.cbox` is padding-free/`overflow:hidden` so head/body/foot carry their
  own gutters. Live-proof tokens only.
- **CSS** — new `.tc`-scoped block in `app.css` (BEM-ish `.tcard`/`.mtabs`/`.pill`/`.ib`/
  `.jmodal`/`.cbox` …), replacing the earlier `.tc-*` utilities. Zero global class clobber.

## §6 verification

- **6.1 Visual parity** (headless Chrome via `browser.mjs`): wrapper `.tc` present, H1
  "Transaction Controls", 3 tabs with counts, gate + presets, `tbody` rows render with
  `.ref`, `.ty` chip and status `.pill`; tab click switches panes client-side (active tab
  and `?tab=` update, unposted table + "New Journal" render); loading a period replaces the
  gate with the filters row and 8-column table; View modal opens (`.mbox`, title, lines
  table, footer actions) and closes. CSS loaded (`cssRules` 9033); only console/network
  error is the pre-existing `/favourites` 401. Modal header colour probe: `.m-head`
  computes the dark teal gradient, `.m-title` `rgb(255,255,255)`, `.m-ref` `rgb(234,255,255)`,
  `.m-ic` white/`rgb(12,53,57)`, `.m-pill.rev` red-on-dark, `.m-close` translucent; `.cb-head`
  computes `linear-gradient(rgb(253,254,255), rgb(246,251,249))` + hairline bottom border.
- **6.2 R1–R6**: R1 no `backdrop-filter` on the workspace; R2 transparent/light header,
  0 stat cards; R3 tab labeled "Authorization"; R4 From/To gate enforced; R5 lifecycle
  actions only inside modals (list rows = View); R6 audit via existing reversals/
  authorization logging (user, timestamp, reason).
- **6.3 Date validation**: `From` required with `To`; `From > To` renders inline error and
  blocks load (tests `test_date_gate_requires_both_dates`, `test_from_after_to_shows_error`).
- **6.4 Tab naming**: "Authorization" (singular) confirmed in DOM and tests.

## Tests

- `TransactionControlsTest` — **16 passed / 42 assertions**.
- `ScopedSearchRenderSmokeTest` — render smoke green (includes the 3 new routes).
- Build: `vite build` → `app-Chzd1GdG.css` / `app-jsJ4VUAS.js` (TC_ICONS/iconFor/authComment
  and the `.tc`-scoped mockup classes + dark `.m-head` / light `.cb-head` gradients
  confirmed present in the bundles).
