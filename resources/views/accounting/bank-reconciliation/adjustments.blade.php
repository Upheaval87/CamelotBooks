<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Adjustments') }}</h1>
                    <div class="sub">{{ __('Manual adjustments recorded while reconciling bank accounts.') }}</div>
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
                <span class="br-pill on" aria-current="page">{{ __('Adjustments') }}</span>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.outstanding') }}">{{ __('Outstanding Items') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reports') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.audit-all') }}">{{ __('Audit Trail') }}</a>
            </nav>

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Adjustment Register') }}</h2>
                    <span class="n">{{ $adjustments->total() }} {{ __('adjustment(s)') }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="br-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Bank Account') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Side') }}</th>
                                <th>{{ __('Sign') }}</th>
                                <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th>{{ __('Account') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adjustment)
                                @php
                                    $statusBadge = [
                                        \App\Models\ReconciliationAdjustment::STATUS_PENDING => ['b-draft', 'Pending'],
                                        \App\Models\ReconciliationAdjustment::STATUS_POSTED => ['b-post', 'Posted'],
                                        \App\Models\ReconciliationAdjustment::STATUS_REVERSED => ['b-red', 'Reversed'],
                                    ][$adjustment->status] ?? ['b-gray', ucfirst($adjustment->status)];
                                @endphp
                                <tr>
                                    <td class="em">{{ $adjustment->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $adjustment->reconciliation?->bankAccount?->code }} — {{ $adjustment->reconciliation?->bankAccount?->name }}</td>
                                    <td class="em">{{ \App\Models\ReconciliationAdjustment::typeLabel($adjustment->type) }}</td>
                                    <td class="em">{{ ucfirst($adjustment->side) }}</td>
                                    <td class="em">{{ $adjustment->sign === \App\Models\ReconciliationAdjustment::SIGN_ADD ? '+' : '−' }}</td>
                                    <td class="numr">{{ format_number($adjustment->amount) }}</td>
                                    <td class="em">{{ $adjustment->account?->code }} — {{ $adjustment->account?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[0] }}"><span class="bdot"></span>{{ $statusBadge[1] }}</span>
                                    </td>
                                    <td class="em">{{ $adjustment->createdBy?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9"><div class="empty">No adjustments recorded.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($adjustments->hasPages())
                    <div class="br-pag">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
