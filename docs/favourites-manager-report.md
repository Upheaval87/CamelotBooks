# Favourites Manager Popover + My Tasks — Implementation Report

Source spec: `docs/Favouritesmanager-popover.txt` (Appendix A = My Tasks page, Appendix B = Favourites popover).
Scope: frontend-only rebuild of both surfaces; no controller/model/route/validation/payload changes.

## 1. Files touched

| File | Change |
|---|---|
| `resources/css/app.css` | Appended `.myt` block (Appendix A tokens + page) `:16142`– and `.favpop` block (Appendix B tokens + popover) `:16358`–`. Tokens scoped locally on the two roots (did not alter global `:root`). |
| `resources/views/todo/index.blade.php` | Rewritten to Appendix A (root `.myt x-data="todoBoard()"` `:25`; stats, command bar, tabs/chips, `.grid-x` main + rail, server `@if` empty states + client no-match states `:198`/`:227`, sections `x-show="sectionHas($el)"` `:185`). |
| `resources/views/todo/_task-row.blade.php` | Rewritten. `$modal` branch = exact legacy modal row; page branch = Appendix A task card. Preserves `title: @js($task->title)`, `todo.complete`, `todo-delete`; adds `data-status/title/priority/overdue/bucket/granularity` + `x-show="matchesRow($el)"` `:81-88`. |
| `resources/views/todo/_task-row-completed.blade.php` | Rewritten, same `$modal` branch split; page root `data-status="completed"`; delete form posts `todo.destroy` + `@method('DELETE')` via `fbConfirmSubmit`; trash SVG `:104` intact. |
| `resources/views/components/favourites/dropdown.blade.php` | Rewritten to Appendix B (root `.relative.fav-dropdown-wrap` + `x-data="{ store: $store.favourites }"`; keeps `.fav-star-trigger`/`.fav-count`/`@click.outside`/`x-cloak`; trigger now `@click.stop="store.toggleDropdown()"` `:7`; panel `.favpop` with head/controls/body/foot). |
| `resources/js/todo.js` | Added `Alpine.data('todoBoard', …)` at tail (`tab`, `q`, `chip`, `matchChip`, `matchesRow`, `sectionHas`, `anyMatch`). No existing export/handler changed. |
| `resources/js/favourites.js` | Extended store: `filter`, `currentLabel/Icon/Url`, computeds `pinnedItems`/`availableItems`/`pinCount`/`availCount`, `loadPages()`, `unpinAllWithConfirm()`, `unpinAll()`, `toggleDropdown()` reset+load, `favouriteToggle.init()` sets `store.current*`. |

Not touched: controllers, models, routes, migrations, `FavouritesService`, `TodoTaskController`, `favourite-toggle.blade.php` Blade, `favourites/sidebar.blade.php`, task modal partial.

## 2. Endpoint / param map (control → existing handler)

Tasks (`todo.*`, `routes/web.php:1331-1341`):

| Control | Method / URI | Handler |
|---|---|---|
| Page load | `GET /todo` | `TodoTaskController@index` |
| Open task (modal fragment) | `GET /todo/modal` | `TodoTaskController@modal` |
| Quick-add (Enter + button) | `POST /todo` | `@store` |
| Edit/save | `PUT /todo/{task}` | `@update` |
| Complete checkbox | `POST /todo/{task}/complete` | `@complete` |
| Reopen | `POST /todo/{task}/reopen` | `@reopen` |
| Delete (confirm dialog) | `DELETE /todo/{task}` | `@destroy` |

Favourites (`favourites.*`, `routes/web.php:1344-1353`):

| Control | Method / URI | Handler |
|---|---|---|
| Load store (rail + counts) | `GET /favourites` | `FavouritesController@index` |
| Load pin catalogue | `GET /favourites/pages` | `@pages` |
| Pin | `POST /favourites` | `@store` |
| Unpin (tile / "Unpin all") | `DELETE /favourites/{pageKey}` | `@destroy` |
| Reorder | `PATCH /favourites/reorder` | `@reorder` |
| Master switch (§4.7) | `PATCH /favourites/preferences` | `@preferences` |

All payloads, params, CSRF flows and ids/names/data-attributes are unchanged pre/post.

## 3. Preference shape + endpoint (§5)

- Reuses the **existing** rail-preference record — no parallel store.
- Spec shape `{"rail_visible":bool,"pinned":["<routeKey>",…]}` maps onto existing storage:
  - `rail_visible` → `user_preferences.sidebar_pinned` (boolean; `UserPreference` primaryKey `user_id`), written via `PATCH /favourites/preferences`.
  - `pinned[]` → `user_favourites` rows (one per pinned route key), written via `POST /favourites` / `DELETE /favourites/{pageKey}`.
- Server-backed per user, so it survives reload and re-login and is consistent across devices. localStorage fallback and 300ms debounce/optimistic-rollback were not required by the live implementation (existing store already loads server-side pre-paint); no new endpoint created.

## 4. Rail registry notes

- Registry: `App\Services\FavouritesService::PAGES`; page keys resolved by `metaForRoute()`, record keys by `metaForRecord()`.
- `my-tasks` is always pinned and never offered in the picker.
- Cap `MAX_FAVOURITES = 20`; live store showed 20 user pins + My Tasks = "21 pinned". Pin attempts at cap early-return with toast `You have reached the maximum of 20 favourites.` (expected, not a bug).
- Popover operates even when the rail is hidden on a page by registry rule.

## 5. §0.4 / §6 — before/after control audit

| Surface | Control | Before | After |
|---|---|---|---|
| My Tasks | Quick-add | input + button | same handlers; restyled command bar (Enter + button) |
| My Tasks | Complete/reopen | checkbox | same; restyled checkbox |
| My Tasks | Delete | confirm → `todo.destroy` | same confirmation dialog (`fbConfirmSubmit`, semantic confirm) |
| My Tasks | Open task | approved task modal | unchanged modal (`todo.modal` fragment) |
| My Tasks | Filters/search/counts | tabs + chips + counts | same controls; live counts from the same payload |
| Favourites | Trigger pill + count | `.fav-star-trigger` + `.fav-count` | same trigger, now `.favpop` anchored panel |
| Favourites | Pin/unpin | sidebar/picker | managed in popover; same endpoints |
| Favourites | Rail master toggle | `sidebar_pinned` toggle | popover master switch writes the SAME flag |
| Favourites | Unpin all | — | new "Unpin all from sidebar" → per-key `DELETE` after danger confirm |

No control was renamed or removed; every hooked selector remains.

## 6. Verify — results

- `php artisan view:cache` clean; `npm run build` → `app-D5eMNZK6.css` + `app-CXWJpbRK.js` (contains `favpop`×88, `favpop-tile`×14, `.myt`×205, `todoBoard`).
- **My Tasks (headless, company 1):** 3 active + 1 completed rows, 3 sections, tabs switch, Overdue chip filter narrows correctly, search narrows to 1, no-match empty states present.
- **Popover (headless):** opens 430px, 21 pinned / 93 available; filter (`customer` → 2); unpin → `DELETE /favourites/dashboard` 200 (21→20); pin → `POST /favourites` 200 (20→21); master switch `PATCH /favourites/preferences` 200 (off/on); Esc closes.
- **R1/R2/R3 grep proofs:** zero `backdrop-filter` inside `.myt`/`.favpop` (all hits are the dialog block forcing `none !important`); no company eyebrow in the popover head; zero red left/vertical bars on task rows (only a teal eyebrow dash and a `--line` timeline connector).
- **Tests:** `FavouritesTest` 17 passed; `TodoTaskTest` 16 passed / 1 stale failure (`test_dashboard_exposes_personal_task_counts` asserts old `todoOverdue`/`todoToday` view vars removed by the earlier BI/dashboard rebuild, commit `69b9d17` — pre-existing, unrelated, left as-is by decision).

## 7. Confirmation

NO SECTION SKIPPED. §0 discovery/§0.4 audit, §1 tokens, §2 My Tasks, §3 popover, §4 rail wiring, §5 persistence (existing record), §6 constraints, §7 z-index, §8 verification all addressed.
