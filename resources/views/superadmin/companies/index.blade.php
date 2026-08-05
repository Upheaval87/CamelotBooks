<x-app-layout>
    <x-slot name="header">{{ __('Companies') }}</x-slot>

    @include('superadmin._nav', ['active' => 'companies'])

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="list-header">
                <h1 class="font-sans italic font-semibold tracking-tight text-ink text-[1.125rem] lg:text-[1.375rem]">{{ __('All Companies') }}</h1>
                <a href="{{ route('superadmin.companies.create') }}" class="list-header-create">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('New Company') }}
                </a>
            </div>

            <div class="card p-6">
                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Database') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Active Modules') }}</th>
                                <th class="text-right">{{ __('Users') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($companies as $company)
                                <tr>
                                    <td>
                                        <a href="{{ route('superadmin.companies.show', $company) }}" class="font-medium text-ink">{{ $company->name }}</a>
                                        <span class="block text-xs text-gray-500">{{ $company->base_currency }}</span>
                                    </td>
                                    <td class="font-mono text-xs">{{ $company->company_code ?? '—' }}</td>
                                    <td>
                                        @if($company->db_name)
                                            <code class="font-mono text-xs">{{ $company->db_name }}</code>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1 flex-wrap">
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
                                    </td>
                                    <td class="text-right font-semibold text-ink">{{ $company->active_modules_count }}</td>
                                    <td class="text-right font-semibold text-ink">{{ $company->assignment_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-500">{{ __('No companies yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
