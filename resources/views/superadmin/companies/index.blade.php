<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Companies') }}" description="{{ __('Every tenant provisioned on this platform.') }}">
            <x-superadmin.btn href="{{ route('superadmin.companies.create') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Company') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Company') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Database') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Modules') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($companies as $company)
                            @php
                                $status = match ($company->provisioning_status) {
                                    'active' => ['label' => 'Active', 'variant' => 'active'],
                                    'pending', 'provisioning' => ['label' => 'Provisioning', 'variant' => 'warning'],
                                    'failed' => ['label' => 'Failed', 'variant' => 'danger'],
                                    default => ['label' => ucfirst($company->provisioning_status), 'variant' => 'muted'],
                                };
                            @endphp
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.companies.show', $company) }}" class="font-bold text-gray-900">{{ $company->name }}</a>
                                    <span class="mt-1 block text-[12.5px] text-gray-400">{{ $company->base_currency }}{{ $company->company_code ? ' · ' . $company->company_code : '' }}</span>
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($company->db_name)
                                        <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $company->db_name }}</code>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    <x-superadmin.badge :variant="$company->is_active ? $status['variant'] : 'danger'">
                                        {{ $company->is_active ? $status['label'] : 'Suspended' }}
                                    </x-superadmin.badge>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-500">
                                    @if($company->provisioning_status === 'active')
                                        {{ $company->active_modules_count }} {{ __('active') }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No companies yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
