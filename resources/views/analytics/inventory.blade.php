<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'inventory';
            $title = 'Inventory';
            $subtitle = 'Stock value, valuation trend and movement for the period as of ' . $period['as_of'] . '.';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.inventory') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($period['key'] === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <span class="an-flabel">Slow-moving threshold (days)
                <input type="number" name="slow_moving_days" class="an-input" min="1" max="365" value="{{ request('slow_moving_days', $slowMovingDays) }}">
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.inventory') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $cur = $data['current_value'] ?? [];
            $turnover = $data['turnover'] ?? [];
            $adjs = (int) array_sum(array_column($data['stockouts'] ?? [], 'adjustment_count'));
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Stock Value</div><div class="v">@money($cur['total_value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Total Quantity</div><div class="v">{{ format_number($cur['total_quantity'] ?? 0, 0) }}</div></div>
            <div class="an-kpi"><div class="l">Tracked Items</div><div class="v">{{ format_number($cur['item_count'] ?? 0, 0) }}</div></div>
            <div class="an-kpi"><div class="l">Stockout Adjustments</div><div class="v {{ $adjs > 0 ? 'dn' : 'up' }}">{{ number_format($adjs, 0) }}</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Stock Value Trend</h2>
            @php
                $labels = $data['labels'] ?? [];
                $values = $data['value_data'] ?? [];
                $maxV = max(array_merge([1], array_map('abs', $values)));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($values[$i] ?? 0)">{{ format_number($values[$i] ?? 0, 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b rev" style="height:{{ ((abs($values[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No valuation history in this window.</div>
            @endif
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Low Stock Items</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="r">On Hand</th>
                                <th class="r">Reorder Point</th>
                                <th class="r">Shortage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['low_stock'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['product_name'] }} <span class="an-chip c-nt">{{ $row['sku'] }}</span></td>
                                    <td class="r">{{ format_number($row['quantity_on_hand'] ?? 0, 0) }}</td>
                                    <td class="r">{{ format_number($row['reorder_point'] ?? 0, 0) }}</td>
                                    <td class="r dn">@money($row['shortage'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="c an-empty-cell">All stock levels OK</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Slow-Moving Stock (&gt;{{ request('slow_moving_days', $slowMovingDays) }} days)</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="r">Qty</th>
                                <th class="r">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['slow_moving'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['product_name'] }} <span class="an-chip c-nt">{{ $row['sku'] }}</span></td>
                                    <td class="r">{{ format_number($row['old_quantity'] ?? 0, 0) }}</td>
                                    <td class="r">@money($row['old_value'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="c an-empty-cell">No slow-moving items</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Turnover by Product</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="r">Value</th>
                            <th class="r">Avg Cost</th>
                            <th class="r">Turnover</th>
                            <th class="r">Days on Hand</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($turnover as $row)
                            <tr>
                                <td>{{ $row['product_name'] }} <span class="an-chip c-nt">{{ $row['sku'] }}</span></td>
                                <td class="r">@money($row['total_value'] ?? 0)</td>
                                <td class="r">{{ format_money($row['avg_cost'] ?? 0, null, 4) }}</td>
                                <td class="r">{{ ($row['turnover'] ?? null) !== null ? format_number($row['turnover'], 1) : 'N/A' }}</td>
                                <td class="r">{{ ($row['days_on_hand'] ?? null) !== null ? format_number($row['days_on_hand'], 0) : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="c an-empty-cell">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>