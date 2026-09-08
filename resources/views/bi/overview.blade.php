<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'overview';
            $title = 'Overview & Command Centre';
            $subtitle = 'Real-time financial health check - revenue, profitability, cash, working capital and alerts for ' . $period['label'] . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters', ['withCostCenter' => true])

        @php
            $k = $data['kpis'] ?? [];
            $st = $data['statement'] ?? [];
            $prevSt = $data['previous'] ?? [];
            $trend = $data['trend'] ?? [];
            $alerts = $data['alerts'] ?? [];
            $cash = $data['cash'] ?? ['bank' => 0, 'cash' => 0, 'total' => 0, 'accounts' => []];
            $ar = $data['ar'] ?? [];
            $ap = $data['ap'] ?? [];
        @endphp

        <div class="an-kpis an-kpis--3">
            @foreach (['revenue' => 'Revenue', 'gross_profit' => 'Gross Profit', 'net_income' => 'Net Income'] as $key => $label)
                @php
                    $val = (float) ($k[$key]['value'] ?? 0);
                    $pval = $k[$key]['prev'] ?? null;
                    $cls = $val < 0 ? 'dn' : ($val > 0 ? 'up' : 'nt');
                    $delta = ($pval !== null && (float) $pval != 0) ? (($val - (float) $pval) / abs((float) $pval)) * 100 : 0;
                @endphp
                <div class="an-kpi">
                    <div class="l">{{ $label }}</div>
                    <div class="v {{ $cls }}">@money($val)</div>
                    @if ($pval !== null)
                        <div class="d">
                            <span class="vs">vs {{ format_money($pval) }}</span>
                            <span class="an-delta {{ $delta >= 0 ? 'up' : 'dn' }}">{{ ($delta >= 0 ? '+' : '') . format_number($delta, 1) }}%</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="an-kpis an-kpis--4">
            @foreach ([
                'cash_balance' => 'Cash Balance',
                'ar_outstanding' => 'Receivables',
                'ap_outstanding' => 'Payables',
                'inventory_value' => 'Inventory Value',
            ] as $key => $label)
                @php $val = (float) ($k[$key]['value'] ?? 0); @endphp
                <div class="an-kpi">
                    <div class="l">{{ $label }}</div>
                    <div class="v">{{ $key === 'cash_balance' ? '' : ($val < 0 ? '-' : '') }}{{ format_money(abs($val)) }}</div>
                </div>
            @endforeach
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Revenue vs Expenses</h2>
                    <p class="an-card-s">Trailing 6 months</p>
                </div>
                <div class="an-legend">
                    <span class="an-legend-k"><span class="an-bar-b rev" style="width:10px;height:10px;border-radius:3px;display:inline-block;"></span> Revenue</span>
                    <span class="an-legend-k"><span class="an-bar-b exp" style="width:10px;height:10px;border-radius:3px;display:inline-block;"></span> Expenses</span>
                </div>
            </div>
            @php
                $tl = $trend['labels'] ?? [];
                $tr = $trend['revenue'] ?? [];
                $te = $trend['expense'] ?? [];
                $maxT = 1.0;
                foreach (array_merge(array_values($tr), array_values($te)) as $v) {
                    $maxT = max($maxT, (float) $v);
                }
            @endphp
            @if ($tl)
                <div class="an-bars">
                    @foreach ($tl as $i => $lbl)
                        @php
                            $rv = (float) ($tr[$i] ?? 0);
                            $ev = (float) ($te[$i] ?? 0);
                            $rh = $rv > 0 ? max(2, ($rv / $maxT) * 100) : 0;
                            $eh = $ev > 0 ? max(2, ($ev / $maxT) * 100) : 0;
                        @endphp
                        <div class="an-bar">
                            <span class="an-bar-v">{{ format_number($rv - $ev, 0) }}</span>
                            <div class="an-bar-cols an-bar-cols--pair">
                                <span class="an-bar-b rev" style="height: {{ $rh }}%"></span>
                                <span class="an-bar-b exp" style="height: {{ $eh }}%"></span>
                            </div>
                            <span class="an-bar-l">{{ $lbl }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No trend data for this period.</div>
            @endif
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Command Centre Alerts</h2>
                <div class="an-sigs">
                    @forelse ($alerts as $alert)
                        <div class="an-sig">
                            <div class="an-sig-l">
                                <span>{{ $alert['label'] ?? '' }}</span>
                                <span class="an-sig-g">{{ $alert['detail'] ?? '' }}</span>
                            </div>
                            <span class="an-chip c-warn">{{ strtoupper($alert['level'] ?? 'info') }}</span>
                        </div>
                    @empty
                        <div class="an-empty">No alerts right now.</div>
                    @endforelse
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Income Statement</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead><tr><th>Metric</th><th class="r">{{ $period['label'] }}</th><th class="r">Previous Period</th></tr></thead>
                        <tbody>
                            @foreach ([
                                'Revenue' => 'revenue',
                                'Cost of Goods Sold' => 'cogs',
                                'Gross Profit' => 'gross_profit',
                                'Operating Expenses' => 'opex',
                                'Total Expenses' => 'total_expenses',
                                'Net Income' => 'net_income',
                            ] as $label => $key)
                                @php $cls = $key === 'net_income' ? ((float) ($st[$key] ?? 0) < 0 ? 'dn' : 'up') : ''; @endphp
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="r {{ $cls }}">@money($st[$key] ?? 0)</td>
                                    <td class="r">@money($prevSt[$key] ?? 0)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Receivables Aging</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead><tr><th>Bucket</th><th class="r">Amount</th></tr></thead>
                        <tbody>
                            @foreach (['current' => 'Current', 'days_1_30' => '1-30 days', 'days_31_60' => '31-60 days', 'days_61_90' => '61-90 days', 'days_90_plus' => '90+ days'] as $key => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="r {{ $key === 'current' ? '' : ((float) ($ar[$key] ?? 0) > 0 ? 'dn' : '') }}">@money($ar[$key] ?? 0)</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th>Total</th><th class="r">@money($ar['total'] ?? 0)</th></tr></tfoot>
                    </table>
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Payables Aging</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead><tr><th>Bucket</th><th class="r">Amount</th></tr></thead>
                        <tbody>
                            @foreach (['current' => 'Current', 'days_1_30' => '1-30 days', 'days_31_60' => '31-60 days', 'days_61_90' => '61-90 days', 'days_90_plus' => '90+ days'] as $key => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="r {{ $key === 'current' ? '' : ((float) ($ap[$key] ?? 0) > 0 ? 'dn' : '') }}">@money($ap[$key] ?? 0)</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th>Total</th><th class="r">@money($ap['total'] ?? 0)</th></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Cash & Bank Balances</h2>
                    <p class="an-card-s">As at {{ $period['as_of'] }} - bank {{ format_money($cash['bank'] ?? 0) }}, cash {{ format_money($cash['cash'] ?? 0) }}, total {{ format_money($cash['total'] ?? 0) }}</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead><tr><th>Account</th><th>Code</th><th class="c">Type</th><th class="r">Balance</th></tr></thead>
                    <tbody>
                        @forelse (($cash['accounts'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['name'] ?? '' }}</td>
                                <td>{{ $row['code'] ?? '' }}</td>
                                <td class="c"><span class="an-chip {{ ($row['bank'] ?? false) ? 'c-ct' : 'c-nt' }}">{{ ($row['bank'] ?? false) ? 'Bank' : 'Cash' }}</span></td>
                                <td class="r">@money($row['balance'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="c an-empty-cell">No cash or bank accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>