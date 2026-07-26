<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cheque Register') }}</h2>
            <a href="{{ route('accounting.cheques.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back to Cheques') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" action="{{ route('accounting.cheques.register') }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Bank Account</label>
                        <select name="bank_account_id" class="mt-1 block w-48 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All Accounts</option>
                            @foreach($bankAccounts as $account)
                                <option value="{{ $account->id }}" {{ ($bankAccountId ?? '') == $account->id ? 'selected' : '' }}>
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cheque #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payee</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Memo</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($cheques as $cheque)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('accounting.cheques.show', $cheque->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ str_pad($cheque->cheque_number, 6, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $cheque->date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $cheque->bankAccount->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $cheque->payee }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $cheque->memo ?? '—' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ format_money($cheque->amount) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @if($cheque->status === 'outstanding')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Outstanding</span>
                                        @elseif($cheque->status === 'cleared')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Cleared</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Void</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-sm text-gray-500">No cheques found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
