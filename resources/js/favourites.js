/**
 * Favourites — personal star dropdown + pinnable sidebar.
 *
 * Single Alpine store shared by the topbar dropdown, the pinned sidebar
 * and the per-page star toggle. State is fetched once from
 * GET /favourites on init and kept in sync via optimistic writes
 * (POST/DELETE/PATCH) so interactions feel instant.
 */
import { fetchJson, csrfToken } from './http';

const MAX_FAVOURITES = 20;

const MY_TASKS = {
    page_key: 'my-tasks',
    label: 'My Tasks',
    icon: 'list-check',
    url: '',
    pinned: true,
};

const ICONS = {
    dashboard: 'M12 3a9 9 0 109 9 9 9 0 00-9-9zM12 12l3.5-3.5M12 8V3',
    'list-check': 'M3 17l2 2 4-4M3 7l2 2 4-4M13 6h8M13 12h8M13 18h8',
    users: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75M9 11a4 4 0 100-8 4 4 0 000 8z',
    truck: 'M1 3h15v13H1zM16 8h4l3 3v5h-7V8zM5.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM18.5 21a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
    box: 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
    user: 'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z',
    invoice: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8',
    'file-text': 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8',
    receipt: 'M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1zM16 8h-6M16 12H8M16 16H8',
    'file-minus': 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M9 15h6',
    'shopping-cart': 'M9 22a1 1 0 100-2 1 1 0 000 2zM20 22a1 1 0 100-2 1 1 0 000 2zM1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6',
    clipboard: 'M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2M15 2H9a1 1 0 00-1 1v2a1 1 0 001 1h6a1 1 0 001-1V3a1 1 0 00-1-1zM9 12h6M9 16h6',
    'package-check': 'M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12M9 10l2 2 4-4',
    wallet: 'M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 000 4h4v-4z',
    'layout-grid': 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
    package: 'M16.5 9.4L7.55 4.24M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
    folder: 'M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z',
    layers: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
    sliders: 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6',
    swap: 'M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4',
    'clipboard-check': 'M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2M15 2H9a1 1 0 00-1 1v2a1 1 0 001 1h6a1 1 0 001-1V3a1 1 0 00-1-1zM9 14l2 2 4-4',
    ruler: 'M16.5 3.5l4 4L7.5 20.5l-4-4zM12 8l4 4M8 12l4 4',
    anchor: 'M12 22V8M5 12H2a10 10 0 0020 0h-3M12 8a3 3 0 100-6 3 3 0 000 6z',
    scale: 'M12 3v18M3 7h18M6 7l-3 5 3 5m12-10l3 5-3 5M7 21h10',
    'alert-triangle': 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01',
    bank: 'M3 21h18M3 10h18M5 10v11M19 10v11M10 10v11M14 10v11M3 10l9-6 9 6M3 21h18',
    'arrow-left-right': 'M8 3L4 7l4 4M4 7h16M16 21l4-4-4-4M20 17H4',
    'arrow-down-circle': 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 8v8M8 12l4 4 4-4',
    'credit-card': 'M1 10h22M1 6a2 2 0 012-2h18a2 2 0 012 2v12a2 2 0 01-2 2H3a2 2 0 01-2-2zM1 10h22',
    cash: 'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6',
    book: 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5z',
    'book-open': 'M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2zM22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z',
    scroll: 'M19 17V5a2 2 0 00-2-2H4M19 17a2 2 0 002-2M19 17v1a2 2 0 01-2 2H8M4 4h11v9H4V4zM4 21a2 2 0 01-2-2V3',
    calendar: 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z',
    'calendar-clock': 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2zM9 16l3 3 5-5',
    repeat: 'M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3',
    building: 'M6 22V4a2 2 0 012-2h8a2 2 0 012 2v18M6 22H4m2 0h16M2 22h20M8 6h8M8 10h8M8 14h8M8 18h8',
    globe: 'M12 22a10 10 0 100-20 10 10 0 000 20zM2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z',
    tags: 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.83zM7 7h.01',
    target: 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 18a6 6 0 100-12 6 6 0 000 12zM12 14a2 2 0 100-4 2 2 0 000 4z',
    'trend-down': 'M23 18l-9.5-9.5-5 5L1 6M17 18h6v-6',
    'trend-up': 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
    activity: 'M22 12h-4l-3 9L9 3l-3 9H2',
    trash: 'M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6',
    banknote: 'M2 6h20v12H2zM2 10h20M2 14h20M2 18h20M9 6c0 2-1 2-1 4M15 18c0-2 1-2 1-4',
    percent: 'M19 5L5 19M6.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM17.5 20a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
    shield: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
    'chart-line': 'M3 3v18h18M19 9l-5 5-4-4-5 5',
    'chart-bar': 'M18 20V10M12 20V4M6 20v-6',
    clock: 'M12 22a10 10 0 100-20 10 10 0 000 20zM12 6v6l4 2',
    cpu: 'M9 9h6v6H9zM9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 15h3M1 9h3M1 15h3M5 5h14v14H5z',
    monitor: 'M2 4h20v14H2zM8 22h8M12 18v4',
    lock: 'M19 11H5a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2zM7 11V7a5 5 0 0110 0v4',
    refresh: 'M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8M3 3v5h5',
    coins: 'M8 21h8M12 17v4M17 4a4 4 0 00-10 0c0 4 10 4 10 8a4 4 0 01-8 0M8 21h8M12 17v4',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    toggle: 'M2 12a4 4 0 014-4h12a4 4 0 010 8H6a4 4 0 01-4-4zM6 8a4 4 0 100 8',
    hash: 'M4 9h16M4 15h16M10 3L8 21M16 3l-2 18',
    bell: 'M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0',
    database: 'M12 3c3.9 0 7 1.8 7 4s-3.1 4-7 4-7-1.8-7-4 3.1-4 7-4zM5 7v6c0 2.2 3.1 4 7 4s7-1.8 7-4V7M5 13v6c0 2.2 3.1 4 7 4s7-1.8 7-4v-6',
    'heart-pulse': 'M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 00-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 000-7.8zM3.2 12h6l2-3 2 6 2-3h6',
    'shield-check': 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10zM9 11l2 2 4-4',
    wand: 'M15 4V2M15 16v-2M8 9h2M20 9h2M17.8 11.8L19 13M15 9h.01M17.8 6.2L19 5M3 21l9-9M12.2 6.2L11 5',
    'git-branch': 'M6 3v12M18 9a4 4 0 100-8 4 4 0 000 8zM6 21a4 4 0 100-8 4 4 0 000 8zM18 9c-1.5 0-3 .8-4 2s-2.5 2-4 2',
    star: 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z',
    pin: 'M12 17v5M9 2h6l1 7 3 3v2H5v-2l3-3z',
    'chevron-left': 'M15 18l-6-6 6-6',
    x: 'M18 6L6 18M6 6l12 12',
    plus: 'M12 5v14M5 12h14',
};

