{{-- Audit Log tab is rendered via a separate Blade view, included from the tab nav --}}

<div class="card">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="form-section-label">1 · Settings Change History</div>
        <p class="mt-1 text-sm text-ink-soft">Track all changes made to system settings for this company.</p>
    </div>

    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <form method="GET" action="{{ route('system-settings.audit-log') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Settings Group</label>
                <select name="group" class="input mt-1">
                    <option value="">All Groups</option>
                    @foreach($groups as $g)
                        <option value="{{ $g }}" {{ request('group') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">User</label>
                <select name="user_id" class="input mt-1">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="input">
            </div>
            <div class="flex items-end gap-2">
                <x-button variant="primary" type="submit">Filter</x-button>
                @if(request()->hasAny(['group', 'user_id', 'from', 'to']))
                    <x-button variant="ghost" href="{{ route('system-settings.audit-log') }}">Clear</x-button>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="datasheet">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Settings Group</th>
                    <th>Changes</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-ink-soft">
                        {{ $log->created_at?->format('M d, Y H:i:s') ?? '-' }}
                    </td>
                    <td>
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ $log->notes ?? 'Settings' }}
                        </span>
                    </td>
                    <td class="text-ink-soft max-w-lg">
                        @if($log->notes === 'Account Mappings' && is_array($log->new_values))
                            <div class="space-y-1">
                                @foreach($log->new_values as $key => $change)
                                    <div class="text-xs">
                                        <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                        @if(is_array($change))
                                            <span class="text-red-600 line-through">{{ $change['from'] ?? '—' }}</span>
                                            <span class="text-gray-400 mx-1">&rarr;</span>
                                            <span class="text-green-600">{{ $change['to'] ?? '—' }}</span>
                                        @else
                                            <span>{{ $change }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @elseif(is_array($log->new_values))
                            <div class="space-y-1">
                                @foreach($log->new_values as $key => $newVal)
                                    <div class="text-xs">
                                        <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                        <span class="text-red-600 line-through">{{ $log->old_values[$key] ?? '—' }}</span>
                                        <span class="text-gray-400 mx-1">&rarr;</span>
                                        <span class="text-green-600">{{ $newVal }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            {{ $log->notes ?? '-' }}
                        @endif
                    </td>
                    <td class="text-ink-soft">
                        {{ $log->ip_address ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-ink-soft text-center">
                        No settings changes recorded yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $logs->links() }}
    </div>
</div>
