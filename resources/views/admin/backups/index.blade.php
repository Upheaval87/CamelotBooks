<x-app-layout>
    <x-list-header title="{{ __('Backup Management') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 ss-suite">

            <div class="sticky-head">
                @include('system-settings._tabnav', ['active' => 'backups'])
                <div>
                    <div class="glabel">{{ __('Actions') }}</div>
                    <div class="tbtns">
                        <form method="POST" action="{{ route('admin.backups.trigger') }}" id="trigger-form">
                            @csrf
                        </form>
                        <button type="submit" form="trigger-form" class="btn cta"
                            onclick="return fbConfirmButton(event, 'Trigger a database backup now? This may take a moment for large databases.', { type: 'action' })">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Trigger Backup Now') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></span>
                        <h2>{{ __('Database Backups') }}</h2>
                        <div class="rule"></div>
                    </div>
                    <p class="sub">Full mysqldump backups of the entire database.</p>

                    <x-settings.callout variant="warn" class="mb-4">
                        <strong>Restore is a Manual Procedure.</strong> Database restores must be performed by a server administrator — see the restore runbook. A running web application should not restore the very database it depends on mid-request.
                    </x-settings.callout>

                    <div class="li-wrap">
                        <table>
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
                                    <td class="mono">{{ $backup->created_at->format('M d, Y H:i:s') }}</td>
                                    <td class="mono">{{ $backup->filename }}</td>
                                    <td>{{ $backup->file_size_human }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($backup->status) {
                                                'success' => 'b-act',
                                                'running' => 'b-gray',
                                                default => 'b-red',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}"><span class="bdot"></span>{{ ucfirst($backup->status) }}</span>
                                        @if($backup->error_message)
                                            <p class="exit-code">{{ Str::limit($backup->error_message, 50) }}</p>
                                            <p class="exit-code">Exit code: {{ $backup->error_message }}</p>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($backup->triggered_by) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="empty">No database backups have been triggered yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($backups, 'links'))
                    <div class="mt-4">{{ $backups->links() }}</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></span>
                        <h2>{{ __('Settings Snapshots') }}</h2>
                        <div class="rule"></div>
                    </div>
                    <p class="sub">Point-in-time snapshots of system settings. Restore to revert settings to a previous state.</p>

                    <div class="card" style="box-shadow: none; border: 1px solid var(--line, #E2ECEC);">
                        <div class="card-sec">
                            <h2 class="glabel" style="margin-bottom: 8px;">{{ __('Create Snapshot') }}</h2>
                            <form method="POST" action="{{ route('admin.backups.create-snapshot') }}" id="snapshot-form">
                                @csrf
                                <div class="g3">
                                    <x-settings.field label="Snapshot Label" name="label" type="text" required value="Backup {{ now()->format('M d, Y H:i') }}" />
                                    <x-settings.field label="Notes (optional)" name="notes" type="text" placeholder="Before making changes to accounting settings..." />
                                </div>
                                <div class="tbtns" style="margin-top: 16px;">
                                    <button type="submit" class="btn sec">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                        {{ __('Create Snapshot Now') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Created By</th>
                                    <th>Date</th>
                                    <th>Settings</th>
                                    <th>Notes</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($snapshots as $snapshot)
                                <tr>
                                    <td class="mono">{{ $snapshot->label }}</td>
                                    <td>{{ $snapshot->createdByUser?->name ?? 'System' }}</td>
                                    <td class="mono">{{ $snapshot->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $snapshot->record_count }} settings</td>
                                    <td class="em">{{ $snapshot->notes ?? '—' }}</td>
                                    <td>
                                        <div class="tbtns" style="justify-content: flex-end;">
                                            <form method="POST" action="{{ route('admin.backups.restore-snapshot', $snapshot) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Restore settings from this snapshot? Current settings will be overwritten.', { type: 'danger' })">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn danger-o sm">Restore</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.backups.delete-snapshot', $snapshot) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Delete this snapshot permanently?', { type: 'danger' })">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn danger-o sm">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="empty">No settings snapshots created yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
