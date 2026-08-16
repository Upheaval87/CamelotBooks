# System-Wide Typography & PDF Branding — Final Report

**Branch:** `feature/typography-branding` · **Period:** Aug 15–16, 2026 · **Commits:** `3e3a3ab` → `004992d`

## Objective

Normalise the whole app onto a single typeface (Inter) with a spec-compliant type scale, make every user-facing print/PDF render with the shared §6.1 `.cbp-*` branding chrome, add a per-user font-size preference, and ship an accessibility guardrail pass over the new typography. Strictly visual — no data, route, or permission changes; tests re-run at each phase.

---

## Phase 0 — Baseline (commit `3e3a3ab`)

Pre-spec cleanup done before the design work so later phases touched a clean tree:

- **Font-scale preference (A−/A+)** replaced the earlier `text-size` control:
  - `users.font_scale` (migration `2026_08_15_000002_add_font_scale_to_users_table.php`), steps `[0.85, 1.00, 1.15, 1.30, 1.50]` (`User::FONT_STEPS`), default `1.00`.
  - Applied as `style="--font-scale: {value}"` on `<html>` in `layouts/app.blade.php`; `html { font-size: calc(15px * var(--font-scale, 1)) }` in `app.css` — the accounting UI's 15px base is the scale anchor, so every rem-sized utility scales together.
  - Live control in topbar Row 1 (`resources/js/font-scale.js`, segmented pill `.topbar-font-scale*`), JSON POST to `preferences.font-scale` (outer auth group — works from tenant pages and the super-admin panel), optimistic with rollback.
  - `text-size.js`/`TextSizePreferenceTest` renamed to `font-scale.js`/`FontScalePreferenceTest`.
- **Figure/typography sweep**: mass figure font-weight/leading normalisation across blade views (`accounts/show`, `bills/_form`, bank-reconciliation screens, POS, etc.).
- `PreferenceController` split → `UserPreferenceController`; `User` `$fillable` + `float` cast.

## Phase 1 — Single typeface via `--font-sans` (commit `58e6f2c`)

- Swapped the `fonts.bunny.net` links in both `layouts/app.blade.php` and `layouts/auth.blade.php` for **Google Fonts Inter `wght 400;500;600;700;800`** with `preconnect`.
- Defined `--font-sans` **once** in `:root`; `--font-serif` now aliases it.
- Replaced all **50 literal font-family stacks** in `app.css` (Inter / ui-monospace variants) with `var(--font-sans)` so no second typeface survives on screen.
- Verified: no `font-family` other than `var(--font-sans)` in the compiled bundle.

## Phase 2 — Global web type-scale weights (commit `00a67fb`)

Normalised the global heading/table/button weights to the spec in `app.css` (sizes stay on the existing rem scale so they remain font-scale aware; suite-scoped overrides still win):

- `h1` 800 / `-0.01em` tracking, `h2` 800, `h3` 700.
- `th` 800 + `0.09em` tracking, colour `#5f7476`.
- `.btn` weight 500 → 600; `.kpi-value` 800; pill badges 800 + `0.05em` tracking.

## Phase 3 — PDF type scale + tabular-nums (commit `3ac8253`)

Applied the spec §4 PDF type scale and tabular-numeral alignment to every PDF template and the accounting print-export shell:

- `pdf/document.blade.php`, `accounting/print-export.blade.php` (27 lines retuned).
- `invoices`, `sales-receipts`, `sales-orders`, `quotations`, `inventory-items`, `bank-reconciliation`, `cash-position` print views.
- Tabular numerals (`font-variant-numeric: tabular-nums`) on all amount columns so figures align in columns.

## Phase 4 — Shared §6.1 chrome partial + presenter mapping + mPDF Inter (commit `53fc415`)

- **New shared chrome partial** `resources/views/components/pdf/chrome.blade.php` — the `.cbp-*` branding block (navy-gradient head with CB monogram tile, company name, document-type eyebrow, number/date, gold accent bar; footer with page markers). Now `@include`d by all six browser-print templates (invoices, sales-receipts, sales-orders, quotations, inventory-items) plus the `print-export` PDF shell — one source of truth for the print brand.
- **`PdfPresenter.php`** (`app/Services/PdfPresenter.php`) — reworked the doc-type → field-map mapping (~288 changed lines): each doc type (quotation/invoice/sales-order/…) now resolves its `number/date/party/amounts/` keys through a single normaliser so the chrome partial receives consistent data.
- **`PdfController.php`** — presenter dispatch fixed.
- **mPDF Inter registration** (`EncryptedPayslipService`): Inter TTFs added under `storage/fonts/inter/` (8 weights incl. Italic) and registered with mPDF so encrypted payslip PDFs embed Inter instead of the fallback.
- **`config/dompdf.php`** committed (301 lines) — DomPDF options (fontdir → Inter, PDF/A metadata, PHP-mode enabled for the footer script).
- `ScopedSearchRenderSmokeTest` extended.

## Phase 5 — Real per-page footers (commit `2b0d69b`)

Both PDF engines now render genuine per-page footers instead of a single static line:

