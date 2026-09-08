<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'purchasing';
            $title = 'Purchasing';
            $subtitle = 'Bill spend, vendor concentration and price variance for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.purchasing') }}" class="an-filters">
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
            <a href="{{ route('analytics.purchasing') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $total = (float) array_sum(array_column($data['monthly_summary'] ?? [], 'total'));
            $billCount = (int) array_sum(array_column($data['monthly_summary'] ?? [], 'count'));
            $ppv = (float) ($data['ppv_total'] ?? 0);
            $avgLead = $data['lead_times']['avg_days'] ?? null;
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Purchases</div><div class="v">@money($total)</div></div>
            <div class="an-kpi"><div class="l">Bills</div><div class="v">{{ number_format($billCount, 0) }}</div></div>
            <div class="an-kpi"><div class="l">Purchase Price Variance</div><div class="v {{ $ppv >= 0 ? 'dn' : 'up' }}">@money(abs($ppv))</div></div>
            <div class="an-kpi"><div class="l">Avg Lead Time</div><div class="v">{{ $avgLead !== null ? number_format($avgLead, 0) . ' days' : 'N/A' }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Spend Trend</h2>
                    <p class="an-card-s">Monthly bill value and count.</p>
                </div>
            </div>
            @php
                $labels = $data['labels'] ?? [];
                $spend = $data['spend_data'] ?? [];
                $countData = $data['bill_count_data'] ?? [];
                $maxV = max(array_merge([1], array_map('abs', $spend)));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($spend[$i] ?? 0)">{{ format_number($spend[$i] ?? 0, 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b exp" style="height:{{ ((abs($spend[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}<i class="an-bar-sub">{{ $countData[$i] ?? 0 }}</i></span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No purchases in this period.</div>
            @endif
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Top Vendors</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th class="r">Bills</th>
                                <th class="r">Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['top_vendors'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['vendor_name'] }}</td>
                                    <td class="r">{{ number_format($row['bill_count'] ?? 0, 0) }}</td>
                                    <td class="r">@money($row['total_spend'] ?? 0)</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="c an-empty-cell">No vendors</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Price Variance Trend</h2>
                @php
                    $pv = $data['ppv_trend'] ?? [];
                    $pvMax = max(array_merge([1], array_map(fn ($r) => abs((float) ($r['net_amount'] ?? 0)), $pv)));
                @endphp
                @if ($pv)
                    <div class="an-bars">
                        @foreach ($pv as $row)
                            @php $amt = (float) ($row['net_amount'] ?? 0); @endphp
                            <div class="an-bar">
                                <span class="an-bar-v" title="{{ $row['month'] }}: @money($amt)">{{ format_number($amt, 0) }}</span>
                                <div class="an-bar-cols">
                                    <i class="an-bar-b {{ $amt >= 0 ? 'exp' : 'net' }}" style="height:{{ (abs($amt) / $pvMax) * 100 }}%"></i>
                                </div>
                                <span class="an-bar-l">{{ $row['month'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="an-empty">No PPV activity (is a purchase-price-variance account mapped?).</div>
                @endif
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Lead Times</h2>
            <div class="an-grid an-grid--3">
                <div class="an-stat"><div class="l">Average</div><div class="v">{{ ($data['lead_times']['avg_days'] ?? null) !== null ? number_format($data['lead_times']['avg_days'], 0) . ' days' : 'N/A' }}</div></div>
                <div class="an-stat"><div class="l">Minimum</div><div class="v">{{ ($data['lead_times']['min_days'] ?? null) !== null ? number_format($data['lead_times']['min_days'], 0) . ' days' : 'N/A' }}</div></div>
                <div class="an-stat"><div class="l">Maximum</div><div class="v">{{ ($data['lead_times']['max_days'] ?? null) !== null ? number_format($data['lead_times']['max_days'], 0) . ' days' : 'N/A' }}</div></div>
            </div>
        </div>
    </div>
</x-app-layout>