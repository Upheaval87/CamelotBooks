POS SUITE — ELEGANT MOBILE POS + POS LOGIN (DESKTOP & MOBILE) + RETURNABLES (MOBILE)
FULL IMPLEMENTATION SPEC. Build exactly as designed in the consolidated mobile mockups.
ALL VALUES INLINE. Surfaces: (A) POS Login desktop+mobile + Reset + Verify, (B) elegant
mobile POS (Home/Sell/Checkout/Receipt/Receipts/Register&Shift/Products/Settings),
(C) Returnables mobile (Intake / BRR Receipt / Register) with redemption EMBEDDED in
checkout only.

DESKTOP RAILS GUARD: the system-wide pinnable rails feature (rails.html) applies to DESKTOP
pages only and stays EXACTLY as implemented; mobile surfaces use their own bottom nav
(Home/Receipts/Products/Settings) and NEVER render desktop rails. Sell has NO bottom nav.

HARD GUARD — UPDATE, DON'T REPLACE: (a) type-ahead product search (Up/Down navigate, Enter
select) + barcode scan; (b) customer search; (c) the four existing payment methods + GL
clearing accounts (Cash→1060, Card→1070, Mobile→1080, Bank Transfer→1010) extended with
Credit→1100 + Split; (d) existing journal posting handler, inventory logic, receipt
numbering. All postings via the existing journal handler. Receipts keep the Invoice
(INV-PROBE-001) brand chrome. Returnables redemption lives ONLY inside checkout.

==================== 0 · DISCOVERY ====================
0.1 Locate auth/user+role model, cashier permissions, register/shift model, branch/till
model, product/customer search endpoints, payment methods + clearing accounts, inventory
stock + returnable items (deposit value), GL posting handler, receipt PDF generator.
0.2 List CURRENT controls/handlers on login + POS pages (drives §15 audit).
0.3 Confirm Invoice PDF chrome as brand reference (C-tile + CamelotBooks + tagline;
"Amount in words …"; footer "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567 · Page 1 of 1"; "Authorised By — Signature & Date").

==================== 1 · TOKENS / TYPOGRAPHY (elegant, flat) ====================
Inter (400–800). Palette: bg #F5F7F6; paper #fff; ink #13292C; sub #5F7476; faint #9AAEAE;
line #E4EAE8; hair #EEF3F1; solid #0E6E67; solid-d #0B3437; green #1B7F4D; red #C2453F;
amber #A9761B. BUTTONS FLAT SOLID — NO GRADIENTS (primary solid #0E6E67; dark bars
#0B3437; secondary white + hairline). Cards white, 1px hairline, 14–16px radius, soft
shadow; small-caps labels (10–11px +.12–.14em); tabular-nums on all figures; hairline
dividers; generous whitespace. Phone frame 384px, screen 780px, radius 36px.

==================== 2 · PAGE INVENTORY ====================
pos.login          POS Login — responsive desktop + mobile.
pos.reset          Password / PIN Reset.
pos.verify         Password / PIN Verify (identity gate).
m.pos.home         Mobile Home.
m.pos.sell         Mobile Sell (NO bottom nav).
m.pos.checkout     Mobile Checkout (swipe; returnables inside).
m.pos.receipt      Mobile e-Receipt (success header + branded doc).
m.pos.receipts     Mobile Receipts / History (Branch + Till selectors).
m.pos.register     Mobile Register & Shift.
m.pos.products     Mobile Products (NO photos).
m.pos.settings     Mobile Settings.
m.ret.intake       Returnables Intake (issue BRR).
m.ret.receipt      Bottle Return Receipt (BRR).
m.ret.register     Returnables Receipts register.

==================== 3 · POS LOGIN (pos.login) ====================
3.1 DESKTOP: split layout — left deep-teal brand panel (C-tile, CamelotBooks, tagline,
"Point of Sale", value bullets, invoice footer); right "Sign in to POS" with
Password / Cashier-PIN tabs. Password: username/email, password, Branch/Terminal select,
remember-me, forgot-password, solid Sign In. PIN: cashier avatar picker, 4-dot indicator,
3×4 keypad, solid Unlock Register.
3.2 MOBILE: stacked, PIN-first (tabs to Password), cashier chips, PIN dots, keypad,
Unlock Register, brand footer; links "Forgot password?" / "Reset PIN".
3.3 AUTH: validate vs user+role; on success bind session to branch/terminal then gate
Open Register / Start Shift. Rate-limit + audit failed attempts; PIN length 4, masked.

