# COA Unified Hierarchy — Build Report

**Date:** Aug 22, 2026
**Scope:** Full rewrite of the Chart of Accounts page — schema, service, controller, view, CSS
**Design source:** APPENDIX A inline spec

---

## Deliverables

### Schema (Stage 1-2)

- `2026_08_22_000010` on `accounts`: `is_contra` (boolean default false), `sort_order` (unsigned default 0), `version` (unsigned default 1)
- `2026_08_22_000011` creates `coa_audit_trail`: `id`, `company_id`, `account_id` FK, `action`, `old_values`/`new_values` JSON, `reason`, `user_id`, `ip_address`, `user_agent`, `created_at`
- `2026_08_22_000012` on `user_preferences`: `coa_view` (string 10, nullable, default 'tree')

All 3 applied to all 4 tenant DBs (acct_acme_149593cc, acct_beta_0b50add8, acct_camelot_ideas_d722dffd, acct_camelot_af68f84e). The 4th DB had column-position differences (missing is_system_account base column) — migrations used adjusted after('is_petty_cash') with hasColumn guards.

### Model Changes

- **Account.php**: Added is_contra/sort_order/version to $fillable+$casts; added auditTrail(), isControlled(), isContra(), isDebitNormal(), isCreditNormal(), scopeOfType()
- **CoaAuditLog.php** (NEW): TenantScoped, $table = 'coa_audit_trail', action constants (CREATED/UPDATED/DEACTIVATED/REACTIVATED/RECLASSIFIED), static log() method
- **UserPreference.php**: Added coa_view to $fillable

### Service — CoaService.php (360 lines)

Built via PHP generator script (Write tool fails on raw PHP due to JSON-escaping backslashes).

| Method | Purpose |
|--------|---------|
| allAccounts() | All accounts for company, sorted by sort_order then code |
| computeBalances($accounts) | Single grouped SUM(debit)/SUM(credit) from journal_entry_lines via posted+reversed JEs; returns [id => [opening, current]] |
| buildTree() | Builds 3-level hierarchy: type -> sub_type -> accounts (recursive buildAccountNode). Returns [tree, balances, system_currency, stats] |
| buildAccountNode() | Recursive — resolves children, computes status (active/inactive/controlled), is_group, is_contra |
| computeEquation() | Assets = Liabilities + Equity check with balanced boolean and difference |
| getViewPreference() / setViewPreference() | Read/write user_preferences.coa_view |
| deactivateAccount() | Validates (non-controlled, zero balance, no active children, no posted JEs), sets is_active=false, bumps version, logs to coa_audit_trail |
| reactivateAccount() | Sets is_active=true, bumps version, logs |
| createAccount() / updateAccount() | CRUD with audit logging; parent must be same type; auto-computes level, is_group, allow_posting |

### Controller — AccountController.php (149 lines, fully rewritten)

| Method | Route | Purpose |
|--------|-------|---------|
| index | GET accounts | Unified COA page |
| tree | GET accounts/tree | JSON API for tree data |
| preference | POST accounts/preference | Save tree/list view preference |
| create / store | Standard | Account creation via CoaService |
| show / edit / update | Standard | Account detail/edit via CoaService |
| deactivate | PATCH accounts/{account}/deactivate | Soft-deactivate with reason; JSON or redirect |
| reactivate | PATCH accounts/{account}/reactivate | Reactivate; JSON or redirect |
| toggle | PATCH accounts/{account}/toggle | Legacy toggle (delegates to deactivate/reactivate) |

### Routes (4 new, before the {account} parameter route)

- GET    accounts/tree                    -> accounts.tree
- POST   accounts/preference              -> accounts.preference
- PATCH  accounts/{account}/deactivate    -> accounts.deactivate
- PATCH  accounts/{account}/reactivate    -> accounts.reactivate

### Views

