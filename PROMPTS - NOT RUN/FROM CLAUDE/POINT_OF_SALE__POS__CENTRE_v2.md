POINT OF SALE (POS) CENTRE — FULL IMPLEMENTATION SPEC (SELF-CONTAINED) — v2
(15 PAGES: DASHBOARD / SALES SCREEN / RECEIPTS / RETURNS / CUSTOMERS / PRODUCTS /
PRICE LISTS / PROMOTIONS / PAYMENT METHODS / REGISTERS / SHIFTS / CASHIERS / OFFLINE /
REPORTS / SETTINGS). The complete reference mockup is embedded in APPENDIX A — no
external file required.
CRITICAL: existing payment options and search features are UPDATED, NOT REPLACED.
RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every POS page per the registry in §15: resting slim icon rail
with teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites
"Pin rails to right side bar" global toggle unchanged; drawer hidden whenever the full
rail is not displayed.
BRANCH SCOPING (ASSUMPTION — confirm before building, matches the rule already applied
elsewhere in this system): only users at Head Office can see POS data — dashboard KPIs,
receipts, customers, products/stock, reports, registers, shifts, and cashier
activity/audit — across every branch. Users at any other branch see only their own
branch's data on every one of these pages, regardless of role. If this system does not
already have a head-office/branch flag from the existing accounting system's branch
model, reuse it rather than creating a second, parallel one. If this assumption is wrong
for POS specifically (e.g. all branches should see each other's sales), stop and confirm
before building rather than guessing.
HARD GUARD — DO NOT REPLACE, ONLY UPDATE: (a) the type-ahead product search (Up/Down to
navigate, Enter to select) and customer search with search-glass buttons; (b) the branch
picker; (c) the four existing payment methods and their clearing accounts (Bank Transfer→
1010 Petty Cash, Cash→1060 Cash-in-Drawer, Card→1070 Card Clearing, Mobile Money→1080
Mobile Money Clearing) with their Ref-Required flags; (d) the Add-Terminal form +
terminals table. Extend these (credit + split, fees, GL links) but never remove them.
All other existing handlers (inventory, customers, GL posting, banking) remain as-is;
POS posts to the ledger ONLY through the existing journal posting handler.

==================== 0 · DISCOVERY ====================
0.0 SAFETY NET — before touching any existing file: create a dedicated branch (or commit
current working state if not using git) so this entire 15-page change set can be reverted
as a whole if the posting-engine changes interact badly with the existing GL handler. Do
not begin editing files until this checkpoint exists. Report the branch/commit reference
used.
0.1 Locate existing POS checkout, terminals, payment-methods pages + handlers; product &
customer search endpoints; inventory stock/COGS logic; GL posting handler; receipt
numbering; PDF/receipt template (Invoice INV-PROBE-001 is the brand reference).
0.2 List CURRENT controls + handlers per page (drives §18 audit).
0.3 Locate user-preference storage (rail prefs) + header Favorites menu.
0.4 CHART OF ACCOUNTS CHECK: confirm against the existing accounting system's chart of
accounts whether these posting targets already exist: Sales Revenue 4000, COGS 5000,
Inventory 1300, the four existing clearing accounts (1010/1060/1070/1080), AR 1100, a Cash
Over/Short (register variance) account, and card/mobile fee expense accounts. Report which
of these already exist (use them, exact code/name) versus which are missing (propose an
exact code/name consistent with the existing numbering scheme and confirm before creating).
Never invent an account code that might collide with an existing one without checking first.
0.5 If anything discovered in 0.1–0.4 conflicts with an assumption made elsewhere in this
spec (e.g. a payment method's clearing account differs from what's listed in §12, or the
existing search implementation doesn't match the Up/Down/Enter pattern described), stop and
report the discrepancy rather than silently overriding either the existing code or this
spec — flag it in the final report per §18 and ask before deciding which wins.

==================== 1 · TOKENS / TYPOGRAPHY ====================
Inter family (400/500/600/700/800) per system typography spec; tokens --deep-1:#17565d;
--deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897; --ink:#0B2A2D; --border:#dceaea;
--line:#e2ecec; --muted:#5f7476; --faint:#8aa5a7; --red-2:#b91c1c; --green:#15803d;
--amber-2:#b45309. tabular-nums on all money/qty columns. html,body{overflow-x:clip}.

==================== 2 · PAGE INVENTORY — BUILD ALL 15 (no skips) =================
pos.dashboard  POS Dashboard.
pos.checkout   POS Sales Screen (main selling page).
pos.receipts   Sales Receipts.
pos.returns    Sales Returns / Refunds.
pos.customers  POS Customers.
pos.products   Products / Inventory Items.
pos.pricelists Price Lists.
pos.promotions Discounts & Promotions.
pos.payments   Payment Methods setup.
pos.registers  Cash Register / Terminal Management.
pos.shifts     Shift Management.
pos.cashiers   Cashier / User Management.
pos.offline    Offline Sales Mode.
pos.reports    POS Reports.
pos.settings   POS Settings.
UI/UX REFERENCE: APPENDIX A (embedded mockup) — replicate layout, KPIs, tables, chips,
buttons, badges and rail icons exactly.

==================== 3 · POS DASHBOARD (pos.dashboard) ====================
3.1 KPIs (6): Today's Sales (hero, +12% vs yesterday) / Transactions (64 · 62 completed ·
2 void) / Avg Sale Value (K7,602) / Cash Collected (K212,000 · drawer 1+2) / Card+Mobile
(K198,500 · card 121K · mobile 77.5K) / Outstanding Credit (K76,000 · 4 credit sales).
3.2 Panels: Top Selling Items (Maize Flour 10kg 48/432,000 · Cooking Oil 2L 35/315,000 ·
Sugar 2kg 30/180,000) · Low Stock (Bread 6 left · Milk 1L 9 left, red) · Sales by
Cashier (M. Banda 212,000 · P. Phiri 198,500) · Sales by Branch.
3.3 Buttons: [➕ New Sale CTA][Open Register sec][Close Register][View Reports]
[Manage Products][Transactions].

==================== 4 · SALES SCREEN (pos.checkout) ====================
4.1 LEFT "1 · Add Items": KEEP type-ahead product search (Up/Down/Enter) + search-glass +
[📷 Scan Barcode]; Qty + [Add]; Favourites★/Recent🕘 quick chips; cart table Product/UOM/
Qty/Price/Discount/Tax/Total with per-line ✎ edit/🗑 remove + notes; [⏸ Hold Sale].
Sample lines: Maize Flour 10kg bag 2×9,000 =18,000 · Cooking Oil 2L bt 1×9,000 disc 500
=8,500.
4.2 RIGHT "2 · Payment": KEEP customer type-ahead search + "Walk-in Customer" default +
[＋ Add Customer][History]; payment method chips = existing four (Cash/Card/Mobile Money/
Bank Transfer) PLUS new Credit + Split (updated, not replaced); Reference field; totals
Subtotal K27,000 / Discount −K500 / Tax K0 / Total Due K26,500; [Complete Sale CTA][🖨][].
Disabled state "ADD ITEMS FIRST" when cart empty.
4.3 Split payment: allocate amounts across ≥2 methods summing to Total Due; each leg posts
to its clearing account. Credit sale: posts to 1100 AR, checks customer credit limit
(block over-limit without supervisor approval — see §4.4 for the approval mechanism).
4.4 SUPERVISOR APPROVAL MECHANISM (applies wherever "supervisor approval" or a cashier
permission limit is referenced — over-credit here, and discount/refund/void limits in
§13.3): a blocked action shows an inline approval modal requiring a second, distinct
supervisor/admin login (PIN or credential re-entry — reuse the existing auth session
mechanism, do not build a new one) before the action proceeds. Record the approving user,
the cashier who triggered it, the action, and a timestamp in the audit trail (§18.5).
Never allow the same cashier session to self-approve — the approving user must have a
supervisor/admin-level permission distinct from the triggering cashier.

==================== 5 · SALE POSTING ENGINE (on Complete Sale) ====================
5.1 Decrement inventory qty per line; compute COGS = Σ(cost × qty).
5.2 Post ONE journal via existing handler: DR payment clearing account(s) (cash 1060 /
card 1070 / mobile 1080 / bank 1010 / AR 1100) for amounts received (cash net of change)
DR COGS 5000 + DR discount/fee accounts; CR Sales Revenue 4000 + CR Tax Payable + CR
Inventory 1300 (or COGS/Inventory per settings). Card/mobile fees (2.5%/1%) to fee expense.
BALANCE GUARD: before committing, assert sum(debit lines) == sum(credit lines) for every
journal this engine posts (sale, void, refund, register-close variance, offline sync). If
they don't balance, abort the whole transaction (do not partially post, do not decrement
stock) and surface a clear error — an unbalanced journal must never reach the ledger
silently. Apply the same guard to void/refund reversals (§6.2/§7.2) and offline sync
posting (§14).
5.3 Update customer record (purchases, balance for credit), cashier/shift/register
totals, receipt record; emit receipt.
5.4 Receipt PDF/thermal uses Invoice brand chrome: "C" tile + CamelotBooks + tagline,
letter-spaced title (RECEIPT), meta (Receipt №/Date/Cashier), line table, TOTAL,
"Amount in words … only.", footer "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567 · Page 1 of 1"; tax-compliant (tax breakdown shown).

