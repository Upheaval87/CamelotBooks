EXPENSE MANAGEMENT MODULE — FULL IMPLEMENTATION SPEC (DASHBOARD / ALL EXPENSES / RECORD /
DETAIL / CLAIM / RECURRING / CATEGORIES / REPORTS). Rebuild expenses as a proper
transaction module: Capture → Review → Approval → Posting → Payment/Reimbursement →
General Ledger. ALL VALUES INLINE; no mockup dependency. The system-wide pinnable rails
feature (rails.html) stays EXACTLY as implemented — each expense page renders its rail
per the registry in §10; global pin applies.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: posting handler (journal creation),
payment/reimbursement handlers, approval handlers, budget module math, category→GL
mappings, export/print handlers, search/filter/pagination params, auth/permissions and
all routes remain EXACTLY as-is. Every pre-existing button keeps its handler; this spec
re-styles/re-arranges UI only. Fix the current mislabeled list heading ("Record
Expense") to "All Expenses".

==================== 0 · DISCOVERY ====================
0.1 Inventory expense routes/pages + handlers: index/create/edit/show/post, claims
(create/approve/reimburse), recurring, categories, reports, reverse/adjustment.
0.2 List CURRENT controls + handlers per page (drives §14 audit).
0.3 Locate: expense categories (with GL account mapping), budget module (budget vs used
per category/cost centre), payment + reimbursement handlers, journal viewer, suppliers,
employees, attachment storage.
0.4 Locate user-preference storage + header Favorites (rails) — reference only.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES ====================
Pill + dot component: Draft rgba(17,69,75,.07)/.2/#11454b; Submitted rgba(70,112,140,
.10)/.4/#46708C; Pending Approval rgba(217,119,6,.10)/.35/#b45309 (dot #d97706);
Approved = mint gradient (#ecfdf3→#dcf5e7, rgba(22,163,74,.28), #15803d, dot #22c55e);
Rejected rgba(185,28,28,.08)/.3/#b91c1c; Returned + Cancelled gray
rgba(138,165,167,.15)/.5/#5f7476; Posted rgba(18,143,142,.10)/.35/#128F8E;
Paid + Reimbursed rgba(22,163,74,.12)/.4/#15803d (Partially Reimbursed = amber).

==================== 3 · DASHBOARD (expenses.dashboard) ====================
3.1 NON-sticky page head: h1 "Expense Management" + sub "Capture, approve, post, track
and report business expenses."; right: [⋯ More ghost: Import · Export · Expense
Categories · Recurring Expenses] [🧾  Expense Claim secondary] [＋ Record Expense CTA].
3.2 KPI ROW (4): hero Total Expenses (teal gradient, "year to date") · This Month ·
Pending Approval (amber value + "Review queue →" link) · Unposted. ≤1000px 2 cols.
3.3 ANALYTICS ROW (3 cards ≤1000px stack): Expenses by Category (label + proportional
bar + value rows) · Monthly Trend (8-column mini bar chart, month labels) · Pending
Approvals (mono ref + description + amount + [Approve] per row → existing handler).

==================== 4 · ALL EXPENSES (expenses.index) ====================
4.1 Page head: h1 "All Expenses" + sub; right [⋯ More: Import · Export · Print] +
[＋ Record Expense CTA].
4.2 CLICKABLE STATUS BOXES (6): All(t-ink) / Pending(t-amber) / Approved(t-mint) /
Posted(t-teal) / Paid(t-green) / Rejected(t-red); live counts; active teal ring; click
sets EXISTING status filter param.
4.3 CONTROLS: search (expense #, description, payee, invoice #, employee, reference;
EXISTING param) + Category + Department + Payment-status selects + Advanced filters
panel (Date range, Branch, Cost Centre, Project, Employee, Amount range, Tax type,
Currency) toggled by link; state in URL params.
4.4 TABLE (mist thead): Expense № (mono) 11 / Date 9 / Description 20 / Category 13 /
Payee 12 / Department 10 / Amount (K) num 9 / Status 10 / Actions 6 with
STATUS-DEPENDENT ⋯ menus (existing handlers):
 Draft → Edit, Submit, Duplicate, Delete, Print.
 Pending Approval → View, Approve, Reject, Return for Correction, Print.
 Approved → View, Post, Edit (authorized), Print.
 Posted → View, Print, Download PDF, View Journal, View Payment, Duplicate, Reverse
 (danger), Audit Trail.
 Paid/Reimbursed → View, Print, View Journal, View Payment, Reverse (danger).
4.5 Pagination "Showing X of Y expenses" + Prev/Next (existing).

