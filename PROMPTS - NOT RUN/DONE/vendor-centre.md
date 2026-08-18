VENDOR CENTRE MODULE — FULL IMPLEMENTATION SPEC (DASHBOARD / DIRECTORY / PROFILE /
BILLS / PAYMENTS + CREDITS / AGING + EVALUATION + REPORTS / COMMUNICATION / SETTINGS).
Rebuild the Vendor Centre as the complete supplier workspace: vendor information,
transactions, bills, payments, documents and performance — tightly integrated with
Accounts Payable, Purchasing/Inventory, General Ledger, Banking, Tax and the Reporting
engine. ALL VALUES INLINE; no mockup dependency. The system-wide pinnable rails feature
(rails.html) stays EXACTLY as implemented — each vendor page renders its rail per the
registry in §10; global pin applies.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: bill approval/scheduling, payment
processing/bulk payment/reversal, PO approval/conversion, GRN receiving, credit-note
apply/reverse, posting handlers (AP/GL journals), tax settings, export/print/email
handlers, search/filter/sort/pagination params, auth/permissions and all routes remain
EXACTLY as-is. Every pre-existing button keeps its handler; this spec re-styles/re-
arranges UI only.

==================== 0 · DISCOVERY ====================
0.1 Inventory vendor routes/pages + handlers: dashboard, directory index/create/edit/
show, bills centre, payments centre, credits, statements, documents, evaluation,
communication, aging, reports, settings.
0.2 List CURRENT controls + handlers per page (drives §14 audit), incl. row actions
(View/Edit/Deactivate/Delete), bulk handlers, statement generation, payment voucher/
email, reversal flows.
0.3 Locate: vendor categories, payment terms, tax IDs, credit limits, bank accounts,
document storage + expiry fields, evaluation/scorecard data, communication log.
0.4 Locate user-preference storage + header Favorites (rails) — reference only.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES ====================
Pill + dot component: Active = mint gradient (#ecfdf3→#dcf5e7, rgba(22,163,74,.28),
#15803d, dot #22c55e); Inactive + Reversed gray rgba(138,165,167,.15)/.5/#5f7476;
Overdue rgba(185,28,28,.08)/.3/#b91c1c; Pending Approval rgba(217,119,6,.10)/.35/
#b45309 (dot #d97706); Unpaid/Partial steel rgba(70,112,140,.10)/.4/#46708C;
Paid/Posted green rgba(22,163,74,.12)/.4/#15803d or teal rgba(18,143,142,.10)/.35/#128F8E;
Applied = green.

==================== 3 · DASHBOARD (vendors.dashboard) ====================
3.1 NON-sticky page head: h1 "Vendor Centre" + sub "The complete supplier workspace —
payables, bills, payments, documents and performance."; right: [⋯ More: Generate
Report · Export Data · Vendor Settings] [🔍 Search Vendor ghost] [➕ Add Vendor CTA].
3.2 KPI ROW (4): hero Total Payables (teal gradient; sub "{n} vendors · {n} active") ·
Overdue Vendor Bills (red value "{count} · {value}" + "View overdue →") · Bills Due
This Week (amber "{count} · {value}" + "Schedule payments →") · Payments This Month
(value + "{n} payments"). ≤1000px 2 cols.
3.3 ANALYTICS ROW (2 cards, ≤1000px stack): Top Vendors by Spending (label +
proportional bar + value, YTD) · Recent Vendor Activity (badge-typed feed: Bill /
Payment / PO / Credit / Overdue with ref · vendor · amount · date; each ref links to
its document).

==================== 4 · VENDOR DIRECTORY (vendors.index) ====================
4.1 Page head: h1 "Vendor Directory" + sub; right [⋯ More: Import Vendors · Export
Vendors] + [＋ Add New Vendor CTA].
4.2 CLICKABLE STATUS BOXES (5): All(t-ink) / Active(t-mint) / Inactive(t-gray) /
Overdue(t-red) / Zero Balance(t-teal); live counts; active teal ring; click sets
EXISTING filter param.
4.3 CONTROLS: search (code, name, contact, email; EXISTING param) + Category select +
sort select [Name A–Z / Balance high→low / Recent activity].
4.4 BULK ACTIONS: checkbox column + header select-all; selection bar (teal-tint, appears
when >0): "{n} selected" + [📤 Export Selected][⏸ Deactivate][🗑 Delete (danger, confirm)]
[Cancel] → EXISTING bulk handlers.
4.5 TABLE (mist thead, min-width scroll): [checkbox] / Code (mono) / Vendor Name (700) /
Contact Person / Phone / Email / Category / Outstanding (K) (red when >0) / Last
Transaction / Status badge / actions (View icon + ⋯ menu: View Profile · Edit Vendor ·
Deactivate · Delete · Print · Email). Pagination existing.

