window.TC_ICONS = {
    journal: '<svg viewBox="0 0 24 24"><path d="M4 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v16l-3.5-2-3.5 2-3.5-2L4 21V5Z"/><path d="M8 7h7M8 11h7"/></svg>',
    deposit: '<svg viewBox="0 0 24 24"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>',
    transfer: '<svg viewBox="0 0 24 24"><path d="M4 8h13m0 0-3-3m3 3-3 3M20 16H7m0 0 3-3m-3 3 3 3"/></svg>',
    repost: '<svg viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.3-5.6M20 3v4h-4"/></svg>',
    reverse: '<svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5M4 9h11a5 5 0 0 1 0 10h-3"/></svg>',
};

document.addEventListener('alpine:init', () => {
    Alpine.data('transactionControls', (config = {}) => ({
        tab: config.tab || 'reversal',
        transactions: config.transactions || [],
        unposted: config.unposted || [],
        authRows: config.authRows || [],
        urls: config.urls || {},
        currencySymbol: config.currencySymbol || '$',
        today: new Date().toISOString().slice(0, 10),

        viewTx: null,
        revTx: null,
        authReq: null,
        unpTx: null,
        authComment: '',

        revForm: {
            mode: 'immediate',
            reversal_date: '',
            reference: '',
            reason: '',
        },

        open: { view: false, reverse: false, auth: false },

        confirm: {
            open: false,
            title: '',
            body: '',
            label: 'Confirm',
            tone: 'primary',
            formId: null,
            note: false,
            noteName: 'reason',
            noteValue: '',
            noteRequired: false,
            notePlaceholder: '',
        },

        init() {
            if (config.viewEntry) {
                this.viewTx = config.viewEntry;
                this.open.view = true;
            }
            if (config.authPayload) {
                this.authReq = config.authPayload;
                this.open.auth = true;
            }
        },

        switchTab(key) {
            this.tab = key;
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', key);
                window.history.replaceState({}, '', url);
            } catch (e) {
                /* no-op */
            }
        },

        txById(id) {
            return this.transactions.find((t) => Number(t.id) === Number(id)) || null;
        },

        unpostedById(id) {
            return this.unposted.find((t) => Number(t.id) === Number(id)) || null;
        },

        authById(id) {
            return this.authRows.find((a) => Number(a.id) === Number(id)) || null;
        },

        openView(tx) {
            if (!tx) return;
            this.viewTx = tx;
            this.open.view = true;
        },

        openReverse(tx) {
            if (!tx) return;
            this.revTx = tx;
            this.revForm = {
                mode: 'immediate',
                reversal_date: this.today,
                reference: '',
                reason: '',
            };
            this.open.reverse = true;
        },

        openAuth(row) {
            if (!row) return;
            this.authReq = row;
            this.authComment = '';
            this.open.auth = true;
        },

        iconFor(type) {
            const t = String(type || '').toLowerCase();
            if (t.includes('reversal')) return window.TC_ICONS.reverse;
            if (t.includes('repost')) return window.TC_ICONS.repost;
            if (t.includes('transfer')) return window.TC_ICONS.transfer;
            if (t.includes('deposit') || t.includes('receipt')) return window.TC_ICONS.deposit;
            return window.TC_ICONS.journal;
        },

        askReopen(id) {
            const tx = this.unpostedById(id);
            if (!tx) return;
            this.unpTx = tx;
            this.askConfirm({
                title: 'Reopen transaction',
                body: 'Reopen ' + tx.ref + ' for editing? It will return to draft status.',
                label: 'Reopen',
                tone: 'primary',
                formId: 'tc-reopen-form',
                note: true,
                noteName: 'reason',
                notePlaceholder: 'Optional reason',
            });
        },

        askDeleteUnposted(id) {
            const tx = this.unpostedById(id);
            if (!tx) return;
            this.unpTx = tx;
            this.askConfirm({
                title: 'Delete draft',
                body: 'Permanently delete ' + tx.ref + '? This cannot be undone.',
                label: 'Delete',
                tone: 'danger',
                formId: 'tc-delete-form',
            });
        },

        askApprove() {
            const r = this.authReq;
            if (!r) return;
            this.askConfirm({
                title: 'Approve & post',
                body: 'Authorize the reversal of ' + (r.ref || 'this request') + ' and post it to the ledger?',
                label: 'Approve & post',
                tone: 'primary',
                formId: 'tc-approve-form',
                note: true,
                noteName: 'comments',
                noteValue: this.authComment,
                notePlaceholder: 'Optional comments',
            });
        },

        askReject() {
            const r = this.authReq;
            if (!r) return;
            this.askConfirm({
                title: 'Reject request',
                body: 'Reject the reversal request ' + (r.ref || '') + '?',
                label: 'Reject',
                tone: 'danger',
                formId: 'tc-reject-form',
                note: true,
                noteName: 'reason',
                noteRequired: true,
                noteValue: this.authComment,
                notePlaceholder: 'Reason for rejection (required)',
            });
        },

        askConfirm(cfg) {
            this.confirm = Object.assign({
                open: true,
                title: '',
                body: '',
                label: 'Confirm',
                tone: 'primary',
                formId: null,
                note: false,
                noteName: 'reason',
                noteValue: '',
                noteRequired: false,
                notePlaceholder: '',
            }, cfg, { open: true });
        },

        confirmYes() {
            const c = this.confirm;
            if (c.noteRequired && !String(c.noteValue || '').trim()) {
                if (window.CB && window.CB.toast) {
                    window.CB.toast('warning', 'A reason is required', 'Please provide a reason before continuing.');
                }
                return;
            }
            const form = c.formId ? document.getElementById(c.formId) : null;
            if (!form) return;
            if (c.note) {
                const input = form.querySelector('[name="' + c.noteName + '"]');
                if (input) input.value = c.noteValue || '';
            }
            form.submit();
        },

        closeAll() {
            this.open = { view: false, reverse: false, auth: false };
            this.confirm.open = false;
        },

        applyPreset(ev, key) {
            const form = ev.currentTarget.closest('form')
                || document.querySelector('[data-tc-gate-form]');
            if (!form) return;
            const from = form.querySelector('input[name="from"]');
            const to = form.querySelector('input[name="to"]');
            const d = new Date();
            const iso = (x) => x.toISOString().slice(0, 10);
            let f;
            let t;
            if (key === 'today') {
                f = t = iso(d);
            } else if (key === '7d') {
                const s = new Date(d);
                s.setDate(d.getDate() - 6);
                f = iso(s);
                t = iso(d);
            } else if (key === '30d') {
                const s = new Date(d);
                s.setDate(d.getDate() - 29);
                f = iso(s);
                t = iso(d);
            } else {
                const s = new Date(d.getFullYear(), d.getMonth(), 1);
                f = iso(s);
                t = iso(d);
            }
            if (from) from.value = f;
            if (to) to.value = t;
            form.submit();
        },

        fmt(n) {
            const value = Number(n || 0);
            return value.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    }));
});
