# FULL IMPLEMENTATION PROMPT — COMPREHENSIVE VENDOR MODULE (INDEX + DETAIL + CREATE/EDIT)

## CRITICAL INSTRUCTION TO OPENCODE: DO NOT SKIP ANY ELEMENT, DO NOT SIMPLIFY, DO NOT REPLACE, DO NOT SUMMARIZE, DO NOT LEAVE OUT ANY PAGE, MODAL, TAB, FIELD, BUTTON, FILTER, SORT, ANIMATION, INTERACTION, VALIDATION, OR BACKEND FUNCTIONALITY IMPLIED BY THESE MOCKUPS.

You are acting as a senior Laravel/PHP enterprise architect, backend engineer, database designer, and premium UI/UX frontend developer.

Build a **comprehensive enterprise Vendor Module** for the existing PHP/Laravel ERP/accounting system, exactly matching the three authoritative HTML mockups embedded at the end of this prompt:

1. `vendors-index.html` — Vendor directory listing
2. `vendor-detail.html` — Single vendor profile
3. `vendor-create.html` — Full-page Create/Edit vendor form

These mockups are the **source of truth** for layout, visual design, spacing, typography, color tokens, gradients, glass surfaces, shadows, cards, tables, pills, modals, form fields, payment-method behavior, split-payment behavior, filtering, sorting, bulk actions, CSV export, validation states, animations, and micro-interactions.

You must redesign/implement **all necessary pages, modals, components, elements, workflows, validations, backend endpoints, database structures, policies, services, controllers, Blade views, JavaScript/Alpine behavior, tests, and seeders** required to make this module production-ready inside the existing system.

---

## ABSOLUTE NON-NEGOTIABLE REQUIREMENTS

1. **Do not skip any element from the mockups.**
2. **Do not remove any modal.**
3. **Do not remove any tab.**
4. **Do not remove any field.**
5. **Do not remove any button, chip, pill, star, checkbox, or icon.**
6. **Do not remove any animation** (fadeUp stagger, sheen, gauge draw, pulse dots, modal spring in/out, expand-in split panels, shake on invalid, checkPop, toastIn).
7. **Do not replace the custom design system** with generic Bootstrap/Tailwind/Material UI.
8. **Do not simplify the Record Payment modal** or its method-conditional panels.
9. **Do not simplify the Split Payment premium checkbox tiles.**
10. **Do not omit the three ageing-filter clear mechanisms** (header Clear button, active-filter chip ✕, KPI/view reset).
11. **Do not omit row-click navigation, keyboard support (Enter/Space), or column sorting.**
12. **Do not omit the bulk-action bar** or the real CSV export.
13. **Do not omit the credit-utilisation gauge** (∞ / "No Limit" state when limit = 0).
14. **Do not omit the create-page live preview, completion meter, terms presets, sticky action bar, or inline validation.**
15. **Do not move or restyle the faint bottom gradient hairline** on cards (it sits at the BOTTOM, centred, at reduced opacity).
16. **Currency is MWK with symbol "K"** everywhere. Never USD/$ as default.
17. **If the implementation is too large for one response, split it into multiple file writes, but still complete every required element.**
18. **No placeholders** like "add remaining fields here", "implement similarly", or "TODO".
19. **Every visible mockup element must map to a real artifact**: Blade component/partial, Alpine state, controller method, route, form-request rule, service method, migration column, policy ability, test case, or seeder record.
20. **Inspect the existing codebase first** and match its directory structure, naming conventions, base controllers, model traits (TenantScoped/SoftDeletes/etc.), middleware, and asset pipeline. Integrate — do not fork a parallel architecture.

---

## TARGET STACK

- Laravel 11+ (match existing), PHP 8.3+ (match existing)
- Blade templating; Alpine.js for interactivity unless Livewire is already the project standard (if Livewire, preserve identical visuals/behavior)
- Custom CSS token system from the mockups compiled through the existing pipeline (Vite/Mix)
- MySQL/PostgreSQL (match existing)
- Form Requests, Policies/Gates, Service classes, Feature tests, Seeders/Factories
- Multi-tenant/company-safe scoping if the system uses tenancy

---

## PAGE-BY-PAGE FUNCTIONAL SPEC

### A. Vendors Index (`vendors-index`)

- Chrome: topnav row 1 (deep-teal gradient + mint radial glow), module rail row 2 (Purchasing active), icon sidebar (Vendors active), breadcrumb.
- Page header: title, subtitle, **Export CSV** (real download of currently filtered rows), **Create Vendor** → navigates to create page.
- **5 clickable KPI tiles** (All / Active / Inactive / Overdue / Zero Balance) that filter the table; active tile ring-highlighted; "All" tile carries the teal gradient + animated sheen.
- **Payables Ageing card**: 5 buckets (Current, 1–30, 31–60, 61–90, 90+) with proportional fill bars; buckets clickable to filter; selected bucket gets inset ring; **Clear ageing filter** button appears in card header only while a bucket is selected; total-outstanding chip updates live.
- **Active-filter chip row** above toolbar showing `Payables: <bucket>` with ✕ clear button whenever a bucket is active.
- Toolbar: search (name/email/phone/code), segmented view control (All/Active/Inactive/Fav with counts), terms dropdown, Filter button, visible-count chip.
- Table: select-all + per-row checkboxes, favourite star toggle, avatar with status dot, name + "Since … · CODE" subtext, contact, phone (mono), email, terms pill, outstanding (mono, red when > 0), status pill, actions (View/Edit/Delete icon buttons).
  - **Whole row clickable → vendor detail page**; row focusable (`tabindex`), Enter/Space opens detail.
  - View icon → detail page; Edit icon → create page in edit mode (`?edit={id}`); Delete icon → delete-confirm modal.
  - Column headers sortable asc/desc with arrow indicators.
  - Striped rows, tint hover, selected-row tint, staggered row entrance.
- **Bulk action bar** (sticky) appears when ≥1 row selected: count badge, Activate, Archive, Message (opens message modal addressed to selection), Export (CSV of selection), Clear.
- Pagination footer.
- Right rail: **Views** card (All/Active/Inactive/Overdue/Zero Balance/Favourites with live counts; active item deep-gradient) and **Reports** card (Vendor Balances, Purchases by Vendor, Ageing Report, 1099/Tax Batch) — each link switches active state and fires a toast/preview action.
- Modals on this page: **Delete Confirm** (danger gradient header, irreversible warning) and **Message Vendor** (subject + message, send → toast).
- All modals: Escape close, backdrop close, body scroll lock, spring open / fade close.

### B. Vendor Detail (`vendor-detail`)

- Breadcrumb back to index; page header with Print, Export PDF, More, **Edit Vendor** (opens edit modal).
- **Hero card** (glass, centred faint bottom hairline): avatar, name + status pill + code chip, meta row (email, phone, terms, currency), **credit-utilisation gauge** (SVG donut, animated draw; ∞/"No Limit" when limit 0; Limit/Used/Avail readouts), Archive button (danger ghost → archive confirm modal), quick-action chips: New Bill, Record Payment, View Statement (switches tab), Print, Email Statement (message modal), Add to Favourites.
- **4-card KPI strip**: Open Balance (teal gradient + sheen), Total Billed, Opening Balance, Payment Terms.
- **Tabs card** (centred faint bottom hairline; gradient active underline): Information / Transactions / Statement / Payments / Notes.
  - Information: 3×3 info grid, billing + remit-to address blocks (empty states), recent-activity timeline.
  - Transactions: toolbar (search, type filter, date range), empty state with New Bill CTA, or table with type chips (bill/payment/credit/journal), mono amounts, status pills, clickable rows.
  - Statement: toolbar (date range, Export PDF, Email Statement), closing-balance readout, full debit/credit/balance ledger with totals row.
  - Payments: empty state with Record Payment CTA, or payments table.
  - Notes: toolbar with Add Note CTA, timeline of notes, empty state.
- Side panel: **Summary** card (opening/billed/paid/limit rows + gradient Open Balance block), **Quick Nav** card (New Bill, Record Payment, View Statement, Print, Email Statement, All Vendors — all wired), **Contact Details** card (email/phone/website + Edit link).
- Modals: **Edit Vendor** (wide; general/contact/billing/remittance/financial sections + active switch + delete in footer), **Record Payment** (mid 780px; method-conditional panels; split tiles; apply-to-open-bills; validation), **New Bill** (wide; line-item repeater; totals; dropzone; draft/save), **Add Note**, **Send Message**, **Archive Confirm** (danger header; nil-balance safe notice).
- Record Payment behavior (identical rules to customer module):
  - bank/mobile/card → Transaction Details panel (Reference*, Provider* with per-method option sets, Account Name, Account Holder) with ≥34px top spacing.
  - cash → Cash Handling panel (tendered/change).
  - split → four premium checkbox tiles (Cash/Bank/Card/Mobile); tick expands capture panel with animation; untick collapses but preserves values; per-tile amount preview; totals (Payment / Allocated / Unallocated) with info/ok/warn banners; post disabled until: method chosen, amount > 0, single-electronic has reference, split has ≥1 tile, |allocated − amount| ≤ 0.01, every electronic split tile has reference.

### C. Create / Edit Vendor (`vendor-create`)

- Dual mode: default create; `?edit={id}` loads edit mode (title, breadcrumb, save labels, prefilled fields, code locked).
- Page header: Cancel (back to index) + primary save (mirrors sticky bar).
- Four section cards (glass, centred faint bottom hairline): **Contact Information** (name*, display name, email w/ format validation, phone, contact person, website, billing address, remit-to with hint), **Classification** (code auto/disabled, category select, tax ID), **Terms & Accounting** (terms preset chips synced two-way with select + days input; currency select defaulting MWK "K"; opening balance mono right-aligned with dynamic symbol label; opening balance date; preferred method), **Status & Availability** (active switch with live label, internal notes).
- Right rail (sticky): **Live Preview** card (avatar initials, name, code, email/phone or muted placeholders, terms, currency label, category) updating on every keystroke; **Completion** meter (percent + animated bar + 4 check items: name, contact method, terms, category) with checkPop animation; **Quick Nav** (Vendors List link, smooth-scroll jumps to each section, report links → toast); **Good to Know** tips.
- **Sticky action bar**: status indicator dot (gray → green ready → red error) with message; Reset (restores defaults + toast), Save as Draft, primary Save (disabled until valid).
- Validation: name required (inline error + shake + red ring), email format when present, days ≥ 0; on failed save → error toast + scroll to first invalid field; on success → payload logged/submitted + success toast + redirect to index.

---

## DESIGN SYSTEM REQUIREMENTS

Preserve exactly:

```css
--deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
--row1:#11454B; --row2:#DCE6E7;
--sec:#107C7B; --sec-2:#0C7E7D;
--ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
--mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
--card:rgba(255,255,255,.75);
--green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
--tint:#DFF7F6; --mint:#7FD1C0;
--blue:#1D6FB8; --purple:#7C3AED;
--teal-btn-shadow / --cta-btn-shadow (as in mockups)
```

Required signatures: deep-teal gradient topnav with mint glow; module rail active deep-gradient pill; icon sidebar with tint active + left gradient bar; glass cards with backdrop blur; **centred FAINT gradient hairline at the BOTTOM of every card/hero/tabs card** (`bottom:0`, 62% width, rgba teal/mint stops, upward soft glow); teal-gradient KPI highlight with sheen sweep; striped mist tables with tint hover; mono tabular numerals; status/type pills; premium split checkbox tiles; modal headers with deep gradient + mint bottom hairline (danger variant red gradient); toast stack bottom-right.

---

## BACKEND IMPLEMENTATION REQUIREMENTS

### Migrations / tables (adapt names to existing conventions)

- `vendors`: tenant/company id, vendor_code (unique per tenant), name, display_name, contact_person, phone, email, website, billing_address, remit_to_address, category_id nullable, currency_id nullable default MWK, tax_id, payment_terms label, payment_terms_days, opening_balance decimal(18,2), opening_balance_date, credit_limit decimal(18,2) default 0 (0 = unlimited), preferred_payment_method, is_active bool, is_favourite bool (or pivot), created_by/updated_by, timestamps, softDeletes.
- `vendor_categories` (or reuse existing categories): id, tenant, name, slug.
- `currencies` if absent: code, name, symbol, decimals, is_default.
- `vendor_bills`: tenant, vendor_id, bill_number, bill_date, due_date, currency, terms, linked_po, subtotal, tax_amount, total_amount, amount_paid, status enum(draft,open,paid,overdue,void), notes, attachment_path, created_by, timestamps, softDeletes.
- `vendor_bill_items`: bill_id, description, qty, unit_price, line_total, tax_amount.
- `vendor_payments`: tenant, vendor_id, payment_number, payment_date, method enum(bank,mobile,card,cash,split), paying_bank_account_id, amount, reference_number, provider_institution, account_name, account_holder_name, cash_tendered, change_given, status enum(pending,cleared,void), note, created_by, timestamps, softDeletes.
- `vendor_payment_split_lines`: payment_id, method, amount, reference_number, provider_institution, account_name, account_holder_name, cash_tendered, change_given.
- `vendor_payment_allocations`: payment_id, bill_id, allocated_amount.
- `vendor_notes`: tenant, vendor_id, type enum(internal,reminder,follow_up), title, body, reminder_date, pinned, created_by, timestamps, softDeletes.
- `vendor_messages`: tenant, vendor_id, direction enum(inbound,outbound), channel enum(email,sms), subject, body, template_key, attachment_path, sent_by, timestamps, softDeletes.
- `vendor_ledger_entries`: tenant, vendor_id, source_type (bill/payment/credit_note/opening/journal), source_id, entry_number, date, type, reference, debit, credit, balance_after.
- `vendor_favourites` pivot if you prefer per-user favourites instead of boolean.

### Models, relationships, casts, scopes

Vendor hasMany bills/payments/notes/messages/ledgerEntries; belongsTo category/currency; scopes: active(), withBalance(), favourite(); computed accessors: outstanding_balance, ageing_bucket. Bill hasMany items/allocations. Payment hasMany splitLines/allocations.

### Routes (namespaced under existing vendor module)

```php
Route::middleware(['auth','tenant'])->prefix('vendors')->name('vendors.')->group(function(){
  Route::get('/',               [VendorController::class,'index'])->name('index');
  Route::get('/create',         [VendorController::class,'create'])->name('create');
  Route::post('/',              [VendorController::class,'store'])->name('store');
  Route::get('/export/csv',     [VendorController::class,'exportCsv'])->name('export');
  Route::get('/{vendor}',       [VendorController::class,'show'])->name('show');
  Route::get('/{vendor}/edit',  [VendorController::class,'edit'])->name('edit');
  Route::put('/{vendor}',       [VendorController::class,'update'])->name('update');
  Route::delete('/{vendor}',    [VendorController::class,'destroy'])->name('destroy');
  Route::post('/{vendor}/archive',  [VendorController::class,'archive'])->name('archive');
  Route::post('/{vendor}/activate', [VendorController::class,'activate'])->name('activate');
  Route::post('/{vendor}/favourite',[VendorController::class,'toggleFavourite'])->name('favourite');
  Route::post('/bulk/activate', [VendorBulkController::class,'activate'])->name('bulk.activate');
  Route::post('/bulk/archive',  [VendorBulkController::class,'archive'])->name('bulk.archive');
  Route::get('/bulk/export',    [VendorBulkController::class,'export'])->name('bulk.export');
  Route::post('/{vendor}/bills',    [VendorBillController::class,'store'])->name('bills.store');
  Route::post('/{vendor}/payments', [VendorPaymentController::class,'store'])->name('payments.store');
  Route::post('/{vendor}/notes',    [VendorNoteController::class,'store'])->name('notes.store');
  Route::post('/{vendor}/messages', [VendorMessageController::class,'store'])->name('messages.store');
  Route::get('/{vendor}/statement',        [VendorStatementController::class,'show'])->name('statement.show');
  Route::get('/{vendor}/statement/export', [VendorStatementController::class,'export'])->name('statement.export');
  Route::get('/ageing', [VendorController::class,'ageing'])->name('ageing'); // JSON buckets for index card
});
```

### Services

- `VendorService` (create/update/archive/activate, code generation, opening-balance ledger posting).
- `VendorStatsService` (KPI counts, outstanding totals, statement closing balance).
- `PayablesAgeingService` (bucket computation from open bills by due date).
- `VendorBillService` (bill + items persistence, totals, status transitions).
- `VendorPaymentService` (payment + split lines + allocations in a DB transaction; ledger entries; bill status recalculation).
- `SplitPaymentValidator` (±0.01 allocation rule; per-line reference rule for electronic methods).
- `VendorStatementService` (running-balance ledger build, PDF/CSV export).
- `VendorMessageService` (template merge, outbound record, mail/SMS dispatch hooks).
- `VendorCsvExporter` (streamed CSV of filtered or selected rows).

### Form Requests

- `StoreVendorRequest` / `UpdateVendorRequest`: name required; email nullable|email; phone nullable; days integer ≥ 0; balances numeric; currency exists; category exists|null.
- `StoreVendorBillRequest`: bill_number required unique per tenant/vendor; dates required; items array min 1 with description/qty/unit_price; totals consistency.
- `StoreVendorPaymentRequest`: method required in bank,mobile,card,cash,split; amount numeric > 0; if method in bank,mobile,card → reference_number required + provider_institution required; if split → split_lines array min 1, each line method required, amount > 0, electronic lines reference+provider required, sum(split amounts) equals amount within 0.01 (custom validator rule).
- `StoreVendorNoteRequest`, `SendVendorMessageRequest`.

### Policy

`VendorPolicy`: viewAny, view, create, update, delete, restore, archive, activate, toggleFavourite, recordPayment, createBill, addNote, sendMessage, viewStatement, exportStatement — all tenant-scoped.

### Seeders & tests

- Seeder reproduces mock data: AHL Group (active, due-on-receipt, nil balance), Gracian Seyama (inactive, Net 15, nil balance), plus optional extra vendors with balances to exercise ageing/overdue states.
- Feature tests: index filters & counts endpoint; CSV export columns; store/update validation (name, email format, days ≥ 0); payment split validation (unallocated, over-allocated, missing reference); bill creation totals; statement running balance; archive blocks new bills; favourite toggle; policy denial for other tenants.

---

## FRONTEND IMPLEMENTATION REQUIREMENTS

Blade structure (adapt to existing layout system):

```text
resources/views/vendors/index.blade.php
resources/views/vendors/show.blade.php
resources/views/vendors/form.blade.php          (create + edit dual mode)
resources/views/vendors/partials/chrome.blade.php (or reuse global layout)
resources/views/vendors/partials/kpi-tiles.blade.php
resources/views/vendors/partials/ageing-card.blade.php
resources/views/vendors/partials/bulk-bar.blade.php
resources/views/vendors/partials/toolbar.blade.php
resources/views/vendors/partials/vendors-table.blade.php
resources/views/vendors/partials/rails.blade.php
resources/views/vendors/partials/hero.blade.php
resources/views/vendors/partials/fin-strip.blade.php
resources/views/vendors/partials/tabs/*.blade.php
resources/views/vendors/partials/side-panel.blade.php
resources/views/vendors/modals/delete-confirm.blade.php
resources/views/vendors/modals/message.blade.php
resources/views/vendors/modals/edit-vendor.blade.php
resources/views/vendors/modals/record-payment.blade.php
resources/views/vendors/modals/new-bill.blade.php
resources/views/vendors/modals/add-note.blade.php
resources/views/vendors/modals/archive-confirm.blade.php
resources/css/vendor-module.css
resources/js/vendor-module.js   (or Alpine components)
```

JS/Alpine components: `vendorsIndex()` (filters, sorts, bulk, csv, ageing state incl. three clear paths), `vendorDetail()` (tabs, gauge, quick-nav wiring, modal orchestration), `recordPayment()` (method panels, providers map, split tiles, totals, validation), `vendorForm()` (preview, meter, presets sync, validation, sticky bar state, draft/save/reset), `modalManager()` (open/close/escape/backdrop/scroll-lock), `toasts()`.

Server-rendered equivalents must produce identical markup so the page works without JS for core navigation (row links are real `<a href>` in Blade; JS enhances).

---

## CURRENCY RULE

All monetary values render as MWK with symbol **K** (e.g. `K 8,178.30`), mono tabular numerals, right-aligned, red when an outstanding vendor balance > 0. Currency select defaults to `K — Malawian Kwacha`. Never default to USD/$.

---

## ACCEPTANCE CHECKLIST

