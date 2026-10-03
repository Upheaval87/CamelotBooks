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

## §7 follow-up (round 2)

Owner-requested refinements applied to the Capture Reversal modal. Presentation + one
validation bound only; no model/core-business-logic changes.

- **Modal radii** — `.tc .mbox` corner radius `18px → 20px` and `overflow: hidden` added so the
  dark `.m-head` band is clipped to the rounded top-left/top-right corners (mockup parity).
  `app.css` ~L19119. Live-probe: `border-radius: 20px`, `overflow: hidden`.
- **Copy removed** in the reversal modal:
  - the `.warn-panel` ("A reversal mirrors this entry with debits and credits swapped. Posting
    is permanent and audited."),
  - the reason textarea `placeholder` ("…why is this entry being reversed"),
  - the footer `.mnote` ("An audit trail records this action.").
  Live-probe: reversal body innerText no longer contains any of the three strings;
  `hasWarnPanel:false`, `hasPlaceholder:false`, `hasMnote:false`.
- **Reason min-length 10 → 3** — client `minlength="3"` (`_modals.blade.php`) and server
  `min:3` (`TransactionControlsController.php:169`). Test renamed
  `test_reverse_requires_a_substantive_reason` → `test_reverse_requires_a_reason`, now posts a
  2-char reason ("no") to trigger the failure.
- **Confirm-first on "Confirm reversal"** — the modal's gold/danger action is now
  `type="button" @click="askReverse()"` (was `type="submit" form="tc-reverse-form"`).
  `askReverse()` (`transaction-controls.js`) runs `form.reportValidity()` (so an empty/short
  reason is blocked by the browser) and then calls the shared `askConfirm({…, formId:
  'tc-reverse-form'})`; `confirmYes()` still submits the real form. Live-probe (reason filled
  with a valid value): clicking "Confirm reversal" opens `.tc .confirm` — visible, title
  "Confirm reversal", body "Post the reversal of JE-2026-0011? Posting is permanent and
  audited.", OK button `btn btn-sm btn-danger`; `navigated:false`.
- **Verification** — `npm run build` → `app-DzZPT43-.css` (951.93 kB, `.mbox` radius/overflow
  confirmed), `app-Bqi5W52B.js`; `view:clear`/`view:cache` clean; `TransactionControlsTest`
  **16 passed / 42 assertions**. Live headless probe `cb-probe/tc-fix-probe.mjs` exercises the
  full open → fill reason → confirm path on JE-2026-0011.
- Out of scope: `resources/views/accounting/reversals/create.blade.php` (separate legacy
  reversals form) still has a `minlength="10"` textarea — left untouched.

## §8 follow-up (round 3) — empty modal flashing on page load

**Symptom:** on every page load (incl. `accounting/transaction-controls`) an empty modal
flashes for a frame and then disappears.

**Root cause (NOT the `.tc` modals):** the two global `<x-modal>` instances rendered
unconditionally by the layout — "My Tasks" and "Task detail" (`layouts/app.blade.php`
L126/L151) — come from the shared `resources/views/components/modal.blade.php` shell. That
shell's root element is `.dlg-scrim`, which the base CSS declares `display: flex`, and the
component had **no `x-cloak`**. So the scrim was visible from first paint until Alpine ran
(`x-show="show"`, `show=false`) and set `display:none` — an empty modal flashing on every
page. The `.tc .jmodal` modals already carry `x-cloak`; they were never the flashers.

**Evidence:** with JS blocked, the shared probe (`cb-probe/tc-flash.mjs`) measured `.dlg-scrim`
→ `{ cloak: false, display: flex }` (visible). The earlier flash probes only sampled
`.tc .jmodal`, which is why the flash was initially not reproduced (flashCount 0).

**Fix:** added `x-cloak` to the shared `<x-modal>` root (`resources/views/components/modal.blade.php`
L107). `[x-cloak] { display: none !important }` (`app.css` L5, unlayered → beats
`.dlg-scrim{display:flex}`), and Alpine removes the attribute + applies `x-show` on init.
This is the single global fix — every `<x-modal>` call site (incl. TC's page) is covered; no
`.tc` markup change was needed.

**Verification:**
- JS-blocked (`cb-probe/tc-flash.mjs`): `.dlg-scrim` → `{ cloak: true, display: none }` (x2).
  No flash before Alpine runs.
- JS-enabled (`cb-probe/tc-globalmodal-probe.mjs`): before open `display:none`; clicking the
  topbar My Tasks trigger → `display:flex`, title "My Tasks", card visible; Escape → `none`.
  The modal still opens/closes correctly.
- `view:clear` + `view:cache` clean (Blade-only change; no asset rebuild required).
- `TransactionControlsTest` **16 passed / 42 assertions**.

## §9 follow-up (round 4) — authorization-only reversal lifecycle + search field

**Ask A — search field rendering.** The reversal pane's search control rendered a visible
"Search" label ABOVE the input, pushing the field out of alignment with the sibling Type
filter. Fixed in `_pane-reversal.blade.php`: removed the `<span class="fl">Search</span>`
inside `.searchwrap` (icon + input only) and moved the accessible name onto the input as
`aria-label="{{ __('Search') }}"`. No CSS change was needed — `.tc .searchwrap` already
positions the icon with `top:50%` + `translateY(-50%)`.

**Ask B — capture must be authorized before it becomes final.** Previously the capture modal
offered a Submission-mode choice (`immediate` / `draft` / `authorization`); `immediate`
posted the mirror at once and `draft` created a draft reversal — both bypassed authorization.
The lifecycle is now authorization-only:

- **Capture** always calls `TransactionReversalService::requestReversal()` and creates a
  `pending_authorization` `TransactionReversalRequest` with its approval chain. The original
  JE stays **POSTED**; no reversal JE is written. Controller `reverse()` dropped the `mode`
  validation + switch (legacy `mode` input is ignored). Flash: "Reversal submitted for
  authorization — it will post once approved."
- **`guardReversal()`** now aborts `422` ("A reversal for this transaction is already awaiting
  authorization.") when an OPEN request (`pending_authorization` or `approved`) already exists
  for the entry — one open capture per transaction.
- **Approve** runs the multi-level gate: after the authorizer's row is marked approved, if any
  `pending` authorization rows remain the request is returned **unexecuted** (still awaiting
  the remaining authorizers). Only the FINAL approval sets `approved` + executes the reversal
  (original → REVERSED, mirror JE posted). The controller flash is dynamic on the outcome.
- **Reject** marks the caller's row approved, then **clears every remaining `pending` row to
  `rejected`**, and sets the request `rejected`. The JE is untouched (stays POSTED) and is
  immediately capturable again.

**Result-state mapping (per the ask):** capture → *Awaiting authorization*; approve →
*Reversed*; reject → the entry returns to its prior state (POSTED), re-capturable.

**View/JS:**
- `_modals.blade.php`: removed the Submission-mode `pseg` block, replaced with a plain
  "This reversal will be submitted for authorization…" note; capture footer button is now
  `btn-cta` "Submit for authorization"; the view modal shows an "Awaiting authorization"
  status pill and a `m-foot` note when `viewTx.awaitingAuthorization`.
- `_pane-reversal.blade.php`: the reversal pane status cell adds
  `@elseif (isset($awaitingAuthIds[$entry->id]))` → `pill fin` "Awaiting authorization".
- `TransactionControlsController`: new `$awaitingAuthIds` map built in `index()`
  (statuses PENDING + APPROVED, keyed by JE id) and passed to the view; `entryPayload()`
  exposes `awaitingAuthorization` and sets `reversible = isPosted() && !awaiting`. The
  controller no longer injects `JournalReversalService` (the import is kept for the static
  `identityVerifyThreshold()`); `approve()` message is dynamic on the final status.
- `resources/js/transaction-controls.js`: removed `revForm.mode`; `askReverse()` now confirms
  "Submit for authorization" (body "…will post to the ledger only after approval."); the
  approve confirm reads "Authorize reversal".

**Verification:**
- `php -l` clean on controller + service; `view:clear` + `view:cache` clean; `npm run build`
  → `app-DzZPT43-.css` (951.93 kB) / `app-Cyna2VWI.js`.
- `TransactionControlsTest` **19 passed / 54 assertions** (was 16/42): new/updated coverage
  for capture-leaves-entry-posted, legacy-mode-ignored, duplicate-capture-blocked,
  reject-clears-pending-chain-and-allows-recapture, awaiting-entry-flagged-in-list.
- Live headless probe `cb-probe/tc-search-probe.mjs`: `.searchwrap` label `null` (no text
  above), icon center y 323 == input center y 323, input bottom 343 == Type select bottom 343
  (fields aligned). (The 401 on `/favourites` during the probe is the known benign probe.)
