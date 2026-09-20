const jrNum = (value) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

document.addEventListener('alpine:init', () => {
    Alpine.data('journalRegister', (config = {}) => ({
        entries: config.entries || [],
        can: config.can || {},
        me: config.me || 0,
        defaultAccount: config.defaultAccount || null,

        filterMode: 'range',

        journalOpen: false,
        mode: 'view',
        currentId: null,
        lines: [],
        viewed: {},

        cf: { open: false, title: '', msg: '', label: 'Confirm', cls: 'jr-btn-p', form: '' },

        deleteOpen: false,
        reopenOpen: false,
        reverseOpen: false,
        rv: { date: '', reference: '', memo: '', post_mode: 'immediate' },

        toastOpen: false,
        toastMsg: '',
        toastTimer: null,

        init() {
            const params = new URL(window.location.href).searchParams;
            this.filterMode = params.get('mode') === 'period' ? 'period' : 'range';
            this.$nextTick(() => this.syncBounds());
        },

        get(id) {
            return this.entries.find((entry) => entry.id === id) || null;
        },

        currentEntry() {
            return this.get(this.currentId);
        },

        isMine(entry) {
            return entry ? Number(entry.creator) === Number(this.me) : false;
        },

        totalDr() {
            return this.lines.reduce((sum, line) => sum + (Number(line.dr) || 0), 0);
        },

        totalCr() {
            return this.lines.reduce((sum, line) => sum + (Number(line.cr) || 0), 0);
        },

        balanced() {
            return Math.abs(this.totalDr() - this.totalCr()) < 0.005 && this.totalDr() > 0;
        },

        fmt(value) {
            return jrNum(value);
        },

        pillClass(status) {
            if (status === 'draft') return 'unfin';
            if (status === 'pending_approval' || status === 'approved') return 'unpost';
            if (status === 'posted') return 'posted';

            return 'reversed';
        },

        pillLabel(status) {
            const map = {
                draft: 'Unfinalized',
                pending_approval: 'Unposted',
                approved: 'Unposted',
                posted: 'Posted',
                reversed: 'Reversed',
            };

            return map[status] || status;
        },

        open(id, mode = 'view') {
            const entry = this.get(id);

            if (!entry) {
                return;
            }

            this.currentId = id;
            this.mode = mode === 'edit' ? 'edit' : 'view';
            this.lines = JSON.parse(JSON.stringify(entry.lines || []));
            this.viewed[id] = true;
            this.journalOpen = true;
        },

        startEdit() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            this.lines = JSON.parse(JSON.stringify(entry.lines || []));
            this.mode = 'edit';
        },

        cancelEdit() {
            const entry = this.currentEntry();

            this.lines = entry ? JSON.parse(JSON.stringify(entry.lines || [])) : [];
            this.mode = 'view';
        },

        addLine() {
            this.lines.push({
                account_id: this.defaultAccount ? this.defaultAccount.id : null,
                a: this.defaultAccount ? this.defaultAccount.code : '—',
                n: this.defaultAccount ? this.defaultAccount.name : '',
                d: '',
                dr: null,
                cr: null,
                cc: '—',
            });
        },

        removeLine(index) {
            if (this.lines.length > 1) {
                this.lines.splice(index, 1);
            }
        },

        openDelete(id) {
            this.currentId = id;
            this.deleteOpen = true;
        },

        openReopen(id) {
            this.currentId = id;
            this.reopenOpen = true;
        },

        openReverse(id) {
            const entry = this.get(id);

            if (!entry) {
                return;
            }

            this.currentId = id;
            this.rv = {
                date: new Date().toISOString().slice(0, 10),
                reference: 'REV-' + entry.no,
                memo: '',
                post_mode: 'immediate',
            };
            this.reverseOpen = true;
        },

        openReversal(id) {
            const entry = this.get(id);

            if (!entry) {
                return;
            }

            const reversal = entry.reversalId ? this.get(entry.reversalId) : null;

            if (reversal) {
                this.open(reversal.id, 'view');
            } else {
                window.location.href = entry.urls.show;
            }
        },

        printEntry(id) {
            this.open(id, 'view');
            this.$nextTick(() => window.print());
        },

        askConfirm(cfg) {
            this.cf = {
                open: true,
                title: cfg.title || 'Confirm',
                msg: cfg.msg || '',
                label: cfg.label || 'Confirm',
                cls: cfg.cls || 'jr-btn-p',
                form: cfg.form || '',
            };
        },

        confirmYes() {
            const form = document.getElementById(this.cf.form);

            this.cf.open = false;

            if (form) {
                form.submit();
            }
        },

        askFinalize() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            this.askConfirm({
                title: 'Finalize journal ' + entry.no,
                label: '✓ Finalize',
                cls: 'jr-btn-p',
                msg: 'This locks the lines and sends <b>' + entry.no + '</b> to <b>Unposted</b> for a second person to post. The ledger is untouched until it is posted.',
                form: 'jr-finalize-form',
            });
        },

        askPost() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            this.askConfirm({
                title: 'Post journal ' + entry.no,
                label: '✓ Post to Ledger',
                cls: 'jr-btn-p',
                msg: 'This writes <b>' + entry.no + '</b> to the General Ledger and locks it. Posted journals can only be corrected by reversing them.',
                form: 'jr-post-form',
            });
        },

        askReverse() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            if (!this.rv.memo || !this.rv.memo.trim()) {
                this.toast('⚠ A reason is required for the reversal');

                return;
            }

            this.askConfirm({
                title: 'Confirm reversal ' + entry.no,
                label: '⟲ Reverse',
                cls: 'jr-btn-danger',
                msg: 'A mirroring journal will be created and posted, cross-linked to <b>' + entry.no + '</b>. The original stays on the register as <b>Reversed</b>.',
                form: 'jr-reverse-form',
            });
        },

        footerNote() {
            const entry = this.currentEntry();

            if (!entry) {
                return '';
            }

            if (this.mode === 'edit') {
                return 'Editing lines — totals update live. Finalize sends it for posting.';
            }

            if (entry.status === 'draft') {
                return this.isMine(entry)
                    ? 'Unfinalized draft — you are the creator, so you can edit, finalize or delete it.'
                    : 'Unfinalized draft — only the creator (' + entry.creatorName + ') can edit, finalize or delete it.';
            }

            if (entry.status === 'pending_approval' || entry.status === 'approved') {
                return 'Finalized and awaiting a second person to post it to the ledger.';
            }

            if (entry.status === 'posted') {
                return 'Posted to the ledger — immutable. Use Reverse to correct it.';
            }

            return entry.reversalNo ? 'Reversed by ' + entry.reversalNo + '.' : 'Reversed.';
        },

        setMode(mode) {
            this.filterMode = mode;
            this.$refs.jrFrom?.classList.remove('bad');
            this.$refs.jrTo?.classList.remove('bad');

            if (mode === 'range') {
                this.syncBounds();
            }
        },

        syncBounds() {
            const from = this.$refs.jrFrom;
            const to = this.$refs.jrTo;

            if (!from || !to) {
                return;
            }

            to.min = from.value || '';
            from.max = to.value || '';
            this.validateDates();
        },

        validateDates() {
            const from = this.$refs.jrFrom;
            const to = this.$refs.jrTo;

            if (!from || !to) {
                return true;
            }

            const bad = Boolean(from.value && to.value && to.value < from.value);

            from.classList.toggle('bad', bad);
            to.classList.toggle('bad', bad);

            if (bad) {
                this.toast('⚠ To date cannot be earlier than From date');
            }

            return !bad;
        },

        toast(message) {
            this.toastMsg = message;
            this.toastOpen = true;

            clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastOpen = false;
            }, 3200);
        },

        closeAll() {
            this.journalOpen = false;
            this.cf.open = false;
            this.deleteOpen = false;
            this.reopenOpen = false;
            this.reverseOpen = false;
        },
    }));
});
