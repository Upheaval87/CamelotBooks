import { csrfToken } from './http.js';
import { todoSubmitFetch } from './todo.js';

document.addEventListener('alpine:init', () => {
    // Topbar "My Tasks" modal. Loads the server-rendered list fragment
    // (todo/partials/modal-list.blade.php) into the modal on every open and
    // after every mutation, so Alpine components inside it re-initialise
    // automatically. All form submits inside the modal go through fetch();
    // nothing ever triggers a page navigation.
    Alpine.data('todoModal', () => ({
        busy: false,

        init() {
            this.$refs.wrap.addEventListener('submit', (e) => this.onSubmit(e));
        },

        async refresh() {
            const url = window.todoModalUrl || '/accounting/todo/modal';
            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                });
                if (!res.ok) throw new Error('Could not load tasks.');
                const html = await res.text();
                this.$refs.list.innerHTML = html;
                this.updateTriggerCount();
            } catch (err) {
                this.$refs.list.innerHTML = '<p class="text-sm text-red-600 p-4">Could not load tasks.</p>';
            }
        },

        updateTriggerCount() {
            const el = document.getElementById('todo-trigger-count');
            const root = this.$refs.list.querySelector('[data-active-count]');
            if (el && root) {
                el.textContent = root.getAttribute('data-active-count') || '0';
            }
        },

        async onSubmit(event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            event.preventDefault();
            if (this.busy) return;
            this.busy = true;
            try {
                const body = await todoSubmitFetch(form.action, new FormData(form));
                if (window.CB) window.CB.toast('success', (body && body.message) || 'Saved.');
                await this.refresh();
            } catch (err) {
                if (window.CB) window.CB.toast('error', err.message || 'Request failed.');
                await this.refresh();
            } finally {
                this.busy = false;
            }
        },

        async onDelete(detail) {
            if (!detail || !detail.url) return;

            let ok = false;
            if (window.CB) {
                ok = await window.CB.confirm({ type: 'danger', title: 'Delete this task permanently?' });
            } else {
                ok = window.confirm('Delete this task permanently?');
            }
            if (!ok) return;

            const fd = new FormData();
            fd.append('_token', csrfToken());
            fd.append('_method', 'DELETE');
            try {
                const body = await todoSubmitFetch(detail.url, fd);
                if (window.CB) window.CB.toast('success', (body && body.message) || 'Task deleted.');
                await this.refresh();
            } catch (err) {
                if (window.CB) window.CB.toast('error', err.message || 'Could not delete the task.');
            }
        },
    }));
});
