<div class="space-y-6">
    {{-- Create Backup --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Create Backup</h3>
            <p class="mt-1 text-sm text-gray-600">Save a snapshot of all current system settings. You can restore from any backup later.</p>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('system-settings.create-backup') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-700">Backup Label</label>
                        <input type="text" name="label" id="label" required maxlength="255"
                            value="Backup {{ now()->format('M d, Y H:i') }}"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                        <input type="text" name="notes" id="notes" maxlength="1000"
                            placeholder="Before making changes to accounting settings..."
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Create Backup Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Backup History --}}
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Backup History</h3>
            <p class="mt-1 text-sm text-gray-600">Previously created settings backups. Restore to revert settings, or delete to remove old snapshots.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Settings</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($backups as $backup)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $backup->label }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $backup->createdByUser?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $backup->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $backup->record_count }} settings</td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $backup->notes ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <form method="POST" action="{{ route('system-settings.restore-backup', $backup) }}" class="inline" onsubmit="return confirm('Restore settings from this backup? Current settings will be overwritten.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Restore</button>
                            </form>
                            <form method="POST" action="{{ route('system-settings.delete-backup', $backup) }}" class="inline" onsubmit="return confirm('Delete this backup permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                            No backups created yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
