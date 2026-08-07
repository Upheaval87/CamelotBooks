<x-app-layout>

    <x-superadmin.layout>
        <x-superadmin.page-head title="{{ __('Currencies') }}" description="{{ __('Reference currencies available to new companies.') }}">
            <a href="{{ route('superadmin.currencies.create') }}" class="inline-flex items-center gap-2 rounded-[12px] border border-white/20 bg-gradient-to-b from-gold-500 to-gold-600 px-5 py-3 text-sm font-semibold text-white shadow-new transition hover:-translate-y-px focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('New Currency') }}
            </a>
        </x-superadmin.page-head>

        <x-elevated-card :flush="true">
            <div class="sa-table-wrap">
                <table class="sa-table">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Symbol') }}</th>
                            <th class="sa-table-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currencies as $currency)
                            <tr>
                                <td>
                                    <a href="{{ route('superadmin.currencies.edit', $currency) }}" class="sa-table-primary">{{ $currency->code }}</a>
                                </td>
                                <td>{{ $currency->name }}</td>
                                <td><span class="sa-table-mono">{{ $currency->symbol }}</span></td>
                                <td class="sa-table-center">
                                    @if($currency->is_active)
                                        <span class="sa-pill sa-pill--accent">{{ __('Active') }}</span>
                                    @else
                                        <span class="sa-pill sa-pill--muted">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="sa-table-empty">{{ __('No currencies yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-elevated-card>
    </x-superadmin.layout>

</x-app-layout>
