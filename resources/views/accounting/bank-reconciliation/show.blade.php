<x-app-layout>
    <x-slot name="header">{{ __('Reconciliation') }} — {{ $reconciliation->bankAccount->name ?? '' }}</x-slot>

    <div class="max-w-full mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button href="{{ route('accounting.bank-reconciliation.index', $reconciliation->bank_account_id) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                New
            </x-toolbar-button>
            @if($reconciliation->status === 'in_progress')
                <form method="POST" action="{{ route('accounting.bank-reconciliation.complete', $reconciliation) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <x-toolbar-button variant="commit" type="submit" {{ $reconciliation->difference != 0 ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Save
                    </x-toolbar-button>
                </form>
            @endif
            <x-toolbar-button href="{{ route('accounting.bank-reconciliation.import-form', $reconciliation->bank_account_id) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Statement
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            <x-toolbar-button href="{{ route('accounting.accounts.show', $reconciliation->bank_account_id) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                View Register
            </x-toolbar-button>
            <x-toolbar-button href="{{ route('accounting.reports.bank-reconciliation-history') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Reconciliation Report
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span id="differencePill" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md {{ $reconciliation->difference == 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Difference: {{ format_money($reconciliation->difference) }}
            </span>

            @if($reconciliation->difference != 0)
                <p class="text-xs text-gray-500 ml-2">Complete when difference is 0.00</p>
            @endif

            <x-slot name="right">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-toolbar-button>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </x-toolbar-button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" onclick="window.print()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                Print
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export to CSV
                            </button>
                            <a href="{{ route('accounting.bank-reconciliation.index', $reconciliation->bank_account_id) }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back to Reconciliation
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-slot>
        </x-toolbar>
    </div>

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

        <div class="grid grid-cols-2 gap-6">
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Unmatched Statement Lines') }}</h3>
                </div>
                <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                    <table class="datasheet">
                        <thead class="sticky top-0">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unmatchedLines as $line)
                                <tr id="statement-line-{{ $line->id }}">
                                    <td>
                                        {{ $line->transaction_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $line->description }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $line->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ format_money(abs($line->amount)) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" onclick="matchLine({{ $line->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            Match →
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        All statement lines are matched.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Unreconciled Book Transactions') }}</h3>
                </div>
                <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                    <table class="datasheet">
                        <thead class="sticky top-0">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unreconciledTransactions as $transaction)
                                <tr id="book-transaction-{{ $transaction->id }}">
                                    <td>
                                        {{ $transaction->transaction_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $transaction->description }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ ($transaction->credit - $transaction->debit) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ format_money(abs($transaction->credit - $transaction->debit)) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" onclick="matchTransaction({{ $transaction->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            ← Match
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
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
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Statement Date</th>
                                <th>Statement Description</th>
                                <th class="text-right">Statement Amount</th>
                                <th class="text-center">↔</th>
                                <th>Book Description</th>
                                <th class="text-right">Book Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matchedLines as $line)
                                <tr>
                                    <td class="text-ink-soft">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ format_money(abs($line->amount)) }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-400">↔</td>
                                    <td class="text-ink-soft">{{ $line->bankTransaction->description ?? '—' }}</td>
                                    <td class="numeric">{{ format_money(abs(($line->bankTransaction->credit ?? 0) - ($line->bankTransaction->debit ?? 0))) }}</td>
                                    <td class="text-center">
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
