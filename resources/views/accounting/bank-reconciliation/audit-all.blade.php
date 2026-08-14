<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Audit Trail') }}</h1>
                    <div class="sub">{{ __('Every change across all reconciliations, with the user and timestamp.') }}</div>
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
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reports') }}</a>
                <span class="br-pill on" aria-current="page">{{ __('Audit Trail') }}</span>
            </nav>

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Activity') }}</h2>
                    <span class="n">{{ $logs->total() }} {{ __('event(s)') }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="br-tbl">
                        <thead>
                            <tr>
                                <th style="width:15%">{{ __('Date / Time') }}</th>
                                <th style="width:15%">{{ __('Bank Account') }}</th>
                                <th style="width:15%">{{ __('User') }}</th>
                                <th style="width:15%">{{ __('Action') }}</th>
                                <th>{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
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
                                <tr>
                                    <td colspan="5"><div class="empty">No activity recorded.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="br-pag">
                        {{ $logs->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
