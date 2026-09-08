<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'pvm';
            $title = 'Price Volume Mix';
            $subtitle = 'Decompose the revenue change for ' . strtolower($period['label']) . ' into price, volume and mix effects.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $rows = $data['rows'] ?? [];
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Previous Revenue</div><div class="v">@money($data['previous_revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Current Revenue</div><div class="v">@money($data['current_revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Total Change</div><div class="v {{ ($data['total_change'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['total_change'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Products Compared</div><div class="v">{{ count($rows) }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Effect Breakdown</h2>
                    <p class="an-card-s">Change vs previous comparable period</p>
                </div>
            </div>
            <div class="an-kpis an-kpis--3">
                <div class="an-kpi"><div class="l">Price Effect</div><div class="v {{ ($data['price_effect'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['price_effect'] ?? 0)</div></div>
                <div class="an-kpi"><div class="l">Volume Effect</div><div class="v {{ ($data['volume_effect'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['volume_effect'] ?? 0)</div></div>
                <div class="an-kpi"><div class="l">Mix Effect</div><div class="v {{ ($data['mix_effect'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['mix_effect'] ?? 0)</div></div>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Product Detail</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="r">Previous Qty</th>
                            <th class="r">Current Qty</th>
                            <th class="r">Prev Revenue</th>
                            <th class="r">Cur Revenue</th>
                            <th class="r">Prev Price</th>
                            <th class="r">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['product_name'] ?? '' }}</td>
                                <td class="nt">{{ $row['sku'] ?? '—' }}</td>
                                <td class="r">{{ format_number($row['prev_qty'] ?? 0, 0) }}</td>
                                <td class="r">{{ format_number($row['cur_qty'] ?? 0, 0) }}</td>
                                <td class="r">@money($row['prev_revenue'] ?? 0)</td>
                                <td class="r">@money($row['cur_revenue'] ?? 0)</td>
                                <td class="r">@money($row['prev_price'] ?? 0)</td>
                                <td class="r">@money($row['price'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="c an-empty-cell">No comparable product revenue yet for the selected period pair.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="an-note">Compared against the immediately preceding window of equal length where available.</p>
    </div>
</x-app-layout>