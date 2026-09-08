<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'product';
            $title = 'Product Profitability';
            $subtitle = 'Revenue, gross margin and ABC classification per product for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $rows = $data['rows'] ?? [];
            $totalRevenue = array_sum(array_column($rows, 'revenue'));
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Products</div><div class="v">{{ count($rows) }}</div></div>
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($totalRevenue)</div></div>
            <div class="an-kpi"><div class="l">Product Profit</div><div class="v">@money(array_sum(array_column($rows, 'gross_profit')))</div></div>
            <div class="an-kpi"><div class="l">A / B / C</div><div class="v" style="font-size:0.95rem;">{{ $data['a'] ?? 0 }} / {{ $data['b'] ?? 0 }} / {{ $data['c'] ?? 0 }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Product Performance</h2>
                    <p class="an-card-s">Grade A holds up to 80% of cumulative revenue, B up to 95%, the rest C.</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="r">Qty</th>
                            <th class="r">Revenue</th>
                            <th class="r">COGS</th>
                            <th class="r">Gross Profit</th>
                            <th class="r">Margin</th>
                            <th class="r">% of Revenue</th>
                            <th class="c">ABC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $cls = strtolower($row['abc_class'] ?? 'c'); @endphp
                            <tr>
                                <td>{{ $row['product_name'] ?? '' }}</td>
                                <td class="nt">{{ $row['sku'] ?? '—' }}</td>
                                <td class="r">{{ number_format((float) ($row['quantity'] ?? 0), 0) }}</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                                <td class="r">@money($row['cost_of_goods'] ?? 0)</td>
                                <td class="r {{ ($row['gross_profit'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['gross_profit'] ?? 0)</td>
                                <td class="r">{{ format_number($row['gross_margin_pct'] ?? 0, 1) }}%</td>
                                <td class="r">{{ format_number($row['revenue_pct_of_total'] ?? 0, 1) }}%</td>
                                <td class="c"><span class="an-chip {{ $cls === 'a' ? 'c-ct' : ($cls === 'b' ? 'c-ok' : 'c-nt') }}">{{ strtoupper($cls) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="c an-empty-cell">No product data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>