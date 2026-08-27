LEAVE MANAGEMENT SUB-MODULE (PAYROLL & HR) — FULL IMPLEMENTATION SPEC (SELF-CONTAINED, REV 2)
Separate leave ENGINE inside Payroll & HR (not mixed into payroll calc). Reference mockups:
APPENDIX A = core screens; APPENDIX B = core workflow extensions; APPENDIX C = reports +
on-leave notifications. Build to match them exactly.

==================== BUILD SEQUENCE — DO NOT PARALLELIZE ====================
Build in this exact order. Do not start a stage until the previous stage's checklist
passes. If a later stage exposes a problem in an earlier stage's design, stop, fix the
earlier stage, re-run its checklist, then continue — do not patch around it downstream.

STAGE 1 — SCHEMA
  Implement §0 DDL exactly (incl. REV 2 additions). Migrations + seed data: a few
  leave_types (incl. one half-day-eligible, one gender-restricted, one with
  negative-balance allowed), one approval rule, public_holidays, working_days_config.
  ✓ Checklist: all tables/constraints/indexes created; FKs valid; seed data loads cleanly;
    no orphaned FKs; leave_applications.version column present for optimistic locking.

STAGE 2 — ENGINE + TESTS
  Implement §1 engine logic (incl. REV 2 §1.8–§1.17) as a standalone, unit-testable module
  with NO UI and NO payroll wiring yet.
  ✓ Checklist: unit tests green for — working-days calc (incl. half-day, public holidays,
    weekends per type config), balance gate (incl. negative-balance/advance-leave path),
    gender eligibility, overlap conflict detection, carry-forward + expiry, cancellation
    reversal math, year-boundary split (application crossing Dec→Jan), backdated-
    application gate, new-hire proration calc, termination freeze + final-balance calc.
    Test against concrete numeric examples, not just "runs without error."

STAGE 3 — PAYROLL INTEGRATION
  Implement §3 (incl. REV 2 additions) as a defined interface only — no changes to
  existing payroll calc/payslip/GL code.
  ✓ Checklist: daily-rate basis produces the same figure whether called for a DEDUCT line
    or an ENCASH payout for the same employee/date (§1.16); getApprovedLeaveForPayrollRun
    returns correct lines for a test period; confirmLeaveImported prevents the same
    application being pulled into two payroll runs; forced-failure test proves the
    ledger-write-plus-status-change is atomic (§1.17); existing payroll module files are
    untouched (diff check).

STAGE 4 — SCREENS
  Build all screens per APPENDIX A/B/C. Pure UI + read wiring against Stage 1–3 data. No
  new business logic here — if a screen seems to need new logic, stop and go back to
  Stage 2/3.
  ✓ Checklist: every screen in APPENDIX A/B/C renders, screenshot-compare against
    mockups, currency via shared money helper (no hardcoded symbols) on any money figure
    (encashment amount, leave liability report), search overlay per system constraints,
    responsive at 1280/1024/768, no console/build errors.

STAGE 5 — WORKFLOWS
  Wire the approval engine (§2, incl. REV 2 delegation), notifications (§6), and
  employee-lifecycle hooks (new hire, transfer, termination — REV 2 §1.11/§1.12) into the
  Stage 4 screens as callable actions.
  ✓ Checklist: full workflow test — apply → level 1 → level 2 → approve → ledger TAKEN
    post → notifications fire. Reject requires comment, blocks progression. Same-person
    guard proven. Escalation fires after configured days. Approver-on-leave routes to
    delegate immediately rather than waiting for escalation timer (§1.14). Withdrawal
    (pending) posts no ledger entry; cancellation (approved) posts a reversal, never a
    delete. New hire gets prorated entitlement automatically on onboarding event;
    terminated employee's accrual freezes on exit date and final balance is computed for
    encashment/forfeiture per leave_type policy.

STAGE 6 — PERMISSIONS + AUDIT
  Implement §5 role checks and the REV 2 leave_audit_trail writes for every mutating
  admin/config action (leave types, policies, entitlements, approval rules, notification
  settings, manual adjustments) — distinct from the leave_transactions balance ledger.
  ✓ Checklist: preparer≠approver enforced server-side; every config change and manual
    adjustment writes an audit row with old/new value, reason, user; unauthorized actions
    rejected with a clear error, not silently no-op'd.

STAGE 7 — VERIFICATION (§8)
  Run the full §8 checklist end to end and produce the REPORT specified at the bottom of
  this document. Only after Stage 7 passes is the module considered done.

==================== SYSTEM CONSTRAINTS ====================
SC1 currency from system setting (never hard-coded) — applies to any money figure
  (encashment amount, leave liability report), not just payroll screens.
SC2 system live-search results overlay above all content (z-index ≥9999).
SC3 pages render as mocked (no rails).
SC4 MULTI-ENTITY SCOPING — if the host system supports multiple companies/branches, every
   table in §0 carries a company_id (and branch_id if branches exist) FK, and every query/
   report/screen and approval-rule resolution is scoped to the active entity. If the
   system is single-entity only, skip this and note it explicitly in the final REPORT so
   the assumption is on record.

HARD GUARD — payroll processing/payslips/GL stay as-is; payroll RECEIVES approved leave
via the defined interface (§3/§1.16), never by querying leave tables directly. Leave
balances live only in the leave ledger (append-only, leave_transactions) — never edited
in place; config/admin changes are separately audited via leave_audit_trail (§6 REV 2).

==================== 0 · DATABASE SCHEMA ====================
leave_types(id, code UQ, name, description, gender ENUM[ALL,MALE,FEMALE] DEF ALL,
  entitlement_days, accrual_per_month, accrual_method ENUM[FIXED,MONTHLY,PRO_RATA],
  payroll_treatment ENUM[PAID,DEDUCT,CONFIGURABLE], min_days, max_days, max_continuous_days,
  carry_forward BOOL, carry_forward_limit, expiry_months, supporting_doc_required BOOL,
  advance_notice_days, count_weekends BOOL, count_public_holidays BOOL, requires_balance
  BOOL, allow_cancel BOOL, allow_withdraw BOOL, requires_hr_approval BOOL,
  approval_levels INT DEF 2, active)
leave_policies(id, name, description, effective_from, active)
leave_entitlements(id, employee_id FK, leave_type_id FK, year, days, pro_rata)
leave_balances(id, employee_id, leave_type_id, year, opening, accrued, taken, pending,
  adjustments, balance) — maintained ONLY by ledger posts
leave_applications(id, ref UQ, employee_id FK, leave_type_id FK, start_date, end_date,
  working_days, reason, status ENUM[DRAFT,SUBMITTED,LEVEL1,LEVEL2,LEVEL3,APPROVED,REJECTED,
  CANCELLED,WITHDRAWN,COMPLETED], supporting_doc, created_at)
leave_approval_rules(id, priority INT, approver1_kind/value, approver2_kind/value,
  approver3_kind/value, applies_kind ENUM[EMPLOYEE,DEPARTMENT,BRANCH,POSITION,GROUP,ALL],
  applies_value, active)
leave_approvals(id, application_id FK, level, approver_id FK, decision ENUM[APPROVED,
  REJECTED], comment, acted_at)
leave_transactions(id, employee_id, leave_type_id, application_id NULL, kind ENUM[OPENING,
  ACCRUAL,TAKEN,ADJUSTMENT,REVERSAL,ENCASH], days SIGNED, balance_after, ref, created_at)
leave_adjustments(id, employee_id, leave_type_id, days, reason, approved_by)
leave_encashments(id, employee_id, days, rate, amount, status, payroll_ref)
leave_notifications(id, user_id, kind, payload JSON, read_at)
notification_settings(channels in_app/email/sms/push; on_leave_start/return bools; audience)
public_holidays(id, date, name); working_days_config(week pattern)
leave_approver_delegations(id, approver_id FK, delegate_id FK, start_date, end_date,
  reason, active) — when approver_id is on approved leave (or manually delegates),
  pending/incoming approvals for them route to delegate_id instead of waiting for the
  escalation timer.