==================== 5 · RECORD EXPENSE (expenses.create) ====================
5.1 Sticky head: h1 + sub "Capture a business expense with correct accounting
allocation."; right Cancel ghost + seg [Save Draft ghost | Save & Submit ⤴ CTA].
5.2 SUMMARY BAR (live): Subtotal / VAT (tax-inclusive note) / Discount (−) / hero Total.
5.3 SECTION "Expense Information" (g4): Expense № (auto, disabled) / Expense Date /
Expense Category select (from categories registry) / Payee–Supplier combo / Description
sp2 / Reference–Invoice № / Currency.
5.4 SECTION "Accounting Allocation": multi-line table Account (select, default from
category GL mapping) / Department / Cost Centre / Amount + delete; [＋ Add Allocation
Line]; tfoot Total = sum of lines (must equal header Total; validate).
5.5 SECTION "Payment Information": Payment Status (Unpaid/Paid) / Payment Method (Bank
Transfer, Cash, Mobile Money, Cheque, Supplier Credit, Employee Reimbursement) /
Payment Account / Payment Date / Payment Reference (optional until paid). Show chip
"expense ≠ payment": recording an expense does not create a payment; [Record Payment]
appears after posting (existing handler).
5.6 SECTION "Budget Control": rows Annual Budget {category} / Used to date / This
expense / Remaining after (live); verdict chip ✓ Within Budget (green) or ⚠ OVER BUDGET
(amber: remaining/expense/over amounts) requiring additional authorization (reason +
approver) before submit.
5.7 SECTION "Receipts & Attachments": attachment chips + [＋ Attach Receipt][📷 Take
Photo][⌨ Scan Receipt (OCR) — prefill supplier/date/invoice/amount/VAT for user
verification, where OCR exists; else hide].

==================== 6 · EDITING RULES (ENFORCED IN UI) ====================
Draft = fully editable. Submitted = limited editing OR Return for Correction. Approved =
editing requires authorization (existing rule). Posted/Paid = NO direct editing: only
[Reverse] or [Create Adjustment] (existing handlers). Surface lock notices accordingly.

==================== 7 · DETAIL (expenses.show) — HEADER STANDARD =================
7.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Expenses › All Expenses › {EXP-№}
(here mono). RIGHT cluster: [🖨 Print][📓 View Journal][💳 View Payment] + [⋯ More:
Download PDF · Duplicate · Create Adjustment · Audit Trail · Reverse (danger)].
Visibility per status (Posted/Paid show all; Draft hides Journal/Payment).
7.2 PROFILE CARD (identity only, NO buttons): tile + "Expense" + mono chip + status
badge(s) (Posted + Paid when applicable); meta chips: category, Payee, Department ·
Cost Centre, Ref.
7.3 SUMMARY BAR: Subtotal / VAT / Discount / hero Total.
7.4 ALLOCATION table (read-only) with tfoot total.
7.5 APPROVAL WORKFLOW card: steps Created → Department Manager → Finance Manager →
Posted (done/current/todo with who + timestamps); posted shows journal line note +
"locked · posted" chip.
7.6 RELATED TRANSACTIONS CHAIN: {EXP-№ here} → {supplier invoice} → {payment} →
{journal} → General Ledger; clickable mono chips; em-dash placeholders when absent.
7.7 ATTACHMENTS chips + NOTES.
7.8 AUDIT TRAIL: rows incl. field-level changes: "Amount changed 450,000 → 475,000 ·
reason …" with user + timestamp; created/submitted/approved/posted/paid entries.

==================== 8 · EXPENSE CLAIM (claims.create) — CENTRED + WIDE ============
8.1 The "New Expense Claim" card MUST be horizontally CENTRED (margin-inline:auto) and
WIDENED to max-width:1100px (was ~860px left-aligned) so all fields fit comfortably.
8.2 Card header: "New Expense Claim" + right chip "Employee claim".
8.3 Field grid: 3 columns at ≥1100px, 2 at ≥700px, 1 below: Employee (default current
user) / Expense Date / Category (Travel/Transport/Meals…) / Amount / Description
(spans 2) / Payment Method (Personal Funds) / Reimburse To (bank/mobile money select).
8.4 Attachments row: receipt chips + [＋ Attach Receipt][📷 Take Photo].
8.5 Footer right: [Save Draft ghost][Submit Claim ⤴ CTA] (existing handlers).
8.6 Approval → reimbursement path surfaces on claim detail: Manager approval → Finance
approval → Reimbursement (existing workflow), statuses incl. Partially/Fully Reimbursed.

==================== 9 · RECURRING + CATEGORIES ====================
9.1 RECURRING EXPENSES page: table Name / Category / Amount / Frequency / Next Date /
Status (Active/Paused) + row actions Edit · Pause/Resume · Skip Next · Generate Now ·
View History (existing handlers; create minimal page if absent using system patterns).
9.2 EXPENSE CATEGORIES page: tree (Administration → Office Supplies/Stationery/…;
Utilities → Electricity/Water/Internet/…; Transport; HR; Professional Services) with
each leaf showing its mapped GL account; Add/Edit category (existing config handlers).