==================== 6 · RECEIPTS (pos.receipts) ====================
6.1 Search receipt №/customer (KEEP search pattern). Table: Receipt №/Date/Customer/
Cashier/Amount/Method chip/Status (Completed/Pending/Cancelled/Refunded).
Sample rows: RCP-006412 15 Aug 10:42 Walk-in M. Banda 26,500 Cash Completed (👁🖨↩) ·
RCP-006411 Beta Industries P. Phiri 76,000 Credit Pending (👁↩) · RCP-006410 Walk-in
18,000 Mobile Refunded (👁🖨).
6.2 Row actions: 👁 View · 🖨 Print · Reprint · ✉ Email ·  Refund · Void (permission +
audit). Void posts reversing journal + restocks.

==================== 7 · RETURNS (pos.returns) ====================
7.1 Two-step: search original sale (KEEP search) + reason chips (Damaged/Wrong item/
Customer changed mind) + return type chips (Cash refund / Credit note / Exchange); select
returned items + restock flag (Cooking Oil 2L sold 1 return 1 restock Yes).
7.2 Actions: [New Return CTA][Approve Return][Process Refund][Print Return Receipt].
Approve posts reversing revenue/tax + restock inventory; refund pays via chosen type
(cash 1060 / credit note to AR/credit balance / exchange creates new sale leg).
7.3 PARTIAL REFUND ALLOCATION (split/credit originals): when the original sale was a split
payment and the return is partial (not a full reversal), allocate the refund amount
pro-rata across the original payment legs in the same proportion they were originally paid
(e.g. a sale paid 60% cash / 40% card refunds a K1,000 return as K600 cash / K400 card).
When the original sale was a credit sale, a partial refund reduces the customer's AR
balance by the refunded amount rather than paying out cash, unless "Cash refund" is
explicitly chosen, in which case it pays cash and leaves the AR balance untouched. Round
allocation to the nearest currency unit and adjust the largest leg to absorb any rounding
remainder so the refund total still ties out exactly.

