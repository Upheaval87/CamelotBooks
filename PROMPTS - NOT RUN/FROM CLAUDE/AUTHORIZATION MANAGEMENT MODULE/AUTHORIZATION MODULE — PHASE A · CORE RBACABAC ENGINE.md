AUTHORIZATION MODULE — PHASE A · CORE RBAC/ABAC ENGINE — IMPLEMENTATION PROMPT (LARAVEL)
(SELF-CONTAINED — applicable mockup screens embedded in APPENDIX A.)
SCOPE (Phase A ONLY): module→section→feature→action registry + roles/permissions +
user overrides + the Authz::can() gate + middleware + policies. NO workflows, limits,
SoD, delegations, dashboard/matrix/audit UI yet (Phases B/C). Phase A alone unblocks all
existing modules (payslip, loans, forms…) to start calling the engine.
SYSTEM CONSTRAINTS: currency from system setting (never hard-coded); system live-search
results overlay above all content (z-index ≥ 9999); pages render as mocked.
HARD GUARD — existing modules keep behaviour; they OPT-IN by calling helper/middleware.
Existing role usage migrates into `roles` without losing grants.

==================== 0 · DISCOVERY ====================
0.1 Locate users table, existing role/permission checks to map (not replace yet), module
list (GL/AP/AR/Banking/Procurement/Expenses/FixedAssets/Payroll/Budgeting/Reporting +
payslip/loans/forms), mail/scheduler setup.
0.2 List current scattered checks per module (drives integration plan + §8 audit).

==================== 1 · SCHEMA (migrations) ====================
auth_modules(id, code UQ, name, sort, active)
auth_sections(id, module_id FK, code, name, sort)
auth_features(id, section_id FK, code UQ e.g. 'gl.journals', name, sort)
auth_actions(id, code UQ, name, category ENUM[ACCESS,TRANSACTION,ADMINISTRATIVE])
  seed catalog: view,create,edit,delete,search,export,print | submit,review,verify,
  approve,reject,return,cancel,reverse,post,unpost,void | configure,override,unlock,
  reopen,close,recalculate
auth_permissions(id, feature_id FK, action_id FK, UQ(feature,action))
roles(id, code UQ, name, active)
role_permissions(id, role_id FK, permission_id FK, UQ(role,permission))
user_roles(id, user_id FK, role_id FK, UQ(user,role))
user_overrides(id, user_id FK, permission_id FK, grant BOOL, limit_amount DEC NULL,
  conditions JSON NULL, starts_at, expires_at NULL, created_by FK, reason)
auth_audit(id, user_id, module_id NULL, section_id NULL, feature_id NULL, action_id NULL,
  entity_type, entity_id, old_value TEXT, new_value TEXT, ip, device, reason, created_at)
  — foundation table; UI lands Phase C, writes start now.

==================== 2 · MODULE REGISTRATION ====================
Each module ships config/authz/{module}.php returning code/name/sections→features→actions.
Command `php artisan authz:sync` upserts catalog + auth_permissions WITHOUT touching
grants; idempotent; adding a module later = new config + sync. Seed auth_actions catalog.

==================== 3 · ENGINE (app/Services/Authz) ====================
Authz::can(User $u, string $feature, string $action, $context=null): bool
  1) user_overrides: explicit deny → deny; grant within dates + limit_amount + ABAC
     conditions → allow;
  2) role_permissions via user_roles → allow;
  3) default deny.
  (Phase C will insert a delegation check before step 1 — keep the evaluator pipeline
  ordered/extensible now.)
AbacEvaluator::passes(conditions, context): branch, department, cost_centre, project,
account, type, supplier, customer, employee, currency, budget_available.
Per-request memo + cache tag 'authz' invalidated on role_permissions/user_roles/
user_overrides changes (model events).
Blade: @canfeat('gl.journals','approve') … @endcanfeat (+ @cannotfeat); helper authz_can().

==================== 4 · MIDDLEWARE & POLICIES ====================
Route middleware `authz:{feature},{action}` → Authz::can else 403 (JSON for AJAX).
Base Policy class delegates every ability to Authz::can (no duplicated logic).
Integration examples for existing modules: Payslip distribution, Loan approvals, Forms
submissions add `authz:...` middleware or authz_can() calls — no logic change.

