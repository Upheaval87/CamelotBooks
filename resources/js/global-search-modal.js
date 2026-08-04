const GLOBAL_SEARCH_ICONS = {
    box: 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
    chart: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    truck: 'M3 7h14v9H3V7zm0 0V5a2 2 0 012-2h8a2 2 0 012 2v2M7 16a2 2 0 11-4 0 2 2 0 014 0zm8 0a2 2 0 11-4 0 2 2 0 014 0zM14 16h2a2 2 0 002-2v-2h4',
    building: 'M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h2m-2 4h2m-2 4h2m4-8h2m-2 4h2m-2 4h2',
    tag: 'M20.59 13.41L11 3.83A2 2 0 009.59 3H4a1 1 0 00-1 1v5.59a2 2 0 00.59 1.41l9.59 9.59a2 2 0 002.83 0l4.58-4.58a2 2 0 000-2.83zM7 7h.01',
    users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
    bank: 'M2 8l10-5 10 5H2zM3 8v10m5-10v10m5-10v10m5-10v10M2 21h20',
    archive: 'M22 7H2v14a2 2 0 002 2h16a2 2 0 002-2V7zM2 7l3-4h14l3 4M12 12v6m0-6h.01',
    currency: 'M2 7h20v10H2V7zm5 5a2 2 0 114 0 2 2 0 01-4 0zm11 0h.01',
    calendar: 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
};

const GLOBAL_SEARCH_ENTITY_LABELS = {
    product: 'Products',
    account: 'Accounts',
    customer: 'Customers',
    vendor: 'Vendors',
    branch: 'Branches',
    'cost-center': 'Cost Centers',
    employee: 'Employees',
    user: 'Users',
    'bank-account': 'Bank Accounts',
    asset: 'Fixed Assets',
    'asset-category': 'Asset Categories',
    'payroll-run': 'Payroll Runs',
    'fiscal-year': 'Fiscal Years',
    invoice: 'Invoices',
    bill: 'Bills',
    'sales-receipt': 'Sales Receipts',
    quotation: 'Quotations',
    'credit-note': 'Credit Notes',
    'vendor-credit': 'Vendor Credits',
};

document.addEventListener('alpine:init', () => {
    Alpine.data('globalSearchModal', (config) => ({
        searchUrl: config.searchUrl || null,
        open: false,
        query: '',
        entity: '',
        groups: [],
        loading: false,
        searched: false,
        error: false,
        highlightIndex: -1,
        _lastQuery: null,
        _seq: 0,
        _fieldRef: null,
        _fieldId: null,

        init() {
            window.addEventListener('open-global-search', (e) => {
                const detail = (e && e.detail) || {};
                this.openModal(detail.query || '', detail.entity || '', detail.field || null, detail.fieldId || null);
            });

            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    this.openModal();
                    return;
                }
                if (e.key === 'Escape' && this.open) {
                    this.close();
                }
            });
        },

        get flatRows() {
            const rows = [];
            this.groups.forEach((group) => {
                (group.results || []).forEach((r) => {
                    rows.push(Object.assign({}, r, { groupKey: group.key, groupLabel: group.label }));
                });
            });
            return rows;
        },

        flatIndexOf(gi, ri) {
            let idx = 0;
            for (let g = 0; g < gi; g++) {
                idx += (this.groups[g].results || []).length;
            }
            return idx + ri;
        },

        openModal(initialQuery = '', entity = '', fieldRef = null, fieldId = null) {
            this.open = true;
            this.query = initialQuery || '';
            this.entity = entity || '';
            this._fieldRef = fieldRef || null;
            this._fieldId = fieldId || null;
            this.groups = [];
            this.highlightIndex = -1;
            this.error = false;
            this.searched = false;
            this._lastQuery = null;
            this.$nextTick(() => {
                if (this.$refs.input) {
                    this.$refs.input.focus();
                    this.$refs.input.select();
                }
            });
            this.doSearch();
        },

        close() {
            this.open = false;
            this.highlightIndex = -1;
        },

        async doSearch() {
            const q = this.query.trim();
            if (q === this._lastQuery) return;
            this._lastQuery = q;
            const seq = ++this._seq;

            if (q === '') {
                this.groups = [];
                this.searched = true;
                this.loading = false;
                this.highlightIndex = -1;
                return;
            }

            this.loading = true;
            this.error = false;

            try {
                let url = this.searchUrl + '?q=' + encodeURIComponent(q);
                if (this.entity) {
                    url += '&entity=' + encodeURIComponent(this.entity);
                }
                const res = await fetch(url);
                if (!res.ok) throw new Error('search failed');
                const groups = await res.json();
                if (seq !== this._seq) return;
                this.groups = groups;
                this.searched = true;
                this.highlightIndex = -1;
            } catch (err) {
                if (seq !== this._seq) return;
                this.error = true;
                this.groups = [];
            } finally {
                if (seq === this._seq) this.loading = false;
            }
        },

        clearScope() {
            this.entity = '';
            this._lastQuery = null;
            this.doSearch();
        },

        entityLabel() {
            return GLOBAL_SEARCH_ENTITY_LABELS[this.entity] || this.entity;
        },

        moveHighlight(dir) {
            const total = this.flatRows.length;
            if (total === 0) return;
            this.highlightIndex += dir;
            if (this.highlightIndex < 0) this.highlightIndex = total - 1;
            if (this.highlightIndex >= total) this.highlightIndex = 0;

            this.$nextTick(() => {
                const list = this.$refs.list;
                const el = list && list.querySelector('[data-i="' + this.highlightIndex + '"]');
                if (el) el.scrollIntoView({ block: 'nearest' });
            });
        },

        confirmHighlight() {
            this.selectResult(this.flatRows[this.highlightIndex]);
        },

        selectResult(row) {
            if (!row) return;

            // Opened from a scoped picker field → fill the field, don't navigate.
            if (this._fieldRef) {
                window.dispatchEvent(new CustomEvent('global-search-selected', {
                    detail: {
                        entity: this.entity,
                        field: this._fieldRef,
                        fieldId: this._fieldId,
                        item: row,
                    },
                }));
                this.close();
                return;
            }

            // List-page scope or topbar / Ctrl+K → jump to the record.
            if (row.url) {
                window.location.href = row.url;
            }
        },

        iconPath(key) {
            return GLOBAL_SEARCH_ICONS[key] || GLOBAL_SEARCH_ICONS.box;
        },
    }));
});