==================== 8 · CUSTOMERS (pos.customers) ====================
Table: Customer/Phone/Credit Limit/Balance/Status; actions 👁 View/✎ Edit/💰 Receive
Payment/Statement. [Add Customer CTA][Export]. Sample: Beta Industries +265 991 000 111
limit 500,000 balance 76,000 Active · Walk-in 0/0 Active. History tab: purchases/returns/
payments/outstanding.

==================== 9 · PRODUCTS (pos.products) ====================
Table: SKU/Barcode/Category/Cost/Price/Stock (red when low); actions ✎ Edit/ Print
Barcode/ View Movement; [Add Product CTA][Adjust Stock]. Sample: SKU-0001 Maize Flour
10kg Staples 7,000/9,000 stock 120 · SKU-0002 Bread Loaf Bakery 1,200/1,800 stock 6 (red).
Barcode scan resolves to product.

==================== 10 · PRICE LISTS (pos.pricelists) ====================
Lists: Retail (All customers 9,000 Active) / Wholesale (Min qty 10 8,200 Active) / VIP
(VIP group 8,600 Scheduled) with applies-to + effective dates + per-item prices;
[Create Price List CTA][Import][Export][Edit]. Checkout resolves price by customer
group/qty/effective date.

==================== 11 · PROMOTIONS (pos.promotions) ====================
Types: percentage / fixed / Buy-X-Get-Y / customer discount; rules: date range, min qty,
customer group. Samples: Weekend 5% off (Percentage, Active, [Pause]) · Buy 2 Get 1 Bread
(Buy X Get Y, Paused, [Activate]); [Create Promotion CTA]; View Usage. Auto-applies at
checkout when rules match; manual discount respects cashier permission/limit.

