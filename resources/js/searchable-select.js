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
        _portal: null,
        _scrollHandler: null,
        _clickOutsideHandler: null,
        _advSearchHandler: null,

        init() {
            if (this.selectedId && this.selectedLabel) {
                this.query = this.selectedLabel;
            }

            this._portal = document.createElement('div');
            this._portal.style.cssText = 'position:fixed;display:none;z-index:9999;max-height:240px;overflow-y:auto;background:white;border:1px solid #d1d5db;border-radius:0.375rem;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);';
            document.body.appendChild(this._portal);

            var self = this;
            this._portal.addEventListener('mousedown', function(e) {
                e.preventDefault();
            });
            this._portal.addEventListener('click', function(e) {
                var itemEl = e.target.closest('[data-ss-idx]');
                if (itemEl) {
                    var idx = parseInt(itemEl.getAttribute('data-ss-idx'));
                    if (idx >= 0 && idx < self.results.length) {
                        self.select(self.results[idx]);
                    }
                }
            });

            this.$watch('open', function(val) {
                if (val) {
                    self._renderPortal();
                    self._positionPortal();
                    self._scrollHandler = function() {
                        if (self.open) self._positionPortal();
                    };
                    window.addEventListener('scroll', self._scrollHandler, true);
                    window.addEventListener('resize', self._scrollHandler);
                    self._clickOutsideHandler = function(e) {
                        if (!self.$el.contains(e.target) && !self._portal.contains(e.target)) {
                            self.open = false;
                        }
                    };
                    setTimeout(function() {
                        document.addEventListener('mousedown', self._clickOutsideHandler);
                    }, 0);
                } else {
                    self._portal.style.display = 'none';
                    if (self._scrollHandler) {
                        window.removeEventListener('scroll', self._scrollHandler, true);
                        window.removeEventListener('resize', self._scrollHandler);
                        self._scrollHandler = null;
                    }
                    if (self._clickOutsideHandler) {
                        document.removeEventListener('mousedown', self._clickOutsideHandler);
                        self._clickOutsideHandler = null;
                    }
                }
            });

            this.$watch('highlightIndex', function() {
                self._updateHighlight();
            });

            this.$watch('results', function() {
                if (self.open) {
                    self._renderPortal();
                    self._positionPortal();
                }
            });

            var advName = this.advancedSearchName || this.name;
            this._advSearchHandler = function(e) {
                if (e.detail && e.detail.targetName === advName) {
                    self.select(e.detail.item);
                }
            };
            window.addEventListener('advanced-search-selected', this._advSearchHandler);
        },

        destroy() {
            if (this._portal && this._portal.parentNode) {
                this._portal.parentNode.removeChild(this._portal);
            }
            if (this._scrollHandler) {
                window.removeEventListener('scroll', this._scrollHandler, true);
                window.removeEventListener('resize', this._scrollHandler);
            }
            if (this._clickOutsideHandler) {
                document.removeEventListener('mousedown', this._clickOutsideHandler);
            }
            if (this._advSearchHandler) {
                window.removeEventListener('advanced-search-selected', this._advSearchHandler);
            }
        },

        _renderPortal() {
            if (!this.results.length) {
                this._portal.style.display = 'none';
                return;
            }
            var self = this;
            var html = '';
            this.results.forEach(function(item, idx) {
                var isHl = idx === self.highlightIndex;
                var bg = isHl ? 'background-color:#4f46e5;color:white;' : '';
                var sub = isHl ? 'color:#c7d2fe;' : 'color:#6b7280;';
                var label = self._esc(String(item[self.labelKey] || ''));
                var fields = '';
                self.showFields.forEach(function(f) {
                    var val = item[f];
                    if (val === null || val === undefined || val === '') return;
                    var display = String(val);
                    if (f === 'sales_price' || f === 'purchase_price' || f === 'unit_price') {
                        try { display = typeof formatMoney === 'function' ? formatMoney(parseFloat(val)) : parseFloat(val).toFixed(2); } catch(e) { display = parseFloat(val).toFixed(2); }
                    }
                    fields += '<span>' + self._esc(display) + '</span>';
                });
                html += '<div data-ss-idx="' + idx + '" style="padding:8px 12px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:14px;border-bottom:1px solid #f3f4f6;' + bg + '">' +
                    '<div style="display:flex;flex-direction:column;min-width:0;flex:1;">' +
                        '<span style="font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + label + '</span>' +
                        (fields ? '<div style="display:flex;gap:8px;font-size:12px;' + sub + '">' + fields + '</div>' : '') +
                    '</div>' +
                '</div>';
            });
            this._portal.innerHTML = html;
            this._portal.style.display = 'block';
        },

        _updateHighlight() {
            if (!this._portal) return;
            var items = this._portal.querySelectorAll('[data-ss-idx]');
            var self = this;
            items.forEach(function(el) {
                var idx = parseInt(el.getAttribute('data-ss-idx'));
                var isHl = idx === self.highlightIndex;
                el.style.backgroundColor = isHl ? '#4f46e5' : '';
                el.style.color = isHl ? 'white' : '';
                var sub = el.querySelector('div > div');
                if (sub && sub !== el.firstElementChild) {
                    sub.style.color = isHl ? '#c7d2fe' : '#6b7280';
                }
            });
        },

        _positionPortal() {
            if (!this.open || !this.results.length) {
                this._portal.style.display = 'none';
                return;
            }
            var trigger = this.$el.querySelector('input[type="text"]');
            if (!trigger) return;
            var rect = trigger.getBoundingClientRect();
            var vw = window.innerWidth;
            var vh = window.innerHeight;
            var dropH = Math.min(this.results.length * 48 + 8, 240);

            var top = rect.bottom + 2;
            if (top + dropH > vh && rect.top - dropH > 0) {
                top = rect.top - dropH - 2;
            }
            var left = rect.left;
            if (left + rect.width > vw) left = vw - rect.width - 8;
            if (left < 0) left = 8;

            this._portal.style.top = top + 'px';
            this._portal.style.left = left + 'px';
            this._portal.style.width = rect.width + 'px';
            this._portal.style.display = 'block';
        },

        _esc(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        },

        filter() {
            var q = this.query.toLowerCase().trim();
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
                    var val = item[key];
                    return val && String(val).toLowerCase().includes(q);
                });
            });

            this.open = true;

            if (this.barcodeAutoSelect && this.results.length === 1) {
                var item = this.results[0];
                var barcode = item.barcode ? String(item.barcode).toLowerCase() : '';
                var sku = item.sku ? String(item.sku).toLowerCase() : '';
                if (q === barcode || q === sku) {
                    this.select(item);
                    return;
                }
            }

            this._renderPortal();
            this._positionPortal();
        },

        async filterServer(q) {
            if (q.length < 1) {
                this.results = [];
                this.open = false;
                return;
            }
            this.loading = true;
            try {
                var r = await fetch(this.searchUrl + '?q=' + encodeURIComponent(q));
                this.results = await r.json();
                this.open = true;
                this._renderPortal();
                this._positionPortal();
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
            this._renderPortal();
            this._scrollHighlightIntoView();
        },

        _scrollHighlightIntoView() {
            if (this.highlightIndex < 0 || !this._portal) return;
            var el = this._portal.querySelector('[data-ss-idx="' + this.highlightIndex + '"]');
            if (el) el.scrollIntoView({ block: 'nearest' });
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
            this._portal.style.display = 'none';

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
            this._portal.style.display = 'none';
        },

        openAdvancedSearch() {
            var modalName = this.advancedSearchName || this.name;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'advanced-search-' + modalName }));
        },
    }));
});
