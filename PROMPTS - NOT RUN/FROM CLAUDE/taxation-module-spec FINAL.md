TAXATION MODULE — FULL IMPLEMENTATION SPEC (SELF-CONTAINED, REV 2)
A central, configurable TAX ENGINE (not per-module calculators). Reference mockups embedded:
APPENDIX A = screens 1–8; APPENDIX B = screens 9–12. Build to match them exactly.

==================== BUILD SEQUENCE — DO NOT PARALLELIZE ====================
Build in this exact order. Do not start a stage until the previous stage's checklist
passes. Each stage must be independently runnable/testable before moving on. If a later
stage reveals a problem in an earlier stage's design, stop, fix the earlier stage, re-run
its checklist, then continue — do not patch around it downstream.

STAGE 1 — SCHEMA
  Implement §0 DDL exactly (incl. REV 2 additions below). Add migrations. Seed minimal
  reference data (tax_types, a few tax_codes/rates, one jurisdiction).
  ✓ Checklist: all tables/constraints/indexes created; FKs valid; effective-date overlap
    constraints in place (§0.1); seed data loads cleanly; no orphaned FKs.

STAGE 2 — ENGINE + TESTS
  Implement §1 engine logic + §1.9–§1.12 (REV 2) as a standalone, unit-testable module
  with NO UI and NO GL wiring yet. Implement the calling contract in §1.13.
  ✓ Checklist: unit tests green for — inclusive/exclusive calc, zero/exempt, WHT deduction,
    reverse charge, rounding (line-level and document-level), apportionment ratio,
    recognition basis per type, rate-selection by transaction date across overlapping
    history, missing-tax-code exception path, period-lock rejection. Test against
    concrete numeric examples, not just "runs without error."

STAGE 3 — GL POSTING
  Wire engine output to GL postings per §2 (incl. REV 2 additions). Still no screens.
  ✓ Checklist: for each template in §2, a posted transaction produces the exact Dr/Cr
    lines specified, amounts tie to tax_transactions, and posting + tax_transactions
    write atomically in one DB transaction (§1.4/§1.14) with rollback proven by a
    forced-failure test.

STAGE 4 — SCREENS
  Build all 12 screens per APPENDIX A/B. Pure UI + read wiring against Stage 1–3 data.
  No new business logic here — if a screen seems to need new logic, stop and go back to
  Stage 2/3.
  ✓ Checklist: 12/12 routes render, screenshot-compare against mockups, currency via
    §C1 helper (no hardcoded symbols), search overlay per §C2 on screens 2/6/8/11,
    responsive at 1280/1024/768, no console/build errors.

STAGE 5 — WORKFLOWS
  Implement §3 lifecycle actions (period transitions, return prep/approve/file/pay, WHT
  certificate issuance, adjustments+approval) as actions callable from Stage 4 screens.
  ✓ Checklist: full workflow test — open period → transactions post → prepare return →
    approve → file → pay → period closes → outstanding = 0. WHT payment → certificate
    issued → numbered correctly. Adjustment blocked until approved. Reversal against a
    closed period lands in the current open period, never mutates the closed one (§1.15).

STAGE 6 — PERMISSIONS + AUDIT
  Implement §5 role checks and §0 tax_audit_trail writes for every mutating action across
  Stages 3–5.
  ✓ Checklist: preparer≠approver enforced server-side (not just hidden in UI); every
    config change, adjustment, period transition, and payment writes an audit row with
    old/new value, reason, user, approval; unauthorized actions rejected with a clear
    error, not silently no-op'd.

STAGE 7 — VERIFICATION (§8)
  Run the full §8 checklist end to end and produce the REPORT specified at the bottom of
  this document. Only after Stage 7 passes is the module considered done.

==================== SYSTEM CONSTRAINTS (apply everywhere) ====================
C1 CURRENCY — DO NOT hardcode any currency/symbol. Read base currency + symbol from the
   system setting (currencies/settings table) and format all money via the shared money
   helper. Mockups show "K" only as an example — substitute the system currency at render.
C2 SEARCH — where a page has search (VAT Transactions, Tax Codes, Certificates, Audit
   Trail, Payments), use the SYSTEM live-search component; its results panel MUST render
   above ALL content (position:absolute/fixed; z-index ≥ 9999; overlays tables/cards/modals).
C3 Rails feature (rails.html) remains for other pages; tax pages render as mocked (no rail).
C4 MULTI-ENTITY SCOPING — if the host system supports multiple companies/branches, every
   table in §0 carries a company_id (and branch_id if branches exist) FK, and every query/
   report/screen is scoped to the active entity. If the system is single-entity only, skip
   this and note it explicitly in the final REPORT so the assumption is on record.
C5 LABELING — mockups use "VAT" throughout (VAT Return, VAT Control Account, VAT
   Transactions) because VAT is the primary regime for the target jurisdiction. Screens,
   routes, and labels stay VAT-specific as shown — do NOT generalize labels to "Output/
   Input Tax" or rename VAT-specific screens. Non-VAT regimes (WHT, PAYE, FBT) get their
   own screens/labels as already shown (6, 11 register, 12) rather than being folded into
   generic wording.

HARD GUARD — integrate via hooks; DO NOT rewrite Sales/Purchases/Expenses/Payroll/Banking/
GL. They call the Tax Engine via the contract in §1.13; the engine writes tax_transactions
+ GL postings.

==================== 0 · DATABASE ARCHITECTURE ====================
tax_types(id PK, code UQ, name, category ENUM[VAT,WHT,PAYE,FBT,CORPORATE,PRESUMPTIVE,OTHER],
  active BOOL)
tax_jurisdictions(id, code UQ, name, country, authority, active)
tax_codes(id, code UQ, name, tax_type_id FK, jurisdiction_id FK NULL,
  treatment ENUM[STANDARD,ZERO_RATED,EXEMPT,DEDUCTED,CHARGED,REVERSE_CHARGE],
  price_basis ENUM[EXCLUSIVE,INCLUSIVE] DEF EXCLUSIVE,
  rounding_mode ENUM[HALF_UP,HALF_DOWN,HALF_EVEN] DEF HALF_UP,
  rounding_level ENUM[LINE,DOCUMENT] DEF LINE,
  gl_output_acct FK accounts, gl_input_acct FK, gl_payable_acct FK,
  effective_from DATE, effective_to DATE NULL, active BOOL)
tax_code_rates(id, tax_code_id FK, rate_pct DEC(8,4), effective_from, effective_to NULL)
  — configurable rates over time; engine picks rate valid at tx date. UQ(code,from).
  Overlap constraint (§0.1): no two rows for the same tax_code_id may have overlapping
  [effective_from, effective_to) ranges; enforce at write time (app-level check inside the
  same transaction, plus a DB constraint/trigger if the platform supports it).
tax_exemptions(id, code UQ, name, reason, scope ENUM[SALES,PURCHASES,BOTH], tax_type_id FK,
  effective_from, effective_to, active)
tax_registrations(id, entity_kind ENUM[COMPANY,CUSTOMER,SUPPLIER], entity_id,
  jurisdiction_id FK, tax_type_id FK, reg_number, effective_from, status)
  — same overlap constraint as §0.1 applies per (entity, jurisdiction, tax_type).
