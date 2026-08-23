<x-app-layout>
<script type="application/json" id="rpc-data">
{!! json_encode([
    'roles' => $roleSummaries,
    'moduleGroups' => $moduleGroups,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>
<div x-data="permissionsConsole()" x-init="init()" x-cloak class="rpc-wrap">
    <div class="rpc-page">
        <div class="rpc-crumbs">
            <a href="#">Authorization Management</a>
            <span class="sep">&rsaquo;</span>
            <span class="here">Roles &amp; Permissions</span>
        </div>
        <div class="rpc-topbar">
            <div>
                <h1>Role Permissions Console</h1>
                <div class="sub">Select a role from the left panel to view and manage permissions.</div>
            </div>
            <div class="actions">
                <button class="rpc-btn rpc-btn-ghost" @click="discard()" x-show="selectedRoleId">Discard Changes</button>
                <button class="rpc-btn rpc-btn-cta" @click="save()" :disabled="saving || barState !== 'dirty'" x-show="selectedRoleId">
                    <span x-text="saving ? 'Saving...' : 'Save Permissions'"></span>
                </button>
            </div>
        </div>
        <div class="rpc-layout">
            <div class="rpc-sidebar">
                <div class="rpc-sidebar-head">
                    <span class="rpc-sidebar-lbl">Roles</span>
                    <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="openCreateModal()">+ New</button>
                </div>
                <div class="rpc-sidebar-srch">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" placeholder="Search roles..." x-model="roleSearch">
                </div>
                <div class="rpc-role-list">
                    <template x-for="r in filteredRoles" :key="r.id">
                        <div class="rpc-role" :class="{'is-on': selectedRoleId===r.id, 'is-off': !r.is_active}" @click="selectRole(r.id)">
                            <div class="rpc-role-av" :class="{'is-off': !r.is_active}"><span x-text="r.initials"></span></div>
                            <div class="rpc-role-info">
                                <div class="rpc-role-name" x-text="r.label"></div>
                                <div class="rpc-role-meta">
                                    <span class="rpc-badge rpc-bz" x-text="r.permission_count + ' perms'"></span>
                                    <span class="rpc-badge rpc-bm" x-text="r.user_count + ' users'"></span>
                                </div>
                            </div>
                            <button class="rpc-role-dup" @click.stop="openDuplicateModal(r)" title="Duplicate role">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            <div class="rpc-main" x-show="selectedRoleId" x-cloak>
                <div x-show="loading" style="display:flex;align-items:center;justify-content:center;padding:3rem 1rem;opacity:.7">
                    <span style="font-size:.875rem;color:#5f7476">Loading permissions...</span>
                </div>
                <div x-show="!loading">
                <div class="rpc-stats">
                    <div class="rpc-stat">
                        <div class="rpc-stat-l">Selected Role</div>
                        <div class="rpc-stat-v" x-text="selectedRoleLabel"></div>
                    </div>
                    <div class="rpc-stat">
                        <div class="rpc-stat-l">Allowed Permissions</div>
                        <div class="rpc-stat-v" x-text="totalGranted"></div>
                    </div>
                    <div class="rpc-stat">
                        <div class="rpc-stat-l">Sensitive Grants</div>
                        <div class="rpc-stat-v" x-text="totalSensitive"></div>
                    </div>
                    <div class="rpc-stat">
                        <div class="rpc-stat-l">Unsaved Changes</div>
                        <div class="rpc-stat-v" x-text="dirtyCount"></div>
                    </div>
                </div>
                <div class="rpc-copybar" x-show="copySource" x-transition x-cloak>
                    <div class="rpc-copybar-text">Copying permissions from <span x-text="copySource"></span> &mdash; modify as needed, then save.</div>
                    <button class="rpc-btn rpc-btn-ghost rpc-btn-sm" @click="copySource=null">Dismiss</button>
                </div>
                <div class="rpc-card">
                    <div class="rpc-card-head">
                        <h2>Permissions</h2>
                        <div class="rpc-tabs">
                            <button class="rpc-tab" :class="{'on': activeTab==='module'}" @click="activeTab='module'"><span>Module</span></button>
                            <button class="rpc-tab" :class="{'on': activeTab==='report'}" @click="activeTab='report'"><span>Report</span></button>
                        </div>
                    </div>
                    <div class="rpc-filters">
                        <div class="rpc-filter-search">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                            <input type="text" placeholder="Search permissions..." x-model="permSearch" @input="applyFilters()">
                        </div>
                        <select class="rpc-filter-select" x-model="moduleFilter" @change="applyFilters()">
                            <option value="">All modules</option>
                            <template x-for="m in allModuleLabels" :key="m">
                                <option :value="m" x-text="m"></option>
                            </template>
                        </select>
                        <button class="rpc-filter-changed" :class="{'on': changedOnly}" @click="changedOnly=!changedOnly; applyFilters()">Changed Only</button>
                    </div>
                    <div x-show="activeTab==='module'">
                        <div class="rpc-expand-row">
                            <button class="rpc-expand-btn" @click="expandAll()" x-show="!allExpanded">Expand All</button>
                            <button class="rpc-expand-btn" @click="collapseAll()" x-show="allExpanded">Collapse All</button>
                        </div>
                        <div class="rpc-modules">
                            <template x-for="g in visibleGroups" :key="g.name">
                                <template x-for="mod in g.modules" :key="mod.key">
                                    <div class="rpc-module" x-show="mod.visible" :class="{'changed': mod.isChanged}">
                                        <div class="rpc-mod-head">
                                            <div class="rpc-mod-ic" x-text="mod.icon"></div>
                                            <span class="rpc-mod-title" x-text="mod.label"></span>
                                            <span class="rpc-mod-changed" x-show="mod.isChanged">&middot; changed</span>
                                            <div class="rpc-mod-actions">
                                                <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="allowView(mod)" title="Allow View">Allow View</button>
                                                <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="allowAll(mod)" title="Allow All">Allow All</button>
                                                <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="clearAll(mod)" title="Clear">Clear</button>
                                            </div>
                                        </div>
                                        <table class="rpc-matrix">
                                            <thead>
                                                <tr>
                                                    <th>Feature</th>
                                                    <th>View</th><th>Create</th><th>Edit</th><th>Delete</th>
                                                    <th>Submit</th><th>Approve</th><th>Post</th><th>Reverse</th>
                                                    <th>Export</th><th>Configure</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td x-text="mod.label"></td>
                                                    <template x-for="col in MATRIX_COLS" :key="col.key">
                                                        <td>
                                                            <label class="check" :class="{'danger': col.danger}">
                                                                <input type="checkbox" :data-perm="mod.key+'.'+col.key" :checked="mod.isGranted(col.key)" @change="onCellChange(mod, col.key, $event.target.checked, $event.target)">
                                                                <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                                            </label>
                                                        </td>
                                                    </template>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                    <div x-show="activeTab==='report'" x-cloak>
                        <div class="rpc-expand-row">
                            <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="allowAllReports()" title="Allow All Reports">Allow All</button>
                            <button class="rpc-btn rpc-btn-ghost rpc-btn-xs" @click="clearAllReports()" title="Clear All Reports">Clear</button>
                        </div>
                        <div class="rpc-modules">
                            <div class="rpc-module">
                                <table class="rpc-matrix">
                                    <thead>
                                        <tr>
                                            <th>Report</th>
                                            <th>View</th><th>Export</th><th>Print</th><th>Email</th><th>Schedule</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="rpt in visibleReports" :key="rpt.key">
                                            <tr>
                                                <td x-text="rpt.label"></td>
                                                <template x-for="col in REPORT_COLS" :key="col.key">
                                                    <td>
                                                        <label class="check">
                                                            <input type="checkbox" :data-perm="rpt.key+'.'+col.key" :checked="rpt.isGranted(col.key)" @change="onReportCellChange(rpt, col.key, $event.target.checked, $event.target)">
                                                            <span class="box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                                                        </label>
                                                    </td>
                                                </template>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rpc-savebar" x-show="selectedRoleId" x-transition x-cloak>
                    <span class="rpc-savebar-ct" x-text="barMessage"></span>
                    <div class="rpc-savebar-r">
                        <button class="rpc-btn rpc-btn-ghost" @click="discard()" :disabled="barState !== 'dirty'">Discard</button>
                        <button class="rpc-btn rpc-btn-cta" @click="save()" :disabled="barState !== 'dirty'" x-text="saving ? 'Saving...' : 'Save Permissions'"></button>
                    </div>
                </div>
                </div>
            </div>
            <div class="rpc-empty" x-show="!selectedRoleId">
                <div class="rpc-empty-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <h3 class="rpc-empty-t">Select a Role</h3>
                <p class="rpc-empty-d">Choose a role from the left panel to manage its permissions.</p>
            </div>
        </div>
    </div>
    <div class="rpc-modal-bg" x-show="showModal" x-transition.opacity @click.self="showModal=false" style="display:none">
        <div class="rpc-modal" @click.stop>
            <h3 x-text="modalMode === 'duplicate' ? 'Duplicate Role' : 'Create Role'"></h3>
            <div class="rpc-modal-field">
                <label>Role Name *</label>
                <input type="text" x-model="newRoleName" placeholder="e.g. inventory_manager" @keydown.enter="createRole()">
                <div class="hint">Use lowercase with underscores. This becomes the system identifier.</div>
            </div>
            <div class="rpc-modal-field">
                <label>Description</label>
                <textarea x-model="newRoleDescription" placeholder="Optional description for this role..."></textarea>
            </div>
            <div class="rpc-modal-field">
                <label>Copy Permissions From</label>
                <select class="rpc-filter-select" x-model="newRoleSource" style="width:100%;min-width:0">
                    <option value="">Start blank</option>
                    <template x-for="r in roles" :key="r.id">
                        <option :value="r.id" x-text="r.label + ' (' + r.permission_count + ')'"></option>
                    </template>
                </select>
            </div>
            <div class="rpc-modal-src" x-show="newRoleSource">
                <strong x-text="getSourceName()"></strong> &mdash; all permissions will be copied. You can modify them after creation.
            </div>
            <div class="rpc-modal-foot">
                <button class="rpc-btn rpc-btn-ghost" @click="showModal=false">Cancel</button>
                <button class="rpc-btn rpc-btn-cta" @click="createRole()" :disabled="!newRoleName.trim()">Create &amp; Edit Permissions</button>
            </div>
        </div>
    </div>
    <div class="rpc-modal-bg" x-show="showLockoutModal" x-transition.opacity @click.self="showLockoutModal=false" style="display:none">
        <div class="rpc-modal" @click.stop>
            <h3>Self-Lockout Warning</h3>
            <p style="font-size:.857rem;color:#374151;line-height:1.6">Removing these permissions would lock you out of role management. At least one of your roles must retain roles.view, roles.create, or roles.edit.</p>
            <div class="rpc-modal-foot">
                <button class="rpc-btn rpc-btn-cta" @click="showLockoutModal=false">I Understand</button>
            </div>
        </div>
    </div>
    <div class="rpc-modal-bg" x-show="showConflictModal" x-transition.opacity @click.self="showConflictModal=false" style="display:none">
        <div class="rpc-modal" @click.stop>
            <h3>Permissions Changed</h3>
            <p style="font-size:.857rem;color:#374151;line-height:1.6">This role's permissions were modified by someone else since you loaded them. Your changes could overwrite theirs.</p>
            <div class="rpc-modal-foot">
                <button class="rpc-btn rpc-btn-ghost" @click="showConflictModal=false">Discard My Changes</button>
                <button class="rpc-btn rpc-btn-cta" @click="forceSave()">Save Anyway</button>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
