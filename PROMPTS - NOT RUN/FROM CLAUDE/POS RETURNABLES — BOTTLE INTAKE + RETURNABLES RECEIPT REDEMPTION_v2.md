POS RETURNABLES — BOTTLE INTAKE + RETURNABLES RECEIPT REDEMPTION — IMPLEMENTATION SPEC — v2
(TWO-STEP BOTTLE-CREDIT WORKFLOW: register empties at the door → issue a numbered
Returnables Receipt → redeem its number at POS checkout so the empty-bottle value cancels
the filled-bottle deposit, with extras charged full price.) ALL VALUES INLINE; the
complete reference mockup is embedded in APPENDIX A — no external file required.
RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to the pages below per the registry in §10: resting slim icon
rail with teal Expand; drawer with pin (true toggle, remembered per page) + X; Favorites
"Pin rails to right side bar" global toggle unchanged; drawer hidden whenever the full
rail is not displayed.
HARD GUARD — DO NOT REPLACE, ONLY EXTEND: (a) the POS type-ahead product search
(Up/Down navigate, Enter select) + barcode scan; (b) customer search; (c) existing payment
methods + clearing accounts; (d) existing journal posting handler, inventory stock logic
and receipt numbering. The Returnables workflow posts journals ONLY through the existing
journal posting handler and moves stock through existing inventory logic.

==================== 0 · DISCOVERY ====================
0.0 SEQUENCING: this spec assumes the POS Centre rebuild (pos.checkout, payment methods,
posting engine, registers/shifts/cashiers) is already complete and stable. Do not run this
before that build is finished — if pos.checkout is only partially built or its posting
engine doesn't yet match what this spec expects to extend, stop and report rather than
guessing at what "existing" means.
0.0b SAFETY NET: before editing pos.checkout or the posting handler, create a dedicated
branch (or commit current working state if not using git) so this change set can be
reverted as a whole if it interacts badly with the existing checkout/posting code. Report
the branch/commit reference used.
0.1 Locate POS checkout page + product/customer search endpoints; inventory item model
(incl. any Returnable Packaging items e.g. COKE500 / COKE_EMPTY and deposit value);
GL posting handler; PDF/receipt generator; the Invoice (INV-PROBE-001) template as the
brand reference; existing receipt numbering sequences.
0.2 List CURRENT controls + handlers on the checkout (drives §13 audit).
0.3 Locate user-preference storage (rail prefs) + header Favorites menu.
0.4 CHART OF ACCOUNTS CHECK: confirm whether 1320 (Returnable Containers) and 2300
(Customer Bottle Credits Liability) already exist in the accounting system's chart of
accounts, and whether a "Bottle Deposit revenue" account already exists under a different
code/name. Report which accounts already exist (use them, exact code/name) versus which
are missing (propose an exact code consistent with the existing numbering scheme and
confirm before creating). Never invent a code that might collide with an existing one.

==================== 1 · TOKENS / TYPOGRAPHY ====================
Inter (400/500/600/700/800) per system type scale; web tokens --deep-1:#17565d;
--deep-2:#0c3539; --sec:#128F8E; --sec-2:#149897; --ink:#0B2A2D; --border:#dceaea;
--muted:#5f7476; --green:#15803d. PDF tokens: deep #0c3539; teal #128F8E; ink #111827;
body #374151; mut #6b7280; hair #e5e7eb; hair2 #f3f4f6. tabular-nums on all figures.

==================== 2 · PAGE INVENTORY ====================
pos.intake      Bottle Intake / Container Registration (NEW).
pos.checkout    POS Sales Screen (UPDATED with Returnables redemption).
pos.returnables Returnables Receipts register (NEW).
inv.containers  Returnable Packaging settings + container stock (LINKED, from Containers
& Deposits feature; do not rebuild).
pos.reports     Container reports (LINKED).
UI/UX REFERENCE: APPENDIX A (embedded mockup) — replicate layout, forms, tables, chips,
hidden-until-ticked redemption section, receipt PDF and totals exactly.

==================== 3 · BOTTLE INTAKE (pos.intake) ====================
3.1 Form: Customer (type-ahead search + Walk-in) · Container (select of returnable items) ·
Qty Received · Value Each (read-only, from item deposit value). Show Total Bottle Credit =
qty × value.
3.2 [Register & Issue Receipt] on click: (1) increase empty-container stock +N via
inventory logic; (2) post journal Dr 1320 Returnable Containers / Cr 2300 Customer Bottle
Credits Liability (N × value) via existing handler; (3) create receipt record with
sequential number BRR-{seq}; (4) render/print branded Returnables Receipt (§4);
(5) status Unredeemed.
3.3 [Print] reprints; [Receipts Register] links to pos.returnables.

