<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Companies') }}" description="{{ __('Every tenant provisioned on this platform.') }}">
            <a href="{{ route('superadmin.companies.create') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/20 bg-gradient-to-b from-gold-500 to-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-new transition hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Company') }}
            </a>
        </x-superadmin.page-head>

        <x-elevated-card :flush="true">
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Database') }}</th>
                            <th class="sa-table-center">{{ __('Status') }}</th>
                            <th>{{ __('Modules') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($companies as $company)
                            @php
                                $status = match ($company->provisioning_status) {
                                    'active' => ['label' => 'Active', 'pill' => 'accent'],
                                    'pending', 'provisioning' => ['label' => 'Provisioning', 'pill' => 'amber'],
                                    'failed' => ['label' => 'Failed', 'pill' => 'danger'],
                                    default => ['label' => ucfirst($company->provisioning_status), 'pill' => 'muted'],
                                };
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('superadmin.companies.show', $company) }}" class="sa-table-primary">{{ $company->name }}</a>
                                    <span class="sa-table-sub">{{ $company->base_currency }}{{ $company->company_code ? ' · ' . $company->company_code : '' }}</span>
                                </td>
                                <td>
                                    @if($company->db_name)
                                        <span class="sa-table-mono">{{ $company->db_name }}</span>
                                    @else
                                        <span style="color: #c8ccd2;">—</span>
                                    @endif
                                </td>
                                <td class="sa-table-center">
                                    <span class="sa-pill sa-pill--{{ $company->is_active ? $status['pill'] : 'danger' }}">
                                        {{ $company->is_active ? $status['label'] : 'Suspended' }}
                                    </span>
                                </td>
                                <td>
                                    @if($company->provisioning_status === 'active')
                                        <span class="sa-table-sub" style="margin-top: 0; color: var(--sa-muted);">{{ $company->active_modules_count }} {{ __('active') }}</span>
                                    @else
                                        <span style="color: #c8ccd2;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sa-table-empty">{{ __('No companies yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-elevated-card>
    </x-superadmin.layout>

</x-app-layout>