==================== 5 · VENDOR CREATE / EDIT ====================
5.1 Sticky head: h1 "Add Vendor" / "Edit Vendor {code}" + sub; right Cancel ghost +
[Save Vendor CTA] (edit adds Delete danger-o with confirm + deactivation rules).
5.2 Sections: Identity (Code auto-disabled, Name*, Category select, Status toggle on
edit) · Contact (Contact Person, Phone, Email) · Address · Tax & Terms (Tax ID,
Payment Terms select, Credit Limit, Currency) · Banking (Bank, Account №, branch) ·
Notes. Summary bar on edit: Outstanding / Current / Overdue / hero Credit Limit.
5.3 Deactivate ≠ Delete: deactivation preserves history (existing rule); delete blocked
when transactions exist (existing rule) — surface the message.

==================== 6 · VENDOR PROFILE (vendors.show) — HEADER STANDARD ============
6.1 STICKY HEAD: LEFT back icon-btn + breadcrumb Purchasing › Vendor Directory ›
{VEN-code} (here mono). RIGHT cluster: [✎ Edit Profile][✚ Add Note][📤 Upload Document]
| hairline | [🧾 New Bill secondary][💳 Make Payment CTA].
6.2 PROFILE CARD (identity only, NO buttons): initials tile; name + mono chip + Active
badge + Overdue chip (red, amount) when applicable; meta chips: category, contact ·
phone, email, Terms Net {n}, Tax ID.
6.3 SUMMARY BAR: Outstanding (n open bills) / Current / Overdue (red) / hero Credit
Limit (teal).
6.4 TABBED VENDOR CARD (8 tabs; panes switch client-side):
 Overview → read-only g3 grid: Code, Category, Contact, Phone, Email, Address, Tax ID,
 Payment Terms, Credit Limit, Bank Account, Current Balance (red when >0), Since.
 Transactions → table Date / Type badge / Reference (mono) / Debit / Credit / Balance
 (running, bold) / Status; refs link to documents.
 Bills → header buttons [🧾 New Bill][📥 Import Bill] + "Open Vendor Bills Centre →";
 table Bill № / Bill Date / Due Date / Amount / Balance / Status + row actions
 (Approve / Pay / Schedule per status — existing handlers).
 Payments → [💳 Make Payment] + "Open Payments Centre →"; table Payment № / Date /
 Method / Amount / Status + row actions (Voucher, Email).
 Purchase Orders → [📄 New Purchase Order] + "Open Purchase Orders →"; table PO № /
 Date / Ordered / Received / Status + row actions (Receive, To Bill).
 Statements → buttons [📄 Generate Statement][✉ Email Statement][📕 Download PDF]
 [📗 Export Excel] (existing handlers).
 Documents → attachment chips with expiry chips (valid green / "expires in {n}d" amber)
 + [📤 Upload Document][⏰ Set Expiry Reminder] (existing handlers; reminders stored).
 Evaluation → metric bars (Delivery / Pricing / Quality / Response / Reliability, %) +
 overall score chip ("★ Overall score {x} — Preferred supplier" green when ≥85) +
 [★ Rate Vendor][✚ Add Review][📊 Generate Scorecard] (existing handlers).

==================== 7 · VENDOR BILLS CENTRE (bills.index) ====================
7.1 Page head: h1 "Vendor Bills" + sub; right [⇩ Export Bills] + [＋ Create Bill CTA].
7.2 STATUS BOXES (5): Unpaid(t-ink) / Due Soon(t-amber) / Overdue(t-red) / Pending
Approval(t-teal) / Paid(t-mint); live counts; click sets EXISTING filter.
7.3 TABLE: Bill № (mono) / Vendor / Bill Date / Due Date / Amount (K) / Balance (K)
(bold; red when overdue) / Status badge / actions: Pending Approval → [Approve]
[Schedule]; Unpaid/Partial → [Pay][Schedule]; Overdue → [Pay]; Paid → print icon.
All existing handlers; approval workflow unchanged.

==================== 8 · PAYMENTS CENTRE + CREDIT NOTES (payments.index) ============
8.1 Page head: h1 "Vendor Payments" + sub; right [🖨 Print Payment Batch][⇩ Export
Payments][∑ Bulk Payment secondary][＋ New Payment CTA].
8.2 PAYMENTS TABLE: [checkbox for bulk] / Payment № (mono) / Date / Vendor / Method /
Reference (mono; em-dash when none) / Amount (bold) / Status badge / actions: Posted →
voucher print + email icons; Pending Approval → [Approve]; Reversed → audit icon.
Bulk selection uses existing bulk-payment handler.
8.3 CREDIT NOTES CARD: header + [＋ Create Credit Note]; table Credit № / Vendor /
Reason / Amount / Status (Unapplied steel / Applied green / Reversed gray) / actions
[Apply] / [View] / [Reverse] — existing handlers.

