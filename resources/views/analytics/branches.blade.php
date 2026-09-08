<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'branches';
            $title = 'Branches';
            $subtitle = 'Branch revenue, profit and growth for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.branches') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                        <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.branches') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $rows = $data['rows'] ?? [];
            $totalRev = (float) ($data['total_revenue'] ?? 0);
            $totalProf = (float) ($data['total_profit'] ?? 0);
            $avgMargin = $totalRev > 0 ? ($totalProf / $totalRev) * 100 : 0;
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($totalRev)</div></div>
            <div class="an-kpi"><div class="l">Total Profit</div><div class="v {{ $totalProf >= 0 ? 'up' : 'dn' }}">@money($totalProf)</div></div>
            <div class="an-kpi"><div class="l">Branches</div><div class="v">{{ count($rows) }}</div></div>
            <div class="an-kpi"><div class="l">Overall Margin</div><div class="v">{{ format_number($avgMargin, 1) }}%</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Branch Performance</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="r">Revenue</th>
                            <th class="r">Expenses</th>
                            <th class="r">Profit</th>
                            <th class="r">Margin</th>
                            <th class="r">Growth vs Prev</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                                <td class="r">@money($row['expense'] ?? 0)</td>
                                <td class="r {{ ($row['profit'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['profit'] ?? 0)</td>
                                <td class="r">{{ isset($row['margin']) && $row['margin'] !== null ? format_number($row['margin'], 1) . '%' : '—' }}</td>
                                <td class="r {{ ($row['growth'] ?? 0) >= 0 ? 'up' : 'dn' }}">{{ isset($row['growth']) && $row['growth'] !== null ? format_number($row['growth'], 1) . '%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="c an-empty-cell">No branch data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>