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

---

# Appendix A v2 — shared shell consolidation + native call-site migration

Follow-up phase. Frontend-only; no routes, controllers, validation, payloads, or server behavior changed. The authoritative design is the Appendix A block in `resources/css/app.css` (`:15391`, "DIALOG SYSTEM v2 — APPENDIX A shared shell").

## Shared shell

- `resources/views/components/modal.blade.php` rewritten onto `.dlg-scrim` / `.dlg-card`. Contract preserved: `name`, `show`, `maxWidth`, `variant`, optional `focusable`, `open-modal`/`close-modal` events, close bubbling, existing slot classes. `maxWidth` maps to shell width (`sm`→`dlg-card--sm`, `lg`→`dlg-card--edit`, `xl`/`3xl`/`4xl`→`dlg-card--list`, default otherwise).
- Bands are DIRECT children of `.dlg-card--alpine`: `.dlg-head` (deep-teal radial gradient; `dlg-head-ic` 32px tile, `dlg-head-txt`, `dlg-head-title`, `dlg-head-x`, `dlg-head-pill`) → `.dlg-body` (`22px 24px`, scroll) or `.dlg-body--flush` → `.dlg-foot` (`justify-content:flex-end`, Delete `mr-auto`).
- HARD RULES enforced: zero `backdrop-filter` on any scrim/card; scrim is a tint only (`--dlg-scrim: rgba(11,42,45,.26)`), never gradient/blur; header band has title + pill only (no eyebrow/company/subtitle); no red left/danger bars.

## Layer stack, focus, scroll lock

- `DialogLayer` (`resources/js/feedback.js`): `push(close, opts)` → `{close, onEscape}`, `drop`, `top`, `count`; exported as `window.DialogLayer`.
- Fixed layers: sticky `40`, nav `≥60`, dialogs `90`, confirmations `140`, global search `200`, toasts `220`. Escape affects only the top-most visible registered layer; the busy overlay registers a layer and swallows Escape (non-dismissible).
- Refcounted body scroll lock shared by all layers. `resources/views/components/modal.blade.php` registers/drops via `x-effect` → `syncLayer()` and holds the layer handle on `this.$el.__dlgLayer` (Alpine `$watch`/multi-statement-`x-effect` gotchas documented). `resources/js/global-search-modal.js` registers with `DialogLayer` (`this.$el.__gsLayer`); the pre-existing Escape fallback is gated on `!window.DialogLayer`.

## Migrated interiors

- **My Tasks** (`layouts/app.blade.php:126-143`) → `.dlg-head` + `.dlg-body` (keeps `x-data="todoModal()"`, `open-modal`/`todo-delete`/`todo-refresh` listeners, `x-ref="wrap"`/`list`); "Personal" eyebrow dropped.
- **Edit/View Task** (`layouts/app.blade.php:151-255`) → head (priority dot + form-associated `title` input + deadline pill) / `<form id="task-update-form" class="dlg-body">` (`@item-selected`, 4 sections, scoped-search `link_picker`, hidden `linkable_*`/`deadline_*`) / foot (Delete `mr-auto`, Cancel, form-associated submit). Added `.dlg-head .todo-modal-title-input` styling (`app.css:15597`).

## Legacy scrim HARD-RULE compliance

- A consolidated override block (`app.css:16080-16107`) already neutralizes all legacy suite scrims (`.wz-modal-backdrop`, `.pd .pd-modal`, `.coa2-modal`, `.rpc-modal-bg`, `.sr-suite .sr-modal-overlay`, `.dp2-suite .dp2-modal`, `.jr-modal`, `.pos-m-modal-overlay`, `.dp .dp-modal-overlay`, `.pos .pos-overlay`) via `background: var(--dlg-scrim) !important; backdrop-filter: none !important; z-index: var(--z-dialog) !important`.
- Additionally stripped dead blur/gradient at source for `.pos .pos-overlay`, `.sr-suite .sr-modal-overlay`, `.dp .dp-modal-overlay`. `.dp .dp-modal` left `z-index:85` (minimal risk). Remaining `backdrop-filter` in CSS/views are page surfaces (auth glass, sticky heads, page `.card`s, icon tiles), not scrims.

