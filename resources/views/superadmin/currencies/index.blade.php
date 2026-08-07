<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Currencies') }}" description="{{ __('Reference currencies available to new companies.') }}">
            <x-superadmin.btn href="{{ route('superadmin.currencies.create') }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Currency') }}
            </x-superadmin.btn>
        </x-superadmin.page-head>

        <x-superadmin.card>
            <div class="overflow-x-auto rounded-[12px] border border-shell bg-row">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr>
                            <x-superadmin.th>{{ __('Code') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Name') }}</x-superadmin.th>
                            <x-superadmin.th>{{ __('Symbol') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Status') }}</x-superadmin.th>
                            <x-superadmin.th align="center">{{ __('Actions') }}</x-superadmin.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse($currencies as $currency)
                            <tr>
                                <td class="px-5 py-[18px] align-middle">
                                    <a href="{{ route('superadmin.currencies.edit', $currency) }}" class="font-bold text-gray-900">{{ $currency->code }}</a>
                                </td>
                                <td class="px-5 py-[18px] align-middle text-gray-600">{{ $currency->name }}</td>
                                <td class="px-5 py-[18px] align-middle">
                                    <code class="rounded-md border border-slate-200 bg-slate-100 px-2 py-[3px] font-mono text-xs text-slate-600">{{ $currency->symbol }}</code>
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    @if($currency->is_active)
                                        <x-superadmin.badge variant="active">{{ __('Active') }}</x-superadmin.badge>
                                    @else
                                        <x-superadmin.badge variant="muted">{{ __('Inactive') }}</x-superadmin.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-[18px] text-center align-middle">
                                    <a href="{{ route('superadmin.currencies.edit', $currency) }}" class="inline-flex items-center gap-1.5 rounded-[10px] border border-gold-600/35 bg-gradient-to-b from-[#fffdf8] to-[#f7f0df] px-4 py-2 text-[13px] font-bold text-gold-700 shadow-edit transition hover:-translate-y-px hover:border-gold-600/55 hover:text-gold-800 hover:shadow-edit-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        {{ __('Edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-[18px] text-center align-middle text-gray-400">{{ __('No currencies yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-superadmin.card>
    </x-superadmin.layout>

</x-app-layout>
