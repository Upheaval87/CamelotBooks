<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $diff = (float) ($summary['difference'] ?? 0);
        $completed = $reconciliation->status === 'completed';
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Reconciliation') }} <span class="mono-chip">#{{ $reconciliation->id }}</span></h1>
                    <div class="sub">{{ $reconciliation->bankAccount->name ?? '' }} · {{ $reconciliation->statement_date?->format('M d, Y') ?? '' }}</div>
                </div>
                <div class="tbtns">
                    @if(!$completed)
                        <button type="button" class="btn btn-sec" onclick="suggestMatches()">{{ __('Suggest Matches') }}</button>
                        <a href="{{ route('accounting.bank-reconciliation.import-form', $reconciliation->bank_account_id) }}" class="btn btn-ghost">{{ __('Import Statement') }}</a>
                        <button type="submit" form="complete-form" class="btn cta" {{ $diff != 0 ? 'disabled' : '' }}>{{ __('Complete') }}</button>
                    @endif
                    <button onclick="window.print()" class="btn btn-ghost">{{ __('Print') }}</button>
                    <a href="{{ route('accounting.bank-reconciliation.index', $reconciliation->bank_account_id) }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            @if(!$completed)
                <form id="complete-form" method="POST" action="{{ route('accounting.bank-reconciliation.complete', $reconciliation->id) }}" class="hidden">
                    @csrf
                </form>
            @endif

            <div class="sgrid">
                <div class="sbox">
                    <div class="l">{{ __('Statement Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($reconciliation->statement_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Book Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($summary['book_balance'] ?? $reconciliation->book_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Cleared Balance') }} ({{ $cs }})</div>
                    <div class="v mint">{{ format_number($summary['cleared_balance'] ?? $reconciliation->cleared_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Difference') }} ({{ $cs }})</div>
                    <div class="v {{ $diff == 0 ? 'mint' : 'red' }}">{{ format_number($diff) }}</div>
                </div>
            </div>

            @if($diff != 0 && !$completed)
                <div class="q2-note-info" style="margin-top:16px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                    {{ __('Difference must be zero before you can complete.') }} {{ __('Match outstanding statement lines or book transactions.') }}
                </div>
            @endif

            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 17H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-2"/><path d="M9 21a3 3 0 0 1-3-3v-2h12v2a3 3 0 0 1-3 3H9z"/><path d="M9 21h6"/></svg></span>
                    <h2>{{ __('Unmatched Statement Lines') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unmatchedStatementLines as $line)
                                <tr id="statement-line-{{ $line->id }}">
                                    <td>{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $line->description }}</td>
                                    <td class="numr {{ $line->amount < 0 ? 'red' : '' }}">{{ format_number(abs((float) $line->amount)) }}</td>
                                    <td class="num" style="white-space:nowrap">
                                        <button type="button" onclick="matchLine({{ $line->id }})" class="btn ghost sm">{{ __('Match') }} →</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"><div class="empty">{{ __('All statement lines are matched.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                    <h2>{{ __('Unreconciled Book Transactions') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unreconciledTransactions as $transaction)
                                <tr id="book-transaction-{{ $transaction->id }}">
                                    <td>{{ $transaction->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $transaction->description }}</td>
                                    <td class="numr {{ (float) $transaction->amount < 0 ? 'red' : '' }}">{{ format_number(abs((float) $transaction->amount)) }}</td>
                                    <td class="num" style="white-space:nowrap">
                                        <button type="button" onclick="matchTransaction({{ $transaction->id }})" class="btn ghost sm">← {{ __('Match') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"><div class="empty">{{ __('All book transactions are reconciled.') }}</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if($matchedItems->count() > 0)
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-9.33-5"/><path d="M8 6l-1-2H4v4"/><path d="M2 8a6 6 0 0 0 9.33 5"/><path d="M16 14l1 2h3v-4"/></svg></span>
                        <h2>{{ __('Matched Items') }}</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap" style="margin-top:0">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Statement Date') }}</th>
                                    <th>{{ __('Statement Description') }}</th>
                                    <th class="num">{{ __('Statement Amount') }} ({{ $cs }})</th>
                                    <th class="num">↔</th>
                                    <th>{{ __('Book Description') }}</th>
                                    <th class="num">{{ __('Book Amount') }} ({{ $cs }})</th>
                                    <th class="num">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matchedItems as $item)
                                    @php
                                        $sLine = $item->statementLine;
                                        $tx = $item->bankTransaction;
                                        $txAmount = $tx ? (float) $tx->amount : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $sLine?->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="em">{{ $sLine?->description ?? '—' }}</td>
                                        <td class="numr {{ $sLine && $sLine->amount < 0 ? 'red' : '' }}">{{ $sLine ? format_number(abs((float) $sLine->amount)) : '—' }}</td>
                                        <td class="num em">↔</td>
                                        <td class="em">{{ $tx->description ?? '—' }}</td>
                                        <td class="numr {{ $txAmount < 0 ? 'red' : '' }}">{{ format_number(abs($txAmount)) }}</td>
                                        <td class="num" style="white-space:nowrap">
                                            <button type="button" onclick="unmatchItem({{ $item->id }})" class="btn danger-o sm">{{ __('Unmatch') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>

    <script>
        const recMatchUrl = '{{ route("accounting.bank-reconciliation.match", $reconciliation->id) }}';
        const recUnmatchUrl = '{{ route("accounting.bank-reconciliation.unmatch", $reconciliation->id) }}';
        const recSuggestUrl = '{{ route("accounting.bank-reconciliation.suggest", $reconciliation->id) }}';

        async function matchLine(lineId) {
            const ok = await CB.confirm({ type: 'action', title: 'Match this statement line with the first available book transaction?' });
            if (!ok) return;
            try {
                const res = await fetch(recMatchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ matches: [{ bank_statement_line_id: lineId, amount: 0 }] })
                });
                const data = await res.json();
                if (data.success) { location.reload(); } else { CB.toast('error', data.error || 'Failed to match.'); }
            } catch (e) { CB.toast('error', 'Failed to match.'); }
        }

        async function matchTransaction(transactionId) {
            const ok = await CB.confirm({ type: 'action', title: 'Match this book transaction with the first available statement line?' });
            if (!ok) return;
            try {
                const res = await fetch(recMatchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ matches: [{ bank_transaction_id: transactionId, amount: 0 }] })
                });
                const data = await res.json();
                if (data.success) { location.reload(); } else { CB.toast('error', data.error || 'Failed to match.'); }
            } catch (e) { CB.toast('error', 'Failed to match.'); }
        }

        async function unmatchItem(itemId) {
            const ok = await CB.confirm({ type: 'danger', title: 'Unmatch this pair?' });
            if (!ok) return;
            try {
                const res = await fetch(recUnmatchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ item_id: itemId })
                });
                const data = await res.json();
                if (data.success) { location.reload(); } else { CB.toast('error', data.error || 'Failed to unmatch.'); }
            } catch (e) { CB.toast('error', 'Failed to unmatch.'); }
        }

        async function suggestMatches() {
            const ok = await CB.confirm({ type: 'action', title: 'Suggest matches for unmatched items?' });
            if (!ok) return;
            try {
                const res = await fetch(recSuggestUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.error) { CB.toast('error', data.error); return; }
                if (!data.suggestions || data.suggestions.length === 0) { CB.toast('info', 'No matches suggested.'); return; }
                const matches = data.suggestions.map(s => ({ bank_statement_line_id: s.bank_statement_line_id, bank_transaction_id: s.bank_transaction_id, amount: 0 }));
                const mRes = await fetch(recMatchUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json' },
                    body: JSON.stringify({ matches })
                });
                const mData = await mRes.json();
                if (mData.success) { CB.toast('success', data.suggestions.length + ' suggestion(s) matched.'); location.reload(); }
                else { CB.toast('error', mData.error || 'Failed to apply suggestions.'); }
            } catch (e) { CB.toast('error', 'Failed to suggest matches.'); }
        }
    </script>
</x-app-layout>
