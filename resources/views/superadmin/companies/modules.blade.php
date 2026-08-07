<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Modules') }}" description="{{ __('Manage which features are enabled for this company.') }}">
            <a href="{{ route('superadmin.companies.show', $company) }}" class="inline-flex items-center gap-2 rounded-[10px] border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                {{ __('Back to Company') }}
            </a>
        </x-superadmin.page-head>

        <div class="overflow-x-auto rounded-xl border border-shell bg-row">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr>
                        <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-[18px] py-[14px] text-left text-[11px] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Module') }}</th>
                        <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-[18px] py-[14px] text-left text-[11px] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Status') }}</th>
                        <th class="bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 px-[18px] py-[14px] text-left text-[11px] font-semibold uppercase tracking-[0.09em] text-navy-200 shadow-thead">{{ __('Activated') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($modules as $module)
                        <tr class="transition-colors hover:bg-[rgba(36,56,79,.035)]">
                            <td class="px-[18px] py-[14px] align-middle">
                                <a href="#modules-{{ $module->id }}" class="font-bold text-gray-900 hover:underline">{{ $module->name }}</a>
                                <span class="mt-0.5 block text-[12.5px] text-gray-400">{{ $module->code }}</span>
                            </td>
                            <td class="px-[18px] py-[14px] align-middle">
                                @if($module->is_core)
                                    <span class="inline-flex items-center gap-[7px] rounded-full border border-gold-600/30 bg-gradient-to-b from-[#fffdf6] to-[#f6ecd2] px-3 py-1.5 text-xs font-bold text-gold-700 shadow-badge">
                                        {{ __('Core') }}
                                    </span>
                                @else
                                    <span class="sa-pill sa-pill--muted">{{ __('Optional') }}</span>
                                @endif
                            </td>
                            <td class="px-[18px] py-[14px] align-middle">
                                @php
                                    $enabled = ($module->is_active ?? false) && $module->is_active;
                                @endphp
                                @if($enabled)
                                    <span class="inline-flex items-center gap-[7px] rounded-full border border-green-600/30 bg-gradient-to-b from-mint-100 to-mint-200 px-3 py-1.5 text-xs font-bold text-green-700 shadow-badge">
                                        <span class="h-[7px] w-[7px] rounded-full bg-green-500 shadow-[0_0_0_3px_rgba(34,197,94,.18)]"></span>
                                        {{ __('Activated') }}
                                    </span>
                                @else
                                    <span class="sa-pill sa-pill--muted">{{ __('Not Activated') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-[18px] py-[14px] align-middle text-center text-gray-400">{{ __('No modules found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-superadmin.layout>

</x-app-layout>
