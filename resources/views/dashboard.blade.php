<x-app-layout>
{{-- Dashboard (Appendix A port — docs/final-dashboard.txt)
     Read-only presentation. All figures come from DashboardOverviewService. --}}
@php
    $money = function ($v) use ($cs, $decimals) {
        $v = (float) $v;
        return ($v < 0 ? '-' : '') . $cs . number_format(abs($v), $decimals, '.', ',');
    };

    $signedMoney = function ($v, $dir = null) use ($cs, $decimals) {
        $v = (float) $v;
        $sign = $dir === 'in' ? '+' : ($dir === 'out' ? '-' : ($v < 0 ? '-' : ''));

        return $sign . $cs . number_format(abs($v), $decimals, '.', ',');
    };

    $bankRows = array_values(array_filter($cash['rows'], fn ($r) => $r['badge'] === 'BANK'));
    $cashRows = array_values(array_filter($cash['rows'], fn ($r) => $r['badge'] === 'CASH'));
    $bankSum = (float) array_sum(array_column($bankRows, 'value'));
    $cashSum = (float) array_sum(array_column($cashRows, 'value'));
    $mixBase = $bankSum + $cashSum;
    $bankPct = $mixBase > 0 ? round($bankSum / $mixBase * 100, 1) : 0.0;
    $cashPct = $mixBase > 0 ? round($cashSum / $mixBase * 100, 1) : 0.0;
    $chartNet = (float) $chart['total_revenue'] - (float) $chart['total_expenses'];

    $barPct = fn ($pct) => max(4.0, (float) $pct);
    $agingTracks = ['t1', 't2', 't3', 't4'];
    $billsUrl = route('accounting.bills.index');
    $bankUrl = route('accounting.banking.accounts');
@endphp

