# AGENTS.md

## Commands
- No test/lint commands discovered yet

## State
All `resources/views/accounting/*/create.blade.php` and `resources/views/accounting/*/edit.blade.php` files have been transformed:

1. `bg-white overflow-hidden shadow-sm sm:rounded-lg p-6` → `card p-6`
2. Section `<h3>` headers → `<div class="form-section-label">N · TITLE</div>` (multi-section files)
3. `<select>` Tailwind classes → `class="input mt-1"`
4. Inline `<a>` cancel buttons → `<x-button variant="ghost" href="...">`
5. Inline "Add Line" `<button>` elements (ghost style) → `<x-button variant="ghost" type="button">`

### Files NOT in scope (not create/edit forms)
- Files in subdirectories like `components/`, `templates/`
- `show.blade.php`, `index.blade.php` files
- Files with `bg-white overflow-hidden...` that are NOT form pages (view-only pages, modals)
