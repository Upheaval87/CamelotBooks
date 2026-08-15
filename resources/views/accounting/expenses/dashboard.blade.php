<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('Expense Management') }}</h1>
                <div class="sub">{{ __('Capture, approve, post, track and report business expenses.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <details class="more">
                    <summary class="btn btn-ghost btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 9h12M6 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ __('More') }}
                    </summary>
                    <div class="more-menu">
                        <a class="more-item" href="{{ route('accounting.expenses.categories.index') }}">{{ __('Expense Categories') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.recurring.index') }}">{{ __('Recurring Expenses') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.reports') }}">{{ __('Reports') }}</a>
                    </div>
                </details>
                <a href="{{ route('accounting.expenses.claims.create') }}" class="btn btn-sec btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('Expense Claim') }}
                </a>
                <a href="{{ route('accounting.expenses.create') }}" class="btn btn-cta btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('Record Expense') }}
                </a>
            </div>
        </div>

        <div class="kpis" style="margin-bottom:16px">
            <div class="kpi hero">
                <div class="l">{{ __('Total Expenses') }}</div>
                <div class="v">{{ $cs }} {{ format_number($totalYtd) }}</div>
                <div class="n" style="color:#dff7f6">{{ __('year to date') }}</div>
            </div>
            <div class="kpi">
                <div class="l">{{ __('This Month') }}</div>
                <div class="v">{{ $cs }} {{ format_number($monthTotal) }}</div>
                <div class="n">{{ $monthLabel }}</div>
            </div>
            <div class="kpi warn">
                <div class="l">{{ __('Pending Approval') }}</div>
                <div class="v">{{ $cs }} {{ format_number($pendingTotal) }}</div>
                <div class="n">
                    <a class="open-l" href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}">{{ __('Review queue') }} →</a>
                </div>
            </div>
            <div class="kpi">
                <div class="l">{{ __('Unposted') }}</div>
                <div class="v">{{ $cs }} {{ format_number($unposted) }}</div>
                <div class="n">{{ __('approved, awaiting post') }}</div>
            </div>
        </div>

        <div class="dashgrid">
            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Expenses by Category') }}</h2>
                    <span class="fmt" style="margin-left:auto">{{ $monthLabel }}</span>
                </div>
                <div class="card-sec bars">
                    @forelse($byCategory as $cat)
                        @php $pct = max((float) $cat->total / max((float) ($byCategory->max('total') ?? 0.01), 0.01), 0.01) * 100; @endphp
                        <div class="brow">
                            <span class="lb">{{ $cat->category?->name ?? 'Uncategorised' }}</span>
                            <span class="bar"><i style="width:{{ $pct }}%"></i></span>
                            <span class="vl">{{ format_number($cat->total) }}</span>
                        </div>
                    @empty
                        <div class="em">{{ __('No expenses this month.') }}</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Monthly Trend') }}</h2>
                    <span class="fmt" style="margin-left:auto">{{ __('last 8 months') }}</span>
                </div>
                <div class="card-sec">
                    <div class="cols">
                        @foreach($trend as $m)
                            @php $h = max((float) $m['total'] / $trendMax * 100, 2); @endphp
                            <div class="c">
                                <i style="height:{{ $h }}%"></i>
                                <span class="m">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Pending Approvals') }}</h2>
                    <span class="fmt" style="margin-left:auto">{{ $pendingCount + $pendingClaims->count() }}</span>
                </div>
                <div class="card-sec" style="display:flex;flex-direction:column;gap:10px">
                    @foreach($pendingExpenses as $exp)
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="mono">{{ $exp->expense_number }}</span>
                            <span class="em" style="font-size:12px">{{ $exp->category?->name ?? ($exp->vendor?->name ?? 'Expense') }}</span>
                            <span class="numr bold" style="margin-left:auto">{{ format_number($exp->amount) }}</span>
                            <a class="btn btn-sec btn-xs" href="{{ route('accounting.expenses.show', $exp) }}">{{ __('Approve') }}</a>
                        </div>
                    @endforeach
                    @foreach($pendingClaims as $claim)
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="mono">{{ $claim->claim_number }}</span>
                            <span class="em" style="font-size:12px">{{ $claim->category?->name ?? ($claim->employee?->full_name ?? 'Claim') }}</span>
                            <span class="numr bold" style="margin-left:auto">{{ format_number($claim->amount) }}</span>
                            <a class="btn btn-sec btn-xs" href="{{ route('accounting.expenses.claims.show', $claim) }}">{{ __('Approve') }}</a>
                        </div>
                    @endforeach
                    @if($pendingExpenses->isEmpty() && $pendingClaims->isEmpty())
                        <div class="em">{{ __('Nothing awaiting approval.') }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div style="border-top:1px solid var(--line, #e2ecec);margin:26px 0"></div>

        <div class="page-head" style="margin-bottom:16px">
            <div>
                <h1>{{ __('All Expenses') }}</h1>
                <div class="sub">{{ __('Every business expense across branches, departments and cost centres.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('accounting.expenses.index') }}" class="btn btn-ghost btn-sm">{{ __('Open full list') }}</a>
                <a href="{{ route('accounting.expenses.create') }}" class="btn btn-cta btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('Record Expense') }}
                </a>
            </div>
        </div>

        @include('accounting.expenses._list')
    </div>
</x-app-layout>
