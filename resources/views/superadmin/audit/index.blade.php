<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Audit Log') }}" description="{{ __('Central platform activity, independent of tenant audit logs.') }}" />

        <x-elevated-card :flush="true">
            <div class="sa-table-filter">
                <form method="GET" action="{{ route('superadmin.audit.index') }}" class="sa-table-filter-form">
                    <select name="company" class="sa-table-filter-input">
                        <option value="">{{ __('All companies') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected(request('company') == $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <select name="action" class="sa-table-filter-input">
                        <option value="">{{ __('All actions') }}</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') == $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="sa-btn sa-btn--ghost">{{ __('Filter') }}</button>
                </form>
            </div>

            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>{{ __('When') }}</th>
                            <th>{{ __('Actor') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="sa-table-mono">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td>{{ $log->company?->name ?? '—' }}</td>
                                <td><span class="sa-table-mono">{{ $log->action }}</span></td>
                                <td style="color: var(--sa-muted);">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="sa-table-empty">{{ __('No activity yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="sa-table-pagination">{{ $logs->links() }}</div>
            @endif
        </x-elevated-card>
    </x-superadmin.layout>

</x-app-layout>
