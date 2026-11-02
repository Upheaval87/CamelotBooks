# FULL IMPLEMENTATION PROMPT — COMPREHENSIVE CUSTOMER MODULE

## CRITICAL INSTRUCTION TO OPENCODE: DO NOT SKIP ANY ELEMENT, DO NOT SIMPLIFY, DO NOT REPLACE, DO NOT SUMMARIZE, DO NOT LEAVE OUT ANY MODAL, TAB, FIELD, BUTTON, ANIMATION, INTERACTION, VALIDATION, OR BACKEND FUNCTIONALITY IMPLIED BY THESE MOCKUPS.

You are acting as a senior Laravel/PHP enterprise architect, backend engineer, database designer, and premium UI/UX frontend developer.

Build a **comprehensive enterprise Customer Module** for an existing PHP/Laravel ERP/accounting system, exactly matching the authoritative HTML mockups embedded below.

These mockups are the **source of truth** for:

- page layout
- visual design
- spacing
- typography
- color tokens
- gradients
- glass surfaces
- shadows
- tabs
- cards
- tables
- pills/statuses
- modals
- form fields
- payment method behavior
- split payment behavior
- currency display (MWK with symbol "K")
- all visible and implied functionality

You must redesign/implement **all necessary pages, modals, components, elements, workflows, validations, backend endpoints, database structures, policies, services, controllers, Blade views, JavaScript/Alpine behavior, tests, and seeders** required to make this module production-ready within the existing system.

---

## ABSOLUTE NON-NEGOTIABLE REQUIREMENTS

1. **Do not skip any element from the mockups.**
2. **Do not remove any modal.**
3. **Do not remove any tab.**
4. **Do not remove any field.**
5. **Do not remove any button.**
6. **Do not remove any status pill.**
7. **Do not remove any animation.**
8. **Do not replace the custom design system with a generic Bootstrap/Tailwind/Material UI.**
9. **Do not simplify the Record Payment modal.**
10. **Do not simplify the Split Payment checkbox tiles.**
11. **Do not omit the method-specific transaction fields.**
12. **Do not omit the credit utilisation gauge.**
13. **Do not omit the receivables ageing strip.**
14. **Do not omit the statement ledger tab.**
15. **Do not invent a different visual direction.**
16. **If the implementation is too large for one response, split it into multiple file writes, but still complete every required element.**
17. **No placeholders like "add remaining fields here", "implement modal similarly", or "TODO" for any part of the mockup.**
18. **Every visible mockup element must map to a real implementation artifact: Blade component, partial, Alpine state, controller method, route, request validation, service logic, database column, policy ability, test case, or seeder record as appropriate.**
19. **Currency must be MWK (Malawian Kwacha) with symbol "K" throughout — never USD or $.**

---

## TARGET STACK

Implement using modern enterprise Laravel practices adapted to the existing project:

- Laravel 11+ or latest stable (match existing version)
- PHP 8.3+ (match existing version)
- Blade templating (match existing patterns)
- Alpine.js for lightweight UI interactivity unless Livewire is already the project standard
- Tailwind is allowed only if it reproduces the exact mockup visually; otherwise preserve the mockup's custom CSS token system
- MySQL/PostgreSQL-compatible schema (match existing database)
- Form Request validation
- Policies/gates for authorization
- Service classes for business workflows
- Feature tests
- Database seeders
- Multi-tenant/company-safe design (if existing system uses tenancy)
- Audit-friendly timestamps and soft deletes where appropriate

**CRITICAL:** Inspect the existing codebase first. Match its:
- Directory structure
- Naming conventions
- Base controller patterns
- Existing model traits (e.g., TenantScoped, SoftDeletes, HasFactory)
- Existing middleware stack
- Existing asset pipeline (Vite, Mix, etc.)
- Existing component library (if any)

Do not introduce conflicting patterns. Integrate seamlessly.

---

## MODULE SCOPE

Build a full **Customer Management Module** including at minimum:

### Customer Master Data

- customer code (auto-generated, unique per tenant)
- display name
- legal name
- category (Retail, Wholesale, Corporate, Government, etc.)
- currency (default MWK, allow override per customer if multi-currency supported)
- tax ID / VAT number
- email
- phone
- website
- billing address
- shipping address
- payment terms in days
- preferred payment method
- opening balance
- opening balance date
- credit limit (0 = unlimited)
- active/inactive status
- created by / updated by
- created at / updated at
- soft delete support
- tenant/company scoping

### Customers Index Page

Exactly as mocked in `customers-index.html`:

- top navigation row 1 (deep teal gradient)
- module rail row 2 (Sales active)
- compact icon sidebar (Customers active)
- breadcrumb
- page header with Export + Create Customer buttons
- Portfolio card with 3 KPI tiles (Customers, Active, Outstanding) — Outstanding tile has animated sheen highlight
- Receivables Ageing card (NEW FEATURE) with 5 buckets (Current, 1–30, 31–60, 61–90, 90+) — 61–90 bucket highlighted red when overdue
- Filters toolbar (search, status dropdown, terms dropdown, filter button, count chip)
- Customers table with:
  - Avatar cell (initials + status dot)
  - Name + "Since [month year]" subtext
  - Email
  - Phone (monospace)
  - Terms pill
  - Balance (right-aligned, monospace, red if > 0)
  - Status pill (Active/Inactive)
  - Actions (View, Edit, Archive icons)
  - **Entire row clickable to navigate to detail page**
  - Row hover/active/focus-visible styling
  - Keyboard support (Enter/Space navigates)
- Pagination footer
- Right rail: Views card (All Customers, Active, Inactive, With Balance) + Reports card (Customer Balances, Sales by Customer, Ageing Report, Statement Batch)

### Customer Detail Page

Exactly as mocked in `customer-detail.html`:

- top navigation row 1
- module rail row 2 (Sales active)
- compact icon sidebar (Customers active)
- breadcrumb (Customers › John Banda)
- page header with Print, Export PDF, More, Edit Customer buttons
- Customer hero card with:
  - Centered gradient hairline
  - Large avatar (initials)
  - Name + Active pill + customer code chip
  - Meta row (email, phone, terms, currency)
  - **Credit Utilisation Gauge (NEW FEATURE)** — SVG donut showing used vs limit; renders ∞/"No Limit" when limit = 0; shows Limit/Used/Available readouts
  - Archive button
  - Quick action chips (New Invoice, Record Payment, View Statement, Print, Email Statement, Add to Favourites)
- Financial KPI strip (4 cards):
  - Open Balance (highlighted with sheen animation)
  - Total Invoiced
  - Opening Balance
  - Payment Terms
- Tabs card with centered gradient hairline:
  - Information tab (contact grid, addresses, recent activity timeline)
  - Transactions tab (toolbar with type filter + date range, table with type chips, amounts, balances, status pills, clickable rows)
  - Statement tab (NEW FEATURE — full debit/credit/balance ledger with totals row, export/email actions, closing balance summary)
  - Payments tab (empty state with Record Payment CTA)
  - Notes tab (timeline with Add Note CTA)
- Side panel:
  - Summary card (Opening Balance, Total Invoiced, Total Paid, Credit Limit, gradient Open Balance block)
  - Quick Nav card (icon-aligned list wired to actions/tabs)
  - Contact Details card (email, phone, website with edit link)

---

## MODALS TO IMPLEMENT EXACTLY

You must implement all modals shown in the mockups:

### From Customers Index (`customers-index.html`):

1. **Create Customer Modal** (wide, 840px)
2. **Edit Customer Modal** (wide, 840px)
3. **Archive Confirm Modal** (narrow, 440px, red gradient header)

### From Customer Detail (`customer-detail.html`):

4. **Record Payment Modal** (mid, 780px) — with full split payment logic
5. **New Invoice Modal** (wide, 840px)
6. **Add Note Modal** (standard, 560px)
7. **Send Message Modal** (standard, 560px)
8. **Edit Customer Modal** (reused from index)
9. **Archive Confirm Modal** (reused from index)

Each modal must preserve:

- header gradient (deep teal with mint radial glow)
- mint hairline at bottom of header
- icon badge (teal gradient with shadow)
- title/subtitle
- close button rotation hover
- body scroll behavior
- footer gradient (mist-a to mist-b)
- button styles (ghost, primary, deep, danger-ghost)
- animation on open/close (scale + fade)
- backdrop blur
- Escape key close
- click outside close
- body scroll lock

---

## RECORD PAYMENT MODAL — CRITICAL BEHAVIOR

The Record Payment modal is widened using `.modal.mid` with `max-width: 780px`.

It must include:

- Payment Date
- Payment Method select:
  - Bank Transfer
  - Mobile Money
  - Card
  - Cash
  - Split Payment
- Paying Bank Account
- Payment Amount (in K, MWK)

### When Bank Transfer / Mobile Money / Card is selected

Show a section titled:

**Transaction Details**

with a method flag badge showing the selected method.

Fields:

- Reference / Transaction No. — required
- Provider / Institution — required, options depend on method:
  - Bank Transfer: Zanaco, Stanbic Bank, FNB Zambia, Absa Bank, Investrust Bank
  - Mobile Money: MTN MoMo, Airtel Money, Zamtel Kwacha
  - Card: Visa, Mastercard, American Express, UnionPay
- Account Name
- Account Holder Name

There must be enough spacing above this dynamic section. The mockup uses:

```css
#pay-elec,
#pay-cash,
#pay-split{
  margin-top:34px;
}
```

Preserve this spacing or improve it without reducing visual separation.

### When Cash is selected

Show:

**Cash Handling**

Fields:

- Cash Tendered
- Change Given

Also preserve spacing above the section.

### When Split Payment is selected

Show:

**Split Allocation**

With four premium checkbox tiles:

- Cash
- Bank Transfer
- Card
- Mobile Money

Each tile initially collapsed.

When a checkbox is selected:

- tile enters selected state
- checkbox animates to teal gradient checked state
- icon medallion becomes active
- chevron rotates
- expanded capture panel appears below tile with animation

Expanded fields:

#### Cash split

- Amount (K)
- Cash Tendered
- Change Given

#### Bank Transfer split

- Amount (K)
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

#### Card split

- Amount (K)
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

#### Mobile Money split

- Amount (K)
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

Split totals must show:

- Payment Amount (K)
- Allocated Across Selected Methods (K)
- Unallocated (K)

Validation banners:

- info when no method selected
- success when fully allocated
- warning when unallocated remains
- warning when over-allocated

Record Payment button must remain disabled until valid:

- payment method selected
- payment amount > 0
- if single electronic method: reference required
- if split:
  - at least one split selected
  - allocated amount equals payment amount within ±0.01
  - every selected electronic split has reference

