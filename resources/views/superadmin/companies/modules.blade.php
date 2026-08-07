<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Modules') }}" description="{{ __('Manage which features are enabled for this company.') }}">
            <x-superadmin.btn variant="ghost" href="{{ route('superadmin.companies.show', $company) }}">
                {{ __('Back to Company') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[720px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Module') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Activated') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($modules as $module)
                            <tr class="transition-colors hover:bg-[rgba(36,56,79,.035)]">
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="#modules-{{ $module->id }}" class="font-bold text-gray-900 hover:underline">{{ $module->name }}</a>
                                    <span class="mt-0.5 block text-[12.5px] text-gray-400">{{ $module->code }}</span>
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @if($module->is_core)
                                        <x-superadmin.badge variant="accent">{{ __('Core') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="muted">{{ __('Optional') }}</x-superadmin.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] align-middle">
                                    @php
                                        $enabled = ($module->is_active ?? false) && $module->is_active;
                                    @endphp
                                    @if($enabled)
                                        <x-superadmin.badge variant="active">{{ __('Activated') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="muted">{{ __('Not Activated') }}</x-superadmin.badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-[18px] align-middle text-center text-gray-400">{{ __('No modules found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
