<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Reconciliation') }} — {{ $reconciliation->bankAccount->name ?? '' }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.bank-reconciliation.import-form', $reconciliation->bank_account_id) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Import Statement') }}
                </a>
                <a href="{{ route('accounting.bank-reconciliation.index', ['bank_account_id' => $reconciliation->bank_account_id]) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Reconciliation') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
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

            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <dt class="text-xs font-medium text-gray-500 uppercase">Statement Balance</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_money($reconciliation->statement_balance) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <dt class="text-xs font-medium text-gray-500 uppercase">Book Balance</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_money($reconciliation->book_balance) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <dt class="text-xs font-medium text-gray-500 uppercase">Cleared Balance</dt>
                    <dd class="mt-1 text-lg font-bold text-gray-900">{{ format_money($reconciliation->cleared_balance) }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <dt class="text-xs font-medium text-gray-500 uppercase">Difference</dt>
                    <dd class="mt-1 text-lg font-bold {{ $reconciliation->difference == 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ format_money($reconciliation->difference) }}
                    </dd>
                </div>
            </div>

            @if($reconciliation->status === 'in_progress')
                <div class="flex justify-end mb-6">
                    <form method="POST" action="{{ route('accounting.bank-reconciliation.complete', $reconciliation) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" {{ $reconciliation->difference != 0 ? 'disabled' : '' }} class="inline-flex items-center px-4 py-2 {{ $reconciliation->difference == 0 ? 'bg-green-600 hover:bg-green-500' : 'bg-gray-300 cursor-not-allowed' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Complete Reconciliation') }}
                        </button>
                        @if($reconciliation->difference != 0)
                            <p class="text-xs text-gray-500 mt-2">Reconciliation can only be completed when the difference is 0.00</p>
                        @endif
                    </form>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Unmatched Statement Lines') }}</h3>
                    </div>
                    <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($unmatchedLines as $line)
                                    <tr id="statement-line-{{ $line->id }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $line->transaction_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $line->description }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $line->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ format_money(abs($line->amount)) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <button type="button" onclick="matchLine({{ $line->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                Match →
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">
                                            All statement lines are matched.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Unreconciled Book Transactions') }}</h3>
                    </div>
                    <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($unreconciledTransactions as $transaction)
                                    <tr id="book-transaction-{{ $transaction->id }}">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $transaction->transaction_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $transaction->description }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ ($transaction->credit - $transaction->debit) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ format_money(abs($transaction->credit - $transaction->debit)) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center">
                                            <button type="button" onclick="matchTransaction({{ $transaction->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                                ← Match
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">
                                            All transactions are reconciled.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($matchedLines->count() > 0)
                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Matched Items') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statement Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statement Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Statement Amount</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">↔</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Book Amount</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($matchedLines as $line)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line->description }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money(abs($line->amount)) }}</td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-400">↔</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $line->bankTransaction->description ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money(abs(($line->bankTransaction->credit ?? 0) - ($line->bankTransaction->debit ?? 0))) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <form method="POST" action="{{ route('accounting.bank-reconciliation.unmatch', $reconciliation->id) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Unmatch</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function matchLine(lineId) {
            const lineRow = document.getElementById('statement-line-' + lineId);
            const bookRows = document.querySelectorAll('#book-transaction-');
            if (bookRows.length === 0) {
                alert('No book transactions available to match.');
                return;
            }
            if (confirm('Match this statement line with the first available book transaction?')) {
                fetch('{{ route("accounting.bank-reconciliation.match", $reconciliation->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        matches: [{
                            bank_statement_line_id: lineId,
                            amount: 0
                        }]
                    })
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to match.');
                    }
                });
            }
        }

        function matchTransaction(transactionId) {
            if (confirm('Match this book transaction with the first available statement line?')) {
                fetch('{{ route("accounting.bank-reconciliation.match", $reconciliation->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        matches: [{
                            bank_transaction_id: transactionId,
                            amount: 0
                        }]
                    })
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to match.');
                    }
                });
            }
        }
    </script>
</x-app-layout>
