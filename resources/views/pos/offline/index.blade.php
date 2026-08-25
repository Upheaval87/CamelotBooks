<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>Offline Sync</h1>
                    <div class="pos-sub">Manage offline transactions · sync status · queue</div>
                </div>
            </div>

            {{-- Status Cards --}}
            <div class="pos-kpis" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px" x-data="offlineSync()">
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Connection Status</div>
                    <div class="pos-kpi-v" x-text="isOnline ? 'Online' : 'Offline'" :style="isOnline ? 'color:var(--pos-green)' : 'color:var(--pos-red)'"></div>
                    <div class="pos-kpi-n" style="color:#dff7f6" x-text="isOnline ? 'Connected to server' : 'Operating in offline mode'"></div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Queued Transactions</div>
                    <div class="pos-kpi-v" x-text="queueCount">0</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Last Sync</div>
                    <div class="pos-kpi-v" x-text="lastSync" style="font-size:14px">Never</div>
                </div>
            </div>

            {{-- Queue Table --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">Offline Queue</span>
                    <div class="pos-right">
                        <button type="button" @click="syncNow()" class="pos-btn pos-btn-cta pos-btn-sm" :disabled="syncing">
                            <span x-text="syncing ? 'Syncing…' : 'Sync Now'"></span>
                        </button>
                    </div>
                </div>
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Receipt Ref</th>
                                <th>Captured</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="num">Retries</th>
                                <th class="num">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in queue" :key="item.id || idx">
                                <tr>
                                    <td class="pos-mono pos-em" x-text="item.receipt_number || item.id"></td>
                                    <td class="pos-em" x-text="item.captured_at"></td>
                                    <td class="num pos-bold" x-text="item.total"></td>
                                    <td>
                                        <span class="pos-badge" :class="{
                                            'pos-badge-pend': item.status === 'pending',
                                            'pos-badge-open': item.status === 'synced',
                                            'pos-badge-rev': item.status === 'failed'
                                        }">
                                            <span class="pos-bdot"></span>
                                            <span x-text="item.status"></span>
                                        </span>
                                    </td>
                                    <td class="num" x-text="item.retries || 0"></td>
                                    <td class="num">
                                        <div class="pos-row-act">
                                            <button type="button" @click="retryItem(idx)" class="pos-ibtn" title="Retry" x-show="item.status === 'failed'">↻</button>
                                            <button type="button" @click="removeItem(idx)" class="pos-ibtn" title="Remove" x-show="item.status === 'failed'">✕</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="queue.length === 0">
                                <td colspan="6" class="pos-empty">
                                    <h3>No offline transactions</h3>
                                    <p>All sales have been synced to the server.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Info --}}
            <div class="pos-note pos-note-info">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <span>Offline transactions are captured locally and synced when connectivity is restored. Each transaction is assigned a unique ID to prevent duplicates. Queue data persists in your browser's local storage.</span>
            </div>
        </div>
    </div>

    <script>
    function offlineSync() {
        return {
            isOnline: navigator.onLine,
            queueCount: 0,
            lastSync: 'Never',
            queue: [],
            syncing: false,

            init() {
                this.loadQueue();
                window.addEventListener('online', () => { this.isOnline = true; });
                window.addEventListener('offline', () => { this.isOnline = false; });
                const lastSyncTime = localStorage.getItem('pos_last_sync');
                if (lastSyncTime) {
                    this.lastSync = new Date(lastSyncTime).toLocaleString();
                }
            },

            loadQueue() {
                try {
                    const raw = localStorage.getItem('pos_offline_queue');
                    this.queue = raw ? JSON.parse(raw) : [];
                    this.queueCount = this.queue.filter(i => i.status === 'pending').length;
                } catch (e) {
                    this.queue = [];
                    this.queueCount = 0;
                }
            },

            async syncNow() {
                if (!this.isOnline || this.syncing) return;
                this.syncing = true;
                const pending = this.queue.filter(i => i.status === 'pending');

                for (let i = 0; i < pending.length; i++) {
                    const item = pending[i];
                    try {
                        const resp = await fetch('{{ route("pos.sales.sync-offline") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(item)
                        });
                        if (resp.ok) {
                            item.status = 'synced';
                        } else {
                            item.status = 'failed';
                            item.retries = (item.retries || 0) + 1;
                        }
                    } catch (e) {
                        item.status = 'failed';
                        item.retries = (item.retries || 0) + 1;
                    }
                }

                this.syncing = false;
                localStorage.setItem('pos_offline_queue', JSON.stringify(this.queue));
                localStorage.setItem('pos_last_sync', new Date().toISOString());
                this.lastSync = new Date().toLocaleString();
                this.loadQueue();
            },

            retryItem(idx) {
                this.queue[idx].status = 'pending';
                localStorage.setItem('pos_offline_queue', JSON.stringify(this.queue));
                this.loadQueue();
            },

            removeItem(idx) {
                this.queue.splice(idx, 1);
                localStorage.setItem('pos_offline_queue', JSON.stringify(this.queue));
                this.loadQueue();
            }
        };
    }
    </script>
</x-app-layout>
