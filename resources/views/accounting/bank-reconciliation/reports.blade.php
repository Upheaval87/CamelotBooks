<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Reports') }}</h1>
                    <div class="sub">{{ __('Control layer &mdash; validate, approve, complete, lock and report.') }}</div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.index') }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back to Register') }}
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

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Approval Setting') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="card-b">
                    <form method="POST" action="{{ route('accounting.bank-reconciliation.approval') }}">
                        @csrf
                        <div class="g2" style="align-items:center">
                            <div>
                                <x-settings.toggle
                                    name="enabled"
                                    label="{{ __('Require approval before completion') }}"
                                    description="{{ __('When enabled, a reconciliation must be approved by a different user before it can be completed and locked.') }}"
                                    :checked="(bool) ($approvalSetting?->requires_approval)"
                                />
                            </div>
                            <div style="display:flex;gap:10px;justify-content:flex-end">
                                <button type="submit" class="btn sec">{{ __('Save Setting') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <div class="br-kpis" style="margin:16px 0">
                <div class="br-kpi">
                    <div class="l">{{ __('Bank Accounts') }}</div>
                    <div class="v">{{ $accounts->count() }}</div>
                    <div class="n">{{ __('active in this company') }}</div>
                </div>
                <div class="br-kpi">
                    <div class="l">{{ __('Reconciliations') }}</div>
                    <div class="v">{{ $reconciliations->count() }}</div>
                    <div class="n">{{ __('all periods') }}</div>
                </div>
                <div class="br-kpi br-hero @if($outOfBalance > 0) warn @endif">
                    <div class="l">{{ __('Out of Balance') }}</div>
                    <div class="v">{{ $outOfBalance }}</div>
                    <div class="n">{{ __('period(s) need attention') }}</div>
                </div>
            </div>

            <div class="repcards">
                @php
                    $reportCards = [
                        ['summary', __('Reconciliation Summary'), __('Statement balance, book balance, adjustments, difference and status per period.')],
                        ['outstanding', __('Outstanding Transactions'), __('Outstanding cheques, deposits in transit and pending bank transactions.')],
                        ['unmatched', __('Unmatched Transactions'), __('Bank-only and book-only items requiring attention.')],
                        ['detail', __('Reconciliation Detail'), __('Every matched / unmatched item with match confidence and method.')],
                        ['history', __('Reconciliation History'), __('Who reconciled, when, difference, status and reversal history.')],
                        ['exceptions', __('Reconciliation Exceptions'), __('Large / old unmatched items, duplicates, bank-only and book-only outliers.')],
                    ];
                @endphp
                @foreach($reportCards as [$key, $label, $description])
                    <div class="repcard">
                        <span class="t">{{ $label }}</span>
                        <span class="d">{{ $description }}</span>
                        <div class="foot">
                            <span class="fmt">PDF</span>
                            <span class="fmt">Excel</span>
                            <a class="drill" style="margin-left:auto" href="{{ route('accounting.bank-reconciliation.report', ['report' => $key]) }}">{{ __('Open') }} &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>

            <section class="card" style="margin-top:16px">
                <div class="card-h">
                    <h2>{{ __('Reconciliation History') }}</h2>
                    <span class="n">{{ $reconciliations->count() }} {{ __('period(s)') }}</span>
                    <span class="rule"></span>
                    <a class="drill" href="{{ route('accounting.bank-reconciliation.report', ['report' => 'history']) }}">{{ __('Full history') }} &rarr;</a>
                </div>
                <div class="li-wrap" style="margin-top:0">
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
                            @forelse($reconciliations as $reconciliation)
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
                                    <td class="numr @if($diff < 0) red @endif @if(abs($diff) > 0.005) warn @endif">{{ format_number($diff) }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"><div class="empty">No reconciliations yet.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
