<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Overview') }}" description="{{ __('Platform health at a glance.') }}">
            <x-superadmin.btn href="{{ route('superadmin.companies.create') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Company') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-superadmin.card>
                <p class="kpi-label">{{ __('Companies') }}</p>
                <p class="kpi-value">{{ $companyCount }}</p>
                <p class="mt-1 text-[13px] text-gray-500">{{ $activeCompanyCount }} {{ __('active') }}</p>
            </x-superadmin.card>

            <x-superadmin.card>
                <p class="kpi-label">{{ __('Platform Users') }}</p>
                <p class="kpi-value">{{ $userCount }}</p>
                <p class="mt-1 text-[13px] text-gray-500">{{ __('Across all companies') }}</p>
            </x-superadmin.card>

            <x-superadmin.card>
                <p class="kpi-label">{{ __('Modules') }}</p>
                <p class="kpi-value">{{ \App\Models\Module::query()->count() }}</p>
                <p class="mt-1 text-[13px] text-gray-500">{{ __('Activation is per company') }}</p>
            </x-superadmin.card>
        </div>

        <x-superadmin.card title="{{ __('Recent Super Admin Activity') }}" class="mt-6">
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('When') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Actor') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Company') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Action') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Description') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($recentAudit as $log)
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $log->created_at->format('M j, Y g:i A') }}</code>
                                </td>
                                <td class="px-5 py-[18px] align-middle font-semibold text-gray-900">{{ $log->user?->name ?? '—' }}</td>
                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $log->company?->name ?? '—' }}</td>
                                <td class="px-5 py-[18px] align-middle">
                                    <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $log->action }}</code>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-500">{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No activity yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($recentAudit->hasPages())
                <div class="mt-4">{{ $recentAudit->links() }}</div>
            @endif
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
