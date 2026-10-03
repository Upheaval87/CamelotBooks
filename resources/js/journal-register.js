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

        filterMode: 'range',

        /* §2.7 — the modal CONTENT is server-rendered. These reactive handles
           only decide which pre-rendered layer is visible and recalculate the
           editable totals. */
        currentId: null,
        mode: 'view',
        lines: [],
        reverseId: null,
        deleteId: null,
        reopenId: null,

        cf: { open: false, title: '', msg: '', label: 'Confirm', cls: 'jr-btn-p', form: '' },

        toastOpen: false,
        toastMsg: '',
        toastTimer: null,

        init() {
            const params = new URL(window.location.href).searchParams;
            this.filterMode = params.get('mode') === 'period' ? 'period' : 'range';
            this.$nextTick(() => this.syncBounds());
        },

        get(id) {
            return this.entries.find((entry) => Number(entry.id) === Number(id)) || null;
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

        cloneLines(entry) {
            return entry ? JSON.parse(JSON.stringify(entry.lines || [])) : [];
        },

        open(id, mode = 'view') {
            const entry = this.get(id);

            if (!entry) {
                return;
            }

            this.currentId = id;
            this.mode = mode === 'edit' ? 'edit' : 'view';
            this.lines = this.cloneLines(entry);
        },

        closeJournal() {
            this.currentId = null;
            this.mode = 'view';
            this.lines = [];
        },

        startEdit() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            this.lines = this.cloneLines(entry);
            this.mode = 'edit';
        },

        cancelEdit() {
            this.lines = this.cloneLines(this.currentEntry());
            this.mode = 'view';
        },

        openDelete(id) {
            this.currentId = id;
            this.deleteId = id;
        },

        closeDelete() {
            this.deleteId = null;
        },

        openReopen(id) {
            this.currentId = id;
            this.reopenId = id;
        },

        closeReopen() {
            this.reopenId = null;
        },

        openReverse(id) {
            this.currentId = id;
            this.reverseId = id;
        },

        closeReverse() {
            this.reverseId = null;
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

            if (!this.balanced()) {
                this.toast('⚠ The journal must balance before it can be finalized');

                return;
            }

            this.askConfirm({
                title: 'Finalize journal ' + entry.no,
                label: '✓ Finalize',
                cls: 'jr-btn-p',
                msg: 'This locks the lines and sends <b>' + entry.no + '</b> to <b>Unposted</b> for a second person to post. The ledger is untouched until it is posted.',
                form: 'jr-finalize-' + entry.id,
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
                form: 'jr-post-' + entry.id,
            });
        },

        askReverse() {
            const entry = this.currentEntry();

            if (!entry) {
                return;
            }

            const form = document.getElementById('jr-reverse-' + entry.id);
            const memo = form ? form.querySelector('[name="memo"]') : null;

            if (!memo || !memo.value.trim()) {
                this.toast('⚠ A reason is required for the reversal');

                return;
            }

            this.askConfirm({
                title: 'Confirm reversal of ' + entry.no + '?',
                label: '⟲ Confirm reversal',
                cls: 'jr-btn-danger',
                msg: 'This creates a mirroring journal — every debit becomes a credit and vice versa — posts it, marks <b>' + entry.no + '</b> Reversed and cross-links the two entries.',
                form: 'jr-reverse-' + entry.id,
            });
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

        closeTop() {
            if (this.cf.open) { this.cf.open = false; return; }
            if (this.deleteId !== null) { this.deleteId = null; return; }
            if (this.reopenId !== null) { this.reopenId = null; return; }
            if (this.reverseId !== null) { this.reverseId = null; return; }
            if (this.currentId !== null) { this.closeJournal(); }
        },
    }));
});