### Index
- [ ] Topnav, module rail (Purchasing active), sidebar (Vendors active), breadcrumb render.
- [ ] Export CSV downloads filtered rows; Create Vendor navigates to form page.
- [ ] 5 KPI tiles filter table; active tile ringed; "All" tile gradient + sheen.
- [ ] Ageing card: 5 buckets, bars, clickable, selected ring, header Clear button visible only when active, total chip live.
- [ ] Active-filter chip with ✕ appears only when a bucket is selected; ✕ clears.
- [ ] KPI tile click and Views rail click also clear bucket selection.
- [ ] Toolbar search, seg views with counts, terms select, filter button, count chip all functional.
- [ ] Table: select-all, row checks, star toggle persists, avatar status dot, subtext, terms pill, red outstanding, status pill, 3 action icons.
- [ ] Row click + Enter/Space navigate to detail; View icon navigates; Edit icon opens form in edit mode; Delete icon opens confirm modal.
- [ ] Headers sort asc/desc with indicator swap.
- [ ] Bulk bar appears/disappears correctly; Activate/Archive mutate rows; Message opens addressed modal; Export downloads selection; Clear resets.
- [ ] Pagination footer; empty-state row message when filters exclude everything.
- [ ] Views rail + Reports rail active states and toasts.

### Detail
- [ ] Hero: avatar, name, status pill, code chip, meta row, gauge (∞ state), Archive button, 6 quick chips all wired.
- [ ] KPI strip 4 cards with highlight sheen.
- [ ] Tabs: 5 panels; underline gradient; counts; panel entrance animation.
- [ ] Information grid, address empty states, timeline.
- [ ] Transactions toolbar + empty state CTA opens New Bill modal.
- [ ] Statement ledger with totals row; Export PDF + Email Statement wired.
- [ ] Payments empty state CTA opens Record Payment modal.
- [ ] Notes toolbar CTA opens Add Note modal; empty state.
- [ ] Side panel Summary/Quick Nav/Contact all wired (Quick Nav jumps tab or opens modal or links index).
- [ ] All 6 modals open/close (button, backdrop, Escape), lock scroll, animate.
- [ ] Record Payment: method panels swap with 34px spacing; providers populate per method; split tiles expand/collapse preserving values; totals + banners; post disabled until fully valid.

### Create/Edit
- [ ] Dual mode via `?edit=`; labels/breadcrumb/save text switch; code locked in edit.
- [ ] All 4 section cards with every field from mockup.
- [ ] Terms presets ↔ select ↔ days three-way sync.
- [ ] Currency default MWK; opening-balance label symbol updates with currency.
- [ ] Live preview updates per keystroke incl. muted placeholders and initials avatar.
- [ ] Completion meter percent, bar width, 4 checks with pop animation.
- [ ] Quick Nav: index link, smooth section jumps, report toasts.
- [ ] Sticky bar: status dot states (idle/ready/error), Reset, Save as Draft, Save disabled until valid.
- [ ] Validation: name required inline error + shake; email format; days ≥ 0; failed save scrolls to first error + error toast; success toast + redirect.

### Global
- [ ] Faint centred BOTTOM hairline on every card/hero/tabs card.
- [ ] All animations present and performant.
- [ ] MWK "K" everywhere; mono tabular figures; red outstanding.
- [ ] Policies enforced server-side for every action.
- [ ] Tests green; seeders reproduce mock dataset.

---

## AUTHORITATIVE HTML MOCKUPS — IMPLEMENT EXACTLY

### MOCKUP 1 — `vendors-index.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendors | CamelotBooks ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{
    --deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
    --row1:#11454B; --row2:#DCE6E7;
    --sec:#107C7B; --sec-2:#0C7E7D;
    --ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
    --mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
    --card:rgba(255,255,255,.75);
    --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 2px 6px rgba(10,42,46,.04);
    --shadow-card-hov:0 2px 4px rgba(10,42,46,.06),0 20px 46px -12px rgba(10,42,46,.18),0 4px 10px rgba(10,42,46,.06);
    --green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
    --tint:#DFF7F6; --mint:#7FD1C0;
    --green-tint:rgba(21,128,61,.10); --warn-tint:rgba(217,119,6,.12); --red-tint:rgba(220,38,38,.10);
    --sec-ring:rgba(16,124,123,.16);
    --teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
    --cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
    --r-md:12px; --r-lg:16px; --r-xl:20px;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;color:var(--ink);min-height:100vh;overflow-x:hidden;
    background:radial-gradient(1100px 480px at 88% -12%, rgba(127,209,192,.28), transparent 60%),
      radial-gradient(900px 420px at -8% -4%, rgba(16,124,123,.12), transparent 55%),
      linear-gradient(180deg,var(--mist-a) 0%,#ECF4F4 45%,var(--mist-b) 100%);
    background-attachment:fixed;}
  body.modal-open{overflow:hidden}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes slideRight{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes barIn{from{transform:scaleY(.2);opacity:0}to{transform:scaleY(1);opacity:1}}
  @keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes overlayIn{from{opacity:0}to{opacity:1}}
  @keyframes overlayOut{from{opacity:1}to{opacity:0}}
  @keyframes modalIn{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes modalOut{from{opacity:1}to{opacity:0;transform:translateY(12px) scale(.97)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}

  /* ═ CHROME  */
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;padding:0 24px;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav>*{position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff;letter-spacing:-.3px}
  .topnav-sep{color:rgba(255,255,255,.32);margin:0 8px;font-size:13px}
  .topnav-org{font-size:13px;color:rgba(255,255,255,.60)}
  .topnav-org strong{color:rgba(255,255,255,.95);font-weight:600}
  .topnav-center{flex:1;display:flex;justify-content:center;gap:8px}
  .topnav-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
  .topnav-pill:hover{background:rgba(255,255,255,.15);color:#fff}
  .topnav-pill .badge-count{background:linear-gradient(180deg,var(--mint),var(--sec));color:var(--deep-3);font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
  .topnav-search{display:flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.55);font-size:12.5px;cursor:pointer;min-width:180px}
  .topnav-search kbd{margin-left:auto;background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-size:10px;color:rgba(255,255,255,.6)}
  .topnav-right{display:flex;align-items:center;gap:12px;margin-left:20px}
  .topnav-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;color:rgba(255,255,255,.68);transition:.2s;position:relative;background:transparent;border:none;font-size:14px}
  .topnav-icon-btn:hover{background:rgba(255,255,255,.13);color:#fff}
  .topnav-icon-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--mint);border-radius:50%;border:1.5px solid var(--deep-2);animation:pulseMint 2.4s infinite}
  .topnav-user{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;cursor:pointer}
  .topnav-avatar{width:30px;height:30px;background:linear-gradient(135deg,var(--mint),var(--sec));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--deep-3)}
  .topnav-user-name{font-size:12px;font-weight:600;color:#fff}
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55);letter-spacing:.3px}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 24px 0 80px;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .sidebar{position:fixed;top:108px;left:0;bottom:0;width:60px;z-index:98;background:rgba(255,255,255,.82);backdrop-filter:blur(10px);border-right:1px solid var(--line);display:flex;flex-direction:column;align-items:center;padding:16px 0;gap:4px}
  .sidebar-item{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;color:var(--ink-muted);cursor:pointer;transition:.18s;font-size:15px;position:relative;text-decoration:none}
  .sidebar-item:hover{background:var(--tint);color:var(--sec);transform:scale(1.06)}
  .sidebar-item.active{background:var(--tint);color:var(--sec)}
  .sidebar-item.active::before{content:'';position:absolute;left:-10px;width:3px;height:20px;background:linear-gradient(180deg,var(--sec),var(--mint));border-radius:0 3px 3px 0}
  .sidebar-divider{width:28px;height:1px;background:var(--line);margin:6px 0}
  .sidebar-spacer{flex:1}
  .main{margin-top:108px;margin-left:60px;padding:28px 32px 60px}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:20px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:20px}
  .page-title{font-size:26px;font-weight:800;letter-spacing:-.6px;line-height:1.2;margin-bottom:6px}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500}
  .page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 14px;font-size:12px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover{border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}

  /* cards / hairlines — BOTTOM, FAINT */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none;z-index:1}
  .card-head{display:flex;align-items:center;gap:10px;padding:16px 22px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-title{font-size:13px;font-weight:800;color:var(--deep-2);letter-spacing:.2px}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px}
  .card-body{padding:22px}

  /* pills / chips */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-active{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-active .pdot{background:var(--green)}
  .pill-inactive{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-inactive .pdot{background:#9DB4B4}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

  /* ═ KPI TILES ═ */
  .kpi-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:18px}
  .kpi{border:1px solid var(--border);border-radius:var(--r-md);padding:15px 18px;background:rgba(255,255,255,.72);box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s;cursor:pointer;display:flex;align-items:center;gap:13px}
  .kpi:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov);border-color:var(--mint)}
  .kpi.active{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring),var(--shadow-card)}
  .kpi.hl{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6)}
  .kpi.hl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .kpi.hl .kpi-label,.kpi.hl .kpi-sub{color:rgba(255,255,255,.72)}
  .kpi.hl .kpi-value{color:#fff}
  .kpi.hl .kpi-icon{background:rgba(255,255,255,.16);color:#fff}
  .kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;background:var(--mist-b);color:var(--ink-soft);flex-shrink:0}
  .kpi-icon.teal{background:var(--tint);color:var(--sec)}
  .kpi-icon.red{background:var(--red-tint);color:var(--red)}
  .kpi-text{display:flex;flex-direction:column;gap:2px;min-width:0}
  .kpi-label{font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted)}
  .kpi-value{font-size:21px;font-weight:800;letter-spacing:-.5px;font-variant-numeric:tabular-nums;line-height:1}
  .kpi-sub{font-size:10.5px;color:var(--ink-soft);font-weight:500;margin-top:3px}

  /* ═ AGEING ═ */
  .ageing{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);margin-bottom:18px}
  .ag-cell{padding:13px 16px;border-right:1px solid var(--line);position:relative;cursor:pointer;transition:.18s}
  .ag-cell:last-child{border-right:none}
  .ag-cell:hover{background:var(--tint)}
  .ag-cell.hot{background:linear-gradient(180deg,var(--red-tint),rgba(255,255,255,.4))}
  .ag-cell.sel{box-shadow:inset 0 0 0 2px var(--sec)}
  .ag-label{font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;display:flex;align-items:center;gap:6px}
  .ag-label .agdot{width:7px;height:7px;border-radius:50%}
  .ag-val{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:800;color:var(--deep-2)}
  .ag-cell.hot .ag-val{color:var(--red-2)}
  .ag-bar{height:4px;border-radius:2px;background:var(--mist-b);margin-top:8px;overflow:hidden}
  .ag-fill{height:100%;border-radius:2px;transform-origin:left;animation:barIn .6s ease both}
  .ageing-clear{display:none;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;border:1px solid rgba(220,38,38,.3);background:var(--red-tint);color:var(--red-2);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:.15s}
  .ageing-clear:hover{background:rgba(220,38,38,.18)}
  .ageing-clear.show{display:inline-flex}
  .active-filters{display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap}
  .active-filters:empty{display:none;margin:0}
  .filter-chip{display:inline-flex;align-items:center;gap:8px;padding:5px 8px 5px 12px;border-radius:20px;background:var(--warn-tint);border:1px solid rgba(217,119,6,.35);color:var(--warn);font-size:11.5px;font-weight:700}
  .filter-chip b{font-weight:800}
  .filter-chip button{width:18px;height:18px;border-radius:50%;border:none;background:rgba(217,119,6,.2);color:var(--warn);cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;transition:.15s}
  .filter-chip button:hover{background:var(--warn);color:#fff}
  .filter-chip-label{font-size:11px;font-weight:700;color:var(--ink-muted);text-transform:uppercase;letter-spacing:.5px}

  /* ═ TOOLBAR ═ */
  .toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
  .toolbar-search{display:flex;align-items:center;gap:8px;padding:9px 14px;background:rgba(255,255,255,.9);border:1.5px solid var(--border);border-radius:9px;flex:1;min-width:240px;color:var(--ink-muted);font-size:12.5px;transition:.18s}
  .toolbar-search:focus-within{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .toolbar-search input{border:none;outline:none;font-family:inherit;font-size:12.5px;color:var(--ink);width:100%;background:transparent}
  .seg{display:inline-flex;background:var(--mist-b);border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px}
  .seg-btn{padding:6px 13px;border:none;border-radius:7px;background:transparent;font-family:inherit;font-size:11.5px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;display:flex;align-items:center;gap:5px}
  .seg-btn:hover{color:var(--ink)}
  .seg-btn.active{background:#fff;color:var(--deep-2);box-shadow:0 1px 3px rgba(10,42,46,.12)}
  .seg-btn .n{font-size:10px;background:var(--mist-b);padding:0 6px;border-radius:8px;color:var(--ink-soft)}
  .seg-btn.active .n{background:var(--tint);color:var(--sec)}

  /* ═ TABLE ═ */
  .table-wrap{border:1px solid var(--border);border-radius:14px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:11px 14px;font-size:9.5px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap;cursor:pointer;user-select:none;transition:.15s}
  .data-table thead th:hover{color:var(--deep-2)}
  .data-table thead th .sort{opacity:.35;margin-left:4px;font-size:9px}
  .data-table thead th.sorted .sort{opacity:1;color:var(--sec)}
  .data-table tbody td{padding:13px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr:nth-child(even) td{background:rgba(244,248,248,.5)}
  .data-table tbody tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr.clickable-row{cursor:pointer}
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .data-table tbody tr.clickable-row:focus-visible{outline:2px solid var(--sec);outline-offset:-2px}
  .data-table tbody tr.selected td{background:rgba(16,124,123,.10)!important}
  .td-mono{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-variant-numeric:tabular-nums}
  .td-right{text-align:right}
  .td-strong{font-weight:700}
  .td-red{color:var(--red-2);font-weight:800}
  .cell-name{font-size:13px;font-weight:700;color:var(--deep-2)}
  .cell-sub{font-size:10.5px;color:var(--ink-muted);margin-top:1px}
  .avatar-cell{display:flex;align-items:center;gap:12px}
  .avatar{width:38px;height:38px;border-radius:11px;background:linear-gradient(145deg,var(--tint),var(--mint));border:1px solid rgba(127,209,192,.5);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--deep-2);flex-shrink:0;position:relative}
  .avatar .astat{position:absolute;bottom:-2px;right:-2px;width:11px;height:11px;border-radius:50%;border:2px solid #fff}
  .avatar .astat.on{background:var(--green)}
  .avatar .astat.off{background:#9DB4B4}
  .terms-pill{display:inline-flex;padding:3px 10px;border-radius:8px;font-size:10.5px;font-weight:700;background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .star-btn{background:none;border:none;color:var(--border);cursor:pointer;font-size:13px;transition:.15s;padding:2px}
  .star-btn:hover{color:var(--warn);transform:scale(1.15)}
  .star-btn.on{color:var(--warn)}
  .row-check{width:16px;height:16px;accent-color:var(--sec);cursor:pointer}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}
  .empty-rows{padding:48px 20px;text-align:center;color:var(--ink-muted);font-size:13px}

  /* ═ BULK BAR ═ */
  .bulk-bar{position:sticky;top:116px;z-index:50;display:none;align-items:center;gap:14px;padding:11px 18px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);margin-bottom:14px;animation:slideDown .25s ease both}
  .bulk-bar.show{display:flex}
  .bulk-count{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:8px}
  .bulk-count b{background:var(--sec);color:#fff;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px}
  .bulk-actions{display:flex;gap:8px;margin-left:auto}
  .bulk-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:8px;font-size:11.5px;font-weight:700;cursor:pointer;border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#fff;font-family:inherit;transition:.15s}
  .bulk-btn:hover{background:rgba(255,255,255,.16)}
  .bulk-btn.primary{background:var(--sec);border-color:transparent}
  .bulk-clear{background:none;border:none;color:rgba(255,255,255,.6);cursor:pointer;font-size:11px;font-weight:600;font-family:inherit}
  .bulk-clear:hover{color:#fff}

  /* ═ LAYOUT ═ */
  .idx-grid{display:grid;grid-template-columns:1fr 290px;gap:20px;align-items:start}
  .side-panel{display:flex;flex-direction:column;gap:16px}
  .side-panel .side-card{animation:slideRight .45s ease both}
  .side-panel .side-card:nth-child(2){animation-delay:.1s}
  .side-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;transition:.2s}
  .side-card:hover{box-shadow:var(--shadow-card-hov)}
  .side-card-header{padding:15px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.5)}
  .side-card-title{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--deep-2)}
  .side-list{display:flex;flex-direction:column;gap:2px;padding:10px}
  .side-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .side-link:hover{background:var(--tint);color:var(--deep-2)}
  .side-link.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .side-link.active i{color:var(--mint)}
  .side-link i{width:18px;text-align:center;font-size:12px;opacity:.85}
  .side-link .cnt{margin-left:auto;font-size:10px;font-weight:800;background:var(--mist-b);color:var(--ink-soft);padding:1px 7px;border-radius:10px}
  .side-link.active .cnt{background:rgba(255,255,255,.16);color:#fff}

  /* ═ MODALS  */
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:overlayIn .22s ease both}
  .modal-overlay.closing{animation:overlayOut .18s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:560px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.15) both;overflow:hidden}
  .modal-overlay.closing .modal{animation:modalOut .18s ease both}
  .modal.wide{max-width:820px}
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;position:relative;overflow:hidden;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2) 0%,var(--deep-3) 100%)}
  .modal-header.danger{background:radial-gradient(120% 150% at 88% -30%, rgba(255,120,120,.25), transparent 55%),linear-gradient(135deg,#991B1B,#7F1D1D)}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,rgba(127,209,192,.4),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
  .modal-header.danger .modal-icon{background:rgba(255,255,255,.15)}
  .modal-head-text{flex:1}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .modal-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .modal-body{padding:24px;max-height:calc(100vh - 280px);overflow-y:auto}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .modal-footer .spacer{margin-right:auto}
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  textarea.form-control{resize:vertical;min-height:80px}
  .form-section-title{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:22px 0 12px;display:flex;align-items:center;gap:8px}
  .form-section-title:first-child{margin-top:0}
  .form-section-title::after{content:'';flex:1;height:1px;background:var(--line)}
  .switch{position:relative;width:40px;height:22px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(18px)}
  .switch-row{display:flex;align-items:center;gap:10px}
  .switch-row-label{font-size:12.5px;font-weight:600}
  .switch-row-sub{font-size:11px;color:var(--ink-muted)}

  /* toast */
  .toast-wrap{position:fixed;bottom:24px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:400px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast i{color:var(--mint);margin-top:2px}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}

  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-brand"><div class="topnav-logo">L</div><span class="topnav-app">CamelotBooks</span><span class="topnav-sep">|</span><span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span></div>
  <div class="topnav-center"><a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a><a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a><div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search vendors, bills… <kbd>Ctrl K</kbd></div></div>
  <div class="topnav-right"><button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button><button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button><div class="topnav-user"><div class="topnav-avatar">ES</div><div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div></div></div>
</nav>
<nav class="modulenav">
  <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item active" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Taxation <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cash-register"></i> POS <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-line"></i> Analytics <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
</nav>
<aside class="sidebar">
  <a class="sidebar-item" data-tip="Dashboard" href="#"><i class="fa-solid fa-gauge-high"></i></a>
  <a class="sidebar-item active" data-tip="Vendors" href="#"><i class="fa-solid fa-handshake"></i></a>
  <a class="sidebar-item" data-tip="Purchase Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Bills" href="#"><i class="fa-solid fa-receipt"></i></a>
  <a class="sidebar-item" data-tip="Payments" href="#"><i class="fa-solid fa-credit-card"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main">
  <div class="breadcrumb anim-1"><a href="#">Vendor Centre</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">Vendors</span></div>
  <div class="page-header anim-1">
    <div><h1 class="page-title">Vendors</h1><div class="page-subtitle">Vendor directory — records, terms, balances and contact details.</div></div>
    <div class="page-header-actions">
      <button class="btn btn-ghost" id="export-csv"><i class="fa-solid fa-download"></i> Export CSV</button>
      <button class="btn btn-deep" onclick="window.location.href='vendor-create.html'"><i class="fa-solid fa-plus"></i> Create Vendor</button>
    </div>
  </div>

  <!-- KPI TILES -->
  <div class="kpi-strip anim-2">
    <div class="kpi hl active" data-filter="all"><div class="kpi-icon"><i class="fa-solid fa-handshake"></i></div><div class="kpi-text"><span class="kpi-label">All Vendors</span><span class="kpi-value" id="k-all">2</span><span class="kpi-sub">Total in directory</span></div></div>
    <div class="kpi" data-filter="active"><div class="kpi-icon teal"><i class="fa-solid fa-circle-check"></i></div><div class="kpi-text"><span class="kpi-label">Active</span><span class="kpi-value" id="k-active">1</span><span class="kpi-sub">Available for POs</span></div></div>
    <div class="kpi" data-filter="inactive"><div class="kpi-icon"><i class="fa-solid fa-circle-pause"></i></div><div class="kpi-text"><span class="kpi-label">Inactive</span><span class="kpi-value" id="k-inactive">1</span><span class="kpi-sub">On hold</span></div></div>
    <div class="kpi" data-filter="overdue"><div class="kpi-icon red"><i class="fa-solid fa-clock"></i></div><div class="kpi-text"><span class="kpi-label">Overdue</span><span class="kpi-value" id="k-overdue">0</span><span class="kpi-sub">Past due bills</span></div></div>
    <div class="kpi" data-filter="zero"><div class="kpi-icon teal"><i class="fa-solid fa-bullseye"></i></div><div class="kpi-text"><span class="kpi-label">Zero Balance</span><span class="kpi-value" id="k-zero">2</span><span class="kpi-sub">Nothing owed</span></div></div>
  </div>

  <!-- AGEING -->
  <div class="card glass anim-2" style="margin-bottom:18px">
    <div class="card-head">
      <div class="card-head-icon" style="background:linear-gradient(180deg,#E8930C,var(--warn))"><i class="fa-solid fa-hourglass-half"></i></div>
      <div><div class="card-title">Payables Ageing</div><div class="card-sub">Outstanding vendor bills by bucket · click a bucket to filter</div></div>
      <div class="card-head-right">
        <button class="ageing-clear" id="ageing-clear"><i class="fa-solid fa-xmark"></i> Clear ageing filter</button>
        <span class="chip-count"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> K <span id="ageing-total">0.00</span> total outstanding</span>
      </div>
    </div>
    <div class="card-body" style="padding:0">
      <div class="ageing" style="border:none;border-radius:0;box-shadow:none;margin:0" id="ageing">
        <div class="ag-cell" data-bucket="current"><div class="ag-label"><span class="agdot" style="background:var(--green)"></span> Current</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--green)"></div></div></div>
        <div class="ag-cell" data-bucket="1-30"><div class="ag-label"><span class="agdot" style="background:var(--sec)"></span> 1–30 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--sec)"></div></div></div>
        <div class="ag-cell" data-bucket="31-60"><div class="ag-label"><span class="agdot" style="background:var(--warn)"></span> 31–60 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--warn)"></div></div></div>
        <div class="ag-cell" data-bucket="61-90"><div class="ag-label"><span class="agdot" style="background:#EA580C"></span> 61–90 Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:#EA580C"></div></div></div>
        <div class="ag-cell" data-bucket="90+"><div class="ag-label"><span class="agdot" style="background:var(--red)"></span> 90+ Days</div><div class="ag-val">0.00</div><div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--red)"></div></div></div>
      </div>
    </div>
  </div>

  <div class="idx-grid anim-3">
    <div>
      <div class="bulk-bar" id="bulk-bar">
        <span class="bulk-count"><b id="bulk-num">0</b> vendor(s) selected</span>
        <div class="bulk-actions">
          <button class="bulk-btn" data-bulk="activate"><i class="fa-solid fa-bolt"></i> Activate</button>
          <button class="bulk-btn" data-bulk="archive"><i class="fa-solid fa-box-archive"></i> Archive</button>
          <button class="bulk-btn" data-bulk="message"><i class="fa-solid fa-envelope"></i> Message</button>
          <button class="bulk-btn primary" data-bulk="export"><i class="fa-solid fa-download"></i> Export</button>
        </div>
        <button class="bulk-clear" id="bulk-clear">Clear</button>
      </div>
      <div class="active-filters" id="active-filters"></div>
      <div class="toolbar">
        <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input id="search-input" placeholder="Name, email, phone…"></div>
        <div class="seg" id="view-seg">
          <button class="seg-btn active" data-view="all">All <span class="n" id="s-all">2</span></button>
          <button class="seg-btn" data-view="active">Active <span class="n" id="s-active">1</span></button>
          <button class="seg-btn" data-view="inactive">Inactive <span class="n" id="s-inactive">1</span></button>
          <button class="seg-btn" data-view="fav"><i class="fa-solid fa-star" style="color:var(--warn)"></i> Fav <span class="n" id="s-fav">0</span></button>
        </div>
        <select class="btn btn-ghost btn-sm" id="terms-filter" style="appearance:auto;-webkit-appearance:auto"><option value="">All Terms</option><option value="Due on receipt">Due on receipt</option><option value="Net 15">Net 15</option><option value="Net 30">Net 30</option></select>
        <button class="btn btn-ghost btn-sm" id="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
        <span class="chip-count" style="margin-left:auto"><span id="visible-count">2</span> vendors</span>
      </div>
      <div class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr>
              <th style="width:34px"><input type="checkbox" class="row-check" id="check-all"></th>
              <th style="width:34px"></th>
              <th data-sort="name" class="sorted">Vendor <i class="fa-solid fa-sort-down sort"></i></th>
              <th data-sort="contact">Contact Person <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="phone">Phone <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="email">Email <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="terms">Terms <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="bal" class="td-right">Outstanding (K) <i class="fa-solid fa-sort sort"></i></th>
              <th data-sort="status">Status <i class="fa-solid fa-sort sort"></i></th>
              <th>Actions</th>
            </tr></thead>
            <tbody id="vendor-rows"></tbody>
          </table>
          <div class="empty-rows" id="empty-rows" style="display:none"><i class="fa-solid fa-magnifying-glass" style="opacity:.4;font-size:20px;display:block;margin-bottom:8px"></i>No vendors match your filters.</div>
        </div>
        <div class="table-footer"><span>Showing <span id="showing">1–2</span> of <span id="total-count">2</span> vendors</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
      </div>
    </div>
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Views</span></div>
        <div class="side-list" id="views-rail">
          <a class="side-link active" data-view="all"><i class="fa-solid fa-handshake"></i> All Vendors <span class="cnt" id="v-all">2</span></a>
          <a class="side-link" data-view="active"><i class="fa-solid fa-circle-check"></i> Active <span class="cnt" id="v-active">1</span></a>
          <a class="side-link" data-view="inactive"><i class="fa-solid fa-circle-pause"></i> Inactive <span class="cnt" id="v-inactive">1</span></a>
          <a class="side-link" data-view="overdue"><i class="fa-solid fa-clock"></i> Overdue <span class="cnt" id="v-overdue">0</span></a>
          <a class="side-link" data-view="zero"><i class="fa-solid fa-bullseye"></i> Zero Balance <span class="cnt" id="v-zero">2</span></a>
          <a class="side-link" data-view="fav"><i class="fa-solid fa-star"></i> Favourites <span class="cnt" id="v-fav">0</span></a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Reports</span></div>
        <div class="side-list" id="reports-rail">
          <a class="side-link" data-report="Vendor Balances"><i class="fa-solid fa-dollar-sign"></i> Vendor Balances</a>
          <a class="side-link" data-report="Purchases by Vendor"><i class="fa-solid fa-chart-pie"></i> Purchases by Vendor</a>
          <a class="side-link" data-report="Ageing Report"><i class="fa-solid fa-hourglass-half"></i> Ageing Report</a>
          <a class="side-link" data-report="1099 / Tax Batch"><i class="fa-solid fa-file-invoice-dollar"></i> 1099 / Tax Batch</a>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- DELETE CONFIRM -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal" style="max-width:440px">
    <div class="modal-header danger"><div class="modal-icon"><i class="fa-solid fa-trash-can"></i></div><div class="modal-head-text"><div class="modal-title">Delete Vendor?</div><div class="modal-subtitle" id="del-name">—</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">This will permanently remove the vendor record. Historical transactions are retained but the vendor will no longer be selectable for new purchase orders or bills.</p>
      <div style="background:var(--red-tint);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--red-2);font-weight:600;display:flex;gap:8px;align-items:flex-start"><i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i><span>This action cannot be undone.</span></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none" id="confirm-delete"><i class="fa-solid fa-trash-can"></i> Yes, Delete</button></div>
  </div>
