# FULL IMPLEMENTATION PROMPT — COMPREHENSIVE VENDOR MODULE  
## CRITICAL INSTRUCTION TO OPENCODE: DO NOT SKIP ANY ELEMENT, DO NOT SIMPLIFY, DO NOT REPLACE, DO NOT SUMMARIZE, DO NOT LEAVE OUT ANY MODAL, TAB, FIELD, BUTTON, ANIMATION, INTERACTION, VALIDATION, OR BACKEND FUNCTIONALITY IMPLIED BY THIS MOCKUP.

You are acting as a senior Laravel/PHP enterprise architect, backend engineer, database designer, and premium UI/UX frontend developer.

reBuild the Vendor Modulein the accounting system, exactly matching the authoritative HTML mockup embedded below.

This mockup is the **source of truth** for:

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
- bill view behavior
- row-click behavior
- animations
- micro-interactions
- validation states
- all visible and implied functionality

You must redesign/implement **all necessary pages, modals, components, elements, workflows, validations, backend endpoints, database structures, policies, services, controllers, Blade views, JavaScript/Alpine behavior, tests, and seeders** required to make this module production-ready.

---

## ABSOLUTE NON-NEGOTIABLE REQUIREMENTS

1. **Do not skip any element from the mockup.**
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
12. **Do not omit the bill row click-to-open behavior.**
13. **Do not omit keyboard accessibility for clickable bill rows.**
14. **Do not omit the extra spacing above dynamic payment sections such as “Transaction Details”.**
15. **Do not invent a different visual direction.**
16. **If the implementation is too large for one response, split it into multiple file writes, but still complete every required element.**
17. **No placeholders like “add remaining fields here”, “implement modal similarly”, or “TODO” for any part of the mockup.**
18. **Every visible mockup element must map to a real implementation artifact: Blade component, partial, Alpine state, controller method, route, request validation, service logic, database column, policy ability, test case, or seeder record as appropriate.**

---

## TARGET STACK

Implement using modern enterprise Laravel practices:

- Laravel 11+ or latest stable
- PHP 8.3+
- Blade templating
- Alpine.js for lightweight UI interactivity unless Livewire is already the project standard
- Tailwind is allowed only if it reproduces the exact mockup visually; otherwise preserve the mockup’s custom CSS token system
- MySQL/PostgreSQL-compatible schema
- Form Request validation
- Policies/gates for authorization
- Service classes for business workflows
- Feature tests
- Database seeders
- Multi-tenant/company-safe design
- Audit-friendly timestamps and soft deletes where appropriate

If the existing project already uses Livewire, Inertia, or another stack, adapt while preserving the exact UI/UX and behavior from the mockup.

---

## MODULE SCOPE

Build a full **Vendor Management Module** including at minimum:

### Vendor Master Data

- vendor code
- display name
- legal name
- category
- currency
- tax ID / VAT number
- email
- phone
- website
- billing address
- remit-to address
- payment terms in days
- preferred payment method
- opening balance
- active/inactive status
- created by / updated by
- created at / updated at
- soft delete support
- tenant/company scoping

### Vendor Detail Page

Exactly as mocked:

- top navigation row 1
- module rail row 2
- compact icon sidebar
- breadcrumb
- page header
- vendor hero card
- centered gradient hairline on hero card
- vendor avatar with status dot
- vendor name row
- status pill
- vendor code chip
- vendor meta items
- delete button
- activate vendor button
- quick action chips:
  - New Bill
  - New Purchase Order
  - Record Payment
  - Send Message
  - Add Note
  - Add to Favourites
- financial KPI strip:
  - Open Balance highlighted card with sheen animation
  - Total Billed
  - Opening Balance
  - Payment Terms
- tabs card with centered gradient hairline
- tabs:
  - Overview
  - Bills
  - Payments
  - Credits
  - Ledger
  - Documents
  - Communication
  - Evaluation
- side panel cards:
  - Risk Assessment
  - Contact Details
  - Recent Documents

### Bills Tab

- toolbar search
- status filter button
- export button
- New Bill button
- bills table
- bill number link
- linked PO subtext
- bill date
- due date
- category
- amount
- status pill
- view button
- **entire row clickable to open Bill View modal**
- row hover/active/focus-visible styling
- keyboard support: Enter and Space open the bill
- pagination footer

### Payments Tab

- toolbar search
- method filter button
- Record Payment button
- payments table:
  - payment number
  - date
  - method
  - reference
  - applied to
  - amount
  - status

### Credits Tab

- empty state:
  - icon
  - title
  - subtitle
  - New Credit Note button

### Ledger Tab

- date range button
- export statement button
- closing balance summary
- ledger table:
  - date
  - entry
  - type
  - reference
  - debit
  - credit
  - balance
- totals row

### Documents Tab

- dropzone
- document cards:
  - file extension badge
  - file name
  - metadata
  - view/download actions

### Communication Tab

- filter button
- New Message button
- message thread cards:
  - inbound/outbound avatar
  - subject
  - meta
  - direction badge
  - body
  - reply/forward/attachment actions

### Evaluation Tab

- scorecard criteria rows
- weight badges
- interactive star ratings
- evaluator comments textarea
- save draft / submit evaluation buttons
- overall rating summary card

---

## MODALS TO IMPLEMENT EXACTLY

You must implement all modals shown in the mockup:

1. **Bill View Modal**
2. **Record Payment Modal**
3. **New Bill Modal**
4. **New Purchase Order Modal**
5. **Send Message Modal**
6. **Add Note Modal**
7. **Edit Vendor Modal**

Each modal must preserve:

- header gradient
- mint hairline
- icon badge
- title/subtitle
- close button rotation hover
- body scroll behavior
- footer gradient
- button styles
- animation on open/close
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
- Payment Amount

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

- Amount
- Cash Tendered
- Change Given

#### Bank Transfer split

- Amount
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

#### Card split

- Amount
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

#### Mobile Money split

- Amount
- Reference / Transaction No.
- Provider / Institution
- Account Name
- Account Holder Name

Split totals must show:

- Payment Amount
- Allocated Across Selected Methods
- Unallocated

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

## BILL VIEW MODAL — CRITICAL BEHAVIOR

The Bill View modal must open when:

- user clicks the eye button
- user clicks anywhere in the bill row
- user presses Enter or Space while the bill row is focused

The row must have:

- `class="clickable-row"`
- `tabindex="0"`
- data attributes:
  - `data-open-bill="1"`
  - `data-no`
  - `data-date`
  - `data-due`
  - `data-po`
  - `data-amt`
  - `data-status`

The modal must populate:

- bill number
- bill date
- due date
- linked PO
- amount
- amount chip
- status pill
- payment history state

If status is paid:

- show paid pill
- show paid payment history alert
- hide Record Payment button

If status is open:

- show open pill
- show no payments alert
- show Record Payment button

Clicking Record Payment inside Bill View must:

- close Bill View
- open Record Payment after a short delay

---

## NEW BILL MODAL

Must include:

- Bill Details section
- Vendor disabled field
- Bill Number
- Bill Date
- Due Date
- Currency
- Category
- Linked Purchase Order
- Line Items table
- Add Line Item button
- removable rows
- totals block:
  - Subtotal
  - VAT
  - Total Due
- Attachments dropzone
- Internal Notes
- Cancel / Save Draft / Save Bill buttons

---

## NEW PURCHASE ORDER MODAL

Must include:

- Order Details
- Vendor disabled field
- PO Number
- Order Date
- Expected Delivery
- Deliver To
- Priority
- Line Items
- Add Line Item
- totals
- Notes to Vendor
- Cancel / Save Draft / Create & Send PO

---

## SEND MESSAGE MODAL

Must include:

- warning alert when no email on file
- To field disabled if no email
- Channel
- Template
- Subject
- Message textarea with prefilled reminder example
- attachment dropzone
- Cancel / Send Message

---

## ADD NOTE MODAL

Must include:

- Note Type
- Reminder Date
- Title
- Note
- Pin to vendor profile switch
- Cancel / Save Note

---

## EDIT VENDOR MODAL

Must include sections:

### General

- Display Name
- Legal Name
- Vendor Code disabled
- Category
- Currency
- Tax ID / VAT No.

### Contact

- Email
- Phone
- Website

### Addresses

- Billing Address
- Remit-To Address

### Payment & Status

- Payment Terms Days
- Preferred Payment Method
- Opening Balance
- Vendor is Active switch

Footer:

- Delete Vendor danger ghost button aligned left
- Cancel
- Save Changes

---

## DESIGN SYSTEM REQUIREMENTS

Preserve the exact token system from the mockup:

```css
--deep-1:#17565D;
--deep-2:#0C3539;
--deep-3:#0A2E32;
--row1:#11454B;
--row2:#DCE6E7;
--sec:#107C7B;
--sec-2:#0C7E7D;
--ink:#0B2A2D;
--ink-soft:#52696B;
--ink-muted:#5F7A7C;
--mist-a:#F4F8F8;
--mist-b:#E8F0F0;
--line:#E2ECEC;
--border:#DCEAEA;
--card:rgba(255,255,255,.75);
--green:#15803D;
--warn:#D97706;
--red:#DC2626;
--red-2:#B91C1C;
--tint:#DFF7F6;
--mint:#7FD1C0;
--teal-btn-shadow:0 1px 2px rgba(4,51,47,.25),0 10px 22px -8px rgba(18,143,142,.55),inset 0 1px 0 rgba(255,255,255,.25);
--cta-btn-shadow:0 1px 2px rgba(6,32,35,.3),0 10px 20px -10px rgba(12,53,57,.6),inset 0 1px 0 rgba(255,255,255,.12);
```

