AUTHORIZATION MODULE — PHASE B · APPROVAL WORKFLOW ENGINE + LIMITS — IMPLEMENTATION PROMPT
(SELF-CONTAINED — applicable mockup screens embedded in APPENDIX B.) Depends on Phase A.
SCOPE (Phase B ONLY): multi-level approval workflow state machine + approval limits/
conditions + pending-approvals queue. Reuses Phase A registry/can()/middleware.
SYSTEM CONSTRAINTS: currency from system setting; live-search overlay z-index ≥ 9999.
HARD GUARD — modules register workflows via config; the engine flips model status through
an Approvable trait listener; no module hard-codes approval logic.

==================== 0 · DISCOVERY ====================
0.1 Confirm Phase A tables/engine present; locate trackable models (PO, journal, payment,
expense, payroll, master-data) to attach Approvable trait.
0.2 List features needing workflows + amount-based routing (drives seed configs).

==================== 1 · SCHEMA (migrations) ====================
approval_workflows(id, code UQ, name, feature_id FK, active, settings JSON
  {same_person_guard,skip_under_limit,escalation_days,reject_comment_required})
approval_levels(id, workflow_id FK, level INT, approver_kind ENUM[ROLE,USER,POSITION],
  approver_value, limit_from NULL, limit_to NULL, conditions JSON, UQ(workflow,level))
approval_limits(id, approver_kind, approver_value, min_amount, max_amount NULL,
  conditions JSON, active)
approval_requests(id, trackable_type, trackable_id, workflow_id FK, amount DEC NULL,
  current_level INT, status ENUM[PENDING,IN_REVIEW,APPROVED,REJECTED,RETURNED,CANCELLED],
  initiated_by, initiated_at) + idx(trackable_type,trackable_id)
approval_steps(id, request_id FK, level, approver_kind, approver_value,
  status ENUM[PENDING,APPROVED,REJECTED,RETURNED,SKIPPED], acted_by, acted_at, comment)

==================== 2 · WORKFLOW ENGINE (app/Services/Approvals) ====================
ApprovalEngine::start($trackable, ?amount): pick workflow by feature; create request +
steps; if settings.skip_under_limit, mark levels whose band is above the amount SKIPPED.
approve($request,$user,$comment): actor must resolve to current level's approver
(role/user/position); same_person_guard (user must not have acted an earlier non-skipped
level); advance current_level; final → status APPROVED + event ApprovalApproved
{trackable} → Approvable listener flips model status.
reject($request,$user,$comment): reject_comment_required enforced; status REJECTED.
return($request,$user,$comment): RETURNED; resubmit resumes at level 1.
LimitResolver::requiredApprover(amount, context) from approval_limits (designer + routing
hints; conditions reuse Phase A AbacEvaluator).
Escalation: scheduler `authz:escalate` notifies next level after escalation_days.

==================== 3 · MIDDLEWARE ====================
`authz.approver:{feature}` on approve/reject/return routes: actor must be the currently
resolved approver on the request else 403. (Phase A `authz:` middleware still applies.)

==================== 4 · SCREENS (match APPENDIX B) ====================
/authz/workflows  list + designer: vertical level timeline (Created→Submitted→levels→
Approved), add/remove levels, approver kind/value, limit bands, conditions; settings
toggles (same-person guard, skip-under-limit, escalation days, reject comment).
/authz/limits     amount band table CRUD (From/To/Approver/Conditions/Status).
/authz/pending    queue of steps resolving to me (incl. delegations later): request,
trackable, amount, level, actions Approve/Reject/Return with comment.

==================== 5 · A11Y / CONSTRAINTS ====================
Tables scroll; text 90–125; no overflow; pixel parity with APPENDIX B; system currency;
search overlay top-most; no console errors.

==================== 6 · VERIFY ====================
6.1 start(): steps created per levels; skip-under-limit marks correct SKIPPED rows.
6.2 approve(): guard blocks same person; level advances; final event flips model;
intermediate modules unchanged. 6.3 reject requires comment when configured; return
resubmits. 6.4 Limits resolver returns correct approver per band + conditions.
6.5 Escalation scheduler notifies after N days. 6.6 Middleware 403s non-approvers.
6.7 Screens match APPENDIX B; no console errors.
REPORT: migration list; workflow traces (skip/guard/reject/return); limit resolver
samples; escalation proof; middleware map; parity confirmation.

