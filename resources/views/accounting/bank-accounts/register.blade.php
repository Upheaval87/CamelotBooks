<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Bank Register') }} — {{ $bankAccount->name }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.bank-accounts.manual-form', $bankAccount->id) }}">{{ __('Manual Transaction') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}">{{ __('Reconcile') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.bank-accounts.index') }}">{{ __('Back to Accounts') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="from_date" value="{{ __('From Date') }}" />
                        <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full" :value="request('from_date')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="to_date" value="{{ __('To Date') }}" />
                        <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full" :value="request('to_date')" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('from_date') || request('to_date'))
                            <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th class="text-right">Debit ({{ $cs }})</th>
                                <th class="text-right">Credit ({{ $cs }})</th>
                                <th class="text-right">Balance</th>
                                <th class="text-center">Cleared</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                <tr>
                                    <td>
                                        {{ $transaction->transaction_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td>
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $transaction->reference ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $transaction->debit > 0 ? format_number($transaction->debit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $transaction->credit > 0 ? format_number($transaction->credit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($transaction->running_balance) }}
                                    </td>
                                    <td class="text-center">
                                        @if($transaction->is_cleared)
                                            <span class="status-pill positive">Cleared</span>
                                        @else
                                            <span class="status-pill neutral">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