- **index.blade.php** (319 lines): Unified COA page — equation strip, toolbar with seg toggle/search/status filter/zero-balance switch, hierarchy tree, flat list, context menu, deactivate modal, inline JS
- **_coa-tree-node.blade.php** (56 lines): Recursive tree node — renders group or leaf with carat, code, name, level/status chips, balances, view/edit actions
- **_coa-list-row.blade.php** (30 lines): Flat list row — code (indented by depth), name, description, sub-type, status chip, balances, view/edit/more actions

### CSS — .coa2-* block (~220 lines)

Appended to resources/css/app.css. Scoped under .coa2-. Covers:
- Equation strip, toolbar with segmented toggle, search, status filter, zero-balance switch
- Hierarchy tree with navy gradient header, nested expandable nodes, rollup summaries
- Flat list with per-type sections, navy thead, bordered table rows
- Chips (Type/Sub-type/Group/Leaf/Controlled/Contra + Active/Inactive/Controlled status)
- Context menu (View ledger, Edit, Deactivate/Reactivate)
- Deactivate modal with reason textarea and warning callout
- Responsive breakpoints at 1100px and 768px

---

## Design Decisions

1. **Balances are live** — computed from journal_entry_lines via a single grouped query in CoaService::computeBalances(). No cache column. The current_balance accessor on Account model was already available but doing per-account N+1; the service batch-computes.

2. **Controlled accounts are read-only** — is_system_account maps to isControlled(). Deactivation blocked at service level (422). UI shows lock chip + disables Edit button.

3. **View preference is per-user** — stored in user_preferences.coa_view, toggled via segmented control. Server persists on every toggle via AJAX POST.

4. **Tree rollups are client-side** — the roll() JS function recursively sums children into parent group/sub-type rows. Type-row totals are computed server-side in buildTree() as fallback.

5. **Deactivation is soft** — sets is_active=false, never deletes. Validated against: controlled status, non-zero balance, active children, posted journal entries. Audit-logged to coa_audit_trail.

6. **Flat list flattens the tree** — _coaFlattenAccounts() helper recursively expands nested children arrays into a flat list with _depth for indentation. Accounts displayed in hierarchy order with visual nesting.

7. **Inline client-side filter** — search input filters by code/name text match, status dropdown, and zero-balance toggle. All filtering happens in JS on the rendered DOM elements.

---

## Bugs Fixed During Build

1. **View tool JSON-escaping** — the Write tool fails on large PHP/Blade content because JSON encoding cannot handle extensive backslash sequences. Solution: PHP generator scripts (gen-*.php) that use single-quoted heredocs to produce the target files.

2. **Tree rollup elements missing** — the roll() JS function looked for .coa2-roll-op/.coa2-roll-cu elements but the type/sub-type rows only had bare <span class="coa2-num">. Fixed by adding the rollup classes to those spans.

3. **Flat list missing nested children** — the list view only iterated $subNode['accounts'] (top-level parents), not their children arrays. Fixed by adding _coaFlattenAccounts() recursive helper at the top of the view.

---

## Known Limitations

1. **No permission enforcement in controller** — the old AccountController used a coarse role_or_permission gate. Per-method requirePermission('chart-of-accounts.view') etc. was specified in the plan but not yet wired. All authenticated company users can currently access the page.

2. **No CoaController cleanup** — the orphaned CoaController (10 dead methods, 12 dead views) was left in place. Cleanup is a separate task.

3. **No audit-trail viewer** — the coa_audit_trail table is populated by deactivation/reactivation/update/create but there is no UI to view it yet.

---

## Verification

- php artisan view:cache — all views compile clean
- npm run build — app-i8Xdlsqs.css (670.65 kB, 240 .coa2- tokens confirmed)
- All 3 tenant DBs have is_contra/sort_order/version on accounts, coa_audit_trail table, coa_view on user_preferences
- Controller syntax: php -l clean
- CoaService syntax: php -l clean
- CoaAuditLog syntax: php -l clean