==================== 9 · AGING + EVALUATION + REPORTS + COMMUNICATION + SETTINGS ===
9.1 VENDORS.REPORTS page: head h1 "Vendor Analytics & Reports" + right [✉ Communication
Centre][⇩ Export All]; filter bar (period seg2 + Category + Vendor selects).
9.2 AGING TABLE card: Vendor / Current / 1–30 / 31–60 / 61–90 / 90+ / Total (K);
overdue cells red; tfoot TOTAL row; header buttons [View Details][Export][Print].
9.3 REPORT CARDS (grid 3 cols): Vendor Balance · Vendor Transaction · Vendor Payment ·
Outstanding Payables · Vendor Spending Analysis · Vendor Price Comparison · Vendor
Performance · Purchases by Vendor · Vendor Evaluation Centre. Each: icon tile, title,
description, PDF + Excel chips, Open →. Existing report pages if present; else create
MINIMAL report pages using the system report pattern.
9.4 COMMUNICATION CENTRE (page or modal): vendor select + channel (Email/SMS) +
document type (Statement / PO / Payment notification / Free email) + preview + Send;
sent-communications log table (date, vendor, channel, document, status). Existing
email/SMS handlers.
9.5 VENDOR SETTINGS page: cards for Vendor Categories (tree with GL mapping), Payment
Terms, Tax Settings, Approval Workflows, Numbering Formats, User Permissions — each
opens/edits via EXISTING settings handlers; create minimal pages only where absent.

==================== 10 · RAILS REGISTRY (per page; rails feature unchanged) =======
vendors.dashboard → Quick Nav [Vendor Directory, Vendor Bills, Vendor Payments,
Vendor Reports].
vendors.index → Views [All Vendors(active), Active, Inactive, Overdue] + Reports
[Vendor Balances, Vendor Aging].
vendors.create/edit → Quick Nav [Vendor Directory, Vendor Balances, Day Book].
vendors.show → Quick Nav [New Bill, Make Payment, Statement, Email Vendor,
Vendor Directory].
bills.index → Views [Unpaid, Due Soon, Overdue, Pending Approval, Paid] + Reports
[Outstanding Payables, Vendor Aging].
payments.index → Quick Nav [New Payment, Bulk Payment, Vendor Bills, Vendor Centre].
vendors.reports → Quick Nav [Vendor Directory, Vendor Aging, Evaluation Centre].
vendors.settings → Quick Nav [Vendor Directory, Categories, General Ledger].

==================== 11 · INTEGRATION RULES (UI SURFACES ONLY) ====================
11.1 Vendor balances = AP ledger balances (live); Outstanding/Current/Overdue computed
from bill due dates; aging buckets from the same source — never stored duplicates.
11.2 New Bill/Make Payment/New PO prefill vendor (terms, credit limit, bank, tax) and
run existing credit-limit checks (warn when limit exceeded — existing rule).
11.3 Posted transactions immutable: corrections via Reverse / Create Adjustment /
Credit Note (existing handlers); never edit history.
11.4 Documents expiry reminders feed a notifications list (existing notifications if
present; else store reminder dates and show amber chips).

==================== 12 · ACCESSIBILITY / RESPONSIVE ====================
12.1 aria: status boxes aria-pressed; bulk bar aria-live count; tabs role=tablist;
⋯ menus aria-haspopup; breadcrumb nav; focus rings #94a3b8; tables th scope.
12.2 ≤1100px statgrid 3-col; ≤1000px KPI 2-col + analytics stack + repcards 2-col;
≤768px slim rail hidden, statgrid 2-col, tables horizontal-scroll inside cards; no
horizontal PAGE scrollbar at 1280/1024/768.

==================== 13 · CONSTRAINTS ====================
No changes to rails feature or other modules; no AP/payment/PO/credit/posting handler
changes; no new packages; ONE shared component/CSS per pattern; no hardcoded sample
data (live registry only); deactivation/deletion rules preserved.

==================== 14 · VERIFY (EVERY PAGE) ====================
14.1 ACTION AUDIT: every button (Add/Search/Generate/Export, status boxes, bulk
Export/Deactivate/Delete, row ⋯ menus, Edit Profile/Add Note/Upload, New Bill/Make
Payment, tab row actions Approve/Pay/Schedule/Receive/To Bill/Voucher/Email, statement
Generate/Email/PDF/Excel, document Upload/Expiry, evaluation Rate/Review/Scorecard,
payments New/Bulk/Print Batch/Export/Approve, credits Create/Apply/Reverse, aging
View/Export/Print, report Opens, communication Send, settings edits) triggers the SAME
handler/route as pre-implementation (spot-click each).
14.2 DIRECTORY: bulk bar appears only when selected; counts live; balances red when >0.
14.3 PROFILE: tabs switch without reload; running balance math correct; expiry chips
amber within 30 days; overall score chip threshold correct.
14.4 BILLS/PAYMENTS: status boxes set existing filters; approval gating unchanged;
reversals logged.
14.5 AGING: bucket sums = vendor totals = AP balance; tfoot totals correct.
14.6 RAILS REGRESSION: slim/full/pins/global pin behave exactly as rails.html on these
and all other pages.
14.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/badge table; rail registry per page; report/communication/
settings pages created (if any); confirmation rails + all existing functionality
unchanged.