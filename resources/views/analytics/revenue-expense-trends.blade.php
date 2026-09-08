<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'revenue-expense-trends';
            $title = 'Revenue &amp; Expense';
            $subtitle = 'Income statement activity across the trailing 12 months to ' . $period['to'] . '.';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.revenue-expense-trends') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($period['key'] === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <span class="an-flabel">Break down by
                <select name="dimension" class="an-input">
                    @foreach (['none' => 'None', 'branch' => 'Branch', 'cost_center' => 'Cost Center'] as $k => $lbl)
                        <option value="{{ $k }}" @selected(($dimension ?? 'none') === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.revenue-expense-trends') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        <div class="an-kpis an-kpis--3">
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($data['total_revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Total Expenses</div><div class="v">@money($data['total_expense'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Net Income</div><div class="v">@money(($data['total_revenue'] ?? 0) - ($data['total_expense'] ?? 0))</div></div>
        </div>

        @php
            $labels = $data['labels'] ?? [];
            $rev = $data['revenue_data'] ?? [];
            $exp = $data['expense_data'] ?? [];
            $net = $data['net_income_data'] ?? [];
            $maxV = max(array_merge([1], array_map('abs', $rev), array_map('abs', $exp), array_map('abs', $net)));
        @endphp
        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Trend</h2>
                    <p class="an-card-s">Revenue, expenses and net income per period.</p>
                </div>
                <div class="an-legend">
                    <span class="an-legend-k"><i class="sw rev"></i>Revenue</span>
                    <span class="an-legend-k"><i class="sw exp"></i>Expenses</span>
                    <span class="an-legend-k"><i class="sw net"></i>Net</span>
                </div>
            </div>
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: net @money($net[$i] ?? 0)">{{ format_number(($net[$i] ?? 0), 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b rev" style="height:{{ ((abs($rev[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                                <i class="an-bar-b exp" style="height:{{ ((abs($exp[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                                <i class="an-bar-b net" style="height:{{ ((abs($net[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No income statement activity in this window.</div>
            @endif
        </div>

        @if (($dimension ?? 'none') !== 'none')
            <div class="an-card">
                <h2 class="an-card-t">Breakdown by {{ ($dimension ?? '') === 'branch' ? 'Branch' : 'Cost Center' }}</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Dimension</th>
                                @foreach ($labels as $label)<th class="r">{{ $label }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $dims = [];
                                foreach (($data['results'] ?? []) as $res) {
                                    foreach (($res['dimensions'] ?? []) as $d) {
                                        if (!isset($dims[$d['name']])) { $dims[$d['name']] = ['revenue' => [], 'expense' => []]; }
                                        $dims[$d['name']]['revenue'][] = $d['revenue'] ?? 0;
                                        $dims[$d['name']]['expense'][] = $d['expense'] ?? 0;
                                    }
                                }
                            @endphp
                            @forelse ($dims as $name => $series)
                                <tr>
                                    <td>{{ $name }}</td>
                                    @foreach ($labels as $i => $label)
                                        <td class="r" title="Revenue @money($series['revenue'][$i] ?? 0)">
                                            @money(($series['revenue'][$i] ?? 0) - ($series['expense'][$i] ?? 0))
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($labels) + 1 }}" class="c an-empty-cell">No dimension activity.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="an-card">
                <h2 class="an-card-t">Monthly Detail</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="r">Revenue</th>
                                <th class="r">Expenses</th>
                                <th class="r">Net Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['results'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['period'] }}</td>
                                    <td class="r">@money($row['revenue'] ?? 0)</td>
                                    <td class="r">@money($row['expense'] ?? 0)</td>
                                    <td class="r {{ ($row['net_income'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['net_income'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="c an-empty-cell">No data this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>