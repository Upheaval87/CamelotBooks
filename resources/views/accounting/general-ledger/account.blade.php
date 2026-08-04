<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('Account Statement') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.general-ledger.account-export-csv', array_merge(['accountId' => $account->id], request()->query())) }}">{{ __('Export CSV') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.general-ledger.account-export-pdf', array_merge(['accountId' => $account->id], request()->query())) }}" target="_blank">{{ __('Export PDF') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center gap-6 mb-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Account Code:</span>
                        <span class="ml-2 text-sm text-gray-900 font-semibold">{{ $account->code }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Account Name:</span>
                        <span class="ml-2 text-sm text-gray-900 font-semibold">{{ $account->name }}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Type:</span>
                        <span class="ml-2 text-sm text-gray-900">{{ ucfirst($account->type) }}</span>
                    </div>
                </div>

                <form method="GET" action="{{ route('accounting.general-ledger.account', $account) }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="date_from" value="{{ __('Date From') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="date_to" value="{{ __('Date To') }}" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="request('branch_id')"
                            :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                            placeholder="{{ __('All Branches') }}"
                        />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('date_from') || request('date_to') || request('branch_id'))
                            <a href="{{ route('accounting.general-ledger.account', $account) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="mb-4 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-sm font-semibold text-gray-700">
                    Opening Balance: <span class="text-gray-900">{{ format_number($openingBalance) }}</span>
                </div>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal #</th>
                                <th>Branch</th>
                                <th>Description</th>
                                <th class="text-right">Debit ({{ $cs }})</th>
                                <th class="text-right">Credit ({{ $cs }})</th>
                                <th class="text-right">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactionsPaginator as $txn)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        {{ $txn['line']->journalEntry->date->format('M d, Y') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.journal-entries.show', $txn['line']->journalEntry) }}" class="text-ink hover:text-gold">
                                            {{ $txn['line']->journalEntry->journal_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $txn['line']->journalEntry->branch->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft max-w-xs truncate">
                                        {{ $txn['line']->memo ?? $txn['line']->journalEntry->memo ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ (float) $txn['line']->debit > 0 ? format_number((float) $txn['line']->debit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ (float) $txn['line']->credit > 0 ? format_number((float) $txn['line']->credit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($txn['running_balance']) }}
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
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transactionsPaginator->links() }}
                </div>
            </div>

            <div class="mt-4 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-sm font-semibold text-gray-700">
                    Closing Balance: <span class="text-gray-900">{{ format_number($closingBalance) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
