document.addEventListener('alpine:init', () => {
    Alpine.data('permissionsConsole', () => ({
        MATRIX_COLS: [
            {key:'view',label:'View',danger:false},
            {key:'create',label:'Create',danger:false},
            {key:'edit',label:'Edit',danger:false},
            {key:'delete',label:'Delete',danger:true},
            {key:'submit',label:'Submit',danger:false},
            {key:'approve',label:'Approve',danger:false},
            {key:'post',label:'Post',danger:false},
            {key:'reverse',label:'Reverse',danger:false},
            {key:'export',label:'Export',danger:false},
            {key:'configure',label:'Configure',danger:true},
        ],
        REPORT_COLS: [
            {key:'view',label:'View',danger:false},
            {key:'export',label:'Export',danger:false},
            {key:'print',label:'Print',danger:false},
            {key:'email',label:'Email',danger:false},
            {key:'schedule',label:'Schedule',danger:false},
        ],
        roles: [],
        moduleGroups: {},
        selectedRoleId: null,
        selectedRoleLabel: '',
        selectedRoleActive: null,
        selectedUpdatedAt: null,
        originalPerms: [],
        modules: [],
        reports: [],
        activeTab: 'module',
        roleSearch: '',
        permSearch: '',
        moduleFilter: '',
        changedOnly: false,
        allExpanded: false,
        dirty: false,
        dirtyCount: 0,
        barState: 'clean',
        saving: false,
        loading: false,
        showModal: false,
        modalMode: 'create',
        showLockoutModal: false,
        showConflictModal: false,
        newRoleName: '',
        newRoleDescription: '',
        newRoleSource: '',
        copySource: null,
        get filteredRoles() {
            const q = this.roleSearch.toLowerCase();
            return this.roles.filter(r => !q || r.label.toLowerCase().includes(q));
        },
        get allModuleLabels() {
            const set = new Set();
            this.modules.forEach(m => set.add(m.label));
            return [...set].sort();
        },
        get visibleGroups() {
            const groups = [];
            const groupMap = {};
            this.modules.forEach(m => {
                if (!m.visible) return;
                const gName = m.group || 'Other';
                if (!groupMap[gName]) { groupMap[gName] = {name: gName, modules: []}; groups.push(groupMap[gName]); }
                groupMap[gName].modules.push(m);
            });
            return groups;
        },
        get visibleReports() { return this.reports.filter(r => r.visible); },
        get totalGranted() {
            let c = 0;
            this.modules.forEach(m => m.actions.forEach(a => { if (a.granted) c++; }));
            return c;
        },
        get totalSensitive() {
            let c = 0;
            this.modules.forEach(m => m.actions.forEach(a => { if (a.granted && a.sensitive) c++; }));
            return c;
        },
        get barMessage() {
            if (this.barState === 'dirty') {
                return this.dirtyCount + ' unsaved permission change' + (this.dirtyCount !== 1 ? 's' : '') + ' for ' + this.selectedRoleLabel;
            } else if (this.barState === 'saved') {
                return '\u2713 Permissions saved for ' + this.selectedRoleLabel;
            } else {
                return this.selectedRoleId ? 'No unsaved changes for ' + this.selectedRoleLabel : '';
            }
        },
        async init() {
            const el = document.getElementById('rpc-data');
            if (el) {
                try {
                    const data = JSON.parse(el.textContent);
                    this.roles = data.roles || [];
                    this.moduleGroups = data.moduleGroups || {};
                } catch(e) { console.error('rpc-data parse', e); }
            }
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty) { e.preventDefault(); e.returnValue = ''; }
            });
        },
        async selectRole(id) {
            if (this.dirty) {
                const ok = window.CB && window.CB.confirm
                    ? await window.CB.confirm({type:'danger', title:'Unsaved changes', body:'You have unsaved changes. Discard them to switch roles?', confirmLabel:'Discard'})
                    : confirm('You have unsaved changes. Discard them to switch roles?');
                if (!ok) return;
            }
            this.loading = true;
            try {
                const res = await fetch('/admin/permissions/' + id + '/permissions', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (!data.ok) return;
                this.selectedRoleId = id;
                this.selectedRoleLabel = data.role.label;
                this.selectedRoleActive = data.role.is_active;
                this.selectedUpdatedAt = data.role.updated_at;
                this.originalPerms = [...data.granted];
                this.dirty = false;
                this.dirtyCount = 0;
                this.barState = 'clean';
                this.copySource = null;
                this.buildMatrix(data.matrix);
                this.$nextTick(() => { this.syncCheckboxes(); this.loading = false; });
            } catch(e) {
                console.error('selectRole', e);
                this.loading = false;
            }
        },
        buildMatrix(matrix) {
            const matrixKeys = new Set(this.MATRIX_COLS.map(c => c.key));
            this.modules = matrix.modules.map(m => {
                const existingMap = {};
                m.actions.forEach(a => { existingMap[a.action] = a; });
                const paddedActions = this.MATRIX_COLS.map(col => {
                    if (existingMap[col.key]) {
                        return { ...existingMap[col.key], visible: true };
                    }
                    return { module: m.key, action: col.key, permission: m.key + '.' + col.key, granted: false, sensitive: false, visible: true };
                });
                m.actions.forEach(a => {
                    if (!matrixKeys.has(a.action)) {
                        paddedActions.push({ ...a, visible: false });
                    }
                });
                const mod = { ...m, visible: true, actions: paddedActions };
                mod.hasAction = (key) => mod.actions.some(a => a.action === key);
                mod.isGranted = (key) => { const a = mod.actions.find(x => x.action === key); return a ? a.granted : false; };
                mod.isSensitive = (key) => { const a = mod.actions.find(x => x.action === key); return a ? a.sensitive : false; };
                mod.allGranted = mod.actions.length > 0 && mod.actions.every(a => a.granted);
                mod.visible = true;
                return mod;
            });
            this.reports = (matrix.reports || []).map(r => {
                const existingMap = {};
                r.actions.forEach(a => { existingMap[a.action] = a; });
                const paddedActions = this.REPORT_COLS.map(col => {
                    if (existingMap[col.key]) {
                        return { ...existingMap[col.key], visible: true };
                    }
                    return { module: 'reports.' + r.key, action: col.key, permission: 'reports.' + r.key + '.' + col.key, granted: false, sensitive: false, visible: true };
                });
                const rpt = { ...r, visible: true, actions: paddedActions };
                rpt.hasAction = (key) => rpt.actions.some(a => a.action === key);
                rpt.isGranted = (key) => { const a = rpt.actions.find(x => x.action === key); return a ? a.granted : false; };
                rpt.allGranted = rpt.actions.length > 0 && rpt.actions.every(a => a.granted);
                rpt.visible = true;
                return rpt;
            });
            this.applyFilters();
        },
        onCellChange(mod, colKey, checked, input) {
            const a = mod.actions.find(x => x.action === colKey);
            if (!a) return;
            a.granted = checked;
            const lbl = input.closest('.check');
            if (lbl) lbl.classList.toggle('is-checked', checked);
            if (!checked && a.sensitive && this.checkSelfLockout()) {
                a.granted = true;
                input.checked = true;
                if (lbl) lbl.classList.add('is-checked');
                this.showLockoutModal = true;
                return;
            }
            this.recalcDirty();
        },
        onReportCellChange(rpt, colKey, checked, input) {
            const a = rpt.actions.find(x => x.action === colKey);
            if (!a) return;
            a.granted = checked;
            const lbl = input.closest('.check');
            if (lbl) lbl.classList.toggle('is-checked', checked);
            this.recalcDirty();
        },
        syncCheckboxes() {
            const permMap = {};
            this.modules.forEach(m => m.actions.forEach(a => { permMap[m.key + '.' + a.action] = a.granted; }));
            this.reports.forEach(r => r.actions.forEach(a => { permMap[r.key + '.' + a.action] = a.granted; }));
            document.querySelectorAll('.check input[data-perm]').forEach(input => {
                const perm = input.getAttribute('data-perm');
                if (!perm) return;
                const granted = permMap[perm] || false;
                input.checked = granted;
                input.closest('.check').classList.toggle('is-checked', granted);
            });
        },
        allowView(mod) {
            mod.actions.forEach(a => { a.granted = a.action === 'view'; });
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        allowAll(mod) {
            mod.actions.forEach(a => { a.granted = true; });
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        clearAll(mod) {
            mod.actions.forEach(a => {
                const wasGrant = a.granted;
                a.granted = false;
                if (wasGrant && a.sensitive && this.checkSelfLockout()) {
                    a.granted = true;
                    this.showLockoutModal = true;
                }
            });
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        allowAllReports() {
            this.reports.forEach(r => r.actions.forEach(a => { a.granted = true; }));
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        clearAllReports() {
            this.reports.forEach(r => r.actions.forEach(a => { a.granted = false; }));
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        applyFilters() {
            const q = this.permSearch.toLowerCase();
            this.modules.forEach(m => {
                const modMatch = !q || m.label.toLowerCase().includes(q) || m.key.toLowerCase().includes(q);
                let anyActionVisible = false;
                m.actions.forEach(a => {
                    let show = modMatch && (!q || a.action.includes(q) || a.permission.includes(q));
                    if (this.moduleFilter && m.label !== this.moduleFilter) show = false;
                    if (this.changedOnly) {
                        const orig = this.originalPerms.includes(a.permission);
                        if (a.granted === orig) show = false;
                    }
                    a.visible = show;
                    if (show) anyActionVisible = true;
                });
                m.visible = anyActionVisible;
            });
            this.reports.forEach(r => {
                let show = !q || r.label.toLowerCase().includes(q);
                if (this.changedOnly) {
                    const anyChanged = r.actions.some(a => {
                        const orig = this.originalPerms.includes(a.permission);
                        return a.granted !== orig;
                    });
                    if (!anyChanged) show = false;
                }
                r.visible = show && r.actions.some(a => a.visible);
            });
        },
        expandAll() {
            this.allExpanded = true;
            this.modules.forEach(m => m.visible = true);
        },
        collapseAll() {
            this.allExpanded = false;
            this.modules.forEach(m => m.visible = false);
        },
        checkSelfLockout() {
            const has = ['roles.view','roles.create','roles.edit'].some(p => this.currentPermsSet().has(p));
            if (has) return false;
            const mgrRoles = this.roles.filter(r => r.id !== this.selectedRoleId && r.is_active && r.permission_count > 0);
            return mgrRoles.length === 0;
        },
        currentPermsSet() {
            const set = new Set();
            this.modules.forEach(m => m.actions.forEach(a => { if (a.granted) set.add(a.permission); }));
            this.reports.forEach(r => r.actions.forEach(a => { if (a.granted) set.add(a.permission); }));
            return set;
        },
        recalcDirty() {
            const orig = new Set(this.originalPerms);
            const curr = this.currentPermsSet();
            let count = 0;
            orig.forEach(p => { if (!curr.has(p)) count++; });
            curr.forEach(p => { if (!orig.has(p)) count++; });
            this.dirtyCount = count;
            this.dirty = count > 0;
            this.barState = count > 0 ? 'dirty' : 'clean';
        },
        discard() {
            this.modules.forEach(m => {
                m.actions.forEach(a => { a.granted = this.originalPerms.includes(a.permission); });
            });
            this.reports.forEach(r => {
                r.actions.forEach(a => { a.granted = this.originalPerms.includes(a.permission); });
            });
            this.recalcDirty();
            this.$nextTick(() => this.syncCheckboxes());
        },
        async save(force = false) {
            if (this.saving) return;
            this.saving = true;
            try {
                const granted = [...this.currentPermsSet()];
                const res = await fetch('/admin/permissions/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        role_id: this.selectedRoleId,
                        permissions: granted,
                        expected_updated_at: this.selectedUpdatedAt,
                        force: force
                    })
                });
                let data;
                try { data = await res.json(); } catch(_) { data = {}; }
                if (res.status === 409) {
                    this.showConflictModal = true;
                    return;
                }
                if (res.status === 422 && data.error === 'lockout') {
                    this.showLockoutModal = true;
                    return;
                }
                if (res.ok && data.ok) {
                    this.originalPerms = [...granted];
                    this.dirty = false;
                    this.dirtyCount = 0;
                    this.barState = 'saved';
                    this.selectedUpdatedAt = data.updated_at || this.selectedUpdatedAt;
                    this.refreshRoles();
                    window.CB && window.CB.toast && window.CB.toast('success', 'Permissions saved', granted.length + ' permissions updated for ' + this.selectedRoleLabel);
                } else {
                    const msg = (data && data.message) || 'Server returned ' + res.status;
                    console.error('save failed', res.status, data);
                    window.CB && window.CB.toast && window.CB.toast('error', 'Save failed', msg);
                }
            } catch(e) {
                console.error('save', e);
                window.CB && window.CB.toast && window.CB.toast('error', 'Save failed', 'An unexpected error occurred.');
            } finally {
                this.saving = false;
            }
        },
        forceSave() {
            this.showConflictModal = false;
            this.save(true);
        },
        async refreshRoles() {
            try {
                const res = await fetch('/admin/permissions', {
                    headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const dataScript = doc.querySelector('#rpc-data');
                if (dataScript) {
                    const data = JSON.parse(dataScript.textContent);
                    this.roles = data.roles || [];
                }
            } catch(e) { console.error('refreshRoles', e); }
        },
        openCreateModal() {
            this.modalMode = 'create';
            this.newRoleName = '';
            this.newRoleDescription = '';
            this.newRoleSource = '';
            this.showModal = true;
        },
        openDuplicateModal(r) {
            this.modalMode = 'duplicate';
            this.newRoleName = r.label;
            this.newRoleDescription = '';
            this.newRoleSource = r.id;
            this.showModal = true;
        },
        getSourceName() {
            if (!this.newRoleSource) return '';
            const r = this.roles.find(x => x.id == this.newRoleSource);
            return r ? r.label : '';
        },
        async createRole() {
            if (!this.newRoleName.trim()) return;
            try {
                const res = await fetch('/admin/permissions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: this.newRoleName.trim(),
                        description: this.newRoleDescription.trim() || null,
                        source_role_id: this.newRoleSource || null
                    })
                });
                const data = await res.json();
                if (data.ok) {
                    this.showModal = false;
                    this.newRoleName = '';
                    this.newRoleDescription = '';
                    this.newRoleSource = '';
                    await this.refreshRoles();
                    this.selectRole(data.role_id);
                    window.CB && window.CB.toast && window.CB.toast('success', 'Role created', data.name);
                }
            } catch(e) { console.error('createRole', e); }
        },
    }));
});