<x-app-layout>

    <div class="sa-page py-6" style="background: #F8F9FC;">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Overview') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Platform health at a glance.') }}</p>
                </div>
                <a href="{{ route('superadmin.companies.create') }}" class="sa-btn sa-btn--primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New Company') }}
                </a>
            </div>

            <div class="sa-kpi-grid grid gap-4 sm:grid-cols-3">
                <x-elevated-card>
                    <p class="kpi-label">{{ __('Companies') }}</p>
                    <p class="kpi-value">{{ $companyCount }}</p>
                    <p class="text-sm" style="color: var(--sa-muted);">{{ $activeCompanyCount }} {{ __('active') }}</p>
                </x-elevated-card>

                <x-elevated-card>
                    <p class="kpi-label">{{ __('Platform Users') }}</p>
                    <p class="kpi-value">{{ $userCount }}</p>
                    <p class="text-sm" style="color: var(--sa-muted);">{{ __('Across all companies') }}</p>
                </x-elevated-card>

                <x-elevated-card>
                    <p class="kpi-label">{{ __('Modules') }}</p>
                    <p class="kpi-value">{{ \App\Models\Module::query()->count() }}</p>
                    <p class="text-sm" style="color: var(--sa-muted);">{{ __('Activation is per company') }}</p>
                </x-elevated-card>
            </div>

            <x-elevated-card :flush="true" class="mt-8">
                <div class="sa-card-head">
                    <h2 class="sa-card-title">{{ __('Recent Super Admin Activity') }}</h2>
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
                            @forelse($recentAudit as $log)
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

                @if($recentAudit->hasPages())
                    <div class="sa-table-pagination">{{ $recentAudit->links() }}</div>
                @endif
            </x-elevated-card>
        </div>
    </div>
</x-app-layout>