---

## DESIGN SYSTEM REQUIREMENTS

Preserve the exact token system from the mockups:

```css
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
--sec-ring:rgba(16,124,123,.16);
--teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
--cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
```

Required visual signatures:

- deep teal gradient topnav with mint radial glow
- module rail with active deep gradient pill
- icon sidebar with active tint and left gradient indicator
- glass cards with backdrop blur
- centered gradient hairlines on:
  - customer hero
  - tabs card
  - all major cards
- highlighted KPI cards with animated sheen
- credit utilisation gauge with SVG donut animation
- receivables ageing strip with proportional fills
- striped mist tables with tint row hover
- status pills (green active/paid, amber open/sent, red overdue/inactive, gray draft)
- type chips (blue invoice, green payment, amber credit note, gray journal)
- modal headers with deep gradient and mint bottom hairline
- premium split checkbox tiles with animated checked state
- sufficient spacing above dynamic payment sections

---

## BACKEND IMPLEMENTATION REQUIREMENTS

Create a production-ready Laravel module structure integrated into the existing system.

Suggested namespace (adapt to existing conventions):

```php
App\Modules\Customers
```

or match existing module pattern.

### Routes

Define customer module routes such as:

```php
Route::middleware(['auth', 'tenant'])->prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    Route::post('/{customer}/archive', [CustomerController::class, 'archive'])->name('archive');
    Route::post('/{customer}/activate', [CustomerController::class, 'activate'])->name('activate');

    Route::post('/{customer}/invoices', [CustomerInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('payments.store');
    Route::post('/{customer}/messages', [CustomerMessageController::class, 'store'])->name('messages.store');
    Route::post('/{customer}/notes', [CustomerNoteController::class, 'store'])->name('notes.store');
    Route::get('/{customer}/statement', [CustomerStatementController::class, 'show'])->name('statement.show');
    Route::get('/{customer}/statement/export', [CustomerStatementController::class, 'export'])->name('statement.export');
    Route::post('/{customer}/statement/email', [CustomerStatementController::class, 'email'])->name('statement.email');
});
```

Adjust to project conventions but cover all actions implied by the UI.

### Controllers

Create controllers/services for:

- CustomerController
- CustomerInvoiceController
- CustomerPaymentController
- CustomerMessageController
- CustomerNoteController
- CustomerStatementController

Keep controllers thin; move business logic into services.

### Services

Create services such as:

- CustomerService
- CustomerActivationService
- InvoiceService
- PaymentRecordingService
- SplitPaymentValidator
- CustomerCommunicationService
- CustomerNoteService
- CustomerStatementService
- CustomerLedgerService
- ReceivablesAgeingService

### Form Requests

Create validated requests for:

- StoreCustomerRequest
- UpdateCustomerRequest
- StoreInvoiceRequest
- StorePaymentRequest
- SendMessageRequest
- StoreNoteRequest

Payment request validation must enforce:

- method required in bank/mobile/card/cash/split
- amount numeric > 0
- if method is bank/mobile/card:
  - reference required
  - provider required
- if method is split:
  - split_lines required array
  - each split line method required
  - each split line amount required numeric > 0
  - if split line method is bank/mobile/card:
    - reference required
    - provider required
  - sum of split line amounts must equal payment amount within 0.01

### Policies

Create CustomerPolicy with abilities:

- viewAny
- view
- create
- update
- delete
- restore
- archive
- activate
- recordPayment
- createInvoice
- sendMessage
- addNote
- viewStatement
- exportStatement
- emailStatement

Enforce tenant/company scoping.

### Database Schema

Create migrations for at least:

#### customers

- id
- tenant_id / company_id
- customer_code unique per tenant
- display_name
- legal_name
- category_id nullable
- currency_id nullable (default MWK)
- tax_id nullable
- email nullable
- phone nullable
- website nullable
- billing_address text nullable
- shipping_address text nullable
- payment_terms_days unsignedInteger default 30
- preferred_payment_method string nullable
- opening_balance decimal(18,2) default 0
- opening_balance_date date nullable
- credit_limit decimal(18,2) default 0 (0 = unlimited)
- is_active boolean default true
- created_by nullable
- updated_by nullable
- timestamps
- softDeletes

#### customer_categories

- id
- tenant_id
- name
- slug/code
- timestamps

#### currencies (if not existing)

- id
- code (MWK, USD, etc.)
- name
- symbol (K, $, etc.)
- decimal_precision
- is_default boolean
- timestamps

#### invoices

- id
- tenant_id
- customer_id
- invoice_number
- invoice_date
- due_date
- category_id nullable
- linked_so_number nullable
- subtotal decimal
- tax_amount decimal
- total_amount decimal
- amount_paid decimal default 0
- status enum draft/sent/paid/overdue/void
- created_by
- timestamps
- softDeletes

#### invoice_items

- id
- invoice_id
- description
- quantity decimal
- unit_price decimal
- line_total decimal
- tax_amount decimal nullable
- timestamps

#### payments

- id
- tenant_id
- customer_id
- payment_number
- payment_date
- payment_method string
- reference_number nullable
- provider_institution nullable
- account_name nullable
- account_holder_name nullable
- paying_bank_account_id nullable
- amount decimal
- status enum pending/cleared/void
- note text nullable
- created_by
- timestamps
- softDeletes

#### payment_split_lines

- id
- payment_id
- method string
- amount decimal
- reference_number nullable
- provider_institution nullable
- account_name nullable
- account_holder_name nullable
- cash_tendered decimal nullable
- change_given decimal nullable
- timestamps

#### payment_allocations

- id
- payment_id
- invoice_id
- allocated_amount decimal
- timestamps

#### customer_messages

- id
- tenant_id
- customer_id
- direction enum inbound/outbound
- channel enum email/sms
- subject
- body text
- template_key nullable
- attachment_path nullable
- sent_by nullable
- received_at nullable
- timestamps
- softDeletes

#### customer_notes

- id
- tenant_id
- customer_id
- type enum internal/reminder/follow_up
- title
- body text
- reminder_date nullable
- pinned boolean default false
- created_by
- timestamps
- softDeletes

#### customer_ledger_entries (or integrate with accounting ledger)

If the system already has a general ledger, create polymorphic source references:

- source_type = invoice / payment / credit_note / journal
- source_id
- customer_id
- date
- entry_number
- type
- reference
- debit
- credit
- balance_after

---

## FRONTEND IMPLEMENTATION REQUIREMENTS

Create Blade components/partials for:

```text
resources/views/customers/index.blade.php
resources/views/customers/show.blade.php
resources/views/customers/partials/topnav.blade.php (or reuse existing)
resources/views/customers/partials/module-rail.blade.php (or reuse existing)
resources/views/customers/partials/sidebar.blade.php (or reuse existing)
resources/views/customers/partials/breadcrumb.blade.php
resources/views/customers/partials/page-header.blade.php
resources/views/customers/partials/portfolio-card.blade.php
resources/views/customers/partials/ageing-card.blade.php
resources/views/customers/partials/filters-toolbar.blade.php
resources/views/customers/partials/customers-table.blade.php
resources/views/customers/partials/views-rail.blade.php
resources/views/customers/partials/reports-rail.blade.php
resources/views/customers/partials/customer-hero.blade.php
resources/views/customers/partials/credit-gauge.blade.php
resources/views/customers/partials/financial-strip.blade.php
resources/views/customers/partials/tabs.blade.php
resources/views/customers/tabs/information.blade.php
resources/views/customers/tabs/transactions.blade.php
resources/views/customers/tabs/statement.blade.php
resources/views/customers/tabs/payments.blade.php
resources/views/customers/tabs/notes.blade.php
resources/views/customers/partials/summary-card.blade.php
resources/views/customers/partials/quick-nav-card.blade.php
resources/views/customers/partials/contact-card.blade.php
resources/views/customers/modals/create-customer.blade.php
resources/views/customers/modals/edit-customer.blade.php
resources/views/customers/modals/archive-confirm.blade.php
resources/views/customers/modals/record-payment.blade.php
resources/views/customers/modals/new-invoice.blade.php
resources/views/customers/modals/add-note.blade.php
resources/views/customers/modals/send-message.blade.php
```

Create CSS asset:

```text
resources/css/customer-module.css
```

or compile into project stylesheet. The CSS must include the full token system and all component styles from the mockups.

Create JS/Alpine components for:

- tabs
- sidebar active state
- modal manager
- customer row click/navigation
- record payment method switching
- split payment tiles
- split totals/validation
- line item repeaters
- credit gauge animation
- ageing bar fills

If using Alpine, structure state cleanly:

```js
customerIndexPage()
customerDetailPage()
recordPaymentModal()
splitPaymentState()
lineItemsRepeater()
creditGauge()
```

If using Livewire, preserve the same visual behavior and validation feedback.

---

## CURRENCY REQUIREMENT

**All monetary values must display in MWK (Malawian Kwacha) with symbol "K".**

- Never use USD or $ in the UI
- Format numbers with 2 decimal places
- Use monospace font for amounts
- Right-align amount columns
- Red color for outstanding balances > 0
- Example: K 8,178.30

---

## ACCEPTANCE CHECKLIST

Before considering the task complete, verify every item:

### Customers Index Layout

- [ ] Topnav row 1 exists with brand, app name, org, center pills/search, notifications, help, user chip.
- [ ] Module rail row 2 exists with Sales active.
- [ ] Icon sidebar exists with Customers active.
- [ ] Breadcrumb exists.
- [ ] Page header exists with title, subtitle, Export button, Create Customer button.
- [ ] Portfolio card exists with 3 KPI tiles.
- [ ] Outstanding tile has animated sheen highlight.
- [ ] Receivables Ageing card exists with 5 buckets.
- [ ] 61–90 bucket highlights red when overdue.
- [ ] Filters toolbar exists with search, status dropdown, terms dropdown, filter button, count chip.
- [ ] Customers table exists with all columns.
- [ ] Avatar cell shows initials + status dot.
- [ ] Name shows "Since [month year]" subtext.
- [ ] Phone is monospace.
- [ ] Terms shows pill.
- [ ] Balance is right-aligned, monospace, red if > 0.
- [ ] Status shows pill.
- [ ] Actions show View, Edit, Archive icons.
- [ ] Entire row is clickable to detail page.
- [ ] Row has pointer cursor.
- [ ] Row hover/active/focus-visible styles exist.
- [ ] Keyboard Enter/Space navigates.
- [ ] Pagination footer exists.
- [ ] Right rail Views card exists.
- [ ] Right rail Reports card exists.

### Customer Detail Layout

