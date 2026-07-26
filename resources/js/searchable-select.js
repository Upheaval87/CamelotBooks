document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (config) => ({
        name: config.name,
        items: config.items || [],
        valueKey: config.valueKey || 'id',
        labelKey: config.labelKey || 'name',
        searchKeys: config.searchKeys || ['name'],
        showFields: config.showFields || [],
        mode: config.mode || 'client',
        searchUrl: config.searchUrl || null,
        onSelectCallback: config.onSelectCallback || null,
        enableAdvancedSearch: config.enableAdvancedSearch || false,
        advancedSearchName: config.advancedSearchName || null,
        barcodeAutoSelect: config.barcodeAutoSelect !== false,

        query: config.preloadLabel || '',
        selectedId: config.preload || '',
        selectedLabel: config.preloadLabel || '',
        results: [],
        open: false,
        highlightIndex: -1,
        loading: false,
        _lastQuery: '',

        init() {
            if (this.selectedId && this.selectedLabel) {
                this.query = this.selectedLabel;
            }

            this.$watch('open', (val) => {
                if (!val) return;
                const handler = (e) => {
                    if (e.detail && e.detail.targetName === this.name) {
                        this.select(e.detail.item);
                    }
                };
                if (this.open) {
                    window.addEventListener('advanced-search-selected', handler);
                    this._advSearchHandler = handler;
                }
            });
            this.$watch('open', (val) => {
                if (!val && this._advSearchHandler) {
                    window.removeEventListener('advanced-search-selected', this._advSearchHandler);
                    this._advSearchHandler = null;
                }
            });
        },

        filter() {
            const q = this.query.toLowerCase().trim();
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

            this.results = this.items.filter(item => {
                return this.searchKeys.some(key => {
                    const val = item[key];
                    return val && String(val).toLowerCase().includes(q);
                });
            });

            this.open = true;

            if (this.barcodeAutoSelect && this.results.length === 1) {
                const item = this.results[0];
                const barcode = item.barcode ? String(item.barcode).toLowerCase() : '';
                const sku = item.sku ? String(item.sku).toLowerCase() : '';
                if (q === barcode || q === sku) {
                    this.select(item);
                    return;
                }
            }
        },

        async filterServer(q) {
            if (q.length < 1) {
                this.results = [];
                this.open = false;
                return;
            }
            this.loading = true;
            try {
                const r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q));
                this.results = await r.json();
                this.open = true;
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
            this._lastQuery = '';

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
            this._lastQuery = '';
        },

        openAdvancedSearch() {
            const modalName = this.advancedSearchName || this.name;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'advanced-search-' + modalName }));
        },
    }));
});
