import { fetchJson } from './http';

document.addEventListener('alpine:init', () => {
    Alpine.data('reportCenter', (config) => ({
        q: (config && config.initialSearch) || '',
        cat: (config && config.initialCategory) || '',
        sort: 'az',
        groups: (config && config.groups) || [],
        favs: (config && config.favorites) || [],
        toggleUrl: (config && config.toggleUrl) || '',

        get cats() {
            return this.groups.map((g) => ({ key: g.key, label: g.label, icon: g.icon, count: g.reports.length }));
        },

        get totalCount() {
            return this.groups.reduce((n, g) => n + g.reports.length, 0);
        },

        get visibleGroups() {
            let groups = this.groups;
            if (this.cat) {
                groups = groups.filter((g) => g.key === this.cat);
            }
            const q = this.q.trim().toLowerCase();
            if (q) {
                groups = groups.map((g) => ({
                    key: g.key,
                    label: g.label,
                    icon: g.icon,
                    reports: g.reports.filter((r) =>
                        (r.name || '').toLowerCase().includes(q) ||
                        (r.description || '').toLowerCase().includes(q)),
                })).filter((g) => g.reports.length > 0);
            }
            const dir = this.sort === 'za' ? -1 : 1;
            groups = groups.map((g) => ({
                key: g.key,
                label: g.label,
                icon: g.icon,
                reports: [...g.reports].sort((a, b) => dir * (a.name || '').localeCompare(b.name || '')),
            }));
            return groups;
        },

        get visibleCount() {
            return this.visibleGroups.reduce((n, g) => n + g.reports.length, 0);
        },

        clearFilters() {
            this.q = '';
            this.cat = '';
            this.sort = 'az';
        },

        isFav(key) {
            return this.favs.some((f) => f.key === key);
        },

        toggleFav(report) {
            const key = report.key;
            const wasFav = this.isFav(key);
            const slim = { key: report.key, name: report.name, url: report.url };
            if (wasFav) {
                this.favs = this.favs.filter((f) => f.key !== key);
            } else {
                this.favs = [...this.favs, slim];
            }
            const url = this.toggleUrl.replace(':key', encodeURIComponent(key));
            fetchJson(url, { method: 'POST' })
                .then((data) => {
                    if (!data.favorited) {
                        this.favs = this.favs.filter((f) => f.key !== key);
                    }
                })
                .catch(() => {
                    if (wasFav) {
                        this.favs = [...this.favs, slim];
                    } else {
                        this.favs = this.favs.filter((f) => f.key !== key);
                    }
                });
        },

        init() {
            const onKey = (e) => {
                if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
                const el = document.activeElement;
                if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable)) return;
                e.preventDefault();
                if (this.$refs.search) this.$refs.search.focus();
            };
            window.addEventListener('keydown', onKey);
            this._onKey = onKey;
        },

        destroy() {
            if (this._onKey) {
                window.removeEventListener('keydown', this._onKey);
            }
        },
    }));
});