==================== 4 · RESET (pos.reset) ====================
Method tabs (Email code / SMS code) → username/email → [Send verification code] →
6-digit code → new password/PIN → confirm → [Save & Return to Login]. Invalidate old
sessions; audit reset.

==================== 5 · VERIFY (pos.verify) ====================
Identity gate before sensitive actions (close shift/Z-report, void, refund, price change).
Shows the action being confirmed; 4-dot PIN + keypad (or "Use password instead");
[Verify]; ✕ cancels. On success returns to the blocked action; failures rate-limited.

==================== 6 · MOBILE HOME (m.pos.home) ====================
Greeting + register/shift line; 3-col summary strip (Today's Sales/Transactions/
Outstanding) hairline dividers + tabular nums; Quick Actions (Receipts/Register/Shifts/
Products + Returnables intake); Recent Activity ledger (± amounts); bottom nav
Home/Receipts/[FAB +]/Products/More with active dot.

==================== 7 · MOBILE SELL (m.pos.sell) ====================
NO bottom nav; dark solid cart bar (items + total + →) opens checkout. KEEP type-ahead
search + separate scan button; underline category tabs; 2-up product cards (small-caps
category, name, price, stock, solid add; low stock red). Add appends to shared cart.

==================== 8 · MOBILE CHECKOUT (m.pos.checkout) ====================
8.1 Header: [✕ close] left, title, page dots. ✕ opens bottom confirm "Abandon this sale?"
[Keep selling][Abandon sale]; abandon clears cart → page 1 → new sale (audited).
8.2 PAGE 1 (Cart): customer card at TOP (Walk-in default + Change → search/＋New);
"Customer has a Returnables Receipt" switch; when ON reveal Returnables Receipt № field
(scan/enter + Apply) + applied chip; line rows WITHOUT photos (name+meta, stepper,
amount); totals (goods / deposit / returnables credit / Total); [Continue to Payment]
solid + [＋ Add more items · return to Sell] dashed (keeps cart, navigates to Sell).
8.3 PAGE 2 (Payment): payment options INSIDE ONE bordered card ("PAYMENT METHOD"
small-caps) = Cash/Card/Mobile Money/Credit-Split with fee notes + radio states;
Total Due card; [Complete Sale] solid; [Back to Cart]. Swipe left/right + dots sync.
8.4 COMPLETE SALE: posts journal via existing handler (Dr clearing account(s) for Total
Due incl. extras' deposit; Cr Sales 4000; Cr deposit revenue for extras; returnables
redemption Dr 2300), decrements stock, records served_by = session user, generates
receipt, navigates to m.pos.receipt.

==================== 9 · MOBILE RECEIPT (m.pos.receipt) ====================
Success check tile + "Sale completed" + "Receipt {№} · served by {user}" header, THEN
branded receipt doc (Invoice chrome; meta Served-by + №; line table; TOTAL; amount-in-
words; footer). Actions [🖨 Print][ Email][＋ New]. Do NOT add a bottom served-by line.

==================== 10 · MOBILE RECEIPTS (m.pos.receipts) ====================
Branch select + Till/Register select at TOP (e.g. Headquarters / All tills / TILL-01…) so
any branch+till's receipts can be viewed/reprinted/emailed; payment filter chips
(All/Cash/Card/Mobile/Credit/Returnables); day-grouped list (#№ + tags Returnables/On
credit + time·customer·method + amount); row tap → view/reprint/email. Bottom nav active.

==================== 11 · REGISTER & SHIFT (m.pos.register) ====================
Shift card (Opened / Cashier / Receipts); Cash count (Opening float / Cash in / Cash out /
Expected in drawer / Counted + variance); [Print X-Report]; [Close Shift · Print
Z-Report] gated by pos.verify; on close post cash over/short via existing handler.

==================== 12 · PRODUCTS (m.pos.products) ====================
NO photos. Search; chips (All/Low stock/categories); text rows (name, price + bottle
note, stock state: n in stock / n left red / Out red). Bottom nav active.

==================== 13 · SETTINGS (m.pos.settings) ====================
Profile header; groups: Store (details / tax & currency / receipt footer), Devices
(printer/scanner/drawer status), Preferences (dark mode / offline / language), Account
(users & roles / plan & billing / sign out). Bottom nav "More" active.