- [ ] Topnav row 1 exists.
- [ ] Module rail row 2 exists with Sales active.
- [ ] Icon sidebar exists with Customers active.
- [ ] Breadcrumb exists (Customers › John Banda).
- [ ] Page header exists with Print, Export PDF, More, Edit Customer buttons.
- [ ] Customer hero card exists.
- [ ] Centered gradient hairline exists on hero.
- [ ] Large avatar shows initials.
- [ ] Name + Active pill + customer code chip exist.
- [ ] Meta row shows email, phone, terms, currency.
- [ ] Credit Utilisation Gauge exists.
- [ ] Gauge renders ∞/"No Limit" when limit = 0.
- [ ] Gauge shows Limit/Used/Available readouts.
- [ ] Archive button exists.
- [ ] Quick action chips exist (New Invoice, Record Payment, View Statement, Print, Email Statement, Add to Favourites).
- [ ] Financial KPI strip exists with 4 cards.
- [ ] Open Balance card has animated sheen highlight.
- [ ] Tabs card exists with centered gradient hairline.
- [ ] Information tab renders contact grid, addresses, recent activity timeline.
- [ ] Transactions tab renders toolbar, table with type chips, amounts, balances, status pills, clickable rows.
- [ ] Statement tab renders full debit/credit/balance ledger with totals row, export/email actions, closing balance summary.
- [ ] Payments tab renders empty state with Record Payment CTA.
- [ ] Notes tab renders timeline with Add Note CTA.
- [ ] Side panel Summary card exists.
- [ ] Side panel Quick Nav card exists.
- [ ] Side panel Contact Details card exists.

### Modals

- [ ] Create Customer modal fully implemented (wide, 840px).
- [ ] Edit Customer modal fully implemented (wide, 840px).
- [ ] Archive Confirm modal fully implemented (narrow, 440px, red gradient header).
- [ ] Record Payment modal fully implemented (mid, 780px).
- [ ] New Invoice modal fully implemented (wide, 840px).
- [ ] Add Note modal fully implemented (standard, 560px).
- [ ] Send Message modal fully implemented (standard, 560px).
- [ ] All modals support Escape close.
- [ ] All modals support backdrop close.
- [ ] All modals lock body scroll.
- [ ] All modal animations preserved.

### Record Payment Modal Specifics

- [ ] Modal width is `.modal.mid` / 780px.
- [ ] Payment Date exists.
- [ ] Payment Method select exists.
- [ ] Paying Bank Account exists.
- [ ] Payment Amount exists (in K, MWK).
- [ ] Selecting Bank Transfer shows Transaction Details.
- [ ] Selecting Mobile Money shows Transaction Details.
- [ ] Selecting Card shows Transaction Details.
- [ ] Transaction Details has enough top spacing (34px margin).
- [ ] Reference / Transaction No. appears and is required.
- [ ] Provider / Institution appears and changes options by method.
- [ ] Account Name appears.
- [ ] Account Holder Name appears.
- [ ] Selecting Cash shows Cash Handling.
- [ ] Cash Handling has tendered/change fields.
- [ ] Selecting Split Payment shows four checkbox tiles.
- [ ] Tiles are collapsed initially.
- [ ] Checking a tile expands its fields.
- [ ] Unchecking collapses but preserves values.
- [ ] Cash split shows tendered/change.
- [ ] Electronic splits show reference/provider/account name/account holder.
- [ ] Totals show Payment Amount, Allocated, Unallocated (all in K).
- [ ] Banners show info/success/warning states.
- [ ] Record Payment button disabled until valid.
- [ ] Split validation enforces ±0.01.
- [ ] Electronic split validation enforces reference.

### Backend

- [ ] Customer model and relationships exist.
- [ ] Migrations exist for all customer-related tables.
- [ ] Seeders create the mocked customers, invoices, payments, messages, notes, ledger entries.
- [ ] Routes exist for all actions.
- [ ] Controller actions exist.
- [ ] Form Requests validate all fields.
- [ ] Policies authorize all actions.
- [ ] Services handle business workflows.
- [ ] Payment recording persists split lines and allocations.
- [ ] Statement data is loaded server-side or via endpoint.
- [ ] Tests cover customer index, detail, payment validation, split validation, invoice creation, statement export.

### Currency

- [ ] All amounts display in K (MWK).
- [ ] No USD or $ symbols appear anywhere.
- [ ] Amounts are right-aligned.
- [ ] Amounts use monospace font.
- [ ] Outstanding balances > 0 are red.

---

## AUTHORITATIVE HTML MOCKUPS — USE THESE EXACTLY

The following HTML mockups are the definitive reference. Implement every visible element, style, interaction, modal, tab, field, button, validation state, animation, and workflow shown here.