==================== 4 · RETURNABLES RECEIPT PDF (invoice-matched branding) =====
4.1 Header: 30px deep-teal "C" tile + "CamelotBooks" (12px/700) + tagline
"Enterprise Accounting & Advisory Services" (7.5px); right: title UPPERCASE letter-spaced
.22em "BOTTLE RETURN RECEIPT" + date line + 26×3px teal accent bar; 1px #e5e7eb rule.
4.2 Table: FIXED layout, min-width:0, colgroup 30/30/12/14/14% (Receipt № / Customer /
Qty / Value / Credit) — MUST NOT overflow the sheet; hairline rules; grand-total row
2px ink top rule with deep-teal figures.
4.3 "Amount in words: … only." italic line; Terms: redeem on purchase, balance carries
forward, valid 30 days.
4.4 Footer IDENTICAL to invoice: "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567" left · "Page 1 of 1" right; 8px #9ca3af; 1px top rule.

==================== 5 · CHECKOUT UPDATE (pos.checkout) ====================
5.1 KEEP product type-ahead search + Qty + [Add] + [📷 Scan Barcode] at top of Items card.
5.2 ADD checkbox "Customer has a Returnables Receipt" (unchecked on load). The
"Returnables Receipt №" field + [Apply] + result chip are HIDDEN until the checkbox is
ticked (and the redemption cart line / redemption totrow / extras note hidden likewise).
5.3 FIELD LABEL: "Returnables Receipt №" (renamed from "Bottle Receipt №").
5.4 [Apply] validates the number: exists · not void · not expired (≤30 days) · remaining
balance > 0; on success show chip "{№} · {bottles} bottles · credit {K} · balance {K}" and
add redemption line to cart; on failure show inline error and block.
5.5 SETTLEMENT RULES (recompute on any qty/apply change):
covered = min(drinks qty, receipt bottles); extras = drinks qty − covered.
Drinks line = drinks qty × selling price. Deposit line = drinks qty × deposit.
Redemption line = −(covered × deposit). Total Due = drinks + deposit − redemption.
Covered bottles pay drink price only; EXTRAS pay full price (drink + deposit).
Leftover credit (receipt bottles > drinks) remains on the receipt for next visit.
AUTHORITATIVE FORMULA NOTICE: this formula, not the worked example in Appendix A, is the
source of truth for Total Due. For the 12-drinks/10-bottles example used throughout this
spec: Total Due = 12,000 + 2,400 − 2,000 = K12,400. If Appendix A's rendering of this
example ever shows a different total, treat that as a typo in the mockup and follow this
formula instead — do not replicate a wrong total pixel-for-pixel.
5.6 [Complete Sale]: posts sale journal (Dr payment clearing for Total Due; Cr Sales
Revenue for drinks; Cr Bottle Deposit revenue for extras' deposit), reduces filled stock
−drinks qty, adds expected returns +drinks qty, and REDEEMS the receipt: reduces its
bottles/credit by covered amount; status → Partial or Redeemed; redemption is recorded
against the sale for audit.

==================== 6 · RETURNABLES REGISTER (pos.returnables) ==================
6.1 Search BRR № / customer. Table: Receipt № / Date / Customer / Qty / Credit /
Redeemed / Balance / Status (Unredeemed / Partial / Redeemed / Void) / actions
(👁 View · Print · Redeem at POS · Void with permission+audit).
6.2 Void reverses the intake journal (Dr 2300 / Cr 1320) and removes empty stock, only if
unredeemed.

==================== 7 · GL ACCOUNTS + JOURNAL MAP ====================
1320 · Returnable Containers (Inventory Asset). 2300 · Customer Bottle Credits Liability.
Deposit-revenue account: use whatever account/code was confirmed or created for this in
§0.4 — do not leave it as a bare name with no account number; the posting handler needs an
exact account code the same way it does for 1320/2300.
Intake: Dr 1320 / Cr 2300. Redemption at sale: Dr 2300 / Cr deposit-revenue (covered).
Sale extras deposit: Cr deposit-revenue. Void: reverse intake. All via existing handler.
BALANCE GUARD: before committing, assert sum(debit lines) == sum(credit lines) for every
journal this feature posts (intake, sale/redemption, void). If they don't balance, abort
the whole transaction — do not partially post, do not move stock — and surface a clear
error; an unbalanced journal must never reach the ledger silently.

==================== 8 · INVENTORY MOVEMENTS ====================
Intake: empty stock +N. Sale: filled stock −N, expected returns +N. Redemption reconciles
expected vs actual returned. Container stock balance + deposit liability feed the
Container reports (issued/returned/missing/liability/balance) in pos.reports.

==================== 9 · PRODUCT MASTER (linked) ====================
Returnable Packaging settings on item: Container Type (Bottle/Crate/Keg/Cylinder) ·
Is Returnable · Deposit Value · Linked Product · Required Return · Container Stock
Tracking. (From Containers & Deposits feature; reuse, do not rebuild.)

==================== 10 · RAILS REGISTRY (rails unchanged) =====================
pos.intake → Quick Nav [Register Bottles, Receipts Register, Print].
pos.checkout → Quick Nav [Scan, Returnables, Receipts].
pos.returnables → Quick Nav [Receipts, Redeem, Void].

==================== 11 · ACCESSIBILITY / RESPONSIVE ====================
Checkbox + Apply keyboard-operable; hidden section uses aria-hidden/hidden attr; receipt
table fixed layout never overflows; ≤1000px grid2 collapses; ≤768px slim rail hidden;
text-size matrix 90/100/110/125 no clipping; no console/build errors.

==================== 12 · CONSTRAINTS ====================
Do not replace product/customer search or payment methods; keep receipt PDF to ONE shared
invoice-matched template; redemption idempotent (a receipt can't be double-redeemed in
parallel sales — lock by receipt number); DO NOT change rails or other modules;
pixel/UX parity with APPENDIX A.
12.1 REDEMPTION LOCK TIMING: the balance/status check performed when the cashier clicks
[Apply] is provisional only — it must be re-checked and re-locked (by receipt number,
inside the same transaction as the journal post) at [Complete Sale] time, immediately
before committing. Two terminals applying the same receipt concurrently must not both be
able to complete a sale against it: the second Complete Sale to reach the lock re-checks
the now-current balance and fails cleanly (clear error, no partial post) if it's
insufficient, even though its own earlier Apply check passed.
12.2 EXPIRY SWEEP: unredeemed/partial receipts do not auto-write-off at 30 days — checkout
simply refuses to redeem them past expiry (§5.4), leaving the 2300 liability sitting on
the books indefinitely otherwise. Add a scheduled sweep (mirroring the pattern used
elsewhere in this system for stale-record review) that flags expired unredeemed/partial
receipts for manual review — do not auto-post a write-off journal; that stays a deliberate
admin action for now, but the flag must exist so expired liabilities aren't invisible.
12.3 BRANCH SCOPING (ASSUMPTION — confirm before building, matches the rule already
applied to the rest of the POS Centre): if a head-office/branch visibility rule already
governs POS data elsewhere in this system, apply it here too — Bottle Intake and the
Returnables Receipts register should only be visible across all branches to head-office
users; other-branch users see only their own branch's receipts. If this assumption is
wrong for Returnables specifically, stop and confirm before building rather than guessing.

==================== 13 · VERIFY ====================
13.1 ROUTE CHECK: pos.intake, pos.checkout, pos.returnables render + reachable.
13.2 PRESERVATION: product type-ahead + scan + payment methods unchanged (spot-test).
13.3 INTAKE: registering 10 × K200 → empty stock +10, journal Dr1320/Cr2300 K2,000,
BRR issued + branded PDF (no table overflow, invoice footer present).
13.4 CHECKOUT: on load Returnables section hidden; ticking checkbox reveals
"Returnables Receipt №"; Apply validates (missing/void/expired/zero-balance rejected);
12 drinks vs 10 bottles → Total Due K12,400 (2 extras at K1,200 — confirm this matches
§5.5's formula, not a hardcoded figure); 8 drinks vs 10 bottles → leftover credit stays on
receipt; Complete Sale posts §5.6 journal + updates receipt status/balance + stock; every
posted journal balances debits=credits (§7 guard — spot check with a malformed input to
confirm the guard actually rejects an unbalanced entry).
13.4b CONCURRENCY: simulate two near-simultaneous Complete Sale attempts against the same
receipt with insufficient combined balance — exactly one succeeds, the other fails cleanly
with no partial post (§12.1).
13.4c BRANCH SCOPING: a non-head-office user sees only their own branch's Bottle Intake
and Returnables Register entries; a head-office user sees all branches (§12.3).
13.5 REGISTER: statuses transition Unredeemed→Partial→Redeemed; void only if unredeemed
and posts reversal.
13.6 RAILS behave exactly as rails.html; pages render §10 registries.
13.7 EXPIRY SWEEP: an expired unredeemed/partial receipt is flagged by the sweep job
(§12.2) and not auto-written-off.
REPORT: safety-net branch/commit reference (§0.0b); chart-of-accounts findings — which
posting accounts existed vs. were created, including the deposit-revenue account (§0.4);
files touched; route table; preservation confirmation (search/payments unchanged); journal
map (intake/redemption/extras/void); balance-guard confirmation (§7); redemption-lock
timing confirmation (§12.1); expiry-sweep confirmation (§12.2); branch-scoping
confirmation (§12.3/§13.4c); receipt-numbering + PDF branding confirmation; rail registry;
confirmation rails + existing functionality unchanged.

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (verbatim) ====================
Replicate exactly. Shell: topbar "CBCamelotBooks · ES · Elvis Seyama" + nav
[Sales][POS][Inventory][Banking][Reports]; rails per §10.

--- 1 · Bottle Intake — receipt table fits the sheet (pos.intake) ---
H1 "Bottle Intake / Container Registration" · sub "Customer hands in empties first → gets
a numbered Returnables Receipt to redeem at checkout" · actions [Receipts Register]
[Register Bottles].
"1 · Intake Details": Customer 🔍 · Container select (Coca-Cola Empty Bottle / Beer Empty
Bottle / Crate (24)) · Qty Received · Value Each (read-only) · Total Bottle Credit K2,000.
Note: "On register: empty stock +10 · issues BRR-000123 · credit K2,000 held as liability
until redeemed." [Register & Issue Receipt CTA][🖨 Print].
RETURNABLES RECEIPT (branded, invoice-matched): "C" tile + CamelotBooks + "Enterprise
Accounting & Advisory Services" · title "Bottle Return Receipt" · 15 August 2026.
| Receipt № | Customer | Qty | Value | Credit |
| BRR-000123 | Walk-in | 10 | 200 | 2,000 |
| Total Bottle Credit (spans 4 cols) | 2,000 |
"Amount in words: Two Thousand Kwacha only." · Terms: "Redeem on purchase; balance carries
forward; valid 30 days." · footer "www.camelotbooks.com · info@camelotbooks.com ·
+265 1 234 567" left · "Page 1 of 1" right. Rail 🍾🖨›.

--- 2 · POS Checkout — Returnables Receipt hidden until activated (pos.checkout) ---
H1 "POS Checkout — TILL-01" · sub "Search/add products as usual; tick the box only if the
customer holds a Returnables Receipt".
"1 · Items + Returnables Receipt": Product 🔍 + Qty + [Add] + [📷 Scan Barcode] (kept).
Checkbox "Customer has a Returnables Receipt" (unchecked on load) → reveals
"Returnables Receipt №" + [Apply]; on Apply shows chip
"BRR-000123 · 10 bottles · credit K2,000 · balance K0" and adds redemption line.
Cart lines:
| Line | Qty | Price | Total |
| Coca-Cola 500ml Filled | 12 | 1,000 | 12,000 |
| Bottle Deposit (12 × 200) | 12 | 200 | 2,400 |
| BRR-000123 Redemption (10 × −200) | 10 | −200 | −2,000 |
Note: "Covered 10 bottles pay drink only (K1,000 each). 2 extra bottles pay full price
incl. bottle (K1,200 each) = K2,400."
"2 · Payment": Drinks (12 × 1,000) K12,000 · Bottle Deposit (12) +K2,400 · Returnables
Redemption (10) −K2,000 · Total Due K12,400 (12,000 + 2,400 − 2,000 = 12,400 — this figure
must match §5.5's formula exactly; if it doesn't, the formula wins, not this appendix).
Note: "If bottles returned > drinks bought, the unused credit stays on the receipt for
the next visit." [Complete Sale CTA]. Rail 📷🎟›.