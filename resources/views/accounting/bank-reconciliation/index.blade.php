<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Bank Reconciliation') }}</h1>
                    <div class="sub">Match bank statement lines to book transactions and clear them on completion.</div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.create', $activeBankAccountId ? ['bank_account_id' => $activeBankAccountId] : []) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        {{ __('Import Statement') }}
                    </a>
                    <a href="{{ url()->current() }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                        {{ __('Refresh') }}
                    </a>
                    <details class="br-more">
                        <summary class="btn ghost">
                            {{ __('More') }}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="br-more-menu">
                            <a href="{{ route('accounting.bank-reconciliation.export', request()->query()) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M9 3.75H6.912a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H15M2.25 13.5h3.386a1.125 1.125 0 011.09.796 2.205 2.205 0 002.115 1.454h3.328c.888 0 1.687-.477 2.115-1.454a1.125 1.125 0 011.09-.796h3.386M3.75 3h16.5a.75.75 0 01.75.75v16.5a.75.75 0 01-.75.75H3.75a.75.75 0 01-.75-.75V3.75A.75.75 0 013.75 3z"/></svg>
                                {{ __('Export Excel') }}
                            </a>
                            <a href="{{ route('accounting.bank-reconciliation.print', request()->query()) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5z"/></svg>
                                {{ __('Export PDF') }}
                            </a>
                            <div class="vdiv"></div>
                            <a href="{{ route('accounting.bank-reconciliation.audit-all') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ __('Audit Trail') }}
                            </a>
                        </div>
                    </details>
                    <span class="vdiv"></span>
                    <a href="{{ route('accounting.bank-reconciliation.create', $activeBankAccountId ? ['bank_account_id' => $activeBankAccountId] : []) }}" class="btn cta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                        {{ __('New Reconciliation') }}
                    </a>
                </div>
            </div>

            <nav class="br-pills">
                <span class="br-pill on" aria-current="page">{{ __('Reconciliations') }}</span>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.statements') }}">{{ __('Bank Statements') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.adjustments') }}">{{ __('Adjustments') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.outstanding') }}">{{ __('Outstanding Items') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reports') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.audit-all') }}">{{ __('Audit Trail') }}</a>
            </nav>

            <form class="br-filterbar" method="GET" action="{{ route('accounting.bank-reconciliation.index') }}">
                <span class="lb">{{ __('Bank Account') }}</span>
                <select name="bank_account_id" class="f">
                    <option value="">All accounts</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" @selected((int) request('bank_account_id') === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
                <span class="lb">{{ __('Period') }}</span>
                <input type="date" name="period_from" class="f" value="{{ request('period_from') }}" />
                <span class="farrow">&rarr;</span>
                <input type="date" name="period_to" class="f" value="{{ request('period_to') }}" />
                <button type="submit" class="btn ghost sm">{{ __('Filter') }}</button>
                <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn ghost sm">{{ __('Clear') }}</a>
                <span class="chip-t br-count">{{ $reconciliations->total() }} {{ __('periods') }}</span>
            </form>

            <div class="br-kpis">
                <div class="br-kpi">
                    <div class="l">{{ __('Statement Balance') }}</div>
                    <div class="v">{{ format_number($kpis['statement_balance']) }}</div>
                    <div class="n">{{ __('per bank statement') }}</div>
                </div>
                <div class="br-kpi">
                    <div class="l">{{ __('Book Balance') }}</div>
                    <div class="v">{{ format_number($kpis['book_balance']) }}</div>
                    <div class="n">{{ __('per general ledger') }}</div>
                </div>
                <div class="br-kpi">
                    <div class="l">{{ __('Matched') }}</div>
                    <div class="v ok">{{ $kpis['matched'] }}</div>
                    <div class="n">{{ __('transactions') }}</div>
                </div>
                <div class="br-kpi">
                    <div class="l">{{ __('Unmatched') }}</div>
                    <div class="v warn">{{ $kpis['unmatched'] }}</div>
                    <div class="n">{{ __('need attention') }}</div>
                </div>
                <div class="br-kpi">
                    <div class="l">{{ __('Adjustments') }}</div>
                    <div class="v">{{ $kpis['adjustments'] }}</div>
                    <div class="n">{{ __('pending') }}</div>
                </div>
                <div class="br-kpi br-hero warn">
                    <div class="l">{{ __('Difference') }}</div>
                    <div class="v">{{ $cs }} {{ format_number($kpis['difference']) }}</div>
                    <div class="n">{{ __('across') }} {{ $kpis['count'] }} {{ __('period(s)') }}</div>
                </div>
            </div>

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Reconciliation Register') }}</h2>
                    <span class="n">{{ $reconciliations->total() }} {{ __('periods') }}</span>
                    <span class="rule"></span>
                    <a class="drill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reconciliation history') }} &rarr;</a>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="br-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('Bank Account') }}</th>
                                <th>{{ __('Statement') }}</th>
                                <th>{{ __('Period End') }}</th>
                                <th class="num">{{ __('Opening') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Closing') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Book') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Statement Bal.') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Difference') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconciliations as $reconciliation)
                                @php
                                    $diff = (float) $reconciliation->difference;
                                    $unbalanced = abs($diff) > 0.005;
                                    $statusBadge = [
                                        \App\Models\Reconciliation::STATUS_DRAFT => ['b-gray', 'Draft'],
                                        \App\Models\Reconciliation::STATUS_IN_PROGRESS => ['b-draft', 'In Progress'],
                                        \App\Models\Reconciliation::STATUS_READY_FOR_REVIEW => ['b-teal', 'Ready for Review'],
                                        \App\Models\Reconciliation::STATUS_APPROVED => ['b-mint', 'Approved'],
                                        \App\Models\Reconciliation::STATUS_RECONCILED => ['b-post', 'Reconciled'],
                                        \App\Models\Reconciliation::STATUS_REVERSED => ['b-red', 'Reversed'],
                                    ][$reconciliation->status] ?? ['b-gray', \App\Models\Reconciliation::statusLabel($reconciliation->status)];
                                @endphp
                                <tr>
                                    <td class="em">{{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}</td>
                                    <td class="mono em">{{ $reconciliation->statement_number ?? '—' }}</td>
                                    <td class="em">{{ $reconciliation->period_end?->format('M d, Y') ?? $reconciliation->statement_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numr">{{ format_number($reconciliation->opening_balance) }}</td>
                                    <td class="numr">{{ format_number($reconciliation->closing_balance) }}</td>
                                    <td class="numr">{{ format_number($reconciliation->book_balance) }}</td>
                                    <td class="numr">{{ format_number($reconciliation->statement_balance) }}</td>
                                    <td class="numr @if($diff < 0) red @endif @if($unbalanced) warn @endif">{{ format_number($diff) }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                                    </td>
                                    <td class="num">
                                        @if(in_array($reconciliation->status, [\App\Models\Reconciliation::STATUS_DRAFT, \App\Models\Reconciliation::STATUS_IN_PROGRESS], true))
                                            <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="drill">{{ __('Continue') }} &rarr;</a>
                                        @elseif(in_array($reconciliation->status, [\App\Models\Reconciliation::STATUS_READY_FOR_REVIEW, \App\Models\Reconciliation::STATUS_APPROVED], true))
                                            <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation->id) }}" class="drill">{{ __('Review') }} &rarr;</a>
                                        @else
                                            <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation->id) }}" class="drill">{{ __('View') }} &rarr;</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10"><div class="empty">No reconciliations found. Start one from a bank account.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reconciliations->hasPages())
                    <div class="br-pag">
                        {{ $reconciliations->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