### Mockup 1: Customers Index (`customers-index.html`)

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customers | CamelotBooks ERP</title>
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
  @keyframes expandIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-4{animation:fadeUp .4s ease both;animation-delay:.26s}

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
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover{filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover{filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}
  .btn-icon{width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-muted);cursor:pointer;transition:.2s;font-size:14px}
  .btn-icon:hover{border-color:var(--mint);color:var(--sec);background:#fff;transform:translateY(-1px)}

  /* cards / hairlines */
  .card{border:1px solid var(--border);border-radius:var(--r-xl);box-shadow:var(--shadow-card);overflow:hidden;position:relative;transition:.2s}
  .card:hover{box-shadow:var(--shadow-card-hov)}
  .card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:0 0 6px 6px;background:linear-gradient(90deg,transparent 0%,var(--mint) 22%,var(--sec) 50%,var(--mint) 78%,transparent 100%);box-shadow:0 2px 10px rgba(16,124,123,.35);pointer-events:none;z-index:1}
  .card-head{display:flex;align-items:center;gap:10px;padding:16px 22px;border-bottom:1px solid var(--line);background:rgba(255,255,255,.5)}
  .card-head-icon{width:30px;height:30px;border-radius:8px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:var(--teal-btn-shadow);flex-shrink:0}
  .card-title{font-size:13px;font-weight:800;color:var(--deep-2);letter-spacing:.2px}
  .card-sub{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .card-head-right{margin-left:auto;display:flex;align-items:center;gap:8px}
  .card-body{padding:22px}

  /* pills */
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-active{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-active .pdot{background:var(--green)}
  .pill-inactive{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-inactive .pdot{background:#9DB4B4}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .chip-count{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700;background:var(--tint);border:1px solid rgba(16,124,123,.25);color:var(--sec)}

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
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .data-table tbody tr.clickable-row:focus-visible{outline:2px solid var(--sec);outline-offset:-2px}
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
  .row-link{color:var(--deep-2);font-weight:700;text-decoration:none}
  .row-link:hover{color:var(--sec);text-decoration:underline}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}

  /* portfolio strip */
  .portfolio-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:18px}
  .pf-card{border:1px solid var(--border);border-radius:var(--r-md);padding:16px 18px;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);position:relative;overflow:hidden;transition:.22s}
  .pf-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-card-hov)}
  .pf-card.hl{background:linear-gradient(135deg,var(--sec) 0%,var(--sec-2) 55%,var(--deep-1) 135%);border-color:rgba(16,124,123,.6)}
  .pf-card.hl::before{content:'';position:absolute;top:0;bottom:0;left:0;width:38%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.20),transparent);animation:sheen 4.2s ease-in-out infinite}
  .pf-card.hl .pf-label,.pf-card.hl .pf-sub{color:rgba(255,255,255,.72)}
  .pf-card.hl .pf-value{color:#fff}
  .pf-card.hl .pf-icon{background:rgba(255,255,255,.16);color:#fff}
  .pf-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  .pf-label{font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--ink-muted)}
  .pf-icon{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;background:var(--mist-b);color:var(--ink-soft)}
  .pf-value{font-size:22px;font-weight:800;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .pf-value .cur{font-size:13px;font-weight:700;opacity:.55;margin-right:2px}
  .pf-sub{font-size:11px;color:var(--ink-soft);font-weight:500;margin-top:2px}

  /* ageing strip */
  .ageing{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden;background:rgba(255,255,255,.7);box-shadow:var(--shadow-card);margin-bottom:18px}
  .ag-cell{padding:13px 16px;border-right:1px solid var(--line);position:relative}
  .ag-cell:last-child{border-right:none}
  .ag-cell.hot{background:linear-gradient(180deg,var(--red-tint),rgba(255,255,255,.4))}
  .ag-label{font-size:9.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:5px;display:flex;align-items:center;gap:6px}
  .ag-label .agdot{width:7px;height:7px;border-radius:50%}
  .ag-val{font-family:'JetBrains Mono',monospace;font-size:16px;font-weight:800;color:var(--deep-2)}
  .ag-cell.hot .ag-val{color:var(--red-2)}
  .ag-bar{height:4px;border-radius:2px;background:var(--mist-b);margin-top:8px;overflow:hidden}
  .ag-fill{height:100%;border-radius:2px}

  /* side panel */
  .idx-grid{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
  .side-panel{display:flex;flex-direction:column;gap:16px}
  .side-card{border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;transition:.2s}
  .side-card:hover{box-shadow:var(--shadow-card-hov)}
  .side-card-header{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,.5)}
  .side-card-title{font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--deep-2)}
  .side-list{display:flex;flex-direction:column;gap:2px;padding:10px}
  .side-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:12.5px;font-weight:600;color:var(--ink-soft);cursor:pointer;transition:.18s;text-decoration:none}
  .side-link:hover{background:var(--tint);color:var(--deep-2)}
  .side-link.active{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff;font-weight:700;box-shadow:var(--cta-btn-shadow)}
  .side-link.active i{color:var(--mint)}
  .side-link i{width:18px;text-align:center;font-size:12px;opacity:.8}
  .side-link .cnt{margin-left:auto;font-size:10px;font-weight:800;background:rgba(255,255,255,.14);padding:1px 7px;border-radius:10px}
  .side-link:not(.active) .cnt{background:var(--mist-b);color:var(--ink-soft)}

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
  .modal-header{display:flex;align-items:center;gap:14px;padding:18px 24px;position:relative;overflow:hidden;background:radial-gradient(120% 150% at 88% -30%, rgba(127,209,192,.28), transparent 55%),linear-gradient(135deg,var(--deep-2) 0%,var(--deep-3) 100%)}
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,var(--mint),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
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
  .switch{position:relative;width:40px;height:22px;display:inline-block;flex-shrink:0}
  .switch input{opacity:0;width:0;height:0}
  .switch-slider{position:absolute;inset:0;background:var(--border);border-radius:20px;cursor:pointer;transition:.2s}
  .switch-slider::before{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .switch input:checked + .switch-slider{background:linear-gradient(90deg,var(--sec),var(--sec-2))}
  .switch input:checked + .switch-slider::before{transform:translateX(18px)}
  .switch-row{display:flex;align-items:center;gap:10px}
  .switch-row-label{font-size:12.5px;font-weight:600}
  .switch-row-sub{font-size:11px;color:var(--ink-muted)}
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-brand">
    <div class="topnav-logo">L</div>
    <span class="topnav-app">CamelotBooks</span>
    <span class="topnav-sep">|</span>
    <span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span>
  </div>
  <div class="topnav-center">
    <a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a>
    <a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a>
    <div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search customers, invoices… <kbd>Ctrl K</kbd></div>
  </div>
  <div class="topnav-right">
    <button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
    <button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button>
    <div class="topnav-user">
      <div class="topnav-avatar">ES</div>
      <div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div>
    </div>
  </div>
</nav>

<nav class="modulenav">
  <a class="module-item active" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
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
  <a class="sidebar-item active" data-tip="Customers" href="#"><i class="fa-solid fa-users"></i></a>
  <a class="sidebar-item" data-tip="Quotations" href="#"><i class="fa-solid fa-file-signature"></i></a>
  <a class="sidebar-item" data-tip="Sales Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Invoices" href="#"><i class="fa-solid fa-file-invoice"></i></a>
  <a class="sidebar-item" data-tip="Receipts" href="#"><i class="fa-solid fa-receipt"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main">

  <div class="breadcrumb anim-1">
    <a href="#">Sales</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current">Customers</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Customers</h1>
      <div class="page-subtitle">Manage customer records, terms and balances.</div>
    </div>
    <div class="page-header-actions">
      <button class="btn btn-ghost"><i class="fa-solid fa-download"></i> Export</button>
      <button class="btn btn-deep" onclick="openModal('modal-create-customer')"><i class="fa-solid fa-plus"></i> Create Customer</button>
    </div>
  </div>

  <div class="idx-grid anim-2">
    <div style="display:flex;flex-direction:column;gap:0">

      <!-- portfolio -->
      <div class="card glass" style="margin-bottom:18px">
        <div class="card-head">
          <div class="card-head-icon"><i class="fa-solid fa-users"></i></div>
          <div><div class="card-title">Portfolio</div><div class="card-sub">Directory snapshot · Oct 2026</div></div>
          <div class="card-head-right"><span class="chip-count"><i class="fa-solid fa-circle-check" style="font-size:9px"></i> Synced just now</span></div>
        </div>
        <div class="card-body">
          <div class="portfolio-strip" style="margin-bottom:0">
            <div class="pf-card">
              <div class="pf-top"><span class="pf-label">Customers</span><div class="pf-icon"><i class="fa-solid fa-user-group"></i></div></div>
              <div class="pf-value">6</div>
              <div class="pf-sub">All time created</div>
            </div>
            <div class="pf-card">
              <div class="pf-top"><span class="pf-label">Active</span><div class="pf-icon"><i class="fa-solid fa-circle-check"></i></div></div>
              <div class="pf-value">6</div>
              <div class="pf-sub">None archived</div>
            </div>
            <div class="pf-card hl">
              <div class="pf-top"><span class="pf-label">Outstanding (K)</span><div class="pf-icon"><i class="fa-solid fa-dollar-sign"></i></div></div>
              <div class="pf-value"><span class="cur">K</span>8,178.30</div>
              <div class="pf-sub">Across 3 open invoices</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ageing -->
      <div class="card glass" style="margin-bottom:18px">
        <div class="card-head">
          <div class="card-head-icon" style="background:linear-gradient(180deg,#E8930C,var(--warn))"><i class="fa-solid fa-hourglass-half"></i></div>
          <div><div class="card-title">Receivables Ageing</div><div class="card-sub">Open invoice balance by bucket · drill-down ready</div></div>
          <div class="card-head-right"><span class="pill" style="background:var(--red-tint);color:var(--red-2);border:1px solid rgba(220,38,38,.25)"><span class="pdot" style="background:var(--red)"></span> K 8,178.30 overdue 30d+</span></div>
        </div>
        <div class="card-body" style="padding:0">
          <div class="ageing" style="border:none;border-radius:0;box-shadow:none;margin:0">
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--green)"></span> Current</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--green)"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--sec)"></span> 1–30 Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--sec)"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--warn)"></span> 31–60 Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--warn)"></div></div>
            </div>
            <div class="ag-cell hot">
              <div class="ag-label"><span class="agdot" style="background:#EA580C"></span> 61–90 Days</div>
              <div class="ag-val">8,178.30</div>
              <div class="ag-bar"><div class="ag-fill" style="width:100%;background:linear-gradient(90deg,#EA580C,var(--red))"></div></div>
            </div>
            <div class="ag-cell">
              <div class="ag-label"><span class="agdot" style="background:var(--red)"></span> 90+ Days</div>
              <div class="ag-val">0.00</div>
              <div class="ag-bar"><div class="ag-fill" style="width:0%;background:var(--red)"></div></div>
            </div>
          </div>
        </div>
      </div>

      <!-- filters + table -->
      <div class="card glass">
        <div class="card-body">
          <div class="tab-toolbar" style="margin-bottom:14px">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Name or email…"></div>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Statuses</option><option>Active</option><option>Inactive</option></select>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Terms</option><option>Net 30</option><option>Due on receipt</option></select>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            </div>
            <span class="chip-count">6 customers</span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Terms</th><th class="td-right">Balance (K)</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">JB<span class="astat on"></span></div><div><div class="cell-name">John Banda</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>john@example.com</td><td class="td-mono">+265 991 234 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-red">8,178.30</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" data-tip="View" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" data-tip="Edit" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" data-tip="Archive" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">MP<span class="astat on"></span></div><div><div class="cell-name">Mary Phiri</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>mary@example.com</td><td class="td-mono">+265 882 345 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">PM<span class="astat on"></span></div><div><div class="cell-name">Peter Mwangonde</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>peter@example.com</td><td class="td-mono">+265 773 456 …</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">ES<span class="astat on"></span></div><div><div class="cell-name">Elvis Seyama</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td>elvis.seyama@gmail.com</td><td class="td-mono">0999602605</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">PC<span class="astat on"></span></div><div><div class="cell-name">Probe Customer…</div><div class="cell-sub">Since Aug 2026</div></div></div></td>
                    <td>probe@customer.test</td><td class="td-mono">+1 555 0100</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0" onclick="window.location.href='customer-detail.html'">
                    <td><div class="avatar-cell"><div class="avatar">WC<span class="astat on"></span></div><div><div class="cell-name">Walk-in Customer</div><div class="cell-sub">Since Jul 2026</div></div></div></td>
                    <td class="td-mono" style="color:#9DB4B4">—</td><td class="td-mono" style="color:#9DB4B4">—</td>
                    <td><span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Net 30</span></td>
                    <td class="td-right td-mono td-strong">0.00</td>
                    <td><span class="pill pill-active"><span class="pdot"></span> Active</span></td>
                    <td><button class="row-icon-btn" onclick="event.stopPropagation();window.location.href='customer-detail.html'"><i class="fa-solid fa-eye"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i></button> <button class="row-icon-btn" onclick="event.stopPropagation();openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer"><span>Showing 1–6 of 6 customers</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- right rail -->
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Views</span></div>
        <div class="side-list">
          <a class="side-link active"><i class="fa-solid fa-users"></i> All Customers <span class="cnt">6</span></a>
          <a class="side-link"><i class="fa-solid fa-circle-check"></i> Active <span class="cnt">6</span></a>
          <a class="side-link"><i class="fa-solid fa-circle-minus"></i> Inactive <span class="cnt">0</span></a>
          <a class="side-link"><i class="fa-solid fa-triangle-exclamation"></i> With Balance <span class="cnt">1</span></a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Reports</span></div>
        <div class="side-list">
          <a class="side-link"><i class="fa-solid fa-dollar-sign"></i> Customer Balances</a>
          <a class="side-link"><i class="fa-solid fa-chart-pie"></i> Sales by Customer</a>
          <a class="side-link"><i class="fa-solid fa-hourglass-half"></i> Ageing Report</a>
          <a class="side-link"><i class="fa-solid fa-file-invoice"></i> Statement Batch</a>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═══════ MODALS ═══════ -->

<!-- CREATE CUSTOMER -->
<div class="modal-overlay" id="modal-create-customer">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-user-plus"></i></div>
      <div class="modal-head-text"><div class="modal-title">Create Customer</div><div class="modal-subtitle">Add a new customer record to the directory</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" placeholder="e.g. John Banda"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" placeholder="Registered company name"></div>
        <div class="form-field"><label>Customer Code</label><input class="form-control" value="CUS-AUTO" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Retail</option><option>Wholesale</option><option>Corporate</option><option>Government</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" placeholder="TPIN / VAT Registration"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" placeholder="name@company.com"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" placeholder="+260 ..."></div>
        <div class="form-field full"><label>Website</label><input class="form-control" placeholder="https://..."></div>
      </div>
      <div class="form-section-title">Billing & Shipping</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control" placeholder="Street, City, Country..."></textarea></div>
        <div class="form-field full"><label>Shipping Address</label><textarea class="form-control" placeholder="Same as billing? Leave blank."></textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="30"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option>Bank Transfer</option><option>Cheque</option><option>Cash</option></select></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Active Immediately</div><div class="switch-row-sub">Enable this customer for transactions upon creation</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Create Customer</button>
    </div>
  </div>
</div>

<!-- EDIT CUSTOMER -->
<div class="modal-overlay" id="modal-edit-customer">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div class="modal-head-text"><div class="modal-title">Edit Customer</div><div class="modal-subtitle">Update master data for CUS-00002</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" value="John Banda"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" value="John Banda Enterprises Ltd"></div>
        <div class="form-field"><label>Customer Code</label><input class="form-control" value="CUS-00002" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option selected>Retail</option><option>Wholesale</option><option>Corporate</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option selected>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" value="1002345678"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" value="john@example.com"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" value="+265 991 234 567"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" value=""></div>
      </div>
      <div class="form-section-title">Billing & Shipping</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
        <div class="form-field full"><label>Shipping Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="30"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option selected>Bank Transfer</option><option>Cheque</option><option>Cash</option></select></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Customer is Active</div><div class="switch-row-sub">Currently active — toggle to archive/deactivate</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Customer</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- ARCHIVE CONFIRM -->