Required visual signatures:

- deep teal gradient topnav
- mint radial glow in topnav
- module rail with active deep gradient pill
- icon sidebar with active tint and left gradient indicator
- glass cards with backdrop blur
- centered gradient hairlines on:
  - vendor hero
  - tabs card
- highlighted Open Balance KPI card with:
  - teal/deep gradient
  - animated sheen
  - mint radial bloom
- striped mist tables
- tint row hover
- status pills:
  - green paid/cleared
  - amber open
  - red inactive/danger
- modal headers with deep gradient and mint bottom hairline
- premium split checkbox tiles with animated checked state
- sufficient spacing above dynamic payment sections

---

## BACKEND IMPLEMENTATION REQUIREMENTS

Create a production-ready Laravel module structure.

Suggested namespace:

```php
App\Modules\Vendors
```

or project-standard equivalent.

### Routes

Define vendor module routes such as:

```php
Route::middleware(['auth', 'tenant'])->prefix('vendors')->name('vendors.')->group(function () {
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::get('/create', [VendorController::class, 'create'])->name('create');
    Route::store(...);
    Route::show(...);
    Route::edit(...);
    Route::update(...);
    Route::delete(...);
    Route::activate(...);
    Route::deactivate(...);

    Route::post('/{vendor}/bills', ...)->name('bills.store');
    Route::get('/bills/{bill}', ...)->name('bills.show');
    Route::post('/{vendor}/payments', ...)->name('payments.store');
    Route::post('/{vendor}/purchase-orders', ...)->name('purchase-orders.store');
    Route::post('/{vendor}/messages', ...)->name('messages.store');
    Route::post('/{vendor}/notes', ...)->name('notes.store');
    Route::post('/{vendor}/evaluations', ...)->name('evaluations.store');
    Route::post('/{vendor}/documents', ...)->name('documents.store');
});
```

Adjust to project conventions but cover all actions implied by the UI.

### Controllers

Create controllers/services for:

- VendorController
- VendorBillController
- VendorPaymentController
- VendorPurchaseOrderController
- VendorMessageController
- VendorNoteController
- VendorEvaluationController
- VendorDocumentController

Keep controllers thin; move business logic into services.

### Services

Create services such as:

- VendorService
- VendorActivationService
- BillService
- PaymentRecordingService
- SplitPaymentValidator
- PurchaseOrderService
- VendorCommunicationService
- VendorNoteService
- VendorEvaluationService
- VendorDocumentService
- VendorLedgerService

### Form Requests

Create validated requests for:

- StoreVendorRequest
- UpdateVendorRequest
- StoreBillRequest
- StorePaymentRequest
- StorePurchaseOrderRequest
- SendMessageRequest
- StoreNoteRequest
- StoreEvaluationRequest
- StoreDocumentRequest

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

Create VendorPolicy with abilities:

- viewAny
- view
- create
- update
- delete
- restore
- recordPayment
- createBill
- createPurchaseOrder
- sendMessage
- addNote
- evaluate
- uploadDocument
- activate
- deactivate

Enforce tenant/company scoping.

### Database Schema

Create migrations for at least:

#### vendors

- id
- tenant_id / company_id
- vendor_code unique per tenant
- display_name
- legal_name
- category_id nullable
- currency_id nullable
- tax_id nullable
- email nullable
- phone nullable
- website nullable
- billing_address text nullable
- remit_to_address text nullable
- payment_terms_days unsignedInteger default 0
- preferred_payment_method string nullable
- opening_balance decimal(18,2) default 0
- is_active boolean default false
- created_by nullable
- updated_by nullable
- timestamps
- softDeletes

#### vendor_categories

- id
- tenant_id
- name
- slug/code
- timestamps

#### currencies

- id
- code
- name
- symbol
- decimal_precision
- timestamps

#### bills

- id
- tenant_id
- vendor_id
- bill_number
- bill_date
- due_date
- category_id nullable
- linked_po_number nullable
- subtotal decimal
- tax_amount decimal
- total_amount decimal
- amount_paid decimal default 0
- status enum draft/open/paid/overdue/void
- created_by
- timestamps
- softDeletes

#### bill_items

- id
- bill_id
- description
- quantity decimal
- unit_price decimal
- line_total decimal
- tax_amount decimal nullable
- timestamps

#### payments

- id
- tenant_id
- vendor_id
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
- bill_id
- allocated_amount decimal
- timestamps

#### purchase_orders

- id
- tenant_id
- vendor_id
- po_number
- order_date
- expected_delivery_date nullable
- deliver_to string nullable
- priority string nullable
- subtotal decimal
- tax_amount decimal
- total_amount decimal
- status enum draft/sent/partially_received/received/closed/void
- notes text nullable
- created_by
- timestamps
- softDeletes

#### purchase_order_items

- id
- purchase_order_id
- description
- quantity
- unit_price
- line_total
- timestamps

#### vendor_documents

- id
- tenant_id
- vendor_id
- name
- path
- mime_type
- size
- uploaded_by
- timestamps
- softDeletes

#### vendor_messages

- id
- tenant_id
- vendor_id
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

#### vendor_notes

- id
- tenant_id
- vendor_id
- type enum internal/reminder/follow_up
- title
- body text
- reminder_date nullable
- pinned boolean default false
- created_by
- timestamps
- softDeletes

#### vendor_evaluations

- id
- tenant_id
- vendor_id
- period string
- quality_score tinyint nullable
- delivery_score tinyint nullable
- price_score tinyint nullable
- communication_score tinyint nullable
- compliance_score tinyint nullable
- weighted_score decimal nullable
- comments text nullable
- status enum draft/submitted
- evaluated_by
- timestamps
- softDeletes

#### vendor_ledger_entries or integrate with accounting ledger

If the system already has a general ledger, create polymorphic source references:

