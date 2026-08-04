function todoResolveLinkFromEvent(event) {
    const detail = (event && event.detail) || {};
    const item = detail.item || {};
    const entityKey = item.groupKey || item.entity || item.type || '';
    const map = window.TODO_LINKABLE_CLASS_MAP || {};

    return {
        linkableType: map[entityKey] || '',
        linkableId: item.id ?? '',
        linkLabel: item.label || '',
        linkUrl: item.url || '',
    };
}

function todoClearLinkValues() {
    return {
        linkableType: '',
        linkableId: '',
        linkLabel: '',
        linkUrl: '',
    };
}

function todoIsoDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

document.addEventListener('alpine:init', () => {
    // Deadline quick-pick chips + custom date picker. Keeps granularity and
    // the end-of-period date in sync, mirroring TodoTask::deadlineFor().
    Alpine.data('todoDeadline', (config) => ({
        name: config.name || 'deadline',
        granularity: config.granularity || '',
        date: config.date || '',
        customOpen: false,

        init() {
            if (!this.granularity && this.date) this.granularity = 'day';
            if (this.granularity === 'day' && this.date && !this.isToday(this.date)) {
                this.customOpen = true;
            }
        },

        todayIso() {
            return todoIsoDate(new Date());
        },

        isToday(value) {
            return value === this.todayIso();
        },

        pick(granularity) {
            const d = new Date();
            let value = '';
            if (granularity === 'day') {
                value = this.todayIso();
            } else if (granularity === 'week') {
                const diff = d.getDay() === 0 ? 0 : 7 - d.getDay();
                const end = new Date(d);
                end.setDate(d.getDate() + diff);
                value = todoIsoDate(end);
            } else if (granularity === 'month') {
                value = todoIsoDate(new Date(d.getFullYear(), d.getMonth() + 1, 0));
            } else if (granularity === 'year') {
                value = todoIsoDate(new Date(d.getFullYear(), 11, 31));
            }
            this.granularity = granularity;
            this.date = value;
            this.customOpen = false;
        },

        pickCustom() {
            this.granularity = 'day';
            this.customOpen = true;
            if (!this.date) this.date = this.todayIso();
        },

        isTodayActive() {
            return this.granularity === 'day' && !!this.date && this.isToday(this.date);
        },

        isCustomActive() {
            return this.granularity === 'day' && !!this.date && !this.isToday(this.date);
        },

        chipClass(granularity, kind) {
            let active = false;
            if (kind === 'today') active = this.isTodayActive();
            else if (kind === 'custom') active = this.isCustomActive();
            else active = this.granularity === granularity;
            return active ? 'is-active' : '';
        },

        get preview() {
            if (!this.granularity || !this.date) return 'No deadline';
            if (this.isTodayActive()) return 'Due today';
            if (this.granularity === 'week') return 'Due this week';
            if (this.granularity === 'month') return 'Due this month';
            if (this.granularity === 'year') return 'Due this year';
            const d = new Date(this.date + 'T00:00:00');
            return 'Due ' + d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        },
    }));

    // Quick-add composer: expand/collapse + record-link capture.
    Alpine.data('todoComposer', () => ({
        open: false,
        linkableType: '',
        linkableId: '',
        linkLabel: '',
        linkUrl: '',

        onLinkSelected(event) {
            Object.assign(this, todoResolveLinkFromEvent(event));
        },

        clearLink() {
            Object.assign(this, todoClearLinkValues());
        },
    }));

    // Shared task detail modal: opened by any row, holds current task state.
    Alpine.data('todoDetailModal', () => ({
        open: false,
        taskId: null,
        title: '',
        priority: 'medium',
        deadlineGranularity: '',
        deadlineDate: '',
        deadlineLabel: '',
        isOverdue: false,
        updateUrl: '',
        deleteUrl: '',
        linkableType: '',
        linkableId: '',
        linkLabel: '',
        linkUrl: '',

        openTask(task) {
            this.taskId = task.taskId;
            this.title = task.title;
            this.priority = task.priority;
            this.deadlineGranularity = task.deadlineGranularity;
            this.deadlineDate = task.deadlineDate;
            this.deadlineLabel = task.deadlineLabel;
            this.isOverdue = task.isOverdue;
            this.updateUrl = task.updateUrl;
            this.deleteUrl = task.deleteUrl;
            this.linkableType = task.linkableType;
            this.linkableId = task.linkableId;
            this.linkLabel = task.linkLabel;
            this.linkUrl = task.linkUrl;
            this.open = true;
            this.$nextTick(() => {
                this.$dispatch('open-modal', 'task-detail');
            });
        },

        onLinkSelected(event) {
            Object.assign(this, todoResolveLinkFromEvent(event));
        },

        clearLink() {
            Object.assign(this, todoClearLinkValues());
        },
    }));
});
