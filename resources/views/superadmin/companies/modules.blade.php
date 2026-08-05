<x-app-layout>
    <x-slot name="header">{{ __('Modules') }} - {{ $company->name }}</x-slot>

    @include('superadmin._nav', ['active' => 'companies'])

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-sm font-semibold text-ink">{{ __('Module Activation') }}</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ __('Activation is controlled here — it is the single source of truth. Tenant-side feature settings are read-only.') }}
                        </p>
                    </div>
                    <a href="{{ route('superadmin.companies.show', $company) }}" class="btn-ghost">{{ __('Back to Company') }}</a>
                </div>

                <div class="list-table-wrap">
                    <table class="list-table">
                        <thead>
                            <tr>
                                <th>{{ __('Module') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                                @php
                                    $isActive = (bool) ($moduleStates[$module->id]?->is_active ?? false);
                                    $effectiveActive = $module->is_core || $isActive;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="font-medium text-ink">{{ $module->name }}</span>
                                        @if($module->is_core)
                                            <x-status-badge variant="default" class="ml-1">Core</x-status-badge>
                                        @endif
                                    </td>
                                    <td class="text-gray-600">{{ $module->description ?? '—' }}</td>
                                    <td>
                                        <x-status-badge :variant="$effectiveActive ? 'success' : 'default'">
                                            {{ $effectiveActive ? 'Enabled' : 'Disabled' }}
                                        </x-status-badge>
                                    </td>
                                    <td class="text-right">
                                        @if($module->is_core)
                                            <span class="text-xs text-gray-400">{{ __('Always on') }}</span>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.companies.modules.toggle', [$company, $module]) }}">
                                                @csrf
                                                <x-button variant="ghost" type="submit" class="btn-sm">
                                                    {{ $isActive ? __('Disable') : __('Enable') }}
                                                </x-button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