==================== 12 · PAYMENT METHODS (pos.payments) ====================
12.1 Add form (KEEP): Name/Type/Clearing Account 🔍/Settlement Bank Account 🔍/Requires
Reference checkbox/[Add].
12.2 Table (KEEP existing four rows exactly) + new Customer Credit→1100 AR; columns add
Fee + Status; Deactivate action. Rows: Bank Transfer→1010 Petty Cash Ref Yes Fee 0 ·
Cash→1060 Cash-in-Drawer Ref No 0 · Card→1070 Card Clearing Ref Yes 2.5% · Mobile Money→
1080 Mobile Money Clearing Ref Yes 1% · Customer Credit→1100 AR Ref No 0. Edit exposes GL
clearing/settlement links + fee % + change-calculation flag.

==================== 13 · REGISTERS / SHIFTS / CASHIERS ====================
13.1 REGISTERS: KEEP Add-Terminal form (Identifier/Name/Branch 🔍 picker/PIN Timeout) +
terminals table (Identifier/Name/Branch/PIN Timeout/Status/Deactivate); sample TILL-01
Front Counter Headquarters 10 min Active with [Open][Cash Count][Deactivate]; closing
computes expected vs counted cash → variance posted to Cash Over/Short account.
13.2 SHIFTS: [Start Shift][End Shift]; opening balance; sales during shift; cash
difference; [View Shift Report]. Samples: M. Banda 08:00 opening 50,000 sales 212,000
variance 0 · P. Phiri 08:00 50,000 198,500 (500).
13.3 CASHIERS: [Add Cashier]; permission matrix (Apply Discounts / Refunds / Void /
Change Prices) with approval limits; ⚙ View Activity (audit). Samples: M. Banda
Discounts Yes/Refunds No/Void No · P. Phiri Yes/Yes/No.

==================== 14 · OFFLINE (pos.offline) ====================
[Enable Offline Mode] (store transactions locally, continue selling); [Sync Transactions]
(idempotent, posts journals + stock on reconnect); "Pending sync: 0 transactions ·
locally stored sales synchronise automatically when connection returns." Conflict
handling: stock shortages flagged for review.
14.1 IDEMPOTENCY MECHANISM: each offline sale is assigned a client-generated UUID at the
moment it's captured locally (before any network round-trip). On sync, the server checks
that UUID against already-posted receipts before creating a journal or receipt record — if
it's already been posted, skip it silently (log as "already synced") rather than posting a
duplicate. This UUID, not receipt number or timestamp, is the sole duplicate-detection key,
since receipt numbers are only assigned on successful sync and timestamps can collide.

