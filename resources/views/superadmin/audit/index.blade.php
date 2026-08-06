<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Audit Log') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Central platform audit trail for super admin actions. This is stored separately from each company\'s own audit log.') }}</p>
                </div>
            </div>

            <div class="card p-6">
                <form method="GET" action="{{ route('superadmin.audit.index') }}" class="grid gap-4 sm:grid-cols-3 mb-6">
                    <div>
                        <label class="sa-label" for="company_id">{{ __('Company') }}</label>
                        <select id="company_id" name="company_id" class="sa-input mt-1 block w-full">
                            <option value="">— {{ __('All companies') }} —</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" @selected((int) request('company_id') === $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sa-label" for="action">{{ __('Action') }}</label>
                        <select id="action" name="action" class="sa-input mt-1 block w-full">
                            <option value="">— {{ __('All actions') }} —</option>
                            @foreach($actions as $action)
                                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="sa-btn sa-btn--ghost">{{ __('Filter') }}</button>
                        @if(request()->has('company_id') || request()->has('action'))
                            <a href="{{ route('superadmin.audit.index') }}" class="sa-btn sa-btn--ghost" style="margin-left: 8px;">{{ __('Clear') }}</a>
                        @endif
                    </div>
                </form>

                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('When') }}</th>
                                <th>{{ __('Actor') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('IP Address') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td class="sa-table-mono">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td>{{ $log->company?->name ?? '—' }}</td>
                                    <td><span class="sa-table-mono">{{ $log->action }}</span></td>
                                    <td>
                                        <span style="color: var(--sa-muted);">{{ $log->description }}</span>
                                        @if($log->after)
                                            <span class="sa-table-mono" style="display: block; margin-top: 4px;">{{ json_encode($log->after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</span>
                                        @endif
                                    </td>
                                    <td class="sa-table-mono">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="sa-table-empty">{{ __('No log entries found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
