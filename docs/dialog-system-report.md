# Dialog System — Executive Teal (Aug 8, 2026)

Replaced the legacy `.fb-*` modal/toast layer and `window.feedback` API with a new CB (CamelotBooks) dialog system styled to `docs/dialog-system.mockup.html`. Server flows, promises, forms, and CSRF behavior are unchanged — every existing call site keeps its original payload and lifecycle; only the presentation layer and explicit confirm "type" (danger/action) declarations were migrated.

## Public API

Primary (`window.CB`):

| Method | Signature | Returns |
|---|---|---|
| `CB.confirm` | `({ type: 'action'\|'danger', title, message?, icon?, chip?, context?, summary?, typeToConfirm?, confirmLabel?, cancelLabel? })` | `Promise<boolean>` |
| `CB.dialog` | `({ type: 'success'\|'warning'\|'info', title, message?, chip?, context?, okLabel?, icon? })` | `Promise<void>` |
| `CB.prompt` | `({ title, label?, placeholder?, confirmLabel?, cancelLabel? })` | `Promise<string\|null>` |
| `CB.modal.open/close` | `(elOrId, opts?)` / `()` | generic form-modal host |
| `CB.toast` | `(type, title, message?, opts?)` — `success\|error\|warning\|info\|system` | `void` |
| `CB.busy` / `CB.busyStop` | `(label?)` / `()` | processing overlay |

Legacy bridges (all preserved, now delegate to CB):

- `window.feedback.{ toast, openConfirm, openPrompt, alert, confirm, prompt, initFlashes }`
- `window.fbConfirmSubmit(event, msg, opts?)`, `fbConfirmButton(event, msg, opts?)`, `fbPromptForm(event, msg, opts?)`, `fbConfirmOnly(event, msg, opts?)`
- `window.atlasToast(msg, type)` (favourites.js Undo flow unchanged)

`opts.type` (`'danger'` / `'action'`) selects the confirm accent: danger → `cb-btn--red`, action → gold `cb-btn--cta`. Inline handlers that previously typed themselves via the old `feedback.openConfirm({variant})` keep working; both map to `CB.confirm`.

## Behavior

- **Scrim** — teal gradient `#11454B→#0C3539→#0A2E32` + radial glows, `backdrop-filter: blur(6px)`, `inset:0`, click-outside cancels.
- **Dialog card** — `min(430px, calc(100vw − 32px))` (`.wide` → 520px, `.processing` → 300px), r20, white .94 + blur 14, scale .96→1 in 180ms; halo icon tile, fact strip (mono chip `#11454B` + context), optional summary rows, type-to-confirm gate (OK disabled until the phrase is typed; Enter submits), focus trap + initial focus on cancel (or type field), Esc/close/X cancel (ignored while a `--processing` overlay is up). `role="alertdialog"` + `aria-labelledby="cb-dialog-title"`, `aria-modal="true"`.
- **Toasts** — r16, white .92 + blur 14, 4px left accent bar, 30px icon tile, action button (favourites Undo), close button, pause-on-hover, auto-dismiss ok/info 4s · warning 6s · error persists; max 4 stacked. Viewport pins `right:20px; top:calc(var(--nav-h,106px) + 12px)`; at ≤520px it becomes full-bleed with 12px gutters.
- **Busy** — scrim + 36px spinner + label ("…Please wait…"), `role="status"`, reference-counted (`busyDepth`); `busyStop` unwinds to zero.
- **a11y** — `prefers-reduced-motion` disables scrim/dialog/toast animations (spinner slows to 2s); body locked (`overflow:hidden`) while anything is open; focus restored to the previously-focused element on close.

## z-index map (targets in `docs/dialog-system.mockup.html`)

| Layer | z-index |
|---|---|
| topbar (two rows, 106px) | 60 (was 30) |
| sticky form-page heads | 40 (was 20) |
| scrim | 80 |
| dialog cards | 85 |
| toast viewport | 95 |

Toasts sit 12px below the topbar (`calc(106px + 12px)`) and always above it (95 > 60). No on-page stack (nav, sticky heads) can paint over the scrim.

## CSS