==================== 15 · RAILS REGISTRY (per page; rails unchanged) ============
pos.dashboard → Quick Nav [New Sale, Registers, Reports].
pos.checkout → Quick Nav [Scan, Hold, Receipts].
pos.receipts → Quick Nav [Receipts, Refund, Print].
pos.returns → Quick Nav [New Return, Receipts].
pos.customers → Quick Nav [Add Customer, Statements].
pos.products → Quick Nav [Add Product, Adjust Stock].
pos.pricelists → Quick Nav [Price Lists, Promotions].
pos.promotions → Quick Nav [Create Promotion, Usage].
pos.payments → Quick Nav [Add Method, Payment Methods].
pos.registers → Quick Nav [Registers, Shifts, Cash Count].
pos.shifts → Quick Nav [Start Shift, End Shift, Shift Report].
pos.cashiers → Quick Nav [Add Cashier, Permissions, Activity].
pos.offline → Quick Nav [Offline, Sync].
pos.reports → Quick Nav [Generate, Daily Sales, Cash Variance].
pos.settings → Quick Nav [Settings, Accounting Integration].

==================== 16 · REPORTS (pos.reports) ====================
Sales: Daily/Sales Summary/By Product/By Category/By Cashier/By Branch. Inventory:
Stock Sold/Fast Movers/Slow Movers/Stock Availability. Cash: Cashier/Register/
Payment Method/Cash Variance. Profit: Gross Profit/Product Profitability/Margin
Analysis. [Generate][Print][PDF][Excel].

==================== 17 · SETTINGS (pos.settings) ====================
General: receipt format (Thermal 80mm/A5), numbering RCP-{seq}, currency, tax. Hardware:
scanner/printer/drawer/customer display. Accounting integration: Sales 4000 / COGS 5000 /
Inventory 1300 / Cash 1060 / Bank / AR 1100. Security: user permissions, approval
limits, audit trail.

==================== 18 · VERIFY (EVERY PAGE) ====================
18.1 ROUTE CHECK: all 15 routes exist, render, reachable.
18.2 PRESERVATION: product type-ahead (Up/Down/Enter) + customer search + branch picker
work as before; existing four payment methods + clearing accounts intact; Add-Terminal +
terminals table intact (spot-test).
18.3 POSTING: Complete Sale decrements stock, computes COGS, posts correct multi-line
journal (clearing per method, split legs, credit→1100, fees, tax, revenue, inventory);
refund/void post reversals + restock; register close posts variance; offline sync posts
queued sales exactly once (verify by simulating a duplicate sync attempt — must not
double-post). Every journal posted (sale/void/refund/variance/offline) balances
debits=credits (§5.2 guard) — spot check with an intentionally malformed input to confirm
the guard actually rejects an unbalanced entry rather than posting it.
18.3b BRANCH SCOPING: a non-head-office user sees only their own branch's data on
dashboard/receipts/customers/products/reports/registers/shifts/cashiers; a head-office
user sees all branches (per the Branch Scoping rule near the top of this spec).
18.4 RECEIPTS: numbering sequential; PDF/thermal matches Invoice brand chrome + footer;
tax breakdown present.
18.5 PERMISSIONS: cashier limits enforced (discount/refund/void); over-credit blocked
without approval.
18.6 RAILS: slim rail + drawer + pins behave exactly as rails.html; pages render §15.
18.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
18.8 Pixel/UX parity with APPENDIX A on all 15 pages.
REPORT: safety-net branch/commit reference (§0.0); chart-of-accounts findings — which
posting accounts existed vs. were created (§0.4); any discrepancies flagged per §0.5 and
how resolved; files touched; page-route table (all 15); preservation confirmation (search +
payment methods + terminals updated not replaced); journal mapping table (method→clearing
account); balance-guard confirmation (§5.2/§18.3); supervisor-approval mechanism used
(§4.4); partial-refund allocation confirmation (§7.3); offline idempotency key used (§14.1);
branch-scoping confirmation (§18.3b); rail registry per page; confirmation rails + existing
functionality unchanged and NO PAGE SKIPPED.

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (verbatim) ====================
Replicate exactly. Shell: topbar "CBCamelotBooks · ES · Elvis Seyama" + nav
[Sales][POS][Inventory][Banking][Reports]; rails per §15.