function svg(d) {
    return '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">'
        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="' + d + '"/></svg>';
}

function makeToast(message, onUndo) {
    var container = document.getElementById('toast-container');
    if (!container) return;
    var t = document.createElement('div');
    t.className = 'pointer-events-auto flex items-center gap-3 bg-white dark:bg-neutral-900 border-l-[3px] border-l-gold rounded-xl px-4 py-3 shadow-elevated min-w-[300px] max-w-sm animate-fade-in-up';
    t.innerHTML = '<span class="text-sm text-neutral-700 dark:text-neutral-300">' + message + '</span>';
    if (onUndo) {
        var undo = document.createElement('button');
        undo.type = 'button';
        undo.className = 'ml-auto text-xs font-semibold text-gold hover:text-gold/80 shrink-0';
        undo.textContent = 'Undo';
        undo.addEventListener('click', function () {
            onUndo();
            dismiss();
        });
        t.appendChild(undo);
    }
    container.appendChild(t);
    function dismiss() {
        if (t.parentNode) {
            t.style.opacity = '0';
            t.style.transform = 'translateX(20px)';
            t.style.transition = 'all 0.3s ease';
            setTimeout(function () { if (t.parentNode) t.remove(); }, 300);
        }
    }
    setTimeout(dismiss, 5000);
}