==================== 14 · RETURNABLES (MOBILE) ====================
14.1 INTAKE (m.ret.intake): customer (Walk-in/search/＋New chips); container select with
deposit value; qty steppers per container; live Total Bottle Credit; Credit-to chips
(Store credit / Cash refund); [Confirm Return · K…] → +empty stock, post Dr 1320 / Cr
2300, create BRR (Unredeemed), open m.ret.receipt.
14.2 BRR RECEIPT (m.ret.receipt): branded Bottle Return Receipt — meta (Receipt № BRR /
Register / Served By); fixed-width table (Container/Qty/Value/Credit 46/14/20/20,
min-width:0); Total credit; amount-in-words; terms (redeem on purchase, balance carries,
valid 30 days); footer. [Print][Share][＋ New Intake].
14.3 REGISTER (m.ret.register): search + status chips (All/Unredeemed/Partial/Redeemed/
Void); BRR cards (qty/credit/redeemed/balance + status chip) with Redeem/View/Print/Void
(void only if unredeemed → reverses Dr 2300/Cr 1320 + removes stock; permission+audit).
14.4 REDEMPTION: occurs ONLY in checkout (§8.2). Settlement: covered=min(drinks qty, BRR
bottles); extras=drinks−covered pay full price (drink+deposit); redemption credit=
covered×deposit; leftover credit stays on BRR; Complete Sale reduces BRR bottles/credit;
status→Partial/Redeemed. Apply validates (exists/not void/not expired/balance>0).

==================== 15 · GL / ACCOUNT MAP ====================
1320 Returnable Containers (asset) · 2300 Customer Bottle Credits Liability · clearing
1060/1070/1080/1010 · 1100 AR · Sales 4000 · deposit revenue per settings · cash over/
short per settings. All via existing journal handler.

==================== 16 · PERMISSIONS / SECURITY ====================
Cashier permissions (discounts/refunds/void/change prices) enforced; void/abandon/return-
void/close-shift require permission + pos.verify + audit; PIN auth + rate limiting;
approval limits for credit.

==================== 17 · ACCESSIBILITY / RESPONSIVE ====================
Touch targets ≥44px; keypad/switches/tabs keyboard-operable; aria for confirm sheet, dots,
switches, selectors; tabular-nums; ≤380px grids collapse; no horizontal scroll; text-size
matrix 90/100/110/125 no clipping; no console errors.

==================== 18 · CONSTRAINTS ====================
Flat solid buttons only (NO gradients); checkout rows WITHOUT item photos; Sell has NO
bottom nav; payment options inside one card; receipt keeps success header + served-by in
meta only; receipts expose Branch+Till selectors; returnables redemption ONLY in
checkout; desktop rails unchanged and not rendered on mobile; ONE shared receipt template
(Invoice chrome).

==================== 19 · VERIFY ====================
19.1 ROUTES: all §2 routes render + reachable; login responsive desktop+mobile.
19.2 AUTH: password + PIN succeed/fail; reset flow issues+validates code; verify gates
sensitive actions; session binds branch/terminal; open register/shift gate.
19.3 PRESERVATION: type-ahead search + scan + payment clearing accounts unchanged
(spot-test); rails unchanged on desktop.
19.4 CHECKOUT: ✕ abandon confirm + clear; add-more returns to Sell keeping cart; swipe +
dots; returnables switch reveals BRR field; Apply validates; settlement covered/extras
totals correct (12 drinks vs 10 bottles → K21,400); Complete posts §8.4 journal +
served_by + auto receipt with success header.
19.5 RECEIPTS: Branch+Till selectors filter list; view/reprint/email per row.
19.6 REGISTER: cash count variance; X/Z reports; close gated by verify; over/short posted.
19.7 RETURNABLES: intake issues BRR + Dr1320/Cr2300 + stock; BRR branded + fixed table;
register statuses + void reversal; redemption only in checkout.
19.8 VISUAL: no gradient buttons; Sell no bottom nav; payment in card; checkout photo-free;
receipt success header present; receipts selectors present.
REPORT: files touched; route table; preservation confirmation (search/payments/rails);
journal map (sale/intake/redemption/void/over-short); auth flow confirmation; visual-
constraint checklist; confirmation NO functional regressions and NO PAGE SKIPPED.