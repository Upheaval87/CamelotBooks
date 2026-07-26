/**
 * POS Offline Queue
 * Stores pending sales in localStorage when offline, syncs when back online.
 */
const PosOfflineQueue = {
    QUEUE_KEY: 'cb_pos_offline_queue',

    isOnline() {
        return navigator.onLine;
    },

    getQueue() {
        try {
            return JSON.parse(localStorage.getItem(this.QUEUE_KEY) || '[]');
        } catch {
            return [];
        }
    },

    enqueue(sale) {
        const queue = this.getQueue();
        sale._offlineId = 'offline_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);
        sale._queuedAt = new Date().toISOString();
        queue.push(sale);
        localStorage.setItem(this.QUEUE_KEY, JSON.stringify(queue));
        return sale._offlineId;
    },

    removeByOfflineId(offlineId) {
        const queue = this.getQueue().filter((s) => s._offlineId !== offlineId);
        localStorage.setItem(this.QUEUE_KEY, JSON.stringify(queue));
    },

    getCount() {
        return this.getQueue().length;
    },

    async syncAll(csrfToken) {
        const queue = this.getQueue();
        if (queue.length === 0) return { synced: 0, failed: 0 };

        let synced = 0;
        let failed = 0;
        const failures = [];

        for (const sale of queue) {
            try {
                const resp = await fetch('/accounting/pos/sales/sync-offline', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(sale),
                });

                const data = await resp.json();
                if (data.success) {
                    synced++;
                    this.removeByOfflineId(sale._offlineId);
                } else {
                    failed++;
                    failures.push({ offlineId: sale._offlineId, error: data.message });
                }
            } catch {
                failed++;
                failures.push({ offlineId: sale._offlineId, error: 'Network error' });
            }
        }

        return { synced, failed, failures };
    },
};