</div>

<!-- MESSAGE -->
<div class="modal-overlay" id="modal-message">
  <div class="modal">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-envelope"></i></div><div class="modal-head-text"><div class="modal-title">Message Vendor</div><div class="modal-subtitle" id="msg-to">—</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Statement & Outstanding Balance"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear Partner,

Please find attached your latest statement of account. Kindly confirm receipt and advise on settlement timing.

Regards,
Acme Corporation — Accounts Payable</textarea></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-deep" id="send-message"><i class="fa-solid fa-paper-plane"></i> Send Message</button></div>
  </div>
</div>

<div class="toast-wrap" id="toasts"></div>

<script>
let vendors = [
  {id:1, code:'VEN-00001', name:'AHL Group', legal:'AHL Group Ltd', contact:'AHL Group', phone:'0999602605', email:'ahl@ahl.ww', terms:'Due on receipt', bal:0, status:'active', since:'Jul 2026', cat:'Office Supplies', cur:'K — Kwacha', tax:'', fav:false},
  {id:2, code:'VEN-00002', name:'Gracian Seyama', legal:'Gracian Seyama', contact:'Yeah', phone:'', email:'', terms:'Net 15', bal:0, status:'inactive', since:'Aug 2026', cat:'Office Supplies', cur:'K — Kwacha', tax:'', fav:false}
];
let nextId = 3;
let state = { filter:'all', view:'all', search:'', terms:'', sortKey:'name', sortDir:1, deletingId:null, messagingIds:[], ageingBucket:'' };

const fmt = n => Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const initials = n => n.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
const esc = s => String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

function passes(v){
  if(state.view==='active'&&v.status!=='active')return false;if(state.view==='inactive'&&v.status!=='inactive')return false;if(state.view==='fav'&&!v.fav)return false;if(state.view==='overdue'&&!(v.bal>0))return false;if(state.view==='zero'&&v.bal!==0)return false;
  if(state.filter!=='all'){if(state.filter==='active'&&v.status!=='active')return false;if(state.filter==='inactive'&&v.status!=='inactive')return false;if(state.filter==='overdue'&&!(v.bal>0))return false;if(state.filter==='zero'&&v.bal!==0)return false;}
  if(state.terms&&v.terms!==state.terms)return false;
  if(state.search){const q=state.search.toLowerCase();if(![v.name,v.contact,v.phone,v.email,v.code].some(f=>String(f).toLowerCase().includes(q)))return false;}
  return true;
}
function sorted(list){const k=state.sortKey,d=state.sortDir;return[...list].sort((a,b)=>{let x=a[k],y=b[k];if(k==='bal'){x=Number(x);y=Number(y);}else{x=String(x??'').toLowerCase();y=String(y??'').toLowerCase();}if(x<y)return-1*d;if(x>y)return 1*d;return 0;});}

function render(){
  const list=sorted(vendors.filter(passes));
  const tb=document.getElementById('vendor-rows');
  tb.innerHTML=list.map(v=>`<tr class="clickable-row" tabindex="0" data-id="${v.id}">
    <td><input type="checkbox" class="row-check row-sel" data-id="${v.id}"></td>
    <td><button class="star-btn ${v.fav?'on':''}" data-star="${v.id}" data-tip="Favourite"><i class="fa-solid fa-star"></i></button></td>
    <td><div class="avatar-cell"><div class="avatar">${initials(v.name)}<span class="astat ${v.status==='active'?'on':'off'}"></span></div><div><div class="cell-name">${esc(v.name)}</div><div class="cell-sub">Since ${esc(v.since)} · ${esc(v.code)}</div></div></div></td>
    <td>${esc(v.contact)||'<span style="color:#9DB4B4">—</span>'}</td>
    <td class="td-mono">${v.phone?esc(v.phone):'<span style="color:#9DB4B4">—</span>'}</td>
    <td>${v.email?esc(v.email):'<span style="color:#9DB4B4">—</span>'}</td>
    <td><span class="terms-pill">${esc(v.terms)}</span></td>
    <td class="td-right td-mono ${v.bal>0?'td-red':'td-strong'}">${fmt(v.bal)}</td>
    <td>${v.status==='active'?'<span class="pill pill-active"><span class="pdot"></span> Active</span>':'<span class="pill pill-inactive"><span class="pdot"></span> Inactive</span>'}</td>
    <td>
      <button class="row-icon-btn" data-view="${v.id}" data-tip="View"><i class="fa-solid fa-eye"></i></button>
      <button class="row-icon-btn" data-edit="${v.id}" data-tip="Edit"><i class="fa-solid fa-pen"></i></button>
      <button class="row-icon-btn" data-del="${v.id}" data-tip="Delete"><i class="fa-solid fa-trash-can"></i></button>
    </td>
  </tr>`).join('');
  document.getElementById('empty-rows').style.display=list.length?'none':'block';
  document.getElementById('visible-count').textContent=list.length;
  document.getElementById('showing').textContent=list.length?`1–${list.length}`:'0';
  document.getElementById('total-count').textContent=vendors.length;
  updateCounts();syncBulk();
  document.getElementById('ageing-clear').classList.toggle('show',!!state.ageingBucket);
  const af=document.getElementById('active-filters');
  if(state.ageingBucket){af.innerHTML=`<span class="filter-chip-label">Active filter:</span><span class="filter-chip"><i class="fa-solid fa-hourglass-half"></i> Payables: <b>${BUCKET_LABELS[state.ageingBucket]}</b><button id="chip-clear" data-tip="Clear filter"><i class="fa-solid fa-xmark"></i></button></span>`;document.getElementById('chip-clear').addEventListener('click',clearAgeing);}else{af.innerHTML='';}
}
function updateCounts(){const all=vendors.length,act=vendors.filter(v=>v.status==='active').length,ina=vendors.filter(v=>v.status==='inactive').length,od=vendors.filter(v=>v.bal>0).length,zero=vendors.filter(v=>v.bal===0).length,fav=vendors.filter(v=>v.fav).length,tot=vendors.reduce((s,v)=>s+Number(v.bal||0),0);[['k-all',all],['k-active',act],['k-inactive',ina],['k-overdue',od],['k-zero',zero],['s-all',all],['s-active',act],['s-inactive',ina],['s-fav',fav],['v-all',all],['v-active',act],['v-inactive',ina],['v-overdue',od],['v-zero',zero],['v-fav',fav]].forEach(([id,n])=>{const e=document.getElementById(id);if(e)e.textContent=n;});document.getElementById('ageing-total').textContent=fmt(tot);}

const openModal=id=>{const ov=document.getElementById(id);ov.classList.remove('closing');ov.classList.add('open');document.body.classList.add('modal-open');};
const closeModal=ov=>{ov.classList.add('closing');setTimeout(()=>{ov.classList.remove('open','closing');document.body.classList.remove('modal-open');},180);};
document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',()=>closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('mousedown',e=>{if(e.target===ov)closeModal(ov);}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(closeModal);});

function toast(title,sub){const t=document.createElement('div');t.className='toast';t.innerHTML=`<i class="fa-solid fa-circle-check"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub}</div></div>`;document.getElementById('toasts').appendChild(t);setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3200);}

document.querySelectorAll('.kpi').forEach(k=>k.addEventListener('click',()=>{state.ageingBucket='';document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.remove('sel'));document.querySelectorAll('.kpi').forEach(x=>x.classList.remove('active'));k.classList.add('active');state.filter=k.dataset.filter;render();}));

const BUCKET_LABELS={'current':'Current','1-30':'1–30 Days','31-60':'31–60 Days','61-90':'61–90 Days','90+':'90+ Days'};
function applyAgeing(bucket){document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.toggle('sel',x.dataset.bucket===bucket));state.ageingBucket=bucket;state.filter=bucket?'overdue':'all';syncKpiActive(bucket?'overdue':'all');render();}
function clearAgeing(){applyAgeing('');toast('Ageing filter cleared','Showing all vendors again.');}
document.querySelectorAll('#ageing .ag-cell').forEach(c=>c.addEventListener('click',()=>{const bucket=c.dataset.bucket;const already=c.classList.contains('sel');applyAgeing(already?'':bucket);if(!already)toast('Ageing bucket selected','Filtered to '+BUCKET_LABELS[bucket]+' outstanding balances.');}));
document.getElementById('ageing-clear').addEventListener('click',clearAgeing);
function syncKpiActive(f){document.querySelectorAll('.kpi').forEach(x=>x.classList.toggle('active',x.dataset.filter===f));}

function setView(v){state.ageingBucket='';document.querySelectorAll('#ageing .ag-cell').forEach(x=>x.classList.remove('sel'));state.view=v;state.filter='all';document.querySelectorAll('#view-seg .seg-btn').forEach(b=>b.classList.toggle('active',b.dataset.view===v));document.querySelectorAll('#views-rail .side-link').forEach(l=>l.classList.toggle('active',l.dataset.view===v));syncKpiActive(v==='all'?'all':v);render();}
document.querySelectorAll('#view-seg .seg-btn').forEach(b=>b.addEventListener('click',()=>setView(b.dataset.view)));
document.querySelectorAll('#views-rail .side-link').forEach(l=>l.addEventListener('click',e=>{e.preventDefault();setView(l.dataset.view);}));
document.querySelectorAll('#reports-rail .side-link').forEach(l=>l.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('#reports-rail .side-link').forEach(x=>x.classList.remove('active'));l.classList.add('active');toast('Report opened',l.dataset.report+' — generating preview…');}));
document.getElementById('search-input').addEventListener('input',e=>{state.search=e.target.value;render();});
document.getElementById('terms-filter').addEventListener('change',e=>{state.terms=e.target.value;render();});
document.getElementById('filter-btn').addEventListener('click',()=>toast('Filters applied','Status + terms + search combined'));
document.querySelectorAll('th[data-sort]').forEach(th=>th.addEventListener('click',()=>{const k=th.dataset.sort;if(state.sortKey===k)state.sortDir*=-1;else{state.sortKey=k;state.sortDir=1;}document.querySelectorAll('th[data-sort]').forEach(h=>{h.classList.remove('sorted');h.querySelector('.sort').className='fa-solid fa-sort sort';});th.classList.add('sorted');th.querySelector('.sort').className='fa-solid fa-sort-'+(state.sortDir===1?'down':'up')+' sort';render();}));

/* ROW ACTIONS — VIEW NAVIGATES TO DETAIL PAGE */
document.getElementById('vendor-rows').addEventListener('click',e=>{
  const star=e.target.closest('[data-star]');if(star){e.stopPropagation();const v=vendors.find(x=>x.id==star.dataset.star);v.fav=!v.fav;render();toast(v.fav?'Added to favourites':'Removed from favourites',v.name);return;}
  const view=e.target.closest('[data-view]');if(view){e.stopPropagation();window.location.href='vendor-detail.html?id='+view.dataset.view;return;}
  const edit=e.target.closest('[data-edit]');if(edit){e.stopPropagation();window.location.href='vendor-create.html?edit='+edit.dataset.edit;return;}
  const del=e.target.closest('[data-del]');if(del){e.stopPropagation();state.deletingId=+del.dataset.del;const v=vendors.find(x=>x.id==state.deletingId);document.getElementById('del-name').textContent=v.name+' ('+v.code+')';openModal('modal-delete');return;}
  const sel=e.target.closest('.row-sel');if(sel){e.stopPropagation();syncBulk();return;}
  const row=e.target.closest('tr[data-id]');if(row) window.location.href='vendor-detail.html?id='+row.dataset.id;
});
document.getElementById('vendor-rows').addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){const row=e.target.closest('tr[data-id]');if(row){e.preventDefault();window.location.href='vendor-detail.html?id='+row.dataset.id;}}});

document.getElementById('confirm-delete').addEventListener('click',()=>{const v=vendors.find(x=>x.id===state.deletingId);vendors=vendors.filter(x=>x.id!==state.deletingId);closeModal(document.getElementById('modal-delete'));toast('Vendor deleted',(v?v.name:'Record')+' removed from the directory.');render();});
function openMessage(ids){state.messagingIds=ids;const names=ids.map(i=>vendors.find(v=>v.id===i)?.name).filter(Boolean);document.getElementById('msg-to').textContent='To: '+names.join(', ');openModal('modal-message');}
document.getElementById('send-message').addEventListener('click',()=>{closeModal(document.getElementById('modal-message'));toast('Message sent','Delivered to '+state.messagingIds.length+' vendor(s).');});

