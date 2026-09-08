<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'customers';
            $title = 'Customers';
            $subtitle = 'Customer growth, retention and concentration for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.customers') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($period['key'] === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <span class="an-flabel">Branch
                <select name="branch_id" class="an-input">
                    <option value="">All branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected((int) request('branch_id') === $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.customers') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $k = $data['kpis'] ?? [];
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Active Customers</div><div class="v">{{ number_format($k['active']['value'] ?? 0, 0) }}</div></div>
            <div class="an-kpi"><div class="l">New Customers</div><div class="v up">{{ number_format($k['new']['value'] ?? 0, 0) }}</div></div>
            <div class="an-kpi"><div class="l">Churn</div><div class="v dn">{{ format_number($k['churn']['value'] ?? 0, 1) }}%</div></div>
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($data['total_revenue'] ?? 0)</div></div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Key Customer Metrics</h2>
                <table class="an-tbl">
                    <tbody>
                        <tr><td>Avg Revenue per Customer</td><td class="r">@money($k['arpc']['value'] ?? 0)</td></tr>
                        <tr><td>Top 10 Customer Share</td><td class="r">{{ format_number($k['top10_share']['value'] ?? 0, 1) }}%</td></tr>
                    </tbody>
                </table>
                <p class="an-note">Share is the percent of total revenue contributed by the ten largest customers by revenue in the period.</p>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Top Customers</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th class="r">Orders</th>
                                <th class="r">Revenue</th>
                                <th class="r">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['top_customers'] ?? []) as $row)
                                <tr>
                                    <td class="c">{{ $row['rank'] }}</td>
                                    <td>{{ $row['name'] }}</td>
                                    <td class="r">{{ number_format($row['orders'] ?? 0, 0) }}</td>
                                    <td class="r">@money($row['revenue'] ?? 0)</td>
                                    <td class="r">{{ format_number($row['share'] ?? 0, 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="c an-empty-cell">No customer revenue</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>