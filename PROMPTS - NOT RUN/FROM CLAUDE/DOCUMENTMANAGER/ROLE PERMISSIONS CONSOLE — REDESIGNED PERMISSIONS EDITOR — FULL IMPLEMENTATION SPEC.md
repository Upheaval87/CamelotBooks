ROLE PERMISSIONS CONSOLE — REDESIGNED PERMISSIONS EDITOR — FULL IMPLEMENTATION SPEC
(SELF-CONTAINED — complete reference mockup HTML in APPENDIX A.)
SCOPE: the role-permissions editor UI of the central Authorization engine: role list +
create/duplicate-with-copy, module/report permission matrices, filters, change tracking,
save/discard. Enforcement stays in the engine (Authz::can + middleware); this console
WRITES GRANTS ONLY.
SYSTEM CONSTRAINTS: currency from system setting (never hard-coded); system live-search
results overlay above all content (z-index ≥ 9999); pages render as mocked.
HARD GUARD — existing role usage migrates into `roles`/`role_permissions` WITHOUT losing
grants; Authz engine, middleware, workflows, SoD, delegations unchanged; console never
posts journals or touches accounting handlers.

==================== 0 · DISCOVERY ====================
0.1 Locate roles/role_permissions/user_roles/user_overrides + auth catalog tables
(modules/sections/features/actions) from the central engine; locate any legacy permission
storage to migrate.
0.2 List CURRENT role-management screens/handlers (drives §9 audit).

==================== 1 · SCHEMA (reuse central engine; add only if missing) =====
roles(id, code UQ, name, description NULL, active)
role_permissions(id, role_id FK, permission_id FK, UQ(role,permission))
user_roles(id, user_id FK, role_id FK, UQ(user,role))
auth_permissions catalog as synced by `authz:sync` (feature×action).
Sensitive-action set (red "danger" cells): delete, configure, override, unlock, reopen,
recalculate + any action flagged sensitive in auth_actions.

