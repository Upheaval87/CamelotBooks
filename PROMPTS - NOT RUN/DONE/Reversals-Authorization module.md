TRANSACTION REVERSALS + REVERSAL AUTHORIZATION — FULL IMPLEMENTATION SPEC
(SEARCH+REQUEST / REVERSALS DASHBOARD+LIST / AUTHORIZATION DASHBOARD / PENDING QUEUE /
REVIEW+AUTHORIZE / RULES+PERMISSIONS+AUDIT). A controlled, audited reversal workflow that
NEVER deletes posted transactions. ALL VALUES INLINE; no mockup dependency.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every page per the registry in §13: resting slim icon rail with
teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites "Pin rails
to right side bar" global toggle unchanged; drawer hidden whenever the full rail is not
displayed.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: existing journal posting handler, GL posting,
period locking, fiscal-year lock, COA mappings, notification/export/print handlers,
search/filter/pagination params, auth/permissions and all routes remain EXACTLY as-is.
Reversal posting reaches the ledger ONLY through the existing journal posting handler.

==================== 0 · DISCOVERY ====================
0.1 Inventory reversal routes/pages + handlers (if any) and the transaction types that can
be reversed (Journal/Payment/Receipt/Invoice/Purchase/Bank/Payroll/Loan).
0.2 List CURRENT controls + handlers per page (drives §16 audit).
0.3 Locate: journal posting handler, period/fiscal lock state, dependent-transaction
graph (invoice→payment→reconciliation), user roles, notification channels.
0.4 Locate user-preference storage (rail prefs) + header Favorites menu.

==================== 1 · TOKENS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red-2:#b91c1c; --green:#15803d; --amber-2:#b45309; --steel:#46708C.
html,body{overflow-x:clip}. Text rem per app rule. prefers-reduced-motion respected.

==================== 2 · BADGES + BUTTON COLOUR SYSTEM ====================
2.1 Status badges: Pending amber; Approved/Posted green; Rejected red; Reversed/Cancelled
gray; Needs-Clarification amber. Type chips per transaction type.
2.2 BUTTON COLOUR SYSTEM — every filled button MUST set a SOLID background-color FIRST with
the gradient layered on top (background-color + background-image) so CTAs are ALWAYS
visible even if gradients are stripped/overridden (white text never on transparent):
  .btn-cta   primary dark-teal  bg-color #0c3539 (Search, Request Reversal,
             Authorization Rules, ＋ Add Rule).
  .btn-sec   secondary teal     bg-color #128F8E (Select, View Pending, My Queue,
             Reversal History, Export).
  .btn-approve green            bg-color #15803d (Approve, Approve Reversal).
  .btn-reject  red              bg-color #b91c1c (Reject).
  .btn-warn   amber             bg-color #b45309 (Request More Info).
  .btn-ghost  neutral light-teal bg-color #e8f0f0 (Cancel, View, Export) — never blank.

==================== 3 · PAGE INVENTORY — BUILD ALL (no skips) ====================
rev.create        Search Transaction + Request Reversal.
rev.index         Reversals Dashboard + List.
rev.auth          Authorization Dashboard.
rev.auth.queue    Pending Reversal Requests (approval queue).
rev.auth.show     Reversal Review + Authorize.
rev.rules         Authorization Rules + Permissions + Audit.

==================== 4 · SEARCH + REQUEST (rev.create) ====================
4.1 SEARCH card: Transaction Date / Reference Number / Transaction Type (Journal/Payment/
Receipt/Invoice/Purchase/Bank/Payroll/Loan/Other) / [🔍 Search CTA].
4.2 RESULT table: Reference/Date/Type/Description/Created By/Amount/Status + [Select].
4.3 PREVIEW: Original Entry (Account/Debit/Credit) + meta (Customer/Supplier, Branch,
Cost Centre, Currency, Attachments, Audit history).
4.4 REQUEST card: Reversal Reason (mandatory textarea) / Reversal Date (same-as-original /
current / user-selected-if-permitted) / Reversal Method (Full / Partial + amount).
[Request Reversal CTA][Cancel Request ghost]. On submit → status Pending Authorisation,
notify approvers. Enforce §10 restrictions before allowing request.

==================== 5 · REVERSALS DASHBOARD + LIST (rev.index) ====================
5.1 KPI cards: Pending Reversals / Approved Today / Rejected This Month / Total Reversed
Amount. [⇩ Export Report].
5.2 REQUESTS table: Request № / Txn Ref / Txn Date / Type / Description / Amount /
Requested By / Request Date / Status / actions (👁 / Approve). Filters: date range,
status, user, transaction type.

==================== 6 · AUTHORIZATION DASHBOARD (rev.auth) ====================
6.1 KPI cards: Pending Requests / Approved Today / Rejected Today / Amount Pending /
Approved This Month.
6.2 Quick actions: [View Pending][My Approval Queue][Reversal History]
[Authorization Rules CTA].

==================== 7 · PENDING QUEUE (rev.auth.queue) ====================
7.1 Filters: Date Requested / Transaction Date / Type / Branch / Requesting User /
Amount Range / Status (Pending/Approved/Rejected/Cancelled/Needs-Clarification).
7.2 TABLE: Request № / Txn Ref / Txn Date / Type / Description / Amount / Requested By /
Request Date / Status / actions (👁 View / Approve / Reject).

