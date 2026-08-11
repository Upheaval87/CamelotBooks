<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $inProgress = $reconciliations->getCollection()->where('status', 'in_progress')->count();
        $completed = $reconciliations->getCollection()->where('status', 'completed')->count();
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="page-head">
                <div>
                    <h1>{{ __('Bank Reconciliation') }} <span class="mono-chip">{{ $bankAccount->code }}</span></h1>
                    <div class="sub">{{ $bankAccount->name }}</div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.bank-reconciliation.import-form', $bankAccount->id) }}" class="btn cta">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ __('Import Statement') }}
                    </a>
                    <a href="{{ route('accounting.bank-accounts.index') }}" class="btn btn-ghost">{{ __('Back to Accounts') }}</a>
                </div>
            </div>

            <div class="sgrid">
                <div class="sbox">
                    <div class="l">{{ __('Book Balance') }} ({{ $cs }})</div>
                    <div class="v">{{ format_number($bankAccount->current_balance) }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Reconciliations') }}</div>
                    <div class="v">{{ $reconciliations->total() }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('In Progress') }}</div>
                    <div class="v t-teal">{{ $inProgress }}</div>
                </div>
                <div class="sbox">
                    <div class="l">{{ __('Completed') }}</div>
                    <div class="v t-mint">{{ $completed }}</div>
                </div>
            </div>

            <section class="card card-sec" style="margin-top:16px">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg></span>
                    <h2>{{ __('Reconciliation History') }}</h2>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Statement Date') }}</th>
                                <th class="num">{{ __('Statement Balance') }} ({{ $cs }})</th>
                                <th class="num">{{ __('Cleared Balance') }} ({{ $cs }})</th>
                                <th>{{ __('Status') }}</th>
                                <th class="num">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconciliations as $reconciliation)
                                <tr>
                                    <td class="em">{{ $reconciliation->statement_date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="numr">{{ format_number($reconciliation->statement_balance) }}</td>
                                    <td class="numr">{{ format_number($reconciliation->cleared_balance) }}</td>
                                    <td>
                                        @if($reconciliation->status === 'completed')
                                            <span class="badge b-post"><span class="bdot"></span>{{ __('Completed') }}</span>
                                        @elseif($reconciliation->status === 'in_progress')
                                            <span class="badge b-teal"><span class="bdot"></span>{{ __('In Progress') }}</span>
                                        @else
                                            <span class="badge b-gray"><span class="bdot"></span>{{ ucfirst(str_replace('_', ' ', $reconciliation->status)) }}</span>
                                        @endif
                                    </td>
                                    <td class="num" style="white-space:nowrap">
                                        <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation) }}" class="btn ghost sm">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"><div class="empty">No reconciliations found. Import a statement to begin.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reconciliations->hasPages())
                    <div class="pagi">
                        <span class="t">{{ __('Showing') }} {{ $reconciliations->firstItem() }}–{{ $reconciliations->lastItem() }} {{ __('of') }} {{ $reconciliations->total() }} {{ __('reconciliations') }}</span>
                        <div style="display:flex;gap:8px">
                            <a href="{{ $reconciliations->appends(request()->query())->previousPageUrl() }}" class="btn btn-ghost btn-sm @if($reconciliations->onFirstPage())" style="opacity:.45;pointer-events:none" aria-disabled="true @endif" aria-label="{{ __('Previous') }}">← {{ __('Prev') }}</a>
                            <a href="{{ $reconciliations->appends(request()->query())->nextPageUrl() }}" class="btn btn-ghost btn-sm @if(!$reconciliations->hasMorePages())" style="opacity:.45;pointer-events:none" aria-disabled="true @endif" aria-label="{{ __('Next') }}">{{ __('Next') }} →</a>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
