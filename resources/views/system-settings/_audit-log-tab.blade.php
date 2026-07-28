{{-- Audit Log tab is rendered via a separate Blade view, included from the tab nav --}}

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Settings Change History</h3>
        <p class="mt-1 text-sm text-gray-600">Track all changes made to system settings for this company.</p>
    </div>

    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <form method="GET" action="{{ route('system-settings.audit-log') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Settings Group</label>
                <select name="group" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Groups</option>
                    @foreach($groups as $g)
                        <option value="{{ $g }}" {{ request('group') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">User</label>
                <select name="user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                    Filter
                </button>
                @if(request()->hasAny(['group', 'user_id', 'from', 'to']))
                    <a href="{{ route('system-settings.audit-log') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Settings Group</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->created_at?->format('M d, Y H:i:s') ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ $log->notes ?? 'Settings' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-lg">
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->ip_address ?? '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
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
