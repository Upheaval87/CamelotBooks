POINT OF SALE (POS) CENTRE — FULL IMPLEMENTATION SPEC
(15 PAGES: DASHBOARD / SALES SCREEN / RECEIPTS / RETURNS / CUSTOMERS / PRODUCTS /
PRICE LISTS / PROMOTIONS / PAYMENT METHODS / REGISTERS / SHIFTS / CASHIERS / OFFLINE /
REPORTS / SETTINGS). Build exactly as designed in the consolidated POS mockup.
CRITICAL: existing payment options and search features are UPDATED, NOT REPLACED.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to every POS page per the registry in §15.

HARD GUARD — DO NOT REPLACE, ONLY UPDATE: (a) the type-ahead product search (Up/Down to
navigate, Enter to select) and customer search with search-glass buttons; (b) the branch
picker; (c) the four existing payment methods and their clearing accounts (Bank Transfer→
1010 Petty Cash, Cash→1060 Cash-in-Drawer, Card→1070 Card Clearing, Mobile Money→1080
Mobile Money Clearing) with their Ref-Required flags; (d) the Add-Terminal form +
terminals table. Extend these (credit + split, fees, GL links) but never remove them.
All other existing handlers (inventory, customers, GL posting, banking) remain as-is;
POS posts to the ledger ONLY through the existing journal posting handler.

==================== 0 · DISCOVERY ====================
0.1 Locate existing POS checkout, terminals, payment-methods pages + handlers; product &
customer search endpoints; inventory stock/COGS logic; GL posting handler; receipt
numbering; PDF/receipt template (Invoice INV-PROBE-001 is the brand reference).
0.2 List CURRENT controls + handlers per page (drives §18 audit).
0.3 Locate user-preference storage (rail prefs) + header Favorites menu.

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

==================== 3 · POS DASHBOARD (pos.dashboard) ====================
3.1 KPIs (6): Today's Sales (hero) / Transactions / Avg Sale Value / Cash Collected /
Card+Mobile / Outstanding Credit.
3.2 Panels: Top Selling Items (qty+revenue) · Low Stock (red counts) · Sales by Cashier ·
Sales by Branch.
3.3 Buttons: [➕ New Sale CTA][Open Register sec][Close Register][View Reports]
[Manage Products][Transactions].

==================== 4 · SALES SCREEN (pos.checkout) ====================
4.1 LEFT "1 · Add Items": KEEP type-ahead product search (Up/Down/Enter) + search-glass +
[📷 Scan Barcode]; Qty + [Add]; Favourites/Recent quick chips; cart table Product/UOM/Qty/
Price/Discount/Tax/Total with per-line edit/remove + notes; [⏸ Hold Sale].
4.2 RIGHT "2 · Payment": KEEP customer type-ahead search + "Walk-in Customer" default +
[＋ Add Customer][History]; payment method chips = existing four (Cash/Card/Mobile Money/
Bank Transfer) PLUS new Credit + Split (updated, not replaced); Reference field; totals
Subtotal/Discount/Tax/Total Due; [Complete Sale CTA][🖨][]. Disabled state "ADD ITEMS
FIRST" when cart empty.
4.3 Split payment: allocate amounts across ≥2 methods summing to Total Due; each leg posts
to its clearing account. Credit sale: posts to 1100 AR, checks customer credit limit
(block over-limit without supervisor approval).

==================== 5 · SALE POSTING ENGINE (on Complete Sale) ====================
5.1 Decrement inventory qty per line; compute COGS = Σ(cost × qty).
5.2 Post ONE journal via existing handler: DR payment clearing account(s) (cash 1060 /
card 1070 / mobile 1080 / bank 1010 / AR 1100) for amounts received (cash net of change)
+ DR COGS 5000 + DR discount/fee accounts; CR Sales Revenue 4000 + CR Tax Payable + CR
Inventory 1300 (or COGS/Inventory per settings). Card/mobile fees (2.5%/1%) to fee expense.
5.3 Update customer record (purchases, balance for credit), cashier/shift/register
totals, receipt record; emit receipt.
5.4 Receipt PDF/thermal uses Invoice brand chrome: "C" tile + CamelotBooks + tagline,
letter-spaced title (RECEIPT), meta (Receipt №/Date/Cashier), line table, TOTAL,
"Amount in words … only.", footer "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567 · Page 1 of 1"; tax-compliant (tax breakdown shown).

==================== 6 · RECEIPTS (pos.receipts) ====================
6.1 Search receipt №/customer (KEEP search pattern). Table: Receipt №/Date/Customer/
Cashier/Amount/Method chip/Status (Completed/Pending/Cancelled/Refunded).
6.2 Row actions: 👁 View · 🖨 Print · Reprint · ✉ Email · ↩ Refund · Void (permission +
audit). Void posts reversing journal + restocks.

