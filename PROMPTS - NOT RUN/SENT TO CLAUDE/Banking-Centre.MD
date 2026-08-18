BANKING CENTRE MODULE — FULL IMPLEMENTATION SPEC "AS DESIGNED IN THE MOCKUP"
(DASHBOARD / BANK ACCOUNTS / ACCOUNT REGISTERS / FUNDS TRANSFER / DEPOSITS / CHEQUES /
PETTY CASH / CASH-FLOW REPORTS / BANKING SETTINGS). Rebuild the Banking Centre as the
workspace for accounts, deposits, withdrawals, transfers, cheque management and petty
cash control — integrated with General Ledger, Accounts Payable, Accounts Receivable,
Payroll, Sales, Purchasing and Cash Flow so every financial transaction updates the
books. ALL VALUES INLINE; no mockup dependency.

OUT OF SCOPE — ALREADY BUILT, DO NOT REBUILD: Cash Position module and Bank Reconciliation
module. Only LINK to them (dashboard "Reconcile Account" button, register "Reconcile"
button, report cards marked "built → Open", rail items). Do not duplicate their logic.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY
as implemented and applies to every banking page per the registry in §12: resting slim
icon rail with teal Expand; drawer with pin (true toggle, remembered per page) + X at
top-right; Favorites "Pin rails to right side bar" global toggle unchanged.

HARD GUARD — DO NOT CHANGE ANY FUNCTIONALITY: posting handlers, GL account mappings,
approval workflows, tax settings, export/print handlers, search/filter/pagination
params, auth/permissions and all routes remain EXACTLY as-is. Every pre-existing button
keeps its handler; this spec re-styles/re-arranges UI only. Balances are ALWAYS computed
from posted ledger entries — never stored.

==================== 0 · DISCOVERY ====================
0.1 Inventory banking routes/pages + handlers: dashboard, bank accounts index/create/
edit/show, registers, transfers, deposits, cheques (issued + received), petty cash
(funds + transactions), reports, settings.
0.2 List CURRENT controls + handlers per page (drives §16 audit): account
Add/Edit/Deactivate/Import/Export, register New Transaction/Print/Export, transfer
Approve/Cancel/Voucher, deposit Post/Slip/Reverse, cheque Write/Print/Void/Reprint/
Deposit/Mark Cleared/Return, petty Record Expense/Approve/Replenish/Print Voucher.
0.3 Locate: chart-of-accounts bank/cash/petty-cash accounts, approval settings,
transaction limits, cheque numbering, custodians, currency rates, notification
handlers.
0.4 Locate user-preference storage (rail prefs live there) + header Favorites menu.

==================== 1 · TOKENS / DIMENSIONS ====================
App tokens: --deep-1:#17565d; --deep-2:#0c3539; --deep-3:#0a2e32; --sec:#128F8E;
--sec-2:#149897; --ink:#0B2A2D; --border:#dceaea; --line:#e2ecec; --muted:#5f7476;
--faint:#8aa5a7; --red:#dc2626; --red-2:#b91c1c; --green:#15803d; --amber:#d97706;
--amber-2:#b45309; --steel:#46708C. html,body{overflow-x:clip}. Text rem per app rule.
prefers-reduced-motion respected.

