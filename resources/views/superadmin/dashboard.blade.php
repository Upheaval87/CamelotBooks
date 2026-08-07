<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Overview') }}" description="{{ __('Platform health at a glance.') }}">
            <a href="{{ route('superadmin.companies.create') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/20 bg-gradient-to-b from-gold-500 to-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-new transition hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Company') }}
            </a>
        </x-superadmin.page-head>

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
    </x-superadmin.layout>

</x-app-layout>
