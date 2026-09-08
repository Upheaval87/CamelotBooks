<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'overview';
            $title = 'Overview';
            $subtitle = 'A snapshot of the business: revenue, margin, cash runway and key health signals.';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <div class="an-kpis an-kpis--5">
            @foreach ([
                'revenue' => ['label' => 'Revenue', 'unit' => 'money'],
                'margin' => ['label' => 'Net Margin', 'unit' => 'pct'],
                'expense' => ['label' => 'Expenses', 'unit' => 'money'],
                'runway' => ['label' => 'Cash Runway', 'unit' => 'months'],
                'working_capital' => ['label' => 'Working Capital', 'unit' => 'money'],
            ] as $k => $spec)
                @php
                    $kpi = $data['kpis'][$k] ?? ['value' => null, 'prev' => null];
                    $val = $kpi['value'] ?? null;
                    $prev = $kpi['prev'] ?? null;
                    $isPct = $spec['unit'] === 'pct';
                    $isMonths = $spec['unit'] === 'months';
                    $delta = (is_numeric($val) && is_numeric($prev) && (float) $prev != 0)
                        ? ((float) $val - (float) $prev) / abs((float) $prev) * 100 : null;
                    $dir = $delta === null ? 'nt' : ($delta > 0.05 ? 'up' : ($delta < -0.05 ? 'dn' : 'nt'));
                @endphp
                <div class="an-kpi">
                    <div class="l">{{ $spec['label'] }}</div>
                    <div class="v">
                        @if ($val === null)
                            &mdash;
                        @elseif ($isPct)
                            {{ format_number($val, 1) }}%
                        @elseif ($isMonths)
                            {{ format_number($val, 1) }} mo
                        @else
                            @money($val)
                        @endif
                    </div>
                    <div class="d">
                        @if ($delta !== null)
                            <span class="an-delta {{ $dir }}" aria-label="{{ round($delta, 1) }}% vs previous period">
                                @if ($dir === 'up')&#9650;@elseif ($dir === 'dn')&#9660;@else&#9679;@endif
                                {{ format_number(abs($delta), 1) }}%
                            </span>
                            <span class="vs">vs prev.</span>
                        @else
                            <span class="an-delta nt">no prior data</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @php
            $trendLabels = $data['trend']['labels'] ?? [];
            $revTrend = $data['trend']['revenue_data'] ?? [];
            $expTrend = $data['trend']['expense_data'] ?? [];
            $maxTrend = max(array_merge([1], $revTrend, $expTrend));
        @endphp
        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Revenue &amp; Expense Trend</h2>
                    <p class="an-card-s">Trailing 12 months to {{ $period['to'] }}</p>
                </div>
                <div class="an-legend">
                    <span class="an-legend-k"><i class="sw rev"></i>Revenue</span>
                    <span class="an-legend-k"><i class="sw exp"></i>Expenses</span>
                    <span class="an-legend-k"><i class="sw net"></i>Net</span>
                </div>
            </div>
            @if ($trendLabels)
                <div class="an-bars">
                    @foreach ($trendLabels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: rev @money($revTrend[$i] ?? 0)">
                                {{ format_number(($revTrend[$i] ?? 0), 0) }}
                            </span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b rev" style="height:{{ (($revTrend[$i] ?? 0) / $maxTrend) * 100 }}%"></i>
                                <i class="an-bar-b exp" style="height:{{ (($expTrend[$i] ?? 0) / $maxTrend) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No revenue or expense activity in this period.</div>
            @endif
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Health Signals</h2>
                <p class="an-card-s">Automated checks against typical benchmarks.</p>
                <div class="an-sigs">
                    @forelse (($data['signals'] ?? []) as $sig)
                        <div class="an-sig">
                            <div class="an-sig-l">
                                <span>{{ $sig['label'] }}</span>
                                <span class="an-sig-g">{{ $sig['good_when'] }}</span>
                            </div>
                            <div class="an-sig-v">{{ format_number($sig['value'] ?? 0, 1) }}%</div>
                            <span class="an-chip {{ ($sig['status'] ?? 'watch') === 'ok' ? 'c-ok' : 'c-warn' }}">
                                {{ ($sig['status'] ?? 'watch') === 'ok' ? 'Healthy' : 'Watch' }}
                            </span>
                        </div>
                    @empty
                        <div class="an-empty">No signals to report.</div>
                    @endforelse
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Period-over-Period</h2>
                <p class="an-card-s">Current vs previous equal period ({{ $period['prev_from'] }} &#8594; {{ $period['prev_to'] }}).</p>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th class="r">Previous</th>
                                <th class="r">Current</th>
                                <th class="r">Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['variants'] ?? []) as $row)
                                @php
                                    $cur = (float) ($row['current'] ?? 0);
                                    $prev = (float) ($row['previous'] ?? 0);
                                    $delta = $prev != 0 ? ($cur - $prev) / abs($prev) * 100 : null;
                                    $good = $row['good_when'] ?? 'up';
                                    $cls = $delta === null ? 'nt' : (($good === 'up') === ($delta >= 0) ? 'up' : 'dn');
                                @endphp
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="r">@money($prev)</td>
                                    <td class="r">{{ $cls === 'up' ? '&uarr;' : ($cls === 'dn' ? '&darr;' : '') }} @money($cur)</td>
                                    <td class="r {{ $cls }}">
                                        @if ($delta === null)&mdash;@else{{ format_number($delta, 1) }}%@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="c an-empty-cell">No data this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>