==================== 10 · RAILS REGISTRY (per page; rails feature unchanged) =======
expenses.dashboard → Quick Nav [All Expenses, Pending Approval, Record Expense,
Expense Reports].
expenses.index → Views [All Expenses(active), Draft, Pending Approval, Approved,
Posted, Paid] + Reports [Expense Register, Expense vs Budget].
expenses.create → Quick Nav [All Expenses, Expense Categories, Day Book].
expenses.show → Quick Nav [View Journal, View Payment, Print, All Expenses].
claims.create → Quick Nav [My Claims, Pending Approval, Reimbursements].
expenses.recurring → Quick Nav [All Expenses, Record Expense, Day Book].
expenses.categories → Quick Nav [All Expenses, Record Expense, General Ledger].
expenses.reports → Quick Nav [All Expenses, Pending Approval, Day Book].

==================== 11 · REPORTS (expenses.reports) ====================
11.1 Page head h1 "Expense Reports" + right [⇩ Export All]; filter bar: period seg2
[This Month|This Quarter|This Year|Custom] + Branch + Department selects + right
[💾 Save Filter]; filters apply to all reports + exports; state in URL params.
11.2 REPORT CARDS (grid 3 cols): Expense Register · Expense by Category · By
Department/Branch/Cost Centre · Employee Expenses · Expense vs Budget · Unpaid Expenses ·
Tax/VAT Expense Report · Pending Approval Report · Expense Audit Report. Each: icon
tile, title, description, PDF + Excel chips, Open →. Existing report pages if present;
else create MINIMAL report pages using the system report pattern.
11.3 Render "Expense by Category" sample table/bars on the page (live data).
11.4 Report toolbar buttons where applicable: Generate · Refresh · Filter · Save
Filter · Export Excel · Export PDF · Print · Email (existing handlers).

==================== 12 · ACCOUNTING INTEGRATION (UI SURFACES ONLY) ===============
12.1 POST (existing handler) creates the journal: paid → DR expense CR bank/cash;
credit → DR expense CR Accounts Payable; reimbursement → DR expense CR Employee
Payable until reimbursed. "View Journal Entry" links to the actual entry.
12.2 Every reference in §7.6 clickable; never edit posted transactions (§6).

==================== 13 · ACCESSIBILITY / RESPONSIVE ====================
13.1 aria: status boxes aria-pressed; ⋯ menus aria-haspopup; breadcrumb nav; focus
rings #94a3b8; tables th scope; budget verdict aria-live.
13.2 ≤1100px: KPI 2-col, analytics stack, allocation table scrolls; ≤768px slim rail
hidden, statgrid 2-col, g4 → 1fr 1fr; claim card full-width centred; no horizontal
PAGE scrollbar at 1280/1024/768.

==================== 14 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/payment/budget/approval
handler changes; no new packages; ONE shared component/CSS per pattern; totals/allocation
sums always visible; claim card centred + 1100px wide; no hardcoded sample data.

==================== 15 · VERIFY (EVERY PAGE) ====================
15.1 ACTION AUDIT: every button (Record/Claim/Import/Export/Print, status boxes, ⋯
menus per status, Add Allocation, Attach/Take Photo/OCR, budget authorization, Save
Draft/Save & Submit, Approve/Reject/Return, Post, View Journal/Payment, Reverse,
Create Adjustment, recurring Edit/Pause/Skip/Generate/History, category add/edit,
report Generate/Export/Email) triggers the SAME handler/route as pre-implementation.
15.2 LIST: heading "All Expenses"; deep search + advanced filters combine; ⋯ menus
match §4.4 per status; posted rows have no plain Edit.
15.3 RECORD: allocation total = header total enforced; budget ✓/⚠ verdicts correct and
over-budget authorization gates submit; payment section keeps expense≠payment.
15.4 CLAIM: card is CENTRED with max-width 1100px at ≥1100px viewports (measure), 3-col
fields, no cramped wrapping at 1280/1024; submit → approval → reimbursement statuses.
15.5 DETAIL: header breadcrumb + cluster only; profile identity-only; chain links
navigate; audit shows field-level changes with reasons.
15.6 REPORTS: render from live data; filters/exports carry params.
15.7 RAILS REGRESSION: slim/full/pins/global pin behave exactly as rails.html on these
and all other pages.
15.8 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/badge table; rail registry per page; claim-card centering +
width confirmed; report pages created; confirmation rails + all existing functionality
unchanged.