leave_audit_trail(id, user_id, acted_at, entity_kind, entity_id, field, old_value,
  new_value, reason, ip) — covers config/admin mutations (leave_types, leave_policies,
  leave_entitlements, leave_approval_rules, notification_settings, manual
  leave_adjustments). Distinct from leave_transactions, which is the balance ledger, not
  an audit log.
REL: applications→types/employees; approvals→applications; transactions→applications;
rules resolve approvers; entitlements/balances→employees+types; delegations→approvers.

REV 2 SCHEMA ADDITIONS:
leave_types — add: half_day_allowed BOOL DEF false; negative_balance_allowed BOOL DEF
  false; negative_balance_limit DEC NULL; backdated_allowed BOOL DEF false;
  backdated_max_days INT NULL; encashable_on_exit BOOL DEF false; forfeit_on_exit BOOL
  DEF false (exactly one of encashable_on_exit/forfeit_on_exit true when active=true,
  validated at save).
leave_applications — add: version INT DEF 0 (optimistic locking, §1.15); day_part ENUM
  [FULL,AM,PM] DEF FULL (half-day support, only valid when start_date=end_date and
  leave_types.half_day_allowed); is_backdated BOOL (system-computed at submit: true if
  start_date < submission date).
leave_entitlements — no structural change, but engine must auto-create prorated rows on
  hire (§1.11) rather than requiring manual entry.

==================== 1 · LEAVE ENGINE ====================
1.1 Working days from start/end using working_days_config + public_holidays, honouring
count_weekends/count_public_holidays per type. 1.2 Balance validation BEFORE submit,
client AND server: requested > available → "Insufficient leave balance…" + Apply disabled
(server must re-check). 1.3 Gender eligibility: hide ineligible types in form; enforce
server-side. 1.4 Conflicts: overlap approved/pending → block; dept-coverage → warn or block
(configurable). 1.5 Accrual monthly/pro-rata; carry-forward + expiry + max accumulation.
1.6 Withdrawal (pending, no deduction) vs Cancellation (approved → REVERSAL transaction of
unused days; before/during/after handled; completed needs HR adjustment). 1.7 Ledger
append-only; balances = Σ transactions; no manual balance edits.

1.8 HALF-DAY LEAVE
  Only permitted when leave_types.half_day_allowed and start_date=end_date. day_part
  (AM/PM) contributes 0.5 to working_days instead of 1. Two half-day applications for the
  same employee/date/leave_type are blocked as a duplicate/overlap, not summed.

1.9 NEGATIVE BALANCE / ADVANCE LEAVE
  Only permitted when leave_types.negative_balance_allowed. Balance may go negative up to
  negative_balance_limit (never unbounded); the balance check in §1.2 changes from
  "requested > available" to "requested > available + remaining negative headroom," and
  the applicant sees an explicit "this will use N days of advance leave" notice, not a
  silent pass. Types without negative_balance_allowed keep the original hard gate.

1.10 BACKDATED APPLICATIONS
  Only permitted when leave_types.backdated_allowed, and only within
  backdated_max_days of the current date. Backdated applications are flagged
  is_backdated=true and, regardless of leave_type's normal requires_hr_approval setting,
  always route through at least one HR-level approval step — client-side date pickers are
  advisory only, the server enforces this gate on submit.

1.11 NEW-HIRE PRORATION
  On an employee-onboarding event (hook from the HR employee master, not a manual leave
  action), the engine automatically creates the current year's leave_entitlements row per
  active leave_type using accrual_method PRO_RATA against the hire date (e.g. remaining
  months in the leave year ÷ 12 × entitlement_days), and posts the corresponding OPENING
  transaction. This must not require an HR admin to manually key in entitlement for every
  new hire.

1.12 TERMINATION / EXIT SETTLEMENT
  On an employee-termination event (hook from the HR employee master), the engine: (a)
  stops future accrual as of the termination date, (b) computes final balance per
  leave_type as of that date, (c) for leave_types.encashable_on_exit=true, creates a
  leave_encashments row for HR/Finance approval (§5) rather than auto-paying, (d) for
  forfeit_on_exit=true, posts a final ADJUSTMENT transaction reducing balance to zero with
  reason "forfeited on exit," visible in the audit trail. Pending/unapproved applications
  for a terminated employee are auto-rejected with a system comment, not left dangling.

1.13 YEAR-BOUNDARY APPLICATIONS
  An application whose date range crosses a leave-year boundary (as defined by
  leave_entitlements.year buckets) splits its TAKEN transaction into two lines, one per
  year, each validated against that year's balance independently. The application record
  itself remains a single row; the split is a ledger-level concern only.

1.14 APPROVER DELEGATION
  Before applying the escalation timer (§2.7), the approval engine checks
  leave_approver_delegations for an active delegation covering the assigned approver (set
  automatically when that approver's own leave is approved and covers the review window,
  or set manually by HR). If found, the pending approval routes to the delegate
  immediately; the original approver's identity and the delegation reason remain visible
  in the audit trail and on the approval record.

1.15 CONCURRENCY
  leave_applications.version supports optimistic locking on every status-changing action
  (submit, approve, reject, withdraw, cancel). Any action reads the current version,
  includes it in the update, and rejects with a clear "this application was just updated,
  please refresh" error if the version has moved — e.g. an employee withdrawing at the
  same moment a manager approves must not result in both actions silently succeeding.

1.16 DAILY-RATE BASIS
  A single, explicit, system-configured formula computes "daily rate" for an employee,
  used identically by both DEDUCT (unpaid leave deduction) and ENCASH (payout of unused
  days) — e.g. annual basic salary ÷ configured working-days-per-year. This lives as a
  system/payroll setting, not something the leave engine or payroll module each derive
  independently. Document the exact formula chosen in the final REPORT.

1.17 ATOMICITY
  For any status-changing action that posts a ledger transaction (approve → TAKEN,
  cancel → REVERSAL, adjustment → ADJUSTMENT, termination → final ADJUSTMENT), the
  leave_transactions write and the leave_applications.status/version update happen in one
  database transaction — both succeed or both roll back. Notification dispatch (§6) may
  be queued/async outside this transaction, but the balance-affecting write and the status
  change may never be split. Prove this with a forced-failure test in Stage 3.

==================== 2 · APPROVAL ENGINE (config-driven) ====================
2.1 Levels 1–3 from leave_types.approval_levels (not hard-coded). 2.2 Rule resolution
priority: Employee → Department → Branch → Position/Group → Default. 2.3 Same-person guard:
one user cannot approve two levels. 2.4 Level n+1 notified only after level n approval;
sees prior decisions/comments. 2.5 Decision buttons enabled ONLY on the review page
(mandatory open); reject requires comment. 2.6 On final approval → status APPROVED → ledger
TAKEN post → notifications. 2.7 Escalation: reminder → escalate to HR after configured
days; see §1.14 for the delegation check that runs before escalation.

==================== 3 · PAYROLL INTEGRATION ====================
Approved leave → engine determines treatment: PAID = no salary change; DEDUCT (unpaid) =
days × daily-rate (§1.16) deduction line; CONFIGURABLE per type. Encashment → payroll
earning. Payroll imports approved lines each run; posts to GL via existing payroll
postings. Never deduct merely because leave exists.

3.1 CALLING CONTRACT (Leave engine → Payroll) — payroll pulls via this interface only, it
never queries leave tables directly, and the leave engine never writes into payroll:
  getApprovedLeaveForPayrollRun(context: {
    company_id, branch_id?, payroll_period_start, payroll_period_end
  }) → [{
    employee_id, leave_type_id, application_ref, treatment: 'PAID'|'DEDUCT'|'ENCASH',
    days, daily_rate, amount, gl_hint
  }]
  confirmLeaveImported(application_refs: [ref]) → marks those lines consumed so the same
  application can never be pulled into two payroll runs (idempotency).
  Both calls are scoped to company_id (and branch_id if applicable, per SC4).

3.2 ENCASHMENT WORKFLOW
  Encashment is never auto-paid. A leave_encashments row (status PENDING) is created
  either by explicit employee/HR request or by the termination hook (§1.12), requires
  Finance+HR approval (§5) before it becomes an earning line payroll can pull, and once
  approved and imported, is marked with payroll_ref and cannot be re-imported.