- source_type = vendor_bill / vendor_payment / vendor_credit / vendor_journal
- source_id
- vendor_id
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
resources/views/vendors/show.blade.php
resources/views/vendors/partials/topnav.blade.php
resources/views/vendors/partials/module-rail.blade.php
resources/views/vendors/partials/sidebar.blade.php
resources/views/vendors/partials/breadcrumb.blade.php
resources/views/vendors/partials/page-header.blade.php
resources/views/vendors/partials/vendor-hero.blade.php
resources/views/vendors/partials/financial-strip.blade.php
resources/views/vendors/partials/tabs.blade.php
resources/views/vendors/tabs/overview.blade.php
resources/views/vendors/tabs/bills.blade.php
resources/views/vendors/tabs/payments.blade.php
resources/views/vendors/tabs/credits.blade.php
resources/views/vendors/tabs/ledger.blade.php
resources/views/vendors/tabs/documents.blade.php
resources/views/vendors/tabs/communication.blade.php
resources/views/vendors/tabs/evaluation.blade.php
resources/views/vendors/partials/side-panel.blade.php
resources/views/vendors/modals/bill-view.blade.php
resources/views/vendors/modals/record-payment.blade.php
resources/views/vendors/modals/new-bill.blade.php
resources/views/vendors/modals/new-purchase-order.blade.php
resources/views/vendors/modals/send-message.blade.php
resources/views/vendors/modals/add-note.blade.php
resources/views/vendors/modals/edit-vendor.blade.php
```

Create CSS asset:

```text
resources/css/vendor-module.css
```

or compile into project stylesheet. The CSS must include the full token system and all component styles from the mockup.

Create JS/Alpine components for:

- tabs
- sidebar active state
- modal manager
- bill row click/open
- bill view population
- record payment method switching
- split payment tiles
- split totals/validation
- line item repeaters
- star rating interaction

If using Alpine, structure state cleanly:

```js
vendorDetailPage()
billViewModal()
recordPaymentModal()
splitPaymentState()
lineItemsRepeater()
starRating()
```

If using Livewire, preserve the same visual behavior and validation feedback.

---

## ACCEPTANCE CHECKLIST

Before considering the task complete, verify every item:

### Layout

- [ ] Topnav row 1 exists with brand, app name, org, center pills/search, notifications, help, user chip.
- [ ] Module rail row 2 exists with Purchasing active.
- [ ] Icon sidebar exists with Vendors active.
- [ ] Breadcrumb exists.
- [ ] Page header exists with title, subtitle, inline inactive badge, print/export/more/edit buttons.
- [ ] Vendor hero exists.
- [ ] Centered gradient hairline exists on vendor hero.
- [ ] Centered gradient hairline exists on tabs card.
- [ ] Financial strip exists with highlighted Open Balance card.
- [ ] Tabs card exists with all 8 tabs.
- [ ] Side panel exists with Risk, Contact, Recent Documents.

### Tabs

- [ ] Overview tab renders contact grid, addresses, recent activity timeline.
- [ ] Bills tab renders toolbar, table, pagination.
- [ ] Payments tab renders toolbar, table, pagination.
- [ ] Credits tab renders empty state.
- [ ] Ledger tab renders toolbar, table, totals row.
- [ ] Documents tab renders dropzone and 3 document cards.
- [ ] Communication tab renders filter, new message button, 2 message cards.
- [ ] Evaluation tab renders criteria, stars, comments, summary card.

### Bills Interaction

- [ ] Eye button opens Bill View.
- [ ] Entire row opens Bill View.
- [ ] Row is focusable.
- [ ] Enter opens Bill View.
- [ ] Space opens Bill View.
- [ ] Row has pointer cursor.
- [ ] Row hover/active/focus-visible styles exist.

### Bill View Modal

- [ ] Opens with correct bill data.
- [ ] Shows status pill.
- [ ] Shows amount chip.
- [ ] Shows bill date, due date, PO, currency.
- [ ] Shows line items.
- [ ] Shows totals.
- [ ] Shows payment history state based on paid/open.
- [ ] Record Payment button appears only for open bills.
- [ ] Record Payment closes Bill View and opens Record Payment.

### Record Payment Modal

- [ ] Modal width is `.modal.mid` / 780px.
- [ ] Payment Date exists.
- [ ] Payment Method select exists.
- [ ] Paying Bank Account exists.
- [ ] Payment Amount exists.
- [ ] Selecting Bank Transfer shows Transaction Details.
- [ ] Selecting Mobile Money shows Transaction Details.
- [ ] Selecting Card shows Transaction Details.
- [ ] Transaction Details has enough top spacing.
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
- [ ] Totals show Payment Amount, Allocated, Unallocated.
- [ ] Banners show info/success/warning states.
- [ ] Record Payment button disabled until valid.
- [ ] Split validation enforces ±0.01.
- [ ] Electronic split validation enforces reference.

### Other Modals

- [ ] New Bill modal fully implemented.
- [ ] New Purchase Order modal fully implemented.
- [ ] Send Message modal fully implemented.
- [ ] Add Note modal fully implemented.
- [ ] Edit Vendor modal fully implemented.
- [ ] All modals support Escape close.
- [ ] All modals support backdrop close.
- [ ] All modals lock body scroll.
- [ ] All modal animations preserved.

### Backend

- [ ] Vendor model and relationships exist.
- [ ] Migrations exist for all vendor-related tables.
- [ ] Seeders create the mocked vendor, bills, payments, documents, messages, ledger entries.
- [ ] Routes exist for all actions.
- [ ] Controller actions exist.
- [ ] Form Requests validate all fields.
- [ ] Policies authorize all actions.
- [ ] Services handle business workflows.
- [ ] Payment recording persists split lines and allocations.
- [ ] Bill view data is loaded server-side or via endpoint.
- [ ] Tests cover vendor show, payment validation, split validation, bill row data, modal endpoints as applicable.

---

## RESPONSE FORMAT FOR OPENCODE

Produce the implementation as a complete set of file writes/patches.

At minimum include:

1. Migration files
2. Models
3. Relationships
4. Policies
5. Form Requests
6. Services
7. Controllers
8. Routes
9. Blade views/components
10. CSS
11. JavaScript/Alpine components
12. Seeders
13. Feature tests
14. Any language files/validation attributes needed

If you cannot output everything in one message, continue across multiple messages until all files are delivered. Do not stop after only the frontend. Do not stop after only the backend. Complete the full module.

---

## AUTHORITATIVE HTML MOCKUP — USE THIS EXACTLY

The following HTML mockup is the definitive reference. Implement every visible element, style, interaction, modal, tab, field, button, validation state, animation, and workflow shown here.

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendor Detail – Gracian Seyama | CamelotBooks ERP</title>
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
  @keyframes panelIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  @keyframes overlayIn{from{opacity:0}to{opacity:1}}
  @keyframes overlayOut{from{opacity:1}to{opacity:0}}
  @keyframes modalIn{from{opacity:0;transform:translateY(24px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
  @keyframes modalOut{from{opacity:1}to{opacity:0;transform:translateY(12px) scale(.97)}}
  @keyframes sheen{0%{transform:translateX(-130%) skewX(-12deg)}55%,100%{transform:translateX(320%) skewX(-12deg)}}
  @keyframes pulseMint{0%{box-shadow:0 0 0 0 rgba(127,209,192,.6)}70%{box-shadow:0 0 0 7px rgba(127,209,192,0)}100%{box-shadow:0 0 0 0 rgba(127,209,192,0)}}
  @keyframes pulseRed{0%{box-shadow:0 0 0 0 rgba(220,38,38,.45)}70%{box-shadow:0 0 0 6px rgba(220,38,38,0)}100%{box-shadow:0 0 0 0 rgba(220,38,38,0)}}
  @keyframes expandIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
  .anim-1{animation:fadeUp .4s ease both;animation-delay:.05s}
  .anim-2{animation:fadeUp .4s ease both;animation-delay:.12s}
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
  .status-badge-inline{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;background:var(--red-tint);border:1px solid rgba(220,38,38,.22);border-radius:20px;font-size:11px;font-weight:700;color:var(--red-2)}
  .status-badge-inline .dot{width:6px;height:6px;background:var(--red);border-radius:50%}
  .page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}

  /* buttons */
  .btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;transition:.2s;border:none;text-decoration:none;font-family:'Inter',sans-serif;white-space:nowrap}
  .btn:active{transform:scale(.97)}
  .btn:disabled{opacity:.45;cursor:not-allowed;transform:none !important;filter:none !important}
  .btn i{font-size:12px}
  .btn-sm{padding:7px 14px;font-size:12px}
  .btn-ghost{background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-soft)}
  .btn-ghost:hover:not(:disabled){border-color:var(--mint);color:var(--deep-2);background:#fff;transform:translateY(-1px)}
  .btn-primary{background:linear-gradient(180deg,#14918F 0%,var(--sec) 45%,var(--sec-2) 100%);color:#fff;box-shadow:var(--teal-btn-shadow)}
  .btn-primary:hover:not(:disabled){filter:brightness(1.07);transform:translateY(-1px)}
  .btn-deep{background:linear-gradient(180deg,var(--deep-1) 0%,var(--deep-2) 55%,var(--deep-3) 100%);color:#fff;box-shadow:var(--cta-btn-shadow)}
  .btn-deep:hover:not(:disabled){filter:brightness(1.12);transform:translateY(-1px)}
  .btn-danger-ghost{background:var(--red-tint);border:1px solid rgba(220,38,38,.25);color:var(--red-2)}
  .btn-danger-ghost:hover{background:rgba(220,38,38,.16)}
  .btn-icon{width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:10px;background:rgba(255,255,255,.85);border:1px solid var(--border);color:var(--ink-muted);cursor:pointer;transition:.2s;font-size:14px}
  .btn-icon:hover{border-color:var(--mint);color:var(--sec);background:#fff;transform:translateY(-1px)}

  /* hero */
  .vendor-hero{border-radius:var(--r-xl);border:1px solid var(--border);padding:28px 32px;margin-bottom:20px;box-shadow:var(--shadow-card);position:relative;overflow:hidden}
  .vendor-hero::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:62%;height:2px;border-radius:0 0 6px 6px;background:linear-gradient(90deg,transparent 0%,var(--mint) 22%,var(--sec) 50%,var(--mint) 78%,transparent 100%);box-shadow:0 2px 10px rgba(16,124,123,.35);pointer-events:none}
  .vendor-hero-inner{display:flex;align-items:center;gap:24px}
  .vendor-avatar-wrap{position:relative;flex-shrink:0}
  .vendor-avatar{width:72px;height:72px;background:linear-gradient(145deg,var(--tint),var(--mint));border:1px solid rgba(127,209,192,.55);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:var(--deep-2);box-shadow:inset 0 1px 0 rgba(255,255,255,.7),0 6px 18px -6px rgba(16,124,123,.45)}
  .vendor-avatar-status{position:absolute;bottom:-3px;right:-3px;width:20px;height:20px;background:var(--red);border:3px solid #fff;border-radius:50%;animation:pulseRed 2.6s infinite}
  .vendor-info{flex:1}
  .vendor-name-row{display:flex;align-items:center;gap:12px;margin-bottom:6px;flex-wrap:wrap}
  .vendor-name{font-size:22px;font-weight:800;letter-spacing:-.4px}
  .vendor-code{font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:500;padding:3px 10px;background:var(--mist-b);border:1px solid var(--border);border-radius:6px;color:var(--ink-soft);letter-spacing:.5px}
  .status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:700}
  .status-inactive{background:var(--red-tint);border:1px solid rgba(220,38,38,.22);color:var(--red-2)}
  .status-inactive .sdot{width:6px;height:6px;background:var(--red);border-radius:50%}
  .vendor-meta{display:flex;align-items:center;gap:16px;font-size:12.5px;color:var(--ink-soft);font-weight:500;flex-wrap:wrap}
  .vendor-meta-item{display:flex;align-items:center;gap:5px}
  .vendor-meta-item i{font-size:11px;color:var(--sec);opacity:.8}
  .vendor-hero-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
  .vendor-quick-actions{display:flex;align-items:center;gap:8px;margin-top:20px;padding-top:20px;border-top:1px solid var(--line);flex-wrap:wrap}
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

  /* tabs */
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
  .tab-panel.active .data-table tbody tr{animation:fadeUp .35s ease both}
  .tab-panel.active .data-table tbody tr:nth-child(1){animation-delay:.04s}
  .tab-panel.active .data-table tbody tr:nth-child(2){animation-delay:.09s}
  .tab-panel.active .data-table tbody tr:nth-child(3){animation-delay:.14s}
  .tab-panel.active .data-table tbody tr:nth-child(4){animation-delay:.19s}
  .tab-panel.active .data-table tbody tr:nth-child(5){animation-delay:.24s}
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
  .td-sub{font-size:11px;color:var(--ink-muted);margin-top:2px;font-weight:500}
  .row-link{color:var(--deep-2);font-weight:700;text-decoration:none}
  .row-link:hover{color:var(--sec);text-decoration:underline}
  .row-icon-btn{width:28px;height:28px;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);cursor:pointer;font-size:11px;transition:.15s}
  .row-icon-btn:hover{background:linear-gradient(180deg,#14918F,var(--sec-2));border-color:transparent;color:#fff;transform:scale(1.1);box-shadow:var(--teal-btn-shadow)}
  .ledger-total td{background:linear-gradient(180deg,var(--mist-a),var(--mist-b)) !important;font-weight:800;color:var(--deep-2);border-top:1.5px solid var(--border)}
  .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:12px;color:var(--ink-soft);background:rgba(255,255,255,.6)}
  .pager{display:flex;gap:4px}
  .pager-btn{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:1px solid var(--border);background:#fff;color:var(--ink-muted);font-size:11px;cursor:pointer;transition:.15s}
  .pager-btn.active{background:linear-gradient(180deg,var(--deep-1),var(--deep-2));color:#fff;border-color:transparent}
  .pager-btn:hover:not(.active){background:var(--tint)}
  .pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:20px;font-size:10.5px;font-weight:700;white-space:nowrap}
  .pill .pdot{width:6px;height:6px;border-radius:50%}
  .pill-paid{background:var(--green-tint);color:var(--green);border:1px solid rgba(21,128,61,.25)}
  .pill-paid .pdot{background:var(--green)}
  .pill-open{background:var(--warn-tint);color:var(--warn);border:1px solid rgba(217,119,6,.3)}
  .pill-open .pdot{background:var(--warn)}
  .pill-draft{background:var(--mist-b);color:var(--ink-soft);border:1px solid var(--border)}
  .pill-draft .pdot{background:#9DB4B4}
  .empty-state{text-align:center;padding:56px 20px;animation:fadeIn .4s ease both}
  .empty-icon{width:64px;height:64px;margin:0 auto 14px;border-radius:16px;background:var(--tint);display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--sec)}
  .empty-title{font-size:15px;font-weight:700;margin-bottom:6px}
  .empty-sub{font-size:12.5px;color:var(--ink-soft);margin-bottom:18px}

  /* docs / comms / eval */
  .dropzone{border:2px dashed var(--border);border-radius:12px;padding:26px;text-align:center;color:var(--ink-soft);background:rgba(255,255,255,.6);margin-bottom:16px;cursor:pointer;transition:.2s}
  .dropzone:hover{border-color:var(--sec);background:var(--tint)}
  .dropzone i{font-size:22px;color:var(--sec);margin-bottom:8px;display:block}
  .dropzone-title{font-size:13px;font-weight:700;color:var(--ink);margin-bottom:3px}
  .dropzone-sub{font-size:11.5px}
  .doc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
  .doc-card{border:1px solid var(--border);border-radius:12px;padding:16px;background:rgba(255,255,255,.85);transition:.2s;animation:fadeUp .4s ease both;box-shadow:var(--shadow-card)}
  .doc-card:nth-child(2){animation-delay:.08s}.doc-card:nth-child(3){animation-delay:.16s}
  .doc-card:hover{box-shadow:var(--shadow-card-hov);transform:translateY(-3px)}
  .doc-card-top{display:flex;align-items:center;gap:10px;margin-bottom:12px}
  .doc-ext{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0}
  .ext-pdf{background:var(--red-tint);color:var(--red-2)}
  .ext-xls{background:var(--green-tint);color:var(--green)}
  .doc-name{font-size:12.5px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .doc-meta{font-size:11px;color:var(--ink-muted);margin-top:2px}
  .doc-card-actions{display:flex;gap:6px}
  .doc-action-btn{flex:1;padding:6px;border-radius:8px;border:1px solid var(--border);background:#fff;color:var(--ink-soft);font-size:11px;font-weight:600;cursor:pointer;transition:.15s}
  .doc-action-btn:hover{background:var(--tint);color:var(--sec)}
  .thread{display:flex;flex-direction:column;gap:12px}
  .msg-card{border:1px solid var(--border);border-radius:12px;background:rgba(255,255,255,.85);overflow:hidden;transition:.2s;animation:fadeUp .4s ease both;box-shadow:var(--shadow-card)}
  .msg-card:nth-child(2){animation-delay:.1s}
  .msg-card:hover{box-shadow:var(--shadow-card-hov)}
  .msg-head{display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--line)}
  .msg-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
  .msg-avatar.in{background:linear-gradient(135deg,var(--tint),var(--mint));color:var(--deep-2)}
  .msg-avatar.out{background:linear-gradient(135deg,var(--deep-1),var(--deep-2));color:#fff}
  .msg-subject{font-size:13px;font-weight:700}
  .msg-meta{font-size:11px;color:var(--ink-muted);margin-top:1px}
  .msg-dir{margin-left:auto;font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;flex-shrink:0}
  .dir-in{background:var(--mist-b);color:var(--ink-soft)}
  .dir-out{background:var(--tint);color:var(--sec)}
  .msg-body{padding:14px 18px;font-size:12.5px;color:var(--ink-soft);line-height:1.65}
  .msg-foot{padding:10px 18px;border-top:1px solid var(--line);display:flex;gap:14px}
  .msg-link{font-size:11.5px;font-weight:700;color:var(--sec);cursor:pointer;background:none;border:none;font-family:inherit}
  .msg-link:hover{text-decoration:underline}
  .eval-grid{display:grid;grid-template-columns:1fr 260px;gap:20px;align-items:start}
  .criteria-row{display:grid;grid-template-columns:1.5fr 1fr auto;gap:12px;align-items:center;padding:13px 0;border-bottom:1px solid var(--line)}
  .criteria-row:last-of-type{border-bottom:none}
  .criteria-name{font-size:13px;font-weight:600}
  .criteria-hint{font-size:11px;color:var(--ink-muted);margin-top:2px}
  .criteria-weight{font-size:11px;font-weight:700;color:var(--ink-soft);background:var(--mist-b);padding:3px 10px;border-radius:12px;justify-self:start}
  .stars-input{display:flex;gap:4px}
  .stars-input i{font-size:16px;color:var(--border);cursor:pointer;transition:.12s}
  .stars-input i.on{color:var(--warn)}
  .stars-input i:hover{transform:scale(1.2)}
  .eval-summary{background:var(--tint);border:1px solid rgba(16,124,123,.25);border-radius:12px;padding:20px;text-align:center}
  .eval-summary-score{font-size:34px;font-weight:800;color:var(--deep-2);line-height:1}
  .eval-summary-label{font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-soft);margin-top:6px}
  .eval-history{margin-top:10px;font-size:11.5px;color:var(--ink-soft)}

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
  .risk-meter{margin-bottom:14px}
  .risk-meter-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
  .risk-label{font-size:12px;font-weight:600}
  .risk-value{font-size:12px;font-weight:700}
  .risk-value.low{color:var(--green)}
  .risk-value.med{color:var(--warn)}
  .risk-track{height:6px;background:var(--mist-b);border-radius:3px;overflow:hidden}
  .risk-fill{height:100%;border-radius:3px}
  .risk-fill.low{background:linear-gradient(90deg,var(--green),#22A34F)}
  .risk-fill.med{background:linear-gradient(90deg,var(--warn),#F59E0B)}
  .contact-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--line)}
  .contact-item:last-child{border-bottom:none;padding-bottom:0}
  .contact-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
  .contact-icon.email{background:var(--mist-b);color:var(--ink-soft)}
  .contact-icon.phone{background:var(--tint);color:var(--sec)}
  .contact-icon.web{background:var(--warn-tint);color:var(--warn)}
  .contact-type{font-size:10px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:2px}
  .contact-val{font-size:12.5px;font-weight:500}
  .contact-val.empty{color:#9DB4B4;font-style:italic}
  .mini-doc{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)}
  .mini-doc:last-child{border-bottom:none;padding-bottom:0}
  .mini-doc-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;flex-shrink:0}
  .mini-doc-name{font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mini-doc-meta{font-size:10.5px;color:var(--ink-muted)}

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
  .bv-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:18px 0}
  .bv-meta-cell{background:var(--mist-a);border:1px solid var(--border);border-radius:10px;padding:10px 14px}
  .bv-meta-label{font-size:9.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px}
  .bv-meta-val{font-size:12.5px;font-weight:700}
  .bv-chips{display:flex;gap:8px;flex-wrap:wrap}
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

  ::-webkit-scrollbar{width:5px;height:5px}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
  ::-webkit-scrollbar-thumb:hover{background:var(--sec)}
  [data-tip]{position:relative}
  [data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:var(--deep-2);color:#fff;font-size:11px;font-weight:500;padding:4px 9px;border-radius:6px;white-space:nowrap;pointer-events:none;z-index:200;animation:fadeIn .15s ease both;box-shadow:var(--cta-btn-shadow)}
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
    <div class="topnav-search"><i class="fa-solid fa-magnifying-glass"></i> Search vendors, bills… <kbd>Ctrl K</kbd></div>
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

  <div class="breadcrumb anim-1">
    <a href="#">Vendor Centre</a><i class="fa-solid fa-chevron-right sep"></i>
    <a href="#">Vendors</a><i class="fa-solid fa-chevron-right sep"></i>
    <span class="current">Gracian Seyama</span>
  </div>

  <div class="page-header anim-1">
    <div>
      <h1 class="page-title">Vendor Detail — Gracian Seyama</h1>
      <div class="page-subtitle">
        <span>Since Aug 2026</span><span class="dot"></span>
        <span class="status-badge-inline"><span class="dot"></span> Inactive</span><span class="dot"></span>
        <span>Last updated 2 hours ago</span>
      </div>
    </div>
    <div class="page-header-actions">
      <button class="btn-icon" data-tip="Print"><i class="fa-solid fa-print"></i></button>
      <button class="btn-icon" data-tip="Export PDF"><i class="fa-solid fa-file-export"></i></button>
      <button class="btn btn-ghost"><i class="fa-solid fa-ellipsis-vertical"></i> More</button>
      <button class="btn btn-deep" data-open-modal="modal-edit-vendor"><i class="fa-solid fa-pen"></i> Edit Vendor</button>
    </div>
  </div>

  <div class="vendor-hero glass anim-2">
    <div class="vendor-hero-inner">
      <div class="vendor-avatar-wrap">
        <div class="vendor-avatar">GS</div>
        <div class="vendor-avatar-status"></div>
      </div>
      <div class="vendor-info">
        <div class="vendor-name-row">
          <span class="vendor-name">Gracian Seyama</span>
          <span class="status-pill status-inactive"><span class="sdot"></span> Inactive</span>
          <span class="vendor-code">VEN-00002</span>
        </div>
        <div class="vendor-meta">
          <span class="vendor-meta-item"><i class="fa-solid fa-calendar"></i> Created Aug 14, 2026</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-tag"></i> Office Supplies</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-coins"></i> Kwacha (K)</span>
          <span class="vendor-meta-item"><i class="fa-solid fa-clock"></i> Net 15 · Due on receipt</span>
        </div>
      </div>
      <div class="vendor-hero-actions">
        <button class="btn btn-danger-ghost"><i class="fa-solid fa-trash-can"></i> Delete</button>
        <button class="btn btn-primary"><i class="fa-solid fa-bolt"></i> Activate Vendor</button>
      </div>
    </div>
    <div class="vendor-quick-actions">
      <a class="quick-chip green" data-open-modal="modal-new-bill"><i class="fa-solid fa-plus"></i> New Bill</a>
      <a class="quick-chip" data-open-modal="modal-new-po"><i class="fa-solid fa-file-lines"></i> New Purchase Order</a>
      <a class="quick-chip" data-open-modal="modal-record-payment"><i class="fa-solid fa-credit-card"></i> Record Payment</a>
      <a class="quick-chip" data-open-modal="modal-send-message"><i class="fa-solid fa-envelope"></i> Send Message</a>
      <a class="quick-chip" data-open-modal="modal-add-note"><i class="fa-solid fa-note-sticky"></i> Add Note</a>
      <a class="quick-chip" href="#"><i class="fa-solid fa-star"></i> Add to Favourites</a>
    </div>
  </div>

  <div class="fin-strip">
    <div class="fin-card highlight">
      <div class="fin-card-top"><span class="fin-label">Open Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-wallet"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>1,875.50</div>
      <div class="fin-sub">1 open bill · due Sep 30, 2026</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Total Billed</span><div class="fin-icon-wrap"><i class="fa-solid fa-receipt"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>10,325.50</div>
      <div class="fin-sub">3 bills issued to date</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Opening Balance</span><div class="fin-icon-wrap"><i class="fa-solid fa-scale-balanced"></i></div></div>
      <div class="fin-value"><span class="currency">K</span>0.00</div>
      <div class="fin-sub">No opening balance set</div>
    </div>
    <div class="fin-card glass">
      <div class="fin-card-top"><span class="fin-label">Payment Terms</span><div class="fin-icon-wrap"><i class="fa-solid fa-clock-rotate-left"></i></div></div>
      <div class="fin-value terms">Net 15</div>
      <div class="fin-sub">Due on receipt of invoice</div>
    </div>
  </div>

  <div class="content-grid anim-4">
    <div class="tabs-card glass">
      <div class="tabs-nav">
        <button class="tab-btn active" data-tab="overview">Overview</button>
        <button class="tab-btn" data-tab="bills">Bills <span class="tab-count">3</span></button>
        <button class="tab-btn" data-tab="payments">Payments <span class="tab-count">2</span></button>
        <button class="tab-btn" data-tab="credits">Credits <span class="tab-count">0</span></button>
        <button class="tab-btn" data-tab="ledger">Ledger</button>
        <button class="tab-btn" data-tab="documents">Documents <span class="tab-count">3</span></button>
        <button class="tab-btn" data-tab="communication">Communication <span class="tab-count">2</span></button>
        <button class="tab-btn" data-tab="evaluation">Evaluation</button>
      </div>

      <div class="tab-body">

        <div class="tab-panel active" id="panel-overview">
          <div class="overview-section-title">Contact Information</div>
          <div class="info-grid">
            <div class="info-cell"><div class="info-cell-label">Display Name</div><div class="info-cell-value">Yeah</div></div>
            <div class="info-cell"><div class="info-cell-label">Email Address</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Phone Number</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Currency</div><div class="info-cell-value mono">K — Kwacha</div></div>
            <div class="info-cell"><div class="info-cell-label">Payment Terms (Days)</div><div class="info-cell-value mono">15</div></div>
            <div class="info-cell"><div class="info-cell-label">Opening Balance</div><div class="info-cell-value mono">K 0.00</div></div>
            <div class="info-cell"><div class="info-cell-label">Tax ID / VAT Number</div><div class="info-cell-value muted">Not provided</div></div>
            <div class="info-cell"><div class="info-cell-label">Vendor Category</div><div class="info-cell-value">Office Supplies</div></div>
            <div class="info-cell"><div class="info-cell-label">Internal Ref.</div><div class="info-cell-value mono">VEN-00002</div></div>
          </div>
          <div class="overview-section-title">Addresses</div>
          <div class="addresses-row">
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-file-invoice"></i> Billing Address</div><div class="address-block-value empty">No billing address on file. Click Edit to add one.</div></div>
            <div class="address-block"><div class="address-block-label"><i class="fa-solid fa-money-bill-transfer"></i> Remit-To Address</div><div class="address-block-value empty">No remit-to address on file. Click Edit to add one.</div></div>
          </div>
          <div class="overview-section-title">Recent Activity</div>
          <div class="timeline">
            <div class="timeline-item"><div class="timeline-dot blue"></div><div class="timeline-title">Payment PAY-2026-0057 recorded</div><div class="timeline-desc">Cheque #00127 for K 4,250.00 applied to BILL-2026-0002.</div><div class="timeline-time">Sep 18, 2026 · 02:14 PM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Bill BILL-2026-0003 created</div><div class="timeline-desc">Invoice for K 1,875.50 received — due Sep 30, 2026.</div><div class="timeline-time">Sep 15, 2026 · 10:02 AM</div></div>
            <div class="timeline-item"><div class="timeline-dot"></div><div class="timeline-title">Vendor record created</div><div class="timeline-desc">Gracian Seyama was added to the vendor directory by Elvis Seyama.</div><div class="timeline-time">Aug 14, 2026 · 09:32 AM</div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-bills">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search bills…"></div>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Status: All</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export</button>
            </div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-new-bill"><i class="fa-solid fa-plus"></i> New Bill</button>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Bill No.</th><th>Bill Date</th><th>Due Date</th><th>Category</th><th class="td-right">Amount</th><th>Status</th><th></th></tr></thead>
                <tbody>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0001" data-date="Aug 20, 2026" data-due="Sep 04, 2026" data-po="PO-2026-0004" data-amt="4,200.00" data-status="paid">
                    <td><a class="row-link" href="#">BILL-2026-0001</a><div class="td-sub">PO-2026-0004</div></td>
                    <td>Aug 20, 2026</td><td>Sep 04, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">4,200.00</td>
                    <td><span class="pill pill-paid"><span class="pdot"></span> Paid</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0002" data-date="Sep 05, 2026" data-due="Sep 20, 2026" data-po="PO-2026-0007" data-amt="4,250.00" data-status="paid">
                    <td><a class="row-link" href="#">BILL-2026-0002</a><div class="td-sub">PO-2026-0007</div></td>
                    <td>Sep 05, 2026</td><td>Sep 20, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">4,250.00</td>
                    <td><span class="pill pill-paid"><span class="pdot"></span> Paid</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                  <tr class="clickable-row" data-open-bill="1" data-no="BILL-2026-0003" data-date="Sep 15, 2026" data-due="Sep 30, 2026" data-po="—" data-amt="1,875.50" data-status="open">
                    <td><a class="row-link" href="#">BILL-2026-0003</a><div class="td-sub">—</div></td>
                    <td>Sep 15, 2026</td><td>Sep 30, 2026</td><td>Office Supplies</td>
                    <td class="td-right td-mono td-strong">1,875.50</td>
                    <td><span class="pill pill-open"><span class="pdot"></span> Open</span></td>
                    <td><button type="button" class="row-icon-btn" data-tip="View Bill"><i class="fa-solid fa-eye"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer">
              <span>Showing 1–3 of 3 bills</span>
              <div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-payments">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <div class="toolbar-search"><i class="fa-solid fa-magnifying-glass"></i><input placeholder="Search payments…"></div>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Method: All</button>
            </div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-record-payment"><i class="fa-solid fa-plus"></i> Record Payment</button>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Payment No.</th><th>Date</th><th>Method</th><th>Reference</th><th>Applied To</th><th class="td-right">Amount</th><th>Status</th></tr></thead>
                <tbody>
                  <tr><td><a class="row-link" href="#">PAY-2026-0041</a></td><td>Sep 02, 2026</td><td><i class="fa-solid fa-building-columns" style="opacity:.5;margin-right:5px"></i>Bank Transfer</td><td class="td-mono">REF-88213</td><td class="td-mono">BILL-2026-0001</td><td class="td-right td-mono td-strong">4,200.00</td><td><span class="pill pill-paid"><span class="pdot"></span> Cleared</span></td></tr>
                  <tr><td><a class="row-link" href="#">PAY-2026-0057</a></td><td>Sep 18, 2026</td><td><i class="fa-solid fa-money-check" style="opacity:.5;margin-right:5px"></i>Cheque</td><td class="td-mono">CHQ-00127</td><td class="td-mono">BILL-2026-0002</td><td class="td-right td-mono td-strong">4,250.00</td><td><span class="pill pill-paid"><span class="pdot"></span> Cleared</span></td></tr>
                </tbody>
              </table>
            </div>
            <div class="table-footer"><span>Showing 1–2 of 2 payments</span><div class="pager"><button class="pager-btn"><i class="fa-solid fa-chevron-left"></i></button><button class="pager-btn active">1</button><button class="pager-btn"><i class="fa-solid fa-chevron-right"></i></button></div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-credits">
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="empty-title">No credit notes yet</div>
            <div class="empty-sub">Credits, refunds and returns issued to this vendor will appear here.</div>
            <button class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> New Credit Note</button>
          </div>
        </div>

        <div class="tab-panel" id="panel-ledger">
          <div class="tab-toolbar">
            <div class="toolbar-left">
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-calendar"></i> Aug 01 – Sep 30, 2026</button>
              <button class="btn btn-ghost btn-sm"><i class="fa-solid fa-download"></i> Export Statement</button>
            </div>
            <span style="font-size:12px;color:var(--ink-soft);font-weight:600">Closing balance: <strong style="color:var(--deep-2)">K 1,875.50</strong></span>
          </div>
          <div class="table-wrap">
            <div class="table-scroll">
              <table class="data-table">
                <thead><tr><th>Date</th><th>Entry</th><th>Type</th><th>Reference</th><th class="td-right">Debit</th><th class="td-right">Credit</th><th class="td-right">Balance</th></tr></thead>
                <tbody>
                  <tr><td>Aug 20, 2026</td><td class="td-mono">JE-1001</td><td>Bill</td><td class="td-mono">BILL-2026-0001</td><td class="td-right td-mono">—</td><td class="td-right td-mono">4,200.00</td><td class="td-right td-mono td-strong">4,200.00</td></tr>
                  <tr><td>Sep 02, 2026</td><td class="td-mono">JE-1044</td><td>Payment</td><td class="td-mono">PAY-2026-0041</td><td class="td-right td-mono">4,200.00</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">0.00</td></tr>
                  <tr><td>Sep 05, 2026</td><td class="td-mono">JE-1062</td><td>Bill</td><td class="td-mono">BILL-2026-0002</td><td class="td-right td-mono">—</td><td class="td-right td-mono">4,250.00</td><td class="td-right td-mono td-strong">4,250.00</td></tr>
                  <tr><td>Sep 15, 2026</td><td class="td-mono">JE-1088</td><td>Bill</td><td class="td-mono">BILL-2026-0003</td><td class="td-right td-mono">—</td><td class="td-right td-mono">1,875.50</td><td class="td-right td-mono td-strong">6,125.50</td></tr>
                  <tr><td>Sep 18, 2026</td><td class="td-mono">JE-1103</td><td>Payment</td><td class="td-mono">PAY-2026-0057</td><td class="td-right td-mono">4,250.00</td><td class="td-right td-mono">—</td><td class="td-right td-mono td-strong">1,875.50</td></tr>
                  <tr class="ledger-total"><td colspan="4">Totals</td><td class="td-right td-mono">8,450.00</td><td class="td-right td-mono">10,325.50</td><td class="td-right td-mono">1,875.50</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-documents">
          <div class="dropzone">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <div class="dropzone-title">Drop files here or click to upload</div>
            <div class="dropzone-sub">Contracts, tax certificates, price lists · PDF, XLSX, JPG · Max 25 MB</div>
          </div>
          <div class="doc-grid">
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-pdf">PDF</div><div><div class="doc-name">Supplier_Agreement_2026.pdf</div><div class="doc-meta">248 KB · Aug 14, 2026 · ES</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-pdf">PDF</div><div><div class="doc-name">Tax_Clearance_Certificate.pdf</div><div class="doc-meta">112 KB · Aug 14, 2026 · ES</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
            <div class="doc-card"><div class="doc-card-top"><div class="doc-ext ext-xls">XLSX</div><div><div class="doc-name">Price_List_Sep2026.xlsx</div><div class="doc-meta">64 KB · Sep 01, 2026 · GS</div></div></div><div class="doc-card-actions"><button class="doc-action-btn"><i class="fa-solid fa-eye"></i> View</button><button class="doc-action-btn"><i class="fa-solid fa-download"></i> Download</button></div></div>
          </div>
        </div>

        <div class="tab-panel" id="panel-communication">
          <div class="tab-toolbar">
            <div class="toolbar-left"><button class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> All messages</button></div>
            <button class="btn btn-primary btn-sm" data-open-modal="modal-send-message"><i class="fa-solid fa-envelope"></i> New Message</button>
          </div>
          <div class="thread">
            <div class="msg-card">
              <div class="msg-head"><div class="msg-avatar in">GS</div><div><div class="msg-subject">Re: September Statement</div><div class="msg-meta">Gracian Seyama · Sep 20, 2026 · 08:41 AM</div></div><span class="msg-dir dir-in">INBOUND</span></div>
              <div class="msg-body">Good morning. I confirm receipt of the September statement. Please note invoice 0003 reflects the revised stationery prices agreed on the 1st. Kindly process payment before month-end as discussed…</div>
              <div class="msg-foot"><button class="msg-link"><i class="fa-solid fa-reply"></i> Reply</button><button class="msg-link"><i class="fa-solid fa-share"></i> Forward</button><button class="msg-link"><i class="fa-solid fa-paperclip"></i> statement_sep.pdf</button></div>
            </div>
            <div class="msg-card">
              <div class="msg-head"><div class="msg-avatar out">ES</div><div><div class="msg-subject">Welcome & Onboarding Documents</div><div class="msg-meta">Elvis Seyama · Aug 14, 2026 · 09:50 AM</div></div><span class="msg-dir dir-out">OUTBOUND</span></div>
              <div class="msg-body">Welcome aboard! Kindly submit your signed supplier agreement, tax clearance certificate and current price list so we can complete your onboarding and activate your account for purchase orders…</div>
              <div class="msg-foot"><button class="msg-link"><i class="fa-solid fa-reply"></i> Reply</button><button class="msg-link"><i class="fa-solid fa-share"></i> Forward</button></div>
            </div>
          </div>
        </div>

        <div class="tab-panel" id="panel-evaluation">
          <div class="eval-grid">
            <div>
              <div class="overview-section-title">Performance Scorecard — Q3 2026</div>
              <div class="criteria-row"><div><div class="criteria-name">Product / Service Quality</div><div class="criteria-hint">Conformance to specifications & returns rate</div></div><span class="criteria-weight">Weight 30%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Delivery Timeliness</div><div class="criteria-hint">On-time & in-full delivery performance</div></div><span class="criteria-weight">Weight 25%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Price Competitiveness</div><div class="criteria-hint">Benchmarked against market rates</div></div><span class="criteria-weight">Weight 20%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Communication & Responsiveness</div><div class="criteria-hint">Query resolution speed and clarity</div></div><span class="criteria-weight">Weight 15%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="criteria-row"><div><div class="criteria-name">Compliance & Documentation</div><div class="criteria-hint">Tax compliance, contracts and invoicing accuracy</div></div><span class="criteria-weight">Weight 10%</span><div class="stars-input"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div></div>
              <div class="form-field" style="margin-top:18px"><label>Evaluator Comments</label><textarea class="form-control" placeholder="Summarise strengths, issues and recommended actions…"></textarea></div>
              <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
                <button class="btn btn-ghost">Save as Draft</button>
                <button class="btn btn-primary"><i class="fa-solid fa-star"></i> Submit Evaluation</button>
              </div>
            </div>
            <div>
              <div class="eval-summary">
                <div class="eval-summary-score">—</div>
                <div style="justify-content:center;margin:8px 0 4px;display:flex;gap:3px">
                  <i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i><i class="fa-solid fa-star" style="color:var(--border);font-size:14px"></i>
                </div>
                <div class="eval-summary-label">Overall Rating</div>
                <div class="eval-history">0 evaluations on record</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="side-panel">
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Risk Assessment</span><a class="side-card-action" href="#">Details</a></div>
        <div class="side-card-body">
          <div class="risk-meter"><div class="risk-meter-top"><span class="risk-label">Credit Risk</span><span class="risk-value low">Low</span></div><div class="risk-track"><div class="risk-fill low" style="width:15%"></div></div></div>
          <div class="risk-meter"><div class="risk-meter-top"><span class="risk-label">Delivery Reliability</span><span class="risk-value med">N/A</span></div><div class="risk-track"><div class="risk-fill med" style="width:0%"></div></div></div>
          <div class="risk-meter" style="margin-bottom:0"><div class="risk-meter-top"><span class="risk-label">Compliance Score</span><span class="risk-value med">Pending</span></div><div class="risk-track"><div class="risk-fill med" style="width:5%"></div></div></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Contact Details</span><a class="side-card-action" href="#">Edit</a></div>
        <div class="side-card-body">
          <div class="contact-item"><div class="contact-icon email"><i class="fa-solid fa-envelope"></i></div><div><div class="contact-type">Email</div><div class="contact-val empty">Not provided</div></div></div>
          <div class="contact-item"><div class="contact-icon phone"><i class="fa-solid fa-phone"></i></div><div><div class="contact-type">Phone</div><div class="contact-val empty">Not provided</div></div></div>
          <div class="contact-item"><div class="contact-icon web"><i class="fa-solid fa-globe"></i></div><div><div class="contact-type">Website</div><div class="contact-val empty">Not provided</div></div></div>
        </div>
      </div>
      <div class="side-card glass">
        <div class="side-card-header"><span class="side-card-title">Recent Documents</span><a class="side-card-action" href="#">View all</a></div>
        <div class="side-card-body">
          <div class="mini-doc"><div class="mini-doc-icon ext-pdf">PDF</div><div style="flex:1;overflow:hidden"><div class="mini-doc-name">Supplier_Agreement_2026.pdf</div><div class="mini-doc-meta">248 KB · Aug 14, 2026</div></div></div>
          <div class="mini-doc"><div class="mini-doc-icon ext-xls">XLSX</div><div style="flex:1;overflow:hidden"><div class="mini-doc-name">Price_List_Sep2026.xlsx</div><div class="mini-doc-meta">64 KB · Sep 01, 2026</div></div></div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ═════════ MODALS ═════════ -->

<!-- BILL VIEW -->
<div class="modal-overlay" id="modal-bill-view">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
      <div class="modal-head-text">
        <div class="modal-title" id="bv-no">BILL-2026-0003</div>
        <div class="modal-subtitle">Gracian Seyama · VEN-00002</div>
      </div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="bv-chips">
        <span id="bv-status"><span class="pill pill-open"><span class="pdot"></span> Open</span></span>
        <span class="pill pill-draft"><span class="pdot" style="background:var(--sec)"></span> Amount Due: K <span id="bv-amt-chip">1,875.50</span></span>
      </div>
      <div class="bv-meta">
        <div class="bv-meta-cell"><div class="bv-meta-label">Bill Date</div><div class="bv-meta-val" id="bv-date">Sep 15, 2026</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Due Date</div><div class="bv-meta-val" id="bv-due">Sep 30, 2026</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Linked PO</div><div class="bv-meta-val" id="bv-po">—</div></div>
        <div class="bv-meta-cell"><div class="bv-meta-label">Currency</div><div class="bv-meta-val">K — Kwacha</div></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table">
        <thead><tr><th style="width:52%">Description</th><th style="width:14%">Qty</th><th style="width:17%" class="td-right">Unit (K)</th><th style="width:17%" class="td-right">Amount (K)</th></tr></thead>
        <tbody>
          <tr><td>Stationery bundle — pens, files, binders</td><td>15</td><td class="td-right td-mono">98.00</td><td class="td-right td-mono">1,470.00</td></tr>
          <tr><td>Delivery & handling — HQ</td><td>1</td><td class="td-right td-mono">350.00</td><td class="td-right td-mono">350.00</td></tr>
          <tr><td>VAT 16% on goods</td><td>—</td><td class="td-right td-mono">—</td><td class="td-right td-mono">55.50</td></tr>
        </tbody>
      </table>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">1,820.00</span></div>
        <div class="totals-row"><span>VAT</span><span class="td-mono">55.50</span></div>
        <div class="totals-row grand"><span>Total</span><span class="td-mono" id="bv-amt">1,875.50</span></div>
      </div>
      <div class="form-section-title">Payment History</div>
      <div id="bv-payrow-empty" class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> No payments applied to this bill yet.</div>
      <div id="bv-payrow" style="display:none" class="form-alert ok"><i class="fa-solid fa-circle-check"></i> Paid in full — payment cleared against this bill.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost"><i class="fa-solid fa-print"></i> Print</button>
      <button class="btn btn-ghost"><i class="fa-solid fa-download"></i> Download PDF</button>
      <button class="btn btn-deep" id="bv-pay-btn"><i class="fa-solid fa-credit-card"></i> Record Payment</button>
    </div>
  </div>
</div>

<!-- ══ RECORD PAYMENT ══ -->
<div class="modal-overlay" id="modal-record-payment">
  <div class="modal mid">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-credit-card"></i></div>
      <div class="modal-head-text"><div class="modal-title">Record Payment</div><div class="modal-subtitle">Apply a payment to open bills for Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Payment Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
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
        <div class="form-field"><label>Payment Amount (K) <span class="req">*</span></label><input class="form-control" id="pay-amount" style="text-align:right" value="1,875.50"></div>
      </div>

      <!-- electronic method details -->
      <div id="pay-elec" style="display:none">
        <div class="form-section-title">Transaction Details <span class="method-flag" id="elec-flag"><i class="fa-solid fa-building-columns"></i> Bank Transfer</span></div>
        <div class="form-grid">
          <div class="form-field"><label>Reference / Transaction No. <span class="req">*</span></label><input class="form-control" id="pay-ref" placeholder="e.g. TXN-88213 / UTR-004512"></div>
          <div class="form-field"><label>Provider / Institution <span class="req">*</span></label><select class="form-control" id="pay-provider"></select></div>
          <div class="form-field"><label>Account Name</label><input class="form-control" placeholder="e.g. Main Operating Account"></div>
          <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
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
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
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
                <div class="form-field"><label>Account Holder Name</label><input class="form-control" placeholder="e.g. Gracian Seyama"></div>
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

      <div class="form-section-title">Apply To Open Bills</div>
      <label class="apply-row"><input type="checkbox" checked><div class="apply-info"><div class="apply-title">BILL-2026-0003</div><div class="apply-sub">Due Sep 30, 2026 · Open</div></div><span class="apply-amt">1,875.50</span></label>
      <div class="form-alert info" style="margin-bottom:0"><i class="fa-solid fa-circle-info"></i> Unapplied amount: K 0.00 — payment fully allocated.</div>
      <div class="form-field" style="margin-top:14px"><label>Payment Note</label><textarea class="form-control" style="min-height:60px" placeholder="Optional…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep" id="post-payment" disabled><i class="fa-solid fa-check"></i> Record Payment</button>
    </div>
  </div>
</div>

<!-- NEW BILL -->
<div class="modal-overlay" id="modal-new-bill">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-receipt"></i></div>
      <div class="modal-head-text"><div class="modal-title">New Bill</div><div class="modal-subtitle">Record a supplier invoice against Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">Bill Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Vendor</label><input class="form-control" value="Gracian Seyama (VEN-00002)" disabled></div>
        <div class="form-field"><label>Bill Number <span class="req">*</span></label><input class="form-control" value="BILL-2026-0004"></div>
        <div class="form-field"><label>Bill Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
        <div class="form-field"><label>Due Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-10-13"></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Office Supplies</option><option>IT Equipment</option><option>Services</option></select></div>
        <div class="form-field full"><label>Linked Purchase Order</label><select class="form-control"><option>— None —</option><option>PO-2026-0004</option><option>PO-2026-0007</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="bill-items">
        <thead><tr><th style="width:46%">Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" value="A4 Ream Paper — Box (50 reams)"></td><td><input class="form-control" value="10"></td><td><input class="form-control" value="320.00"></td><td class="td-right td-mono">3,200.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
          <tr><td><input class="form-control" value="Toner Cartridge HP 26A"></td><td><input class="form-control" value="4"></td><td><input class="form-control" value="480.00"></td><td class="td-right td-mono">1,920.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="bill-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">5,120.00</span></div>
        <div class="totals-row"><span>VAT 16%</span><span class="td-mono">819.20</span></div>
        <div class="totals-row grand"><span>Total Due</span><span class="td-mono">5,939.20</span></div>
      </div>
      <div class="form-section-title">Attachments & Notes</div>
      <div class="dropzone-sm"><i class="fa-solid fa-paperclip"></i> Attach invoice scan (PDF/JPG)</div>
      <div class="form-field" style="margin-top:12px"><label>Internal Notes</label><textarea class="form-control" placeholder="Optional notes for internal reference…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-ghost">Save as Draft</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Bill</button>
    </div>
  </div>
</div>

<!-- NEW PURCHASE ORDER -->
<div class="modal-overlay" id="modal-new-po">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-file-lines"></i></div>
      <div class="modal-head-text"><div class="modal-title">New Purchase Order</div><div class="modal-subtitle">Raise a PO for Gracian Seyama</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">Order Details</div>
      <div class="form-grid">
        <div class="form-field"><label>Vendor</label><input class="form-control" value="Gracian Seyama (VEN-00002)" disabled></div>
        <div class="form-field"><label>PO Number <span class="req">*</span></label><input class="form-control" value="PO-2026-0012"></div>
        <div class="form-field"><label>Order Date <span class="req">*</span></label><input class="form-control" type="date" value="2026-09-28"></div>
        <div class="form-field"><label>Expected Delivery</label><input class="form-control" type="date" value="2026-10-08"></div>
        <div class="form-field"><label>Deliver To</label><select class="form-control"><option>Headquarters — Main Store</option><option>Branch — Lusaka</option></select></div>
        <div class="form-field"><label>Priority</label><select class="form-control"><option>Normal</option><option>Urgent</option><option>Low</option></select></div>
      </div>
      <div class="form-section-title">Line Items</div>
      <table class="items-table" id="po-items">
        <thead><tr><th style="width:46%">Item Description</th><th style="width:12%">Qty</th><th style="width:18%">Unit Price (K)</th><th style="width:16%" class="td-right">Amount (K)</th><th></th></tr></thead>
        <tbody>
          <tr><td><input class="form-control" value="Office Chair — Executive"></td><td><input class="form-control" value="6"></td><td><input class="form-control" value="850.00"></td><td class="td-right td-mono">5,100.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
          <tr><td><input class="form-control" value="Filing Cabinet 4-Drawer"></td><td><input class="form-control" value="2"></td><td><input class="form-control" value="1,450.00"></td><td class="td-right td-mono">2,900.00</td><td><button class="item-remove"><i class="fa-solid fa-trash-can"></i></button></td></tr>
        </tbody>
      </table>
      <button class="add-item-btn" data-add-item="po-items"><i class="fa-solid fa-plus"></i> Add Line Item</button>
      <div class="totals-block">
        <div class="totals-row"><span>Subtotal</span><span class="td-mono">8,000.00</span></div>
        <div class="totals-row"><span>VAT 16%</span><span class="td-mono">1,280.00</span></div>
        <div class="totals-row grand"><span>PO Total</span><span class="td-mono">9,280.00</span></div>
      </div>
      <div class="form-section-title">Terms & Notes</div>
      <div class="form-field"><label>Notes to Vendor</label><textarea class="form-control" placeholder="Delivery instructions, specifications…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-ghost">Save as Draft</button>
      <button class="btn btn-deep"><i class="fa-solid fa-paper-plane"></i> Create & Send PO</button>
    </div>
  </div>
</div>

<!-- SEND MESSAGE -->
<div class="modal-overlay" id="modal-send-message">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-envelope"></i></div>
      <div class="modal-head-text"><div class="modal-title">Send Message</div><div class="modal-subtitle">Email or SMS the vendor contact</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-alert warn"><i class="fa-solid fa-triangle-exclamation"></i> No email on file for this vendor — add a contact email to deliver messages.</div>
      <div class="form-grid">
        <div class="form-field full"><label>To</label><input class="form-control" placeholder="No email on file" disabled></div>
        <div class="form-field"><label>Channel</label><select class="form-control"><option>Email</option><option>SMS</option></select></div>
        <div class="form-field"><label>Template</label><select class="form-control"><option>— Blank —</option><option>Statement Reminder</option><option>Payment Remittance Advice</option><option>PO Confirmation</option></select></div>
        <div class="form-field full"><label>Subject <span class="req">*</span></label><input class="form-control" value="Outstanding Invoice — BILL-2026-0003"></div>
        <div class="form-field full"><label>Message <span class="req">*</span></label><textarea class="form-control" style="min-height:120px">Dear Gracian,

This is a friendly reminder that invoice BILL-2026-0003 (K 1,875.50) falls due on 30 Sep 2026. Kindly confirm your bank details are unchanged for settlement.

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

<!-- ADD NOTE -->
<div class="modal-overlay" id="modal-add-note">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-note-sticky"></i></div>
      <div class="modal-head-text"><div class="modal-title">Add Note</div><div class="modal-subtitle">Internal note or reminder on this vendor</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-grid">
        <div class="form-field"><label>Note Type</label><select class="form-control"><option>Internal Note</option><option>Reminder</option><option>Follow-up</option></select></div>
        <div class="form-field"><label>Reminder Date</label><input class="form-control" type="date"></div>
        <div class="form-field full"><label>Title <span class="req">*</span></label><input class="form-control" placeholder="e.g. Await updated tax clearance"></div>
        <div class="form-field full"><label>Note <span class="req">*</span></label><textarea class="form-control" placeholder="Details visible to internal users only…"></textarea></div>
        <div class="form-field full">
          <div class="switch-row">
            <label class="switch"><input type="checkbox"><span class="switch-slider"></span></label>
            <div><div class="switch-row-label">Pin to vendor profile</div><div class="switch-row-sub">Shows the note at the top of the Overview tab</div></div>
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

<!-- EDIT VENDOR -->
<div class="modal-overlay" id="modal-edit-vendor">
  <div class="modal wide">
    <div class="modal-header">
      <div class="modal-icon"><i class="fa-solid fa-pen-to-square"></i></div>
      <div class="modal-head-text"><div class="modal-title">Edit Vendor</div><div class="modal-subtitle">Update master data for VEN-00002</div></div>
      <button class="modal-close" data-close-modal><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-section-title">General</div>
      <div class="form-grid">
        <div class="form-field"><label>Display Name <span class="req">*</span></label><input class="form-control" value="Yeah"></div>
        <div class="form-field"><label>Legal Name <span class="req">*</span></label><input class="form-control" value="Gracian Seyama"></div>
        <div class="form-field"><label>Vendor Code</label><input class="form-control" value="VEN-00002" disabled></div>
        <div class="form-field"><label>Category</label><select class="form-control"><option>Office Supplies</option><option>IT Equipment</option><option>Services</option><option>Logistics</option></select></div>
        <div class="form-field"><label>Currency</label><select class="form-control"><option>K — Kwacha</option><option>$ — US Dollar</option></select></div>
        <div class="form-field"><label>Tax ID / VAT No.</label><input class="form-control" placeholder="e.g. 1002345678"></div>
      </div>
      <div class="form-section-title">Contact</div>
      <div class="form-grid">
        <div class="form-field"><label>Email</label><input class="form-control" type="email" placeholder="name@company.com"></div>
        <div class="form-field"><label>Phone</label><input class="form-control" placeholder="+260 …"></div>
        <div class="form-field full"><label>Website</label><input class="form-control" placeholder="https://…"></div>
      </div>
      <div class="form-section-title">Addresses</div>
      <div class="form-grid">
        <div class="form-field full"><label>Billing Address</label><textarea class="form-control" style="min-height:64px" placeholder="Street, city, country…"></textarea></div>
        <div class="form-field full"><label>Remit-To Address</label><textarea class="form-control" style="min-height:64px" placeholder="If different from billing…"></textarea></div>
      </div>
      <div class="form-section-title">Payment & Status</div>
      <div class="form-grid">
        <div class="form-field"><label>Payment Terms (Days)</label><input class="form-control" type="number" value="15"></div>
        <div class="form-field"><label>Preferred Payment Method</label><select class="form-control"><option>Bank Transfer</option><option>Cheque</option><option>Mobile Money</option></select></div>
        <div class="form-field"><label>Opening Balance (K)</label><input class="form-control" type="number" value="0.00" step="0.01"></div>
        <div class="form-field" style="display:flex;align-items:flex-end;padding-bottom:6px">
          <div class="switch-row">
            <label class="switch"><input type="checkbox"><span class="switch-slider"></span></label>
            <div><div class="switch-row-label">Vendor is Active</div><div class="switch-row-sub">Currently inactive — toggle to re-activate</div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger-ghost spacer"><i class="fa-solid fa-trash-can"></i> Delete Vendor</button>
      <button class="btn btn-ghost" data-close-modal>Cancel</button>
      <button class="btn btn-deep"><i class="fa-solid fa-check"></i> Save Changes</button>
    </div>
  </div>
</div>

<script>
  /* tabs / sidebar */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
    });
  });
  document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', e => {
      e.preventDefault();
      document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');
    });
  });

  /* modals */
  const openModal = id => { const ov = document.getElementById(id); ov.classList.remove('closing'); ov.classList.add('open'); document.body.classList.add('modal-open'); };
  const closeModal = ov => { ov.classList.add('closing'); setTimeout(() => { ov.classList.remove('open','closing'); document.body.classList.remove('modal-open'); }, 180); };
  document.querySelectorAll('[data-open-modal]').forEach(el => el.addEventListener('click', e => { e.preventDefault(); openModal(el.dataset.openModal); }));
  document.querySelectorAll('.modal-overlay').forEach(ov => {
    ov.addEventListener('mousedown', e => { if (e.target === ov) closeModal(ov); });
    ov.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(ov)));
  });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(closeModal); });

  /* bill view — whole row clickable */
  function openBillFromData(d){
    document.getElementById('bv-no').textContent = d.no;
    document.getElementById('bv-date').textContent = d.date;
    document.getElementById('bv-due').textContent = d.due;
    document.getElementById('bv-po').textContent = d.po;
    document.getElementById('bv-amt').textContent = d.amt;
    document.getElementById('bv-amt-chip').textContent = d.amt;
    const paid = d.status === 'paid';
    document.getElementById('bv-status').innerHTML = paid
      ? '<span class="pill pill-paid"><span class="pdot"></span> Paid</span>'
      : '<span class="pill pill-open"><span class="pdot"></span> Open</span>';
    document.getElementById('bv-payrow').style.display = paid ? 'flex' : 'none';
    document.getElementById('bv-payrow-empty').style.display = paid ? 'none' : 'flex';
    document.getElementById('bv-pay-btn').style.display = paid ? 'none' : 'inline-flex';
    openModal('modal-bill-view');
  }

  document.querySelectorAll('tr[data-open-bill]').forEach(row => {
    row.setAttribute('tabindex', '0');
    row.addEventListener('click', e => {
      if (e.target.closest('a')) e.preventDefault();
      openBillFromData(row.dataset);
    });
    row.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openBillFromData(row.dataset);
      }
    });
  });

  document.getElementById('bv-pay-btn').addEventListener('click', () => {
    closeModal(document.getElementById('modal-bill-view'));
    setTimeout(() => openModal('modal-record-payment'), 200);
  });

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

  /* stars */
  document.querySelectorAll('.stars-input').forEach(group => {
    const stars = group.querySelectorAll('i');
    stars.forEach((star, idx) => star.addEventListener('click', () => stars.forEach((s, i) => s.classList.toggle('on', i <= idx))));
  });

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
</script>
</body>
</html>
```

---

## FINAL ENFORCEMENT STATEMENT FOR OPENCODE

This is not a suggestion. This is a strict implementation requirement.

You must implement the vendor module so that when a user opens the vendor detail page, they see and can interact with **every element present in the mockup above**.

If any element is missing, the implementation is incomplete.

If any modal is missing, the implementation is incomplete.

If any payment method behavior is missing, the implementation is incomplete.

If split payment checkbox tiles are not premium, collapsible, expandable, validated, and fully wired to backend persistence, the implementation is incomplete.

If bill rows cannot be clicked anywhere to open the bill view, the implementation is incomplete.

If the Transaction Details section lacks sufficient top spacing, the implementation is incomplete.

If the design tokens, gradients, glass effects, shadows, animations, or centered hairlines are altered away from the mockup, the implementation is incomplete.

Do not stop until all pages, modals, components, routes, controllers, models, migrations, seeders, policies, services, validations, frontend interactions, and tests required by this module are fully delivered.