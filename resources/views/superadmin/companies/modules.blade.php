<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Modules') }}" description="{{ __('Manage which features are enabled for this company.') }}">
            <x-superadmin.btn variant="ghost" href="{{ route('superadmin.companies.show', $company) }}">
                {{ __('Back to Company') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[900px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Module') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Type') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Actions') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($modules as $module)
                            @php
                                $state = $moduleStates[$module->id] ?? null;
                                $isActive = (bool) ($state?->is_active ?? false);
                                $effectiveActive = $module->is_core || $isActive;
                            @endphp
                            <tr class="transition-colors hover:bg-[rgba(17,69,75,.035)]">
                                <td class="px-5 py-[18px] align-middle">
                                    <span class="font-bold text-gray-900">{{ $module->name }}</span>
                                    @if($module->description)
                                        <span class="mt-0.5 block max-w-[420px] text-[12.5px] leading-relaxed text-gray-400">{{ $module->description }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($module->is_core)
                                        <x-superadmin.badge variant="accent">{{ __('Core') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="muted">{{ __('Optional') }}</x-superadmin.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($effectiveActive)
                                        <x-superadmin.badge variant="active">{{ __('Activated') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="muted">{{ __('Not Activated') }}</x-superadmin.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    @if($module->is_core)
                                        <span class="inline-flex items-center gap-1.5 rounded-[10px] border border-slate-200 bg-slate-100/70 px-3 py-2 text-[12.5px] font-semibold text-slate-500">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            {{ __('Always on') }}
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('superadmin.companies.modules.toggle', [$company, $module]) }}">
                                            @csrf
                                            @if($isActive)
                                                <x-superadmin.btn variant="ghost" size="md" type="submit">
                                                    {{ __('Disable') }}
                                                </x-superadmin.btn>
                                            @else
                                                <x-superadmin.btn variant="primary" size="md" type="submit">
                                                    {{ __('Enable') }}
                                                </x-superadmin.btn>
                                            @endif
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No modules found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