==================== 4 · SCREENS (map to appendices) ====================
Dashboard, Calendar, My Leave (Apply/My Applications/Balance/History), Approvals (Pending/
First/Final/History), Administration (Types/Policies/Entitlements/Balances/Adjustments/
Transactions), Reports, HR Settings (Approval Rules/Notification Settings/Working Days/
Public Holidays/Escalation), Notification Centre. = APPENDIX A (1–8), B (A–E), C (F–G).

==================== 5 · PERMISSIONS ====================
Apply/own: all employees. Approve: per rules. HR admin (types/rules/entitlements/
adjustments): HR Manager. Encashment approve: Finance+HR. Reports: HR/finance. Segregation
preparer≠approver. Enforce + audit.

==================== 6 · NOTIFICATIONS ====================
In-app + email (+optional SMS/push) per notification_settings. Applicant notified on
submit/first-approval/final/reject/cancel/withdraw. Approver notified on submit + reminders.
Delegate approvers (§1.14) are notified in place of the original approver when a
delegation is active, and the notification states who they are standing in for.
On-leave announcements to audience with name, type, start and RETURN date; return-day
notice. All configurable.

REV 2: leave_audit_trail (§0) captures every config/admin mutation — leave_types,
leave_policies, leave_entitlements, leave_approval_rules, notification_settings, and
manual leave_adjustments — with old/new value, user, reason. This is separate from the
leave_transactions balance ledger and from user-facing notifications above.

==================== 7 · A11Y / RESPONSIVE / CONSTRAINTS ====================
Tables th scope; overlays keyboard-navigable; tables scroll (no page overflow) 1280/1024/768;
text 90–125 no clipping; pixel parity with appendices; system currency; search overlay
above everything; no console/build errors.

==================== 8 · VERIFY ====================
8.1 All screens render per appendices. 8.2 Engine: balance gate (incl. negative-balance
path), gender, conflicts, working days (incl. half-day, public holidays), carry-forward/
expiry, reversal math, year-boundary split, backdated-application gate, new-hire
proration, termination freeze/settlement — backed by Stage 2 unit tests. 8.3 Approval:
levels, priority routing, same-person guard, mandatory open + reject comment, escalation,
delegate routing. 8.4 Payroll: paid/deduct/configurable + encashment lines via the §3.1
contract only; GL via existing payroll postings; daily-rate basis identical across
DEDUCT and ENCASH (§1.16); idempotent import proven (confirmLeaveImported). 8.5 Ledger
append-only; balances tie; no manual balance edits anywhere in the codebase. 8.6
Notifications (incl. on-leave + return date + delegate stand-in) fire per settings.
8.7 Permissions enforced server-side + leave_audit_trail written for every config/admin
mutation (distinct from the balance ledger). 8.8 Concurrency: optimistic-lock rejection
proven on simultaneous application actions (§1.15). 8.9 Atomicity: forced-failure
rollback test proven for ledger-write-plus-status-change (§1.17). 8.10 No console/build
errors; payroll module files untouched (diff check).
REPORT: schema DDL; engine + approval formula confirmations (incl. half-day, proration,
termination-settlement, and negative-balance worked examples with numbers); payroll
sample postings incl. daily-rate formula used; notification matrix; concurrency +
atomicity test evidence; explicit note on SC4 (multi-entity) decision taken; parity
confirmation; NO SCREEN SKIPPED.

