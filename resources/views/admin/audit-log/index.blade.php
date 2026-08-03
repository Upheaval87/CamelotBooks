<x-app-layout>
    <x-slot name="header">{{ __('Audit Log') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-toolbar class="mb-6">
                <form method="GET" action="{{ route('admin.audit-log.index') }}" class="flex items-center gap-2 w-full">
                    <x-scoped-search-field
                        name="user_id"
                        entity="user"
                        search-url="{{ route('accounting.search.entity', ['entity' => 'user']) }}"
                        :value="request('user_id')"
                        :label="request('user_id') ? ($users->firstWhere('id', (int) request('user_id'))?->name ?? '') : ''"
                        placeholder="{{ __('Search users...') }}"
                    />
                    <input type="date" name="from" value="{{ request('from') }}" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent w-36" placeholder="From" />
                    <span class="text-atlas-navy/40 text-sm">to</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent w-36" placeholder="To" />
                    <select name="action" class="border border-gray-200 rounded-md px-2 py-1.5 text-sm text-atlas-navy focus:outline-none focus:ring-2 focus:ring-atlas-blue focus:border-transparent">
                        <option value="">All Actions</option>
                        <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="posted" {{ request('action') === 'posted' ? 'selected' : '' }}>Posted</option>
                        <option value="voided" {{ request('action') === 'voided' ? 'selected' : '' }}>Voided</option>
                        <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                        <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                        <option value="login_failed" {{ request('action') === 'login_failed' ? 'selected' : '' }}>Login Failed</option>
                        <option value="password_changed" {{ request('action') === 'password_changed' ? 'selected' : '' }}>Password Changed</option>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-atlas-navy/20 text-atlas-navy text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                    </button>

                    <span class="ml-auto">
                        <a href="{{ route('admin.audit-log.export-csv', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-atlas-navy/20 text-atlas-navy text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </a>
                    </span>
                </form>
            </x-toolbar>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Subject</th>
                                <th>IP Address</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    {{ $log->created_at?->format('M d, Y H:i:s') ?? '-' }}
                                </td>
                                <td>
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td>
                                    @php
                                        $actionClass = match($log->action) {
                                            'posted' => 'positive',
                                            'voided', 'deleted', 'login_failed' => 'negative',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <span class="status-pill {{ $actionClass }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td>
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                </td>
                                <td>
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                                <td>
                                    {{ $log->notes ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No audit log entries found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="datasheet-footer">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