function syncBulk(){const checked=[...document.querySelectorAll('.row-sel:checked')];document.getElementById('bulk-num').textContent=checked.length;document.getElementById('bulk-bar').classList.toggle('show',checked.length>0);document.querySelectorAll('#vendor-rows tr').forEach(r=>{const c=r.querySelector('.row-sel');r.classList.toggle('selected',!!(c&&c.checked));});const all=document.querySelectorAll('.row-sel');document.getElementById('check-all').checked=all.length>0&&checked.length===all.length;}
document.getElementById('check-all').addEventListener('change',e=>{document.querySelectorAll('.row-sel').forEach(c=>c.checked=e.target.checked);syncBulk();});
document.getElementById('bulk-clear').addEventListener('click',()=>{document.querySelectorAll('.row-check').forEach(c=>c.checked=false);syncBulk();});
document.querySelectorAll('[data-bulk]').forEach(b=>b.addEventListener('click',()=>{const ids=[...document.querySelectorAll('.row-sel:checked')].map(c=>+c.dataset.id);const act=b.dataset.bulk;if(act==='activate'){ids.forEach(i=>{const v=vendors.find(x=>x.id===i);if(v)v.status='active';});toast('Activated',ids.length+' vendor(s) activated.');}if(act==='archive'){ids.forEach(i=>{const v=vendors.find(x=>x.id===i);if(v)v.status='inactive';});toast('Archived',ids.length+' vendor(s) archived.');}if(act==='message'){openMessage(ids);return;}if(act==='export'){exportCSV(ids);return;}document.querySelectorAll('.row-check').forEach(c=>c.checked=false);render();}));
function exportCSV(ids){const rows=(ids&&ids.length?vendors.filter(v=>ids.includes(v.id)):vendors.filter(passes));const head=['Code','Name','Legal Name','Contact','Phone','Email','Terms','Outstanding','Status','Category','Currency'];const csv=[head.join(','),...rows.map(v=>[v.code,v.name,v.legal,v.contact,v.phone,v.email,v.terms,v.bal,v.status,v.cat,v.cur].map(f=>`"${String(f??'').replace(/"/g,'""')}"`).join(','))].join('\n');const blob=new Blob([csv],{type:'text/csv'});const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='vendors.csv';a.click();URL.revokeObjectURL(url);toast('Exported',rows.length+' vendor(s) downloaded as CSV.');}
document.getElementById('export-csv').addEventListener('click',()=>exportCSV());
document.querySelectorAll('.sidebar-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));
document.querySelectorAll('.module-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));
render();
</script>
</body>
</html>
```

### MOCKUP 2 — `vendor-detail.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendor Detail – AHL Group | CamelotBooks ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{
    --deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
    --row1:#11454B; --row2:#DCE6E7;
    --sec:#107C7B; --sec-2:#0C7E7D;
    --ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
    --mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
    --card:rgba(255,255,255,.75);
    --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 2px 6px rgba(10,42,46,.04);
    --shadow-card-hov:0 2px 4px rgba(10,42,46,.06),0 20px 46px -12px rgba(10,42,46,.18),0 4px 10px rgba(10,42,46,.06);
    --green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
    --tint:#DFF7F6; --mint:#7FD1C0;
    --green-tint:rgba(21,128,61,.10); --warn-tint:rgba(217,119,6,.12); --red-tint:rgba(220,38,38,.10);
    --blue:#1D6FB8; --blue-tint:rgba(29,111,184,.10);
    --purple:#7C3AED; --purple-tint:rgba(124,58,237,.10);
    --sec-ring:rgba(16,124,123,.16);
    --teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
    --cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
    --r-md:12px; --r-lg:16px; --r-xl:20px;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;color:var(--ink);min-height:100vh;overflow-x:hidden;
    background:radial-gradient(1100px 480px at 88% -12%, rgba(127,209,192,.28), transparent 60%),
      radial-gradient(900px 420px at -8% -4%, rgba(16,124,123,.12), transparent 55%),
      linear-gradient(180deg,var(--mist-a) 0%,#ECF4F4 45%,var(--mist-b) 100%);
    background-attachment:fixed;}
  body.modal-open{overflow:hidden}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes slideRight{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}
  @keyframes panelIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes overlayIn{from{opacity:0}to{opacity:1}}
  @keyframes overlayOut{from{opacity:1}to{opacity:0}}
  @keyframes modalIn{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes modalOut{from{opacity:1}to{opacity:0;transform:translateY(12px) scale(.97)}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes drawGauge{from{stroke-dashoffset:264}to{stroke-dashoffset:var(--dash-to,264)}}
  @keyframes expandIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}
  .anim-4{animation:fadeUp .4s ease both;animation-delay:.26s}

  /* ═ CHROME ═ */
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;padding:0 24px;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav>*{position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff;letter-spacing:-.3px}
  .topnav-sep{color:rgba(255,255,255,.32);margin:0 8px;font-size:13px}
  .topnav-org{font-size:13px;color:rgba(255,255,255,.60)}
  .topnav-org strong{color:rgba(255,255,255,.95);font-weight:600}
  .topnav-center{flex:1;display:flex;justify-content:center;gap:8px}
  .topnav-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
  .topnav-pill:hover{background:rgba(255,255,255,.15);color:#fff}
  .topnav-pill .badge-count{background:linear-gradient(180deg,var(--mint),var(--sec));color:var(--deep-3);font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
  .topnav-search{display:flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.55);font-size:12.5px;cursor:pointer;min-width:180px}
  .topnav-search kbd{margin-left:auto;background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-size:10px;color:rgba(255,255,255,.6)}
  .topnav-right{display:flex;align-items:center;gap:12px;margin-left:20px}
  .topnav-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;color:rgba(255,255,255,.68);transition:.2s;position:relative;background:transparent;border:none;font-size:14px}
  .topnav-icon-btn:hover{background:rgba(255,255,255,.13);color:#fff}
  .topnav-icon-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--mint);border-radius:50%;border:1.5px solid var(--deep-2);animation:pulseMint 2.4s infinite}
  .topnav-user{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;cursor:pointer}
  .topnav-avatar{width:30px;height:30px;background:linear-gradient(135deg,var(--mint),var(--sec));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--deep-3)}
  .topnav-user-name{font-size:12px;font-weight:600;color:#fff}
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55);letter-spacing:.3px}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 24px 0 80px;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .sidebar{position:fixed;top:108px;left:0;bottom:0;width:60px;z-index:98;background:rgba(255,255,255,.82);backdrop-filter:blur(10px);border-right:1px solid var(--line);display:flex;flex-direction:column;align-items:center;padding:16px 0;gap:4px}
  .sidebar-item{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;color:var(--ink-muted);cursor:pointer;transition:.18s;font-size:15px;position:relative;text-decoration:none}
  .sidebar-item:hover{background:var(--tint);color:var(--sec);transform:scale(1.06)}
  .sidebar-item.active{background:var(--tint);color:var(--sec)}
  .sidebar-item.active::before{content:'';position:absolute;left:-10px;width:3px;height:20px;background:linear-gradient(180deg,var(--sec),var(--mint));border-radius:0 3px 3px 0}
  .sidebar-divider{width:28px;height:1px;background:var(--line);margin:6px 0}
  .sidebar-spacer{flex:1}
  .main{margin-top:108px;margin-left:60px;padding:28px 32px 60px}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:20px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:28px;gap:20px}
  .page-title{font-size:26px;font-weight:800;letter-spacing:-.6px;line-height:1.2;margin-bottom:6px}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500;display:flex;align-items:center;gap:8px}
  .page-subtitle .dot{width:3px;height:3px;background:var(--border);border-radius:50%}
  .page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 14px;font-size:12px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover{border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover{filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}
  .btn-icon{width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-muted);cursor:pointer;transition:.2s;font-size:14px}
  .btn-icon:hover{border-color:var(--mint);color:var(--sec);background:#fff;transform:translateY(-1px)}

  /* pills / chips */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-active{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-active .pdot{background:var(--green)}
  .pill-inactive{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-inactive .pdot{background:#9DB4B4}
  .pill-paid{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-paid .pdot{background:var(--green)}
  .pill-open{background:var(--warn-tint);color:var(--warn);border:1px solid rgba(217,119,6,.3)}
  .pill-open .pdot{background:var(--warn)}
  .pill-overdue{background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.25)}
  .pill-overdue .pdot{background:var(--red)}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .pill-sent{background:var(--blue-tint);color:var(--blue);border:1px solid rgba(29,111,184,.25)}
  .pill-sent .pdot{background:var(--blue)}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

  /* ═ HERO — BOTTOM FAINT HAIRLINE ═ */
  .vend-hero{border-radius:var(--r-xl);border:1px solid var(--border);padding:26px 30px;margin-bottom:20px;box-shadow:var(--shadow-card);position:relative;overflow:hidden;background:var(--card);backdrop-filter:blur(14px)}
  .vend-hero::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none}
  .hero-inner{display:flex;align-items:center;gap:24px}
  .hero-avatar-wrap{position:relative;flex-shrink:0}
  .hero-avatar{width:72px;height:72px;background:linear-gradient(145deg,var(--tint),var(--mint));border:1px solid rgba(127,209,192,.55);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:var(--deep-2);box-shadow:inset 0 1px 0 rgba(255,255,255,.7),0 6px 18px -6px rgba(16,124,123,.45)}
  .hero-info{flex:1;min-width:0}
  .hero-name-row{display:flex;align-items:center;gap:12px;margin-bottom:6px;flex-wrap:wrap}
  .hero-name{font-size:22px;font-weight:800;letter-spacing:-.4px}
  .hero-code{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:500;padding:3px 10px;background:var(--mist-b);border:1px solid var(--border);border-radius:6px;color:var(--ink-soft);letter-spacing:.5px}
  .hero-meta{display:flex;align-items:center;gap:16px;font-size:12.5px;color:var(--ink-soft);font-weight:500;flex-wrap:wrap}
  .hero-meta-item{display:flex;align-items:center;gap:5px}
  .hero-meta-item i{font-size:11px;color:var(--sec);opacity:.8}
  .hero-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* credit gauge */
  .gauge-wrap{display:flex;align-items:center;gap:14px;flex-shrink:0;padding:10px 16px;border:1px solid var(--border);border-radius:14px;background:rgba(255,255,255,.6)}
  .gauge-svg{width:78px;height:78px;transform:rotate(-90deg)}
  .gauge-track{fill:none;stroke:var(--mist-b);stroke-width:9}
  .gauge-fill{fill:none;stroke:url(#gg);stroke-width:9;stroke-linecap:round;stroke-dasharray:264;stroke-dashoffset:264;animation:drawGauge 1.1s cubic-bezier(.2,.8,.3,1) .3s forwards}
  .gauge-holder{position:relative;width:78px;height:78px}
  .gauge-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
  .gauge-pct{font-size:15px;font-weight:800;color:var(--deep-2);line-height:1}
  .gauge-lbl{font-size:8px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-top:2px}
  .gauge-stats{display:flex;flex-direction:column;gap:3px}
  .gs-row{display:flex;align-items:baseline;gap:6px;font-size:11px;color:var(--ink-muted);font-weight:600}
  .gs-row b{font-family:'JetBrains Mono',monospace;font-size:12.5px;color:var(--deep-2);font-weight:800}
  .gs-row b.red{color:var(--red-2)}

  .quick-chips{display:flex;align-items:center;gap:8px;margin-top:18px;padding-top:18px;border-top:1px solid var(--line);flex-wrap:wrap}
  .quick-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.7);border:1px solid var(--border);border-radius:20px;font-size:12px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .quick-chip:hover{background:var(--tint);border-color:var(--mint);color:var(--deep-2);transform:translateY(-1px)}
  .quick-chip i{font-size:11px}
  .quick-chip.green{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;font-weight:700;box-shadow:var(--teal-btn-shadow)}
  .quick-chip.green:hover{filter:brightness(1.08)}

  /* fin strip */
  .fin-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px}
  .fin-card{border-radius:var(--r-md);border:1px solid var(--border);padding:14px 18px;box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s;animation:fadeUp .45s ease both}
  .fin-card:nth-child(1){animation-delay:.20s}.fin-card:nth-child(2){animation-delay:.26s}.fin-card:nth-child(3){animation-delay:.32s}.fin-card:nth-child(4){animation-delay:.38s}
  .fin-card:hover{box-shadow:var(--shadow-card-hov);transform:translateY(-2px)}
  .fin-card.highlight{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6);box-shadow:var(--teal-btn-shadow)}
  .fin-card.highlight::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .fin-card.highlight::after{content:'';position:absolute;bottom:-26px;right:-26px;width:82px;height:82px;background:radial-gradient(circle,rgba(127,209,192,.35),transparent 70%);border-radius:50%}
  .fin-card.highlight .fin-label,.fin-card.highlight .fin-sub{color:rgba(255,255,255,.72)}
  .fin-card.highlight .fin-value{color:#fff}
  .fin-card.highlight .fin-icon-wrap{background:rgba(255,255,255,.16);color:#fff}
  .fin-card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .fin-label{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-muted)}
  .fin-icon-wrap{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;background:var(--mist-b);color:var(--ink-soft)}
  .fin-value{font-size:21px;font-weight:800;letter-spacing:-.5px;line-height:1.1;margin-bottom:2px;font-variant-numeric:tabular-nums}
  .fin-value .currency{font-size:13px;font-weight:700;opacity:.55;margin-right:2px}
  .fin-value.terms{font-size:17px;line-height:1.3}
  .fin-sub{font-size:11px;color:var(--ink-soft);font-weight:500}

  /* tabs card — BOTTOM FAINT HAIRLINE */
  .content-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
  .tabs-card{border-radius:var(--r-xl);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;position:relative}
  .tabs-card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);z-index:1;pointer-events:none}
  .tabs-nav{display:flex;align-items:center;padding:0 8px;border-bottom:1px solid var(--line);overflow-x:auto;background:rgba(255,255,255,.5)}
  .tab-btn{position:relative;padding:16px 18px;font-size:13px;font-weight:600;color:var(--ink-muted);cursor:pointer;transition:.18s;border:none;white-space:nowrap;background:none;font-family:'Inter',sans-serif}
  .tab-btn:hover{color:var(--ink)}
  .tab-btn.active{color:var(--deep-2);font-weight:700}
  .tab-btn.active::after{content:'';position:absolute;left:14px;right:14px;bottom:0;height:2.5px;background:linear-gradient(90deg,var(--sec),var(--mint));border-radius:3px 3px 0 0}
  .tab-btn .tab-count{font-size:10px;font-weight:700;background:var(--mist-b);color:var(--ink-soft);padding:1px 6px;border-radius:10px;margin-left:5px}
  .tab-btn.active .tab-count{background:var(--tint);color:var(--sec)}
  .tab-body{padding:28px 32px}
  .tab-panel{display:none}
  .tab-panel.active{display:block;animation:panelIn .32s ease both}
  .overview-section-title{font-size:10.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:16px;display:flex;align-items:center;gap:8px}
  .overview-section-title::after{content:'';flex:1;height:1px;background:var(--line)}
  .info-grid{display:grid;grid-template-columns:repeat(3,1fr);margin-bottom:28px}
  .info-cell{padding:16px 20px;border-bottom:1px solid var(--line);border-right:1px solid var(--line)}
  .info-cell:nth-child(3n){border-right:none}
  .info-cell-label{font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .info-cell-value{font-size:14px;font-weight:600}
  .info-cell-value.muted{color:#9DB4B4;font-weight:400;font-style:italic;font-size:13px}
  .info-cell-value.mono{font-family:'JetBrains Mono',monospace;font-size:13px}
  .address-block{background:var(--mist-a);border:1px solid var(--border);border-radius:var(--r-md);padding:18px 20px}
  .address-block-label{font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:8px;display:flex;align-items:center;gap:6px}
  .address-block-label i{color:var(--sec)}
  .address-block-value{font-size:13.5px;font-weight:500;line-height:1.6}
  .address-block-value.empty{color:#9DB4B4;font-style:italic}
  .addresses-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px}
  .timeline{position:relative;padding-left:24px}
  .timeline::before{content:'';position:absolute;left:7px;top:8px;bottom:8px;width:1.5px;background:var(--line)}
  .timeline-item{position:relative;padding-bottom:20px}
  .timeline-item:last-child{padding-bottom:0}
  .timeline-dot{position:absolute;left:-20px;top:4px;width:12px;height:12px;border-radius:50%;border:2px solid var(--border);background:#fff}
  .timeline-dot.blue{border-color:var(--sec);background:linear-gradient(135deg,var(--sec),var(--mint))}
  .timeline-title{font-size:13px;font-weight:600;margin-bottom:3px}
  .timeline-desc{font-size:12px;color:var(--ink-soft);line-height:1.5}
  .timeline-time{font-size:11px;color:#9DB4B4;margin-top:3px;font-weight:500}
  .empty-state{text-align:center;padding:56px 20px;animation:fadeIn .4s ease both}
  .empty-icon{width:64px;height:64px;margin:0 auto 14px;border-radius:16px;background:var(--tint);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--sec)}
  .empty-title{font-size:15px;font-weight:700;margin-bottom:6px}
  .empty-sub{font-size:12.5px;color:var(--ink-soft);margin-bottom:18px}
  .type-chip{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:8px;font-size:10.5px;font-weight:700}
  .type-bill{background:var(--purple-tint);color:var(--purple)}
  .type-pay{background:var(--green-tint);color:var(--green)}
  .type-cn{background:var(--warn-tint);color:var(--warn)}
  .type-jn{background:var(--mist-b);color:var(--ink-soft)}

  /* tables */
  .tab-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
  .toolbar-left{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .toolbar-search{display:flex;align-items:center;gap:8px;padding:8px 12px;background:rgba(255,255,255,.9);border:1.5px solid var(--border);border-radius:8px;min-width:230px;color:var(--ink-muted);font-size:12.5px;transition:.18s}
  .toolbar-search:focus-within{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .toolbar-search input{border:none;outline:none;font-family:inherit;font-size:12.5px;color:var(--ink);width:100%;background:transparent}
  .table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden;background:rgba(255,255,255,.85);box-shadow:var(--shadow-card)}
  .table-scroll{overflow-x:auto}
  .data-table{width:100%;border-collapse:collapse;font-size:13px}
  .data-table thead th{background:linear-gradient(180deg,var(--mist-a),var(--mist-b));text-align:left;padding:10px 14px;font-size:10px;font-weight:700;letter-spacing:.7px;text-transform:uppercase;color:var(--ink-soft);border-bottom:1px solid var(--line);white-space:nowrap}
  .data-table tbody td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .data-table tbody tr:nth-child(even) td{background:rgba(244,248,248,.55)}
  .data-table tbody tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr:hover td{background:var(--tint)}
  .data-table tbody tr.clickable-row{cursor:pointer}
  .td-mono{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-variant-numeric:tabular-nums}
  .td-right{text-align:right}
  .td-strong{font-weight:700}
  .td-red{color:var(--red-2);font-weight:800}
  .row-link{color:var(--deep-2);font-weight:700;text-decoration:none}
  .row-link:hover{color:var(--sec);text-decoration:underline}
  .ledger-total td{background:linear-gradient(180deg,var(--mist-a),var(--mist-b)) !important;font-weight:800;color:var(--deep-2);border-top:1.5px solid var(--border)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}

  /* side panel */
  .side-panel{display:flex;flex-direction:column;gap:16px}
  .side-panel .side-card{animation:slideRight .45s ease both}
  .side-panel .side-card:nth-child(2){animation-delay:.1s}
  .side-panel .side-card:nth-child(3){animation-delay:.18s}
  .side-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;transition:.2s}
  .side-card:hover{box-shadow:var(--shadow-card-hov)}
  .side-card-header{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.5)}
  .side-card-title{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--deep-2)}
  .side-card-action{font-size:11.5px;font-weight:700;color:var(--sec);cursor:pointer;text-decoration:none}
  .side-card-action:hover{opacity:.65}
  .side-card-body{padding:16px 20px}
  .sum-row{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--line);font-size:12.5px}
  .sum-row:last-of-type{border-bottom:none}
  .sum-lbl{color:var(--ink-soft);font-weight:500}
  .sum-val{font-family:'JetBrains Mono',monospace;font-weight:700;color:var(--deep-2)}
  .sum-big{display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--sec),var(--deep-1));color:#fff;box-shadow:var(--teal-btn-shadow)}
  .sum-big .sb-lbl{font-size:10px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;opacity:.85}
  .sum-big .sb-val{font-family:'JetBrains Mono',monospace;font-size:18px;font-weight:800}
  .qnav{display:flex;flex-direction:column;gap:2px;padding:10px}
  .qnav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .qnav-item:hover{background:var(--tint);color:var(--deep-2)}
  .qnav-item i{width:18px;text-align:center;font-size:12px;color:var(--sec)}
  .contact-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)}
  .contact-item:last-child{border-bottom:none;padding-bottom:0}
  .contact-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
  .contact-icon.email{background:var(--mist-b);color:var(--ink-soft)}
  .contact-icon.phone{background:var(--tint);color:var(--sec)}
  .contact-icon.web{background:var(--warn-tint);color:var(--warn)}
  .contact-type{font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:2px}
  .contact-val{font-size:12.5px;font-weight:500}
  .contact-val.empty{color:#9DB4B4;font-style:italic}

  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}

  /* modals */
  .modal-overlay{position:fixed;inset:0;background:rgba(10,46,50,.55);backdrop-filter:blur(5px);z-index:300;display:none;align-items:flex-start;justify-content:center;padding:48px 20px;overflow-y:auto}
  .modal-overlay.open{display:flex;animation:overlayIn .22s ease both}
  .modal-overlay.closing{animation:overlayOut .18s ease both}
  .modal{background:#fff;border-radius:16px;width:100%;max-width:560px;box-shadow:0 24px 70px -18px rgba(10,46,50,.5);animation:modalIn .3s cubic-bezier(.2,.9,.3,1.15) both;overflow:hidden}
  .modal-overlay.closing .modal{animation:modalOut .18s ease both}
  .modal.wide{max-width:840px}
  .modal.mid{max-width:780px}
  @media (max-width:840px){ .modal.mid{max-width:100%} }
  .modal.mid .split-opts{grid-template-columns:1fr 1fr;gap:12px}
  .modal.mid .split-fields .form-grid{grid-template-columns:1fr 1fr;gap:12px 14px}
  .modal.mid .form-grid{gap:14px 18px}
  .modal.mid .totals-block.fullw{max-width:420px;margin-left:auto}
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;position:relative;overflow:hidden;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2) 0%,var(--deep-3) 100%)}
  .modal-header.danger{background:radial-gradient(120% 150% at 88% -30%, rgba(255,120,120,.25), transparent 55%),linear-gradient(135deg,#991B1B,#7F1D1D)}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,rgba(127,209,192,.4),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
  .modal-header.danger .modal-icon{background:rgba(255,255,255,.15)}
  .modal-head-text{flex:1}
  .modal-title{font-size:16px;font-weight:800;color:#fff}
  .modal-subtitle{font-size:12px;color:rgba(255,255,255,.62);margin-top:2px}
  .modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:13px;transition:.18s;flex-shrink:0}
  .modal-close:hover{background:rgba(255,255,255,.22);transform:rotate(90deg)}
  .modal-body{padding:24px;max-height:calc(100vh - 300px);overflow-y:auto}
  .modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--line);background:linear-gradient(180deg,var(--mist-a),var(--mist-b))}
  .modal-footer .spacer{margin-right:auto}

  /* forms */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 16px}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:6px}
  .form-field label .req{color:var(--red)}
  .form-control{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted)}
  textarea.form-control{resize:vertical;min-height:84px}
  .form-section-title{font-size:10.5px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;color:var(--deep-2);margin:22px 0 12px;display:flex;align-items:center;gap:8px}
  .form-section-title::after{content:'';flex:1;height:1px;background:var(--line)}
  .form-section-title:first-child{margin-top:0}
  .form-alert{display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:8px;font-size:12px;font-weight:600;margin-bottom:16px;animation:fadeIn .4s ease both}
  .form-alert.warn{background:var(--warn-tint);border:1px solid rgba(217,119,6,.3);color:var(--warn)}
  .form-alert.info{background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--deep-1)}
  .form-alert.ok{background:var(--green-tint);border:1px solid rgba(21,128,61,.25);color:var(--green)}
  .items-table{width:100%;border-collapse:collapse;font-size:12.5px;margin-bottom:10px}
  .items-table th{text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--ink-soft);padding:6px 8px;border-bottom:1px solid var(--line);background:var(--mist-a)}
  .items-table td{padding:6px 8px;border-bottom:1px solid var(--line)}
  .items-table .form-control{padding:7px 10px;font-size:12.5px}
  .item-remove{width:28px;height:28px;border-radius:6px;border:none;background:var(--red-tint);color:var(--red-2);cursor:pointer;transition:.15s}
  .item-remove:hover{background:rgba(220,38,38,.18);transform:scale(1.06)}
  .add-item-btn{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--sec);background:none;border:1.5px dashed var(--border);padding:6px 12px;border-radius:8px;cursor:pointer;transition:.15s;font-family:inherit}
  .add-item-btn:hover{border-color:var(--sec);background:var(--tint)}
  .totals-block{margin-left:auto;width:280px;margin-top:12px}
  .totals-block.fullw{margin-left:0;width:100%}
  .totals-row{display:flex;justify-content:space-between;padding:6px 0;font-size:12.5px;color:var(--ink-soft);font-weight:500}
  .totals-row.grand{border-top:1.5px solid var(--border);margin-top:6px;padding-top:10px;font-size:15px;font-weight:800;color:var(--deep-2)}
  .switch{position:relative;width:40px;height:22px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(18px)}
  .switch-row{display:flex;align-items:center;gap:10px}
  .switch-row-label{font-size:12.5px;font-weight:600}
  .switch-row-sub{font-size:11px;color:var(--ink-muted)}
  .apply-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px;cursor:pointer;transition:.15s}
  .apply-row:hover{background:var(--tint);border-color:var(--mint)}
  .apply-row input{accent-color:var(--sec);width:15px;height:15px}
  .apply-info{flex:1}
  .apply-title{font-size:12.5px;font-weight:700}
  .apply-sub{font-size:11px;color:var(--ink-muted)}
  .apply-amt{font-family:'JetBrains Mono',monospace;font-size:12.5px;font-weight:700}
  .dropzone-sm{border:1.5px dashed var(--border);border-radius:10px;padding:16px;text-align:center;font-size:12px;color:var(--ink-soft);cursor:pointer;transition:.15s}
  .dropzone-sm:hover{border-color:var(--sec);background:var(--tint)}
  .dropzone-sm i{margin-right:6px;color:var(--sec)}
  .method-flag{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;padding:3px 10px;border-radius:12px;background:var(--mist-b);color:var(--ink-soft);margin-left:8px}
  #pay-elec,#pay-cash,#pay-split{margin-top:34px}
  #pay-elec .form-section-title,#pay-cash .form-section-title,#pay-split .form-section-title{margin-top:0}

  /* split tiles */
  .split-opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
  .split-opt{position:relative}
  .split-head{display:flex;align-items:center;gap:11px;padding:13px 14px;border:1.5px solid var(--border);border-radius:12px;background:rgba(255,255,255,.78);cursor:pointer;transition:all .22s;position:relative;user-select:none}
  .split-head:hover{border-color:var(--mint);transform:translateY(-1px);box-shadow:var(--shadow-card)}
  .sp-check{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
  .sp-box{width:22px;height:22px;border-radius:7px;border:1.5px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;transition:all .22s;box-shadow:inset 0 1px 2px rgba(10,42,46,.07);flex-shrink:0}
  .sp-box i{font-size:10px;color:#fff;transform:scale(0) rotate(-25deg);transition:transform .25s cubic-bezier(.2,.9,.3,1.5)}
  .sp-check:checked ~ .sp-box{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:var(--sec);box-shadow:var(--teal-btn-shadow)}
  .sp-check:checked ~ .sp-box i{transform:scale(1) rotate(0deg)}
  .sp-icon{width:34px;height:34px;border-radius:9px;background:var(--mist-b);color:var(--ink-soft);display:flex;align-items:center;justify-content:center;font-size:13px;transition:all .22s;flex-shrink:0}
  .sp-text{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px}
  .sp-name{font-size:12.5px;font-weight:700;color:var(--ink);line-height:1.2}
  .sp-hint{font-size:10px;color:var(--ink-muted);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .sp-amt-preview{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:800;color:var(--sec);min-width:0}
  .sp-chev{color:var(--ink-muted);font-size:10px;transition:transform .28s;flex-shrink:0}
  .split-opt.on .split-head{border-color:var(--sec);background:linear-gradient(180deg,rgba(223,247,246,.92),rgba(255,255,255,.88));box-shadow:0 0 0 3px var(--sec-ring),var(--shadow-card);border-bottom-left-radius:0;border-bottom-right-radius:0}
  .split-opt.on .sp-icon{background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;box-shadow:var(--teal-btn-shadow)}
  .split-opt.on .sp-chev{transform:rotate(180deg);color:var(--sec)}
  .split-fields{display:none;border:1.5px solid var(--sec);border-top:1px dashed rgba(16,124,123,.35);border-radius:0 0 12px 12px;background:linear-gradient(180deg,rgba(223,247,246,.55),rgba(255,255,255,.9));padding:14px;margin-top:-1.5px}
  .split-opt.on .split-fields{display:block;animation:expandIn .3s ease both}
  .split-fields .form-grid{gap:10px 12px}
  .split-fields .form-field label{font-size:9.5px}
  .split-fields .form-control{padding:8px 10px;font-size:12.5px}

  /* toast */
  .toast-wrap{position:fixed;bottom:24px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:400px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast i{color:var(--mint);margin-top:2px}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-brand"><div class="topnav-logo">L</div><span class="topnav-app">CamelotBooks</span><span class="topnav-sep">|</span><span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span></div>
  <div class="topnav-center"><a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a><a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a><div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search vendors, bills… <kbd>Ctrl K</kbd></div></div>
  <div class="topnav-right"><button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button><button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button><div class="topnav-user"><div class="topnav-avatar">ES</div><div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div></div></div>
</nav>
<nav class="modulenav">
  <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item active" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Taxation <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cash-register"></i> POS <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-line"></i> Analytics <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
</nav>
<aside class="sidebar">
  <a class="sidebar-item" data-tip="Dashboard" href="#"><i class="fa-solid fa-gauge-high"></i></a>
  <a class="sidebar-item active" data-tip="Vendors" href="#"><i class="fa-solid fa-handshake"></i></a>
  <a class="sidebar-item" data-tip="Purchase Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Bills" href="#"><i class="fa-solid fa-receipt"></i></a>
  <a class="sidebar-item" data-tip="Payments" href="#"><i class="fa-solid fa-credit-card"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main">
  <div class="breadcrumb anim-1"><a href="vendors-index.html">Vendors</a><i class="fa-solid fa-chevron-right sep"></i><span class="current">AHL Group</span></div>
  <div class="page-header anim-1">
    <div><h1 class="page-title">Vendor Detail — AHL Group</h1><div class="page-subtitle"><span>VEN-00001</span><span class="dot"></span><span>Since Jul 2026</span><span class="dot"></span><span>Last activity Oct 24, 2026</span></div></div>
    <div class="page-header-actions">
      <button class="btn-icon" data-tip="Print"><i class="fa-solid fa-print"></i></button>
      <button class="btn-icon" data-tip="Export PDF"><i class="fa-solid fa-file-export"></i></button>
      <button class="btn btn-ghost"><i class="fa-solid fa-ellipsis-vertical"></i> More</button>
      <button class="btn btn-deep" onclick="openModal('modal-edit-vendor')"><i class="fa-solid fa-pen"></i> Edit Vendor</button>
    </div>
  </div>

  <div class="vend-hero glass anim-2">
    <div class="hero-inner">
      <div class="hero-avatar-wrap"><div class="hero-avatar">AG</div></div>
      <div class="hero-info">
        <div class="hero-name-row"><span class="hero-name">AHL Group</span><span class="pill pill-active"><span class="pdot"></span> Active</span><span class="hero-code">VEN-00001</span></div>
        <div class="hero-meta">
          <span class="hero-meta-item"><i class="fa-solid fa-envelope"></i> ahl@ahl.ww</span>
          <span class="hero-meta-item"><i class="fa-solid fa-phone"></i> 0999602605</span>
          <span class="hero-meta-item"><i class="fa-solid fa-clock"></i> Due on receipt</span>
          <span class="hero-meta-item"><i class="fa-solid fa-coins"></i> K — Kwacha</span>
        </div>
      </div>
      <div class="gauge-wrap" data-tip="Credit utilisation vs limit">
        <div class="gauge-holder">
          <svg class="gauge-svg" viewBox="0 0 100 100"><defs><linearGradient id="gg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#7FD1C0"/><stop offset="100%" stop-color="#107C7B"/></linearGradient></defs><circle class="gauge-track" cx="50" cy="50" r="42"/><circle class="gauge-fill" cx="50" cy="50" r="42" style="--dash-to:264"/></svg>
          <div class="gauge-center"><div class="gauge-pct">∞</div><div class="gauge-lbl">No Limit</div></div>
        </div>
        <div class="gauge-stats"><div class="gs-row">Limit <b>0.00</b></div><div class="gs-row">Used <b class="red">0.00</b></div><div class="gs-row">Avail <b>—</b></div></div>
      </div>
      <div class="hero-actions"><button class="btn btn-danger-ghost" onclick="openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i> Archive</button></div>
    </div>
    <div class="quick-chips">
      <a class="quick-chip green" onclick="openModal('modal-new-bill')"><i class="fa-solid fa-plus"></i> New Bill</a>
      <a class="quick-chip" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
      <a class="quick-chip" onclick="switchTab('statement')"><i class="fa-solid fa-file-invoice-dollar"></i> View Statement</a>
      <a class="quick-chip"><i class="fa-solid fa-print"></i> Print</a>
      <a class="quick-chip" onclick="openModal('modal-message')"><i class="fa-solid fa-envelope"></i> Email Statement</a>
      <a class="quick-chip"><i class="fa-solid fa-star"></i> Add to Favourites</a>
    </div>
  </div>

  <div class="fin-strip">
    <div class="fin-card highlight">
      <div class="fin-card-top"><span class="fin-label">Open Balance (K)</span><div class="fin-icon-wrap"><i class="fa-solid fa-wallet"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">No open bills · fully settled</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Total Billed</span><div class="fin-icon-wrap"><i class="fa-solid fa-receipt"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">0 bills issued to date</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Opening Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-scale-balanced"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">No opening balance set</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Payment Terms</span><div class="fin-icon-wrap"><i class="fa-solid fa-clock-rotate-left"></i></div></div>
      <div class="fin-value terms">Due on receipt</div>
      <div class="fin-sub">Immediate settlement expected</div>
    </div>
  </div>

  <div class="content-grid anim-4">
    <div class="tabs-card glass">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="info">Information</button>
        <button class="tab-btn" data-tab="transactions">Transactions <span class="tab-count">0</span></button>
        <button class="tab-btn" data-tab="statement">Statement</button>
        <button class="tab-btn" data-tab="payments">Payments <span class="tab-count">0</span></button>
        <button class="tab-btn" data-tab="notes">Notes <span class="tab-count">0</span></button>
      </div>
      <div class="tab-body">
        <!-- INFO -->
        <div class="tab-panel active" id="panel-info">
          <div class="overview-section-title">Contact Information</div>
          <div class="info-grid">
            <div class="info-cell"><div class="info-cell-label">Display Name</div><div class="info-cell-value muted">Not set</div></div>
            <div class="info-cell"><div class="info-cell-label">Email Address</div><div class="info-cell-value">ahl@ahl.ww</div></div>
            <div class="info-cell"><div class="info-cell-label">Phone Number</div><div class="info-cell-value mono">0999602605</div></div>
            <div class="info-cell"><div class="info-cell-label">Currency</div><div class="info-cell-value mono">K — Kwacha</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms</div><div class="info-cell-value">Due on receipt</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms (Days)</div><div class="info-cell-value mono">0</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance</div><div class="info-cell-value mono">K 0.00</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance Date</div><div class="info-cell-value muted">Not set</div></div>
            <div class="info-cell"><div class="info-cell-label">Credit Limit</div><div class="info-cell-value muted">Unlimited</div></div>
          </div>
          <div class="overview-section-title">Addresses</div>
          <div class="addresses-row">
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-file-invoice"></i> Billing Address</div><div class="address-block-value empty">No billing address on file. Click Edit to add one.</div></div>
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-location-dot"></i> Remit To Address</div><div class="address-block-value empty">No remit-to address on file. Click Edit to add one.</div></div>
          </div>
          <div class="overview-section-title">Recent Activity</div>
          <div class="timeline">
            <div class="timeline-item"><div class="timeline-dot blue"></div><div class="timeline-title">Vendor record created</div><div class="timeline-desc">AHL Group was added to the directory by Elvis Seyama.</div><div class="timeline-time">Jul 12, 2026 · 08:30 AM</div></div>
          </div>
        </div>
        <!-- TRANSACTIONS -->
        <div class="tab-panel" id="panel-transactions">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search transactions…"></div>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Types</option><option>Bills</option><option>Payments</option><option>Credits</option></select>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Oct 01 – Oct 31, 2026</button>
            </div>
            <span class="chip-count">0 transactions</span>
          </div>
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="empty-title">No transactions yet</div>
            <div class="empty-sub">Bills, payments and credits for this vendor will appear here.</div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-new-bill')"><i class="fa-solid fa-plus"></i> New Bill</button>
          </div>
        </div>
        <!-- STATEMENT -->
        <div class="tab-panel" id="panel-statement">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Jul 01 – Oct 31, 2026</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export PDF</button>
              <button class="btn btn-ghost btn-sm" onclick="openModal('modal-message')"><i class="fa-solid fa-envelope"></i> Email Statement</button>
            </div>
            <span style="font-size:12px;color:var(--ink-soft);font-weight:600">Closing balance: <strong>K 0.00</strong></span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Entry</th><th>Type</th><th>Reference</th><th class="td-right">Debit</th><th class="td-right">Credit</th><th class="td-right">Balance</th></tr></thead>
                <tbody>
                  <tr><td>Jul 12, 2026</td><td class="td-mono">OB-0001</td><td><span class="type-chip type-jn">Opening</span></td><td class="td-mono">—</td><td class="td-right td-mono">—</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">0.00</td></tr>
                  <tr class="ledger-total"><td colspan="4">Totals</td><td class="td-right td-mono">0.00</td><td class="td-right td-mono">0.00</td><td class="td-right td-mono">0.00</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- PAYMENTS -->
        <div class="tab-panel" id="panel-payments">
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="empty-title">No payments recorded</div>
            <div class="empty-sub">Settlements paid to this vendor will appear here.</div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-plus"></i> Record Payment</button>
          </div>
        </div>
        <!-- NOTES -->
        <div class="tab-panel" id="panel-notes">
          <div class="tab-toolbar">
            <div class="toolbar-left"><button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> All notes</button></div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-note')"><i class="fa-solid fa-note-sticky"></i> Add Note</button>
          </div>
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-note-sticky"></i></div>
            <div class="empty-title">No notes yet</div>
            <div class="empty-sub">Internal notes and reminders about this vendor will appear here.</div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-note')"><i class="fa-solid fa-plus"></i> Add Note</button>
          </div>
        </div>
      </div>
    </div>

    <!-- SIDE PANEL -->
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Summary</span></div>
        <div class="side-card-body">
          <div class="sum-row"><span class="sum-lbl">Opening Balance</span><span class="sum-val">0.00</span></div>
          <div class="sum-row"><span class="sum-lbl">Total Billed</span><span class="sum-val">0.00</span></div>
          <div class="sum-row"><span class="sum-lbl">Total Paid</span><span class="sum-val">0.00</span></div>
          <div class="sum-row"><span class="sum-lbl">Credit Limit</span><span class="sum-val">0.00</span></div>
          <div class="sum-big"><span class="sb-lbl">Open Balance</span><span class="sb-val">0.00</span></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Quick Nav</span></div>
        <div class="qnav">
          <a class="qnav-item" onclick="openModal('modal-new-bill')"><i class="fa-solid fa-receipt"></i> New Bill</a>
          <a class="qnav-item" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
          <a class="qnav-item" onclick="switchTab('statement')"><i class="fa-solid fa-chart-column"></i> View Statement</a>
          <a class="qnav-item"><i class="fa-solid fa-print"></i> Print</a>
          <a class="qnav-item" onclick="openModal('modal-message')"><i class="fa-solid fa-envelope"></i> Email Statement</a>
          <a class="qnav-item" href="vendors-index.html"><i class="fa-solid fa-handshake"></i> All Vendors</a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Contact Details</span><a class="side-card-action" href="#" onclick="openModal('modal-edit-vendor')">Edit</a></div>
        <div class="side-card-body">
          <div class="contact-item"><div class="contact-icon email"><i class="fa-solid fa-envelope"></i></div><div><div class="contact-type">Email</div><div class="contact-val">ahl@ahl.ww</div></div></div>
          <div class="contact-item"><div class="contact-icon phone"><i class="fa-solid fa-phone"></i></div><div><div class="contact-type">Phone</div><div class="contact-val" style="font-family:'JetBrains Mono',monospace">0999602605</div></div></div>
          <div class="contact-item"><div class="contact-icon web"><i class="fa-solid fa-globe"></i></div><div><div class="contact-type">Website</div><div class="contact-val empty">Not provided</div></div></div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═══ MODALS ═══ -->

<!-- EDIT VENDOR -->
<div class="modal-overlay" id="modal-edit-vendor">
  <div class="modal wide">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div><div class="modal-head-text"><div class="modal-title">Edit Vendor</div><div class="modal-subtitle">Update master data for VEN-00001</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Name <span class="req">*</span></label><input class="form-control" value="AHL Group"></div>
        <div class="form-field"><label>Display Name</label><input class="form-control" placeholder="e.g. J. Banda Trading"></div>
        <div class="form-field"><label>Vendor Code</label><input class="form-control" value="VEN-00001" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option selected>Office Supplies</option><option>IT Equipment</option><option>Services</option><option>Logistics</option><option>Raw Materials</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option selected>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" placeholder="TPIN / VAT Registration"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" value="ahl@ahl.ww"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" value="0999602605"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" placeholder="https://..."></div>
      </div>
      <div class="form-section-title">Billing & Remittance</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control" placeholder="Street, City, Country..."></textarea></div>
        <div class="form-field full"><label>Remit To Address</label><textarea class="form-control" placeholder="Where payments should be sent..."></textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms</label><select class="form-control"><option selected>Due on receipt</option><option>Net 15</option><option>Net 30</option><option>Net 60</option></select></div>
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="0"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option>Bank Transfer</option><option>Cheque</option><option>Cash</option><option>Mobile Money</option></select></div>
        <div class="form-field"><label>Opening Balance Date</label><input class="form-control" type="date"></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Vendor is Active</div><div class="switch-row-sub">Currently active — toggle to archive/deactivate</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Vendor</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep" onclick="saveToast()"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- RECORD BILL PAYMENT -->
<div class="modal-overlay" id="modal-record-payment">
  <div class="modal mid">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-credit-card"></i></div><div class="modal-head-text"><div class="modal-title">Record Payment</div><div class="modal-subtitle">Pay an outstanding bill to AHL Group</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Payment Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-28"></div>
        <div class="form-field"><label>Payment Method <span class="req">*</span></label>
          <select class="form-control" id="pay-method">
            <option value="">— Select method —</option>
            <option value="bank">Bank Transfer</option>
            <option value="mobile">Mobile Money</option>
            <option value="card">Card</option>
            <option value="cash">Cash</option>
            <option value="split">Split Payment</option>
          </select>
        </div>
        <div class="form-field"><label>Paying Bank Account</label><select class="form-control"><option>Zanaco — ****4402</option><option>Stanbic — ****1188</option><option>Cash Drawer — HQ</option></select></div>
        <div class="form-field"><label>Payment Amount (K) <span class="req">*</span></label><input class="form-control" id="pay-amount" style="text-align:right" value="0.00"></div>
      </div>
      <div id="pay-elec" style="display:none">
        <div class="form-section-title">Transaction Details <span class="method-flag" id="elec-flag"><i class="fa-solid fa-building-columns"></i> Bank Transfer</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control" id="pay-ref" placeholder="e.g. TXN-88213 / UTR-004512"></div>
          <div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control" id="pay-provider"></select></div>
          <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Main Operating Account"></div>
          <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. AHL Group"></div>
        </div>
      </div>
      <div id="pay-cash" style="display:none">
        <div class="form-section-title">Cash Handling <span class="method-flag" style="background:var(--green-tint);color:var(--green)"><i class="fa-solid fa-money-bill"></i> Cash</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div>
          <div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div>
        </div>
      </div>
      <div id="pay-split" style="display:none">
        <div class="form-section-title">Split Allocation <span class="method-flag"><i class="fa-solid fa-layer-group"></i> Tick every method used</span></div>
        <div class="split-opts" id="split-opts">
          <div class="split-opt" data-method="cash"><label class="split-head"><input type="checkbox" class="sp-check"><span class="sp-box"><i class="fa-solid fa-check"></i></span><span class="sp-icon"><i class="fa-solid fa-money-bill"></i></span><span class="sp-text"><span class="sp-name">Cash</span><span class="sp-hint">Tendered & change</span></span><span class="sp-amt-preview"></span><i class="fa-solid fa-chevron-down sp-chev"></i></label><div class="split-fields"><div class="form-grid"><div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div><div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div><div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div></div></div></div>
          <div class="split-opt" data-method="bank"><label class="split-head"><input type="checkbox" class="sp-check"><span class="sp-box"><i class="fa-solid fa-check"></i></span><span class="sp-icon"><i class="fa-solid fa-building-columns"></i></span><span class="sp-text"><span class="sp-name">Bank Transfer</span><span class="sp-hint">Reference & bank details</span></span><span class="sp-amt-preview"></span><i class="fa-solid fa-chevron-down sp-chev"></i></label><div class="split-fields"><div class="form-grid"><div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div><div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. UTR-004512"></div><div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control sp-provider"><option>National Bank of Malawi</option><option>Standard Bank Malawi</option><option>FNB Malawi</option><option>Absa Bank Malawi</option></select></div><div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Vendor Settlement Account"></div><div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. AHL Group"></div></div></div></div>
          <div class="split-opt" data-method="card"><label class="split-head"><input type="checkbox" class="sp-check"><span class="sp-box"><i class="fa-solid fa-check"></i></span><span class="sp-icon"><i class="fa-solid fa-credit-card"></i></span><span class="sp-text"><span class="sp-name">Card</span><span class="sp-hint">Terminal / scheme details</span></span><span class="sp-amt-preview"></span><i class="fa-solid fa-chevron-down sp-chev"></i></label><div class="split-fields"><div class="form-grid"><div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div><div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. AUTH-771203"></div><div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control sp-provider"><option>Visa</option><option>Mastercard</option><option>American Express</option></select></div><div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Merchant Settlement"></div><div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Acme Corporation"></div></div></div></div>
          <div class="split-opt" data-method="mobile"><label class="split-head"><input type="checkbox" class="sp-check"><span class="sp-box"><i class="fa-solid fa-check"></i></span><span class="sp-icon"><i class="fa-solid fa-mobile-screen"></i></span><span class="sp-text"><span class="sp-name">Mobile Money</span><span class="sp-hint">Wallet transaction capture</span></span><span class="sp-amt-preview"></span><i class="fa-solid fa-chevron-down sp-chev"></i></label><div class="split-fields"><div class="form-grid"><div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div><div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. MOMO-558812"></div><div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control sp-provider"><option>TNM Mpamba</option><option>Airtel Money</option></select></div><div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Wallet Settlement"></div><div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. AHL Group"></div></div></div></div>
        </div>
        <div class="totals-block fullw">
          <div class="totals-row"><span>Payment Amount</span><span class="td-mono" id="sp-total">0.00</span></div>
          <div class="totals-row"><span>Allocated Across Selected Methods</span><span class="td-mono" id="sp-alloc">0.00</span></div>
          <div class="totals-row grand"><span>Unallocated</span><span class="td-mono" id="sp-remain">0.00</span></div>
        </div>
        <div id="split-banner" style="margin-top:12px"></div>
      </div>
      <div class="form-section-title">Apply To Open Bills</div>
      <div class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> This vendor has no open bills to apply against.</div>
      <div class="form-field" style="margin-top:14px"><label>Payment Note</label><textarea class="form-control" style="min-height:60px" placeholder="Optional…"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-deep" id="post-payment" disabled><i class="fa-solid fa-check"></i> Record Payment</button></div>
  </div>
</div>

<!-- NEW BILL -->
<div class="modal-overlay" id="modal-new-bill">
  <div class="modal wide">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-receipt"></i></div><div class="modal-head-text"><div class="modal-title">New Bill</div><div class="modal-subtitle">Record a supplier bill from AHL Group</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-section-title">Bill Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Vendor</label><input class="form-control" value="AHL Group (VEN-00001)" disabled></div>
        <div class="form-field"><label>Bill Number <span class="req">*</span></label><input class="form-control" value="BILL-2026-0001"></div>
        <div class="form-field"><label>Bill Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-28"></div>
        <div class="form-field"><label>Due Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-28"></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Payment Terms</label><select class="form-control"><option>Due on Receipt</option><option>Net 15</option><option>Net 30</option></select></div>
        <div class="form-field full"><label>Linked Purchase Order</label><select class="form-control"><option>— None —</option><option>PO-2026-0004</option><option>PO-2026-0007</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="bill-items">
        <thead><tr><th style="width:46%">Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" placeholder="Item description"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="0.00"></td><td class="td-right td-mono">0.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="bill-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">0.00</span></div>
        <div class="totals-row"><span>VAT 16.5%</span><span class="td-mono">0.00</span></div>
        <div class="totals-row grand"><span>Total Due</span><span class="td-mono">0.00</span></div>
      </div>
      <div class="form-section-title">Attachments & Notes</div>
      <div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach supporting documents (PDF/JPG)</div>
      <div class="form-field" style="margin-top:12px"><label>Internal Notes</label><textarea class="form-control" placeholder="Optional notes for internal reference…"></textarea></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-ghost">Save as Draft</button><button class="btn btn-deep" onclick="saveToast()"><i class="fa-solid fa-check"></i> Save Bill</button></div>
  </div>
</div>

<!-- ADD NOTE -->
<div class="modal-overlay" id="modal-add-note">
  <div class="modal">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-note-sticky"></i></div><div class="modal-head-text"><div class="modal-title">Add Note</div><div class="modal-subtitle">Internal note or reminder on this vendor</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Note Type</label><select class="form-control"><option>Internal Note</option><option>Reminder</option><option>Follow-up</option></select></div>
        <div class="form-field"><label>Reminder Date</label><input class="form-control" type="date"></div>
        <div class="form-field full"><label>Title <span class="req">*</span></label><input class="form-control" placeholder="e.g. Follow up on delivery schedule"></div>
        <div class="form-field full"><label>Note <span class="req">*</span></label><textarea class="form-control" placeholder="Details visible to internal users only…"></textarea></div>
        <div class="form-field full"><div class="switch-row"><label class="switch"><input type="checkbox"><span class="switch-slider"></span></label><div><div class="switch-row-label">Pin to vendor profile</div><div class="switch-row-sub">Shows the note at the top of the Overview tab</div></div></div></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-deep" onclick="saveToast()"><i class="fa-solid fa-check"></i> Save Note</button></div>
  </div>
</div>

<!-- MESSAGE -->
<div class="modal-overlay" id="modal-message">
  <div class="modal">
    <div class="modal-header"><div class="modal-icon"><i class="fa-solid fa-envelope"></i></div><div class="modal-head-text"><div class="modal-title">Send Message</div><div class="modal-subtitle">Email or SMS the vendor contact</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field full"><label>To</label><input class="form-control" value="ahl@ahl.ww" readonly></div>
        <div class="form-field"><label>Channel</label><select class="form-control"><option>Email</option><option>SMS</option></select></div>
        <div class="form-field"><label>Template</label><select class="form-control"><option>— Blank —</option><option>Statement Reminder</option><option>Payment Confirmation</option><option>Bill Acknowledgement</option></select></div>
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Statement of Account — AHL Group"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear AHL Group,

Please find attached your latest statement of account showing a nil balance. Kindly confirm receipt.

Regards,
Elvis Seyama — Acme Corporation</textarea></div>
        <div class="form-field full"><div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach files (optional)</div></div>
      </div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-deep" onclick="saveToast()"><i class="fa-solid fa-paper-plane"></i> Send Message</button></div>
  </div>
</div>

<!-- ARCHIVE CONFIRM -->
<div class="modal-overlay" id="modal-archive-confirm">
  <div class="modal" style="max-width:440px">
    <div class="modal-header danger"><div class="modal-icon"><i class="fa-solid fa-box-archive"></i></div><div class="modal-head-text"><div class="modal-title">Archive Vendor?</div><div class="modal-subtitle">AHL Group (VEN-00001)</div></div><button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button></div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">Archiving will prevent new purchase orders, bills, and payments from being recorded against this vendor. Existing transaction history will remain intact.</p>
      <div style="background:var(--green-tint);border:1px solid rgba(21,128,61,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--green);font-weight:600;display:flex;gap:8px;align-items:flex-start"><i class="fa-solid fa-circle-check" style="margin-top:2px"></i><span>This vendor has a nil balance — safe to archive.</span></div>
    </div>
    <div class="modal-footer"><button class="btn btn-ghost" data-close-modal>Cancel</button><button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none" onclick="saveToast()"><i class="fa-solid fa-check"></i> Yes, Archive</button></div>
  </div>
</div>

<div class="toast-wrap" id="toasts"></div>

<script>
/* tabs */
function switchTab(tabId){document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));const btn=document.querySelector(`[data-tab="${tabId}"]`);if(btn){btn.classList.add('active');document.getElementById('panel-'+tabId).classList.add('active');}}
document.querySelectorAll('.tab-btn').forEach(btn=>btn.addEventListener('click',()=>switchTab(btn.dataset.tab)));

/* modals */
const openModal=id=>{const ov=document.getElementById(id);ov.classList.remove('closing');ov.classList.add('open');document.body.classList.add('modal-open');};
const closeModal=ov=>{ov.classList.add('closing');setTimeout(()=>{ov.classList.remove('open','closing');document.body.classList.remove('modal-open');},180);};
document.querySelectorAll('[data-close-modal]').forEach(b=>b.addEventListener('click',()=>closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov=>ov.addEventListener('mousedown',e=>{if(e.target===ov)closeModal(ov);}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(closeModal);});

/* line items */
document.querySelectorAll('[data-add-item]').forEach(btn=>btn.addEventListener('click',()=>{const tbody=document.getElementById(btn.dataset.addItem).querySelector('tbody');const row=tbody.insertRow();row.style.animation='fadeUp .3s ease both';row.innerHTML='<td><input class="form-control" placeholder="Item description"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="0.00"></td><td class="td-right td-mono">0.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td>';}));
document.addEventListener('click',e=>{const rm=e.target.closest('.item-remove');if(rm)rm.closest('tr').remove();});

/* payment logic */
const PROVIDERS={bank:['National Bank of Malawi','Standard Bank Malawi','FNB Malawi','Absa Bank Malawi'],mobile:['TNM Mpamba','Airtel Money'],card:['Visa','Mastercard','American Express']};
const METHOD_META={bank:{label:'Bank Transfer',icon:'fa-building-columns'},mobile:{label:'Mobile Money',icon:'fa-mobile-screen'},card:{label:'Card',icon:'fa-credit-card'}};
const fmt=n=>Number(n||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
const num=v=>parseFloat(String(v).replace(/,/g,''))||0;
const payMethod=document.getElementById('pay-method'),payAmount=document.getElementById('pay-amount'),elecPanel=document.getElementById('pay-elec'),cashPanel=document.getElementById('pay-cash'),splitPanel=document.getElementById('pay-split'),splitOpts=[...document.querySelectorAll('#split-opts .split-opt')],postBtn=document.getElementById('post-payment');
function populateProviders(sel,method){sel.innerHTML=(PROVIDERS[method]||[]).map(p=>`<option>${p}</option>`).join('');}
function syncMethodPanels(){const v=payMethod.value;elecPanel.style.display=['bank','mobile','card'].includes(v)?'':'none';cashPanel.style.display=v==='cash'?'':'none';splitPanel.style.display=v==='split'?'':'none';if(elecPanel.style.display===''){populateProviders(document.getElementById('pay-provider'),v);document.getElementById('elec-flag').innerHTML=`<i class="fa-solid ${METHOD_META[v].icon}"></i> ${METHOD_META[v].label}`;}if(v==='split')recomputeSplits();else validatePayment();}
function checkedOpts(){return splitOpts.filter(o=>o.querySelector('.sp-check').checked);}
function recomputeSplits(){const total=num(payAmount.value);const checked=checkedOpts();const alloc=checked.reduce((s,o)=>s+num(o.querySelector('.sp-amt').value),0);const remain=total-alloc;document.getElementById('sp-total').textContent=fmt(total);document.getElementById('sp-alloc').textContent=fmt(alloc);document.getElementById('sp-remain').textContent=(remain<0?'-':'')+fmt(Math.abs(remain));const banner=document.getElementById('split-banner');if(checked.length===0){banner.innerHTML='<div class="form-alert info" style="margin:0"><i class="fa-solid fa-hand-pointer"></i> Tick the methods used in this split — each selected method expands its capture fields.</div>';}else if(Math.abs(remain)<=0.01){banner.innerHTML=`<div class="form-alert ok" style="margin:0"><i class="fa-solid fa-circle-check"></i> Fully allocated across ${checked.length} method${checked.length>1?'s':''}.</div>`;}else if(remain>0){banner.innerHTML=`<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> K ${fmt(remain)} still unallocated — enter amounts in the expanded method panels.</div>`;}else{banner.innerHTML=`<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> Over-allocated by K ${fmt(Math.abs(remain))} — selected amounts exceed the payment.</div>`;}validatePayment();}
splitOpts.forEach(opt=>{const chk=opt.querySelector('.sp-check');chk.addEventListener('change',()=>{opt.classList.toggle('on',chk.checked);recomputeSplits();});opt.querySelector('.sp-amt').addEventListener('input',e=>{const v=num(e.target.value);opt.querySelector('.sp-amt-preview').textContent=v>0?fmt(v):'';recomputeSplits();});const ref=opt.querySelector('.sp-ref');if(ref)ref.addEventListener('input',validatePayment);});
function validatePayment(){const v=payMethod.value;let ok=v!==''&&num(payAmount.value)>0;if(['bank','mobile','card'].includes(v))ok=ok&&document.getElementById('pay-ref').value.trim()!=='';if(v==='split'){const checked=checkedOpts();const total=num(payAmount.value);const alloc=checked.reduce((s,o)=>s+num(o.querySelector('.sp-amt').value),0);ok=ok&&checked.length>0&&Math.abs(total-alloc)<=0.01;checked.forEach(o=>{if(o.dataset.method!=='cash'){const r=o.querySelector('.sp-ref');if(r&&!r.value.trim())ok=false;}});}postBtn.disabled=!ok;}
payMethod.addEventListener('change',syncMethodPanels);
payAmount.addEventListener('input',()=>{if(payMethod.value==='split')recomputeSplits();else validatePayment();});
document.getElementById('pay-ref').addEventListener('input',validatePayment);
postBtn.addEventListener('click',()=>{closeModal(document.getElementById('modal-record-payment'));toast('Payment recorded','Saved successfully.');});

/* sidebar */
document.querySelectorAll('.sidebar-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));
document.querySelectorAll('.module-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));

/* toast helpers */
function toast(title,sub){const t=document.createElement('div');t.className='toast';t.innerHTML=`<i class="fa-solid fa-circle-check"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub}</div></div>`;document.getElementById('toasts').appendChild(t);setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3200);}
function saveToast(){document.querySelectorAll('.modal-overlay.open').forEach(closeModal);toast('Saved','Changes saved successfully.');}
</script>
</body>
</html>
```

### MOCKUP 3 — `vendor-create.html`

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Vendor | CamelotBooks ERP</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root{
    --deep-1:#17565D; --deep-2:#0C3539; --deep-3:#0A2E32;
    --row1:#11454B; --row2:#DCE6E7;
    --sec:#107C7B; --sec-2:#0C7E7D;
    --ink:#0B2A2D; --ink-soft:#52696B; --ink-muted:#5F7A7C;
    --mist-a:#F4F8F8; --mist-b:#E8F0F0; --line:#E2ECEC; --border:#DCEAEA;
    --card:rgba(255,255,255,.75);
    --shadow-card:0 1px 2px rgba(10,42,46,.05),0 12px 32px -8px rgba(10,42,46,.10),0 2px 6px rgba(10,42,46,.04);
    --shadow-card-hov:0 2px 4px rgba(10,42,46,.06),0 20px 46px -12px rgba(10,42,46,.18),0 4px 10px rgba(10,42,46,.06);
    --green:#15803D; --warn:#D97706; --red:#DC2626; --red-2:#B91C1C;
    --tint:#DFF7F6; --mint:#7FD1C0;
    --green-tint:rgba(21,128,61,.10); --warn-tint:rgba(217,119,6,.12); --red-tint:rgba(220,38,38,.10);
    --blue:#1D6FB8; --blue-tint:rgba(29,111,184,.10);
    --purple:#7C3AED; --purple-tint:rgba(124,58,237,.10);
    --sec-ring:rgba(16,124,123,.16);
    --teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
    --cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
    --r-md:12px; --r-lg:16px; --r-xl:20px;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',sans-serif;color:var(--ink);min-height:100vh;overflow-x:hidden;
    background:radial-gradient(1100px 480px at 88% -12%, rgba(127,209,192,.28), transparent 60%),
      radial-gradient(900px 420px at -8% -4%, rgba(16,124,123,.12), transparent 55%),
      linear-gradient(180deg,var(--mist-a) 0%,#ECF4F4 45%,var(--mist-b) 100%);
    background-attachment:fixed;}
  ::selection{background:var(--tint);color:var(--deep-2)}
  .glass{background:var(--card);backdrop-filter:blur(14px) saturate(1.15);-webkit-backdrop-filter:blur(14px) saturate(1.15)}
  @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  @keyframes slideRight{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes shake{10%,90%{transform:translateX(-1px)}20%,80%{transform:translateX(2px)}30%,50%,70%{transform:translateX(-4px)}40%,60%{transform:translateX(4px)}}
  @keyframes checkPop{0%{transform:scale(.4);opacity:0}60%{transform:scale(1.15)}100%{transform:scale(1);opacity:1}}
  @keyframes toastIn{from{opacity:0;transform:translateY(12px) scale(.96)}to{opacity:1;transform:none}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}
  .anim-4{animation:fadeUp .4s ease both;animation-delay:.26s}
  .anim-5{animation:fadeUp .4s ease both;animation-delay:.33s}

  /* ═ CHROME ═ */
  .topnav{position:fixed;top:0;left:0;right:0;z-index:100;height:60px;display:flex;align-items:center;padding:0 24px;background:linear-gradient(180deg,var(--row1) 0%,var(--deep-2) 100%);box-shadow:0 2px 18px rgba(10,46,50,.35),inset 0 1px 0 rgba(255,255,255,.08)}
  .topnav::after{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(600px 120px at 82% 0%,rgba(127,209,192,.16),transparent 70%)}
  .topnav>*{position:relative;z-index:1}
  .topnav-brand{display:flex;align-items:center;gap:10px;padding-right:24px;border-right:1px solid rgba(255,255,255,.14);margin-right:20px;flex-shrink:0}
  .topnav-logo{width:32px;height:32px;background:linear-gradient(180deg,var(--mint),var(--sec));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;color:var(--deep-3)}
  .topnav-app{font-size:14px;font-weight:700;color:#fff;letter-spacing:-.3px}
  .topnav-sep{color:rgba(255,255,255,.32);margin:0 8px;font-size:13px}
  .topnav-org{font-size:13px;color:rgba(255,255,255,.60)}
  .topnav-org strong{color:rgba(255,255,255,.95);font-weight:600}
  .topnav-center{flex:1;display:flex;justify-content:center;gap:8px}
  .topnav-pill{display:flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:600;cursor:pointer;transition:.2s;text-decoration:none}
  .topnav-pill:hover{background:rgba(255,255,255,.15);color:#fff}
  .topnav-pill .badge-count{background:linear-gradient(180deg,var(--mint),var(--sec));color:var(--deep-3);font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;min-width:18px;text-align:center}
  .topnav-search{display:flex;align-items:center;gap:8px;padding:6px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.14);border-radius:20px;color:rgba(255,255,255,.55);font-size:12.5px;cursor:pointer;min-width:180px}
  .topnav-search kbd{margin-left:auto;background:rgba(255,255,255,.12);padding:1px 5px;border-radius:4px;font-size:10px;color:rgba(255,255,255,.6)}
  .topnav-right{display:flex;align-items:center;gap:12px;margin-left:20px}
  .topnav-icon-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;color:rgba(255,255,255,.68);transition:.2s;position:relative;background:transparent;border:none;font-size:14px}
  .topnav-icon-btn:hover{background:rgba(255,255,255,.13);color:#fff}
  .topnav-icon-btn .notif-dot{position:absolute;top:6px;right:6px;width:7px;height:7px;background:var(--mint);border-radius:50%;border:1.5px solid var(--deep-2);animation:pulseMint 2.4s infinite}
  .topnav-user{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:24px;cursor:pointer}
  .topnav-avatar{width:30px;height:30px;background:linear-gradient(135deg,var(--mint),var(--sec));border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:var(--deep-3)}
  .topnav-user-name{font-size:12px;font-weight:600;color:#fff}
  .topnav-user-role{font-size:10px;color:rgba(255,255,255,.55);letter-spacing:.3px}
  .modulenav{position:fixed;top:60px;left:0;right:0;z-index:99;height:48px;background:linear-gradient(180deg,#E4EDEE,var(--row2));border-bottom:1px solid var(--line);display:flex;align-items:center;padding:0 24px 0 80px;gap:2px}
  .module-item{display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;white-space:nowrap;text-decoration:none}
  .module-item i{font-size:13px;opacity:.75}
  .module-item:hover{background:rgba(255,255,255,.65);color:var(--deep-2)}
  .module-item.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .module-item.active i{opacity:1;color:var(--mint)}
  .module-item .chevron{font-size:9px;opacity:.5;margin-left:2px}
  .sidebar{position:fixed;top:108px;left:0;bottom:0;width:60px;z-index:98;background:rgba(255,255,255,.82);backdrop-filter:blur(10px);border-right:1px solid var(--line);display:flex;flex-direction:column;align-items:center;padding:16px 0;gap:4px}
  .sidebar-item{width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:10px;color:var(--ink-muted);cursor:pointer;transition:.18s;font-size:15px;position:relative;text-decoration:none}
  .sidebar-item:hover{background:var(--tint);color:var(--sec);transform:scale(1.06)}
  .sidebar-item.active{background:var(--tint);color:var(--sec)}
  .sidebar-item.active::before{content:'';position:absolute;left:-10px;width:3px;height:20px;background:linear-gradient(180deg,var(--sec),var(--mint));border-radius:0 3px 3px 0}
  .sidebar-divider{width:28px;height:1px;background:var(--line);margin:6px 0}
  .sidebar-spacer{flex:1}
  .main{margin-top:108px;margin-left:60px;padding:28px 32px 60px}
  .breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:18px;font-size:12.5px}
  .breadcrumb a{color:var(--ink-muted);text-decoration:none;font-weight:500}
  .breadcrumb a:hover{color:var(--sec)}
  .breadcrumb .sep{color:var(--border);font-size:10px}
  .breadcrumb .current{color:var(--ink);font-weight:600}
  .page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;gap:20px}
  .page-title{font-size:26px;font-weight:800;letter-spacing:-.6px;line-height:1.2;margin-bottom:6px}
  .page-subtitle{font-size:13px;color:var(--ink-soft);font-weight:500}
  .page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 14px;font-size:12px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover{border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover{filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}

  /* cards / hairlines — BOTTOM, FAINT */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s;background:rgba(255,255,255,.78)}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:6px 6px 0 0;background:linear-gradient(90deg,transparent 0%,rgba(127,209,192,.35) 22%,rgba(16,124,123,.45) 50%,rgba(127,209,192,.35) 78%,transparent 100%);box-shadow:0 -1px 6px rgba(16,124,123,.12);pointer-events:none;z-index:1}
  .card-head{display:flex;align-items:center;gap:12px;padding:18px 24px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:34px;height:34px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-head-icon.amber{background:linear-gradient(180deg,#E8930C,var(--warn))}
  .card-head-icon.purple{background:linear-gradient(180deg,#8B5CF6,var(--purple))}
  .card-head-icon.blue{background:linear-gradient(180deg,#2B7FC4,var(--blue))}
  .card-title{font-size:15px;font-weight:800;color:var(--deep-2);letter-spacing:-.2px}
  .card-sub{font-size:11.5px;color:var(--ink-muted);margin-top:2px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px}
  .card-body{padding:24px}

  /* forms */
  .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 20px}
  .form-grid.cols-3{grid-template-columns:repeat(3,1fr)}
  .form-grid .full{grid-column:1/-1}
  .form-field label{display:block;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:7px}
  .form-field label .req{color:var(--red);margin-left:2px}
  .form-control{width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:9px;font-family:inherit;font-size:13.5px;color:var(--ink);background:#fff;outline:none;transition:.15s}
  .form-control::placeholder{color:#A9BCBD}
  .form-control:focus{border-color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}
  .form-control:disabled{background:var(--mist-a);color:var(--ink-muted);cursor:not-allowed}
  textarea.form-control{resize:vertical;min-height:96px;line-height:1.6}
  select.form-control{appearance:none;-webkit-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235F7A7C' d='M6 8L2 4h8z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px;cursor:pointer}
  .field-hint{font-size:11px;color:var(--ink-muted);margin-top:5px;font-weight:500}
  .field-error{font-size:11px;color:var(--red-2);margin-top:5px;font-weight:600;display:none;align-items:center;gap:5px}
  .field-error.show{display:flex}
  .form-field.invalid .form-control{border-color:var(--red);box-shadow:0 0 0 3px var(--red-tint);animation:shake .4s}
  .input-affix{position:relative}
  .input-affix .affix{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--ink-muted);font-weight:700;pointer-events:none}
  .input-affix .form-control{padding-left:32px}
  .input-money{text-align:right;font-family:'JetBrains Mono',monospace}

  /* switch */
  .switch{position:relative;width:42px;height:23px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:17px;height:17px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(19px)}
  .switch-row{display:flex;align-items:center;gap:12px}
  .switch-row-label{font-size:13px;font-weight:700;color:var(--ink)}
  .switch-row-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}

  /* terms presets */
  .terms-presets{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
  .term-preset{padding:7px 14px;border:1.5px solid var(--border);border-radius:20px;font-size:12px;font-weight:700;color:var(--ink-soft);cursor:pointer;transition:.18s;background:rgba(255,255,255,.7);font-family:inherit}
  .term-preset:hover{border-color:var(--mint);color:var(--deep-2)}
  .term-preset.on{border-color:var(--sec);background:var(--tint);color:var(--sec);box-shadow:0 0 0 3px var(--sec-ring)}

  /* preview card */
  .preview-card{border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;background:rgba(255,255,255,.8);box-shadow:var(--shadow-card)}
  .preview-head{padding:20px;display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,var(--tint),rgba(255,255,255,.4));border-bottom:1px solid var(--line)}
  .preview-avatar{width:52px;height:52px;border-radius:14px;background:linear-gradient(145deg,var(--mint),var(--sec));display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;box-shadow:0 6px 16px -6px rgba(16,124,123,.5)}
  .preview-name{font-size:16px;font-weight:800;color:var(--deep-2);letter-spacing:-.3px}
  .preview-code{font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--ink-muted);margin-top:2px}
  .preview-body{padding:16px 20px;display:flex;flex-direction:column;gap:11px}
  .pv-row{display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--ink-soft)}
  .pv-row i{width:16px;text-align:center;color:var(--sec);font-size:11px}
  .pv-row b{color:var(--ink);font-weight:700}
  .pv-empty{color:#A9BCBD;font-style:italic}

  /* layout */
  .create-grid{display:grid;grid-template-columns:1fr 300px;gap:22px;align-items:start}
  .form-col{display:flex;flex-direction:column;gap:20px}
  .rail{display:flex;flex-direction:column;gap:16px;position:sticky;top:128px}
  .rail-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;background:rgba(255,255,255,.8);animation:slideRight .45s ease both}
  .rail-card:nth-child(2){animation-delay:.08s}
  .rail-card:nth-child(3){animation-delay:.16s}
  .rail-head{padding:15px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.5)}
  .rail-head-icon{width:28px;height:28px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow)}
  .rail-title{font-size:12px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:var(--deep-2)}
  .qnav{display:flex;flex-direction:column;gap:2px;padding:10px}
  .qnav-item{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .qnav-item:hover{background:var(--tint);color:var(--deep-2)}
  .qnav-item i{width:18px;text-align:center;font-size:12px;color:var(--sec)}
  .tips-list{padding:14px 18px;display:flex;flex-direction:column;gap:12px}
  .tip{display:flex;gap:10px;font-size:12px;color:var(--ink-soft);line-height:1.55}
  .tip i{color:var(--sec);font-size:11px;margin-top:3px;flex-shrink:0}
  .tip b{color:var(--deep-2);font-weight:700}

  /* completion meter */
  .meter{padding:16px 18px}
  .meter-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:9px}
  .meter-lbl{font-size:11px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted)}
  .meter-pct{font-size:14px;font-weight:800;color:var(--sec);font-family:'JetBrains Mono',monospace}
  .meter-bar{height:7px;border-radius:4px;background:var(--mist-b);overflow:hidden}
  .meter-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,var(--mint),var(--sec));transition:width .4s cubic-bezier(.2,.8,.3,1);width:0%}
  .meter-checks{display:flex;flex-direction:column;gap:7px;margin-top:13px}
  .mc{display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--ink-muted);font-weight:600;transition:.2s}
  .mc .mcb{width:16px;height:16px;border-radius:50%;border:1.5px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;font-size:8px;color:#fff;transition:.2s;flex-shrink:0}
  .mc.done{color:var(--green)}
  .mc.done .mcb{background:var(--green);border-color:var(--green)}
  .mc.done .mcb i{animation:checkPop .3s ease both}

  /* action bar */
  .action-bar{position:sticky;bottom:0;z-index:60;margin:24px -32px -60px;padding:16px 32px;background:linear-gradient(180deg,rgba(244,248,248,.6),rgba(232,240,240,.95));backdrop-filter:blur(10px);border-top:1px solid var(--line);display:flex;align-items:center;gap:12px;box-shadow:0 -6px 20px -10px rgba(10,42,46,.15)}
  .action-bar .left{display:flex;align-items:center;gap:10px}
  .action-bar .right{margin-left:auto;display:flex;align-items:center;gap:10px}
  .ab-status{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--ink-soft)}
  .ab-status .dot{width:8px;height:8px;border-radius:50%;background:#9DB4B4}
  .ab-status.ready .dot{background:var(--green);box-shadow:0 0 0 3px var(--green-tint)}
  .ab-status.err .dot{background:var(--red);box-shadow:0 0 0 3px var(--red-tint)}

  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}

  /* toast */
  .toast-wrap{position:fixed;bottom:90px;right:24px;z-index:400;display:flex;flex-direction:column;gap:10px}
  .toast{display:flex;align-items:flex-start;gap:10px;min-width:300px;max-width:400px;padding:13px 16px;border-radius:12px;background:linear-gradient(135deg,var(--deep-2),var(--deep-3));color:#fff;box-shadow:var(--cta-btn-shadow);animation:toastIn .3s cubic-bezier(.2,.9,.3,1.2) both}
  .toast.ok i{color:var(--mint)}
  .toast.warn i{color:#FBBF24}
  .toast.err i{color:#FCA5A5}
  .toast .t-title{font-size:12.5px;font-weight:800}
  .toast .t-sub{font-size:11px;color:rgba(255,255,255,.65);margin-top:3px;line-height:1.5}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-brand"><div class="topnav-logo">L</div><span class="topnav-app">CamelotBooks</span><span class="topnav-sep">|</span><span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span></div>
  <div class="topnav-center"><a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a><a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a><div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search vendors, bills… <kbd>Ctrl K</kbd></div></div>
  <div class="topnav-right"><button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button><button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button><div class="topnav-user"><div class="topnav-avatar">ES</div><div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div></div></div>
</nav>
<nav class="modulenav">
  <a class="module-item" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item active" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cubes"></i> Inventory <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building-columns"></i> Banking <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-book-open"></i> Accounting <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-building"></i> Assets <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-users"></i> Payroll <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-bar"></i> Reports <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-file-invoice-dollar"></i> Taxation <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-cash-register"></i> POS <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-chart-line"></i> Analytics <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-gear"></i> System <i class="fa-solid fa-chevron-down chevron"></i></a>
</nav>
<aside class="sidebar">
  <a class="sidebar-item" data-tip="Dashboard" href="#"><i class="fa-solid fa-gauge-high"></i></a>
  <a class="sidebar-item active" data-tip="Vendors" href="vendors-index.html"><i class="fa-solid fa-handshake"></i></a>
  <a class="sidebar-item" data-tip="Purchase Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Bills" href="#"><i class="fa-solid fa-receipt"></i></a>
  <a class="sidebar-item" data-tip="Payments" href="#"><i class="fa-solid fa-credit-card"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main">
  <div class="breadcrumb anim-1">
    <a href="vendors-index.html">Vendor Centre</a><i class="fa-solid fa-chevron-right sep"></i>
    <a href="vendors-index.html">Vendors</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current" id="crumb-current">Create Vendor</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title" id="page-title">Create Vendor</h1>
      <div class="page-subtitle" id="page-subtitle">Store contact details, payment terms and opening balances for this vendor.</div>
    </div>
    <div class="page-header-actions">
      <button class="btn btn-ghost" onclick="location.href='vendors-index.html'"><i class="fa-solid fa-xmark"></i> Cancel</button>
      <button class="btn btn-deep" id="top-save"><i class="fa-solid fa-check"></i> <span id="top-save-label">Create Vendor</span></button>
    </div>
  </div>

  <div class="create-grid">
    <!-- FORM COLUMN -->
    <div class="form-col">

      <!-- CONTACT INFORMATION -->
      <section class="card glass anim-2" id="sec-contact">
        <div class="card-head">
          <div class="card-head-icon"><i class="fa-solid fa-address-card"></i></div>
          <div><div class="card-title">Contact Information</div><div class="card-sub">Primary identity and how to reach this vendor</div></div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-field" id="ff-name">
              <label>Name <span class="req">*</span></label>
              <input class="form-control" id="f-name" placeholder="e.g. AHL Group" autocomplete="off">
              <div class="field-error" id="err-name"><i class="fa-solid fa-circle-exclamation"></i> Vendor name is required.</div>
            </div>
            <div class="form-field">
              <label>Display Name</label>
              <input class="form-control" id="f-display" placeholder="e.g. J. Banda Trading" autocomplete="off">
              <div class="field-hint">Shown on documents if different from legal name.</div>
            </div>
            <div class="form-field">
              <label>Email Address</label>
              <input class="form-control" id="f-email" type="email" placeholder="name@company.com" autocomplete="off">
              <div class="field-error" id="err-email"><i class="fa-solid fa-circle-exclamation"></i> Enter a valid email address.</div>
            </div>
            <div class="form-field">
              <label>Phone Number</label>
              <input class="form-control" id="f-phone" placeholder="+265 99 123 4567" autocomplete="off">
            </div>
            <div class="form-field">
              <label>Contact Person</label>
              <input class="form-control" id="f-contact" placeholder="Primary contact name" autocomplete="off">
            </div>
            <div class="form-field">
              <label>Website</label>
              <input class="form-control" id="f-website" placeholder="https://" autocomplete="off">
            </div>
            <div class="form-field full">
              <label>Billing Address</label>
              <textarea class="form-control" id="f-billing" placeholder="Street, City, Region, Country…"></textarea>
            </div>
            <div class="form-field full">
              <label>Remit To Address</label>
              <textarea class="form-control" id="f-remit" placeholder="Where payments should be sent (leave blank if same as billing)…"></textarea>
              <div class="field-hint">Defaults to the billing address when left empty.</div>
            </div>
          </div>
        </div>
      </section>

      <!-- CLASSIFICATION -->
      <section class="card glass anim-3" id="sec-class">
        <div class="card-head">
          <div class="card-head-icon purple"><i class="fa-solid fa-tags"></i></div>
          <div><div class="card-title">Classification</div><div class="card-sub">Category, tax registration and identifiers</div></div>
        </div>
        <div class="card-body">
          <div class="form-grid cols-3">
            <div class="form-field">
              <label>Vendor Code</label>
              <input class="form-control" id="f-code" value="VEN-AUTO" disabled style="font-family:'JetBrains Mono',monospace">
              <div class="field-hint">Auto-generated on save.</div>
            </div>
            <div class="form-field">
              <label>Category</label>
              <select class="form-control" id="f-cat">
                <option value="">— Select category —</option>
                <option>Office Supplies</option>
                <option>IT Equipment</option>
                <option>Services</option>
                <option>Logistics</option>
                <option>Raw Materials</option>
                <option>Utilities</option>
                <option>Professional Fees</option>
              </select>
            </div>
            <div class="form-field">
              <label>Tax ID / TPIN</label>
              <input class="form-control" id="f-tax" placeholder="e.g. 4001234567" autocomplete="off">
            </div>
          </div>
        </div>
      </section>

      <!-- TERMS & ACCOUNTING -->
      <section class="card glass anim-4" id="sec-terms">
        <div class="card-head">
          <div class="card-head-icon amber"><i class="fa-solid fa-dollar-sign"></i></div>
          <div><div class="card-title">Terms &amp; Accounting</div><div class="card-sub">Currency, settlement terms and opening position</div></div>
        </div>
        <div class="card-body">
          <div class="terms-presets" id="term-presets">
            <button class="term-preset on" data-days="0" data-label="Due on receipt">Due on receipt</button>
            <button class="term-preset" data-days="15" data-label="Net 15">Net 15</button>
            <button class="term-preset" data-days="30" data-label="Net 30">Net 30</button>
            <button class="term-preset" data-days="45" data-label="Net 45">Net 45</button>
            <button class="term-preset" data-days="60" data-label="Net 60">Net 60</button>
            <button class="term-preset" data-days="" data-label="Custom">Custom</button>
          </div>
          <div class="form-grid cols-3">
            <div class="form-field">
              <label>Currency <span class="req">*</span></label>
              <select class="form-control" id="f-cur">
                <option value="MWK" selected>K — Malawian Kwacha</option>
                <option value="USD">$ — US Dollar</option>
                <option value="ZAR">R — South African Rand</option>
                <option value="GBP">£ — British Pound</option>
                <option value="EUR">€ — Euro</option>
              </select>
            </div>
            <div class="form-field">
              <label>Payment Terms</label>
              <select class="form-control" id="f-terms">
                <option value="Due on receipt" selected>Due on receipt</option>
                <option value="Net 15">Net 15</option>
                <option value="Net 30">Net 30</option>
                <option value="Net 45">Net 45</option>
                <option value="Net 60">Net 60</option>
                <option value="Custom">Custom</option>
              </select>
            </div>
            <div class="form-field">
              <label>Payment Terms (Days)</label>
              <input class="form-control" id="f-days" type="number" min="0" value="0" placeholder="e.g. 30">
            </div>
            <div class="form-field">
              <label>Opening Balance (K)</label>
              <input class="form-control input-money" id="f-bal" type="number" step="0.01" value="0.00">
              <div class="field-hint">Amount already owed to this vendor.</div>
            </div>
            <div class="form-field">
              <label>Opening Balance Date</label>
              <input class="form-control" id="f-bal-date" type="date">
            </div>
            <div class="form-field">
              <label>Preferred Method</label>
              <select class="form-control" id="f-method">
                <option value="">— Not specified —</option>
                <option>Bank Transfer</option>
                <option>Mobile Money</option>
                <option>Cheque</option>
                <option>Cash</option>
                <option>Card</option>
              </select>
            </div>
          </div>
        </div>
      </section>

      <!-- STATUS -->
      <section class="card glass anim-5" id="sec-status">
        <div class="card-head">
          <div class="card-head-icon blue"><i class="fa-solid fa-toggle-on"></i></div>
          <div><div class="card-title">Status &amp; Availability</div><div class="card-sub">Control whether this vendor can transact</div></div>
        </div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-field full" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid var(--border);border-radius:12px;background:var(--mist-a)">
              <label class="switch"><input type="checkbox" id="f-active" checked><span class="switch-slider"></span></label>
              <div>
                <div class="switch-row-label" id="active-label">Vendor is Active</div>
                <div class="switch-row-sub">Active vendors are immediately available for purchase orders and bills.</div>
              </div>
            </div>
            <div class="form-field full">
              <label>Internal Notes</label>
              <textarea class="form-control" id="f-notes" placeholder="Anything the team should know about onboarding, credit standing, delivery expectations…" style="min-height:80px"></textarea>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- RAIL -->
    <aside class="rail">
      <!-- LIVE PREVIEW -->
      <div class="rail-card">
        <div class="rail-head"><div class="rail-head-icon"><i class="fa-solid fa-eye"></i></div><span class="rail-title">Live Preview</span></div>
        <div class="preview-card" style="border:none;box-shadow:none;border-radius:0">
          <div class="preview-head">
            <div class="preview-avatar" id="pv-avatar">?</div>
            <div>
              <div class="preview-name" id="pv-name">New Vendor</div>
              <div class="preview-code" id="pv-code">VEN-AUTO</div>
            </div>
          </div>
          <div class="preview-body">
            <div class="pv-row"><i class="fa-solid fa-envelope"></i><span id="pv-email" class="pv-empty">No email</span></div>
            <div class="pv-row"><i class="fa-solid fa-phone"></i><span id="pv-phone" class="pv-empty">No phone</span></div>
            <div class="pv-row"><i class="fa-solid fa-clock"></i><b id="pv-terms">Due on receipt</b></div>
            <div class="pv-row"><i class="fa-solid fa-coins"></i><b id="pv-cur">K — Malawian Kwacha</b></div>
            <div class="pv-row"><i class="fa-solid fa-tag"></i><span id="pv-cat" class="pv-empty">Uncategorised</span></div>
          </div>
        </div>
      </div>

      <!-- COMPLETION -->
      <div class="rail-card">
        <div class="rail-head"><div class="rail-head-icon"><i class="fa-solid fa-circle-nodes"></i></div><span class="rail-title">Completion</span></div>
        <div class="meter">
          <div class="meter-top"><span class="meter-lbl">Form progress</span><span class="meter-pct" id="meter-pct">0%</span></div>
          <div class="meter-bar"><div class="meter-fill" id="meter-fill"></div></div>
          <div class="meter-checks">
            <div class="mc" id="mc-name"><span class="mcb"><i class="fa-solid fa-check"></i></span> Name provided</div>
            <div class="mc" id="mc-contact"><span class="mcb"><i class="fa-solid fa-check"></i></span> At least one contact method</div>
            <div class="mc" id="mc-terms"><span class="mcb"><i class="fa-solid fa-check"></i></span> Payment terms set</div>
            <div class="mc" id="mc-cat"><span class="mcb"><i class="fa-solid fa-check"></i></span> Category chosen</div>
          </div>
        </div>
      </div>

      <!-- QUICK NAV -->
      <div class="rail-card">
        <div class="rail-head"><div class="rail-head-icon"><i class="fa-solid fa-bolt"></i></div><span class="rail-title">Quick Nav</span></div>
        <div class="qnav">
          <a class="qnav-item" href="vendors-index.html"><i class="fa-solid fa-handshake"></i> Vendors List</a>
          <a class="qnav-item" href="#" data-jump="sec-contact"><i class="fa-solid fa-address-card"></i> Contact Info</a>
          <a class="qnav-item" href="#" data-jump="sec-class"><i class="fa-solid fa-tags"></i> Classification</a>
          <a class="qnav-item" href="#" data-jump="sec-terms"><i class="fa-solid fa-dollar-sign"></i> Terms &amp; Accounting</a>
          <a class="qnav-item" href="#" data-report="Vendor Balances"><i class="fa-solid fa-chart-simple"></i> Vendor Balances</a>
          <a class="qnav-item" href="#" data-report="Purchases by Vendor"><i class="fa-solid fa-clock-rotate-left"></i> Purchases by Vendor</a>
        </div>
      </div>

      <!-- TIPS -->
      <div class="rail-card">
        <div class="rail-head"><div class="rail-head-icon"><i class="fa-solid fa-lightbulb"></i></div><span class="rail-title">Good to Know</span></div>
        <div class="tips-list">
          <div class="tip"><i class="fa-solid fa-circle-info"></i><span><b>Vendor code</b> is generated automatically and cannot be edited after creation.</span></div>
          <div class="tip"><i class="fa-solid fa-circle-info"></i><span>An <b>opening balance</b> posts as an unpaid bill dated on the opening date.</span></div>
          <div class="tip"><i class="fa-solid fa-circle-info"></i><span>Inactive vendors stay in history but can't receive new POs or bills.</span></div>
        </div>
      </div>
    </aside>
  </div>

  <!-- STICKY ACTION BAR -->
  <div class="action-bar">
    <div class="left">
      <span class="ab-status" id="ab-status"><span class="dot"></span> <span id="ab-text">Fill required fields to enable saving</span></span>
    </div>
    <div class="right">
      <button class="btn btn-ghost" onclick="resetForm()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
      <button class="btn btn-ghost" id="save-draft"><i class="fa-solid fa-floppy-disk"></i> Save as Draft</button>
      <button class="btn btn-deep" id="save-main" disabled><i class="fa-solid fa-check"></i> <span id="save-main-label">Create Vendor</span></button>
    </div>
  </div>
</main>

<div class="toast-wrap" id="toasts"></div>

<script>
/* ── MODE (create vs edit) ── */
const params = new URLSearchParams(location.search);
const editId = params.get('edit');
const EDIT_DATA = {
  '1': {code:'VEN-00001', name:'AHL Group', display:'', email:'ahl@ahl.ww', phone:'0999602605', contact:'AHL Group', website:'', billing:'', remit:'', cat:'Office Supplies', tax:'', cur:'MWK', terms:'Due on receipt', days:0, bal:0, balDate:'', method:'', active:true, notes:''},
  '2': {code:'VEN-00002', name:'Gracian Seyama', display:'', email:'', phone:'', contact:'Yeah', website:'', billing:'', remit:'', cat:'', tax:'', cur:'MWK', terms:'Net 15', days:15, bal:0, balDate:'', method:'', active:false, notes:''}
};
let mode = editId && EDIT_DATA[editId] ? 'edit' : 'create';

/* ── ELEMENTS ── */
const $ = id => document.getElementById(id);
const els = {
  name:$('f-name'), display:$('f-display'), email:$('f-email'), phone:$('f-phone'), contact:$('f-contact'),
  website:$('f-website'), billing:$('f-billing'), remit:$('f-remit'), code:$('f-code'), cat:$('f-cat'),
  tax:$('f-tax'), cur:$('f-cur'), terms:$('f-terms'), days:$('f-days'), bal:$('f-bal'), balDate:$('f-bal-date'),
  method:$('f-method'), active:$('f-active'), notes:$('f-notes')
};
const CUR_SYMBOL = {MWK:'K', USD:'$', ZAR:'R', GBP:'£', EUR:'€'};
const CUR_LABEL = {MWK:'K — Malawian Kwacha', USD:'$ — US Dollar', ZAR:'R — South African Rand', GBP:'£ — British Pound', EUR:'€ — Euro'};

/* ── APPLY EDIT DATA ── */
function applyMode(){
  if(mode==='edit'){
    const d = EDIT_DATA[editId];
    $('page-title').textContent = 'Edit Vendor';
    $('page-subtitle').textContent = 'Update master data for ' + d.code;
    $('crumb-current').textContent = d.name;
    $('top-save-label').textContent = 'Save Changes';
    $('save-main-label').textContent = 'Save Changes';
    els.code.value = d.code;
    els.name.value = d.name; els.display.value = d.display; els.email.value = d.email;
    els.phone.value = d.phone; els.contact.value = d.contact; els.website.value = d.website;
    els.billing.value = d.billing; els.remit.value = d.remit;
    els.cat.value = d.cat; els.tax.value = d.tax; els.cur.value = d.cur;
    els.terms.value = d.terms; els.days.value = d.days;
    els.bal.value = Number(d.bal).toFixed(2); els.balDate.value = d.balDate;
    els.method.value = d.method; els.active.checked = d.active; els.notes.value = d.notes;
    syncTermPreset();
  }
  updatePreview(); updateMeter(); validate(false);
}

/* ── TERM PRESETS ↔ SELECT/DAYS SYNC ── */
function syncTermPreset(){
  const t = els.terms.value, d = parseInt(els.days.value)||0;
  document.querySelectorAll('.term-preset').forEach(p=>{
    const pd = p.dataset.days, pl = p.dataset.label;
    let on=false;
    if(pl==='Custom') on = !['Due on receipt','Net 15','Net 30','Net 45','Net 60'].includes(t);
    else on = (pl===t) || (pd!=='' && parseInt(pd)===d && pl.startsWith('Net'));
    if(pl==='Due on receipt') on = (t==='Due on receipt');
    p.classList.toggle('on', on);
  });
}
document.querySelectorAll('.term-preset').forEach(p=>p.addEventListener('click',()=>{
  const pl=p.dataset.label;
  if(pl==='Custom'){ els.terms.value='Custom'; els.days.focus(); }
  else { els.terms.value=pl; els.days.value=p.dataset.days||0; }
  syncTermPreset(); updatePreview(); updateMeter();
}));
els.terms.addEventListener('change',()=>{
  const map={'Due on receipt':0,'Net 15':15,'Net 30':30,'Net 45':45,'Net 60':60};
  if(map[els.terms.value]!==undefined) els.days.value=map[els.terms.value];
  else els.days.value='';
  syncTermPreset(); updatePreview(); updateMeter();
});
els.days.addEventListener('input',()=>{
  const rev={0:'Due on receipt',15:'Net 15',30:'Net 30',45:'Net 45',60:'Net 60'};
  const v=parseInt(els.days.value);
  els.terms.value = rev[v]!==undefined ? rev[v] : 'Custom';
  syncTermPreset(); updatePreview(); updateMeter();
});

/* ── LIVE PREVIEW ── */
const initials = n => (n||'?').trim().split(/\s+/).map(w=>w[0]).slice(0,2).join('').toUpperCase() || '?';
function updatePreview(){
  const nm = els.name.value.trim() || 'New Vendor';
  $('pv-avatar').textContent = initials(els.name.value);
  $('pv-name').textContent = nm;
  $('pv-code').textContent = els.code.value;
  const em=els.email.value.trim(), ph=els.phone.value.trim();
  $('pv-email').textContent = em||'No email'; $('pv-email').classList.toggle('pv-empty',!em);
  $('pv-phone').textContent = ph||'No phone'; $('pv-phone').classList.toggle('pv-empty',!ph);
  $('pv-terms').textContent = els.terms.value;
  $('pv-cur').textContent = CUR_LABEL[els.cur.value]||els.cur.value;
  const ct=els.cat.value; $('pv-cat').textContent = ct||'Uncategorised'; $('pv-cat').classList.toggle('pv-empty',!ct);
  const sym = CUR_SYMBOL[els.cur.value]||'K';
  const balLabel = [...document.querySelectorAll('#sec-terms label')].find(l=>l.textContent.includes('Opening Balance'));
  if(balLabel) balLabel.innerHTML = `Opening Balance (${sym})`;
}

/* ── COMPLETION METER ── */
function updateMeter(){
  const checks = {
    'mc-name': els.name.value.trim()!=='',
    'mc-contact': els.email.value.trim()!=='' || els.phone.value.trim()!=='',
    'mc-terms': els.terms.value!=='',
    'mc-cat': els.cat.value!==''
  };
  let done=0;
  Object.entries(checks).forEach(([id,ok])=>{ $(id).classList.toggle('done',ok); if(ok)done++; });
  const pct = Math.round(done/Object.keys(checks).length*100);
  $('meter-fill').style.width = pct+'%';
  $('meter-pct').textContent = pct+'%';
}

/* ── VALIDATION ── */
const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
function validate(showErrors){
  let ok = true;
  const nameBad = els.name.value.trim()==='';
  toggleFieldError('ff-name','err-name', nameBad && showErrors);
  if(nameBad) ok=false;
  const emailBad = els.email.value.trim()!=='' && !emailRe.test(els.email.value.trim());
  toggleFieldError(null,'err-email', emailBad && showErrors);
  if(emailBad) ok=false;
  if(els.days.value!=='' && parseInt(els.days.value)<0) ok=false;

  const btn=$('save-main'); btn.disabled=!ok;
  const st=$('ab-status'), txt=$('ab-text');
  st.classList.remove('ready','err');
  if(ok){ st.classList.add('ready'); txt.textContent = mode==='edit'?'Ready to save changes':'Ready to create vendor'; }
  else if(showErrors){ st.classList.add('err'); txt.textContent='Please fix the highlighted fields'; }
  else { txt.textContent='Fill required fields to enable saving'; }
  return ok;
}
function toggleFieldError(fieldWrapId, errId, show){
  if(errId){ const e=$(errId); if(e) e.classList.toggle('show',!!show); }
  if(fieldWrapId){ const w=$(fieldWrapId); if(w) w.classList.toggle('invalid',!!show); }
}

Object.values(els).forEach(el=>{ el.addEventListener('input',()=>{updatePreview();updateMeter();validate(false);}); el.addEventListener('change',()=>{updatePreview();updateMeter();validate(false);}); });
els.name.addEventListener('input',()=>toggleFieldError('ff-name','err-name',false));
els.email.addEventListener('input',()=>toggleFieldError(null,'err-email',false));

/* ── SAVE ── */
$('save-main').addEventListener('click',()=>doSave(false));
$('top-save').addEventListener('click',()=>doSave(false));
$('save-draft').addEventListener('click',()=>doSave(true));
function doSave(isDraft){
  if(!validate(true)){ toast('Cannot save','Fix the highlighted fields first.','err'); scrollToFirstError(); return; }
  const payload = collect();
  if(isDraft) payload._draft=true;
  console.log((mode==='edit'?'UPDATE ':'CREATE ')+JSON.stringify(payload,null,2));
  toast(mode==='edit'?'Vendor updated':'Vendor created', (isDraft?'Saved as draft — ': '')+payload.name+(isDraft?' stored safely.':' added to the directory.'),'ok');
  setTimeout(()=>location.href='vendors-index.html', 900);
}
function collect(){
  return {
    code: els.code.value, name: els.name.value.trim(), displayName: els.display.value.trim(),
    email: els.email.value.trim(), phone: els.phone.value.trim(), contactPerson: els.contact.value.trim(),
    website: els.website.value.trim(), billingAddress: els.billing.value.trim(), remitToAddress: els.remit.value.trim(),
    category: els.cat.value, taxId: els.tax.value.trim(), currency: els.cur.value,
    paymentTerms: els.terms.value, paymentTermsDays: parseInt(els.days.value)||0,
    openingBalance: parseFloat(els.bal.value)||0, openingBalanceDate: els.balDate.value,
    preferredMethod: els.method.value, isActive: els.active.checked, notes: els.notes.value.trim()
  };
}
function scrollToFirstError(){
  const bad=document.querySelector('.form-field.invalid');
  if(bad) bad.scrollIntoView({behavior:'smooth',block:'center'});
}

/* ── RESET ─ */
function resetForm(){
  ['f-name','f-display','f-email','f-phone','f-contact','f-website','f-billing','f-remit','f-tax','f-notes','f-bal-date'].forEach(id=>$(id).value='');
  $('f-code').value = mode==='edit'?EDIT_DATA[editId].code:'VEN-AUTO';
  $('f-cat').value=''; $('f-cur').value='MWK'; $('f-terms').value='Due on receipt'; $('f-days').value='0';
  $('f-bal').value='0.00'; $('f-method').value=''; $('f-active').checked=true;
  toggleFieldError('ff-name','err-name',false); toggleFieldError(null,'err-email',false);
  syncTermPreset(); updatePreview(); updateMeter(); validate(false);
  toast('Form reset','All fields cleared back to defaults.','warn');
}

/* ── ACTIVE TOGGLE LABEL ── */
els.active.addEventListener('change',()=>{
  $('active-label').textContent = els.active.checked?'Vendor is Active':'Vendor is Inactive';
});

/* ── QUICK NAV jump ── */
document.querySelectorAll('[data-jump]').forEach(a=>a.addEventListener('click',e=>{
  e.preventDefault();
  const sec=$(a.dataset.jump); if(sec) sec.scrollIntoView({behavior:'smooth',block:'start'});
}));
document.querySelectorAll('[data-report]').forEach(a=>a.addEventListener('click',e=>{
  e.preventDefault(); toast('Report opened', a.dataset.report+' — generating preview…','ok');
}));

/* ── SIDEBAR / MODULE ── */
document.querySelectorAll('.sidebar-item').forEach(item=>item.addEventListener('click',e=>{if(item.getAttribute('href')==='#'){e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}}));
document.querySelectorAll('.module-item').forEach(item=>item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.module-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');}));

/* ── TOAST ── */
function toast(title,sub,type){
  const t=document.createElement('div'); t.className='toast '+(type||'ok');
  const icon = type==='err'?'fa-circle-exclamation':type==='warn'?'fa-triangle-exclamation':'fa-circle-check';
  t.innerHTML=`<i class="fa-solid ${icon}"></i><div><div class="t-title">${title}</div><div class="t-sub">${sub}</div></div>`;
  $('toasts').appendChild(t);
  setTimeout(()=>{t.style.transition='opacity .4s, transform .4s';t.style.opacity='0';t.style.transform='translateY(8px)';setTimeout(()=>t.remove(),400);},3200);
}

/* ── INIT ── */
applyMode();
</script>
</body>
</html>
```

---

## FINAL ENFORCEMENT STATEMENT FOR OPENCODE

This is not a suggestion. This is a strict implementation requirement.

Implement the vendor module so that opening the index, detail, or create/edit page reproduces **every element, interaction, validation, animation, and workflow** present in the three mockups above.

If any KPI tile, ageing bucket, clear mechanism, bulk action, sort indicator, favourite star, gauge state, tab, empty state, side-rail link, modal, split tile, term preset, preview field, meter check, or sticky-bar state is missing or altered — **the implementation is incomplete**.

If any currency renders as USD/$ instead of MWK "K" — **the implementation is incomplete**.

If the faint centred bottom hairline is moved back to the top or made fully opaque — **the implementation is incomplete**.

Do not stop until every page, partial, component, route, controller, model, migration, seeder, policy, service, form request, validation rule, frontend behavior, and test required by this module is fully delivered and integrated into the existing system.