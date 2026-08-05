<x-app-layout>
    <x-slot name="header">{{ __('Super Admin') }}</x-slot>

    @include('superadmin._nav', ['active' => 'dashboard'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card p-6">
                    <p class="kpi-label">{{ __('Companies') }}</p>
                    <p class="kpi-value">{{ $companyCount }}</p>
                    <p class="text-sm text-gray-500">{{ $activeCompanyCount }} {{ __('active') }}</p>
                </div>

                <div class="card p-6">
                    <p class="kpi-label">{{ __('Platform Users') }}</p>
                    <p class="kpi-value">{{ $userCount }}</p>
                    <p class="text-sm text-gray-500">{{ __('Across all companies') }}</p>
                </div>

                <div class="card p-6">
                    <p class="kpi-label">{{ __('Modules') }}</p>
                    <p class="kpi-value">{{ \App\Models\Module::query()->count() }}</p>
                    <p class="text-sm text-gray-500">{{ __('Activation is per company') }}</p>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">{{ __('Recent Super Admin Activity') }}</h3>
                <div class="list-table-wrap">
                    <table class="list-table">
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
                            @forelse($recentAudit as $log)
                                <tr>
                                    <td class="text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td>{{ $log->company?->name ?? '—' }}</td>
                                    <td><code class="font-sans text-xs text-ink">{{ $log->action }}</code></td>
                                    <td class="text-gray-600">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">{{ __('No activity yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
