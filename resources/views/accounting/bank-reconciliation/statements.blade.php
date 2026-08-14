<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Bank Statements') }}</h1>
                    <div class="sub">{{ __('Every statement import across all bank accounts.') }}</div>
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
                <span class="br-pill on" aria-current="page">{{ __('Bank Statements') }}</span>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.adjustments') }}">{{ __('Adjustments') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.outstanding') }}">{{ __('Outstanding Items') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.reports') }}">{{ __('Reports') }}</a>
                <a class="br-pill" href="{{ route('accounting.bank-reconciliation.audit-all') }}">{{ __('Audit Trail') }}</a>
            </nav>

            <section class="card">
                <div class="card-h">
                    <h2>{{ __('Statement Imports') }}</h2>
                    <span class="n">{{ $imports->total() }} {{ __('file(s)') }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table class="br-tbl">
                        <thead>
                            <tr>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Bank Account') }}</th>
                                <th>{{ __('Statement Date') }}</th>
                                <th class="num">{{ __('Lines') }}</th>
                                <th class="num">{{ __('End Balance') }} ({{ $cs }})</th>
                                <th>{{ __('Imported') }}</th>
                                <th>{{ __('By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($imports as $import)
                                <tr>
                                    <td class="mono em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $import->filename }}">{{ $import->filename }}</td>
                                    <td class="em">
                                        @if($import->reconciliation)
                                            <a class="drill" href="{{ route('accounting.bank-reconciliation.workspace', $import->reconciliation->id) }}">
                                                {{ $import->bankAccount?->code }} — {{ $import->bankAccount?->name }}
                                            </a>
                                        @else
                                            {{ $import->bankAccount?->code }} — {{ $import->bankAccount?->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="em">{{ $import->statement_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numr">{{ $import->line_count }}</td>
                                    <td class="numr">{{ format_number($import->statement_end_balance) }}</td>
                                    <td class="em">{{ $import->created_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                    <td class="em">{{ $import->importedBy?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7"><div class="empty">No statements imported yet.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($imports->hasPages())
                    <div class="br-pag">
                        {{ $imports->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