- **DomPDF** (`pdf/document.blade.php`): a `page_text()` footer drawn in PHP (`set_page()` + text at the page bottom) — left: document type + number, right: `Page N / total` with `{PAGE_NUM}`/`{PAGE_COUNT}` — enabled via `enable_php => true` in `config/dompdf.php`.
- **Browser-print** (all 6 print templates): CSS `@page` margin boxes (`@bottom-right { content: counter(page) " / " counter(pages) }`, `@bottom-left` doc label), replacing the old non-repeating footer content.
- DomPDF page-number colour `[18,143,142]` (later darkened in Phase 6).

## Phase 6 — Accessibility guardrails (commit `004992d`)

Contrast + motion + focus + size audit over the new typography:

- **Contrast-fixed tokens** (`:root` + `.auth-login` scoped block, `resources/css/app.css`): `--ink-soft`/`--muted` → `#52696B` (5.85:1 on white), `--ink-muted`/`--faint` → `#5F7A7C` (4.60:1 on white), `--gold`/`--sec`/`--acc` → `#107C7B` (5.01:1), `--sec-2`/`--acc-2` → `#0C7E7D` (4.89:1), `--sec-3` → `#0C3539`, `--gold-500` → `#107C7B`, `--gold-600` → `#0C6B6A` (6.31:1 white-on-fill). Color hierarchy preserved (each step still darkest→lightest).
- **Bulk sweep of hardcoded literals** (ordered `.Replace()` passes over app.css): every `#128F8E`/`#149897`/`#5F7476`/`#8AA5A7` fallback and all 3/2-stop teal gradient stops, `var(--sec/#128F8E)`-style fallback pairs (400+ replacements), `rgba(18,143,142,…)` solids → darkened equivalents. Zero stale literals remain (grep-verified); translucent tints kept (intentional backgrounds/borders/glows).
- **Tailwind `gold` scale re-sequenced** (`tailwind.config.js`): `DEFAULT`/`500` → `#107C7B`, `600` → `#0C6B6A` (was 3.93:1 with white text — now 6.31), `700` → `#0B5C5B` so `hover:bg-gold-700` still darkens relative to the new `600`. Fixes `bg-gold-600 text-white` buttons app-wide (POS checkout Proceed, aging summary/detail, fixed-assets, recurring-journals, stock-counts, cashier login, companies index).
- **Font-size floor**: sub-10px micro-labels bumped to 10px (`q-fbox-lbl`, `va-fmt`, cp2 `fstep h3`/`thead th`). One 9px item remains in the do-not-touch Phase-50 `.suite` WIP block (`suite .p-doc .l`). All remaining 10px items are intentional uppercase micro-labels.
- **`:focus-visible` outlines** appended for the q2/sr/ss/cs/pr/ex suites: `outline: 2px solid var(--sec, #107C7B); outline-offset: 2px` on interactive elements (the suite blocks previously had no focus treatment; base `--focus` `#94a3b8` ≈ 2.6:1 is left only where pre-existing).
- **`prefers-reduced-motion`** blanket coverage for the same suites (`animation-duration/transition-duration: 0.01ms !important; scroll-behavior: auto`), now covering q2/sr/ss/cs which had none.
- **Print views darkened** to match: inline `:root` tokens in all 7 print templates (`--sec`/`--acc` → `#107C7B`, muted/faint darkened) and the DomPDF page-number colour `[18,143,142]` → `[16,124,123]` (best-effort for print, not WCAG-bound).
- Print views are self-contained (own inline `:root`) — app.css changes deliberately do not reach them.

## Verification

- `php artisan view:cache` clean after every phase (all ~330 views compile).
- `npm run build` after every CSS change; compiled bundle grep-verified for the new tokens (stale-bundle lesson from the quotations phase applied each time).
- **Test suites green at Phase 6 close:**
  - `FontScalePreferenceTest|ListPageRenderTest|CashPositionTest` — **19 passed / 141 assertions**
  - `Payroll|Payslip` — **33 passed / 73 assertions**
  - `ScopedSearchRenderSmokeTest` — **passed** (renders every converted route as company_admin, ~162 s)
- Pre-existing failure, unchanged (out of scope): `ReportRenderSmokeTest` 76-vs-77 at `tests/Feature/Accounting/ReportRenderSmokeTest.php:271`.

## Out of scope (unchanged)

7 financial-statement fragments, `welcome.blade.php` (Laravel starter, unrouted), the POS thermal receipt, payroll print views, `bank-reconciliation/print` and `cash-position/print` layout restyle (tokens darkened only). Full test suite exceeds the 10-min shell timeout — run in slices with `--filter`.

## Follow-ups

- SuperAdmin `UsersController` creation still uses `Password::defaults()` rather than the per-company policy (pre-existing, unrelated).
- `.suite .p-doc .l` 9px label inside the Phase-50 WIP block — revisit when that suite ships.
- Base `--focus` (`#94a3b8`) remains in the cp2 `:focus-visible` at app.css `:9016` (pre-existing pattern) — suites introduced during this work use the passing `var(--sec)` ring instead.
