@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
@endphp
<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('Account Statement') }} <span class="mono-chip">{{ $account->code }}</span></h1>
                        <div class="sub">{{ $account->name }} · {{ ucfirst($account->type) }}</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.general-ledger.account-export-csv', array_merge(['accountId' => $account->id], request()->query())) }}" class="btn btn-ghost btn-sm">⇩ {{ __('Export CSV') }}</a>
                        <a href="{{ route('accounting.general-ledger.account-export-pdf', array_merge(['accountId' => $account->id], request()->query())) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">🖨 {{ __('Export PDF') }}</a>
                        <a href="{{ route('accounting.general-ledger.index') }}" class="btn btn-ghost btn-sm">← {{ __('Back') }}</a>
                    </div>
                </div>

                {{-- stats --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                        <h2>{{ __('Statement') }}</h2>
                        <span class="rule"></span>
                    </div>

                    <div class="sgrid">
                        <div class="sbox">
                            <div class="l">{{ __('Opening Balance') }}</div>
                            <div class="v">{{ format_number($openingBalance) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">Debit ({{ $cs }})</div>
                            <div class="v">{{ format_number($accountStats['debit']) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">Credit ({{ $cs }})</div>
                            <div class="v">{{ format_number($accountStats['credit']) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">{{ __('Closing Balance') }}</div>
                            <div class="v t-teal">{{ format_number($closingBalance) }}</div>
                        </div>
                    </div>

                    {{-- filters --}}
                    <form method="GET" action="{{ route('accounting.general-ledger.account', $account) }}" id="gl-account-form">
                        <div class="controls">
                            <input type="date" name="date_from" class="input" value="{{ request('date_from') }}" title="{{ __('Date from') }}" />
                            <input type="date" name="date_to" class="input" value="{{ request('date_to') }}" title="{{ __('Date to') }}" />
                            <x-scoped-search-field
                                name="branch_id"
                                entity="branch"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                :value="request('branch_id')"
                                :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                                placeholder="{{ __('All Branches') }}"
                            />
                            <button type="submit" class="btn ghost">{{ __('Filter') }}</button>
                            @if(request()->hasAny('date_from', 'date_to', 'branch_id'))
                                <a href="{{ route('accounting.general-ledger.account', $account) }}" class="btn ghost">{{ __('Clear') }}</a>
                            @endif
                            <span class="chip-t">{{ $transactionsPaginator->total() }} {{ __('lines') }}</span>
                        </div>
                    </form>
                </section>

                {{-- transactions --}}
                <section class="card" style="padding:20px 24px; margin-top:16px">
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:11%">{{ __('Date') }}</th>
                                    <th style="width:13%">{{ __('Journal #') }}</th>
                                    <th style="width:14%">{{ __('Branch') }}</th>
                                    <th style="width:26%">{{ __('Description') }}</th>
                                    <th class="num" style="width:10%">Debit ({{ $cs }})</th>
                                    <th class="num" style="width:10%">Credit ({{ $cs }})</th>
                                    <th class="num" style="width:10%">{{ __('Running Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactionsPaginator as $txn)
                                    <tr>
                                        <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $txn['line']->journalEntry->date->format('M d, Y') }}</td>
                                        <td><a href="{{ route('accounting.journal-entries.show', $txn['line']->journalEntry) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $txn['line']->journalEntry->journal_number }}</a></td>
                                        <td class="em">{{ $txn['line']->journalEntry->branch->name ?? '—' }}</td>
                                        <td class="em">{{ $txn['line']->memo ?? $txn['line']->journalEntry->memo ?? '—' }}</td>
                                        <td class="numr">{{ (float) $txn['line']->debit > 0 ? format_number($txn['line']->debit) : '—' }}</td>
                                        <td class="numr">{{ (float) $txn['line']->credit > 0 ? format_number($txn['line']->credit) : '—' }}</td>
                                        <td class="numr">{{ format_number($txn['running_balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"><div class="empty">{{ __('No transactions found.') }}</div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($transactionsPaginator->hasPages())
                            @php
                                $paginator = $transactionsPaginator->appends(request()->query());
                                $last = $paginator->lastPage();
                                $cur = $paginator->currentPage();
                                $winStart = max(1, $cur - 2);
                                $winEnd = min($last, $cur + 2);
                                $firstItem = $paginator->firstItem() ?: 0;
                                $lastItem = $paginator->lastItem() ?: 0;
                            @endphp
                            <div class="pagi">
                                <span class="t">{{ __('Showing') }} {{ $firstItem }}–{{ $lastItem }} {{ __('of') }} {{ $paginator->total() }} {{ __('lines') }}</span>
                                <span class="pg">
                                    @if($paginator->onFirstPage())
                                        <span class="pgbtn" aria-disabled="true" aria-label="{{ __('Previous') }}">‹</span>
                                    @else
                                        <a href="{{ $paginator->previousPageUrl() }}" aria-label="{{ __('Previous') }}">‹</a>
                                    @endif

                                    @if($winStart > 1)
                                        <a href="{{ $paginator->url(1) }}">1</a>
                                        @if($winStart > 2)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                    @endif

                                    @for($page = $winStart; $page <= $winEnd; $page++)
                                        @if($page === $cur)
                                            <span class="pgbtn cur" aria-current="page">{{ $page }}</span>
                                        @else
                                            <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($winEnd < $last)
                                        @if($winEnd < $last - 1)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                        <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
                                    @endif

                                    @if($paginator->hasMorePages())
                                        <a href="{{ $paginator->nextPageUrl() }}" aria-label="{{ __('Next') }}">›</a>
                                    @else
                                        <span class="pgbtn" aria-disabled="true" aria-label="{{ __('Next') }}">›</span>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
