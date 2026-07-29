<x-app-layout>
    <x-slot name="header">{{ __('Backup Management') }}</x-slot>

<div class="py-12">
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md">{{ session('error') }}</div>
        @endif

        {{-- Database Backups --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Database Backups</h1>
                <p class="mt-1 text-sm text-gray-600">Full mysqldump backups of the entire database.</p>
            </div>
            <form method="POST" action="{{ route('admin.backups.trigger') }}" onsubmit="return confirm('Trigger a database backup now? This may take a moment for large databases.')">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Trigger Backup Now') }}</x-button>
            </form>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Restore is a Manual Procedure</h3>
                    <p class="mt-1 text-sm text-yellow-700">Database restores must be performed by a server administrator — see the restore runbook. A running web application should not restore the very database it depends on mid-request.</p>
                </div>
            </div>
        </div>

        <div class="datasheet-wrap mb-10">
            <div class="overflow-x-auto">
                <table class="datasheet">
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
                                <span class="status-pill {{ $statusClass }}">
                                    {{ ucfirst($backup->status) }}
                                </span>
                                @if($backup->error_message)
                                    <p class="mt-1 text-xs text-red-600 max-w-xs truncate">{{ $backup->error_message }}</p>
                                @endif
                            </td>
                            <td>{{ ucfirst($backup->triggered_by) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No database backups have been triggered yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="datasheet-footer">
                {{ $backups->links() }}
            </div>
        </div>

        {{-- Settings Snapshots --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Settings Snapshots</h1>
                <p class="mt-1 text-sm text-gray-600">Point-in-time snapshots of system settings. Restore to revert settings to a previous state.</p>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Create Snapshot</h3>
                <p class="mt-1 text-sm text-gray-600">Save a snapshot of all current system settings.</p>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('admin.backups.create-snapshot') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="snapshot_label" class="block text-sm font-medium text-gray-700">Snapshot Label</label>
                            <input type="text" name="label" id="snapshot_label" required maxlength="255"
                                value="Backup {{ now()->format('M d, Y H:i') }}"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="snapshot_notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                            <input type="text" name="notes" id="snapshot_notes" maxlength="1000"
                                placeholder="Before making changes to accounting settings..."
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Create Snapshot Now
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="datasheet-wrap">
            <div class="overflow-x-auto">
                <table class="datasheet">
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
                                <form method="POST" action="{{ route('admin.backups.restore-snapshot', $snapshot) }}" class="inline" onsubmit="return confirm('Restore settings from this snapshot? Current settings will be overwritten.')">
                                    @csrf
                                    @method('PATCH')
                                    <x-button variant="ghost" type="submit">{{ __('Restore') }}</x-button>
                                </form>
                                <form method="POST" action="{{ route('admin.backups.delete-snapshot', $snapshot) }}" class="inline ml-2" onsubmit="return confirm('Delete this snapshot permanently?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="ghost" type="submit">{{ __('Delete') }}</x-button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No settings snapshots created yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