==================== 7 · RETURNS (pos.returns) ====================
7.1 Two-step: search original sale (KEEP search) + reason + return type chips
(Cash refund / Credit note / Exchange); select returned items + restock flag.
7.2 Actions: [New Return CTA][Approve Return][Process Refund][Print Return Receipt].
Approve posts reversing revenue/tax + restock inventory; refund pays via chosen type
(cash 1060 / credit note to AR/credit balance / exchange creates new sale leg).

==================== 8 · CUSTOMERS (pos.customers) ====================
Table: Customer/Phone/Credit Limit/Balance/Status; actions View/Edit/Receive Payment/
Statement. [Add Customer CTA][Export]. History tab: purchases/returns/payments/outstanding.

==================== 9 · PRODUCTS (pos.products) ====================
Table: SKU/Barcode/Category/Cost/Price/Stock (red when low); actions Edit/Print Barcode/
View Movement; [Add Product CTA][Adjust Stock]. Barcode scan resolves to product.

==================== 10 · PRICE LISTS (pos.pricelists) ====================
Lists: Retail/Wholesale/VIP/Promotional with applies-to + effective dates + per-item
prices; [Create Price List CTA][Import][Export][Edit]. Checkout resolves price by
customer group/qty/effective date.

==================== 11 · PROMOTIONS (pos.promotions) ====================
Types: percentage / fixed / Buy-X-Get-Y / customer discount; rules: date range, min qty,
customer group; [Create Promotion CTA]; Activate/Pause; View Usage. Auto-applies at
checkout when rules match; manual discount respects cashier permission/limit.

==================== 12 · PAYMENT METHODS (pos.payments) ====================
12.1 Add form (KEEP): Name/Type/Clearing Account (search)/Settlement Bank Account (search)/
Requires Reference checkbox/[Add].
12.2 Table (KEEP existing four rows exactly) + new Customer Credit→1100 AR; columns add
Fee + Status; Deactivate action. Edit exposes GL clearing/settlement links + fee % +
change-calculation flag.

==================== 13 · REGISTERS / SHIFTS / CASHIERS ====================
13.1 REGISTERS: KEEP Add-Terminal form (Identifier/Name/Branch picker/PIN Timeout) +
terminals table (Identifier/Name/Branch/PIN Timeout/Status/Deactivate); add actions
Open/Close/Cash Count/View History; closing computes expected vs counted cash → variance
posted to Cash Over/Short account.
13.2 SHIFTS: Start/End shift; opening balance; sales during shift; cash difference;
[View Shift Report].
13.3 CASHIERS: add cashier; permission matrix (Apply Discounts / Refunds / Void / Change
Prices) with approval limits; View Activity (audit).

==================== 14 · OFFLINE (pos.offline) ====================
Enable Offline Mode (store transactions locally, continue selling); Sync Transactions
(idempotent, posts journals + stock on reconnect); View Pending Sync count. Conflict
handling: stock shortages flagged for review.

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
General: receipt format (thermal 80mm/A5), numbering RCP-{seq}, currency, tax. Hardware:
scanner/printer/drawer/customer display. Accounting integration: Sales 4000 / COGS 5000 /
Inventory 1300 / Cash 1060 / Bank / AR 1100. Security: user permissions, approval limits,
audit trail.

==================== 18 · VERIFY (EVERY PAGE) ====================
18.1 ROUTE CHECK: all 15 routes exist, render, reachable.
18.2 PRESERVATION: product type-ahead (Up/Down/Enter) + customer search + branch picker
work as before; existing four payment methods + clearing accounts intact; Add-Terminal +
terminals table intact (spot-test).
18.3 POSTING: Complete Sale decrements stock, computes COGS, posts correct multi-line
journal (clearing per method, split legs, credit→1100, fees, tax, revenue, inventory);
refund/void post reversals + restock; register close posts variance; offline sync posts
queued sales exactly once.
18.4 RECEIPTS: numbering sequential; PDF/thermal matches Invoice brand chrome + footer;
tax breakdown present.
18.5 PERMISSIONS: cashier limits enforced (discount/refund/void); over-credit blocked
without approval.
18.6 RAILS: slim rail + drawer + pins behave exactly as rails.html; pages render §15.
18.7 Text-size matrix 90/100/110/125: no clipping; no console/build errors.
REPORT: files touched; page-route table (all 15); preservation confirmation (search +
payment methods + terminals updated not replaced); journal mapping table (method→clearing
account); rail registry per page; confirmation rails + existing functionality unchanged
and NO PAGE SKIPPED.