==================== APPENDIX B — PHASE B MOCKUP (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authorization — Phase B (Workflows & Limits)</title>
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
  .btn-cta{color:#fff;background:var(--deep-2);font-weight:700;box-shadow:0 10px 22px -10px rgba(8,40,44,.55)}
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
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:860px}
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
  .tchip{display:inline-flex;padding:4px 11px;border-radius:999px;font-size:10.5px;font-weight:700}
  .t-role{background:rgba(18,143,142,.1);border:1px solid rgba(18,143,142,.35);color:var(--sec)}
  .tl{position:relative;padding-left:22px}
  .tl::before{content:"";position:absolute;left:7px;top:4px;bottom:4px;width:2px;background:var(--line)}
  .tl .st{position:relative;padding:7px 0}
  .tl .st::before{content:"";position:absolute;left:-19px;top:12px;width:10px;height:10px;border-radius:50%;background:var(--sec);border:2px solid #fff}
  .tl .st.pend::before{background:var(--amber-2)}
  .tl .st.off::before{background:var(--faint)}
  .tl .st .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .tl .st .s{font-size:11px;color:var(--muted)}
  .sw{width:44px;height:25px;border-radius:999px;background:#CBD8D6;position:relative;transition:.2s;flex:none;cursor:pointer}
  .sw.on{background:var(--sec)}
  .sw::after{content:"";position:absolute;top:3px;left:3px;width:19px;height:19px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .sw.on::after{left:22px}
  .swrow{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid var(--hair)}
  .swrow:last-child{border-bottom:none}
  .swrow .t{font-size:12.5px;font-weight:700;color:var(--ink)}
  .swrow .s{font-size:11px;color:var(--muted);margin-top:2px}
  .dl{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--hair);font-size:12.5px}
  .dl:last-child{border-bottom:none}
  .dl .l{color:var(--muted);font-weight:600}
  .dl .v{font-weight:700;color:var(--ink)}
</style>
</head>
<body>
<div class="wrap">
  <!-- Workflow Designer -->
  <div><span class="opt-tag">Phase B · Approval Workflow Designer · Purchase Order</span>
    <div class="page-head"><div><h1>Approval Workflow Designer</h1><div class="sub">Multi-level workflows configured without code.</div></div>
      <button class="btn btn-cta">＋ Add Level</button></div>
    <div class="grid2">
      <div class="card"><div class="card-h"><span class="ic">🧭</span><h2>Purchase Order · PO-WF-01</h2><div class="right"><span class="badge b-ok"><span class="bdot"></span>Active</span></div></div>
        <div class="pad"><div class="tl">
          <div class="st off"><div class="t">Created</div><div class="s">Procurement Officer</div></div>
          <div class="st off"><div class="t">Submitted</div><div class="s">Procurement Officer</div></div>
          <div class="st"><div class="t">Level 1 · Review</div><div class="s">Procurement Manager · ≤ K5,000,000</div></div>
          <div class="st"><div class="t">Level 2 · Approval</div><div class="s">Finance Officer</div></div>
          <div class="st"><div class="t">Level 3 · Approval</div><div class="s">Finance Manager · ≤ K25,000,000</div></div>
          <div class="st pend"><div class="t">Level 4 · Final Approval</div><div class="s">Chief Accountant · above K25,000,000</div></div>
          <div class="st off"><div class="t">Approved</div><div class="s">→ becomes available for posting</div></div>
        </div></div></div>
      <div>
        <div class="card mb"><div class="card-h"><span class="ic">⚙</span><h2>Workflow Settings</h2></div>
          <div class="pad">
            <div class="swrow"><div><div class="t">Same-person guard</div><div class="s">one user can't approve two levels</div></div><span class="sw on"></span></div>
            <div class="swrow"><div><div class="t">Skip level when under limit</div><div class="s">route by approval limits</div></div><span class="sw on"></span></div>
            <div class="swrow"><div><div class="t">Escalate after 3 days</div><div class="s">notify next level + HR</div></div><span class="sw on"></span></div>
            <div class="swrow"><div><div class="t">Require comment on reject</div><div class="s">mandatory reason</div></div><span class="sw on"></span></div>
          </div></div>
        <div class="card"><div class="card-h"><span class="ic">🎯</span><h2>Applies To (conditions)</h2></div>
          <div class="pad">
            <div class="dl"><span class="l">Module / Feature</span><span class="v">Procurement · Purchase Orders</span></div>
            <div class="dl"><span class="l">Branch</span><span class="v">All</span></div>
            <div class="dl"><span class="l">Department</span><span class="v">All</span></div>
            <div class="dl"><span class="l">Amount basis</span><span class="v">Transaction total (system currency)</span></div>
          </div></div>
      </div>
    </div>
  </div>
  <!-- Approval Limits -->
  <div><span class="opt-tag">Phase B · Approval Limits</span>
    <div class="page-head"><div><h1>Approval Limits</h1><div class="sub">Amount-based routing · conditions on branch, department, cost centre, project, account, type.</div></div>
      <button class="btn btn-cta">＋ New Limit Band</button></div>
    <div class="card"><div class="li-wrap"><table><thead><tr><th class="num">From</th><th class="num">To</th><th>Approver</th><th>Conditions</th><th>Status</th><th></th></tr></thead><tbody>
      <tr><td class="num">0</td><td class="num">500,000</td><td><span class="tchip t-role">Supervisor</span></td><td class="em">All branches</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="btn btn-ghost btn-sm">Edit</button></td></tr>
      <tr><td class="num">500,001</td><td class="num">5,000,000</td><td><span class="tchip t-role">Manager</span></td><td class="em">All branches</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="btn btn-ghost btn-sm">Edit</button></td></tr>
      <tr><td class="num">5,000,001</td><td class="num">25,000,000</td><td><span class="tchip t-role">Finance Manager</span></td><td class="em">All branches</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="btn btn-ghost btn-sm">Edit</button></td></tr>
      <tr><td class="num">25,000,001</td><td class="num">∞</td><td><span class="tchip t-role">CFO</span></td><td class="em">All branches</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td><td class="row-act"><button class="btn btn-ghost btn-sm">Edit</button></td></tr>
    </tbody></table></div></div>
  </div>
</div>
</body>
</html>
```