==================== 5 · SCREENS (match APPENDIX A) ====================
/authz/registry  read-only browser of synced catalog (module→section→feature→action).
/authz/roles     roles CRUD + permission assignment (module→feature→action checkboxes).
/authz/users     user roles + individual overrides (grant/deny, limit, dates, reason) with
                 expiry badges; clearly badged "Individual override".
Manage authorization: System Admin (+ Chief Accountant view). SoD on the module itself:
grant ≠ approve-of-grant where configured (rule enforcement lands Phase C).

==================== 6 · AUDIT (writes now, UI Phase C) ====================
Observer writes auth_audit on roles/role_permissions/user_roles/user_overrides changes
(old→new) + ip + user-agent + reason. Never log passwords/keys.

==================== 7 · A11Y / CONSTRAINTS ====================
Tables scroll; text 90–125; no overflow; pixel parity with APPENDIX A; system currency;
search overlay top-most; no console errors.

==================== 8 · VERIFY ====================
8.1 authz:sync builds catalog from configs; re-run idempotent; grants untouched.
8.2 can(): override deny > override grant > role > deny; expiry respected; limit_amount
enforced; ABAC conditions evaluated (sample traces per condition key).
8.3 Middleware 403s unauthorized (web + AJAX JSON); policies delegate correctly.
8.4 Cache invalidates on grant changes; per-request memo works.
8.5 Audit rows written with old→new on every grant change.
8.6 Screens match APPENDIX A; existing modules unaffected (opt-in only).
REPORT: migration list; sync output; can() decision traces; middleware map; audit samples;
integration snippet list for existing modules; parity confirmation.

==================== APPENDIX A — PHASE A MOCKUP (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authorization — Phase A (Roles & Overrides)</title>
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
  th.ctr,td.ctr{text-align:center}
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
  .t-ovr{background:rgba(180,83,9,.1);border:1px solid rgba(180,83,9,.35);color:var(--amber-2)}
</style>
</head>
<body>
<div class="wrap">
  <div><span class="opt-tag">Phase A · Roles &amp; User Authorizations</span>
    <div class="grid2">
      <div class="card"><div class="card-h"><span class="ic">👥</span><h2>Roles</h2><div class="right"><button class="btn btn-ghost btn-sm">＋ New Role</button></div></div>
        <div class="li-wrap"><table><thead><tr><th>Role</th><th class="num">Permissions</th><th class="num">Users</th><th>Status</th></tr></thead><tbody>
          <tr><td class="name">Finance Manager</td><td class="num">212</td><td class="num">3</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td></tr>
          <tr><td class="name">Finance Officer</td><td class="num">164</td><td class="num">6</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td></tr>
          <tr><td class="name">Procurement Manager</td><td class="num">98</td><td class="num">2</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td></tr>
          <tr><td class="name">Chief Accountant</td><td class="num">240</td><td class="num">1</td><td><span class="badge b-ok"><span class="bdot"></span>Active</span></td></tr>
        </tbody></table></div></div>
      <div class="card"><div class="card-h"><span class="ic">👤</span><h2>User Authorizations &amp; Overrides</h2></div>
        <div class="li-wrap"><table><thead><tr><th>User</th><th>Role</th><th>Override</th></tr></thead><tbody>
          <tr><td class="name">John Banda</td><td><span class="tchip t-role">Finance Officer</span></td><td><span class="tchip t-ovr">Individual override · approve ≤ K10,000,000 · expires 30 Sep 2026</span></td></tr>
          <tr><td class="name">Mary Phiri</td><td><span class="tchip t-role">Procurement Manager</span></td><td class="em">—</td></tr>
          <tr><td class="name">Grace Mbewe</td><td><span class="tchip t-role">Finance Officer</span></td><td><span class="tchip t-ovr">Export reports · expires 31 Dec 2026</span></td></tr>
        </tbody></table></div></div>
    </div>
  </div>
</div>
</body>
</html>
```