<div class="dash8">
    <div class="wrap">

        {{-- HERO --}}
        <section class="hero">
            <div>
                <span class="h-eye"><i></i>{{ $today_display }}</span>
                <h1>{{ $greeting }}</h1>
                <p class="h-sub">{{ $subhead }}</p>
                <p class="h-kick"><b>{{ $range_label }}</b> &nbsp;&middot;&nbsp; how money came in, went out, and stands right now</p>
            </div>
            <div class="h-ctl">
                <nav class="seg" aria-label="Dashboard period">
                    <a class="seg-t {{ $preset === 'month' ? 'on' : '' }}" href="{{ route('dashboard', ['period' => 'month']) }}" aria-current="{{ $preset === 'month' ? 'page' : 'false' }}">This Month</a>
                    <a class="seg-t {{ $preset === 'quarter' ? 'on' : '' }}" href="{{ route('dashboard', ['period' => 'quarter']) }}" aria-current="{{ $preset === 'quarter' ? 'page' : 'false' }}">This Quarter</a>
                    <a class="seg-t {{ $preset === 'ytd' ? 'on' : '' }}" href="{{ route('dashboard', ['period' => 'ytd']) }}" aria-current="{{ $preset === 'ytd' ? 'page' : 'false' }}">Year to Date</a>
                </nav>
                <a class="h-export" href="{{ $exportUrl }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5M12 15V3"/></svg>
                    Export
                </a>
            </div>
        </section>

        {{-- KPI STRIP --}}
        <section class="kpis" aria-label="Key metrics">
            <div class="kpi" style="--d:60ms">
                <div class="k-top">
                    <span class="k-l">Total Revenue</span>
                    <span class="dchip {{ $kpi['revenue']['dir'] === 'up' ? 'up' : ($kpi['revenue']['dir'] === 'dn' ? 'down' : 'flat') }}">
                        @if ($kpi['revenue']['dir'] === 'up')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                        @elseif ($kpi['revenue']['dir'] === 'dn')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>@endif
                        {{ $kpi['revenue']['delta'] }}%
                    </span>
                </div>
                <div class="k-v">{{ $money($kpi['revenue']['value']) }}</div>
                <div class="k-n">{{ $kpi['revenue']['sub'] }}</div>
            </div>

            <div class="kpi" style="--d:110ms">
                <div class="k-top">
                    <span class="k-l">Total Expenses</span>
                    <span class="dchip {{ $kpi['expenses']['dir'] === 'dn' ? 'down' : ($kpi['expenses']['dir'] === 'up' ? 'flat' : 'flat') }}">
                        @if ($kpi['expenses']['dir'] === 'up')<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>@endif
                        {{ $kpi['expenses']['delta'] }}%
                    </span>
                </div>
                <div class="k-v">{{ $money($kpi['expenses']['value']) }}</div>
                <div class="k-n">{{ $kpi['expenses']['sub'] }}</div>
            </div>

            <div class="kpi" style="--d:160ms">
                <div class="k-top">
                    <span class="k-l">Net Profit</span>
                    <span class="dchip {{ $kpi['net']['margin_dir'] === 'dn' ? 'warn' : 'flat' }}">margin {{ $kpi['net']['margin'] }}%</span>
                </div>
                <div class="k-v">{{ $money($kpi['net']['value']) }}</div>
                <div class="k-n">{{ $kpi['net']['sub'] }}</div>
            </div>

            <a class="kpi kpi-link" style="--d:210ms" href="{{ $kpi['outstanding']['href'] }}" title="View outstanding invoices">
                <div class="k-top">
                    <span class="k-l">Outstanding Inv.</span>
                    <span class="dchip {{ $kpi['outstanding']['overdue'] > 0 ? 'warn' : 'flat' }}">{{ $kpi['outstanding']['unpaid'] }} unpaid</span>
                </div>
                <div class="k-v">{{ $money($kpi['outstanding']['value']) }}</div>
                <div class="k-n">{{ $kpi['outstanding']['sub'] }}</div>
                <span class="go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></span>
            </a>

            <a class="kpi kpi-link" style="--d:260ms" href="{{ $kpi['payables']['href'] }}" title="View bills payable">
                <div class="k-top">
                    <span class="k-l">Bills Payable</span>
                    <span class="dchip {{ $kpi['payables']['overdue'] > 0 ? 'warn' : 'flat' }}">{{ $kpi['payables']['overdue'] }} overdue</span>
                </div>
                <div class="k-v">{{ $money($kpi['payables']['value']) }}</div>
                <div class="k-n">{{ $kpi['payables']['sub'] }}</div>
                <span class="go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></span>
            </a>

            <a class="kpi kpi-link" style="--d:310ms" href="{{ $bankUrl }}" title="View bank &amp; cash accounts">
                <div class="k-top">
                    <span class="k-l">Cash &amp; Bank</span>
                    <span class="dchip flat">{{ $kpi['cash']['num_accounts'] }} {{ $kpi['cash']['num_accounts'] === 1 ? 'account' : 'accounts' }}</span>
                </div>
                <div class="k-v">{{ $money($kpi['cash']['value']) }}</div>
                <div class="k-n">Available across all accounts</div>
                <span class="go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></span>
            </a>
        </section>

        {{-- ROW 1 --}}
        <div class="row">
            <section class="card" style="--d:120ms">
                <div class="c-head">
                    <span class="c-title"><i></i>Revenue vs Expenses</span>
                    <div class="legend">
                        <span class="lg rev"><i></i>Revenue</span>
                        <span class="lg exp"><i></i>Expenses</span>
                        <span class="lg-chip">{{ $chart['caption'] }}</span>
                    </div>
                </div>
                <div class="plot">
                    <div class="cols">
                        @foreach ($chart['labels'] as $i => $label)
                            <div class="colM">
                                <div class="bars">
                                    <span class="bar rev" style="height:{{ $barPct($chart['rev_pct'][$i] ?? 0) }}%" data-v="{{ $money($chart['revenue'][$i] ?? 0) }}"></span>
                                    <span class="bar exp" style="height:{{ $barPct($chart['exp_pct'][$i] ?? 0) }}%" data-v="{{ $money($chart['expenses'][$i] ?? 0) }}"></span>
                                </div>
                                <span class="m-lbl">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="chart-foot">
                    <div class="cf"><span class="l">Money in</span><span class="v">{{ $money($chart['total_revenue']) }}</span></div>
                    <div class="cf"><span class="l">Money out</span><span class="v">{{ $money($chart['total_expenses']) }}</span></div>
                    <div class="cf"><span class="l">Net</span><span class="v {{ $chartNet >= 0 ? 'pos' : '' }}">{{ $signedMoney($chartNet) }}</span></div>
                </div>
            </section>

            <section class="card" style="--d:160ms">
                <div class="c-head">
                    <span class="c-title"><i></i>Cash Position</span>
                    <a class="c-link" href="{{ $bankUrl }}">Banking <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                </div>
                @if (! empty($cash['rows']))
                    <div class="cash">
                        <span class="cl">Cash on hand</span>
                        <span class="cv">{{ $money($cash['total']) }}</span>
                        <span class="cs">{{ $cash['num_accounts'] }} {{ $cash['num_accounts'] === 1 ? 'account' : 'accounts' }} &middot; {{ $cash['cheques'] }} outstanding {{ $cash['cheques'] === 1 ? 'cheque' : 'cheques' }}</span>

                        <div class="mix" aria-hidden="true">
                            @if ($bankPct > 0)<i style="width:{{ $bankPct }}%"></i>@endif
                            @if ($cashPct > 0)<i style="width:{{ $cashPct }}%"></i>@endif
                        </div>

                        <div class="acc-list">
                            @foreach ($cash['rows'] as $row)
                                <a class="acc" href="{{ $bankUrl }}">
                                    <span class="aic">
                                        @if ($row['badge'] === 'CASH')
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11"/></svg>
                                        @endif
                                    </span>
                                    <span class="an">{{ $row['name'] }}</span>
                                    <span class="at">{{ $row['badge'] }}</span>
                                    <span class="av">{{ $money($row['value']) }}</span>
                                </a>
                            @endforeach
                        </div>

                        <div class="note">
                            <i style="background:{{ $cash['cheques'] > 0 ? '#e8a33c' : '#5f7476' }}; box-shadow:none;"></i>
                            {{ $cash['cheques'] > 0
                                ? $cash['cheques'] . ' outstanding ' . ($cash['cheques'] === 1 ? 'cheque' : 'cheques') . ' awaiting clearance'
                                : 'No cheques awaiting clearance' }}
                        </div>
                    </div>
                @else
                    <div class="zero">No cash or bank accounts yet. Set up an account to start tracking cash on hand.</div>
                @endif
            </section>
        </div>

        {{-- ROW 2 --}}
        <div class="row">
            <section class="card" style="--d:200ms">
                <div class="c-head">
                    <span class="c-title"><i></i>Receivables Aging</span>
                    <a class="c-link" href="{{ route('accounting.aging.ar-summary') }}">View AR Aging <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                </div>
                <div class="age">
                    @foreach ($aging['buckets'] as $i => $bucket)
                        <div class="age-row">
                            <span class="l">{{ $bucket['label'] }}</span>
                            <span class="track"><i class="{{ $agingTracks[$i] ?? 't1' }}" style="width:{{ max(0, (float) $bucket['pct']) }}%"></i></span>
                            <span class="v">{{ $money($bucket['amount']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="age-total">
                    <span class="l">Total receivables</span>
                    <span class="v">{{ $money($aging['total']) }}</span>
                </div>
            </section>

            <section class="card" style="--d:240ms">
                <div class="c-head">
                    <span class="c-title"><i></i>Upcoming Bills &amp; Taxes</span>
                    <a class="c-link" href="{{ $billsUrl }}">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                </div>
                <div class="bills">
                @forelse ($upcoming['items'] as $i => $item)
                    <button type="button" class="bill" data-bill="{{ $i }}" title="View details">
                        <span class="bic">
                            @if ($item['icon'] === 'tax')
                                <svg viewBox="0 0 24 24" aria-hidden="true"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            @endif
                        </span>
                        <span class="bm">
                            <span class="bn">{{ $item['name'] }}</span>
                            <span class="br">{{ $item['ref_line'] }}</span>
                        </span>
                        <span class="bd">
                            <span class="due {{ $item['due_tone'] }}">{{ $item['due_label'] }}</span>
                            <span class="bv">{{ $money($item['amount']) }}</span>
                        </span>
                        <span class="act-go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></span>
                    </button>
                @empty
                    <div class="zero">Nothing due in the next 45 days. Your bills and tax filings are all clear.</div>
                @endforelse
                </div>
                <div class="bills-foot">
                    <span class="l">Total overdue</span>
                    <span class="v">{{ $money($upcoming['overdue_total']) }}</span>
                </div>
            </section>
        </div>

        {{-- ROW 3 --}}
        <div class="row">
            <section class="card" style="--d:280ms">
                <div class="c-head"><span class="c-title"><i></i>Quick Actions</span></div>
                <div class="qa">
                    @if ($can['invoice'])
                        <a href="{{ route('accounting.invoices.create') }}" class="qa-t primary">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></svg></span>New Invoice<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                    @if ($can['bill'])
                        <a href="{{ route('accounting.bills.create') }}" class="qa-t">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></svg></span>New Bill<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                    @if ($can['payment'])
                        <a href="{{ route('accounting.customer-payments.create') }}" class="qa-t">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg></span>Record Payment<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                    @if ($can['journal'])
                        <a href="{{ route('accounting.journal-entries.create') }}" class="qa-t">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg></span>New Journal<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                    @if ($can['customer'])
                        <a href="{{ route('accounting.customers.create') }}" class="qa-t">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></span>New Customer<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                    @if ($can['reconcile'])
                        <a href="{{ route('accounting.bank-reconciliation.index') }}" class="qa-t">
                            <span class="ic"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></span>Reconcile<span class="plus" aria-hidden="true">+</span>
                        </a>
                    @endif
                </div>
            </section>

            <section class="card" style="--d:320ms">
                <div class="c-head">
                    <span class="c-title"><i></i>My Tasks</span>
                    <a class="c-link" href="{{ route('todo.index') }}">Open tasks <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
                </div>
                @if (! empty($tasks))
                    <div class="task-list">
                        @foreach ($tasks as $task)
                            <a class="task" href="{{ $task['url'] }}">
                                <span class="tn">{{ $task['label'] }}</span>
                                <span class="tt" @if ($task['overdue']) style="color:var(--red-2); font-weight:800;" @endif>{{ $task['due'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="tasks-empty">
                        <div class="halo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></div>
                        <div class="t">You&rsquo;re all caught up</div>
                        <div class="s">No tasks or approvals are waiting on you.</div>
                        <a class="btn-ghost" href="{{ route('todo.index') }}">
                            <svg viewBox="0 0 24 24" style="width:13px;height:13px;stroke:currentColor;stroke-width:2.5;fill:none;stroke-linecap:round;stroke-linejoin:round;" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Add a task
                        </a>
                    </div>
                @endif
            </section>
        </div>

        {{-- RECENT ACTIVITY --}}
        <section class="card" style="--d:360ms; margin-top:18px;">
            <div class="c-head">
                <span class="c-title"><i></i>Recent Activity</span>
                <a class="c-link" href="{{ route('accounting.journal-entries.index') }}">View all <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
            </div>

            @forelse ($activity as $i => $event)
                <button type="button" class="act-row" data-act="{{ $i }}">
                    <span class="act-ic {{ in_array($event['tone'], ['green', 'amber', 'teal'], true) ? $event['tone'] : 'teal' }}">
                        @if ($event['kind'] === 'customer_payment')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
                        @elseif ($event['kind'] === 'vendor_payment')
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>
                        @endif
                    </span>
                    <span class="act-m">
                        <span class="act-t">
                            <b>{{ $event['title'] }}</b>
                            @if (! empty($event['party'])) &mdash; <span class="pty">{{ $event['party'] }}</span>@endif
                            @if (! empty($event['ref'])) &middot; <span class="ref">{{ $event['ref'] }}</span>@endif
                        </span>
                        <span class="act-time">{{ $event['when'] }} &middot; {{ $event['module'] }}</span>
                    </span>
                    <span class="act-v {{ $event['dir'] === 'out' ? 'out' : ($event['dir'] === 'in' ? 'in' : '') }}">{{ $signedMoney($event['amount'], $event['dir']) }}</span>
                    <span class="act-go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg></span>
                </button>
            @empty
                <div class="zero">No activity recorded yet. Post a journal, invoice or payment and it will show up here.</div>
            @endforelse
        </section>

    </div>

    {{-- ACTIVITY DETAIL MODAL --}}
    <div class="scrim" id="actScrim" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="amTitle">
            <div class="m-head">
                <div class="m-ic" id="amIcon"></div>
                <div class="m-head-left">
                    <span class="m-title" id="amTitle">&nbsp;</span>
                    <span class="m-sub" id="amRef">&nbsp;</span>
                </div>
                <span class="m-pill" id="amAmt">&nbsp;</span>
                <button type="button" class="m-close" data-close aria-label="Close"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="m-body">
                <div class="mrow"><span class="l">Date &amp; time</span><span class="v" id="amDate">&mdash;</span></div>
                <div class="mrow"><span class="l">Module</span><span class="v" id="amModule">&mdash;</span></div>
                <div class="mrow"><span class="l">Recorded by</span><span class="v" id="amBy">&mdash;</span></div>
                <div class="mrow" id="amPartyRow" style="display:none"><span class="l" id="amPartyLabel">Party</span><span class="v" id="amParty">&mdash;</span></div>
                <div class="mrow"><span class="l">Status</span><span class="v"><span class="stchip ok" id="amStatus"><i></i>&nbsp;</span></span></div>
                <div id="amLines"></div>
                <div class="m-memo"><span class="ml2">Description</span><span id="amMemo">&mdash;</span></div>
            </div>
            <div class="m-foot">
                <button type="button" class="btn btn-ghost" data-close>Close</button>
                <span class="spacer"></span>
                <a class="btn btn-primary" id="amOpen" href="#">Open full page<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"/></svg></a>
            </div>
        </div>
    </div>

    {{-- BILL / TAX DETAIL MODAL --}}
    <div class="scrim" id="billScrim" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="bmTitle">
            <div class="m-head">
                <div class="m-ic" id="bmIcon"></div>
                <div class="m-head-left">
                    <span class="m-title" id="bmTitle">&nbsp;</span>
                    <span class="m-sub" id="bmRef">&nbsp;</span>
                </div>
                <span class="m-pill" id="bmAmt">&nbsp;</span>
                <button type="button" class="m-close" data-bclose aria-label="Close"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="m-body" id="bmBody"></div>
            <div class="m-foot">
                <button type="button" class="btn btn-ghost" data-bclose>Close</button>
                <span class="spacer"></span>
                <a class="btn btn-primary" id="bmOpen" href="#">Open full page<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"/></svg></a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
window.DASH_ACTIVITY = @json($activity);
window.DASH_UPCOMING = @json(array_values($upcoming['items']));

var ACT = window.DASH_ACTIVITY;
var BILLS = window.DASH_UPCOMING;

    var ICONS = {
        doc: '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>',
        book: '<svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>',
        money: '<svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>',
        tax: '<svg viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>'
    };

    var $ = function (id) { return document.getElementById(id); };
    var lastFocus = null;

    function el(tag, cls, text) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (text !== undefined && text !== null) { n.textContent = String(text); }
        return n;
    }

    function mrow(label, valueNode) {
        var row = el('div', 'mrow');
        row.appendChild(el('span', 'l', label));
        var v = el('span', 'v');
        v.appendChild(valueNode);
        row.appendChild(v);
        return row;
    }

    function chip(text, tone) {
        var c = el('span', 'stchip ' + (tone || 'wait'));
        c.appendChild(document.createElement('i'));
        c.appendChild(document.createTextNode(text));
        return c;
    }

    /* ---------- ACTIVITY MODAL ---------- */
    var actScrim = $('actScrim');

    function openAct(i) {
        var a = ACT[i];
        if (!a) { return; }

        var icon = a.kind === 'customer_payment' ? 'money' : (a.kind === 'vendor_payment' ? 'doc' : 'book');
        $('amIcon').innerHTML = ICONS[icon];
        $('amTitle').textContent = a.title || '';
        $('amRef').textContent = a.ref || a.module || '';
        $('amAmt').textContent = a.amount_display || '';
        $('amDate').textContent = (a.date_display || '—') + (a.time_display ? ' · ' + a.time_display : '');
        $('amModule').textContent = a.module || '—';
        $('amBy').textContent = a.recorded_by || '—';

        var partyRow = $('amPartyRow');
        if (a.party) {
            partyRow.style.display = '';
            $('amPartyLabel').textContent = a.party_label || 'Party';
            $('amParty').textContent = a.party;
        } else {
            partyRow.style.display = 'none';
        }

        var status = $('amStatus');
        status.className = 'stchip ' + (a.status_tone || 'ok');
        while (status.firstChild) { status.removeChild(status.firstChild); }
        status.appendChild(document.createElement('i'));
        status.appendChild(document.createTextNode(a.status || '—'));

        var host = $('amLines');
        host.textContent = '';
        if (a.lines && a.lines.length) {
            var box = el('div', 'm-lines');
            box.appendChild(el('div', 'm-lines-h', 'Accounts & Amounts'));
            a.lines.forEach(function (l) {
                var row = el('div', 'm-line');
                row.appendChild(el('span', 'side ' + (l.side === 'DR' ? 'dr' : 'cr'), l.side));
                row.appendChild(el('span', 'code', l.code));
                row.appendChild(el('span', 'name', l.name));
                row.appendChild(el('span', 'amt', l.amt));
                box.appendChild(row);
            });
            host.appendChild(box);
        }

        $('amMemo').textContent = a.memo || a.desc || '—';
        $('amOpen').setAttribute('href', a.href || '#');

        lastFocus = document.activeElement;
        actScrim.hidden = false;
        actScrim.classList.add('on');
        var closeBtn = actScrim.querySelector('.m-close');
        if (closeBtn) { closeBtn.focus(); }
    }

    function closeAct() {
        actScrim.classList.remove('on');
        actScrim.hidden = true;
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        lastFocus = null;
    }

    /* ---------- BILL / TAX MODAL ---------- */
    var billScrim = $('billScrim');

    function openBill(i) {
        var b = BILLS[i];
        if (!b) { return; }

        $('bmIcon').innerHTML = ICONS[b.icon] || ICONS.doc;
        $('bmTitle').textContent = (b.kind === 'tax' ? 'Statutory Tax' : 'Vendor Bill');
        $('bmRef').textContent = b.ref || '';
        $('bmAmt').textContent = b.amount_display || '';

        var body = $('bmBody');
        body.textContent = '';

        (b.rows || []).forEach(function (r) {
            if (r.chip) {
                var isDue = r.chip.tone === 'over' || r.chip.tone === 'soon';
                var node = isDue ? el('span', 'due ' + r.chip.tone, r.chip.text) : chip(r.chip.text, r.chip.tone);
                if (r.v) {
                    var wrap = el('span');
                    wrap.appendChild(el('span', null, r.v));
                    wrap.appendChild(document.createTextNode(' '));
                    wrap.appendChild(node);
                    body.appendChild(mrow(r.l, wrap));
                } else {
                    body.appendChild(mrow(r.l, node));
                }
            } else {
                body.appendChild(mrow(r.l, el('span', null, r.v || '—')));
            }
        });

        if (b.desc) {
            var memo = el('div', 'm-memo');
            memo.appendChild(el('span', 'ml2', 'Description'));
            memo.appendChild(el('span', null, b.desc));
            body.appendChild(memo);
        }

        $('bmOpen').setAttribute('href', b.href || '#');

        lastFocus = document.activeElement;
        billScrim.hidden = false;
        billScrim.classList.add('on');
        var closeBtn = billScrim.querySelector('.m-close');
        if (closeBtn) { closeBtn.focus(); }
    }

    function closeBill() {
        billScrim.classList.remove('on');
        billScrim.hidden = true;
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
        lastFocus = null;
    }

    /* ---------- WIRING ---------- */
    document.querySelectorAll('.act-row').forEach(function (r) {
        r.addEventListener('click', function () { openAct(parseInt(r.dataset.act, 10)); });
    });

    document.querySelectorAll('.bill').forEach(function (r) {
        r.addEventListener('click', function () { openBill(parseInt(r.dataset.bill, 10)); });
    });

    document.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', closeAct);
    });

    document.querySelectorAll('[data-bclose]').forEach(function (b) {
        b.addEventListener('click', closeBill);
    });

    actScrim.addEventListener('click', function (e) { if (e.target === actScrim) { closeAct(); } });
    billScrim.addEventListener('click', function (e) { if (e.target === billScrim) { closeBill(); } });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        if (actScrim.classList.contains('on')) { closeAct(); }
        if (billScrim.classList.contains('on')) { closeBill(); }
    });
})();
</script>
@endpush
</x-app-layout>