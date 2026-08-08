<x-app-layout>
    <x-list-header title="{{ __('Reconciliation') }} — {{ $reconciliation->bankAccount->name ?? '' }}" />

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Record') }}</span>
                    <a href="{{ route('accounting.bank-reconciliation.create', $reconciliation->bank_account_id) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($reconciliation->status === 'in_progress')
                        <form method="POST" action="{{ route('accounting.bank-reconciliation.complete', $reconciliation) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tr-save" {{ $reconciliation->difference != 0 ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                {{ __('Save') }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('accounting.bank-reconciliation.import-form', $reconciliation->bank_account_id) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ __('Import Statement') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <a href="{{ route('accounting.accounts.show', $reconciliation->bank_account_id) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        {{ __('View Register') }}
                    </a>
                    <a href="{{ route('accounting.reports.bank-reconciliation-history') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ __('Reconciliation Report') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('Export CSV') }}
                    </button>
                </div>

                <div class="tr-spacer"></div>

                <span id="differencePill" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md {{ $reconciliation->difference == 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('Difference') }}: {{ format_money($reconciliation->difference) }}
                </span>
                @if($reconciliation->difference != 0)
                    <p class="text-xs text-ink-soft ml-2">{{ __('Complete when difference is 0.00') }}</p>
                @endif
            </x-record-toolbar>

            

            

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="grid grid-cols-4 gap-6">
                        <div class="card p-4">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Statement Balance') }}</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ format_money($reconciliation->statement_balance) }}</p>
                        </div>
                        <div class="card p-4">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Book Balance') }}</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ format_money($reconciliation->book_balance) }}</p>
                        </div>
                        <div class="card p-4">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Cleared Balance') }}</p>
                            <p class="mt-1 text-lg font-bold text-ink">{{ format_money($reconciliation->cleared_balance) }}</p>
                        </div>
                        <div class="card p-4">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Difference') }}</p>
                            <p class="mt-1 text-lg font-bold {{ $reconciliation->difference == 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ format_money($reconciliation->difference) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="card overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <p class="text-base font-semibold text-ink">{{ __('Unmatched Statement Lines') }}</p>
                            </div>
                            <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                                <table class="record-datasheet">
                                    <thead class="sticky top-0">
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($unmatchedLines as $line)
                                            <tr id="statement-line-{{ $line->id }}">
                                                <td>{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                                <td>{{ $line->description }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ $line->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ format_money(abs($line->amount)) }}
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" onclick="matchLine({{ $line->id }})" class="text-gold-700 hover:text-gold-800 text-sm font-medium">
                                                        {{ __('Match') }} →
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-ink-soft">
                                                    {{ __('All statement lines are matched.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <p class="text-base font-semibold text-ink">{{ __('Unreconciled Book Transactions') }}</p>
                            </div>
                            <div class="overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                                <table class="record-datasheet">
                                    <thead class="sticky top-0">
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th class="text-right">{{ __('Amount') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($unreconciledTransactions as $transaction)
                                            <tr id="book-transaction-{{ $transaction->id }}">
                                                <td>{{ $transaction->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                                <td>{{ $transaction->description }}</td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-right {{ ($transaction->credit - $transaction->debit) < 0 ? 'text-red-600' : 'text-green-600' }}">
                                                    {{ format_money(abs($transaction->credit - $transaction->debit)) }}
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" onclick="matchTransaction({{ $transaction->id }})" class="text-gold-700 hover:text-gold-800 text-sm font-medium">
                                                        ← {{ __('Match') }}
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-ink-soft">
                                                    {{ __('All transactions are reconciled.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($matchedLines->count() > 0)
                        <div class="card overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <p class="text-base font-semibold text-ink">{{ __('Matched Items') }}</p>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Statement Date') }}</th>
                                            <th>{{ __('Statement Description') }}</th>
                                            <th class="text-right">{{ __('Statement Amount') }}</th>
                                            <th class="text-center">↔</th>
                                            <th>{{ __('Book Description') }}</th>
                                            <th class="text-right">{{ __('Book Amount') }}</th>
                                            <th class="text-center">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($matchedLines as $line)
                                            <tr>
                                                <td>{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                                <td>{{ $line->description }}</td>
                                                <td class="numeric">{{ format_money(abs($line->amount)) }}</td>
                                                <td class="px-6 py-4 text-center text-sm text-gray-400">↔</td>
                                                <td>{{ $line->bankTransaction->description ?? '—' }}</td>
                                                <td class="numeric">{{ format_money(abs(($line->bankTransaction->credit ?? 0) - ($line->bankTransaction->debit ?? 0))) }}</td>
                                                <td class="text-center">
                                                    <form method="POST" action="{{ route('accounting.bank-reconciliation.unmatch', $reconciliation->id) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">{{ __('Unmatch') }}</button>
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.bank-reconciliation.print', $reconciliation), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.bank-reconciliation.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>

    <script>
        async function matchLine(lineId) {
            const lineRow = document.getElementById('statement-line-' + lineId);
            const bookRows = document.querySelectorAll('#book-transaction-');
            if (bookRows.length === 0) {
                CB.toast('error', 'No book transactions available to match.');
                return;
            }
            if (!(await CB.confirm({ type: 'action', title: 'Match this statement line with the first available book transaction?' }))) return;
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
                    CB.toast('error', data.message || 'Failed to match.');
                }
            });
        }

        async function matchTransaction(transactionId) {
            if (!(await CB.confirm({ type: 'action', title: 'Match this book transaction with the first available statement line?' }))) return;
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
                    CB.toast('error', data.message || 'Failed to match.');
                }
            });
        }
    </script>
</x-app-layout>