tax_recognition_rules(id, tax_type_id FK UQ, basis ENUM[INVOICE,CASH,PAYMENT,ACCRUAL], note)
tax_apportionment_rules(id, tax_type_id FK, jurisdiction_id FK NULL, method
  ENUM[TURNOVER_RATIO,DIRECT_ATTRIBUTION], recoverable_pct DEC(6,3) NULL,
  effective_from, effective_to NULL, note)
  — used when a business makes both taxable and exempt supplies (partial exemption). When
  method=TURNOVER_RATIO, recoverable_pct is either entered manually per period or computed
  from period taxable/total turnover — engine applies it to otherwise-recoverable input tax.
tax_periods(id, tax_type_id FK, label, start_date, end_date,
  status ENUM[OPEN,IN_PREPARATION,SUBMITTED,CLOSED,AMENDED], filing_due_date,
  filed_date NULL, payment_date NULL, reference NULL, locked BOOL, version INT DEF 0)
  — version column supports optimistic locking (§1.16).
tax_transactions(id, period_id FK, tax_code_id FK, rate_pct DEC snapshot,
  side ENUM[OUTPUT,INPUT,WHT,PAYE,ADJUST,REVERSE_CHARGE_OUT,REVERSE_CHARGE_IN],
  source_kind, source_id,
  base_amount, tax_amount, gross_amount, net_amount, exemption_id NULL, exemption_reason,
  apportionment_pct NULL, recoverable_tax_amount NULL,
  jurisdiction_id FK, gl_account_id FK, recognition_basis snapshot, recognized_at,
  is_reversal BOOL DEF false, reverses_transaction_id FK NULL,
  status ENUM[POSTED,UNPOSTED], created_at)  idx(period, source, side)
tax_adjustments(id, period_id FK, tax_type_id FK, amount, direction ENUM[ADD,REDUCE],
  reason, status ENUM[PENDING,APPROVED,REJECTED], created_by, approved_by NULL)
tax_payments(id, tax_type_id FK, period_id FK, amount, payment_date, bank_account_id FK,
  payment_ref UQ, receipt_number, authority, recorded_by FK users, status ENUM[PENDING,PAID])
  — payment_ref generation: sequential per company, format PAY-{seq}; never reused.
wht_certificates(id, cert_number UQ, supplier_id FK, tax_code_id FK, period_id FK,
  gross, wht_amount, rate_pct, status ENUM[DRAFT,ISSUED], issued_date)
  — cert_number generation: sequential per company (or per company+year if the authority
  requires annual reset — confirm which and document the choice in the REPORT), format
  WHT-{seq}, zero-padded, never reused or reassigned even if a certificate is voided.
tax_returns(id, tax_type_id FK, period_id FK, status ENUM[DRAFT,APPROVED,FILED],
  output_tax, input_tax, adjustments, net_payable, filed_date, reference,
  prepared_by, approved_by)
tax_return_lines(id, return_id FK, section ENUM[OUTPUT,INPUT,ADJUST,TOTAL], label, amount,
  drill_query)
tax_audit_trail(id, user_id, acted_at, entity_kind, entity_id, field, old_value, new_value,
  reason, approval, ip)
RELATIONSHIPS: codes→types/jurisdictions; rates→codes; transactions→codes/periods/
exemptions; payments→periods/types; certificates→suppliers/codes/periods; returns→
periods/types; adjustments→periods. Company/customer/supplier tax regs → registrations.
If C4 applies, every table above also FKs to company (and branch where relevant).

0.1 EFFECTIVE-DATE OVERLAP VALIDATION
  Applies to tax_code_rates and tax_registrations (and any other effective-dated table
  added later). On insert/update, reject if the new [from,to) range overlaps an existing
  row for the same natural key. NULL effective_to means "open-ended" and must be closed
  (effective_to set) before a new overlapping row can be added — i.e. rate changes close
  the prior row's effective_to = new_from - 1 day in the same transaction (§1.8).

==================== 1 · TAX ENGINE LOGIC ====================
1.1 Determine tax code: item/service default → counterparty override → jurisdiction +
effective-date validity; fallback STANDARD; if none, flag "missing tax code" exception.
1.2 Compute: EXCLUSIVE tax=base×rate/100; INCLUSIVE tax=gross×rate/(100+rate). ZERO_RATED/
EXEMPT → rate 0 with treatment + exemption reason recorded. WHT → deduct at source.
1.3 Recognition: apply tax_recognition_rules.basis per type (INVOICE → recognise at invoice
date even if unpaid; CASH → at receipt/payment) — configurable, not hard-coded.
1.4 Write tax_transactions (one or more sides) + GL postings (§2) atomically — see §1.14
for the transaction-boundary contract.
1.5 Net payable per period/type = Σ OUTPUT − Σ recoverable INPUT ± approved adjustments.
    "Recoverable INPUT" = tax_amount × apportionment_pct where an apportionment rule
    applies (§1.10); otherwise recoverable INPUT = tax_amount in full.
1.6 Control-account outstanding = collected − recoverable − paid (live from ledger).
1.7 Period lock: CLOSED periods reject new/changed transactions (reversal-only elsewhere,
see §1.15 for where the reversal lands).
1.8 Rate changes create new tax_code_rates rows (history), never mutate old rates; the
prior row's effective_to is closed per §0.1 in the same write.

1.9 ROUNDING
  Every tax_code carries rounding_mode and rounding_level (§0). LINE-level rounding
  computes and rounds tax per line item, then sums; DOCUMENT-level rounding sums the
  taxable base across lines first, computes tax once on the total, then rounds. The two
  methods can differ by a cent on multi-line documents — this is expected and correct,
  not a bug; do not "reconcile" them against each other. Default HALF_UP/LINE unless a
  jurisdiction's tax_code specifies otherwise. Any residual rounding difference between
  the sum of line tax and a separately-rounded document total must be posted as an
  explicit rounding adjustment line (not silently absorbed into the tax or revenue line).

1.10 PARTIAL EXEMPTION / APPORTIONMENT
  When a counterparty or the company itself makes both taxable and exempt supplies,
  tax_apportionment_rules.recoverable_pct determines what fraction of otherwise-
  recoverable input tax can actually be reclaimed. Engine applies this at the point of
  computing recoverable_tax_amount on the transaction (§1.5), never retroactively rewrites
  tax_amount. If no apportionment rule is active for the tax_type/jurisdiction, treat as
  100% recoverable (current default behavior — this is additive, not a breaking change).

1.11 REVERSE CHARGE
  For REVERSE_CHARGE-treated tax codes (imports, cross-border services), the engine
  writes both a REVERSE_CHARGE_OUT (self-assessed output tax) and REVERSE_CHARGE_IN
  (simultaneous input tax claim, subject to §1.10 apportionment) side for the same source
  document, net effect zero unless apportionment restricts the input side. Both sides
  reference the same source_id so they always net together in reporting.

1.12 MISSING RULES / CONFIGURATION
  If a required rate, recognition rule, or apportionment rule is missing for a given date/
  jurisdiction/type combination, the engine must raise a blocking exception (visible on
  the Tax Dashboard exceptions panel, screen 1) rather than silently defaulting — this
  applies in addition to the existing "missing tax code" exception in §1.1.

