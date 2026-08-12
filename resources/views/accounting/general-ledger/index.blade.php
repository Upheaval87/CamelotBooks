@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $activeAccountId = request('account_id');
@endphp
<x-app-layout>
    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page head --}}
                <div class="page-head">
                    <div>
                        <h1>{{ __('General Ledger') }}</h1>
                        <div class="sub">{{ __('Every posted journal line, with running balances per account.') }}</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.general-ledger.export-csv', request()->query()) }}" class="btn btn-ghost btn-sm">⇩ {{ __('Export CSV') }}</a>
                    </div>
                </div>

                {{-- portfolio / stats --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                        <h2>{{ __('Ledger') }}</h2>
                        <span class="rule"></span>
                    </div>

                    <div class="sgrid sgrid--4">
                        <div class="sbox">
                            <div class="l">{{ __('Transactions') }}</div>
                            <div class="v">{{ number_format($glStats['transactions']) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">{{ __('Accounts') }}</div>
                            <div class="v">{{ number_format($glStats['accounts']) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">Debit ({{ $cs }})</div>
                            <div class="v">{{ format_number($glStats['debit']) }}</div>
                        </div>
                        <div class="sbox">
                            <div class="l">Credit ({{ $cs }})</div>
                            <div class="v">{{ format_number($glStats['credit']) }}</div>
                        </div>
                    </div>

                    {{-- filters --}}
                    <form method="GET" action="{{ route('accounting.general-ledger.index') }}" id="gl-list-form">
                        <div class="controls">
                            <div class="gl-row">
                                <x-scoped-search-field
                                    name="account_id"
                                    entity="account"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                    :value="request('account_id')"
                                    :label="$activeAccountId ? ($accounts->firstWhere('id', (int) $activeAccountId)?->name ?? '') : ''"
                                    placeholder="{{ __('Search accounts...') }}"
                                />
                                <x-scoped-search-field
                                    name="branch_id"
                                    entity="branch"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                    :value="request('branch_id')"
                                    :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                                    placeholder="{{ __('All Branches') }}"
                                />
                            </div>
                            <div class="gl-row">
                                <label class="field gl-field">
                                    <span class="label">{{ __('From') }}</span>
                                    <input type="date" name="date_from" class="input" value="{{ request('date_from') }}" />
                                </label>
                                <label class="field gl-field">
                                    <span class="label">{{ __('To') }}</span>
                                    <input type="date" name="date_to" class="input" value="{{ request('date_to') }}" />
                                </label>
                            </div>
                            <button type="submit" class="btn ghost">{{ __('Filter') }}</button>
                            @if(request()->hasAny('account_id', 'date_from', 'date_to', 'branch_id'))
                                <a href="{{ route('accounting.general-ledger.index') }}" class="btn ghost">{{ __('Clear') }}</a>
                            @endif
                            <span class="chip-t">{{ $glPaginator->total() }} {{ __('lines') }}</span>
                        </div>
                    </form>
                </section>

                {{-- line list --}}
                <section class="card" style="padding:20px 24px; margin-top:16px">
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:11%">{{ __('Date') }}</th>
                                    <th style="width:13%">{{ __('Journal #') }}</th>
                                    <th style="width:24%">{{ __('Account') }}</th>
                                    <th style="width:22%">{{ __('Description') }}</th>
                                    <th class="num" style="width:10%">Debit ({{ $cs }})</th>
                                    <th class="num" style="width:10%">Credit ({{ $cs }})</th>
                                    <th class="num" style="width:10%">{{ __('Running Balance') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($glPaginator as $row)
                                    <tr>
                                        <td style="font-weight:600;color:var(--ink,#0B2A2D)">{{ $row['line']->journalEntry->date->format('M d, Y') }}</td>
                                        <td><a href="{{ route('accounting.journal-entries.show', $row['line']->journalEntry) }}" class="mono" style="color:var(--sec,#128F8E)">{{ $row['line']->journalEntry->journal_number }}</a></td>
                                        <td class="em">{{ $row['line']->account->code }} — {{ $row['line']->account->name }}</td>
                                        <td class="em">{{ $row['line']->memo ?? $row['line']->journalEntry->memo ?? '—' }}</td>
                                        <td class="numr">{{ (float) $row['line']->debit > 0 ? format_number($row['line']->debit) : '—' }}</td>
                                        <td class="numr">{{ (float) $row['line']->credit > 0 ? format_number($row['line']->credit) : '—' }}</td>
                                        <td class="numr">{{ format_number($row['running_balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"><div class="empty">{{ __('No journal entry lines found.') }}</div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($glPaginator->hasPages())
                            @php
                                $paginator = $glPaginator->appends(request()->query());
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