--- 1 · POS Dashboard ---
H1 "POS Dashboard" · sub "Today's sales performance · Fri 15 Aug 2026".
Actions: [➕ New Sale CTA][Open Register sec][Close Register][View Reports]
[Manage Products][Transactions].
KPIs: Today's Sales K486,500 (+12% vs yesterday, hero) · Transactions 64 (62 completed ·
2 void) · Avg Sale Value K7,602 (per transaction) · Cash Collected K212,000 (drawer 1+2) ·
Card / Mobile K198,500 (card 121K · mobile 77.5K) · Outstanding Credit K76,000 (4 credit
sales).
Top Selling Items: Maize Flour 10kg 48/432,000 · Cooking Oil 2L 35/315,000 · Sugar 2kg
30/180,000. Low Stock & Sales by Cashier: Bread Loaf 6 left · Milk 1L 9 left ·
Cashier M. Banda 212,000 · Cashier P. Phiri 198,500. Rail ＋🗄📊›.

--- 2 · POS Sales Screen (pos.checkout) ---
H1 "POS Checkout — TILL-01" · sub "Shift open · Cashier E. Seyama · Register 1" ·
[⏸ Hold Sale].
LEFT "1 · Add Items": type-ahead product search "↑/↓ navigate · Enter select" + 🔍 +
[📷 Scan Barcode]; Qty + [Add]; ★ Favourites · 🕘 Recent chips.
Cart: Maize Flour 10kg | bag | 2 | 9,000 | 0 | 0 | 18,000 | ✎🗑 · Cooking Oil 2L | bt | 1 |
9,000 | 500 | 0 | 8,500 | ✎🗑.
RIGHT "2 · Payment": Customer 🔍 "Walk-in Customer" + [＋ Add Customer]·[History];
Payment Method chips: Cash / Card / Mobile Money / Bank Transfer / Credit / Split;
Reference; Subtotal K27,000 · Discount −K500 · Tax K0 · Total Due K26,500;
[Complete Sale CTA][🖨][✉]. Rail 📷›.

--- 3 · Sales Receipts (pos.receipts) ---
H1 "Sales Receipts" · sub "Completed sales · reprint · refund · void".
Table: RCP-006412 | 15 Aug 10:42 | Walk-in | M. Banda | 26,500 | Cash | Completed | 👁🖨↩ ·
RCP-006411 | 15 Aug 10:15 | Beta Industries | P. Phiri | 76,000 | Credit | Pending |
👁🖨↩ · RCP-006410 | 15 Aug 09:58 | Walk-in | M. Banda | 18,000 | Mobile | Refunded |
👁🖨. Rail 🧾›.

--- 4 · Sales Returns / Refunds (pos.returns) ---
H1 "Sales Returns" · sub "Return items · restock · refund via cash / credit note /
exchange" · [New Return CTA].
"1 · Original Sale": search original sale 🔍; Return Reason (Damaged / Wrong item /
Customer changed mind); Return Type chips (Cash refund / Credit note / Exchange);
[Approve Return][Process Refund][Print Return Receipt].
"2 · Returned Items": Cooking Oil 2L | sold 1 | return 1 | restock Yes. Rail ↩›.