<div class="modal-overlay" id="modal-archive-confirm">
  <div class="modal" style="max-width:440px">
    <div class="modal-header" style="background:linear-gradient(135deg,#991B1B,#7F1D1D)">
      <div class="modal-icon" style="background:rgba(255,255,255,.15)"><i class="fa-solid fa-box-archive"></i></div>
      <div class="modal-head-text"><div class="modal-title">Archive Customer?</div><div class="modal-subtitle">John Banda (CUS-00002)</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">Archiving will prevent new invoices, orders, and payments from being recorded against this customer. Existing transaction history will remain intact.</p>
      <div style="background:var(--red-tint);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--red-2);font-weight:600;display:flex;gap:8px;align-items:flex-start">
        <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i>
        <span>This customer currently has an outstanding balance of <strong>K 8,178.30</strong>. Ensure all dues are settled before archiving.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none"><i class="fa-solid fa-check"></i> Yes, Archive</button>
    </div>
  </div>
</div>

<script>
/* modals */
const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

/* sidebar */
document.querySelectorAll('.sidebar-item').forEach(item=>{
  item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
</script>
</body>
</html>
```

### Mockup 2: Customer Detail (`customer-detail.html`)

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Detail – John Banda | CamelotBooks ERP</title>
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
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
  .anim-3{animation:fadeUp .4s ease both;animation-delay:.19s}
  .anim-4{animation:fadeUp .4s ease both;animation-delay:.26s}

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

  /* ═ HERO ═ */
  .cust-hero{border-radius:var(--r-xl);border:1px solid var(--border);padding:26px 30px;margin-bottom:20px;box-shadow:var(--shadow-card);position:relative;overflow:hidden;background:var(--card);backdrop-filter:blur(14px)}
  .cust-hero::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:0 0 6px 6px;background:linear-gradient(90deg,transparent 0%,var(--mint) 22%,var(--sec) 50%,var(--mint) 78%,transparent 100%);box-shadow:0 2px 10px rgba(16,124,123,.35);pointer-events:none}
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

  /* tabs card */
  .content-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
  .tabs-card{border-radius:var(--r-xl);border:1px solid var(--border);box-shadow:var(--shadow-card);overflow:hidden;position:relative}
  .tabs-card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:0 0 6px 6px;background:linear-gradient(90deg,transparent 0%,var(--mint) 22%,var(--sec) 50%,var(--mint) 78%,transparent 100%);box-shadow:0 2px 10px rgba(16,124,123,.35);z-index:1;pointer-events:none}
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
  .type-inv{background:var(--blue-tint);color:var(--blue)}
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
  .data-table tbody tr.clickable-row:active td{background:rgba(16,124,123,.14)}
  .data-table tbody tr.clickable-row:focus-visible{outline:2px solid var(--sec);outline-offset:-2px}
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
  .modal-header::after{content:'';position:absolute;left:0;right:0;bottom:0;height:1px;background:linear-gradient(90deg,var(--mint),transparent 60%)}
  .modal-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(180deg,#14918F,var(--sec-2));color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;box-shadow:var(--teal-btn-shadow)}
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

  /* extra spacing above dynamic payment sections */
  #pay-elec,
  #pay-cash,
  #pay-split{
    margin-top:34px;
  }
  #pay-elec .form-section-title,
  #pay-cash .form-section-title,
  #pay-split .form-section-title{
    margin-top:0;
  }

  /* ═ PREMIUM SPLIT CHECKBOX TILES ═ */
  .split-opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
  .split-opt{position:relative}
  .split-head{display:flex;align-items:center;gap:11px;padding:13px 14px;border:1.5px solid var(--border);border-radius:12px;background:rgba(255,255,255,.78);cursor:pointer;transition:all .22s;position:relative;user-select:none}
  .split-head:hover{border-color:var(--mint);transform:translateY(-1px);box-shadow:var(--shadow-card)}
  .sp-check{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
  .sp-box{width:22px;height:22px;border-radius:7px;border:1.5px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;transition:all .22s;box-shadow:inset 0 1px 2px rgba(10,42,46,.07);flex-shrink:0}
  .sp-box i{font-size:10px;color:#fff;transform:scale(0) rotate(-25deg);transition:transform .25s cubic-bezier(.2,.9,.3,1.5)}
  .sp-check:checked ~ .sp-box{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:var(--sec);box-shadow:var(--teal-btn-shadow)}
  .sp-check:checked ~ .sp-box i{transform:scale(1) rotate(0deg)}
  .sp-check:focus-visible ~ .sp-box{outline:2px solid var(--sec);outline-offset:2px}
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
</style>
</head>
<body>

<nav class="topnav">
  <div class="topnav-brand">
    <div class="topnav-logo">L</div>
    <span class="topnav-app">CamelotBooks</span>
    <span class="topnav-sep">|</span>
    <span class="topnav-org"><strong>Acme Corporation</strong> &nbsp;·&nbsp; Headquarters</span>
  </div>
  <div class="topnav-center">
    <a class="topnav-pill" href="#"><i class="fa-solid fa-list-check"></i> My Tasks <span class="badge-count">3</span></a>
    <a class="topnav-pill" href="#"><i class="fa-solid fa-star"></i> Favourites <span class="badge-count">16</span></a>
    <div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search customers, invoices… <kbd>Ctrl K</kbd></div>
  </div>
  <div class="topnav-right">
    <button class="topnav-icon-btn" data-tip="Notifications"><i class="fa-solid fa-bell"></i><span class="notif-dot"></span></button>
    <button class="topnav-icon-btn" data-tip="Help"><i class="fa-solid fa-circle-question"></i></button>
    <div class="topnav-user">
      <div class="topnav-avatar">ES</div>
      <div><div class="topnav-user-name">Elvis Seyama</div><div class="topnav-user-role">SYSTEM_ADMIN</div></div>
    </div>
  </div>
</nav>

<nav class="modulenav">
  <a class="module-item active" href="#"><i class="fa-solid fa-cart-shopping"></i> Sales <i class="fa-solid fa-chevron-down chevron"></i></a>
  <a class="module-item" href="#"><i class="fa-solid fa-truck-field"></i> Purchasing <i class="fa-solid fa-chevron-down chevron"></i></a>
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
  <a class="sidebar-item active" data-tip="Customers" href="#"><i class="fa-solid fa-users"></i></a>
  <a class="sidebar-item" data-tip="Quotations" href="#"><i class="fa-solid fa-file-signature"></i></a>
  <a class="sidebar-item" data-tip="Sales Orders" href="#"><i class="fa-solid fa-file-lines"></i></a>
  <a class="sidebar-item" data-tip="Invoices" href="#"><i class="fa-solid fa-file-invoice"></i></a>
  <a class="sidebar-item" data-tip="Receipts" href="#"><i class="fa-solid fa-receipt"></i></a>
  <div class="sidebar-divider"></div>
  <a class="sidebar-item" data-tip="Inventory" href="#"><i class="fa-solid fa-boxes-stacked"></i></a>
  <a class="sidebar-item" data-tip="Analytics" href="#"><i class="fa-solid fa-chart-pie"></i></a>
  <a class="sidebar-item" data-tip="Reports" href="#"><i class="fa-solid fa-chart-column"></i></a>
  <div class="sidebar-spacer"></div>
  <a class="sidebar-item" data-tip="Settings" href="#"><i class="fa-solid fa-sliders"></i></a>
</aside>

<main class="main">

  <div class="breadcrumb anim-1">
    <a href="customers-index.html">Customers</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current">John Banda</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Customer Detail — John Banda</h1>
      <div class="page-subtitle">
        <span>CUS-00002</span><span class="dot"></span>
        <span>Since Jul 2026</span><span class="dot"></span>
        <span>Last activity Oct 24, 2026</span>
      </div>
    </div>
    <div class="page-header-actions">
      <button class="btn-icon" data-tip="Print"><i class="fa-solid fa-print"></i></button>
      <button class="btn-icon" data-tip="Export PDF"><i class="fa-solid fa-file-export"></i></button>
      <button class="btn btn-ghost"><i class="fa-solid fa-ellipsis-vertical"></i> More</button>
      <button class="btn btn-deep" onclick="openModal('modal-edit-customer')"><i class="fa-solid fa-pen"></i> Edit Customer</button>
    </div>
  </div>

  <div class="cust-hero glass anim-2">
    <div class="hero-inner">
      <div class="hero-avatar-wrap">
        <div class="hero-avatar">JB</div>
      </div>
      <div class="hero-info">
        <div class="hero-name-row">
          <span class="hero-name">John Banda</span>
          <span class="pill pill-active"><span class="pdot"></span> Active</span>
          <span class="hero-code">CUS-00002</span>
        </div>
        <div class="hero-meta">
          <span class="hero-meta-item"><i class="fa-solid fa-envelope"></i> john@example.com</span>
          <span class="hero-meta-item"><i class="fa-solid fa-phone"></i> +265 991 234 567</span>
          <span class="hero-meta-item"><i class="fa-solid fa-clock"></i> Net 30</span>
          <span class="hero-meta-item"><i class="fa-solid fa-coins"></i> K — Kwacha</span>
        </div>
      </div>
      <!-- CREDIT GAUGE -->
      <div class="gauge-wrap" data-tip="Credit utilisation vs limit">
        <div class="gauge-holder">
          <svg class="gauge-svg" viewBox="0 0 100 100">
            <defs><linearGradient id="gg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#7FD1C0"/><stop offset="100%" stop-color="#107C7B"/></linearGradient></defs>
            <circle class="gauge-track" cx="50" cy="50" r="42"/>
            <circle class="gauge-fill" cx="50" cy="50" r="42" style="--dash-to:264"/>
          </svg>
          <div class="gauge-center"><div class="gauge-pct">∞</div><div class="gauge-lbl">No Limit</div></div>
        </div>
        <div class="gauge-stats">
          <div class="gs-row">Limit <b>0.00</b></div>
          <div class="gs-row">Used <b class="red">8,178.30</b></div>
          <div class="gs-row">Avail <b>—</b></div>
        </div>
      </div>
      <div class="hero-actions">
        <button class="btn btn-danger-ghost" onclick="openModal('modal-archive-confirm')"><i class="fa-solid fa-box-archive"></i> Archive</button>
      </div>
    </div>
    <div class="quick-chips">
      <a class="quick-chip green" onclick="openModal('modal-new-invoice')"><i class="fa-solid fa-plus"></i> New Invoice</a>
      <a class="quick-chip" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
      <a class="quick-chip" onclick="switchTab('statement')"><i class="fa-solid fa-file-invoice-dollar"></i> View Statement</a>
      <a class="quick-chip"><i class="fa-solid fa-print"></i> Print</a>
      <a class="quick-chip" onclick="openModal('modal-send-message')"><i class="fa-solid fa-envelope"></i> Email Statement</a>
      <a class="quick-chip"><i class="fa-solid fa-star"></i> Add to Favourites</a>
    </div>
  </div>

  <div class="fin-strip">
    <div class="fin-card highlight">
      <div class="fin-card-top"><span class="fin-label">Open Balance (K)</span><div class="fin-icon-wrap"><i class="fa-solid fa-wallet"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>8,178.30</div>
      <div class="fin-sub">3 open invoices · oldest due Sep 24</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Total Invoiced</span><div class="fin-icon-wrap"><i class="fa-solid fa-file-invoice"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>24,529.08</div>
      <div class="fin-sub">4 invoices issued to date</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Opening Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-scale-balanced"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">No opening balance set</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Payment Terms</span><div class="fin-icon-wrap"><i class="fa-solid fa-clock-rotate-left"></i></div></div>
      <div class="fin-value terms">Net 30</div>
      <div class="fin-sub">30 days from invoice date</div>
    </div>
  </div>

  <div class="content-grid anim-4">
    <div class="tabs-card glass">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="info">Information</button>
        <button class="tab-btn" data-tab="transactions">Transactions <span class="tab-count">3</span></button>
        <button class="tab-btn" data-tab="statement">Statement</button>
        <button class="tab-btn" data-tab="payments">Payments <span class="tab-count">0</span></button>
        <button class="tab-btn" data-tab="notes">Notes <span class="tab-count">1</span></button>
      </div>
      <div class="tab-body">

        <!-- INFORMATION -->
        <div class="tab-panel active" id="panel-info">
          <div class="overview-section-title">Contact Information</div>
          <div class="info-grid">
            <div class="info-cell"><div class="info-cell-label">Display Name</div><div class="info-cell-value muted">Not set</div></div>
            <div class="info-cell"><div class="info-cell-label">Email Address</div><div class="info-cell-value">john@example.com</div></div>
            <div class="info-cell"><div class="info-cell-label">Phone Number</div><div class="info-cell-value mono">+265 991 234 567</div></div>
            <div class="info-cell"><div class="info-cell-label">Currency</div><div class="info-cell-value mono">K — Kwacha</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms</div><div class="info-cell-value">Net 30</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms (Days)</div><div class="info-cell-value mono">30</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance</div><div class="info-cell-value mono">K 0.00</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance Date</div><div class="info-cell-value muted">Not set</div></div>
            <div class="info-cell"><div class="info-cell-label">Credit Limit</div><div class="info-cell-value muted">Unlimited</div></div>
          </div>
          <div class="overview-section-title">Addresses</div>
          <div class="addresses-row">
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-file-invoice"></i> Billing Address</div><div class="address-block-value empty">No billing address on file. Click Edit to add one.</div></div>
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-truck-fast"></i> Shipping Address</div><div class="address-block-value empty">No shipping address on file. Click Edit to add one.</div></div>
          </div>
          <div class="overview-section-title">Recent Activity</div>
          <div class="timeline">
            <div class="timeline-item"><div class="timeline-dot blue"></div><div class="timeline-title">Invoice INV-2026-0004 sent</div><div class="timeline-desc">Issued K 8,178.30 — emailed to john@example.com.</div><div class="timeline-time">Oct 24, 2026 · 11:02 AM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Invoice INV-2026-0003 drafted</div><div class="timeline-desc">K 8,172.48 prepared but not yet issued.</div><div class="timeline-time">Oct 24, 2026 · 10:48 AM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Invoice INV-2026-0002 created</div><div class="timeline-desc">K 8,178.30 received — due Nov 08, 2026.</div><div class="timeline-time">Oct 09, 2026 · 09:15 AM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Customer record created</div><div class="timeline-desc">John Banda was added to the directory by Elvis Seyama.</div><div class="timeline-time">Jul 12, 2026 · 08:30 AM</div></div>
          </div>
        </div>

        <!-- TRANSACTIONS -->
        <div class="tab-panel" id="panel-transactions">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search transactions…"></div>
              <select class="btn btn-ghost btn-sm" style="appearance:auto;-webkit-appearance:auto"><option>All Types</option><option>Invoices</option><option>Payments</option><option>Credits</option></select>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Oct 01 – Oct 31, 2026</button>
            </div>
            <span class="chip-count">3 transactions</span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Type</th><th>Date</th><th>Reference</th><th class="td-right">Amount (K)</th><th class="td-right">Paid</th><th class="td-right">Balance</th><th>Status</th></tr></thead>
                <tbody>
                  <tr class="clickable-row" tabindex="0">
                    <td><span class="type-chip type-inv"><i class="fa-solid fa-file-invoice"></i> Invoice</span></td>
                    <td>Oct 24, 2026</td><td class="td-mono"><a class="row-link" href="#">INV-2026-0003</a></td>
                    <td class="td-right td-mono td-strong">8,172.48</td><td class="td-right td-mono">0.00</td>
                    <td class="td-right td-mono td-red">8,172.48</td>
                    <td><span class="pill pill-draft"><span class="pdot"></span> Draft</span></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0">
                    <td><span class="type-chip type-inv"><i class="fa-solid fa-file-invoice"></i> Invoice</span></td>
                    <td>Oct 24, 2026</td><td class="td-mono"><a class="row-link" href="#">INV-2026-0004</a></td>
                    <td class="td-right td-mono td-strong">8,178.30</td><td class="td-right td-mono">0.00</td>
                    <td class="td-right td-mono td-red">8,178.30</td>
                    <td><span class="pill pill-sent"><span class="pdot"></span> Sent</span></td>
                  </tr>
                  <tr class="clickable-row" tabindex="0">
                    <td><span class="type-chip type-inv"><i class="fa-solid fa-file-invoice"></i> Invoice</span></td>
                    <td>Oct 09, 2026</td><td class="td-mono"><a class="row-link" href="#">INV-2026-0002</a></td>
                    <td class="td-right td-mono td-strong">8,178.30</td><td class="td-right td-mono">0.00</td>
                    <td class="td-right td-mono td-red">8,178.30</td>
                    <td><span class="pill pill-draft"><span class="pdot"></span> Draft</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer"><span>Showing 1–3 of 3 transactions</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
          </div>
        </div>

        <!-- STATEMENT -->
        <div class="tab-panel" id="panel-statement">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Jul 01 – Oct 31, 2026</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export PDF</button>
              <button class="btn btn-ghost btn-sm" onclick="openModal('modal-send-message')"><i class="fa-solid fa-envelope"></i> Email Statement</button>
            </div>
            <span style="font-size:12px;color:var(--ink-soft);font-weight:600">Closing balance: <strong style="color:var(--red-2)">K 8,178.30</strong></span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Entry</th><th>Type</th><th>Reference</th><th class="td-right">Debit</th><th class="td-right">Credit</th><th class="td-right">Balance</th></tr></thead>
                <tbody>
                  <tr><td>Jul 12, 2026</td><td class="td-mono">OB-0001</td><td><span class="type-chip type-jn">Opening</span></td><td class="td-mono">—</td><td class="td-right td-mono">—</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">0.00</td></tr>
                  <tr><td>Aug 15, 2026</td><td class="td-mono">JE-0912</td><td><span class="type-chip type-inv">Invoice</span></td><td class="td-mono">INV-2026-0001</td><td class="td-right td-mono">—</td><td class="td-right td-mono">8,000.00</td><td class="td-right td-mono td-strong">8,000.00</td></tr>
                  <tr><td>Sep 02, 2026</td><td class="td-mono">JE-0958</td><td><span class="type-chip type-pay">Payment</span></td><td class="td-mono">PAY-2026-0012</td><td class="td-right td-mono">8,000.00</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">0.00</td></tr>
                  <tr><td>Oct 09, 2026</td><td class="td-mono">JE-1021</td><td><span class="type-chip type-inv">Invoice</span></td><td class="td-mono">INV-2026-0002</td><td class="td-right td-mono">—</td><td class="td-right td-mono">8,178.30</td><td class="td-right td-mono td-strong">8,178.30</td></tr>
                  <tr><td>Oct 24, 2026</td><td class="td-mono">JE-1044</td><td><span class="type-chip type-inv">Invoice</span></td><td class="td-mono">INV-2026-0004</td><td class="td-right td-mono">—</td><td class="td-right td-mono">8,178.30</td><td class="td-right td-mono td-strong">16,356.60</td></tr>
                  <tr><td>Oct 26, 2026</td><td class="td-mono">JE-1051</td><td><span class="type-chip type-cn">Credit Note</span></td><td class="td-mono">CN-2026-0003</td><td class="td-right td-mono">8,178.30</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">8,178.30</td></tr>
                  <tr class="ledger-total"><td colspan="4">Totals</td><td class="td-right td-mono">16,178.30</td><td class="td-right td-mono">24,356.60</td><td class="td-right td-mono">8,178.30</td></tr>
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
            <div class="empty-sub">Settlements received from this customer will appear here.</div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-plus"></i> Record Payment</button>
          </div>
        </div>

        <!-- NOTES -->
        <div class="tab-panel" id="panel-notes">
          <div class="tab-toolbar">
            <div class="toolbar-left"><button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> All notes</button></div>
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-note')"><i class="fa-solid fa-note-sticky"></i> Add Note</button>
          </div>
          <div class="timeline">
            <div class="timeline-item"><div class="timeline-dot blue"></div><div class="timeline-title">Pricing agreement renewed</div><div class="timeline-desc">Discussed volume discount for Q4 — awaiting signed amendment before applying new rates to invoices.</div><div class="timeline-time">Sep 30, 2026 · Elvis Seyama</div></div>
          </div>
        </div>

      </div>
    </div>

    <!-- side panel -->
    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Summary</span></div>
        <div class="side-card-body">
          <div class="sum-row"><span class="sum-lbl">Opening Balance</span><span class="sum-val">0.00</span></div>
          <div class="sum-row"><span class="sum-lbl">Total Invoiced</span><span class="sum-val">24,529.08</span></div>
          <div class="sum-row"><span class="sum-lbl">Total Paid</span><span class="sum-val">16,350.78</span></div>
          <div class="sum-row"><span class="sum-lbl">Credit Limit</span><span class="sum-val">0.00</span></div>
          <div class="sum-big"><span class="sb-lbl">Open Balance</span><span class="sb-val">8,178.30</span></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Quick Nav</span></div>
        <div class="qnav">
          <a class="qnav-item" onclick="openModal('modal-new-invoice')"><i class="fa-solid fa-file-invoice"></i> New Invoice</a>
          <a class="qnav-item" onclick="openModal('modal-record-payment')"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
          <a class="qnav-item" onclick="switchTab('statement')"><i class="fa-solid fa-chart-column"></i> View Statement</a>
          <a class="qnav-item"><i class="fa-solid fa-print"></i> Print</a>
          <a class="qnav-item" onclick="openModal('modal-send-message')"><i class="fa-solid fa-envelope"></i> Email Statement</a>
          <a class="qnav-item" href="customers-index.html"><i class="fa-solid fa-users"></i> All Customers</a>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Contact Details</span><a class="side-card-action" href="#" onclick="openModal('modal-edit-customer')">Edit</a></div>
        <div class="side-card-body">
          <div class="contact-item"><div class="contact-icon email"><i class="fa-solid fa-envelope"></i></div><div><div class="contact-type">Email</div><div class="contact-val">john@example.com</div></div></div>
          <div class="contact-item"><div class="contact-icon phone"><i class="fa-solid fa-phone"></i></div><div><div class="contact-type">Phone</div><div class="contact-val" style="font-family:'JetBrains Mono',monospace">+265 991 234 567</div></div></div>
          <div class="contact-item"><div class="contact-icon web"><i class="fa-solid fa-globe"></i></div><div><div class="contact-type">Website</div><div class="contact-val empty">Not provided</div></div></div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═══════ MODALS ═══════ -->

<!-- RECORD PAYMENT -->
<div class="modal-overlay" id="modal-record-payment">
  <div class="modal mid">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-credit-card"></i></div>
      <div class="modal-head-text"><div class="modal-title">Record Payment</div><div class="modal-subtitle">Apply a payment to open invoices for John Banda</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
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
        <div class="form-field"><label>Paying Bank Account</label>
          <select class="form-control"><option>Zanaco — ****4402</option><option>Stanbic — ****1188</option><option>Cash Drawer — HQ</option></select>
        </div>
        <div class="form-field"><label>Payment Amount (K) <span class="req">*</span></label><input class="form-control" id="pay-amount" style="text-align:right" value="8,178.30"></div>
      </div>

      <!-- electronic method details -->
      <div id="pay-elec" style="display:none">
        <div class="form-section-title">Transaction Details <span class="method-flag" id="elec-flag"><i class="fa-solid fa-building-columns"></i> Bank Transfer</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control" id="pay-ref" placeholder="e.g. TXN-88213 / UTR-004512"></div>
          <div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control" id="pay-provider"></select></div>
          <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Main Operating Account"></div>
          <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. John Banda"></div>
        </div>
      </div>

      <!-- cash details -->
      <div id="pay-cash" style="display:none">
        <div class="form-section-title">Cash Handling <span class="method-flag" style="background:var(--green-tint);color:var(--green)"><i class="fa-solid fa-money-bill"></i> Cash</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div>
          <div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div>
        </div>
      </div>

      <!-- ═ SPLIT PAYMENT — CHECKBOX TILES ═ -->
      <div id="pay-split" style="display:none">
        <div class="form-section-title">Split Allocation <span class="method-flag"><i class="fa-solid fa-layer-group"></i> Tick every method used</span></div>

        <div class="split-opts" id="split-opts">

          <!-- CASH -->
          <div class="split-opt" data-method="cash">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-money-bill"></i></span>
              <span class="sp-text"><span class="sp-name">Cash</span><span class="sp-hint">Tendered & change</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Cash Tendered</label><input class="form-control" style="text-align:right" placeholder="—"></div>
                <div class="form-field"><label>Change Given</label><input class="form-control" style="text-align:right" placeholder="—"></div>
              </div>
            </div>
          </div>

          <!-- BANK TRANSFER -->
          <div class="split-opt" data-method="bank">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-building-columns"></i></span>
              <span class="sp-text"><span class="sp-name">Bank Transfer</span><span class="sp-hint">Reference & bank details</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. UTR-004512"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>Zanaco</option><option>Stanbic Bank</option><option>FNB Zambia</option><option>Absa Bank</option><option>Investrust Bank</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Vendor Settlement Account"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. John Banda"></div>
              </div>
            </div>
          </div>

          <!-- CARD -->
          <div class="split-opt" data-method="card">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-credit-card"></i></span>
              <span class="sp-text"><span class="sp-name">Card</span><span class="sp-hint">Terminal / scheme details</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. AUTH-771203"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>Visa</option><option>Mastercard</option><option>American Express</option><option>UnionPay</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Merchant Settlement"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Acme Corporation"></div>
              </div>
            </div>
          </div>

          <!-- MOBILE MONEY -->
          <div class="split-opt" data-method="mobile">
            <label class="split-head">
              <input type="checkbox" class="sp-check">
              <span class="sp-box"><i class="fa-solid fa-check"></i></span>
              <span class="sp-icon"><i class="fa-solid fa-mobile-screen"></i></span>
              <span class="sp-text"><span class="sp-name">Mobile Money</span><span class="sp-hint">Wallet transaction capture</span></span>
              <span class="sp-amt-preview"></span>
              <i class="fa-solid fa-chevron-down sp-chev"></i>
            </label>
            <div class="split-fields">
              <div class="form-grid">
                <div class="form-field full"><label>Amount (K) <span class="req">*</span></label><input class="form-control sp-amt" style="text-align:right" placeholder="0.00"></div>
                <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control sp-ref" placeholder="e.g. MOMO-558812"></div>
                <div class="form-field"><label>Provider / Institution <span class="req">*</span></label>
                  <select class="form-control sp-provider"><option>MTN MoMo</option><option>Airtel Money</option><option>Zamtel Kwacha</option></select></div>
                <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Wallet Settlement"></div>
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. John Banda"></div>
              </div>
            </div>
          </div>

        </div>

        <div class="totals-block fullw">
          <div class="totals-row"><span>Payment Amount</span><span class="td-mono" id="sp-total">0.00</span></div>
          <div class="totals-row"><span>Allocated Across Selected Methods</span><span class="td-mono" id="sp-alloc">0.00</span></div>
          <div class="totals-row grand"><span>Unallocated</span><span class="td-mono" id="sp-remain">0.00</span></div>
        </div>
        <div id="split-banner" style="margin-top:12px"></div>
      </div>

      <div class="form-section-title">Apply To Open Invoices</div>
      <label class="apply-row"><input type="checkbox" checked><div class="apply-info"><div class="apply-title">INV-2026-0004</div><div class="apply-sub">Due Nov 23, 2026 · Sent</div></div><span class="apply-amt">8,178.30</span></label>
      <label class="apply-row"><input type="checkbox"><div class="apply-info"><div class="apply-title">INV-2026-0003</div><div class="apply-sub">Draft · Not Issued</div></div><span class="apply-amt">8,172.48</span></label>
      <div class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> Unapplied amount: K 0.00 — payment fully allocated.</div>
      <div class="form-field" style="margin-top:14px"><label>Payment Note</label><textarea class="form-control" style="min-height:60px" placeholder="Optional…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep" id="post-payment" disabled><i class="fa-solid fa-check"></i> Record Payment</button>
    </div>
  </div>
</div>

<!-- NEW INVOICE -->
<div class="modal-overlay" id="modal-new-invoice">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-file-invoice"></i></div>
      <div class="modal-head-text"><div class="modal-title">New Invoice</div><div class="modal-subtitle">Create a sales invoice for John Banda</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">Invoice Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Customer</label><input class="form-control" value="John Banda (CUS-00002)" disabled></div>
        <div class="form-field"><label>Invoice Number <span class="req">*</span></label><input class="form-control" value="INV-2026-0005"></div>
        <div class="form-field"><label>Invoice Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-28"></div>
        <div class="form-field"><label>Due Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-11-27"></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Payment Terms</label><select class="form-control"><option>Net 30</option><option>Due on Receipt</option><option>Net 15</option></select></div>
        <div class="form-field full"><label>Linked Sales Order</label><select class="form-control"><option>— None —</option><option>SO-2026-0004</option><option>SO-2026-0007</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="inv-items">
        <thead><tr><th style="width:46%">Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" value="Consulting Services — October"></td><td><input class="form-control" value="40"></td><td><input class="form-control" value="85.00"></td><td class="td-right td-mono">3,400.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
          <tr><td><input class="form-control" value="Server Maintenance Contract"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="1,200.00"></td><td class="td-right td-mono">1,200.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="inv-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">4,600.00</span></div>
        <div class="totals-row"><span>VAT 16%</span><span class="td-mono">736.00</span></div>
        <div class="totals-row grand"><span>Total Due</span><span class="td-mono">5,336.00</span></div>
      </div>
      <div class="form-section-title">Attachments & Notes</div>
      <div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach supporting documents (PDF/JPG)</div>
      <div class="form-field" style="margin-top:12px"><label>Internal Notes</label><textarea class="form-control" placeholder="Optional notes for internal reference…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-ghost">Save as Draft</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Invoice</button>
    </div>
  </div>
</div>

<!-- ADD NOTE -->
<div class="modal-overlay" id="modal-add-note">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-note-sticky"></i></div>
      <div class="modal-head-text"><div class="modal-title">Add Note</div><div class="modal-subtitle">Internal note or reminder on this customer</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Note Type</label><select class="form-control"><option>Internal Note</option><option>Reminder</option><option>Follow-up</option></select></div>
        <div class="form-field"><label>Reminder Date</label><input class="form-control" type="date"></div>
        <div class="form-field full"><label>Title <span class="req">*</span></label><input class="form-control" placeholder="e.g. Follow up on Q4 pricing"></div>
        <div class="form-field full"><label>Note <span class="req">*</span></label><textarea class="form-control" placeholder="Details visible to internal users only…"></textarea></div>
        <div class="form-field full">
          <div class="switch-row">
            <label class="switch"><input type="checkbox"><span class="switch-slider"></span></label>
            <div><div class="switch-row-label">Pin to customer profile</div><div class="switch-row-sub">Shows the note at the top of the Overview tab</div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Note</button>
    </div>
  </div>
</div>

<!-- SEND MESSAGE -->
<div class="modal-overlay" id="modal-send-message">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-envelope"></i></div>
      <div class="modal-head-text"><div class="modal-title">Send Message</div><div class="modal-subtitle">Email or SMS the customer contact</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field full"><label>To</label><input class="form-control" value="john@example.com" readonly></div>
        <div class="form-field"><label>Channel</label><select class="form-control"><option>Email</option><option>SMS</option></select></div>
        <div class="form-field"><label>Template</label><select class="form-control"><option>— Blank —</option><option>Statement Reminder</option><option>Payment Confirmation</option><option>Invoice Dispatch</option></select></div>
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Outstanding Invoice — INV-2026-0004"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear John,

This is a friendly reminder that invoice INV-2026-0004 (K 8,178.30) falls due on 23 Nov 2026. Kindly arrange settlement before the due date.

Regards,
Elvis Seyama — Acme Corporation</textarea></div>
        <div class="form-field full"><div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach files (optional)</div></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
    </div>
  </div>
</div>

<!-- EDIT CUSTOMER (reused from index) -->
<div class="modal-overlay" id="modal-edit-customer">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div class="modal-head-text"><div class="modal-title">Edit Customer</div><div class="modal-subtitle">Update master data for CUS-00002</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General Information</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" value="John Banda"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" value="John Banda Enterprises Ltd"></div>
        <div class="form-field"><label>Customer Code</label><input class="form-control" value="CUS-00002" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option selected>Retail</option><option>Wholesale</option><option>Corporate</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option selected>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" value="1002345678"></div>
      </div>
      <div class="form-section-title">Contact Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Email Address</label><input class="form-control" type="email" value="john@example.com"></div>
        <div class="form-field"><label>Phone Number</label><input class="form-control" value="+265 991 234 567"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" value=""></div>
      </div>
      <div class="form-section-title">Billing & Shipping</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
        <div class="form-field full"><label>Shipping Address</label><textarea class="form-control">Plot 123, Cairo Road, Lusaka, Zambia</textarea></div>
      </div>
      <div class="form-section-title">Financial Settings</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="30"></div>
        <div class="form-field"><label>Credit Limit</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Opening Balance</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field"><label>Preferred Method</label><select class="form-control"><option selected>Bank Transfer</option><option>Cheque</option><option>Cash</option></select></div>
        <div class="form-field full" style="display:flex;align-items:center;gap:10px">
          <label class="switch"><input type="checkbox" checked><span class="switch-slider"></span></label>
          <div><div class="switch-row-label">Customer is Active</div><div class="switch-row-sub">Currently active — toggle to archive/deactivate</div></div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Customer</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<!-- ARCHIVE CONFIRM -->
<div class="modal-overlay" id="modal-archive-confirm">
  <div class="modal" style="max-width:440px">
    <div class="modal-header" style="background:linear-gradient(135deg,#991B1B,#7F1D1D)">
      <div class="modal-icon" style="background:rgba(255,255,255,.15)"><i class="fa-solid fa-box-archive"></i></div>
      <div class="modal-head-text"><div class="modal-title">Archive Customer?</div><div class="modal-subtitle">John Banda (CUS-00002)</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;line-height:1.6;color:var(--ink-soft);margin-bottom:16px">Archiving will prevent new invoices, orders, and payments from being recorded against this customer. Existing transaction history will remain intact.</p>
      <div style="background:var(--red-tint);border:1px solid rgba(220,38,38,.2);border-radius:8px;padding:12px;font-size:12px;color:var(--red-2);font-weight:600;display:flex;gap:8px;align-items:flex-start">
        <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px"></i>
        <span>This customer currently has an outstanding balance of <strong>K 8,178.30</strong>. Ensure all dues are settled before archiving.</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-danger-ghost" style="background:var(--red);color:#fff;border:none"><i class="fa-solid fa-check"></i> Yes, Archive</button>
    </div>
  </div>
</div>

<script>
/* tabs */
function switchTab(tabId){
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  const btn=document.querySelector(`[data-tab="${tabId}"]`);
  if(btn){btn.classList.add('active');document.getElementById('panel-'+tabId).classList.add('active');}
}
document.querySelectorAll('.tab-btn').forEach(btn=>{
  btn.addEventListener('click',()=>switchTab(btn.dataset.tab));
});

/* modals */
const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(b.closest('.modal-overlay'))));
document.querySelectorAll('.modal-overlay').forEach(ov => {
  ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

/* line items */
document.querySelectorAll('[data-add-item]').forEach(btn => {
  btn.addEventListener('click', () => {
    const tbody = document.getElementById(btn.dataset.addItem).querySelector('tbody');
    const row = tbody.insertRow();
    row.style.animation = 'fadeUp .3s ease both';
    row.innerHTML = '<td><input class="form-control" placeholder="Item description"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="0.00"></td><td class="td-right td-mono">0.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td>';
  });
});
document.addEventListener('click', e => { const rm = e.target.closest('.item-remove'); if (rm) rm.closest('tr').remove(); });

/* ══ RECORD PAYMENT LOGIC ══ */
const PROVIDERS = {
  bank:   ['Zanaco', 'Stanbic Bank', 'FNB Zambia', 'Absa Bank', 'Investrust Bank'],
  mobile: ['MTN MoMo', 'Airtel Money', 'Zamtel Kwacha'],
  card:   ['Visa', 'Mastercard', 'American Express', 'UnionPay']
};
const METHOD_META = {
  bank:   { label: 'Bank Transfer', icon: 'fa-building-columns' },
  mobile: { label: 'Mobile Money',  icon: 'fa-mobile-screen' },
  card:   { label: 'Card',          icon: 'fa-credit-card' }
};
const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const num = v => parseFloat(String(v).replace(/,/g, '')) || 0;

const payMethod  = document.getElementById('pay-method');
const payAmount  = document.getElementById('pay-amount');
const elecPanel  = document.getElementById('pay-elec');
const cashPanel  = document.getElementById('pay-cash');
const splitPanel = document.getElementById('pay-split');
const splitOpts  = [...document.querySelectorAll('#split-opts .split-opt')];
const postBtn    = document.getElementById('post-payment');

function populateProviders(sel, method) { sel.innerHTML = (PROVIDERS[method] || []).map(p => `<option>${p}</option>`).join(''); }

function syncMethodPanels() {
  const v = payMethod.value;
  elecPanel.style.display  = ['bank','mobile','card'].includes(v) ? '' : 'none';
  cashPanel.style.display  = v === 'cash' ? '' : 'none';
  splitPanel.style.display = v === 'split' ? '' : 'none';
  if (elecPanel.style.display === '') {
    populateProviders(document.getElementById('pay-provider'), v);
    document.getElementById('elec-flag').innerHTML = `<i class="fa-solid ${METHOD_META[v].icon}"></i> ${METHOD_META[v].label}`;
  }
  if (v === 'split') recomputeSplits();
  else validatePayment();
}

/* split tiles */
function checkedOpts() { return splitOpts.filter(o => o.querySelector('.sp-check').checked); }
function recomputeSplits() {
  const total = num(payAmount.value);
  const checked = checkedOpts();
  const alloc = checked.reduce((s, o) => s + num(o.querySelector('.sp-amt').value), 0);
  const remain = total - alloc;
  document.getElementById('sp-total').textContent = fmt(total);
  document.getElementById('sp-alloc').textContent = fmt(alloc);
  document.getElementById('sp-remain').textContent = (remain < 0 ? '-' : '') + fmt(Math.abs(remain));
  const banner = document.getElementById('split-banner');
  if (checked.length === 0) {
    banner.innerHTML = '<div class="form-alert info" style="margin:0"><i class="fa-solid fa-hand-pointer"></i> Tick the methods used in this split — each selected method expands its capture fields.</div>';
  } else if (Math.abs(remain) <= 0.01) {
    banner.innerHTML = `<div class="form-alert ok" style="margin:0"><i class="fa-solid fa-circle-check"></i> Fully allocated across ${checked.length} method${checked.length > 1 ? 's' : ''}.</div>`;
  } else if (remain > 0) {
    banner.innerHTML = `<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> K ${fmt(remain)} still unallocated — enter amounts in the expanded method panels.</div>`;
  } else {
    banner.innerHTML = `<div class="form-alert warn" style="margin:0"><i class="fa-solid fa-triangle-exclamation"></i> Over-allocated by K ${fmt(Math.abs(remain))} — selected amounts exceed the payment.</div>`;
  }
  validatePayment();
}
splitOpts.forEach(opt => {
  const chk = opt.querySelector('.sp-check');
  chk.addEventListener('change', () => { opt.classList.toggle('on', chk.checked); recomputeSplits(); });
  opt.querySelector('.sp-amt').addEventListener('input', e => {
    const v = num(e.target.value);
    opt.querySelector('.sp-amt-preview').textContent = v > 0 ? fmt(v) : '';
    recomputeSplits();
  });
  const ref = opt.querySelector('.sp-ref');
  if (ref) ref.addEventListener('input', validatePayment);
});

function validatePayment() {
  const v = payMethod.value;
  let ok = v !== '' && num(payAmount.value) > 0;
  if (['bank','mobile','card'].includes(v)) ok = ok && document.getElementById('pay-ref').value.trim() !== '';
  if (v === 'split') {
    const checked = checkedOpts();
    const total = num(payAmount.value);
    const alloc = checked.reduce((s, o) => s + num(o.querySelector('.sp-amt').value), 0);
    ok = ok && checked.length > 0 && Math.abs(total - alloc) <= 0.01;
    checked.forEach(o => {
      if (o.dataset.method !== 'cash') {
        const r = o.querySelector('.sp-ref');
        if (r && !r.value.trim()) ok = false;
      }
    });
  }
  postBtn.disabled = !ok;
}

payMethod.addEventListener('change', syncMethodPanels);
payAmount.addEventListener('input', () => { if (payMethod.value === 'split') recomputeSplits(); else validatePayment(); });
document.getElementById('pay-ref').addEventListener('input', validatePayment);
postBtn.addEventListener('click', () => closeModal(document.getElementById('modal-record-payment')));

/* sidebar */
document.querySelectorAll('.sidebar-item').forEach(item=>{
  item.addEventListener('click',e=>{e.preventDefault();document.querySelectorAll('.sidebar-item').forEach(i=>i.classList.remove('active'));item.classList.add('active');});
});
</script>
</body>
</html>
```

---

## FINAL ENFORCEMENT STATEMENT FOR OPENCODE

This is not a suggestion. This is a strict implementation requirement.

You must implement the customer module so that when a user opens the customers index page or customer detail page, they see and can interact with **every element present in the mockups above**.

If any element is missing, the implementation is incomplete.

If any modal is missing, the implementation is incomplete.

If any payment method behavior is missing, the implementation is incomplete.

If split payment checkbox tiles are not premium, collapsible, expandable, validated, and fully wired to backend persistence, the implementation is incomplete.

If the credit utilisation gauge is missing or non-functional, the implementation is incomplete.

If the receivables ageing strip is missing or non-functional, the implementation is incomplete.

If the statement ledger tab is missing or non-functional, the implementation is incomplete.

If the design tokens, gradients, glass effects, shadows, animations, or centered hairlines are altered away from the mockup, the implementation is incomplete.

If any currency displays as USD or $ instead of K (MWK), the implementation is incomplete.

Do not stop until all pages, modals, components, routes, controllers, models, migrations, seeders, policies, services, validations, frontend interactions, and tests required by this module are fully delivered and integrated into the existing system.