1.13 CALLING CONTRACT (Sales/Purchases/Expenses/Payroll/Banking/GL → Tax Engine)
  All calling modules use a single, versioned interface — do not let each module invent
  its own calling shape:
    calculateAndPostTax(context: {
      company_id, branch_id?,
      source_kind: 'SALES_INVOICE'|'PURCHASE_BILL'|'EXPENSE'|'PAYROLL_RUN'|'BANK_TXN'|...,
      source_id, document_date, jurisdiction_id?, counterparty_id?,
      lines: [{ line_id, item_id?, amount, tax_code_id? }]
    }) → {
      status: 'OK' | 'EXCEPTION',
      tax_lines: [{ line_id, tax_code_id, rate_pct, base_amount, tax_amount,
                     treatment, recognition_basis }],
      exceptions: [{ line_id?, type: 'MISSING_TAX_CODE'|'MISSING_RATE'|
                      'MISSING_RECOGNITION_RULE'|'PERIOD_LOCKED'|'INVALID_TREATMENT',
                      message }]
    }
  On EXCEPTION, the calling module's own save must still be allowed to complete (so the
  business document isn't blocked), but the document is flagged and surfaced on the Tax
  Exceptions panel (screen 1) and Reconciliation exceptions table (screen 5) until
  resolved. A reverseTax(source_kind, source_id, reason) counterpart handles §1.15.

1.14 ATOMICITY
  calculateAndPostTax runs inside the same database transaction as the calling module's
  own document save. If either the source document write or the tax_transactions/GL
  posting write fails, the entire transaction rolls back — no document is ever saved with
  a missing or partial tax posting, and no tax posting ever exists without its source
  document. Prove this with a forced-failure test in Stage 3.

1.15 REVERSALS AGAINST CLOSED PERIODS
  A credit note, correction, or void against a document whose original tax transaction
  sits in a CLOSED period never modifies that closed period. Instead, reverseTax posts an
  offsetting ADJUST-side entry (is_reversal=true, reverses_transaction_id set) into the
  current OPEN period, flows through tax_adjustments with the standard approval gate
  (§3.5), and is reported as an adjustment in the open period's return — not a restatement
  of the closed one. If business/legal requirements ever require reopening a closed period
  instead, that only happens via the explicit AMENDED status path (§3.1) with its own
  approval, never as a side effect of an unrelated reversal.

1.16 CONCURRENCY
  tax_periods.version supports optimistic locking: any status-changing action (prepare,
  approve, file, pay, close, reopen) reads the current version, includes it in the update,
  and rejects with a "period was modified, please refresh" error if the version has moved.
  Apply the same pattern to tax_returns status transitions. Do not rely on UI-level
  disabling of buttons as the only safeguard.

==================== 2 · GL POSTING TEMPLATES ====================
Sales:      Dr AR(gross) Cr Sales(net) Cr Output VAT(tax) [code.gl_output_acct]
Purchase:   Dr Expense/Asset(net) Dr Input VAT(tax) Cr AP(gross) [gl_input_acct]
WHT:        Dr Expense(gross) Cr Supplier Payable(net) Cr WHT Payable(tax) [gl_payable_acct]
PAYE:       Dr Salaries(gross) Cr Net Pay Cr PAYE Payable(tax)
Reverse charge: Dr Input VAT(tax, subject to §1.10) Cr Output VAT(tax) — same net accounts
  as standard input/output, posted as a self-assessed pair per §1.11, net zero unless
  apportionment restricts recovery.
Tax payment:Dr {VAT/WHT/PAYE} Payable Cr Bank
Adjustment: Dr/Cr tax control ± opposite per direction.
Rounding residual (§1.9): Dr/Cr a dedicated "Tax Rounding" account for any residual cent,
  never absorbed into Sales/Expense/Tax Payable lines.
All amounts in SYSTEM currency (§C1); tax never treated as revenue/expense.

==================== 3 · WORKFLOWS ====================
3.1 Period lifecycle OPEN→IN_PREPARATION→SUBMITTED→CLOSED (AMENDED reopens w/ approval).
3.2 Return prep: engine builds working paper (Output/Input/Adjust/Net) → Approve → File
(sets filed_date/reference) → Pay (§3.3) → period CLOSED.
3.3 Payment: Record Payment posts Dr Payable Cr Bank; status PAYABLE→PAID; stores date,
amount, bank, ref, receipt №, authority, user; clears outstanding.
3.4 WHT: on supplier payment deduct → issue certificate (numbered per §0's wht_certificates
note) → remit → clear.
3.5 Adjustments require approval before affecting net payable; immutable audit trail;
reversal instead of deletion everywhere (see §1.15 for reversals against closed periods).
3.6 Opening balances / go-live migration: on first activation, allow a one-time
"Opening Tax Position" entry per tax_type (outstanding collected/recoverable/paid as of
go-live date) that seeds tax_periods/tax_transactions history without requiring every
historical document to be re-processed through the engine. This entry is itself audited
and requires Finance Manager approval, and is clearly labeled as an opening balance in
reports (not mixed into ordinary period activity).

==================== 4 · SCREENS (12) ====================
1 Tax Dashboard (KPIs, input-vs-output chart, deadlines, exceptions) — A.
2 Tax Configuration · Tax Codes (+tabs Types/Rates/Exemptions/Jurisdictions/Accounts) — A.
3 Tax Periods (statuses + taxable/output/input/net + close/view) — A.
4 VAT Return working paper (+drill-down + reconciliation check) — A.
5 Tax Reconciliation (Expected/Calculated/Posted/Reported/Variance + exceptions) — A.
6 WHT Certificates — A.  7 Tax Reports (20 tiles) — A.  8 Tax Audit Trail — A.
9 Current Tax Position (Collected/Recoverable/Adjustments/Paid/Outstanding) — B.
10 VAT Control Account (liability ledger, running Cr balance) — B.
11 Tax Payments (register + Record Payment form; PAYABLE→PAID) — B.
12 Tax Recognition Rules (invoice/cash/payment/accrual per type) — B.
Search on 2/6/8/11 uses system live-search overlaying everything (§C2).
Labeling stays as shown in the mockups per §C5 — do not genericize VAT-specific screens.

==================== 5 · PERMISSIONS ====================
View tax: finance roles. Configure codes/rates/rules: Finance Manager. Record payment:
Accountant. Approve adjustments/returns: Finance Manager. Close period: Finance Manager.
Issue certificates: Accountant. Segregation: preparer ≠ approver, enforced server-side
(not only hidden/disabled in the UI). Enforce + audit every mutating action per §0
tax_audit_trail.

==================== 6 · REPORTS (20) ====================
VAT Transaction/Input/Output/Return Summary/Reconciliation/Audit Trail; Tax Liability;
WHT Report/Certificates; Exemption; Zero-Rated Sales; Taxable Sales/Purchases;
Adjustments; Tax Account Ledger; Period Summary; Payable/Receivable; Transaction Register;
Exception; Audit Report. All use system currency + live figures from tax_transactions.

==================== 7 · ACCESSIBILITY / RESPONSIVE ====================
Tables th scope; search overlay keyboard-navigable; tables horizontal-scroll (no page
overflow) 1280/1024/768; text-size 90–125 no clipping; no console/build errors.