--- 5 · Customers (pos.customers) ---
H1 "POS Customers" · sub "Profiles · credit limits · history · statements" ·
[Export][Add Customer CTA].
Table: Beta Industries | +265 991 000 111 | 500,000 | 76,000 | Active | 👁✎💰 ·
Walk-in Customer | — | 0 | 0 | Active | 👁. Rail ＋›.

--- 6 · Products / Items (pos.products) ---
H1 "Products / Inventory Items" · sub "SKU · barcode · pricing · stock · movement" ·
[Adjust Stock][Add Product CTA].
Table: SKU-0001 | Maize Flour 10kg | Staples | 7,000 | 9,000 | 120 | ✎📦 · SKU-0002 |
Bread Loaf | Bakery | 1,200 | 1,800 | 6 (red) | ✎🏷. Rail ＋›.

--- 7 · Price Lists + Discounts & Promotions ---
Price Lists: [Import][Create Price List CTA]. Table: Retail | All customers | 9,000 |
Active · Wholesale | Min qty 10 | 8,200 | Active · VIP | VIP group | 8,600 | Scheduled.
Discounts & Promotions: [Create Promotion CTA]. Table: Weekend 5% off | Percentage |
Active | [Pause] · Buy 2 Get 1 Bread | Buy X Get Y | Paused | [Activate]. Rail 🏷%›.

--- 8 · Payment Methods (pos.payments) ---
"1 · Add Payment Method": Name / Type (Cash/Card/Mobile Money/Bank Transfer/Customer
Credit) / Clearing Account 🔍 / Settlement Bank Account 🔍 / [✓] Requires Reference
Number / [Add].
Table (existing four kept exactly + credit): Bank Transfer | Bank Transfer | 1010 ·
Petty Cash | — | Yes | 0 | Active | Deactivate · Cash | Cash | 1060 · Cash-in-Drawer | — |
No | 0 | Active | Deactivate · Card | Card | 1070 · Card Clearing | — | Yes | 2.5% |
Active | Deactivate · Mobile Money | Mobile Money | 1080 · Mobile Money Clearing | — |
Yes | 1% | Active | Deactivate · Customer Credit | Credit | 1100 · Accounts Receivable |
— | No | 0 | Active | Deactivate. Rail ＋›.

--- 9 · Registers + Shifts + Cashiers ---
"1 · Add Terminal" (KEEP): Identifier / Name / Branch 🔍 / PIN Timeout (min) / [Add].
Registers / Terminals: TILL-01 | Front Counter | Headquarters | 10 min | Active |
[Open][Cash Count][Deactivate].
Shifts: [Start Shift][End Shift]. Table: M. Banda | 08:00 | 50,000 | 212,000 | 0 ·
P. Phiri | 08:00 | 50,000 | 198,500 | (500).
Cashiers & Permissions: [Add Cashier]. Table: M. Banda | Yes | No | No | ⚙ · P. Phiri |
Yes | Yes | No | ⚙. Rail 🗄⏱›.

--- 10 · Offline + Reports + Settings ---
Offline Sales Mode: [Online] badge · [Enable Offline Mode][Sync Transactions] ·
"Pending sync: 0 transactions · locally stored sales synchronise automatically when
connection returns."
POS Reports: [Print][PDF][Excel][Generate] + tiles: Daily Sales / Sales Summary / By
Product / By Category / By Cashier / By Branch / Stock Sold / Fast Movers / Slow Movers /
Cashier Report / Register Report / Payment Method / Cash Variance / Gross Profit /
Product Profitability / Margin Analysis.
POS Settings: Receipt Format (Thermal 80mm/A5) · Receipt Numbering (RCP-{seq}) · Sales
Revenue Account (4000) · COGS Account (5000) · Inventory Account (1300) · Cash Account
(1060) · Hardware · Approval Limits. Rail 📴📊›.