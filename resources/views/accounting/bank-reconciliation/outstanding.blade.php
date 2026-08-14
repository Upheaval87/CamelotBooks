<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Outstanding Items') }}</h1>
                    <div class="sub">{{ __('Statement lines still unmatched across all open reconciliations.') }}</div>
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
                <span class="br-pill on" aria-current="page">{{ __('Outstanding Items') }}</span>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reports') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.audit-all') }}">{{ __('Audit Trail') }}</a>
            </nav>

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Outstanding Statement Lines') }}</h2>
                    <span class="n">{{ $lines->total() }} {{ __('item(s)') }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="br-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Bank Account') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="num">{{ __('Amount') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Balance') }} ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                <tr>
                                    <td class="em">{{ $line->transaction_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="em">{{ $line->reconciliation?->bankAccount?->code }} — {{ $line->reconciliation?->bankAccount?->name }}</td>
                                    <td class="mono em">{{ $line->reference ?? '—' }}</td>
                                    <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $line->description }}">{{ $line->description }}</td>
                                    <td class="numr @if((float) $line->amount < 0) red @endif">{{ format_number($line->amount) }}</td>
                                    <td class="numr">{{ format_number($line->balance) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><div class="empty">No outstanding items.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($lines->hasPages())
                    <div class="br-pag">
                        {{ $lines->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
