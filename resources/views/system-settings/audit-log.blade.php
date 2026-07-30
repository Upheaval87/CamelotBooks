<x-app-layout>
    <x-slot name="header">System Settings</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="settings-layout">
                <div class="settings-layout-sidebar">
                    <x-settings.sidebar activeTab="audit-log" :groups="[['company','regional','currency','accounts','accounting','approval'],['notifications','data-hub','import-export','backups'],['audit-log']]" />
                </div>

                <div class="settings-layout-content">
                    <div class="settings-section-header">
                        <div class="settings-section-eyebrow">SETTINGS CHANGE HISTORY</div>
                        <div class="settings-section-title">Settings Change History</div>
                        <p class="settings-section-desc">Track all changes made to system settings for this company.</p>
                        <hr class="settings-section-divider">
                    </div>

                    {{-- Filters --}}
                    <div class="settings-card">
                        <form method="GET" action="{{ route('system-settings.audit-log') }}" class="settings-grid">
                            <x-settings.field label="Settings Group" name="group" type="select" value="">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g }}" {{ request('group') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </x-settings.field>
                            <x-settings.field label="User" name="user_id" type="select" value="">
                                <option value="">All Users</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </x-settings.field>
                            <x-settings.field label="From" name="from" type="date" value="{{ request('from') }}" />
                            <x-settings.field label="To" name="to" type="date" value="{{ request('to') }}" />
                            <div class="flex items-end gap-2">
                                <button type="submit" class="btn-primary">Filter</button>
                                @if(request()->hasAny(['group', 'user_id', 'from', 'to']))
                                    <a href="{{ route('system-settings.audit-log') }}" class="settings-pill-btn">Clear</a>
                                @endif
                            </div>
                        </form>
                    </div>

                    {{-- Log table --}}
                    <div class="settings-card">
                        <div class="settings-table-wrapper">
                            <table class="settings-table">
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
                                        <td class="text-ink-soft">{{ $log->created_at?->format('M d, Y H:i:s') ?? '-' }}</td>
                                        <td>{{ $log->user?->name ?? 'System' }}</td>
                                        <td>
                                            <span class="status-pill neutral">{{ $log->notes ?? 'Settings' }}</span>
                                        </td>
                                        <td class="max-w-lg">
                                            @if($log->notes === 'Account Mappings' && is_array($log->new_values))
                                                <div class="space-y-1">
                                                    @foreach($log->new_values as $key => $change)
                                                        <div class="text-xs">
                                                            <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                            @if(is_array($change))
                                                                <span class="settings-diff-old">{{ $change['from'] ?? '—' }}</span>
                                                                <span class="settings-diff-arrow">&rarr;</span>
                                                                <span class="settings-diff-new">{{ $change['to'] ?? '—' }}</span>
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
                                                            @if(is_array($newVal))
                                                                <span class="settings-diff-old">{{ is_array($log->old_values[$key] ?? '—') ? json_encode($log->old_values[$key]) : ($log->old_values[$key] ?? '—') }}</span>
                                                                <span class="settings-diff-arrow">&rarr;</span>
                                                                <span class="settings-diff-new">{{ json_encode($newVal) }}</span>
                                                            @else
                                                                <span class="settings-diff-old">{{ $log->old_values[$key] ?? '—' }}</span>
                                                                <span class="settings-diff-arrow">&rarr;</span>
                                                                <span class="settings-diff-new">{{ $newVal }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                {{ $log->notes ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="text-ink-soft">{{ $log->ip_address ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="settings-table-empty">No settings changes recorded yet.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($logs, 'links'))
                        <div class="mt-4">{{ $logs->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