Replaced the `.fb-toast-viewport` / `.fb-toast*` / `.fb-confirm*` / `.fb-btn*` block in `resources/css/app.css` with the `.cb-*` executive-teal block (viewport, toast + 4 variants + leaving, scrim + leaving, dialog + wide/processing/form + leaving, halos, fact strip, summary, field/input, actions, buttons ghost/cta/sec/red/warn, spinner, reduced-motion, ≤520px). Re-tokenized in place (no markup changes):

- `.fb-alert` — inline alert component colors now `--deep-1`-accented / mint check / red; `.fb-alert--{info,success,warning,error}` retained.
- `.fb-banner` / `.fb-banner__dismiss` — system banner kept, navy-tinted to `--deep-*`.

New `:root` tokens: `--red-2`, `--warn-2`, `--shadow-dlg` (dialog shadow `0 30px 80px -20px …`). All z-index changes are in `app.css` (`.topbar` z 60, `.form-page-head` z 40); nothing depends on Tailwind ordering.

## Call-site migration (67 replacements, 43 files)

All Blade call sites were migrated with identical messages/payloads, adding an explicit `{ type: … }`:

| Category | Count | Result |
|---|---|---|
| `fbConfirmSubmit` inline `onsubmit` | 30 | 17 danger / 13 action |
| `fbConfirmButton` inline `onclick` | 14 | 9 danger / 5 action |
| `window.feedback.openConfirm({…})` (promise `.then`) | 7 | `CB.confirm({type,…})` |
| `window.feedback.openPrompt({…})` | 1 | `CB.prompt({…})` (expenses withdrawal reason) |
| `window.feedback.alert('…')` | 12 | `CB.toast('error', '…')` |

Destructive flows (voids, cancels, deletes, reverses, deactivations, suspensions) declared `type:'danger'`; posting/approving/locking/archiving flows `type:'action'`. `window.feedback.toast` stays in use by `layouts/app.blade.php` (flash forwarder) and `favourites.js` (Undo).

### Not migrated (kept by design)
- POS checkout dynamic JS/Alpine state classes, `pos/cashier/login` (standalone page, no app CSS), `admin/system-health` diagnostics list — pre-existing per the feedback-phase decisions.
- `x-feedback.flashes` component (renders `#feedback-flashes[data-flashes]`, consumed by `CB`-backed `initFlashes`); `status` key still excluded.
- `x-feedback.alert` Blade component — renders `.fb-alert` with `$attributes` merge; now styled via the re-tokenized `.fb-alert`.

## Bug fixed during E2E

`mountDialog()` guarded on any `.cb-dialog` element, but the busy overlay also renders a `.cb-dialog--processing` node that `busyStop()` removes after a 180ms animation. Opening any dialog immediately after `busyStop()` (within that window) silently failed. Fixed: the guard now purges `.cb-scrim--leaving` wrappers first and ignores `.cb-dialog--processing` when checking for an existing interactive dialog.

## Verification

- `php artisan view:clear` + `npm run build` clean; compiled CSS contains all `.cb-*` tokens and no stale `.fb-*` dialog/toast classes; `.topbar` z 60 and `.form-page-head` z 40 confirmed in the emitted CSS.
- `php artisan view:cache` compiles all ~330 views.
- Tests: Auth (21), ScopedSearchRenderSmokeTest (1), TodoTask+SuperAdminPanel (49), Budget/Cheque/Expense/PeriodLocking/Reconciliation (33), BranchRequest+TodoTask (30) — all green.
- Headless Chrome (1440/1280/1024/768/375): confirm (danger + type-to-confirm enable + Enter), Esc-cancel with initial focus on cancel, action confirm focus-trap, info dialog (Esc close), prompt (value + cancel), busy overlay + `busyStop`, processing confirm→busy hand-off, legacy `fbConfirmButton(event, msg, {type:'danger'})` inline-attribute proof, toast geometry (`right:20, top:118, w:360, z:95`, bg rgba(255,255,255,.92)), topbar z 60. No JS errors on migrated pages (the `/favourites` 401 poll and the accountant dev-user 403s on `/todo`, `/accounting/periods` are pre-existing permission gaps, unrelated to this change).
