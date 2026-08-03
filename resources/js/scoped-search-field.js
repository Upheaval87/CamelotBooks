document.addEventListener('alpine:init', () => {
    Alpine.data('scopedSearchField', (config) => ({
        name: config.name,
        entity: config.entity || '',
        searchUrl: config.searchUrl || null,
        mode: config.mode || 'server',
        items: config.items || [],
        secondary: config.secondary || [],
        valueKey: config.valueKey || 'id',
        labelKey: config.labelKey || 'label',
        onSelectCallback: config.onSelect || null,
        required: config.required || false,
        disabled: config.disabled || false,

        query: config.label || '',
        selectedId: config.value || '',
        selectedLabel: config.label || '',
        results: [],
        open: false,
        loading: false,
        highlightIndex: -1,
        _lastQuery: null,
        fieldId: null,

        init() {
            this.fieldId = 'ssf-' + Math.random().toString(36).slice(2, 10) + '-' + Date.now().toString(36);
            if (this.$el) {
                this.$el.setAttribute('data-scoped-search-field', this.fieldId);
            }

            this.$watch('disabled', (val) => {
                if (val) this.open = false;
            });

            window.addEventListener('global-search-selected', (e) => {
                const detail = (e && e.detail) || {};
                if (!detail.entity || detail.entity !== this.entity) return;
                const idMatches = detail.fieldId != null && detail.fieldId === this.fieldId;
                const elMatches = detail.field != null && detail.field === this.$el;
                if (!idMatches && !elMatches) return;
                const item = detail.item || {};
                if (item.id === undefined || item.id === null || item.id === '') return;
                this.select(item);
            });
        },

        filter() {
            if (this.disabled) return;
            const q = this.query.trim();
            if (q === this._lastQuery) return;
            this._lastQuery = q;
            this.highlightIndex = -1;

            if (this.mode === 'client') {
                this.filterClient(q);
            } else {
                this.filterServer(q);
            }
        },

        filterClient(q) {
            if (q === '') {
                this.results = [];
                this.open = false;
                return;
            }

            const needle = q.toLowerCase();
            this.results = this.items.filter(item => {
                const hay = [
                    item[this.labelKey],
                    item.subtitle,
                    ...this.secondary.map(k => item[k]),
                ].filter(v => v !== null && v !== undefined).join(' ').toLowerCase();
                return hay.includes(needle);
            });

            this.open = true;
        },

        async filterServer(q) {
            if (q === '' || !this.searchUrl) {
                this.results = [];
                this.open = false;
                return;
            }

            this.loading = true;
            try {
                const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q));
                if (!r.ok) throw new Error('search failed');
                this.results = await r.json();
                this.open = true;

                if (this.results.length === 1) {
                    const item = this.results[0];
                    const barcode = item.barcode ? String(item.barcode).toLowerCase() : '';
                    const sku = item.sku ? String(item.sku).toLowerCase() : '';
                    if (q.toLowerCase() === barcode || q.toLowerCase() === sku) {
                        this.select(item);
                        return;
                    }
                }
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        moveHighlight(dir) {
            if (!this.open || this.results.length === 0) return;
            this.highlightIndex += dir;
            if (this.highlightIndex < 0) this.highlightIndex = this.results.length - 1;
            if (this.highlightIndex >= this.results.length) this.highlightIndex = 0;
        },

        confirmHighlight() {
            if (this.highlightIndex >= 0 && this.highlightIndex < this.results.length) {
                this.select(this.results[this.highlightIndex]);
            }
        },

        select(item) {
            this.selectedId = item[this.valueKey];
            this.selectedLabel = item[this.labelKey];
            this.query = this.selectedLabel;
            this.open = false;
            this.highlightIndex = -1;
            this._lastQuery = null;

            if (this.onSelectCallback && typeof window[this.onSelectCallback] === 'function') {
                window[this.onSelectCallback](this.selectedId, item);
            }

            this.$el.dispatchEvent(new CustomEvent('item-selected', {
                detail: { id: this.selectedId, item: item },
                bubbles: true,
            }));
        },

        clear() {
            this.selectedId = '';
            this.selectedLabel = '';
            this.query = '';
            this.results = [];
            this.open = false;
            this._lastQuery = null;
        },

        rowSubtitle(item) {
            if (item.subtitle) return item.subtitle;
            return (this.secondary || [])
                .map(k => item[k])
                .filter(v => v !== null && v !== undefined && v !== '')
                .join(' · ');
        },

        openGlobalSearch() {
            window.dispatchEvent(new CustomEvent('open-global-search', {
                detail: { query: this.query, entity: this.entity, field: this.$el, fieldId: this.fieldId },
            }));
        },
    }));

    function escapeAttr(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /**
     * Build the markup for a server-mode scoped search field, used when a
     * picker must be injected into the DOM dynamically (e.g. line-item rows).
     * Mirrors the <x-scoped-search-field> Blade component.
     */
    window.scopedSearchFieldHtml = function (config) {
        const cfg = Object.assign({ mode: 'server', valueKey: 'id', labelKey: 'label', secondary: [], required: false }, config || {});
        const xData = 'scopedSearchField(' + JSON.stringify(cfg) + ')';
        const placeholder = escapeAttr(cfg.placeholder || 'Search...');
        const required = cfg.required ? 'required' : '';

        return `
<div x-data="${escapeAttr(xData)}" class="relative">
    <input type="hidden" name="${escapeAttr(cfg.name)}" :value="selectedId" ${required} />
    <div class="scoped-search-field">
        <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        <input type="text" x-model="query" @input.debounce.200ms="filter()" @focus="if (query.length > 0) open = true" @keydown.down.prevent="moveHighlight(1)" @keydown.up.prevent="moveHighlight(-1)" @keydown.enter.prevent="confirmHighlight()" @keydown.escape="open = false" @keydown.tab="open = false" placeholder="${placeholder}" autocomplete="off" />
        <span class="scoped-search-divider" aria-hidden="true"></span>
        <button type="button" class="scoped-search-open" title="Search" @click="openGlobalSearch()">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </div>
    <div x-show="open" x-cloak class="scoped-search-dropdown">
        <template x-for="(item, idx) in results" :key="item[valueKey]">
            <div @click="select(item)" @mouseenter="highlightIndex = parseInt(idx)" class="scoped-search-option" :class="parseInt(idx) === highlightIndex ? 'is-highlighted' : ''">
                <span class="scoped-search-option-label" x-text="item[labelKey]"></span>
                <span class="scoped-search-option-sub" x-show="rowSubtitle(item)" x-text="rowSubtitle(item)"></span>
            </div>
        </template>
        <div x-show="loading" class="scoped-search-empty">Searching&hellip;</div>
        <div x-show="!loading && results.length === 0" class="scoped-search-empty">No matches found</div>
    </div>
</div>`;
    };
});