==================== APPENDIX A — CORE SCREENS (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Management — Payroll & HR sub-module</title>
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
  .btn-sm{height:34px;padding:0 13px;font-size:12px;border-radius:10px}
  .btn-ok{color:#fff;background:var(--green)}
  .btn-no{color:var(--red-2);background:#fff;border-color:rgba(185,28,28,.3)}
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
  .kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:14px 0 16px}
  @media (max-width:1000px){.kpis{grid-template-columns:1fr 1fr}}
  .kpi{border:1px solid var(--border);border-radius:14px;padding:14px 16px;background:rgba(255,255,255,.94)}
  .kpi .l{font-size:9.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)}
  .kpi .v{margin-top:5px;font-size:1.3rem;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
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
  .mono{font-family:ui-monospace,Menlo,monospace;font-size:12px}
  .name{font-weight:600;color:var(--ink)}
  .em{color:var(--muted)}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
  .badge .bdot{width:6px;height:6px;border-radius:50%}
  .b-ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.4);color:var(--green)}.b-ok .bdot{background:#22c55e}
  .b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:#d97706}
  .b-rev{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-rev .bdot{background:var(--red-2)}
  .b-post{background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.4);color:var(--sec)}.b-post .bdot{background:var(--sec)}
  .b-off{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-off .bdot{background:var(--muted)}
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .lt-ann{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .lt-sick{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
  .lt-mat{background:rgba(12,53,57,.08);border:1px solid rgba(12,53,57,.3);color:var(--deep-2)}
  .lt-unp{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}
  .lt-cmp{background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.4);color:var(--green)}
  .pt{display:inline-flex;padding:4px 10px;border-radius:9px;font-size:10.5px;font-weight:800}
  .pt-paid{background:rgba(22,163,74,.1);color:var(--green)}
  .pt-deduct{background:rgba(185,28,28,.08);color:var(--red-2)}
  .pt-config{background:rgba(180,83,9,.1);color:var(--amber-2)}
  .dl{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid var(--hair);font-size:12.5px}
  .dl:last-child{border-bottom:none}
  .dl .l{color:var(--muted);font-weight:600}
  .dl .v{font-weight:700;color:var(--ink)}
  .g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
  @media (max-width:900px){.g3{grid-template-columns:1fr}}
  .f label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
  .f .in{width:100%;height:42px;border-radius:11px;border:1px solid var(--border);background:#fff;padding:0 13px;font-size:13.5px;color:var(--ink);font-family:inherit}
  .f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 13px center;padding-right:32px}
  .f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.13)}
  .cal{display:grid;grid-template-columns:repeat(7,1fr);gap:6px}
  .cal .dow{font-size:10px;font-weight:800;letter-spacing:.08em;color:var(--muted);text-transform:uppercase;text-align:center;padding:4px 0}
  .day{border:1px solid var(--border);border-radius:10px;min-height:64px;padding:6px;background:#fff}
  .day.wknd{background:var(--hair)}
  .day .n{font-size:11px;font-weight:700;color:var(--muted)}
  .dchip{display:inline-block;margin:4px 3px 0 0;font-size:9px;font-weight:800;border-radius:6px;padding:2px 6px;color:#fff}
  .c-ann{background:var(--sec)}.c-sick{background:var(--amber-2)}.c-cmp{background:var(--green)}.c-mat{background:var(--deep-2)}.c-unp{background:var(--red-2)}
  .legend{display:flex;gap:14px;flex-wrap:wrap;margin-top:12px;font-size:11px;color:var(--muted);font-weight:600}
  .legend i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:6px}
  .tiles{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  @media (max-width:1100px){.tiles{grid-template-columns:repeat(2,1fr)}}
  .tile{border:1px solid var(--border);border-radius:13px;background:rgba(255,255,255,.94);padding:14px;display:flex;gap:10px;align-items:center;text-decoration:none;color:var(--ink);font-size:12.5px;font-weight:700}
  .tile:hover{border-color:rgba(18,143,142,.45)}
  .tile .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px;flex:none}
</style>
</head>
<body>
<div class="wrap">

<!-- 1 · LEAVE DASHBOARD -->
<div><span class="opt-tag">1 · Leave Dashboard</span>
  <div class="page-head"><div><h1>Leave Dashboard</h1><div class="sub">Payroll &amp; HR → Leave Management · live position.</div></div>
    <div style="display:flex;gap:10px"><button class="btn btn-ghost">Leave Calendar</button><button class="btn btn-cta">＋ New Application</button></div></div>
  <div class="kpis">
    <div class="kpi hero"><div class="l">On Leave Today</div><div class="v">3</div><div class="n">of 45 employees</div></div>
    <div class="kpi"><div class="l">Pending Approvals</div><div class="v">5</div><div class="n" style="color:var(--amber-2)">2 awaiting HR</div></div>
    <div class="kpi"><div class="l">Accrued Liability</div><div class="v">K412,000</div><div class="n">unused annual leave</div></div>
    <div class="kpi"><div class="l">Encashments · Aug</div><div class="v">K96,000</div><div class="n">4 employees</div></div>
  </div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🏖</span><h2>On Leave Today</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Employee</th><th>Department</th><th>Type</th><th>Return Date</th><th>Status</th></tr></thead><tbody>
        <tr><td class="name">Grace Phiri</td><td class="em">Finance</td><td><span class="tchip lt-ann">Annual</span></td><td class="em">24 Aug 2026</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td></tr>
        <tr><td class="name">Moses Banda</td><td class="em">Warehouse</td><td><span class="tchip lt-sick">Sick</span></td><td class="em">21 Aug 2026</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td></tr>
        <tr><td class="name">Ruth Mwale</td><td class="em">Sales</td><td><span class="tchip lt-mat">Maternity</span></td><td class="em">12 Nov 2026</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td></tr>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><span class="ic">⏳</span><h2>Awaiting Your Approval</h2></div>
      <div class="pad">
        <div class="dl"><span class="l">Peter Phiri · Annual · 3d</span><span class="v"><button class="btn btn-ok btn-sm">✓</button> <button class="btn btn-no btn-sm">✕</button></span></div>
        <div class="dl"><span class="l">Anna Kachale · Study · 10d</span><span class="v"><button class="btn btn-ok btn-sm">✓</button> <button class="btn btn-no btn-sm">✕</button></span></div>
        <div class="dl"><span class="l">John Tembo · Unpaid · 5d</span><span class="v"><button class="btn btn-ok btn-sm">✓</button> <button class="btn btn-no btn-sm">✕</button></span></div>
      </div></div>
  </div>
</div>

<!-- 2 · LEAVE TYPES -->
<div><span class="opt-tag">2 · Leave Types (configurable payroll treatment)</span>
  <div class="page-head"><div><h1>Leave Types</h1><div class="sub">Rules, accrual and payroll treatment per type.</div></div>
    <button class="btn btn-cta">＋ New Leave Type</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Code</th><th>Name</th><th class="num">Annual Entitlement</th><th class="num">Accrual / mo</th><th>Payroll Treatment</th><th>Carry-Forward</th><th>Status</th><th></th></tr></thead><tbody>
    <tr><td class="mono">ANN</td><td class="name">Annual Leave</td><td class="num">30</td><td class="num">2.5</td><td><span class="pt pt-paid">Paid</span></td><td class="em">15 max</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">SCK</td><td class="name">Sick Leave</td><td class="num">12</td><td class="num">1.0</td><td><span class="pt pt-config">Configurable</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">MAT</td><td class="name">Maternity Leave</td><td class="num">90</td><td class="num">—</td><td><span class="pt pt-config">Configurable</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">PAT</td><td class="name">Paternity Leave</td><td class="num">10</td><td class="num">—</td><td><span class="pt pt-paid">Paid</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">CMP</td><td class="name">Compassionate</td><td class="num">5</td><td class="num">—</td><td><span class="pt pt-paid">Paid</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">STD</td><td class="name">Study Leave</td><td class="num">20</td><td class="num">—</td><td><span class="pt pt-config">Configurable</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">UNP</td><td class="name">Unpaid Leave</td><td class="num">0</td><td class="num">—</td><td><span class="pt pt-deduct">Deduct</span></td><td class="em">No</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
  </tbody></table></div></div>
</div>

<!-- 3 · LEAVE BALANCES -->
<div><span class="opt-tag">3 · Leave Balances</span>
  <div class="page-head"><div><h1>Leave Balances</h1><div class="sub">Elvis Seyama · EMP-0001 · Finance.</div></div>
    <select class="f in" style="height:42px;border-radius:11px"><option>Elvis Seyama</option><option>Grace Phiri</option><option>Moses Banda</option></select></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Leave Type</th><th class="num">Entitlement</th><th class="num">Taken</th><th class="num">Pending</th><th class="num">Adjustments</th><th class="num">Balance</th></tr></thead><tbody>
    <tr><td><span class="tchip lt-ann">Annual</span></td><td class="num">30</td><td class="num">10</td><td class="num">2</td><td class="num">0</td><td class="num">18</td></tr>
    <tr><td><span class="tchip lt-sick">Sick</span></td><td class="num">12</td><td class="num">3</td><td class="num">0</td><td class="num">0</td><td class="num">9</td></tr>
    <tr><td><span class="tchip lt-cmp">Compassionate</span></td><td class="num">5</td><td class="num">1</td><td class="num">0</td><td class="num">0</td><td class="num">4</td></tr>
  </tbody></table></div></div>
</div>

<!-- 4 · LEAVE APPLICATIONS -->
<div><span class="opt-tag">4 · Leave Applications</span>
  <div class="page-head"><div><h1>Leave Applications</h1><div class="sub">Draft → Submitted → Supervisor → HR → Approved.</div></div>
    <button class="btn btn-cta">＋ New Application</button></div>
  <div class="card mb"><div class="li-wrap"><table><thead><tr><th>Ref</th><th>Employee</th><th>Type</th><th>Start</th><th>Return</th><th class="num">Working Days</th><th>Status</th><th></th></tr></thead><tbody>
    <tr><td class="mono">LV-0231</td><td class="name">Peter Phiri</td><td><span class="tchip lt-ann">Annual</span></td><td class="em">24 Aug 2026</td><td class="em">26 Aug 2026</td><td class="num">3</td><td><span class="badge b-pend"><span class="bdot"></span>Supervisor</span></td><td class="row-act"><button class="ibtn">👁</button></td></tr>
    <tr><td class="mono">LV-0230</td><td class="name">Anna Kachale</td><td><span class="tchip lt-ann">Study</span></td><td class="em">01 Sep 2026</td><td class="em">12 Sep 2026</td><td class="num">10</td><td><span class="badge b-post"><span class="bdot"></span>HR</span></td><td class="row-act"><button class="ibtn">👁</button></td></tr>
    <tr><td class="mono">LV-0229</td><td class="name">Grace Phiri</td><td><span class="tchip lt-ann">Annual</span></td><td class="em">10 Aug 2026</td><td class="em">24 Aug 2026</td><td class="num">10</td><td><span class="badge b-ok"><span class="bdot"></span>Approved</span></td><td class="row-act"><button class="ibtn">👁</button></td></tr>
    <tr><td class="mono">LV-0228</td><td class="name">John Tembo</td><td><span class="tchip lt-unp">Unpaid</span></td><td class="em">17 Aug 2026</td><td class="em">21 Aug 2026</td><td class="num">5</td><td><span class="badge b-rev"><span class="bdot"></span>Rejected</span></td><td class="row-act"><button class="ibtn">👁</button></td></tr>
  </tbody></table></div></div>
  <div class="card"><div class="card-h"><span class="ic">📝</span><h2>New Application</h2></div>
    <div class="pad"><div class="g3">
      <div class="f"><label>Employee</label><select class="in"><option>Elvis Seyama</option><option>Peter Phiri</option></select></div>
      <div class="f"><label>Leave Type</label><select class="in"><option>Annual</option><option>Sick</option><option>Unpaid</option></select></div>
      <div class="f"><label>Working Days</label><input class="in" value="3" disabled style="background:var(--hair);color:var(--muted)"></div>
      <div class="f"><label>Start Date</label><input class="in" type="date" value="2026-08-24"></div>
      <div class="f"><label>Return Date</label><input class="in" type="date" value="2026-08-26"></div>
      <div class="f"><label>Balance After</label><input class="in" value="15" disabled style="background:var(--hair);color:var(--muted)"></div>
      <div class="f" style="grid-column:1/-1"><label>Reason</label><input class="in" placeholder="Optional note"></div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px"><button class="btn btn-ghost">Save Draft</button><button class="btn btn-sec">Submit for Approval</button></div>
    </div></div>
</div>

<!-- 5 · LEAVE APPROVALS -->
<div><span class="opt-tag">5 · Leave Approvals</span>
  <div class="page-head"><div><h1>Leave Approvals</h1><div class="sub">Two-stage: Supervisor → HR, with segregation.</div></div></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Ref</th><th>Employee</th><th>Type · Days</th><th>Stage</th><th>Coverage</th><th>Actions</th></tr></thead><tbody>
    <tr><td class="mono">LV-0231</td><td class="name">Peter Phiri</td><td><span class="tchip lt-ann">Annual</span> · 3</td><td><span class="badge b-pend"><span class="bdot"></span>Supervisor</span></td><td class="em">Warehouse OK (1/4 away)</td><td class="row-act"><button class="btn btn-ok btn-sm">Approve</button><button class="btn btn-no btn-sm">Reject</button></td></tr>
    <tr><td class="mono">LV-0230</td><td class="name">Anna Kachale</td><td><span class="tchip lt-ann">Study</span> · 10</td><td><span class="badge b-post"><span class="bdot"></span>HR</span></td><td class="em">Finance OK</td><td class="row-act"><button class="btn btn-ok btn-sm">Approve</button><button class="btn btn-no btn-sm">Reject</button></td></tr>
  </tbody></table></div></div>
</div>

<!-- 6 · LEAVE CALENDAR -->
<div><span class="opt-tag">6 · Leave Calendar · Aug 2026</span>
  <div class="page-head"><div><h1>Leave Calendar</h1><div class="sub">Who is / will be on leave, by department and type.</div></div>
    <div style="display:flex;gap:8px"><button class="btn btn-ghost btn-sm">‹ Jul</button><button class="btn btn-ghost btn-sm">Aug</button><button class="btn btn-ghost btn-sm">Sep ›</button></div></div>
  <div class="card"><div class="pad">
    <div class="cal">
      <span class="dow">Mon</span><span class="dow">Tue</span><span class="dow">Wed</span><span class="dow">Thu</span><span class="dow">Fri</span><span class="dow">Sat</span><span class="dow">Sun</span>
      <div class="day wknd"><span class="n">1</span></div><div class="day wknd"><span class="n">2</span></div>
      <div class="day"><span class="n">3</span><span class="dchip c-ann">GP</span></div><div class="day"><span class="n">4</span><span class="dchip c-ann">GP</span></div><div class="day"><span class="n">5</span><span class="dchip c-ann">GP</span></div><div class="day wknd"><span class="n">6</span></div><div class="day wknd"><span class="n">7</span></div>
      <div class="day"><span class="n">10</span><span class="dchip c-sick">MB</span></div><div class="day"><span class="n">11</span><span class="dchip c-sick">MB</span></div><div class="day"><span class="n">12</span><span class="dchip c-sick">MB</span></div><div class="day"><span class="n">13</span></div><div class="day"><span class="n">14</span></div><div class="day wknd"><span class="n">15</span></div><div class="day wknd"><span class="n">16</span></div>
      <div class="day"><span class="n">17</span><span class="dchip c-mat">RM</span></div><div class="day"><span class="n">18</span><span class="dchip c-mat">RM</span></div><div class="day"><span class="n">19</span><span class="dchip c-mat">RM</span></div><div class="day"><span class="n">20</span><span class="dchip c-mat">RM</span></div><div class="day"><span class="n">21</span><span class="dchip c-mat">RM</span></div><div class="day wknd"><span class="n">22</span></div><div class="day wknd"><span class="n">23</span></div>
      <div class="day"><span class="n">24</span><span class="dchip c-ann">GP</span><span class="dchip c-ann">PP</span></div><div class="day"><span class="n">25</span><span class="dchip c-ann">PP</span></div><div class="day"><span class="n">26</span><span class="dchip c-ann">PP</span></div><div class="day"><span class="n">27</span></div><div class="day"><span class="n">28</span></div><div class="day wknd"><span class="n">29</span></div><div class="day wknd"><span class="n">30</span></div>
      <div class="day"><span class="n">31</span></div>
    </div>
    <div class="legend"><span><i class="c-ann"></i>Annual</span><span><i class="c-sick"></i>Sick</span><span><i class="c-mat"></i>Maternity</span><span><i class="c-cmp"></i>Compassionate</span><span><i class="c-unp"></i>Unpaid</span><span><i style="background:var(--hair);border:1px solid var(--border)"></i>Weekend</span></div>
  </div></div>
</div>

<!-- 7 · ADJUSTMENTS & ENCASHMENT -->
<div><span class="opt-tag">7 · Leave Adjustments &amp; Encashment</span>
  <div class="page-head"><div><h1>Adjustments &amp; Encashment</h1><div class="sub">Corrections and cash-out of unused leave → payroll.</div></div>
    <button class="btn btn-cta">＋ New Encashment</button></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🛠</span><h2>Leave Adjustments</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th class="num">± Days</th><th>Reason</th><th>Approved By</th></tr></thead><tbody>
        <tr><td class="name">Moses Banda</td><td><span class="tchip lt-sick">Sick</span></td><td class="num">+1</td><td class="em">Medical certificate</td><td class="em">HR Manager</td></tr>
        <tr><td class="name">Grace Phiri</td><td><span class="tchip lt-ann">Annual</span></td><td class="num">−2</td><td class="em">Carry-forward expiry</td><td class="em">System</td></tr>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><span class="ic">💵</span><h2>Leave Encashment</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Employee</th><th class="num">Days</th><th class="num">Rate</th><th class="num">Amount</th><th>Status</th></tr></thead><tbody>
        <tr><td class="name">Elvis Seyama</td><td class="num">5</td><td class="num">K16,000</td><td class="num">80,000</td><td><span class="badge b-post"><span class="bdot"></span>In Payroll</span></td></tr>
        <tr><td class="name">Ruth Mwale</td><td class="num">1</td><td class="num">K16,000</td><td class="num">16,000</td><td><span class="badge b-pend"><span class="bdot"></span>Pending</span></td></tr>
      </tbody></table></div></div>
  </div>
</div>

<!-- 8 · LEAVE REPORTS -->
<div><span class="opt-tag">8 · Leave Reports</span>
  <div class="page-head"><div><h1>Leave Reports</h1><div class="sub">Management + statutory leave reporting.</div></div></div>
  <div class="tiles">
    <a class="tile" href="#"><span class="ic">⚖</span>Leave Balance Report</a>
    <a class="tile" href="#"><span class="ic">📅</span>Leave Taken Report</a>
    <a class="tile" href="#"><span class="ic">⏳</span>Pending Approvals</a>
    <a class="tile" href="#"><span class="ic">💵</span>Encashment Report</a>
    <a class="tile" href="#"><span class="ic">💰</span>Leave Liability (Accrued)</a>
    <a class="tile" href="#"><span class="ic">🏢</span>Department Coverage</a>
    <a class="tile" href="#"><span class="ic">📉</span>Absence Analysis</a>
    <a class="tile" href="#"><span class="ic">🔐</span>Leave Audit Trail</a>
  </div>
</div>

</div>
</body>
</html>
```

==================== APPENDIX B — CORE WORKFLOW EXTENSIONS (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Management — core workflow extensions</title>
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
  .btn[disabled]{opacity:.45;cursor:not-allowed}
  .btn-ghost{background:#e8f0f0;border-color:var(--border);color:var(--ink)}
  .btn-sec{color:#fff;background:var(--sec);box-shadow:0 8px 18px -8px rgba(18,143,142,.5)}
  .btn-cta{color:#fff;background:var(--deep-2);font-weight:700;box-shadow:0 10px 22px -10px rgba(8,40,44,.55)}
  .btn-ok{color:#fff;background:var(--green)}
  .btn-no{color:var(--red-2);background:#fff;border-color:rgba(185,28,28,.3)}
  .btn-sm{height:34px;padding:0 13px;font-size:12px;border-radius:10px}
  .card{background:rgba(255,255,255,.92);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow-card);overflow:hidden}
  .card-h{display:flex;align-items:center;gap:10px;padding:15px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .card-h .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px}
  .card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
  .card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .pad{padding:20px 24px}
  .mb{margin-bottom:16px}
  .grid2{display:grid;grid-template-columns:1.5fr 1fr;gap:16px;align-items:start}
  @media (max-width:1100px){.grid2{grid-template-columns:1fr}}
  .li-wrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:820px}
  thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
  th.num,td.num{text-align:right}
  tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle;color:var(--sub)}
  td.num{font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
  tbody tr:hover td{background:rgba(17,69,75,.04)}
  tbody tr:last-child td{border-bottom:none}
  .mono{font-family:ui-monospace,Menlo,monospace;font-size:12px}
  .name{font-weight:600;color:var(--ink)}
  .em{color:var(--muted)}
  .neg{color:var(--red-2)}
  .pos{color:var(--green)}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
  .badge .bdot{width:6px;height:6px;border-radius:50%}
  .b-ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.4);color:var(--green)}.b-ok .bdot{background:#22c55e}
  .b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:#d97706}
  .b-rev{background:rgba(185,28,28,.08);border:1px solid rgba(185,28,28,.3);color:var(--red-2)}.b-rev .bdot{background:var(--red-2)}
  .b-post{background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.4);color:var(--sec)}.b-post .bdot{background:var(--sec)}
  .b-off{background:rgba(138,165,167,.15);border:1px solid rgba(138,165,167,.5);color:var(--muted)}.b-off .bdot{background:var(--muted)}
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .lt-ann{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .lt-sick{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
  .g3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px}
  @media (max-width:900px){.g3{grid-template-columns:1fr}}
  .f label{display:block;font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
  .f .in{width:100%;height:42px;border-radius:11px;border:1px solid var(--border);background:#fff;padding:0 13px;font-size:13.5px;color:var(--ink);font-family:inherit}
  .f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 13px center;padding-right:32px}
  .f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.13)}
  .dl{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--hair);font-size:12.5px}
  .dl:last-child{border-bottom:none}
  .dl .l{color:var(--muted);font-weight:600}
  .dl .v{font-weight:700;color:var(--ink)}
  .alert{border-radius:12px;padding:11px 14px;font-size:12px;font-weight:700;margin-top:12px}
  .alert.red{border:1px solid rgba(185,28,28,.4);background:rgba(185,28,28,.07);color:var(--red-2)}
  .alert.amber{border:1px solid rgba(180,83,9,.4);background:rgba(180,83,9,.07);color:var(--amber-2)}
  .alert.teal{border:1px dashed rgba(18,143,142,.5);background:rgba(18,143,142,.06);color:var(--sec)}
  .sw{width:44px;height:25px;border-radius:999px;background:#CBD8D6;position:relative;transition:.2s;flex:none;cursor:pointer}
  .sw.on{background:var(--sec)}
  .sw::after{content:"";position:absolute;top:3px;left:3px;width:19px;height:19px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .sw.on::after{left:22px}
  .swrow{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid var(--hair)}
  .swrow:last-child{border-bottom:none}
  .swrow .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .swrow .s{font-size:11px;color:var(--muted);margin-top:2px}
  .tl{position:relative;padding-left:22px}
  .tl::before{content:"";position:absolute;left:7px;top:4px;bottom:4px;width:2px;background:var(--line)}
  .tl .st{position:relative;padding:6px 0}
  .tl .st::before{content:"";position:absolute;left:-19px;top:11px;width:10px;height:10px;border-radius:50%;background:var(--sec);border:2px solid #fff}
  .tl .st.pend::before{background:var(--amber-2)}
  .tl .st .t{font-size:12px;font-weight:700;color:var(--ink)}
  .tl .st .s{font-size:11px;color:var(--muted)}
  .file{display:flex;align-items:center;gap:8px;border:1px dashed var(--border);border-radius:10px;padding:9px 12px;font-size:12px;color:var(--muted);margin-top:8px}
</style>
</head>
<body>
<div class="wrap">

<!-- A · HR SETTINGS · APPROVAL RULES -->
<div><span class="opt-tag">A · HR Settings · Leave Approval Rules</span>
  <div class="page-head"><div><h1>Leave Approval Rules</h1><div class="sub">Configuration-driven routing — 1, 2 or 3 levels, never hard-coded.</div></div>
    <button class="btn btn-cta">＋ New Rule</button></div>
  <div class="card mb"><div class="li-wrap"><table><thead><tr><th>#</th><th>Approver 1</th><th>Approver 2</th><th>Applies To</th><th>Priority</th><th>Status</th><th></th></tr></thead><tbody>
    <tr><td class="mono">1</td><td class="name">Branch Manager</td><td class="name">HR Manager</td><td class="em">Branch = Limbe</td><td class="num">10</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">2</td><td class="name">Department Manager</td><td class="name">HR Manager</td><td class="em">Department = Finance</td><td class="num">20</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
    <tr><td class="mono">3</td><td class="name">Line Supervisor</td><td class="name">HR Manager</td><td class="em">All employees (default)</td><td class="num">99</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="ibtn">✎</button></td></tr>
  </tbody></table></div></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🧭</span><h2>Approval Levels per Leave Type</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Leave Type</th><th>Levels</th><th>Same-person guard</th></tr></thead><tbody>
        <tr><td><span class="tchip lt-ann">Annual</span></td><td><select class="f in" style="height:36px;border-radius:10px"><option>2</option><option>1</option><option>3</option></select></td><td class="em">Enforced — one person can't approve twice</td></tr>
        <tr><td><span class="tchip lt-sick">Sick</span></td><td><select class="f in" style="height:36px;border-radius:10px"><option>1</option><option>2</option></select></td><td class="em">Enforced</td></tr>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><span class="ic">⏰</span><h2>Reminders &amp; Escalation</h2></div>
      <div class="pad">
        <div class="swrow"><div><div class="t">Approver reminder</div><div class="s">"3 applications awaiting your review"</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Leave-starts-tomorrow reminder</div><div class="s">to employee</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Return-to-work reminder</div><div class="s">to employee</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Escalate to HR after</div><div class="s">3 days without Level-1 action</div></div><span class="sw on"></span></div>
      </div></div>
  </div>
</div>

<!-- B · APPLY (balance-gated, eligibility, conflicts) -->
<div><span class="opt-tag">B · Apply for Leave (core validations)</span>
  <div class="page-head"><div><h1>Apply for Leave</h1><div class="sub">Validated client- and server-side before submission.</div></div></div>
  <div class="card"><div class="pad"><div class="g3">
    <div class="f"><label>Employee</label><select class="in"><option>Elvis Seyama · EMP-0001 · Male</option></select></div>
    <div class="f"><label>Leave Type</label><select class="in"><option>Annual</option><option>Sick</option><option>Unpaid</option></select><div class="em" style="font-size:10.5px;margin-top:5px">Maternity/Paternity hidden — gender-ineligible.</div></div>
    <div class="f"><label>Working Days</label><input class="in" value="7" disabled style="background:var(--hair);color:var(--muted)"></div>
    <div class="f"><label>Start Date</label><input class="in" type="date" value="2026-08-24"></div>
    <div class="f"><label>Return Date</label><input class="in" type="date" value="2026-09-02"></div>
    <div class="f"><label>Available</label><input class="in" value="4" disabled style="background:var(--hair);color:var(--muted)"></div>
    <div class="f" style="grid-column:1/-1"><label>Supporting Document (required for Sick)</label><div class="file">📎 medical_certificate.pdf · upload</div></div>
  </div>
  <div class="alert red">Insufficient leave balance. You have only 4 days available — requested 7. Apply is disabled.</div>
  <div class="alert amber">Conflict: 2 employees in Finance are already scheduled on leave during this period (warning only).</div>
  <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px"><button class="btn btn-ghost">Save Draft</button><button class="btn btn-sec" disabled>Submit for Approval</button></div>
  </div></div>
</div>

<!-- C · REVIEW (mandatory open before decision) -->
<div><span class="opt-tag">C · Review Application LV-0231 (Level 1)</span>
  <div class="page-head"><div><h1 style="display:flex;gap:10px;align-items:center">Review LV-0231 <span class="badge b-pend"><span class="bdot"></span>Awaiting Level 1</span></h1><div class="sub">Decision buttons enabled only after opening this review.</div></div></div>
  <div class="grid2">
    <div>
      <div class="card mb"><div class="card-h"><span class="ic">👤</span><h2>Employee Information</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">Employee № / Name</span><span class="v">EMP-0014 · Peter Phiri</span></div>
          <div class="dl"><span class="l">Department / Position</span><span class="v">Warehouse · Supervisor</span></div>
          <div class="dl"><span class="l">Branch / Status</span><span class="v">Limbe · Permanent</span></div>
        </div></div>
      <div class="card mb"><div class="card-h"><span class="ic">🏖</span><h2>Leave Information</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">Type</span><span class="v"><span class="tchip lt-ann">Annual</span></span></div>
          <div class="dl"><span class="l">Start → Return</span><span class="v">24 Aug → 26 Aug 2026</span></div>
          <div class="dl"><span class="l">Working Days / Reason</span><span class="v">3 · Family commitment</span></div>
          <div class="file">📎 none required for Annual</div>
        </div></div>
      <div class="card"><div class="card-h"><span class="ic">🕑</span><h2>Previous Leave</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">12–16 May 2026 · Annual</span><span class="v">5d · Approved</span></div>
          <div class="dl"><span class="l">03 Mar 2026 · Sick</span><span class="v">1d · Approved</span></div>
        </div></div>
    </div>
    <div>
      <div class="card mb"><div class="card-h"><span class="ic">⚖</span><h2>Leave Balance</h2></div>
        <div class="pad">
          <div class="dl"><span class="l">Entitlement</span><span class="v">30</span></div>
          <div class="dl"><span class="l">Used</span><span class="v">12</span></div>
          <div class="dl"><span class="l">Pending</span><span class="v">0</span></div>
          <div class="dl"><span class="l">Available</span><span class="v">18</span></div>
          <div class="dl"><span class="l">Requested</span><span class="v">3</span></div>
          <div class="dl"><span class="l">Balance After Approval</span><span class="v pos">15</span></div>
        </div></div>
      <div class="card mb"><div class="card-h"><span class="ic">🧾</span><h2>Approval History</h2></div>
        <div class="pad"><div class="tl">
          <div class="st"><div class="t">Submitted</div><div class="s">Employee · 20 Aug</div></div>
          <div class="st pend"><div class="t">Level 1 — Pending</div><div class="s">Branch Manager (you)</div></div>
          <div class="st pend"><div class="t">Level 2 — Not started</div><div class="s">HR Manager</div></div>
        </div></div></div>
      <div class="card"><div class="pad">
        <div class="f"><label>Comment (mandatory on reject)</label><textarea class="f in" style="height:70px;border-radius:11px;border:1px solid var(--border);padding:10px 13px;font-family:inherit" placeholder="Add a review comment…"></textarea></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px"><button class="btn btn-no">Reject</button><button class="btn btn-ok">Approve → Level 2</button></div>
      </div></div>
    </div>
  </div>
</div>

<!-- D · NOTIFICATION CENTRE -->
<div><span class="opt-tag">D · Notification Centre + Channels</span>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🔔</span><h2>Notifications</h2><div class="right"><button class="btn btn-ghost btn-sm">Mark all read</button></div></div>
      <div class="pad">
        <div class="dl"><span class="l">🔔 Leave Approval Required — John Banda submitted Annual (5d)</span><span class="v em">5m</span></div>
        <div class="dl"><span class="l">🔔 Approved — your Annual application was approved (Level 2)</span><span class="v em">2h</span></div>
        <div class="dl"><span class="l">🔔 Rejected — your application was rejected by HR Manager</span><span class="v em">1d</span></div>
        <div class="dl"><span class="l">🔔 Reminder — your leave starts tomorrow</span><span class="v em">1d</span></div>
      </div></div>
    <div class="card"><div class="card-h"><span class="ic">📡</span><h2>Channels (HR Settings → Notifications)</h2></div>
      <div class="pad">
        <div class="swrow"><div><div class="t">In-app</div><div class="s">always on</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">Email</div><div class="s">approvers + applicants</div></div><span class="sw on"></span></div>
        <div class="swrow"><div><div class="t">SMS</div><div class="s">optional</div></div><span class="sw"></span></div>
        <div class="swrow"><div><div class="t">Push</div><div class="s">optional</div></div><span class="sw"></span></div>
      </div></div>
  </div>
</div>

<!-- E · LEAVE TRANSACTION LEDGER -->
<div><span class="opt-tag">E · Leave Transaction Ledger (auditable)</span>
  <div class="page-head"><div><h1>Leave Transactions</h1><div class="sub">Every balance change is a transaction — reversals, not edits.</div></div>
    <button class="btn btn-ghost">Export</button></div>
  <div class="card"><div class="li-wrap"><table><thead><tr><th>Date</th><th>Type</th><th>Ref</th><th class="num">Days</th><th class="num">Balance</th></tr></thead><tbody>
    <tr><td class="em">01 Jan 2026</td><td class="name">Opening Balance</td><td class="mono">SYS</td><td class="num pos">+30</td><td class="num">30</td></tr>
    <tr><td class="em">15 Mar 2026</td><td>Leave Taken · Annual</td><td class="mono">LV-0198</td><td class="num neg">−5</td><td class="num">25</td></tr>
    <tr><td class="em">20 Apr 2026</td><td>Leave Taken · Sick</td><td class="mono">LV-0210</td><td class="num neg">−3</td><td class="num">22</td></tr>
    <tr><td class="em">10 May 2026</td><td>Adjustment · carry-forward</td><td class="mono">ADJ-014</td><td class="num pos">+2</td><td class="num">24</td></tr>
    <tr><td class="em">20 Aug 2026</td><td>Leave Taken · Annual</td><td class="mono">LV-0229</td><td class="num neg">−4</td><td class="num">20</td></tr>
    <tr><td class="em">25 Aug 2026</td><td>Cancellation Reversal</td><td class="mono">LV-0229-C</td><td class="num pos">+2</td><td class="num">22</td></tr>
  </tbody></table></div>
    <div class="alert teal" style="margin:0;border-radius:0">Net consumed 8 days · cancellations post reversals (never delete); completed leave requires HR adjustment.</div></div>
</div>

</div>
</body>
</html>
```

==================== APPENDIX C — REPORTS + ON-LEAVE NOTIFICATIONS (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leave Management — Reports + On-Leave Notifications</title>
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
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:12px;font-weight:600;font-size:13px;border:1px solid transparent;cursor:pointer;font-family:inherit;white-space:nowrap}
  .btn-ghost{background:#e8f0f0;border-color:var(--border);color:var(--ink)}
  .btn-sec{color:#fff;background:var(--sec);box-shadow:0 8px 18px -8px rgba(18,143,142,.5)}
  .btn-sm{height:34px;padding:0 13px;font-size:12px;border-radius:10px}
  .card{background:rgba(255,255,255,.92);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow-card);overflow:hidden}
  .card-h{display:flex;align-items:center;gap:10px;padding:15px 20px;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .card-h .ic{width:34px;height:34px;border-radius:10px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:15px}
  .card-h h2{font-size:14px;font-weight:800;color:var(--ink)}
  .card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .pad{padding:20px 24px}
  .mb{margin-bottom:16px}
  .grid2{display:grid;grid-template-columns:1.5fr 1fr;gap:16px;align-items:start}
  @media (max-width:1100px){.grid2{grid-template-columns:1fr}}
  .li-wrap{overflow-x:auto}
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:760px}
  thead th{background:linear-gradient(180deg,#f4f8f8,#e8f0f0);color:#111827;text-align:left;font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:11px 12px;box-shadow:inset 0 1px 0 rgba(255,255,255,.9),inset 0 -1px 0 rgba(71,95,97,.45)}
  th.num,td.num{text-align:right}
  tbody td{padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle;color:var(--sub)}
  td.num{font-variant-numeric:tabular-nums;font-weight:600;color:var(--ink)}
  tbody tr:hover td{background:rgba(17,69,75,.04)}
  tbody tr:last-child td{border-bottom:none}
  .name{font-weight:600;color:var(--ink)}
  .em{color:var(--muted)}
  .badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;font-size:10px;font-weight:800}
  .badge .bdot{width:6px;height:6px;border-radius:50%}
  .b-ok{background:rgba(22,163,74,.10);border:1px solid rgba(22,163,74,.4);color:var(--green)}.b-ok .bdot{background:#22c55e}
  .b-pend{background:rgba(217,119,6,.10);border:1px solid rgba(217,119,6,.35);color:var(--amber-2)}.b-pend .bdot{background:#d97706}
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .lt-ann{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .lt-sick{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
  .lt-mat{background:rgba(12,53,57,.08);border:1px solid rgba(12,53,57,.3);color:var(--deep-2)}
  .tiles{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
  @media (max-width:1100px){.tiles{grid-template-columns:repeat(2,1fr)}}
  @media (max-width:640px){.tiles{grid-template-columns:1fr}}
  .tile{border:1px solid var(--border);border-radius:13px;background:rgba(255,255,255,.94);padding:14px;display:flex;gap:10px;align-items:center;text-decoration:none;color:var(--ink);font-size:12px;font-weight:700}
  .tile:hover{border-color:rgba(18,143,142,.45)}
  .tile .ic{width:32px;height:32px;border-radius:9px;background:rgba(18,143,142,.1);display:grid;place-items:center;font-size:14px;flex:none}
  .ubar{height:6px;border-radius:4px;background:var(--line);overflow:hidden;margin-top:6px}
  .ubar i{display:block;height:100%;border-radius:4px;background:var(--sec)}
  .bell{position:relative;width:42px;height:42px;border-radius:12px;border:1px solid var(--border);background:#fff;display:grid;place-items:center;font-size:16px;cursor:pointer}
  .bell .n{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;border-radius:999px;background:var(--red-2);color:#fff;font-size:10px;font-weight:800;display:grid;place-items:center;padding:0 5px}
  .feed .item{display:flex;gap:12px;padding:12px 20px;border-bottom:1px solid var(--hair);align-items:flex-start}
  .feed .item:last-child{border-bottom:none}
  .feed .av{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-size:12px;font-weight:800;color:#fff;flex:none}
  .av.ann{background:var(--sec)}.av.sick{background:var(--amber-2)}.av.mat{background:var(--deep-2)}
  .feed .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .feed .s{font-size:11.5px;color:var(--muted);margin-top:2px}
  .feed .when{margin-left:auto;font-size:10.5px;color:var(--faint);font-weight:700;white-space:nowrap}
  .sw{width:44px;height:25px;border-radius:999px;background:#CBD8D6;position:relative;transition:.2s;flex:none;cursor:pointer}
  .sw.on{background:var(--sec)}
  .sw::after{content:"";position:absolute;top:3px;left:3px;width:19px;height:19px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .sw.on::after{left:22px}
  .swrow{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid var(--hair)}
  .swrow:last-child{border-bottom:none}
  .swrow .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .swrow .s{font-size:11px;color:var(--muted);margin-top:2px}
</style>
</head>
<body>
<div class="wrap">

<!-- F · LEAVE REPORTS -->
<div><span class="opt-tag">F · Leave Reports</span>
  <div class="page-head"><div><h1>Leave Reports</h1><div class="sub">Management, statutory and liability reporting.</div></div>
    <button class="btn btn-ghost">Export All</button></div>
  <div class="tiles mb">
    <a class="tile" href="#"><span class="ic">⚖</span>Leave Balance</a>
    <a class="tile" href="#"><span class="ic">📅</span>Leave Taken</a>
    <a class="tile" href="#"><span class="ic">📊</span>Utilisation</a>
    <a class="tile" href="#"><span class="ic">⏳</span>Pending Leave</a>
    <a class="tile" href="#"><span class="ic">🏢</span>By Department</a>
    <a class="tile" href="#"><span class="ic">💰</span>Leave Liability</a>
    <a class="tile" href="#"><span class="ic">⌛</span>Expiring Leave</a>
    <a class="tile" href="#"><span class="ic">💵</span>Encashment</a>
    <a class="tile" href="#"><span class="ic">📉</span>Absence Analysis</a>
    <a class="tile" href="#"><span class="ic">🔐</span>Leave Audit</a>
  </div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">📊</span><h2>Leave Utilisation by Department · 2026</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Department</th><th class="num">Entitlement</th><th class="num">Taken</th><th style="width:30%">Utilisation</th></tr></thead><tbody>
        <tr><td class="name">Finance</td><td class="num">240</td><td class="num">96</td><td><div class="ubar"><i style="width:40%"></i></div><span class="em" style="font-size:10.5px">40%</span></td></tr>
        <tr><td class="name">Warehouse</td><td class="num">360</td><td class="num">216</td><td><div class="ubar"><i style="width:60%"></i></div><span class="em" style="font-size:10.5px">60%</span></td></tr>
        <tr><td class="name">Sales</td><td class="num">300</td><td class="num">135</td><td><div class="ubar"><i style="width:45%"></i></div><span class="em" style="font-size:10.5px">45%</span></td></tr>
      </tbody></table></div></div>
    <div class="card"><div class="card-h"><span class="ic">⚖</span><h2>Leave Balance Report (excerpt)</h2></div>
      <div class="li-wrap"><table><thead><tr><th>Employee</th><th>Type</th><th class="num">Balance</th></tr></thead><tbody>
        <tr><td class="name">Elvis Seyama</td><td><span class="tchip lt-ann">Annual</span></td><td class="num">18</td></tr>
        <tr><td class="name">Grace Phiri</td><td><span class="tchip lt-ann">Annual</span></td><td class="num">8</td></tr>
        <tr><td class="name">Moses Banda</td><td><span class="tchip lt-sick">Sick</span></td><td class="num">9</td></tr>
        <tr><td class="name">Ruth Mwale</td><td><span class="tchip lt-mat">Maternity</span></td><td class="num">62</td></tr>
      </tbody></table></div></div>
  </div>
</div>

<!-- G · IN-APP ON-LEAVE NOTIFICATIONS -->
<div><span class="opt-tag">G · In-App Notifications — who is on leave &amp; return dates</span>
  <div class="page-head"><div><h1>Notifications</h1><div class="sub">Team visibility of who is away and when they return.</div></div>
    <div class="bell">🔔<span class="n">4</span></div></div>
  <div class="grid2">
    <div class="card"><div class="card-h"><span class="ic">🔔</span><h2>On-Leave Feed</h2><div class="right"><button class="btn btn-ghost btn-sm">Mark all read</button></div></div>
      <div class="feed">
        <div class="item"><span class="av ann">GP</span><div><div class="t">Grace Phiri is on Annual Leave</div><div class="s">Finance · returns <b>24 Aug 2026</b></div></div><span class="when">today</span></div>
        <div class="item"><span class="av sick">MB</span><div><div class="t">Moses Banda is on Sick Leave</div><div class="s">Warehouse · returns <b>21 Aug 2026</b></div></div><span class="when">today</span></div>
        <div class="item"><span class="av mat">RM</span><div><div class="t">Ruth Mwale is on Maternity Leave</div><div class="s">Sales · returns <b>12 Nov 2026</b></div></div><span class="when">since 17 Aug</span></div>
        <div class="item"><span class="av ann">PP</span><div><div class="t">Upcoming: Peter Phiri · Annual</div><div class="s">starts <b>24 Aug</b> · returns <b>26 Aug 2026</b></div></div><span class="when">scheduled</span></div>
      </div></div>
    <div>
      <div class="card mb"><div class="card-h"><span class="ic">📣</span><h2>On-Leave Announcements</h2></div>
        <div class="pad">
          <div class="swrow"><div><div class="t">Notify on leave start</div><div class="s">"X is on leave — returns Y"</div></div><span class="sw on"></span></div>
          <div class="swrow"><div><div class="t">Notify on return</div><div class="s">"X returns today"</div></div><span class="sw on"></span></div>
          <div class="swrow"><div><div class="t">Audience</div><div class="s">All employees / managers only</div></div><span class="sw on"></span></div>
        </div></div>
      <div class="card"><div class="card-h"><span class="ic">🗓</span><h2>Who's Away This Week</h2></div>
        <div class="pad">
          <div class="swrow" style="border:none;padding:4px 0"><div><div class="t">Mon–Fri · 3 away</div><div class="s">Finance 1 · Warehouse 1 · Sales 1</div></div><button class="btn btn-sec btn-sm">View calendar</button></div>
        </div></div>
    </div>
  </div>
</div>

</div>
</body>
</html>
```