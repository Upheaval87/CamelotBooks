# Role Permissions Console v2 — Rebuild Report

## Summary

Complete rebuild of the Role Permissions Console (`admin/permissions`) to match the APPENDIX A pixel reference mockup. The v1 implementation had 16 deviations from the mockup; all were deleted and replaced with mockup-accurate components.

**Date**: Aug 22, 2026
**Route**: `GET /admin/permissions`
**Controller**: `app/Http/Controllers/Admin/PermissionController.php`
**Service**: `app/Services/Accounting/RoleService.php`
**View**: `resources/views/admin/permissions/index.blade.php`
**JS**: `resources/js/permissions-console.js`
**CSS**: `resources/css/app.css` (`.rpc-*` block)

## What Changed

### Deleted (v1 deviations)
| v1 Feature | Why Deleted |
|---|---|
| Chip-pill permission buttons | Mockup uses a matrix table with checkbox cells |
| Per-feature on/off toggles | Mockup uses matrix column-based checkboxes |
| Filter chips (All/Granted/Locked/Sensitive/Deactivate) | Mockup uses search + module select + Changed Only toggle |
| "TOTAL GRANTED / SENSITIVE / ACCESS LEVEL" cards | Mockup has 4 stat cards (Selected Role / Allowed Permissions / Sensitive Grants / Unsaved Changes) |
| Section count badges (7/8) | Removed |
| Accordion groups with row toggles | Mockup has flat module sections with expand/collapse all |
| Disabled/greyed permission pills | Replaced by matrix table "—" for N/A |
| Toggle active button in topbar | Removed from permissions view |
| Top nav/sidebar menu | No nav designed by this feature |

### Added (v2 APPENDIX A)
| v2 Feature | Detail |
|---|---|
| Breadcrumb | "Authorization Management › Roles & Permissions" |
| Topbar with title + actions | h1 + subtitle + Discard/Save buttons (show only when dirty) |
| 2-column layout | 290px sticky sidebar + 1fr main |
| Sidebar: Roles card | Search input + role cards with avatar initials, name, perm/user counts, ⧉ duplicate button |
| 4 stat cards | Selected Role / Allowed Permissions / Sensitive Grants / Unsaved Changes |
| Copy banner | Shows when creating with a source role |
| Permissions card | Tabs: Module \| Report |
| Filter row | Search + module select + Changed Only toggle |
| Module sections | Expand/Collapse All, amber border for changed modules |
| Matrix tables | 10 columns: View, Create, Edit, Delete, Submit, Approve, Post, Reverse, Export, Configure |
| Danger checkboxes | Delete + Configure use red checked style |
| Report table | 5 columns: View, Export, Print, Email, Schedule |
| In-flow save bar | Compact (max 900px), dark (`var(--deep-2)`), aligned right, hidden when clean |
| Create/Duplicate modal | Role Name + Description + Copy From select + source note |
| Self-lockout modal | Warning when removing own role management permissions |
| Conflict modal | 409 conflict detection from server |

### Retained (unchanged)
- **Controller**: All 5 endpoints untouched — same route, same JSON contract
- **Service**: `RoleService` untouched — `getRoleSummaries()`, `getMatrix()`, `savePermissions()`, `createRole()`, `wouldSelfLockout()`
- **Routes**: 5 routes unchanged
- **Auth engine**: Spatie v6.25 with teams, same middleware, same guards
- **Data contract**: `#rpc-data` JSON script + `/admin/permissions/{id}/permissions` JSON endpoint

## File Changes

| File | Action | Size |
|---|---|---|
| `resources/css/app.css` | Old `.rpc-*` block deleted (lines 12536–12640); new block appended | 685KB total |
| `resources/views/admin/permissions/index.blade.php` | Full rewrite | 18.9KB |
| `resources/js/permissions-console.js` | Full rewrite | 16.6KB |
| `tests/Feature/Admin/PermissionsConsoleRenderTest.php` | Updated assertion | +1 line |

## CSS Architecture

New `.rpc-*` block (lines ~12640–12770 in `app.css`):
- **BEM-style scoping**: All rules under `.rpc-` prefix, no global pollution
- **Teal tokens**: Uses existing `--deep-1/2/3`, `--sec`, `--sec-2`, `--ink`, `--muted`, `--faint`, `--border`, `--line`
- **Glass cards**: `backdrop-filter:blur(14px)`, `rgba(255,255,255,.75)` background
- **Matrix table**: Full-width, bordered cells, teal checked state, red danger state
- **Save bar**: In-flow, compact (`max-width:min(900px,100%)`), dark background
- **Responsive**: 1100px (240px sidebar), 900px (single column), 720px (compact)
- **Reduced motion**: `@media(prefers-reduced-motion:reduce)` disables animations

## JS Architecture

Alpine `permissionsConsole` data:
- **State**: `modules[]`, `reports[]`, `roles[]`, `selectedRoleId`, `dirty`, `dirtyCount`
- **Computed**: `filteredRoles`, `allModuleLabels`, `visibleGroups`, `visibleReports`, `totalGranted`, `totalSensitive`
- **Actions**: `selectRole()`, `toggleCell()`, `toggleReportCell()`, `allowView/All()`, `clearAll()`
- **Dirty tracking**: `originalPerms` baseline snapshot; `currentPermsSet()` computes live diff
- **API**: Same endpoints, same JSON contract, `CB.confirm()` integration for unsaved-changes dialog

## Verification

- `npm run build` — clean (674KB CSS, 318KB JS)
- `artisan view:cache` — all views compile
- `artisan test --filter=PermissionsConsoleRender` — 3 passed / 15 assertions
- Old v1 classes (`rpc-card-tabs`, `rpc-perm-card`, `rpc-chip-pill`, etc.) all absent from compiled bundle
- New v2 classes (145 `.rpc-*` tokens) confirmed in compiled CSS
