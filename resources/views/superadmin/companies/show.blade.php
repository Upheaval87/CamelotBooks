<x-app-layout>
    <x-slot name="header">{{ __('Company Detail') }} - {{ $company->name }}</x-slot>

    @include('superadmin._nav', ['active' => 'companies'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-lg font-semibold text-ink">{{ $company->name }}</span>
                        @if(!$company->is_active)
                            <x-status-badge variant="danger">Suspended</x-status-badge>
                        @endif
                        @php
                            $badge = match ($company->provisioning_status) {
                                'active' => 'success',
                                'pending', 'provisioning' => 'warning',
                                'failed' => 'danger',
                                default => 'default',
                            };
                        @endphp
                        <x-status-badge :variant="$badge">{{ ucfirst($company->provisioning_status) }}</x-status-badge>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('superadmin.companies.modules', $company) }}" class="btn-ghost">{{ __('Manage Modules') }}</a>
                        @if($company->is_active && $company->db_name)
                            <form method="POST" action="{{ route('companies.select', $company->id) }}">
                                @csrf
                                <x-button variant="primary" type="submit" title="{{ __('Enter this company as support access. Every entry is logged with a start/end time.') }}">
                                    {{ __('Enter Company (Support)') }}
                                </x-button>
                            </form>
                        @endif
                        @if($company->is_active)
                            <form method="POST" action="{{ route('superadmin.companies.suspend', $company) }}" onsubmit="return confirm('{{ __('Suspend this company? Users will lose access immediately.') }}')">
                                @csrf
                                <x-button variant="danger" type="submit">{{ __('Suspend') }}</x-button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('superadmin.companies.reactivate', $company) }}">
                                @csrf
                                <x-button variant="primary" type="submit">{{ __('Reactivate') }}</x-button>
                            </form>
                        @endif
                    </div>
                    @if($company->last_provisioning_error)
                    <div class="mt-4 rounded-lg border border-brick bg-brick-soft px-4 py-3 text-sm text-brick">
                        <p class="font-semibold">{{ __('Last provisioning error') }}</p>
                        <p class="mt-1 font-mono text-xs break-all">{{ $company->last_provisioning_error }}</p>
                    </div>
                @endif

                <div class="detail-grid mt-6">
                    <x-detail-field label="Company Code">{{ $company->company_code ?? '—' }}</x-detail-field>
                    <x-detail-field label="Legal Name">{{ $company->legal_name ?? '—' }}</x-detail-field>
                    <x-detail-field label="Base Currency">{{ $company->base_currency }}</x-detail-field>
                    <x-detail-field label="Tax ID">{{ $company->tax_id ?? '—' }}</x-detail-field>
                    <x-detail-field label="Fiscal Year Start">{{ \Carbon\Carbon::create()->month($company->fiscal_year_start_month)->format('F') }}</x-detail-field>
                    <x-detail-field label="Email">{{ $company->email ?? '—' }}</x-detail-field>
                    <x-detail-field label="Phone">{{ $company->phone ?? '—' }}</x-detail-field>
                    <x-detail-field label="Address">{{ $company->address ?? '—' }}</x-detail-field>
                    <x-detail-field label="City">{{ $company->city ?? '—' }}</x-detail-field>
                    <x-detail-field label="Country">{{ $company->country ?? '—' }}</x-detail-field>
                    <x-detail-field label="Database Name">
                        @if($company->db_name)
                            <code class="font-mono text-xs">{{ $company->db_name }}</code>
                        @else
                            —
                        @endif
                    </x-detail-field>
                    <x-detail-field label="Provisioned At">{{ $company->provisioned_at?->format('M j, Y g:i A') ?? '—' }}</x-detail-field>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Assigned Users') }}</h3>
                        <a href="{{ route('superadmin.assignments.create') }}" class="text-sm text-accent hover:underline">{{ __('Assign a user') }}</a>
                    </div>
                    <div class="list-table-wrap">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th class="text-right">{{ __('Branches') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <span class="font-medium text-ink">{{ $assignment->user->name }}</span>
                                            <span class="block text-xs text-gray-500">{{ $assignment->user->email }}</span>
                                        </td>
                                        <td>{{ $assignment->role }}</td>
                                        <td class="text-right">{{ count($assignment->branch_ids) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-gray-500">{{ __('No users assigned.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-ink">{{ __('Active Modules') }}</h3>
                        <a href="{{ route('superadmin.companies.modules', $company) }}" class="text-sm text-accent hover:underline">{{ __('Manage') }}</a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @forelse($modules->filter(fn ($m) => ($moduleStates[$m->id]?->is_active ?? false) || $m->is_core) as $module)
                            <x-status-badge variant="success">{{ $module->name }}</x-status-badge>
                        @empty
                            <p class="text-sm text-gray-500">{{ __('No modules enabled.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">{{ __('Support Access Sessions') }}</h3>
                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Admin') }}</th>
                                <th>{{ __('Started') }}</th>
                                <th>{{ __('Ended') }}</th>
                                <th>{{ __('Duration') }}</th>
                                <th>{{ __('End Reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supportSessions as $session)
                                <tr>
                                    <td>
                                        <span class="font-medium text-ink">{{ $session->user?->name ?? '—' }}</span>
                                        <span class="block text-xs text-gray-500">{{ $session->user?->email }}</span>
                                    </td>
                                    <td class="text-gray-500">{{ $session->started_at->format('M j, Y g:i A') }}</td>
                                    <td class="text-gray-500">{{ $session->ended_at?->format('M j, Y g:i A') ?? __('Ongoing') }}</td>
                                    <td>{{ $session->duration }}</td>
                                    <td>
                                        @if($session->ended_reason)
                                            <code class="font-sans text-xs text-ink">{{ $session->ended_reason }}</code>
                                        @else
                                            <x-status-badge variant="warning">Ongoing</x-status-badge>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-500">{{ __('No support access recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-6">
                <h3 class="text-sm font-semibold text-ink mb-4">{{ __('Recent Activity') }}</h3>
                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('When') }}</th>
                                <th>{{ __('Actor') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Description') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($audit as $log)
                                <tr>
                                    <td class="text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $log->user?->name ?? '—' }}</td>
                                    <td><code class="font-sans text-xs text-ink">{{ $log->action }}</code></td>
                                    <td class="text-gray-600">{{ $log->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-500">{{ __('No activity yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
