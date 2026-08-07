# CamelotBooks Design System

Reference implementation: the Super Admin panel (`resources/views/superadmin/*`), sourced from
`resources/views/components/superadmin/*` + `resources/views/components/form-section.blade.php`.

Every page in the app is being migrated onto this visual language. This document is the single
source of truth for the patterns; shared components are preferred over hand-rolled markup.

---

## 1. Page shell

- Outer layout: `<x-app-layout>` (topbar, favourites sidebar, page-title slot).
- Page title slot: `<x-slot name="header">Plain Title</x-slot>` — rendered by the layout.
- Section shell (two-column with left nav): `<x-superadmin.layout>` → grid
  `lg:grid-cols-[252px_1fr]`, sticky glass aside (`rounded-[20px] bg-white/[.66] p-3 shadow-card
  backdrop-blur-[14px]`) hosting `<x-tab-bar :active="...">`. Single-column pages can skip the
  aside and use the main column directly.
- Page head: `<x-superadmin.page-head title description>` — H1 `text-2xl font-extrabold
  tracking-[-0.02em] text-gray-900`, optional `badge` slot (inline pill), description
  `mt-1.5 text-sm text-gray-500`, trailing `action` slot (primary button).
- Content max width: `max-w-8xl mx-auto` (already provided by `app.blade.php` main).

## 2. Cards

`<x-superadmin.card>` → `rounded-3xl bg-white/[.66] p-6 shadow-card backdrop-blur-[14px]`.
Optional `title` prop renders header `mb-4 flex flex-wrap items-center justify-between gap-3`
(title `text-[15px] font-extrabold text-gray-900`, `action` slot right).

- Form sections use `<x-form-section icon title columns action>` → `rounded-3xl bg-white/[.66]
  p-[26px] shadow-card backdrop-blur-[14px]`, header `flex items-center gap-3`, icon tile
  `h-7 w-7 rounded-[9px] bg-gradient-to-b from-[#2e4763] to-[#22394f] text-[#e2c069]`,
  hairline `h-px flex-1 bg-line`, grid from `columns` prop (2/3/4). Fields wrapped in
  `.form-field` (`.form-field--full` spans grid).
- KPI cards: `<x-superadmin.card>` containing `.kpi-label` (11px uppercase slate) + `.kpi-value`
  (large extrabold) + caption `mt-1 text-[13px] text-gray-500`. Grid `grid gap-4 sm:grid-cols-3`.

## 3. Datasheets (tables)

- Wrapper: `overflow-x-auto rounded-[12px] border border-shell bg-row`
- Table: `w-full min-w-[960px] border-collapse text-sm`
- Header row: `<x-superadmin.th>` → `bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900
  px-5 py-4 text-[11px] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead`,
  `align` prop (left/right/center).
- Body: `<tbody class="divide-y divide-line">`; cells `px-5 py-[18px] align-middle`;
  primary/name column is a link `font-bold text-gray-900`; secondary text `text-gray-600`;
  meta/code `code.rounded-md.border.border-slate-200.bg-slate-100.px-2.py-[3px].font-mono.text-xs`.
- Empty state: single `<tr><td colspan class="px-5 py-[18px] text-center ... text-gray-400">`.
- Actions column: `text-center`, per-row edit button (see §5 ghost/edit), no bare text links.

## 4. Status / badges

`<x-superadmin.badge variant>` → `inline-flex items-center rounded-full border px-3 py-1.5
text-xs font-bold shadow-badge`. Variants: `active` (mint + green dot), `muted` (gray),
`warning`, `danger`, `accent`/`core` (gold-soft), `navy`. Never color-only — dots/text carry meaning.

## 5. Buttons

`<x-superadmin.btn>` variants: `primary` (gold gradient `shadow-new`), `edit` (soft gold,
`shadow-edit`), `ghost`, `danger`. Sizes: `lg` `rounded-[12px] gap-2 px-5 py-3 text-sm
font-semibold`; `md` `rounded-[10px] gap-1.5 px-4 py-2 text-[13px] font-bold`. Prop `href`
renders `<a>`, otherwise `<button>` (`type` default `button`).

## 6. Forms

- Page wrapper: `mx-auto flex w-full max-w-[1080px] flex-col gap-[22px]`
- Sections: `<x-form-section>` (see §2). Required fields: red asterisk inside `.sa-label`.
- `.form-field` grid; `.form-field--full` for wide fields.
- Inputs: `.sa-label` (11px uppercase #64748b) + `.sa-input` (40px, rounded-12, gold focus ring)
  or `.sa-select`/`.sa-checkbox`/`.sa-textarea`. Errors: `<x-input-error>`.
- Actions footer: `.sa-form-actions` (left ghost "Cancel", right primary submit).
- Edit pages reuse the create layout, add page-head `badge` (active/deactivated etc.) and
  `old('x', $model->x)` values.

## 7. Detail / show pages

- Title inside card: `text-[26px] font-extrabold tracking-[-0.02em] text-gray-900` + inline
  badges; actions top-right (`edit` button + `danger` form).
- Detail fields: label/value rows with `.sa-label`-style labels, `text-sm text-gray-600` values,
  mono `code` for references/numbers. Sections as `<x-form-section>` or `<x-superadmin.card>`.
- No `record-toolbar`/`tr-*`/`detail-field` legacy classes on migrated pages.

## 8. Review / decision pages

Already migrated (AGENTS.md item 32): `<x-review.head/card/field/badge/decision/outcome/btn>`.
Same glass/navy/gold language; keep those components.

## 9. Tokens

- `--sa-accent`, `--sa-accent-tint`, `--sa-border`, `--sa-muted` CSS vars in `app.css`.
- Tailwind palette tokens in use: `navy-700/800/900/200`, `gold-500/600/700/800`,
  `line`, `shell`, `row`, `shadow-card`, `shadow-thead`, `shadow-edit(-hover)`, `shadow-new`,
  `shadow-badge`. Any new utility must be verified in `npm run build` output before use.

## 10. Rules

- Never mix legacy `.list-*`, `.datasheet`, `.detail-grid`, `.tr-*`, `.record-toolbar`,
  `.icon-btn`, `.status-pill`, `.btn-*`, `.card p-6` patterns on converted pages.
- Blade directives must never appear inside component attribute expressions (`:prop="[@if...]"`);
  precompute arrays in `@php`.
- Empty-state rows, right-aligned numeric columns, and status semantics must be preserved.
