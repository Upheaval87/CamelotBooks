<x-app-layout>
    @include('superadmin._nav', ['active' => 'companies'])

    <div class="sa-page py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="sa-page-head">
                <div>
                    <h1 class="sa-page-title">{{ __('Companies') }}</h1>
                    <p class="sa-page-subtitle">{{ __('Every tenant provisioned on this platform.') }}</p>
                </div>
                <a href="{{ route('superadmin.companies.create') }}" class="sa-btn sa-btn--primary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('New Company') }}
                </a>
            </div>

            <x-elevated-card :flush="true">
                <div class="sa-table-wrap">
                    <table class="sa-table">
                        <thead>
                            <tr>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Database') }}</th>
                                <th>{{ __('Status') }}</th>
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
                                    <td>
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
        </div>
    </div>
</x-app-layout>
