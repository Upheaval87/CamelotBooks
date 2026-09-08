<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'supplier';
            $title = 'Supplier Scorecard';
            $subtitle = 'Spend, on-time delivery, quality and open commitments per supplier for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $rows = $data['rows'] ?? [];
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Suppliers</div><div class="v">{{ count($rows) }}</div></div>
            <div class="an-kpi"><div class="l">Total Spend</div><div class="v">@money($data['total_spend'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Open on Account</div><div class="v">@money($data['total_open'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">On-Time (Overall)</div><div class="v">{{ isset($data['overall_on_time_pct']) ? format_number($data['overall_on_time_pct'], 1) . '%' : '—' }}</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Supplier Performance</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th class="r">Spend</th>
                            <th class="r">Bills</th>
                            <th class="r">GRNs</th>
                            <th class="r">On-Time</th>
                            <th class="r">Quality</th>
                            <th class="r">Price Drift</th>
                            <th class="r">Open</th>
                            <th class="r">Open POs</th>
                            <th class="r">Terms</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['vendor_name'] ?? '' }} <span class="nt">({{ $row['currency'] ?? '' }})</span></td>
                                <td class="r">@money($row['spend'] ?? 0)</td>
                                <td class="r">{{ (int) ($row['bill_count'] ?? 0) }}</td>
                                <td class="r">{{ (int) ($row['grn_count'] ?? 0) }}</td>
                                <td class="r {{ isset($row['on_time_pct']) && $row['on_time_pct'] >= 90 ? 'up' : 'dn' }}">{{ $row['on_time_pct'] !== null ? format_number($row['on_time_pct'], 1) . '%' : '—' }}</td>
                                <td class="r {{ ($row['quality_pct'] ?? 100) >= 90 ? 'up' : 'dn' }}">{{ format_number($row['quality_pct'] ?? 100, 1) }}%</td>
                                <td class="r {{ ($row['price_drift_pct'] ?? 0) < 0 ? '' : 'dn' }}">{{ format_number($row['price_drift_pct'] ?? 0, 1) }}%</td>
                                <td class="r">@money($row['open_amount'] ?? 0)</td>
                                <td class="r">{{ (int) ($row['open_pos'] ?? 0) }}</td>
                                <td class="r">{{ $row['payment_terms_days'] ?? '—' }}d</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="c an-empty-cell">No supplier data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="an-note">On-time = GRNs delivered within their promise date. Quality = share of spend not later credited. An on-time score below 90% is highlighted in red.</p>
    </div>
</x-app-layout>