function buildStore() {
    return {
        items: [],
        pinned: false,
        collapsed: true,
        dropdownOpen: false,
        pickerOpen: false,
        pickerQuery: '',
        pages: [],
        pagesLoaded: false,
        currentKey: '',
        dragId: null,
        holdTimer: null,
        holdingKey: null,
        dragArmed: null,
        suppressClick: null,

        init() {
            var self = this;
            fetchJson(window.favouritesIndexUrl || '/favourites')
                .then(function (data) {
                    self.items = data.favourites || [];
                    self.pinned = !!data.pinned;
                })
                .catch(function () {});
        },

        count() {
            return this.items.length + 1;
        },

        visibleItems() {
            var tasks = Object.assign({}, MY_TASKS);
            tasks.url = window.todoIndexUrl || '/todo';
            return [tasks].concat(this.items);
        },

        isFav(key) {
            return this.items.some(function (f) { return f.page_key === key; });
        },

        icon(name) {
            return svg(ICONS[name] || ICONS.star);
        },

        get pinHint() {
            return this.pinned
                ? 'Shown in your sidebar. This choice is saved to your account.'
                : 'Not shown in sidebar — pin to keep it visible while you work.';
        },

        toggleDropdown() {
            this.dropdownOpen = !this.dropdownOpen;
            if (this.dropdownOpen) {
                this.pickerOpen = false;
                this.pickerQuery = '';
            }
        },

        toggleCollapse() {
            this.collapsed = !this.collapsed;
        },

        expand() {
            if (this.collapsed) {
                this.collapsed = false;
            }
        },

        // Press-and-hold arms drag-reorder; a plain click just navigates.
        startHold(pageKey) {
            if (this.locked) return;
            this.cancelHold();
            this.holdingKey = pageKey;
            var self = this;
            this.holdTimer = setTimeout(function () {
                if (self.holdingKey === pageKey) {
                    self.dragArmed = pageKey;
                }
            }, 450);
        },

        cancelHold() {
            if (this.holdTimer) {
                clearTimeout(this.holdTimer);
                this.holdTimer = null;
            }
            this.holdingKey = null;
        },

        endHold(pageKey) {
            if (this.holdingKey !== pageKey && this.dragArmed !== pageKey) {
                this.cancelHold();
                return;
            }
            var wasArmed = this.dragArmed === pageKey;
            this.cancelHold();
            if (wasArmed) {
                this.dragArmed = null;
            }
        },

        handleItemClick(item, e) {
            if (this.dragArmed) {
                this.dragArmed = null;
                return;
            }
            if (this.collapsed) {
                return;
            }
            this.go(item);
        },

        toggle(pageKey, label, icon, url) {
            if (this.isFav(pageKey)) {
                this.remove(pageKey);
            } else {
                this.add({ page_key: pageKey, label: label, icon: icon, url: url });
            }
        },

        add(meta) {
            if (this.isFav(meta.page_key)) return;
            if (this.items.length >= MAX_FAVOURITES) {
                if (window.atlasToast) window.atlasToast('You have reached the maximum of ' + MAX_FAVOURITES + ' favourites.', 'warning');
                return;
            }
            this.items.push(Object.assign({}, meta));
            var self = this;
            fetchJson(window.favouritesStoreUrl || '/favourites', {
                method: 'POST',
                body: JSON.stringify(meta),
            })
                .then(function (data) {
                    if (data && data.favourite && data.favourite.page_key) {
                        var idx = self.items.findIndex(function (f) { return f.page_key === meta.page_key; });
                        if (idx > -1) self.items[idx] = data.favourite;
                    }
                })
                .catch(function () {
                    self.items = self.items.filter(function (f) { return f.page_key !== meta.page_key; });
                    if (window.atlasToast) window.atlasToast('Could not add favourite. Please try again.', 'error');
                });
        },

        remove(pageKey, opts) {
            opts = opts || {};
            var removed = this.items.find(function (f) { return f.page_key === pageKey; });
            this.items = this.items.filter(function (f) { return f.page_key !== pageKey; });

            var self = this;
            fetchJson(window.favouritesDestroyUrl ? window.favouritesDestroyUrl.replace(':pageKey', encodeURIComponent(pageKey)) : '/favourites/' + encodeURIComponent(pageKey), {
                method: 'DELETE',
            })
                .catch(function () {
                    if (removed) self.items.push(removed);
                    if (window.atlasToast) window.atlasToast('Could not remove favourite. Please try again.', 'error');
                });

            if (opts.undo !== false && removed) {
                var label = removed.label;
                makeToast('Removed "' + label + '" from favourites.', function () {
                    self.add(removed);
                });
            } else {
                if (window.atlasToast) window.atlasToast('Removed from favourites.');
            }
        },

        setPinned(value) {
            this.pinned = !!value;
            var self = this;
            fetchJson(window.favouritesPreferencesUrl || '/favourites/preferences', {
                method: 'PATCH',
                body: JSON.stringify({ sidebar_pinned: this.pinned }),
            }).catch(function () {
                self.pinned = !self.pinned;
                if (window.atlasToast) window.atlasToast('Could not update sidebar preference.', 'error');
            });
        },

        go(item) {
            if (!item.url) return;
            this.dropdownOpen = false;
            window.location.href = item.url;
        },

        openPicker() {
            this.pickerOpen = true;
            this.pickerQuery = '';
            var self = this;
            if (!this.pagesLoaded) {
                fetchJson(window.favouritesPagesUrl || '/favourites/pages')
                    .then(function (data) {
                        self.pages = data.pages || [];
                        self.pagesLoaded = true;
                    })
                    .catch(function () {});
            }
        },

        get filteredPages() {
            var q = this.pickerQuery.trim().toLowerCase();
            var self = this;
            return this.pages.filter(function (p) {
                if (self.isFav(p.page_key)) return false;
                if (!q) return true;
                return p.label.toLowerCase().indexOf(q) !== -1
                    || p.page_key.toLowerCase().indexOf(q) !== -1;
            });
        },

        pick(page) {
            if (this.isFav(page.page_key)) return;
            if (this.items.length >= MAX_FAVOURITES) {
                if (window.atlasToast) window.atlasToast('You have reached the maximum of ' + MAX_FAVOURITES + ' favourites.', 'warning');
                return;
            }
            this.add({ page_key: page.page_key, label: page.label, icon: page.icon, url: page.url });
        },

        dragStart(pageKey, e) {
            this.dragId = pageKey;
            e.dataTransfer.effectAllowed = 'move';
            if (e.target && e.target.classList) e.target.classList.add('dragging');
        },

        dragEnd() {
            this.dragId = null;
            this.clearDropMarks();
        },

        dragOver(pageKey, e) {
            e.preventDefault();
            if (pageKey === this.dragId) return;
            var el = e.currentTarget;
            var rect = el.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            this.clearDropMarks();
            el.classList.add(before ? 'drop-before' : 'drop-after');
        },

        drop(targetKey, e) {
            e.preventDefault();
            var from = this.dragId;
            if (!from || from === targetKey) {
                this.dragId = null;
                this.clearDropMarks();
                return;
            }
            var rect = e.currentTarget.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            var arr = this.items.map(function (f) { return f.page_key; });
            var fromIdx = arr.indexOf(from);
            var toIdx = arr.indexOf(targetKey);
            if (fromIdx === -1 || toIdx === -1) {
                this.dragId = null;
                this.clearDropMarks();
                return;
            }
            arr.splice(fromIdx, 1);
            toIdx = arr.indexOf(targetKey);
            if (!before) toIdx += 1;
            arr.splice(toIdx, 0, from);
            this.reorderKeys(arr);
            this.dragId = null;
            this.clearDropMarks();
        },

        reorderKeys(keys) {
            var self = this;
            var byKey = {};
            this.items.forEach(function (f) { byKey[f.page_key] = f; });
            this.items = keys
                .map(function (k) { return byKey[k]; })
                .filter(function (f) { return !!f; });

            fetchJson(window.favouritesReorderUrl || '/favourites/reorder', {
                method: 'PATCH',
                body: JSON.stringify({ keys: keys }),
            }).catch(function () {
                if (window.atlasToast) window.atlasToast('Could not reorder favourites.', 'error');
            });
        },

        clearDropMarks() {
            document.querySelectorAll('.fav-item.drop-before, .fav-item.drop-after')
                .forEach(function (el) { el.classList.remove('drop-before', 'drop-after'); });
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.store('favourites', buildStore());

    Alpine.data('favouriteToggle', (pageKey, label, icon, url, locked) => ({
        pageKey,
        label,
        icon,
        url,
        locked: !!locked,
        get isFav() {
            return Alpine.store('favourites').isFav(this.pageKey);
        },
        get title() {
            if (this.locked) return 'This page is always pinned';
            return this.isFav ? 'Remove from favourites' : 'Add to favourites';
        },
        init() {
            var store = Alpine.store('favourites');
            if (!store.currentKey) store.currentKey = this.pageKey;
            if (this.locked && !store.isFav(this.pageKey)) {
                store.items.unshift({
                    page_key: this.pageKey,
                    label: this.label,
                    icon: this.icon,
                    url: this.url,
                    pinned: true,
                });
            }
        },
        toggle() {
            if (this.locked) return;
            Alpine.store('favourites').toggle(this.pageKey, this.label, this.icon, this.url);
        },
    }));
});
