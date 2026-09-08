<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'clv';
            $title = 'Customer Lifetime Value';
            $subtitle = 'Order behaviour, revenue and estimated lifetime value per customer, segmented across the whole customer base.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters', ['showPeriod' => false])

        @php
            $rows = $data['customers'] ?? [];
            $segCounts = ['high' => 0, 'medium' => 0, 'low' => 0];
            foreach ($rows as $row) {
                $s = ($row['segment'] ?? 'low');
                if (isset($segCounts[$s])) {
                    $segCounts[$s]++;
                }
            }
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Customers</div><div class="v">{{ number_format((float) ($data['total_customers'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Net Revenue</div><div class="v">@money($data['total_revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Avg LTV</div><div class="v">@money($data['avg_ltv'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Active / Lapsed</div><div class="v">@money($data['active_customers'] ?? 0)<span style="font-size:0.75rem;color:var(--muted,#52696B);"> / {{ number_format((float) ($data['lapsed_customers'] ?? 0), 0) }}</span></div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Customer Segments</h2>
                    <p class="an-card-s">High = 10,000+ revenue · Medium = 1,000+ · Low = below 1,000</p>
                </div>
                <div class="an-chips">
                    <span class="an-chip c-ct">High: {{ $segCounts['high'] }}</span>
                    <span class="an-chip c-ok">Medium: {{ $segCounts['medium'] }}</span>
                    <span class="an-chip c-nt">Low: {{ $segCounts['low'] }}</span>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th class="r">Orders</th>
                            <th class="r">Total Revenue</th>
                            <th class="r">Avg Order</th>
                            <th class="r">Months Active</th>
                            <th class="r">Est. LTV</th>
                            <th class="c">Segment</th>
                            <th class="c">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $seg = $row['segment'] ?? 'low'; @endphp
                            <tr>
                                <td>{{ $row['customer_name'] ?? '' }}</td>
                                <td class="nt">{{ $row['email'] ?? '—' }}</td>
                                <td class="r">{{ number_format((float) ($row['order_count'] ?? 0), 0) }}</td>
                                <td class="r">@money($row['total_revenue'] ?? 0)</td>
                                <td class="r">@money($row['avg_order_value'] ?? 0)</td>
                                <td class="r">{{ number_format((float) ($row['months_active'] ?? 0), 0) }}</td>
                                <td class="r">@money($row['est_ltv'] ?? 0)</td>
                                <td class="c">
                                    <span class="an-chip {{ $seg === 'high' ? 'c-ct' : ($seg === 'medium' ? 'c-ok' : 'c-nt') }}">{{ ucfirst($seg) }}</span>
                                </td>
                                <td class="c">
                                    <span class="an-chip {{ ($row['lapsed'] ?? false) ? 'c-ov' : 'c-ok' }}">{{ ($row['lapsed'] ?? false) ? 'Lapsed' : 'Active' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="c an-empty-cell">No customer data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="an-note">Estimated lifetime value uses historical purchase frequency and average order value; it is an indicator, not a cash forecast.</p>
    </div>
</x-app-layout>