==================== 2 · CONSOLE STRUCTURE (match APPENDIX A) ====================
2.1 HEADER: title + sub; right: [Discard Changes ghost][Save Permissions cta].
2.2 LEFT ROLE PANEL: search; role cards (avatar initials, name, "{n} permissions ·
{u} users", Active/Read badges) with per-role ⧉ Duplicate; [＋ New Role].
2.3 SUMMARY STATS (live): Selected Role / Allowed Permissions (= role_permissions count) /
Sensitive Grants / Unsaved Changes.
2.4 COPY BANNER: after create-with-copy — "⧉ Copied {n} permissions from {role} — adjust
below, then Save Permissions." dismissible.
2.5 PERMISSIONS CARD: header [Expand All][Collapse All][Save Permissions sec]; tabs
Module Permissions / Report Permissions; filters row: 🔍 search (live), module select,
[Changed Only] toggle; "no results" empty state.
2.6 MODULE SECTIONS: one card per auth module; header (icon, title, quick actions
[Allow View][Allow All][Clear]); matrix table Feature × actions (View/Create/Edit/Delete/
Submit/Approve/Post/Reverse/Export/Configure). FEATURE CELLS SHOW NAME ONLY — no
subheadings/hints. Sensitive columns use red checked style. Header click collapses module.
2.7 REPORT PANEL: Financial Reports matrix (View/Export/Print/Email/Schedule) per report.
2.8 SAVE BAR: compact (max-width 900px), IN PAGE FLOW (scrolls with content), aligned FAR
RIGHT; shows "{n} unsaved permission changes for {role}" + [Discard Changes][Save
Permissions]; hidden when clean.

==================== 3 · BEHAVIOUR ====================
3.1 Baseline: on load/save, snapshot each checkbox state; dirty = diff vs baseline;
modules with diffs get amber border + "· changed"; Unsaved stat + save bar update live.
3.2 SAVE: single transaction — delete removed role_permissions, insert added; fire cache-
invalidation event; write auth_audit rows (role, feature·action, old→new, ip/device,
reason optional); clear dirty; remove Draft badge; refresh counts.
3.3 DISCARD: restore checkboxes to baseline; clear dirty (no server call).
3.4 QUICK ACTIONS: Allow View = check view column; Allow All = check all; Clear = uncheck
all (all count as changes until saved).
3.5 FILTERS: search matches module title + feature names; module select filters; Changed
Only shows only .changed modules; combined AND; empty state when none.
3.6 CREATE ROLE MODAL: Role Name* / Description / "Copy Permissions From" (Start blank or
any role with live count) + live source note; [Create & Edit Permissions] → insert role,
clone source role_permissions (if any), select it, Draft badge, copy banner, save bar
prompt. ⧉ Duplicate opens same modal pre-filled with that role as source.
3.7 Role card click selects role (loads its grants as baseline; unsaved changes of prior
role prompt discard/keep per app convention).

==================== 4 · ENGINE INTEGRATION ====================
Enforcement unchanged: Authz::can (delegation → override → role → deny) + middleware
`authz:{feature},{action}`; editor writes grants only; matrix ✓/— computed from
role_permissions (never hard-coded); cache invalidated on save.

==================== 5 · PERMISSIONS / SECURITY ====================
Manage roles/permissions: System Admin; Chief Accountant view-only. SoD on the module:
grant ≠ approve-of-grant where configured. All grant changes audited (§3.2).

==================== 6 · A11Y / RESPONSIVE ====================
Checkboxes keyboard-operable with visible focus rings; labels associated; tables
horizontal-scroll; ≤1050px sidebar stacks; ≤800px stats 2-col; ≤700px save bar wraps;
text-size matrix 90/100/110/125 no clipping; no console errors.

==================== 7 · CONSTRAINTS (PIXEL PARITY) ====================
Replicate APPENDIX A exactly: flat solid buttons; teal check chips; red danger checks;
no feature subheadings; save bar compact + scrollable + far right; modal copy flow;
filters wired. System currency; search overlay top-most.

==================== 8 · VERIFY ====================
8.1 Role select loads correct grants; counts match DB. 8.2 Change tracking: diff vs
baseline, module changed markers, unsaved counter, save bar visibility. 8.3 Save persists
exact diff in one transaction + audit rows + cache invalidation; Discard reverts.
8.4 Create-with-copy clones source grants; Draft badge; banner; duplicate prefill.
8.5 Filters (search/module/changed-only) combine correctly + empty state. 8.6 Quick
actions mutate only in-memory until saved. 8.7 Engine enforcement unchanged (spot-check
Authz::can + middleware). 8.8 Screens match APPENDIX A; no console errors.
REPORT: migration/mapping notes; save-transaction + audit samples; copy-clone proof;
filter matrix; parity confirmation; NO SECTION SKIPPED.

==================== APPENDIX A — EMBEDDED REFERENCE MOCKUP (HTML) ====================
```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Role Permissions Console — filters wired, hints removed</title>
<style>
  :root{--deep-2:#0c3539;--sec:#128F8E;--ink:#0B2A2D;--sub:#41585c;--muted:#5f7476;--faint:#8aa5a7;--border:#dceaea;--line:#e2ecec;--green:#15803d;--red:#b91c1c;--amber:#b45309;--hair:#EEF3F1;--bg:#eef4f4;
    --shadow:0 1px 2px rgba(10,42,46,.04),0 12px 30px -14px rgba(10,42,46,.22);}
  *{box-sizing:border-box;margin:0;padding:0}html,body{overflow-x:clip}
  body{font-family:Inter,"Segoe UI",system-ui,sans-serif;background:var(--bg);color:#374151;font-size:14px;-webkit-font-smoothing:antialiased}
  .wrap{max-width:1440px;margin:0 auto;padding:24px 28px 40px}
  .crumbs{font-size:12px;font-weight:700;color:var(--muted);margin-bottom:10px}
  .crumbs .here{color:var(--ink)}
  .topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px;flex-wrap:wrap}
  h1{color:var(--ink);font-size:24px;font-weight:850}
  .sub{color:var(--muted);font-size:12.5px;margin-top:5px}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:12px;border:1px solid transparent;cursor:pointer;font-family:inherit;font-weight:750;font-size:13px;white-space:nowrap}
  .btn-cta{color:#fff;background:var(--deep-2);box-shadow:0 12px 24px -14px rgba(8,40,44,.6)}
  .btn-sec{color:#fff;background:var(--sec);box-shadow:0 10px 20px -14px rgba(18,143,142,.55)}
  .btn-ghost{color:var(--ink);background:#e8f0f0;border-color:var(--border)}
  .btn.on{background:var(--sec);border-color:var(--sec);color:#fff}
  .btn-sm{height:34px;padding:0 13px;border-radius:10px;font-size:12px}
  .layout{display:grid;grid-template-columns:290px 1fr;gap:18px;align-items:start}
  @media(max-width:1050px){.layout{grid-template-columns:1fr}}
  .card{background:rgba(255,255,255,.94);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);overflow:hidden}
  .card-h{display:flex;align-items:center;gap:10px;padding:15px 18px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#fff,#f6faf9)}
  .card-h h2{color:var(--ink);font-size:14px;font-weight:850}
  .card-h .right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
  .pad{padding:16px 18px}
  .search{width:100%;height:40px;border-radius:11px;border:1px solid var(--border);background:#fff;padding:0 12px;color:var(--ink);font-family:inherit;font-size:13px}
  .search:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.12)}
  .roles{display:flex;flex-direction:column;gap:8px;margin-top:14px}
  .role{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:13px;border:1px solid transparent;cursor:pointer;background:#fff}
  .role:hover{border-color:rgba(18,143,142,.3)}
  .role.on{background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.42)}
  .avatar{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;color:#fff;background:var(--deep-2);font-size:12px;font-weight:850;flex:none}
  .role .name{color:var(--ink);font-weight:800;font-size:13px}
  .role .meta{color:var(--muted);font-size:11px;margin-top:2px}
  .role .dup{margin-left:auto;border:1px solid var(--border);background:#fff;border-radius:9px;width:30px;height:30px;cursor:pointer;color:var(--muted)}
  .role .dup:hover{color:var(--sec);border-color:rgba(18,143,142,.4)}
  .badge{display:inline-flex;padding:4px 9px;border-radius:999px;font-size:10px;font-weight:850}
  .b-draft{background:rgba(180,83,9,.1);color:var(--amber);border:1px solid rgba(180,83,9,.35)}
  .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
  @media(max-width:800px){.summary{grid-template-columns:1fr 1fr}}
  .stat{background:#fff;border:1px solid var(--border);border-radius:15px;padding:14px 15px}
  .stat .l{color:var(--muted);font-size:9.5px;text-transform:uppercase;letter-spacing:.1em;font-weight:850}
  .stat .v{color:var(--ink);font-size:20px;font-weight:850;margin-top:5px}
  .copybar{display:none;align-items:center;gap:12px;border:1px solid rgba(18,143,142,.4);background:rgba(18,143,142,.08);border-radius:14px;padding:11px 14px;margin-bottom:14px;font-size:12.5px;color:var(--ink);font-weight:700}
  .copybar.on{display:flex}
  .copybar .x{margin-left:auto;border:none;background:none;color:var(--muted);cursor:pointer;font-size:14px}
  .tabs{display:flex;gap:6px;background:#e8f0f0;padding:5px;border-radius:14px;margin-bottom:14px;width:max-content;max-width:100%;flex-wrap:wrap}
  .tab{border:none;background:transparent;color:var(--muted);height:34px;padding:0 14px;border-radius:10px;font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.09em;cursor:pointer}
  .tab.on{background:#fff;color:var(--sec);box-shadow:0 1px 2px rgba(8,40,44,.08)}
  .filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
  .filters .search{max-width:340px}
  select.search{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center;padding-right:30px;max-width:210px}
  .module{border:1px solid var(--border);border-radius:16px;background:#fff;margin-bottom:12px;overflow:hidden}
  .module.changed{border-color:rgba(180,83,9,.5)}
  .module.changed .module-title::after{content:" · changed";color:var(--amber);font-size:10px;font-weight:850}
  .module.collapsed .table-wrap{display:none}
  .module-head{display:flex;align-items:center;gap:12px;padding:13px 16px;background:#fbfdfd;border-bottom:1px solid var(--line);cursor:pointer}
  .mod-icon{width:34px;height:34px;border-radius:11px;background:rgba(18,143,142,.1);color:var(--sec);display:grid;place-items:center;font-weight:850}
  .module-title{color:var(--ink);font-size:13.5px;font-weight:850}
  .module-actions{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
  .table-wrap{overflow-x:auto}
  table{width:100%;min-width:860px;border-collapse:collapse}
  th{background:#f4f8f8;color:var(--muted);font-size:10px;font-weight:850;letter-spacing:.08em;text-transform:uppercase;text-align:center;padding:10px 8px;border-bottom:1px solid var(--line)}
  th:first-child{text-align:left;padding-left:16px}
  td{padding:11px 8px;border-bottom:1px solid var(--hair);text-align:center;color:var(--sub);font-size:12.5px}
  td:first-child{text-align:left;padding-left:16px}
  tr:last-child td{border-bottom:none}
  .feature{color:var(--ink);font-weight:750}
  .check{position:relative;display:inline-grid;place-items:center;width:24px;height:24px;cursor:pointer}
  .check input{position:absolute;opacity:0}
  .box{width:22px;height:22px;border-radius:7px;border:2px solid var(--border);background:#fff;color:#fff;display:grid;place-items:center;font-size:12px;transition:.15s}
  .box:after{content:"✓";opacity:0}
  .check input:checked + .box{background:var(--sec);border-color:var(--sec)}
  .check input:checked + .box:after{opacity:1}
  .danger input:checked + .box{background:var(--red);border-color:var(--red)}
  .panel{display:none}
  .panel.on{display:block}
  .noresult{border:1px dashed var(--border);border-radius:14px;padding:22px;text-align:center;color:var(--muted);font-size:13px;display:none}
  /* SAVE BAR — compact, in page flow, far right */
  .save-footer{width:min(900px,100%);margin:18px 0 0 auto;background:var(--deep-2);color:#fff;border-radius:18px;box-shadow:0 24px 50px -18px rgba(8,40,44,.35);display:none;align-items:center;gap:14px;padding:12px 14px 12px 18px}
  .save-footer.on{display:flex}
  .save-footer .msg{font-size:13px;font-weight:750}
  .save-footer .msg span{color:#b7f1ee}
  .save-footer .spacer{flex:1}
  .save-footer .btn-ghost{background:rgba(255,255,255,.11);color:#fff;border-color:rgba(255,255,255,.25)}
  @media(max-width:700px){.save-footer{flex-wrap:wrap;justify-content:flex-end}.save-footer .msg{width:100%}}
  .modal{position:fixed;inset:0;background:rgba(8,40,44,.5);display:none;place-items:center;z-index:90;padding:20px}
  .modal.on{display:grid}
  .mbox{width:min(520px,100%);background:#fff;border-radius:18px;box-shadow:0 30px 60px -20px rgba(8,40,44,.5);overflow:hidden}
  .mbox-h{padding:18px 20px;border-bottom:1px solid var(--line)}
  .mbox-h h3{color:var(--ink);font-size:17px;font-weight:850}
  .mbox-h p{color:var(--muted);font-size:12px;margin-top:4px}
  .mbox-b{padding:18px 20px;display:flex;flex-direction:column;gap:14px}
  .f label{display:block;font-size:10.5px;font-weight:850;letter-spacing:.09em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
  .f .req{color:var(--red)}
  .f .in{width:100%;height:44px;border-radius:11px;border:1px solid var(--border);background:#fff;padding:0 13px;font-size:13.5px;color:var(--ink);font-family:inherit}
  .f select.in{appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%235f7476' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 13px center;padding-right:32px}
  .f .in:focus{outline:none;border-color:var(--sec);box-shadow:0 0 0 4px rgba(18,143,142,.13)}
  .srcnote{border:1px dashed var(--border);border-radius:12px;padding:10px 12px;font-size:12px;color:var(--muted)}
  .srcnote b{color:var(--ink)}
  .mbox-f{display:flex;gap:10px;justify-content:flex-end;padding:16px 20px;border-top:1px solid var(--line);background:#fbfdfd}
</style>
</head>
<body>
<div class="wrap">
  <div class="crumbs">Authorization Management › <span class="here">Roles &amp; Permissions</span></div>

  <div class="topbar">
    <div><h1>Role Permissions Console</h1>
      <div class="sub">Create roles from scratch or copy an existing role's permissions, then adjust and save.</div></div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="btn btn-ghost" id="discardTop">Discard Changes</button>
      <button class="btn btn-cta" id="saveTop">Save Permissions</button>
    </div>
  </div>

  <div class="layout">
    <aside class="card">
      <div class="card-h"><h2>Roles</h2><div class="right"><button class="btn btn-sec btn-sm" id="newRole">＋ New Role</button></div></div>
      <div class="pad">
        <input class="search" placeholder="Search roles…">
        <div class="roles" id="roleList">
          <div class="role on" data-name="Finance Manager" data-perms="212"><div class="avatar">FM</div>
            <div><div class="name">Finance Manager</div><div class="meta">212 permissions · 3 users</div></div>
            <button class="dup" title="Duplicate role (copy permissions)">⧉</button></div>
          <div class="role" data-name="Finance Officer" data-perms="164"><div class="avatar">FO</div>
            <div><div class="name">Finance Officer</div><div class="meta">164 permissions · 6 users</div></div>
            <button class="dup" title="Duplicate role (copy permissions)">⧉</button></div>
          <div class="role" data-name="Procurement Manager" data-perms="98"><div class="avatar">PM</div>
            <div><div class="name">Procurement Manager</div><div class="meta">98 permissions · 2 users</div></div>
            <button class="dup" title="Duplicate role (copy permissions)">⧉</button></div>
          <div class="role" data-name="Chief Accountant" data-perms="240"><div class="avatar">CA</div>
            <div><div class="name">Chief Accountant</div><div class="meta">240 permissions · 1 user</div></div>
            <button class="dup" title="Duplicate role (copy permissions)">⧉</button></div>
          <div class="role" data-name="Auditor" data-perms="60"><div class="avatar">AU</div>
            <div><div class="name">Auditor</div><div class="meta">View-only · 4 users</div></div>
            <button class="dup" title="Duplicate role (copy permissions)">⧉</button></div>
        </div>
      </div>
    </aside>

    <main>
      <div class="summary">
        <div class="stat"><div class="l">Selected Role</div><div class="v" id="sumRole">Finance Manager</div></div>
        <div class="stat"><div class="l">Allowed Permissions</div><div class="v" id="sumPerms">212</div></div>
        <div class="stat"><div class="l">Sensitive Grants</div><div class="v">18</div></div>
        <div class="stat"><div class="l">Unsaved Changes</div><div class="v" id="sumUnsaved">0</div></div>
      </div>

      <div class="copybar" id="copybar">⧉ <span id="copymsg"></span><button class="x" id="copybarX">✕</button></div>

      <div class="card">
        <div class="card-h"><h2>Permissions</h2>
          <div class="right">
            <button class="btn btn-ghost btn-sm" id="expand">Expand All</button>
            <button class="btn btn-ghost btn-sm" id="collapseBtn">Collapse All</button>
            <button class="btn btn-sec btn-sm" id="saveCard">Save Permissions</button>
          </div></div>
        <div class="pad">
          <div class="tabs">
            <button class="tab on" data-p="module">Module Permissions</button>
            <button class="tab" data-p="report">Report Permissions</button>
          </div>
          <div class="filters">
            <input class="search" id="filterQ" placeholder="🔍 Filter modules & features…">
            <select class="search" id="filterMod"><option value="">All modules</option><option>General Ledger</option><option>Accounts Payable</option><option>Procurement</option><option>Financial Reports</option></select>
            <button class="btn btn-ghost btn-sm" id="changedOnly">Changed Only</button>
          </div>
          <div class="noresult" id="noresult">No modules match the current filter.</div>

          <!-- MODULE PERMISSIONS (no feature subheadings) -->
          <div class="panel on" id="panel-module">
            <section class="module" data-mod="General Ledger">
              <div class="module-head"><div class="mod-icon">GL</div>
                <div><div class="module-title">General Ledger</div></div>
                <div class="module-actions"><button class="btn btn-ghost btn-sm">Allow View</button><button class="btn btn-ghost btn-sm">Allow All</button><button class="btn btn-ghost btn-sm">Clear</button></div></div>
              <div class="table-wrap"><table><thead><tr><th>Feature</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th><th>Submit</th><th>Approve</th><th>Post</th><th>Reverse</th><th>Export</th><th>Configure</th></tr></thead><tbody>
                <tr><td><div class="feature">Journal Entries</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Chart of Accounts</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Period Closing</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input checked type="checkbox"><span class="box"></span></label></td></tr>
              </tbody></table></div>
            </section>

            <section class="module" data-mod="Accounts Payable">
              <div class="module-head"><div class="mod-icon">AP</div>
                <div><div class="module-title">Accounts Payable</div></div>
                <div class="module-actions"><button class="btn btn-ghost btn-sm">Allow View</button><button class="btn btn-ghost btn-sm">Allow All</button><button class="btn btn-ghost btn-sm">Clear</button></div></div>
              <div class="table-wrap"><table><thead><tr><th>Feature</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th><th>Submit</th><th>Approve</th><th>Post</th><th>Reverse</th><th>Export</th><th>Configure</th></tr></thead><tbody>
                <tr><td><div class="feature">Purchase Invoices</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Supplier Payments</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
              </tbody></table></div>
            </section>

            <section class="module" data-mod="Procurement">
              <div class="module-head"><div class="mod-icon">PR</div>
                <div><div class="module-title">Procurement</div></div>
                <div class="module-actions"><button class="btn btn-ghost btn-sm">Allow View</button><button class="btn btn-ghost btn-sm">Allow All</button><button class="btn btn-ghost btn-sm">Clear</button></div></div>
              <div class="table-wrap"><table><thead><tr><th>Feature</th><th>View</th><th>Create</th><th>Edit</th><th>Delete</th><th>Submit</th><th>Approve</th><th>Post</th><th>Reverse</th><th>Export</th><th>Configure</th></tr></thead><tbody>
                <tr><td><div class="feature">Purchase Requisitions</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Purchase Orders</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check danger"><input type="checkbox"><span class="box"></span></label></td></tr>
              </tbody></table></div>
            </section>
          </div>

          <!-- REPORT PERMISSIONS -->
          <div class="panel" id="panel-report">
            <section class="module" data-mod="Financial Reports">
              <div class="module-head"><div class="mod-icon">RP</div>
                <div><div class="module-title">Financial Reports</div></div>
                <div class="module-actions"><button class="btn btn-ghost btn-sm">Allow View</button><button class="btn btn-ghost btn-sm">Allow All</button><button class="btn btn-ghost btn-sm">Clear</button></div></div>
              <div class="table-wrap"><table><thead><tr><th>Report</th><th>View</th><th>Export</th><th>Print</th><th>Email</th><th>Schedule</th></tr></thead><tbody>
                <tr><td><div class="feature">Trial Balance</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Income Statement</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Balance Sheet</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td></tr>
                <tr><td><div class="feature">Cash Flow</div></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input checked type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td>
                  <td><label class="check"><input type="checkbox"><span class="box"></span></label></td></tr>
              </tbody></table></div>
            </section>
          </div>

        </div>
      </div>
    </main>
  </div>

  <!-- SAVE BAR — compact, scrolls with page, far right -->
  <div class="save-footer" id="savebar">
    <div class="msg"><span id="saveMsg">0 unsaved permission changes</span> for <span id="saveRole">Finance Manager</span></div>
    <div class="spacer"></div>
    <button class="btn btn-ghost" id="discardBar">Discard Changes</button>
    <button class="btn btn-sec" id="saveBar">Save Permissions</button>
  </div>
</div>

<!-- CREATE / DUPLICATE ROLE MODAL -->
<div class="modal" id="modal">
  <div class="mbox">
    <div class="mbox-h"><h3 id="mTitle">Create New Role</h3><p>Optionally start from an existing role's permission set, then adjust.</p></div>
    <div class="mbox-b">
      <div class="f"><label>Role Name <span class="req">*</span></label><input class="in" id="mName" placeholder="e.g. Deputy Finance Manager"></div>
      <div class="f"><label>Description</label><input class="in" id="mDesc" placeholder="What is this role for?"></div>
      <div class="f"><label>Copy Permissions From</label>
        <select class="in" id="mSource">
          <option value="0">Start blank (no permissions)</option>
          <option value="212" data-role="Finance Manager">Finance Manager — 212 permissions</option>
          <option value="164" data-role="Finance Officer">Finance Officer — 164 permissions</option>
          <option value="98" data-role="Procurement Manager">Procurement Manager — 98 permissions</option>
          <option value="240" data-role="Chief Accountant">Chief Accountant — 240 permissions</option>
          <option value="60" data-role="Auditor">Auditor — 60 permissions (view-only)</option>
        </select></div>
      <div class="srcnote" id="mNote">New role will start with <b>0 permissions</b>. You can grant them individually after creation.</div>
    </div>
    <div class="mbox-f"><button class="btn btn-ghost" id="mCancel">Cancel</button><button class="btn btn-cta" id="mCreate">Create &amp; Edit Permissions</button></div>
  </div>
</div>

<script>
(function(){
  /* capture initial checkbox states for discard */
  document.querySelectorAll('.check input').forEach(function(i){i.dataset.init=i.checked;});

  var dirty=0;
  function showSave(msg){
    document.getElementById('savebar').classList.add('on');
    document.getElementById('saveMsg').textContent=msg||(dirty+' unsaved permission change'+(dirty===1?'':'s'));
  }

  /* ---- FILTERS: search + module select + Changed Only ---- */
  var q=document.getElementById('filterQ'),modSel=document.getElementById('filterMod'),
      changedBtn=document.getElementById('changedOnly'),nores=document.getElementById('noresult');
  function applyFilters(){
    var term=q.value.trim().toLowerCase(), mod=modSel.value,
        onlyChanged=changedBtn.classList.contains('on'), visible=0;
    document.querySelectorAll('.module').forEach(function(m){
      var inPanel=m.closest('.panel').classList.contains('on');
      var hideQ=term&&m.textContent.toLowerCase().indexOf(term)===-1;
      var hideM=mod&&m.dataset.mod!==mod;
      var hideC=onlyChanged&&!m.classList.contains('changed');
      var show=inPanel&&!hideQ&&!hideM&&!hideC;
      m.style.display=show?'':'none';
      if(show)visible++;
    });
    nores.style.display=visible?'none':'block';
  }
  q.addEventListener('input',applyFilters);
  modSel.addEventListener('change',applyFilters);
  changedBtn.addEventListener('click',function(){changedBtn.classList.toggle('on');applyFilters();});

  /* ---- tabs ---- */
  document.querySelectorAll('.tab').forEach(function(t){
    t.addEventListener('click',function(){
      document.querySelectorAll('.tab').forEach(function(x){x.classList.remove('on');});
      t.classList.add('on');
      document.getElementById('panel-module').classList.toggle('on',t.dataset.p==='module');
      document.getElementById('panel-report').classList.toggle('on',t.dataset.p==='report');
      applyFilters();
    });
  });

  /* ---- expand / collapse ---- */
  document.getElementById('expand').addEventListener('click',function(){document.querySelectorAll('.module').forEach(function(m){m.classList.remove('collapsed');});});
  document.getElementById('collapseBtn').addEventListener('click',function(){document.querySelectorAll('.module').forEach(function(m){m.classList.add('collapsed');});});
  document.querySelectorAll('.module-head').forEach(function(h){h.addEventListener('click',function(e){if(!e.target.closest('.module-actions'))h.closest('.module').classList.toggle('collapsed');});});

  /* ---- change tracking ---- */
  document.addEventListener('change',function(e){
    if(e.target.matches('.check input')){
      dirty++;document.getElementById('sumUnsaved').textContent=dirty;
      e.target.closest('.module').classList.add('changed');
      showSave();applyFilters();
    }
  });
  function save(){
    document.querySelectorAll('.check input').forEach(function(i){i.dataset.init=i.checked;});
    dirty=0;document.getElementById('sumUnsaved').textContent=0;
    document.querySelectorAll('.module').forEach(function(m){m.classList.remove('changed');});
    document.getElementById('savebar').classList.remove('on');
    document.getElementById('copybar').classList.remove('on');
    var r=document.querySelector('.role.on .badge');if(r)r.remove();
    applyFilters();
  }
  function discard(){
    document.querySelectorAll('.check input').forEach(function(i){i.checked=(i.dataset.init==='true');});
    dirty=0;document.getElementById('sumUnsaved').textContent=0;
    document.querySelectorAll('.module').forEach(function(m){m.classList.remove('changed');});
    document.getElementById('savebar').classList.remove('on');
    applyFilters();
  }
  ['saveTop','saveCard','saveBar'].forEach(function(id){document.getElementById(id).addEventListener('click',save);});
  ['discardTop','discardBar'].forEach(function(id){document.getElementById(id).addEventListener('click',discard);});

  /* ---- roles / modal / copy ---- */
  var modal=document.getElementById('modal'),src=document.getElementById('mSource'),note=document.getElementById('mNote');
  function syncNote(){
    var v=parseInt(src.value,10),role=src.selectedOptions[0].dataset.role;
    note.innerHTML=v>0?('New role will copy <b>'+v+' permissions</b> from <b>'+role+'</b>. Adjust anything below before saving.'):'New role will start with <b>0 permissions</b>. You can grant them individually after creation.';
  }
  function openModal(sourceRole){
    document.getElementById('mTitle').textContent=sourceRole?('Duplicate "'+sourceRole+'"'):'Create New Role';
    if(sourceRole){
      [].slice.call(src.options).forEach(function(o){if(o.dataset.role===sourceRole)src.value=o.value;});
      document.getElementById('mName').value=sourceRole+' (Copy)';
    }
    syncNote();modal.classList.add('on');
  }
  src.addEventListener('change',syncNote);
  document.getElementById('newRole').addEventListener('click',function(){openModal(null);});
  document.getElementById('roleList').addEventListener('click',function(e){
    var d=e.target.closest('.dup');if(d){openModal(d.closest('.role').dataset.name);return;}
    var r=e.target.closest('.role');if(r){
      [].slice.call(document.querySelectorAll('.role')).forEach(function(x){x.classList.remove('on');});
      r.classList.add('on');
      document.getElementById('sumRole').textContent=r.dataset.name;
      document.getElementById('sumPerms').textContent=r.dataset.perms;
      document.getElementById('saveRole').textContent=r.dataset.name;
    }
  });
  document.getElementById('mCancel').addEventListener('click',function(){modal.classList.remove('on');});
  modal.addEventListener('click',function(e){if(e.target===modal)modal.classList.remove('on');});
  document.getElementById('mCreate').addEventListener('click',function(){
    var name=document.getElementById('mName').value.trim();
    if(!name){document.getElementById('mName').focus();return;}
    var v=parseInt(src.value,10),role=src.selectedOptions[0].dataset.role||null;
    modal.classList.remove('on');
    var list=document.getElementById('roleList');
    var el=document.createElement('div');el.className='role on';el.dataset.name=name;el.dataset.perms=v;
    el.innerHTML='<div class="avatar">'+name.split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase()+'</div>'+
      '<div><div class="name">'+name+'</div><div class="meta">'+v+' permissions · 0 users</div></div> <span class="badge b-draft">Draft</span>';
    [].slice.call(list.children).forEach(function(x){x.classList.remove('on');});
    list.appendChild(el);
    document.getElementById('sumRole').textContent=name;
    document.getElementById('sumPerms').textContent=v;
    document.getElementById('saveRole').textContent=name;
    if(v>0){document.getElementById('copymsg').textContent='Copied '+v+' permissions from '+role+' — adjust below, then Save Permissions.';document.getElementById('copybar').classList.add('on');}
    else{document.getElementById('copybar').classList.remove('on');}
    showSave('New role "'+name+'" — review copied permissions and Save');
  });
  document.getElementById('copybarX').addEventListener('click',function(){document.getElementById('copybar').classList.remove('on');});
})();
</script>
</body>
</html>
```