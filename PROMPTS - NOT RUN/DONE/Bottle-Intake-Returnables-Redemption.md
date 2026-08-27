POS RETURNABLES — BOTTLE INTAKE + RETURNABLES RECEIPT REDEMPTION — IMPLEMENTATION SPEC
(TWO-STEP BOTTLE-CREDIT WORKFLOW: register empties at the door → issue a numbered
Returnables Receipt → redeem its number at POS checkout so the empty-bottle value cancels
the filled-bottle deposit, with extras charged full price.) Build exactly as designed in
the Bottle Intake + Redemption mockups. ALL VALUES INLINE.

RAILS: the system-wide pinnable rails feature (rails.html + refinements) stays EXACTLY as
implemented and applies to the pages below per the registry in §10.

HARD GUARD — DO NOT REPLACE, ONLY EXTEND: (a) the POS type-ahead product search
(Up/Down navigate, Enter select) + barcode scan; (b) customer search; (c) existing payment
methods + clearing accounts; (d) existing journal posting handler, inventory stock logic
and receipt numbering. The Returnables workflow posts journals ONLY through the existing
journal posting handler and moves stock through existing inventory logic.

==================== 0 · DISCOVERY ====================
0.1 Locate POS checkout page + product/customer search endpoints; inventory item model
(incl. any Returnable Packaging items e.g. COKE500 / COKE_EMPTY and deposit value);
GL posting handler; PDF/receipt generator; the Invoice (INV-PROBE-001) template as the
brand reference; existing receipt numbering sequences.
0.2 List CURRENT controls + handlers on the checkout (drives §13 audit).
0.3 Locate user-preference storage (rail prefs) + header Favorites menu.

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
5.6 [Complete Sale]: posts sale journal (Dr payment clearing for Total Due; Cr Sales
Revenue for drinks; Cr Bottle Deposit revenue for extras' deposit), reduces filled stock
−drinks qty, adds expected returns +drinks qty, and REDEEMS the receipt: reduces its
bottles/credit by covered amount; status → Partial or Redeemed; redemption is recorded
against the sale for audit.

==================== 6 · RETURNABLES REGISTER (pos.returnables) ==================
6.1 Search BRR № / customer. Table: Receipt № / Date / Customer / Qty / Credit /
Redeemed / Balance / Status (Unredeemed / Partial / Redeemed / Void) / actions
(👁 View ·  Print ·  Redeem at POS · Void with permission+audit).
6.2 Void reverses the intake journal (Dr 2300 / Cr 1320) and removes empty stock, only if
unredeemed.

==================== 7 · GL ACCOUNTS + JOURNAL MAP ====================
1320 · Returnable Containers (Inventory Asset). 2300 · Customer Bottle Credits Liability.
Intake: Dr 1320 / Cr 2300. Redemption at sale: Dr 2300 / Cr deposit-revenue (covered).
Sale extras deposit: Cr deposit-revenue. Void: reverse intake. All via existing handler.

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
parallel sales — lock by receipt number); DO NOT change rails or other modules.

==================== 13 · VERIFY ====================
13.1 ROUTE CHECK: pos.intake, pos.checkout, pos.returnables render + reachable.
13.2 PRESERVATION: product type-ahead + scan + payment methods unchanged (spot-test).
13.3 INTAKE: registering 10 × K200 → empty stock +10, journal Dr1320/Cr2300 K2,000,
BRR issued + branded PDF (no table overflow, invoice footer present).
13.4 CHECKOUT: on load Returnables section hidden; ticking checkbox reveals
"Returnables Receipt №"; Apply validates (missing/void/expired/zero-balance rejected);
12 drinks vs 10 bottles → Total Due K12,400 (2 extras at K1,200); 8 drinks vs 10 bottles
→ leftover credit stays on receipt; Complete Sale posts §5.6 journal + updates receipt
status/balance + stock.
13.5 REGISTER: statuses transition Unredeemed→Partial→Redeemed; void only if unredeemed
and posts reversal.
13.6 RAILS behave exactly as rails.html; pages render §10 registries.
REPORT: files touched; route table; preservation confirmation (search/payments unchanged);
journal map (intake/redemption/extras/void); receipt-numbering + PDF branding
confirmation; rail registry; confirmation rails + existing functionality unchanged.