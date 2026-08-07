<x-app-layout>
    <x-list-header title="{{ __('Write Cheque') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.cheques.create') }}">
                    {{ __('Write Cheque') }}
                </x-button>
            </div>
            

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" action="{{ route('accounting.cheques.index') }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Bank Account</label>
                        <x-scoped-search-field
                            name="bank_account_id"
                            entity="bank-account"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                            :value="$bankAccountId ?? ''"
                            :label="($bankAccountId ?? '') ? ($bankAccounts->firstWhere('id', (int) $bankAccountId)?->name ?? '') : ''"
                            placeholder="{{ __('Search bank accounts...') }}"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">From</label>
                        <input type="date" name="from_date" value="{{ $fromDate ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">To</label>
                        <input type="date" name="to_date" value="{{ $toDate ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        Filter
                    </button>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Cheque #</th>
                                <th>Date</th>
                                <th>Bank Account</th>
                                <th>Payee</th>
                                <th>Memo</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cheques as $cheque)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.cheques.show', $cheque->id) }}" class="text-ink hover:text-gold">
                                            {{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">{{ $cheque->date->format('M d, Y') }}</td>
                                    <td class="text-ink-soft">{{ $cheque->bankAccount->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $cheque->payee }}</td>
                                    <td class="text-ink-soft">{{ $cheque->memo ?? '—' }}</td>
                                    <td class="numeric">{{ format_money($cheque->amount) }}</td>
                                    <td class="text-center">
                                        @if($cheque->status === 'outstanding')
                                            <span class="status-pill neutral">Outstanding</span>
                                        @elseif($cheque->status === 'cleared')
                                            <span class="status-pill positive">Cleared</span>
                                        @else
                                            <span class="status-pill negative">Void</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">No cheques found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
