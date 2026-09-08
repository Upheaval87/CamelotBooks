<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'sales';
            $title = 'Sales';
            $subtitle = 'Invoiced, POS and receipt value for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.sales') }}" class="an-filters">
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
            <a href="{{ route('analytics.sales') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $rev = $data['revenue'] ?? [];
            $invCount = (int) ($data['invoice_count'] ?? 0);
            $monthCount = (int) array_sum(array_column($data['monthly_summary'] ?? [], 'count'));
            $monthTotal = (float) array_sum(array_column($data['monthly_summary'] ?? [], 'total'));
            $avgVal = $monthCount > 0 ? $monthTotal / $monthCount : 0;
            $convRate = $data['conversion']['rate'] ?? null;
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($rev['total_income'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Sales Count</div><div class="v">{{ number_format($invCount, 0) }}</div></div>
            <div class="an-kpi"><div class="l">Avg Invoice Value</div><div class="v">@money($avgVal)</div></div>
            <div class="an-kpi"><div class="l">Quotation Conversion</div><div class="v">{{ $convRate !== null ? number_format($convRate, 1) . '%' : 'N/A' }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Sales Trend</h2>
                    <p class="an-card-s">Monthly value and count for the period.</p>
                </div>
            </div>
            @php
                $labels = $data['labels'] ?? [];
                $valueData = $data['invoice_value_data'] ?? [];
                $countData = $data['invoice_count_data'] ?? [];
                $maxV = max(array_merge([1], array_map('abs', $valueData)));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($valueData[$i] ?? 0)">{{ format_number($valueData[$i] ?? 0, 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b rev" style="height:{{ ((abs($valueData[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}<i class="an-bar-sub">{{ $countData[$i] ?? 0 }}</i></span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No sales activity in this period.</div>
            @endif
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Conversion Funnel</h2>
                    <p class="an-card-s">Quotations created to invoices generated.</p>
                </div>
            </div>
            <div class="an-grid an-grid--3">
                <div class="an-stat">
                    <div class="l">Quotations</div>
                    <div class="v">{{ number_format($data['conversion']['quotations'] ?? 0, 0) }}</div>
                </div>
                <div class="an-stat">
                    <div class="l">Invoices</div>
                    <div class="v">{{ number_format($data['conversion']['invoices'] ?? 0, 0) }}</div>
                </div>
                <div class="an-stat">
                    <div class="l">Conversion Rate</div>
                    <div class="v">{{ $convRate !== null ? number_format($convRate, 1) . '%' : 'No quotations' }}</div>
                </div>
            </div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Top Customers</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th class="r">Sales</th>
                                <th class="r">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['top_customers'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['customer_name'] }}</td>
                                    <td class="r">{{ number_format($row['invoice_count'] ?? 0, 0) }}</td>
                                    <td class="r">@money($row['total_revenue'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="c an-empty-cell">No customers</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Top Products</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="r">Qty</th>
                                <th class="r">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['top_products'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['product_name'] }} <span class="an-chip c-nt">{{ $row['sku'] }}</span></td>
                                    <td class="r">{{ number_format($row['total_quantity'] ?? 0, 0) }}</td>
                                    <td class="r">@money($row['total_revenue'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="c an-empty-cell">No products</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>