==================== 8 · VERIFY ====================
8.1 All 12 routes render per APPENDIX A/B (screenshot-compare). 8.2 Engine: inclusive/
exclusive, zero/exempt, WHT, reverse charge, apportionment, rounding (line + document
level), recognition basis, rate-by-date all correct — backed by the Stage 2 unit tests.
8.3 GL postings match §2 incl. rounding residual account; control account ties to GL.
8.4 Workflows §3 incl. PAYABLE→PAID, period lock, and reversal-lands-in-open-period
(§1.15). 8.5 Currency from system (§C1); search overlay above everything (§C2).
8.6 Permissions enforced server-side + audit trail written for every mutating action.
8.7 Concurrency: optimistic-lock rejection proven on period/return status transitions.
8.8 Atomicity: forced-failure rollback test proven (§1.14). 8.9 No console/build errors;
existing modules untouched (only called via §1.13 contract).
REPORT: schema DDL; engine formula confirmation (incl. rounding/apportionment/reverse-
charge worked examples with numbers); posting samples; workflow confirmations incl.
reversal-against-closed-period example; currency + search confirmation; permissions
matrix; concurrency + atomicity test evidence; explicit note on C4 (multi-entity) and
certificate-numbering-reset (annual vs continuous) decisions taken; NO SCREEN SKIPPED.