==================== 8 · REVIEW + AUTHORIZE (rev.auth.show) ====================
8.1 Multi-level approval chips (Requested → Supervisor → Finance Manager → CFO(>500K) →
Reversed) reflecting reversal_authorization_requests levels.
8.2 TWO-COLUMN: Original Entry (Posted badge) vs Proposed Reversal (auto-generated mirror
entry, "mirror entry" chip).
8.3 REQUEST INFO: Requested By / Request Date / Reason / Attachments (disabled inputs).
8.4 ACTIONS: [✓ Approve Reversal green][✕ Reject red][? Request More Info amber].
  Approve: confirmation prompt; on confirm → (1) create reversal journal, (2) post to GL
  via existing handler, (3) original POSTED→REVERSED, (4) generate REV-2026-xxxxxx,
  (5) record approval audit.
  Reject: mandatory rejection reason → status Rejected.
  Request More Info: status PENDING→NEEDS-CLARIFICATION; requester responds + resubmits.
8.5 WARN: approval effect note. ERROR card: blocked if already reversed / period closed /
year locked / dependent transactions exist.

==================== 9 · RULES + PERMISSIONS + AUDIT (rev.rules) ====================
9.1 AUTHORIZATION RULES table: Amount Range → Approver → Levels (0–50K Accountant 1;
50K–500K Finance Manager 1; >500K CFO Multi). [＋ Add Rule CTA].
9.2 USER PERMISSIONS table: Accountant(request/view-own/cancel) / Finance Manager
(approve/reject/clarify) / Senior Approver-CFO(high-value) / Auditor(view all) /
Administrator(configure).
9.3 AUDIT TRAIL table: Action(Requested/Reviewed/Approved/Posted to GL)/User/Date/Remarks;
[⇩ Export].

==================== 10 · ENGINE / BUSINESS RULES ====================
10.1 NEVER DELETE: posted transactions remain permanently; reversal creates a linked
opposite entry; original status POSTED→REVERSED; reversal ref REV-… linked to original.
10.2 RESTRICT RIGHTS per §9.2 roles.
10.3 REVERSAL RESTRICTIONS (block request & approval): already reversed; accounting period
closed; financial year locked; dependent transactions exist (e.g. Invoice→Payment→
Reconciliation completed) until dependents handled.
10.4 PARTIAL reversal reverses only the selected amount; full reverses the whole entry.
10.5 PROPOSED REVERSAL auto-generated as the exact mirror (swap Dr/Cr) of the original.
10.6 MULTI-LEVEL approval driven by reversal_authorization_rules (amount→role→levels).
10.7 NOTIFICATIONS (system/email/SMS-optional): submitted / pending / approved / rejected /
clarification requested.

==================== 11 · DATABASE SCHEMA ====================
transaction_reversal_requests: id, original_transaction_id, reference_number, requested_by,
request_date, reason, status, approved_by, approved_date, rejection_reason.
transaction_reversals: id, original_transaction_id, reversal_transaction_id, reversal_date,
amount, created_by, created_at.
reversal_authorization_requests: id, reversal_request_id, approval_level, assigned_to,
status, comments, approved_by, approved_date, created_at.
reversal_authorization_rules: id, transaction_type, minimum_amount, maximum_amount,
required_approvals, approver_role, branch_id, active.
reversal_approval_history: id, request_id, action, performed_by, remarks, date_time.
audit_logs: who requested/approved/rejected, date/time, IP, changes.

==================== 12 · GENERIC APPROVAL ENGINE NOTE ====================
Design the authorization workflow as a reusable approval engine (same pattern later for
payment/purchase/journal/expense/payroll/master-data approvals).

==================== 13 · RAILS REGISTRY (per page; rails unchanged) ============
rev.create → Quick Nav [Search, My Requests, Reversal History].
rev.index → Quick Nav [Pending, History, Request Reversal].
rev.auth → Quick Nav [My Queue, Authorization Rules, Reversal History].
rev.auth.queue → Quick Nav [My Queue, Rules, Authorization Dashboard].
rev.auth.show → Quick Nav [Approve, Reject, Pending Queue].
rev.rules → Quick Nav [Rules, Audit, Authorization Dashboard].

==================== 14 · ACCESSIBILITY / RESPONSIVE ====================
14.1 aria: approval chips aria-current; confirm dialog role=alertdialog; reason fields
required; focus rings #94a3b8; tables th scope.
14.2 ≤1000px g4/g2 collapse; ≤768px slim rail hidden; tables horizontal-scroll; no
horizontal PAGE scrollbar at 1280/1024/768.

==================== 15 · CONSTRAINTS ====================
No changes to rails feature or other modules; no posting/period/lock handler changes; no
new packages; ONE shared component/CSS per pattern; no hardcoded sample data; NEVER delete
posted transactions; DO NOT SKIP ANY PAGE (§3); filled buttons use §2.2 solid+gradient.

==================== 16 · VERIFY (EVERY PAGE) ====================
16.1 ROUTE CHECK: all six routes exist, render, reachable.
16.2 ACTION AUDIT: every button (search, select, request/cancel, approve/reject/clarify,
add rule, export, view) triggers the SAME handler/route as pre-implementation where it
existed (spot-click each).
16.3 RULES: request blocked when §10.3 conditions hold; approve creates mirror journal +
posts + flips status + generates REV ref + audit; reject requires reason; clarification
round-trips; partial reversal amounts correct.
16.4 BUTTONS: all CTAs render with visible solid backgrounds (§2.2) in light + dark.
16.5 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as rails.html
on these and all other pages; pages render §13 registries.
16.6 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; schema migrations; page-route table (all six, confirmed built);
action-mapping table; rail registry per page; confirmation rails + all existing
functionality unchanged, posted transactions never deleted, and NO PAGE SKIPPED.