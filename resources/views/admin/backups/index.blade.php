<x-app-layout>
    <x-list-header title="{{ __('Backup Management') }}" />

<div class="pb-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        
        

        <div class="settings-layout">
            <div class="settings-layout-sidebar">
                <x-settings.sidebar activeTab="backups" :groups="[['company','regional','currency','accounts','accounting','approval'],['notifications','data-hub','import-export','backups'],['audit-log']]" />
            </div>

            <div class="settings-layout-content">
                <div class="settings-section-header">
                    <div class="settings-section-eyebrow">DATABASE BACKUPS</div>
                    <div class="settings-section-title">Database Backups</div>
                    <p class="settings-section-desc">Full mysqldump backups of the entire database.</p>
                    <hr class="settings-section-divider">
                </div>

                <div class="flex justify-end mb-4">
                    <form method="POST" action="{{ route('admin.backups.trigger') }}" onsubmit="return fbConfirmSubmit(event, 'Trigger a database backup now? This may take a moment for large databases.', { type: 'action' })">
                        @csrf
                        <button type="submit" class="btn-primary">Trigger Backup Now</button>
                    </form>
                </div>

                <x-settings.callout variant="warn" class="mb-6">
                    <strong>Restore is a Manual Procedure.</strong> Database restores must be performed by a server administrator — see the restore runbook. A running web application should not restore the very database it depends on mid-request.
                </x-settings.callout>

                <div class="settings-card">
                    <div class="settings-table-wrapper">
                        <table class="settings-table">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Triggered By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($backups as $backup)
                                <tr>
                                    <td>{{ $backup->created_at->format('M d, Y H:i:s') }}</td>
                                    <td>{{ $backup->filename }}</td>
                                    <td>{{ $backup->file_size_human }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($backup->status) {
                                                'success' => 'positive',
                                                'running' => 'neutral',
                                                default => 'negative',
                                            };
                                        @endphp
                                        <span class="status-pill {{ $statusClass }}">{{ ucfirst($backup->status) }}</span>
                                        @if($backup->error_message)
                                            <p class="settings-exit-code">{{ Str::limit($backup->error_message, 50) }}</p>
                                            <p class="text-xs text-brick">Exit code: {{ $backup->error_message }}</p>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($backup->triggered_by) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="settings-table-empty">No database backups have been triggered yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($backups, 'links'))
                    <div class="mt-4">{{ $backups->links() }}</div>
                    @endif
                </div>

                <div class="settings-section-header mt-8">
                    <div class="settings-section-eyebrow">SETTINGS SNAPSHOTS</div>
                    <div class="settings-section-title">Settings Snapshots</div>
                    <p class="settings-section-desc">Point-in-time snapshots of system settings. Restore to revert settings to a previous state.</p>
                    <hr class="settings-section-divider">
                </div>

                <div class="settings-card">
                    <div class="settings-section-eyebrow mb-2">CREATE SNAPSHOT</div>
                    <p class="settings-section-desc mb-4">Save a snapshot of all current system settings.</p>
                    <form method="POST" action="{{ route('admin.backups.create-snapshot') }}">
                        @csrf
                        <div class="settings-grid">
                            <x-settings.field label="Snapshot Label" name="label" type="text" required value="Backup {{ now()->format('M d, Y H:i') }}" />
                            <x-settings.field label="Notes (optional)" name="notes" type="text" placeholder="Before making changes to accounting settings..." />
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn-primary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                Create Snapshot Now
                            </button>
                        </div>
                    </form>
                </div>

                <div class="settings-card">
                    <div class="settings-table-wrapper">
                        <table class="settings-table">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Settings</th>
                                    <th>Notes</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($snapshots as $snapshot)
                                <tr>
                                    <td>{{ $snapshot->label }}</td>
                                    <td>{{ $snapshot->createdByUser?->name ?? 'System' }}</td>
                                    <td>{{ $snapshot->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $snapshot->record_count }} settings</td>
                                    <td>{{ $snapshot->notes ?? '—' }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('admin.backups.restore-snapshot', $snapshot) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Restore settings from this snapshot? Current settings will be overwritten.', { type: 'danger' })">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="settings-pill-btn">Restore</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.backups.delete-snapshot', $snapshot) }}" class="inline ml-2" onsubmit="return fbConfirmSubmit(event, 'Delete this snapshot permanently?', { type: 'danger' })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="settings-pill-btn" style="border-color: rgba(142,59,59,1); color: rgba(142,59,59,1);">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="settings-table-empty">No settings snapshots created yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