==================== APPENDIX A — MOCKUP SCREENS 1–8 (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Taxation Module — mockups</title>
<style>
  :root{--deep-1:#17565d;--deep-2:#0c3539;--sec:#128F8E;--sec-2:#149897;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--green:#15803d;--red-2:#b91c1c;--amber-2:#b45309;--hair:#EEF3F1;
    --shadow-card:0 1px 2px rgba(10,42,46,.04),0 10px 30px -10px rgba(10,42,46,.10),0 30px 60px -30px rgba(8,40,44,.12);}
  *{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
  body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:#eef4f4;color:#374151;font-size:14px;-webkit-font-smoothing:antialiased}
  :focus-visible{outline:2px solid #94a3b8;outline-offset:2px}
  .wrap{max-width:1440px;margin:0 auto;padding:0 28px 80px}
  .opt-tag{display:inline-flex;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--deep-1);background:rgba(17,69,75,.08);border:1px solid rgba(17,69,75,.22);border-radius:999px;padding:5px 12px;margin:44px 0 14px}
  .page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 0 6px}
  .page-head h1{font-size:22px;font-weight:800;color:var(--ink)}
  .page-head .sub{font-size:12.5px;color:var(--muted);margin-top:4px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:12px;font-weight:600;font-size:13px;border:1px solid transparent;cursor:pointer;font-family:inherit;transition:all .14s;white-space:nowrap}
  .btn-ghost{background:#e8f0f0;border-color:var(--border);color:var(--ink)}
  .btn-ghost:hover{background:#dceaea}
  .btn-sec{color:#fff;background:var(--sec);box-shadow:0 8px 18px -8px rgba(18,143,142,.5)}
  .btn-cta{color:#fff;background:var(--deep-2);font-weight:700;box-shadow:0 10px 22px -10px rgba(8,40,44,.55)}
  .btn-sm{height:36px;padding:0 14px;font-size:12px;border-radius:10px}
  .tabs{display:flex;gap:4px;border-bottom:1px solid var(--line);margin:12px 0 18px;overflow-x:auto;scrollbar-width:none}
  .tab{flex:none;padding:10px 14px 12px;font-size:12.5px;font-weight:700;color:var(--sub);border-bottom:2.5px solid transparent;margin-bottom:-1px;text-decoration:none;white-space:nowrap}
  .tab:hover{color:var(--ink)}
  .tab.on{color:var(--sec);border-bottom-color:var(--sec)}
  .card{background:rgba(255,255,255,.92);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow-card);overflow:hidden}
  .card-h{display:flex;align-items:center;gap:10px;padding:15px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .card-h .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px}
  .card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
  .card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .pad{padding:20px 24px}
  .mb{margin-bottom:16px}
  .kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin:14px 0 16px}
  @media (max-width:1100px){.kpis{grid-template-columns:repeat(2,1fr)}}
  .kpi{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.94)}
  .kpi .l{font-size:9.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
  .kpi .v{margin-top:5px;font-size:1.25rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  .kpi .n{margin-top:3px;font-size:10.5px;font-weight:700;color:var(--muted)}
  .kpi.hero{background:var(--sec);border:none}.kpi.hero .l{color:#dff7f6}.kpi.hero .v{color:#fff}.kpi.hero .n{color:#dff7f6}
  .grid2{display:grid;grid-template-columns:1.5fr 1fr;gap:16px;align-items:start}
  @media (max-width:1100px){.grid2{grid-template-columns:1fr}}
  .li-wrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:860px}
  thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
  th.num,td.num{text-align:right}
  tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle;color:var(--sub)}
  td.num{font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
  tbody tr:hover td{background:rgba(17,69,75,.04)}
  tbody tr:last-child td{border-bottom:none}
  tfoot td{padding:12px;border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink)}
  .mono{font-family:ui-monospace,Menlo,monospace;font-size:12px}
  .jl{color:var(--sec);font-weight:700;text-decoration:none}
  .jl:hover{text-decoration:underline}
  .name{font-weight:600;color:var(--ink)}
  .em{color:var(--muted)}
  .neg{color:var(--red-2)}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
  .badge .bdot{width:6px;height:6px;border-radius:50%}
  .b-ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.4);color:var(--green)}.b-ok .bdot{background:#22c55e}
  .b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:#d97706}
  .b-rev{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-rev .bdot{background:var(--red-2)}
  .b-post{background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.4);color:var(--sec)}.b-post .bdot{background:var(--sec)}
  .b-off{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-off .bdot{background:var(--muted)}
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .t-vat{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .t-wht{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
  .t-paye{background:rgba(12,53,57,.08);border:1px solid rgba(12,53,57,.3);color:var(--deep-2)}
  .t-fbt{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}
  .chart{display:flex;gap:16px;align-items:flex-end;height:150px;padding:10px 6px 0}
  .cg{flex:1;display:flex;flex-direction:column;gap:6px;align-items:center}
  .cb{display:flex;gap:5px;align-items:flex-end;height:120px;width:100%;justify-content:center}
  .bar{width:26px;border-radius:6px 6px 0 0}
  .bar.in{background:rgba(18,143,142,.55)}
  .bar.out{background:var(--deep-2)}
  .cl{font-size:10px;font-weight:700;color:var(--muted)}
  .legend{display:flex;gap:16px;margin-top:10px;font-size:11px;color:var(--muted);font-weight:600}
  .legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px}
  .tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  @media (max-width:1100px){.tiles{grid-template-columns:repeat(2,1fr)}}
  @media (max-width:640px){.tiles{grid-template-columns:1fr}}
  .tile{border:1px solid var(--border);border-radius:13px;background:rgba(255,255,255,.94);padding:14px;display:flex;gap:10px;align-items:center;text-decoration:none;color:var(--ink);font-size:12.5px;font-weight:700;transition:all .14s}
  .tile:hover{border-color:rgba(18,143,142,.45);transform:translateY(-1px)}
  .tile .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px;flex:none}
  .dl{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--hair);font-size:12.5px}
  .dl:last-child{border-bottom:none}
  .dl .l{color:var(--muted);font-weight:600}
  .dl .v{font-weight:700;color:var(--ink);font-variant-numeric:tabular-nums}
  .dl .v.red{color:var(--red-2)}
  .sect-t{font-size:10.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin:14px 0 6px}
</style>
</head>
<body>
<div class="wrap">

<!-- 1 · TAX DASHBOARD -->
<div><span class="opt-tag">1 · Tax Dashboard</span>
  <div class="page-head"><div><h1>Tax Dashboard</h1><div class="sub">Live tax position across VAT, withholding and payroll.</div></div>
    <div style="display:flex;gap:10px"><button class="btn btn-ghost">Tax Periods</button><button class="btn btn-cta">Prepare Return</button></div></div>
  <div class="kpis">
    <div class="kpi hero"><div class="l">VAT Payable</div><div class="v">K537,150</div><div class="n">Jul 2026 · due 10 Sep</div></div>
    <div class="kpi"><div class="l">Output VAT</div><div class="v">K1,394,250</div><div class="n">Jul 2026</div></div>
    <div class="kpi"><div class="l">Input VAT</div><div class="v">K844,800</div><div class="n">Jul 2026</div></div>
    <div class="kpi"><div class="l">WHT Payable</div><div class="v">K215,400</div><div class="n">due 14 Sep</div></div>
    <div class="kpi"><div class="l">Current Period</div><div class="v" style="font-size:15px">Aug 2026</div><div class="n" style="color:var(--green)">Open</div></div>
  </div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">📊</span><h2>Input vs Output VAT — last 6 months</h2></div>
      <div class="pad">
        <div class="chart">
          <div class="cg"><div class="cb"><div class="bar in" style="height:52%"></div><div class="bar out" style="height:70%"></div></div><span class="cl">Mar</span></div>
          <div class="cg"><div class="cb"><div class="bar in" style="height:60%"></div><div class="bar out" style="height:76%"></div></div><span class="cl">Apr</span></div>
          <div class="cg"><div class="cb"><div class="bar in" style="height:48%"></div><div class="bar out" style="height:80%"></div></div><span class="cl">May</span></div>
          <div class="cg"><div class="cb"><div class="bar in" style="height:66%"></div><div class="bar out" style="height:88%"></div></div><span class="cl">Jun</span></div>
          <div class="cg"><div class="cb"><div class="bar in" style="height:62%"></div><div class="bar out" style="height:96%"></div></div><span class="cl">Jul</span></div>
          <div class="cg"><div class="cb"><div class="bar in" style="height:30%"></div><div class="bar out" style="height:44%"></div></div><span class="cl">Aug</span></div>
        </div>
        <div class="legend"><span><i style="background:rgba(18,143,142,.55)"></i>Input VAT</span><span><i style="background:var(--deep-2)"></i>Output VAT</span></div>
      </div></div>
    <div>
      <div class="card mb"><div class="card-h"><span class="ic">⏰</span><h2>Upcoming Filing Deadlines</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">VAT Return · Jul 2026</span><span class="v">10 Sep 2026</span></div>
          <div class="dl"><span class="l">PAYE · Aug 2026</span><span class="v">10 Sep 2026</span></div>
          <div class="dl"><span class="l">WHT · Aug 2026</span><span class="v">14 Sep 2026</span></div>
          <div class="dl"><span class="l">Unfiled periods</span><span class="v red">1</span></div>
        </div></div>
      <div class="card"><div class="card-h"><span class="ic">⚠</span><h2>Tax Exceptions</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">Missing tax code</span><span class="v red">3</span></div>
          <div class="dl"><span class="l">Invalid tax treatment</span><span class="v red">1</span></div>
          <div class="dl"><span class="l">Unposted tax transactions</span><span class="v">2</span></div>
        </div></div>
    </div>
  </div>
</div>

<!-- 2 · TAX CODES -->
<div><span class="opt-tag">2 · Tax Configuration · Tax Codes</span>
  <div class="page-head"><div><h1>Tax Codes</h1><div class="sub">Central tax-code master — rates configurable, never hard-coded.</div></div>
    <button class="btn btn-cta">＋ New Tax Code</button></div>
  <div class="tabs"><a class="tab" href="#">Tax Types</a><a class="tab on" href="#">Tax Codes</a><a class="tab" href="#">Tax Rates</a><a class="tab" href="#">Exemptions</a><a class="tab" href="#">Jurisdictions</a><a class="tab" href="#">Tax Accounts</a></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Code</th><th>Description</th><th class="num">Rate (%)</th><th>Type</th><th>Treatment</th><th>Effective</th><th>Status</th><th></th></tr></thead><tbody>
    <tr><td class="mono">VAT_STD</td><td class="name">Standard VAT</td><td class="num">16.50</td><td><span class="tchip t-vat">VAT</span></td><td class="em">Output / Input</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">VAT_ZERO</td><td class="name">Zero Rated</td><td class="num">0.00</td><td><span class="tchip t-vat">VAT</span></td><td class="em">Zero-rated</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">VAT_EXEMPT</td><td class="name">Exempt</td><td class="num">0.00</td><td><span class="tchip t-vat">VAT</span></td><td class="em">Exempt</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">WHT_SUP</td><td class="name">Supplier Withholding</td><td class="num">10.00</td><td><span class="tchip t-wht">WHT</span></td><td class="em">Deducted</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">WHT_SERV</td><td class="name">Service Withholding</td><td class="num">15.00</td><td><span class="tchip t-wht">WHT</span></td><td class="em">Deducted</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">FBT_STD</td><td class="name">Fringe Benefits Tax</td><td class="num">30.00</td><td><span class="tchip t-fbt">FBT</span></td><td class="em">Charged</td><td class="em">01 Jan 2024</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
  </tbody></table></div></div>
</div>

<!-- 3 · TAX PERIODS -->
<div><span class="opt-tag">3 · Tax Periods</span>
  <div class="page-head"><div><h1>Tax Periods</h1><div class="sub">Open, prepare, submit and close filing periods.</div></div>
    <button class="btn btn-ghost">＋ Generate Periods</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Period</th><th>Start</th><th>End</th><th>Status</th><th class="num">Taxable Sales</th><th class="num">Output Tax</th><th class="num">Taxable Purchases</th><th class="num">Input Tax</th><th class="num">Net Payable</th><th></th></tr></thead><tbody>
    <tr><td class="name">Aug 2026</td><td class="em">01 Aug 2026</td><td class="em">31 Aug 2026</td><td><span class="badge b-ok"><span class="bdot"></span>Open</span></td><td class="num">4,120,000</td><td class="num">679,800</td><td class="num">2,300,000</td><td class="num">379,500</td><td class="num">300,300</td><td class="row-act"><button class="btn btn-ghost btn-sm">Close</button></td></tr>
    <tr><td class="name">Jul 2026</td><td class="em">01 Jul 2026</td><td class="em">31 Jul 2026</td><td><span class="badge b-pend"><span class="bdot"></span>Submitted</span></td><td class="num">8,450,000</td><td class="num">1,394,250</td><td class="num">5,120,000</td><td class="num">844,800</td><td class="num">537,150</td><td class="row-act"><button class="btn btn-ghost btn-sm">View</button></td></tr>
    <tr><td class="name">Jun 2026</td><td class="em">01 Jun 2026</td><td class="em">30 Jun 2026</td><td><span class="badge b-rev"><span class="bdot"></span>Closed</span></td><td class="num">7,900,000</td><td class="num">1,303,500</td><td class="num">4,600,000</td><td class="num">759,000</td><td class="num">544,500</td><td class="row-act"><button class="btn btn-ghost btn-sm">View</button></td></tr>
  </tbody></table></div></div>
</div>

<!-- 4 · VAT RETURN -->
<div><span class="opt-tag">4 · VAT Return · Jul 2026</span>
  <div class="page-head"><div><h1 style="display:flex;gap:10px;align-items:center">VAT Return <span class="badge b-pend"><span class="bdot"></span>Submitted</span></h1><div class="sub">Tax period July 2026 · auto-generated working paper.</div></div>
    <div style="display:flex;gap:10px"><button class="btn btn-ghost">Export</button><button class="btn btn-cta">Approve &amp; File</button></div></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🧾</span><h2>Return Working Paper</h2></div>
      <div class="pad">
        <div class="sect-t">Output Tax</div>
        <div class="dl"><span class="l">Taxable Sales <a class="jl" href="#">drill-down</a></span><span class="v">8,450,000</span></div>
        <div class="dl"><span class="l">Output VAT</span><span class="v">1,394,250</span></div>
        <div class="sect-t">Input Tax</div>
        <div class="dl"><span class="l">Taxable Purchases <a class="jl" href="#">drill-down</a></span><span class="v">5,120,000</span></div>
        <div class="dl"><span class="l">Input VAT</span><span class="v">844,800</span></div>
        <div class="dl"><span class="l">Adjustments</span><span class="v red">(12,300)</span></div>
        <div class="dl" style="border-top:1.5px solid var(--deep-1);margin-top:8px;padding-top:12px"><span class="l" style="font-weight:800;color:var(--ink)">Net VAT Payable</span><span class="v">537,150</span></div>
      </div></div>
    <div class="card"><div class="card-h"><span class="ic">🔁</span><h2>Reconciliation Check</h2></div>
      <div class="pad">
        <div class="dl"><span class="l">Expected (GL)</span><span class="v">537,150</span></div>
        <div class="dl"><span class="l">Calculated (return)</span><span class="v">537,150</span></div>
        <div class="dl"><span class="l">Posted</span><span class="v">537,150</span></div>
        <div class="dl"><span class="l">Variance</span><span class="v" style="color:var(--green)">0</span></div>
        <div class="dl"><span class="l">Filing date</span><span class="v">08 Aug 2026</span></div>
        <div class="dl"><span class="l">Reference</span><span class="v mono">VAT-2026-07</span></div>
      </div></div>
  </div>
</div>

<!-- 5 · TAX RECONCILIATION -->
<div><span class="opt-tag">5 · Tax Reconciliation</span>
  <div class="page-head"><div><h1>Tax Reconciliation</h1><div class="sub">Accounting transactions → tax ledger → tax return.</div></div>
    <button class="btn btn-ghost">Run Reconciliation</button></div>
  <div class="card mb"><div class="li-wrap"><table><thead><tr><th>Figure</th><th class="num">Expected</th><th class="num">Calculated</th><th class="num">Posted</th><th class="num">Reported</th><th class="num">Variance</th></tr></thead><tbody>
    <tr><td class="name">Output VAT · Jul</td><td class="num">1,394,250</td><td class="num">1,394,250</td><td class="num">1,394,250</td><td class="num">1,394,250</td><td class="num" style="color:var(--green)">0</td></tr>
    <tr><td class="name">Input VAT · Jul</td><td class="num">857,100</td><td class="num">844,800</td><td class="num">844,800</td><td class="num">844,800</td><td class="num neg">(12,300)</td></tr>
    <tr><td class="name">WHT · Jul</td><td class="num">215,400</td><td class="num">215,400</td><td class="num">215,400</td><td class="num">215,400</td><td class="num" style="color:var(--green)">0</td></tr>
  </tbody></table></div></div>
  <div class="card"><div class="card-h"><span class="ic">⚠</span><h2>Exceptions</h2></div>
    <div class="li-wrap"><table><thead><tr><th>Ref</th><th>Issue</th><th>Source</th><th class="num">Amount</th><th>Status</th></tr></thead><tbody>
      <tr><td class="mono">INV-1042</td><td>Missing tax code</td><td class="em">Sales</td><td class="num">45,000</td><td><span class="badge b-rev"><span class="bdot"></span>Unresolved</span></td></tr>
      <tr><td class="mono">BILL-0871</td><td>Invalid tax treatment</td><td class="em">Purchases</td><td class="num">12,300</td><td><span class="badge b-pend"><span class="bdot"></span>In review</span></td></tr>
      <tr><td class="mono">EXP-0339</td><td>Unposted tax transaction</td><td class="em">Expenses</td><td class="num">8,150</td><td><span class="badge b-off"><span class="bdot"></span>Queued</span></td></tr>
    </tbody></table></div></div>
</div>

<!-- 6 · WHT CERTIFICATES -->
<div><span class="opt-tag">6 · Withholding Tax · Certificates</span>
  <div class="page-head"><div><h1>Withholding Certificates</h1><div class="sub">Auto-issued on deducted supplier payments.</div></div>
    <button class="btn btn-ghost">Export</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Certificate №</th><th>Supplier</th><th>Type</th><th class="num">Rate (%)</th><th class="num">Gross</th><th class="num">WHT</th><th>Status</th><th></th></tr></thead><tbody>
    <tr><td class="mono">WHT-000126</td><td class="name">Beta Industries</td><td><span class="tchip t-wht">Service</span></td><td class="num">15.00</td><td class="num">500,000</td><td class="num">75,000</td><td><span class="badge b-ok"><span class="bdot"></span>Issued</span></td><td class="row-act"><button class="ibtn">🖨</button></td></tr>
    <tr><td class="mono">WHT-000125</td><td class="name">Mvera Logistics</td><td><span class="tchip t-wht">Supplier</span></td><td class="num">10.00</td><td class="num">1,400,000</td><td class="num">140,000</td><td><span class="badge b-ok"><span class="bdot"></span>Issued</span></td><td class="row-act"><button class="ibtn">🖨</button></td></tr>
    <tr><td class="mono">WHT-000124</td><td class="name">Kandodo Consultants</td><td><span class="tchip t-wht">Service</span></td><td class="num">15.00</td><td class="num">2,000,000</td><td class="num">300,000</td><td><span class="badge b-pend"><span class="bdot"></span>Draft</span></td><td class="row-act"><button class="ibtn">🖨</button></td></tr>
  </tbody></table></div></div>
</div>

<!-- 7 · TAX REPORTS -->
<div><span class="opt-tag">7 · Tax Reports</span>
  <div class="page-head"><div><h1>Tax Reports</h1><div class="sub">Statutory and management tax reporting.</div></div></div>
  <div class="tiles">
    <a class="tile" href="#"><span class="ic">🧾</span>VAT Transaction Report</a>
    <a class="tile" href="#"><span class="ic">⬇</span>VAT Input Report</a>
    <a class="tile" href="#"><span class="ic">⬆</span>VAT Output Report</a>
    <a class="tile" href="#"><span class="ic">📑</span>VAT Return Summary</a>
    <a class="tile" href="#"><span class="ic">🔁</span>VAT Reconciliation</a>
    <a class="tile" href="#"><span class="ic">🕵</span>VAT Audit Trail</a>
    <a class="tile" href="#"><span class="ic">💰</span>Tax Liability Report</a>
    <a class="tile" href="#"><span class="ic">🧮</span>Withholding Tax Report</a>
    <a class="tile" href="#"><span class="ic">📜</span>WHT Certificates</a>
    <a class="tile" href="#"><span class="ic">🚫</span>Tax Exemption Report</a>
    <a class="tile" href="#"><span class="ic">0%</span>Zero-Rated Sales</a>
    <a class="tile" href="#"><span class="ic">🛒</span>Taxable Purchases</a>
    <a class="tile" href="#"><span class="ic">🛍</span>Taxable Sales</a>
    <a class="tile" href="#"><span class="ic">🛠</span>Tax Adjustments</a>
    <a class="tile" href="#"><span class="ic">📒</span>Tax Account Ledger</a>
    <a class="tile" href="#"><span class="ic">🗓</span>Tax Period Summary</a>
    <a class="tile" href="#"><span class="ic">⚖</span>Tax Payable / Receivable</a>
    <a class="tile" href="#"><span class="ic">📚</span>Tax Transaction Register</a>
    <a class="tile" href="#"><span class="ic">⚠</span>Tax Exception Report</a>
    <a class="tile" href="#"><span class="ic">🔐</span>Tax Audit Report</a>
  </div>
</div>

<!-- 8 · TAX AUDIT TRAIL -->
<div><span class="opt-tag">8 · Tax Audit Trail</span>
  <div class="page-head"><div><h1>Tax Audit Trail</h1><div class="sub">Immutable log of every tax-related change.</div></div>
    <button class="btn btn-ghost">Export</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Date / Time</th><th>User</th><th>Transaction</th><th>Change</th><th>Reason</th><th>Approval</th></tr></thead><tbody>
    <tr><td class="em">19 Aug 2026 09:14</td><td class="name">E. Seyama</td><td class="mono">INV-1042</td><td>Tax code VAT_STD → VAT_ZERO · rate 16.5 → 0</td><td class="em">Export zero-rating</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td></tr>
    <tr><td class="em">18 Aug 2026 16:40</td><td class="name">M. Banda</td><td class="mono">BILL-0871</td><td>Input VAT 12,300 reversed</td><td class="em">Credit note received</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td></tr>
    <tr><td class="em">17 Aug 2026 11:02</td><td class="name">P. Phiri</td><td class="mono">PERIOD-2026-06</td><td>Period Closed</td><td class="em">Filing complete</td><td><span class="badge b-post"><span class="bdot"></span>System</span></td></tr>
  </tbody></table></div></div>
</div>

</div>
</body>
</html>
```

==================== APPENDIX B — MOCKUP SCREENS 9–12 (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Taxation — Liability Ledger, Payments, Position, Recognition</title>
<style>
  :root{--deep-1:#17565d;--deep-2:#0c3539;--sec:#128F8E;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--green:#15803d;--red-2:#b91c1c;--amber-2:#b45309;--hair:#EEF3F1;
    --shadow-card:0 1px 2px rgba(10,42,46,.04),0 10px 30px -10px rgba(10,42,46,.10),0 30px 60px -30px rgba(8,40,44,.12);}
  *{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
  body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:#eef4f4;color:#374151;font-size:14px;-webkit-font-smoothing:antialiased}
  :focus-visible{outline:2px solid #94a3b8;outline-offset:2px}
  .wrap{max-width:1440px;margin:0 auto;padding:0 28px 80px}
  .opt-tag{display:inline-flex;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--deep-1);background:rgba(17,69,75,.08);border:1px solid rgba(17,69,75,.22);border-radius:999px;padding:5px 12px;margin:44px 0 14px}
  .page-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 0 6px}
  .page-head h1{font-size:22px;font-weight:800;color:var(--ink)}
  .page-head .sub{font-size:12.5px;color:var(--muted);margin-top:4px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:12px;font-weight:600;font-size:13px;border:1px solid transparent;cursor:pointer;font-family:inherit;transition:all .14s;white-space:nowrap}
  .btn-ghost{background:#e8f0f0;border-color:var(--border);color:var(--ink)}
  .btn-ghost:hover{background:#dceaea}
  .btn-sec{color:#fff;background:var(--sec);box-shadow:0 8px 18px -8px rgba(18,143,142,.5)}
  .btn-cta{color:#fff;background:var(--deep-2);font-weight:700;box-shadow:0 10px 22px -10px rgba(8,40,44,.55)}
  .btn-sm{height:36px;padding:0 14px;font-size:12px;border-radius:10px}
  .card{background:rgba(255,255,255,.92);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow-card);overflow:hidden}
  .card-h{display:flex;align-items:center;gap:10px;padding:15px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .card-h .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px}
  .card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
  .card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .pad{padding:20px 24px}
  .mb{margin-bottom:16px}
  .chips{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
  @media (max-width:1000px){.chips{grid-template-columns:1fr 1fr}}
  .chipbox{border:1px solid var(--border);border-radius:13px;padding:12px 14px;background:rgba(255,255,255,.94)}
  .chipbox .l{font-size:9.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
  .chipbox .v{margin-top:4px;font-size:1.1rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  .chipbox .v.green{color:var(--green)}.chipbox .v.red{color:var(--red-2)}
  .li-wrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:900px}
  thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
  th.num,td.num{text-align:right}
  tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle;color:var(--sub)}
  td.num{font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
  tbody tr:hover td{background:rgba(17,69,75,.04)}
  tbody tr:last-child td{border-bottom:none}
  tfoot td{padding:12px;border-top:1.5px solid var(--deep-1);font-weight:800;color:var(--ink)}
  .mono{font-family:ui-monospace,Menlo,monospace;font-size:12px}
  .name{font-weight:600;color:var(--ink)}
  .em{color:var(--muted)}
  .neg{color:var(--red-2)}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
  .badge .bdot{width:6px;height:6px;border-radius:50%}
  .b-ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.4);color:var(--green)}.b-ok .bdot{background:#22c55e}
  .b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:#d97706}
  .b-rev{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-rev .bdot{background:var(--red-2)}
  .b-post{background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.4);color:var(--sec)}.b-post .bdot{background:var(--sec)}
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .t-vat{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .t-wht{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
  .t-paye{background:rgba(12,53,57,.08);border:1px solid rgba(12,53,57,.3);color:var(--deep-2)}
  .grid2{display:grid;grid-template-columns:1.5fr 1fr;gap:16px;align-items:start}
  @media (max-width:1100px){.grid2{grid-template-columns:1fr}}
  .dl{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--hair);font-size:12.5px}
  .dl:last-child{border-bottom:none}
  .dl .l{color:var(--muted);font-weight:600}
  .dl .v{font-weight:700;color:var(--ink);font-variant-numeric:tabular-nums}
  .g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
  @media (max-width:900px){.g3{grid-template-columns:1fr}}
  .f label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
  .f .in{width:100%;height:42px;border-radius:11px;border:1px solid var(--border);background:#fff;padding:0 13px;font-size:13.5px;color:var(--ink);font-family:inherit}
  .f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 13px center;padding-right:32px}
  .f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.13)}
  .note{margin-top:12px;border:1px dashed rgba(18,143,142,.5);background:rgba(18,143,142,.06);border-radius:12px;padding:10px 12px;font-size:11.5px;font-weight:700;color:var(--sec)}
</style>
</head>
<body>
<div class="wrap">

<!-- 9 · CURRENT TAX POSITION -->
<div><span class="opt-tag">9 · Current Tax Position</span>
  <div class="page-head"><div><h1>Current Tax Position</h1><div class="sub">Collected vs recoverable vs paid vs outstanding — real-time liability picture.</div></div>
    <button class="btn btn-ghost">Export</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr>
    <th>Tax</th><th class="num">Collected</th><th class="num">Recoverable</th><th class="num">Adjustments</th><th class="num">Paid</th><th class="num">Outstanding</th></tr></thead><tbody>
    <tr><td><span class="tchip t-vat">VAT</span></td><td class="num">15,500,000</td><td class="num">9,200,000</td><td class="num">300,000</td><td class="num">4,000,000</td><td class="num neg">2,600,000</td></tr>
    <tr><td><span class="tchip t-wht">WHT</span></td><td class="num">2,100,000</td><td class="num">—</td><td class="num">0</td><td class="num">1,500,000</td><td class="num neg">600,000</td></tr>
    <tr><td><span class="tchip t-paye">PAYE</span></td><td class="num">5,800,000</td><td class="num">—</td><td class="num">0</td><td class="num">5,000,000</td><td class="num neg">800,000</td></tr>
  </tbody>
  <tfoot><tr><td>Total outstanding</td><td></td><td></td><td></td><td></td><td class="num neg">4,000,000</td></tr></tfoot></table></div></div>
</div>

<!-- 10 · VAT CONTROL ACCOUNT -->
<div><span class="opt-tag">10 · VAT Control Account · Tax Liability Ledger</span>
  <div class="page-head"><div><h1>VAT Control Account</h1><div class="sub">Tax collected (credit) vs recoverable + payments (debit) → outstanding liability.</div></div>
    <button class="btn btn-ghost">Export</button></div>
  <div class="chips">
    <div class="chipbox"><div class="l">Collected (Output)</div><div class="v">165,000</div></div>
    <div class="chipbox"><div class="l">Recoverable (Input)</div><div class="v">82,500</div></div>
    <div class="chipbox"><div class="l">Paid</div><div class="v">82,500</div></div>
    <div class="chipbox"><div class="l">Outstanding</div><div class="v green">0</div></div>
  </div>
  <div class="card"><div class="card-h"><span class="ic">📒</span><h2>VAT Control Account — Jul 2026</h2>
    <div class="right"><span class="badge b-ok"><span class="bdot"></span>Fully settled</span></div></div>
    <div class="li-wrap"><table><thead><tr><th>Date</th><th>Particulars</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance (Cr)</th></tr></thead><tbody>
      <tr><td class="em">31 Jul 2026</td><td class="name">Output VAT — sales (collected)</td><td class="num">—</td><td class="num">165,000</td><td class="num">165,000</td></tr>
      <tr><td class="em">31 Jul 2026</td><td class="name">Input VAT — purchases (recoverable)</td><td class="num">82,500</td><td class="num">—</td><td class="num">82,500</td></tr>
      <tr><td class="em">08 Aug 2026</td><td class="name">VAT payment — MRA (PAY-001)</td><td class="num">82,500</td><td class="num">—</td><td class="num">0</td></tr>
    </tbody>
    <tfoot><tr><td colspan="2">Closing balance</td><td class="num">165,000</td><td class="num">165,000</td><td class="num">0</td></tr></tfoot></table></div></div>
</div>

<!-- 11 · TAX PAYMENTS -->
<div><span class="opt-tag">11 · Tax Payments (PAYABLE → PAID)</span>
  <div class="page-head"><div><h1>Tax Payments</h1><div class="sub">Record remittances to the authority and clear tax liabilities.</div></div>
    <button class="btn btn-cta">＋ Record Payment</button></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🏦</span><h2>Payments Register</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Date</th><th>Tax</th><th>Period</th><th class="num">Amount</th><th>Bank</th><th>Reference</th><th>Status</th></tr></thead><tbody>
        <tr><td class="em">08 Aug 2026</td><td><span class="tchip t-vat">VAT</span></td><td class="em">Jul 2026</td><td class="num">82,500</td><td class="em">NBC ···1042</td><td class="mono">PAY-001</td><td><span class="badge b-ok"><span class="bdot"></span>Paid</span></td></tr>
        <tr><td class="em">10 Aug 2026</td><td><span class="tchip t-paye">PAYE</span></td><td class="em">Jul 2026</td><td class="num">5,000,000</td><td class="em">NBC ···1042</td><td class="mono">PAY-002</td><td><span class="badge b-ok"><span class="bdot"></span>Paid</span></td></tr>
        <tr><td class="em">—</td><td><span class="tchip t-wht">WHT</span></td><td class="em">Aug 2026</td><td class="num">600,000</td><td class="em">—</td><td class="em">—</td><td><span class="badge b-pend"><span class="bdot"></span>Payable</span></td></tr>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><span class="ic">🧾</span><h2>Record Payment</h2></div>
      <div class="pad"><div class="g3">
        <div class="f"><label>Tax Type</label><select class="in"><option>VAT</option><option>WHT</option><option>PAYE</option></select></div>
        <div class="f"><label>Tax Period</label><select class="in"><option>Aug 2026</option><option>Jul 2026</option></select></div>
        <div class="f"><label>Amount</label><input class="in" value="600,000" style="text-align:right"></div>
        <div class="f"><label>Payment Date</label><input class="in" type="date" value="2026-08-20"></div>
        <div class="f"><label>Bank Account</label><select class="in"><option>NBC ···1042</option><option>Standard ···2210</option></select></div>
        <div class="f"><label>Tax Authority</label><input class="in" value="Malawi Revenue Authority"></div>
        <div class="f"><label>Payment Reference</label><input class="in" placeholder="e.g. PAY-003"></div>
        <div class="f"><label>Receipt №</label><input class="in" placeholder="Authority receipt"></div>
        <div class="f"><label>Recorded By</label><input class="in" value="E. Seyama" disabled style="background:var(--hair);color:var(--muted)"></div>
      </div>
      <div class="note">On save: Dr Tax Payable · Cr Bank — liability cleared and period status moves PAYABLE → PAID.</div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px"><button class="btn btn-ghost">Cancel</button><button class="btn btn-cta">Save Payment</button></div>
      </div></div>
  </div>
</div>

<!-- 12 · TAX RECOGNITION RULES -->
<div><span class="opt-tag">12 · Tax Recognition Rules (configurable)</span>
  <div class="page-head"><div><h1>Tax Recognition Rules</h1><div class="sub">Invoice-basis vs cash-basis per regime — not hard-coded.</div></div></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Tax Type</th><th>Recognition Basis</th><th>Timing Rule</th><th>VAT Charged vs Cash</th></tr></thead><tbody>
    <tr><td><span class="tchip t-vat">VAT</span></td><td><select class="f in" style="height:36px;border-radius:10px"><option>Invoice basis</option><option>Cash basis</option></select></td><td class="em">Tax due on invoice date regardless of receipt</td><td class="em">Charged 165,000 · received 0 → still liable</td></tr>
    <tr><td><span class="tchip t-wht">WHT</span></td><td><select class="f in" style="height:36px;border-radius:10px"><option>Payment basis</option><option>Invoice basis</option></select></td><td class="em">Deducted when supplier is paid</td><td class="em">—</td></tr>
    <tr><td><span class="tchip t-paye">PAYE</span></td><td><select class="f in" style="height:36px;border-radius:10px"><option>Payroll accrual</option></select></td><td class="em">Due on salary payment date</td><td class="em">—</td></tr>
  </tbody></table></div>
    <div class="note">The Tax Engine applies the configured basis when computing each period's collected/recoverable figures — changing a rule does not require code changes.</div></div>
</div>

</div>
</body>
</html>
```