==================== 2 · STATUS BADGES + TYPE CHIPS ====================
2.1 Badges (pill + dot): Active/Posted/Cleared/Completed/Approved = mint gradient
(#ecfdf3→#dcf5e7, rgba(22,163,74,.28), #15803d, dot #22c55e); Pending Approval/
Pending/Clearing rgba(217,119,6,.10)/.35/#b45309 (dot #d97706); Draft/On Hand
rgba(17,69,75,.07)/.2/#11454b; Issued/Transfer steel rgba(70,112,140,.10)/.4/#46708C;
Cancelled/Reversed/Deactivated gray rgba(138,165,167,.15)/.5/#5f7476; Outstanding/
Overdue red rgba(185,28,28,.08)/.3/#b91c1c.
2.2 Type chips (.tchip): Deposit/Interest green tint; Cheque/Withdrawal red tint;
Transfer steel tint; Bank Charge gray; Expense red tint; Replenishment green tint.
2.3 Amount coloring: credits/debits colored green/red in registers; balances bold;
account-type chips: Current gray, Savings teal tint, Mobile Money amber tint,
Petty Cash gray, Cash steel.

==================== 3 · DASHBOARD (banking.dashboard) ====================
3.1 NON-sticky page head: h1 "Banking Centre" + sub; right actions in order:
[🔄 Reconcile Account ghost → LINKS to built reconciliation][📊 Reports ghost]
[🧾 Write Cheque ghost][💰 Make Deposit ghost][💸 Transfer Funds secondary]
[➕ Add Bank Account CTA].
3.2 KPI ROW 1 (4): hero Total Bank Balances (teal gradient; sub "{n} bank + {n} mobile
money") · Cash on Hand (sub "{n} petty cash funds") · Available Funds (sub "after
outstanding cheques") · Pending Deposits (amber; "{n} · {value}" + "Review →").
3.3 KPI ROW 2 (4): Outstanding Cheques (red; "{n} · {value}" + "Cheque register →") ·
Deposits This Month · Transfers This Month (sub "{n} pending approval") · Bank Charges
(30d) (sub "auto-posted on reconcile").
3.4 ANALYTICS ROW (2 cards ≤1000px stack): "Account Balances by Bank" (label + bar +
value rows per account; "Bank Accounts →") · "Recent Transactions" (type chip + ref ·
party · ±amount · date feed; "Registers →") + "Upcoming Payments" list (date ·
description · amount from scheduled payables/payroll).

==================== 4 · BANK ACCOUNTS (banking.accounts) ====================
4.1 Page head: h1 "Bank Accounts" + sub; right [⋯ More:  Import Bank Statement ·
📤 Export Account Details · ⚙ Banking Settings] + [➕ Add Bank Account CTA].
4.2 TABLE: [Account name (700) + masked № (mono)] / Type chip (Current/Savings/Cash/
Mobile Money/Petty Cash) / Branch / CCY / Balance (bold) / GL Account (mono) / Status
badge / actions (👁 View · ✎ Edit · ⋯: Deactivate, Delete (blocked with transactions),
View Register, Import Statement).
4.3 ACCOUNT DETAILS panel (on View/select): g3 read grid: Bank Name / Account Number /
Branch / Currency / Bank GL Account / Opening Balance · Date / Access Permissions /
Approval Requirement (e.g. "Transfers > 1M require approval") / Transaction Limit;
header [✎ Edit Account][⏸ Deactivate][📒 View Register].
4.4 CREATE/EDIT form fields per spec (bank name, account name, number, branch,
currency, type, GL link, opening balance/date, permissions, approval, limits) via
existing handlers.

==================== 5 · ACCOUNT REGISTER (banking.register) ====================
5.1 Sticky head: h1 "Register — {account} {masked}" + sub; right [🔄 Reconcile ghost →
built][⋯ More: 🖨 Print Register · 📗 Export Excel · 📕 Export PDF] + [＋ New
Transaction CTA].
5.2 CONTROLS: search (reference/description) + Type select (All/Deposit/Withdrawal/
Transfer/Cheque/Bank Charge/Interest) + From/To dates.
5.3 TABLE: Date / Reference (mono) / Description / Type chip / Debit (green) / Credit
(red) / Balance (bold, RUNNING — computed) / Status badge. Pagination existing.
5.4 Running balance recomputed from opening + posted entries; no stored balances.

==================== 6 · FUNDS TRANSFER (banking.transfers) =====================
6.1 Page head (combined Transfers & Deposits page as mocked, or separate — follow
existing routing): h1 + right [💰 New Deposit secondary][💸 New Transfer CTA].
6.2 TRANSFERS CARD: table Transfer № (mono) / Date / From → To / Amount (bold) /
Charges / Status badge / actions: Pending → [Approve][Cancel]; Completed → [Voucher];
Cancelled → [View]. [🕘 View History] in header.
6.3 TRANSFER FORM: From Account / To Account / Amount / Date / Reference /
Description / Transfer Charges; multi-currency uses existing rate table; approval
workflow per settings (§11); Approve posts DR to-account CR from-account (+ charges →
bank charges expense) via existing handlers.

==================== 7 · DEPOSITS (banking.deposits) ====================
7.1 DEPOSITS CARD: header status chips (Draft / Posted / Reversed counts); table
Deposit № (mono) / Date / Bank Account / Source (customer payment / cash deposit /
other income / loan proceeds / owner contribution) / Amount (bold) / Status badge /
actions: Draft → [Post][Slip]; Posted → [Slip][Reverse]; Reversed → [View].
7.2 DEPOSIT FORM: Deposit Date / Bank Account / Amount / Source / Reference /
Description / Attachments (deposit slip chips); Post posts DR bank CR source account
(customer payment routes through existing AR receipt handler where linked).

==================== 8 · CHEQUES (banking.cheques) ====================
8.1 Page head: h1 "Cheques" + right [＋ Add Received Cheque secondary][🧾 Write Cheque
CTA].
8.2 ISSUED CHEQUES CARD: header chips Outstanding (red "{n} · {value}") / Cleared /
Cancelled; table Cheque № (mono) / Date / Payee / Amount (bold) / Bank Account /
Status badge (Draft/Printed/Issued/Cleared/Cancelled) / actions: Draft → [Print]
[Void]; Issued → [Reprint]; Cleared/Cancelled → [View]. Cheque № from settings
numbering; print uses existing cheque format handler.
8.3 RECEIVED CHEQUES CARD: table Cheque № (mono) / Date / Customer / Amount (bold) /
Deposited To (em-dash when on hand) / Status badge (On Hand/Clearing/Cleared/Returned) /
actions: On Hand → [Deposit][Return]; Clearing → [Mark Cleared]; Cleared → [View].
8.4 ACCOUNTING (existing handlers): issued cheque cleared → CR bank; received on hand →
cheques-on-hand asset; deposit → DR bank (clearing); cleared/returned post counterparts.

==================== 9 · PETTY CASH (banking.petty) ====================
9.1 Page head: h1 "Petty Cash" + right [＋ Create Petty Cash Fund CTA].
9.2 FUND CARDS (.mcards 2-col): fund name + code chip; sub "Custodian: {name} · Max
balance {K} · Approval limit {K}"; fields Current Balance (teal 800) / Spent This
Month; right [Replenish secondary][Print Voucher ghost].
9.3 TRANSACTIONS CARD per fund: header [💸 Record Expense][📤 Request Reimbursement];
table Date / Type chip (Expense red / Replenishment green / Cash Issue steel) /
Description · Category / Custodian / Amount (±colored) / Receipt (📎 chip) / Status
(Pending/Approved/Posted) / actions: Pending → [Approve]; Approved/Posted → [Voucher].
9.4 ACCOUNTING (existing handlers): approved expense → DR expense category CR petty
cash; replenishment → DR petty cash CR bank; approval limits per custodian enforced.

==================== 10 · REPORTS + SETTINGS (banking.reports) ====================
10.1 REPORT CARDS (grid 3 cols): Cash Position Report (chip "built" + Open → existing
module) · Daily Cash Report · Bank Balance Report · Transaction Register · Deposit
Report · Cheque Register · Transfer Report · Petty Cash Summary (incl. Custodian
Report) · Bank Reconciliation Report (chip "built" + Open → existing). Each non-built
card: PDF + Excel chips + Open →; existing report pages if present else MINIMAL pages
using the system report pattern; [Generate][Print][Export PDF][Export Excel].
10.2 SETTINGS CARD (g3 read/edit): Bank Transaction Numbering (BNK-{yyyy}-{seq:5}) /
Cheque Numbering (CHQ-{seq:6} + next №) / Payment Approval Workflow (thresholds) /
Default Bank Accounts (receipts/payroll mappings) / Currency Settings / Reconciliation
Rules (auto-match tolerance, auto-post charges/interest) — existing settings handlers.

==================== 11 · APPROVALS & SECURITY (settings-driven) =================
11.1 Transfers above threshold and petty expenses above custodian limit require
approval (existing workflow handlers); account access permissions per §4.3 enforced on
register/transaction actions; transaction limits validated before posting.
11.2 Negative bank balances prevented per settings (warn/block) via listener on
existing posting handlers.

==================== 12 · RAILS REGISTRY (per page; rails feature unchanged) =======
banking.dashboard → Quick Nav [Bank Accounts, Transfer Funds, Make Deposit,
Write Cheque, Cash Flow Reports].
banking.accounts → Views [All Accounts(active), Active, Deactivated] + Quick Nav
[Add Bank Account, Registers, Reconcile (built)].
banking.register → Quick Nav [New Transaction, Reconcile (built), Print Register,
Bank Accounts].
banking.transfers → Views [All Transfers(active), Pending Approval, Completed,
Cancelled] + Quick Nav [New Transfer, New Deposit, View History].
banking.deposits → Views [All Deposits(active), Draft, Posted, Reversed] + Quick Nav
[New Deposit, Bank Accounts].
banking.cheques → Views [Cheque Register(active), Outstanding, Cleared, Cancelled] +
Quick Nav [Write Cheque, Add Received Cheque].
banking.petty → Quick Nav [Record Expense, Replenish, Create Fund, Petty Cash Reports].
banking.reports → Quick Nav [Cash Position (built), Bank Balance, Cheque Register,
Banking Settings].

==================== 13 · ACCESSIBILITY / RESPONSIVE ====================
13.1 aria: status boxes/tabs aria-pressed where present; ⋯ menus aria-haspopup;
focus rings #94a3b8; tables th scope; amount colors backed by text/signs.
13.2 ≤1000px KPI 2-col + analytics stack; ≤768px slim rail hidden, fund cards 1-col,
tables horizontal-scroll inside cards; no horizontal PAGE scrollbar at 1280/1024/768.

==================== 14 · CONSTRAINTS ====================
No changes to rails feature or other modules; NO rebuild of Cash Position or Bank
Reconciliation (link only); no posting/GL handler changes; no new packages; ONE shared
component/CSS per pattern; no hardcoded sample data (live ledger only); no stored
balances.

==================== 15 · VERIFY (EVERY PAGE) ====================
15.1 ACTION AUDIT: every button (dashboard six actions, account Add/Edit/Deactivate/
Import/Export/View Register, register New/Print/Export/Reconcile-link, transfer
Approve/Cancel/Voucher/History, deposit Post/Slip/Reverse, cheque Write/Print/Void/
Reprint/Deposit/Mark Cleared/Return, petty Record/Approve/Replenish/Print Voucher/
Create Fund, report Opens incl. "built" links, settings edits) triggers the SAME
handler/route as pre-implementation (spot-click each).
15.2 MATH: register running balance = opening + Σ(debits − credits); Available Funds =
bank balances − outstanding cheques; fund current balance = replenishments − approved
expenses; tfoot/chip counts = table rows.
15.3 CONTROLS: approvals gate transfers/expenses per thresholds; limits and negative-
balance rules enforced; deletes blocked with transactions; "built" cards open existing
modules (no duplication).
15.4 RAILS: slim rail + drawer + per-page pins + global pin behave exactly as
rails.html on these and all other pages; banking pages render §12 registries.
15.5 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; action-mapping table (old control → new location → handler
confirmed same); status/chip table; rail registry per page; accounting-entry mapping
per flow; confirmation Cash Position + Bank Reconciliation untouched (linked only),
rails + all existing functionality unchanged, balances always ledger-derived.