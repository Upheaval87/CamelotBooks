<x-app-layout>
    <x-list-header title="{{ __('System Settings') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 ss-suite">

            <div class="sticky-head">
                @include('system-settings._tabnav', ['active' => 'audit-log'])
                <div>
                    <div class="glabel">{{ __('Actions') }}</div>
                    <div class="tbtns">
                        <a href="{{ route('admin.audit-log.export-csv') }}" class="btn ghost">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            {{ __('Export CSV') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></span>
                        <h2>{{ __('Settings Change History') }}</h2>
                        <div class="rule"></div>
                    </div>
                    <p class="sub">Track all changes made to system settings for this company.</p>

                    <form method="GET" action="{{ route('system-settings.audit-log') }}">
                        <div class="g3">
                            <x-settings.field label="Settings Group" name="group" type="select">
                                <option value="">All Groups</option>
                                @foreach($groups as $g)
                                    <option value="{{ $g }}" {{ request('group') === $g ? 'selected' : '' }}>{{ $g }}</option>
                                @endforeach
                            </x-settings.field>
                            <div class="field">
                                <label for="user_id" class="label">User</label>
                                <x-scoped-search-field
                                    name="user_id"
                                    entity="user"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'user']) }}"
                                    :value="request('user_id')"
                                    :label="request('user_id') ? ($users->firstWhere('id', (int) request('user_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('All Users') }}"
                                />
                            </div>
                            <x-settings.field label="From" name="from" type="date" value="{{ request('from') }}" />
                            <x-settings.field label="To" name="to" type="date" value="{{ request('to') }}" />
                        </div>
                        <div class="tbtns" style="margin-top: 16px;">
                            <button type="submit" class="btn sec">{{ __('Filter') }}</button>
                            @if(request()->hasAny(['group', 'user_id', 'from', 'to']))
                                <a href="{{ route('system-settings.audit-log') }}" class="btn ghost">{{ __('Clear') }}</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                        <h2>{{ __('Log Entries') }}</h2>
                        <div class="rule"></div>
                    </div>

                    <div class="li-wrap li-wrap-auto">
                        <table>
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
                                    <td class="mono">{{ $log->created_at?->format('M d, Y H:i:s') ?? '-' }}</td>
                                    <td>{{ $log->user?->name ?? 'System' }}</td>
                                    <td>
                                        <span class="badge b-gray"><span class="bdot"></span>{{ $log->notes ?? 'Settings' }}</span>
                                    </td>
                                    <td>
                                        @if($log->notes === 'Account Mappings' && is_array($log->new_values))
                                            <div class="space-y-1">
                                                @foreach($log->new_values as $key => $change)
                                                    <div class="text-xs">
                                                        <span class="log-key">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                        @if(is_array($change))
                                                            <span class="diff-old">{{ $change['from'] ?? '—' }}</span>
                                                            <span class="diff-arrow">&rarr;</span>
                                                            <span class="diff-new">{{ $change['to'] ?? '—' }}</span>
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
                                                        <span class="log-key">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                                        @if(is_array($newVal))
                                                            <span class="diff-old">{{ is_array($log->old_values[$key] ?? '—') ? json_encode($log->old_values[$key]) : ($log->old_values[$key] ?? '—') }}</span>
                                                            <span class="diff-arrow">&rarr;</span>
                                                            <span class="diff-new">{{ json_encode($newVal) }}</span>
                                                        @else
                                                            <span class="diff-old">{{ $log->old_values[$key] ?? '—' }}</span>
                                                            <span class="diff-arrow">&rarr;</span>
                                                            <span class="diff-new">{{ $newVal }}</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{ $log->notes ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="em">{{ $log->ip_address ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="empty">No settings changes recorded yet.</td>
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
</x-app-layout>
