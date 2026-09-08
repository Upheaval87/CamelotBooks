<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'cash-flow-trend';
            $title = 'Cash Flow';
            $subtitle = 'Historical net cash flow by operating, investing and financing activity, with a forecast (' . $period['from'] . ' onward).';
            $hc = (int) ($data['historical_count'] ?? 0);
            $pc = (int) ($data['projection_count'] ?? 0);
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.cash-flow-trend') }}" class="an-filters">
            <span class="an-flabel">From
                <input type="date" name="date_from" class="an-input" value="{{ request('date_from', $period['from']) }}">
            </span>
            <span class="an-flabel">To
                <input type="date" name="date_to" class="an-input" value="{{ request('date_to', $period['to']) }}">
            </span>
            <span class="an-flabel">Projection months
                <input type="number" name="projection_months" class="an-input" min="1" max="24" value="{{ request('projection_months', $projectionMonths) }}">
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.cash-flow-trend') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $labels = $data['labels'] ?? [];
            $netAll = array_merge($data['net'] ?? [], $data['projection_net'] ?? []);
            $sumNet = array_sum($netAll);
            $projSum = array_sum($data['projection_net'] ?? []);
            $lastHist = ($data['net'][$hc - 1] ?? 0);
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Net Cash Flow</div><div class="v {{ $sumNet >= 0 ? 'up' : 'dn' }}">@money($sumNet)</div></div>
            <div class="an-kpi"><div class="l">Projected Net ({!! $data['projection_count'] ?? 0 !!} mo)</div><div class="v {{ $projSum >= 0 ? 'up' : 'dn' }}">@money($projSum)</div></div>
            <div class="an-kpi"><div class="l">Last Historical Month</div><div class="v {{ $lastHist >= 0 ? 'up' : 'dn' }}">@money($lastHist)</div></div>
            <div class="an-kpi"><div class="l">Historical Periods</div><div class="v">{{ $hc }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Net Cash Flow</h2>
                    <p class="an-card-s">Historical and projected net cash flow by period.</p>
                </div>
                <div class="an-legend">
                    <span class="an-legend-k"><i class="sw net"></i>Net</span>
                    <span class="an-legend-k"><i class="sw proj"></i>Projected</span>
                </div>
            </div>
            @php
                $maxN = max(array_merge([1], array_map('abs', $netAll)));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        @php
                            $isProj = $i >= $hc;
                            $net = $isProj ? ($data['projection_net'][$i - $hc] ?? 0) : ($data['net'][$i] ?? 0);
                        @endphp
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($net)">{{ format_number($net, 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b {{ $net >= 0 ? 'net' : 'neg' }} {{ $isProj ? 'proj' : '' }}" style="height:{{ (abs($net) / $maxN) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}{!! $isProj ? '<i class="an-chip c-warn">proj</i>' : '' !!}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No cash-movement data in this window.</div>
            @endif
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Monthly Breakdown</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="r">Operating</th>
                            <th class="r">Investing</th>
                            <th class="r">Financing</th>
                            <th class="r">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($labels as $i => $label)
                            @php
                                $isProj = $i >= $hc;
                                $idx = $i - $hc;
                                $operating = $isProj ? ($data['projection_operating'][$idx] ?? 0) : ($data['operating'][$i] ?? 0);
                                $investing = $isProj ? ($data['projection_investing'][$idx] ?? 0) : ($data['investing'][$i] ?? 0);
                                $financing = $isProj ? ($data['projection_financing'][$idx] ?? 0) : ($data['financing'][$i] ?? 0);
                                $net = $isProj ? ($data['projection_net'][$idx] ?? 0) : ($data['net'][$i] ?? 0);
                            @endphp
                            <tr class="{{ $isProj ? 'an-proj-row' : '' }}">
                                <td>{{ $label }}@if($isProj) <span class="an-chip c-warn">Projected</span>@endif</td>
                                <td class="r">@money($operating)</td>
                                <td class="r">@money($investing)</td>
                                <td class="r">@money($financing)</td>
                                <td class="r {{ $net >= 0 ? 'up' : 'dn' }}">@money($net)</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="c an-empty-cell">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="an-note">{{ $data['projection_note'] ?? 'Projected values are based on trend analysis and are not a guarantee of future performance.' }}</p>
        </div>
    </div>
</x-app-layout>