## Native confirm/prompt call-site migration

Every remaining native `confirm()`/`prompt()` and the two dead `data-fb-confirm` attributes were routed through the CB bridges/`CB.prompt`:

| Site | Bridge | Type |
|---|---|---|
| `pos/mobile/ret-register.blade.php:94` (Void BRR) | `fbConfirmSubmit` | danger |
| `pos/returnables/show.blade.php:12`, `returnables/index.blade.php:102` (Void BRR) | `fbConfirmSubmit` | danger |
| `cost-centers/index.blade.php:56` (Toggle) | `fbConfirmSubmit` | action |
| `exchange-rates/index.blade.php:50` (Delete) | `fbConfirmSubmit` | danger |
| `report-schedules/index.blade.php:64` (Delete) | `fbConfirmSubmit` | danger |
| `fiscal-years/show.blade.php:9` (Lock all), `:53` (Lock period) | `fbConfirmSubmit` | action |
| `periods/index.blade.php:53` (Lock), `:58` (Reopen) | `fbConfirmSubmit` | action |
| `rj/index.blade.php:86` (Delete recurring journal) | `fbConfirmButton` | danger |
| `rj/templates.blade.php:52` (Delete template) | `fbConfirmButton` | danger |
| `payroll/distribution-finalized.blade.php:26` (Finalize all) | `fbConfirmButton` | action |
| `payroll/distribution-validate.blade.php:32` (Send payslips) | `fbConfirmButton` | action |
| `reversals/rules.blade.php:117` (dead `data-fb-confirm` → Delete rule) | `fbConfirmSubmit` | danger |
| `reversals/auth-show.blade.php:145` (dead `data-fb-confirm` → Reject; form-level) | `fbConfirmSubmit` | danger |
| `rj/approvals.blade.php:34` (buggy always-truthy → Post) | `fbConfirmSubmit` | action |
| `rj/approvals.blade.php:41` (Reject reason) | `CB.prompt` (IIFE, keeps empty-check) | — |

- `data-fb-confirm` had **no JavaScript consumer** — both usages were broken (the guard never ran). Now fixed.
- Verification: no inline `return confirm(` / `=prompt(` / `data-fb-confirm` remain in `resources/views`. JS `window.confirm` fallbacks are intentionally kept (`todo.js:223`, `todo-modal.js:68`, `permissions-console.js:107`).

### Constraint-validation caveat

The bridges submit via `form.submit()`, which BYPASSES HTML5 constraint validation. A handler placed on the button `onclick` therefore skips `required`/`minlength`. `onsubmit` handlers run AFTER constraint validation, so form-level handlers preserve it. `reversals/auth-show.blade.php` (reject form with `required minlength="10"` textarea) was moved to a form-level `onsubmit` and the button `onclick` removed. The other `fbConfirmButton` targets sit in forms with only `@csrf`/`@method` (no required fields) — harmless.

## Verification (this phase)

- `php artisan view:clear` + `view:cache` clean; `npm run build` → `app-DZ1P9zBN.css` (853.98 kB, `--dlg-scrim`/`--z-dialog`/`backdrop-filter:none` and all `.dlg-*` tokens present) + `app-Cu4ZjM8V.js`.
- Headless Chrome probes: `dlg-probe` 54/54, `dlg-todo` 21/21, `dlg-focus` 9/9, `dlg-layer2` 8/8, and a new `dlg-migrate` 9/9 (real server-rendered `fbConfirmSubmit` form, plus synthetic `fbConfirmButton`/`fbPromptForm` — each opens the shared `.dlg-card`, Escape dismisses, `Page.javascriptDialogOpening` never fired = no native dialog, zero console errors).
