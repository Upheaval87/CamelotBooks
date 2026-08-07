<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-list-header title="{{ __('General Ledger') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.general-ledger.export-csv', request()->query()) }}">{{ __('Export CSV') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.general-ledger.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="account_id" value="{{ __('Account') }}" />
                        <x-scoped-search-field
                            name="account_id"
                            entity="account"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                            :value="request('account_id')"
                            :label="request('account_id') ? ($accounts->firstWhere('id', (int) request('account_id'))?->name ?? '') : ''"
                            placeholder="{{ __('Search accounts...') }}"
                        />
                    </div>
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
                        @if(request('account_id') || request('date_from') || request('date_to') || request('branch_id'))
                            <a href="{{ route('accounting.general-ledger.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
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
                                <th>Journal #</th>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="text-right">Debit ({{ $cs }})</th>
                                <th class="text-right">Credit ({{ $cs }})</th>
                                <th class="text-right">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($glPaginator as $row)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        {{ $row['line']->journalEntry->date->format('M d, Y') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.journal-entries.show', $row['line']->journalEntry) }}" class="text-ink hover:text-gold">
                                            {{ $row['line']->journalEntry->journal_number }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $row['line']->account->code }} - {{ $row['line']->account->name }}
                                    </td>
                                    <td class="text-ink-soft max-w-xs truncate">
                                        {{ $row['line']->memo ?? $row['line']->journalEntry->memo ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ (float) $row['line']->debit > 0 ? format_number((float) $row['line']->debit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ (float) $row['line']->credit > 0 ? format_number((float) $row['line']->credit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($row['running_balance']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No journal entry lines found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $glPaginator->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
