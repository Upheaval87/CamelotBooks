<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ $title }}</h1>
                    <div class="sub">{{ __('Control-layer report on the bank reconciliation register.') }}</div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.reports') }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('All Reports') }}
                    </a>
                </div>
            </div>

            <nav class="br-pills">
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.index') }}">{{ __('Reconciliations') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.statements') }}">{{ __('Bank Statements') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.adjustments') }}">{{ __('Adjustments') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.outstanding') }}">{{ __('Outstanding Items') }}</a>
                <span class="br-pill on" aria-current="page">{{ __('Reports') }}</span>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.audit-all') }}">{{ __('Audit Trail') }}</a>
            </nav>

            <form class="br-filterbar" method="GET" action="{{ route('accounting.bank-reconciliation.report', ['report' => $report]) }}">
                <span class="lb">{{ __('Bank Account') }}</span>
                <select name="bank_account_id" class="f">
                    <option value="">All accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((int) $accountId === $account->id)>{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </select>
                @if(in_array($report, ['detail', 'history'], true))
                    <span class="lb">{{ __('Reconciliation') }}</span>
                    <select name="reconciliation_id" class="f">
                        <option value="">All periods</option>
                        @foreach($reconciliations as $reconciliation)
                            <option value="{{ $reconciliation->id }}" @selected($selectedRecon?->id === $reconciliation->id)>
                                {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->statement_number ?? '#' . $reconciliation->id }} ({{ $reconciliation->period_end?->format('M d, Y') ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="btn ghost sm">{{ __('Filter') }}</button>
                <a href="{{ route('accounting.bank-reconciliation.report', ['report' => $report]) }}" class="btn ghost sm">{{ __('Clear') }}</a>
                <span class="chip-t br-count">{{ $rows->count() }} {{ __('row(s)') }}</span>
            </form>

            <section class="card">
                <div class="card-h">
                    <h2>{{ $title }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    @if($report === 'summary')
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
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $reconciliation)
                                    @php
                                        $diff = (float) $reconciliation->difference;
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
                                        <td class="em">{{ $reconciliation->period_end?->format('M d, Y') ?? '—' }}</td>
                                        <td class="numr">{{ format_number($reconciliation->opening_balance) }}</td>
                                        <td class="numr">{{ format_number($reconciliation->closing_balance) }}</td>
                                        <td class="numr">{{ format_number($reconciliation->book_balance) }}</td>
                                        <td class="numr">{{ format_number($reconciliation->statement_balance) }}</td>
                                        <td class="numr @if($diff < 0) red @endif @if(abs($diff) > 0.005) warn @endif">{{ format_number($diff) }}</td>
                                        <td><span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9"><div class="empty">No reconciliations match the filters.</div></td></tr>
                                @endforelse
                            </tbody>
                            @if($rows->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="n">{{ __('Total') }}</td>
                                        <td class="numr n">{{ format_number($totals['Opening'] ?? 0) }}</td>
                                        <td class="numr n">{{ format_number($totals['Closing'] ?? 0) }}</td>
                                        <td class="numr n">{{ format_number($totals['Book'] ?? 0) }}</td>
                                        <td class="numr n">{{ format_number($totals['Statement'] ?? 0) }}</td>
                                        <td class="numr n">{{ format_number($totals['Difference'] ?? 0) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    @elseif(in_array($report, ['outstanding', 'detail'], true))
                        <table class="br-tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Bank Account') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                    @if($report === 'outstanding')
                                        <th class="num">{{ __('Balance') }} ({{ $cs }})</th>
                                    @else
                                        <th>{{ __('Matched To') }}</th>
                                        <th class="num">{{ __('Confidence') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $line)
                                    <tr>
                                        <td class="em">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="em">{{ $line->reconciliation?->bankAccount?->code }} — {{ $line->reconciliation?->bankAccount?->name }}</td>
                                        <td class="mono em">{{ $line->reference ?? '—' }}</td>
                                        <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $line->description }}">{{ $line->description }}</td>
                                        <td class="numr @if((float) $line->amount < 0) red @endif">{{ format_number($line->amount) }}</td>
                                        @if($report === 'outstanding')
                                            <td class="numr">{{ format_number($line->balance) }}</td>
                                        @else
                                            <td class="em">
                                                @if($line->match)
                                                    <span class="mono">{{ $line->match->bankTransaction?->reference ?? '—' }}</span>
                                                @else
                                                    <span class="badge b-inact"><span class="bdot"></span>{{ __('Unmatched') }}</span>
                                                @endif
                                            </td>
                                            <td class="numr">{{ $line->match ? number_format((float) $line->match->confidence, 0) . '%' : '—' }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="8"><div class="empty">No rows match the filters.</div></td></tr>
                                @endforelse
                            </tbody>
                            @if($report === 'outstanding' && $rows->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="n">{{ __('Total') }}</td>
                                        <td class="numr n">{{ format_number($totals['Amount'] ?? 0) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    @elseif($report === 'unmatched')
                        <table class="br-tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Bank Account') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th>{{ __('Journal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $transaction)
                                    <tr>
                                        <td class="em">{{ $transaction->date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="em">{{ $transaction->bankAccount?->code }} — {{ $transaction->bankAccount?->name }}</td>
                                        <td class="mono em">{{ $transaction->reference ?? '—' }}</td>
                                        <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $transaction->description }}">{{ $transaction->description }}</td>
                                        <td class="numr @if((float) $transaction->amount < 0) red @endif">{{ format_number($transaction->amount) }}</td>
                                        <td class="em">
                                            @if($transaction->journalEntry)
                                                <a class="drill" href="{{ route('accounting.journal-entries.show', $transaction->journalEntry->id) }}">#{{ $transaction->journalEntry->id }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty">No unmatched transactions.</div></td></tr>
                                @endforelse
                            </tbody>
                            @if($rows->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="n">{{ __('Total') }}</td>
                                        <td class="numr n">{{ format_number($totals['Amount'] ?? 0) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    @elseif($report === 'history')
                        <table class="br-tbl">
                            <thead>
                                <tr>
                                    <th style="width:15%">{{ __('Date / Time') }}</th>
                                    <th style="width:15%">{{ __('Bank Account') }}</th>
                                    <th style="width:12%">{{ __('User') }}</th>
                                    <th style="width:15%">{{ __('Action') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $log)
                                    <tr>
                                        <td class="mono em">{{ $log->created_at?->format('M d, Y g:i:s A') ?? '—' }}</td>
                                        <td class="em">{{ $log->reconciliation?->bankAccount?->code }} — {{ $log->reconciliation?->bankAccount?->name }}</td>
                                        <td class="em">{{ $log->user?->name ?? '—' }}</td>
                                        <td>
                                            <span class="badge
                                                @if(in_array($log->action, ['approved','completed'], true)) b-mint
                                                @elseif(in_array($log->action, ['reversed'], true)) b-red
                                                @elseif(in_array($log->action, ['matched','statement_imported'], true)) b-teal
                                                @elseif(in_array($log->action, ['ready_for_review'], true)) b-draft
                                                @else b-gray
                                                @endif"><span class="bdot"></span>{{ \App\Models\ReconciliationAuditLog::actionLabel($log->action) }}</td>
                                        <td class="em" style="white-space:normal">
                                            @if(!empty($log->details))
                                                <span style="color:var(--muted,#5F7476)">
                                                    @foreach($log->details as $key => $value)
                                                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                        {{ is_array($value) ? json_encode($value) : $value }}&nbsp;
                                                    @endforeach
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="empty">No history recorded.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        {{-- exceptions --}}
                        <table class="br-tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('Bank Account') }}</th>
                                    <th>{{ __('Statement') }}</th>
                                    <th>{{ __('Period End') }}</th>
                                    <th class="num">{{ __('Statement Bal.') }} ({{ $cs }})</th>
                                    <th class="num">{{ __('Book') }} ({{ $cs }})</th>
                                    <th class="num">{{ __('Difference') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $reconciliation)
                                    @php
                                        $diff = (float) $reconciliation->difference;
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
                                        <td class="em">{{ $reconciliation->period_end?->format('M d, Y') ?? '—' }}</td>
                                        <td class="numr">{{ format_number($reconciliation->statement_balance) }}</td>
                                        <td class="numr">{{ format_number($reconciliation->book_balance) }}</td>
                                        <td class="numr @if($diff < 0) red @endif warn">{{ format_number($diff) }}</td>
                                        <td><span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7"><div class="empty">No exceptions — all reconciliations balance.</div></td></tr>
                                @endforelse
                            </tbody>
                            @if($rows->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="n">{{ __('Total') }}</td>
                                        <td class="numr n">{{ format_number($totals['Difference'] ?? 0) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    @endif
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
