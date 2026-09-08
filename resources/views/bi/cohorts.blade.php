<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'cohorts';
            $title = 'Cohort Retention';
            $subtitle = 'Monthly percentage of customers retained 0-5 months after first purchase, plus churn within the trailing look-back window.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $grid = $data['grid'] ?? [];
            $churn = $data['churn'] ?? [];
            $maxCell = 100;
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Cohorts</div><div class="v">{{ count($grid) }}</div></div>
            <div class="an-kpi"><div class="l">Total Customers</div><div class="v">{{ number_format((float) ($data['total_customers'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Churned</div><div class="v">{{ number_format((float) ($data['churned_customers'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Churn Rate</div><div class="v">{{ format_number($data['churn_rate_pct'] ?? 0, 1) }}%</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Retention by Cohort</h2>
                    <p class="an-card-s">% of customers from each first-purchase month who transacted again in months 0-5. Retention window: {{ $data['retention_window_days'] ?? 90 }} days.</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Cohort</th>
                            <th class="r">Customers</th>
                            @for ($m = 0; $m <= 5; $m++)
                                <th class="c">M{{ $m }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($grid as $row)
                            <tr>
                                <td>{{ $row['cohort'] }}</td>
                                <td class="r">{{ number_format((float) ($row['customers'] ?? 0), 0) }}</td>
                                @for ($m = 0; $m <= 5; $m++)
                                    @php $pct = (float) ($row["m{$m}"] ?? 0); @endphp
                                    <td class="c {{ $pct >= 80 ? 'up' : ($pct >= 50 ? '' : 'nt') }}">
                                        {{ format_number($pct, 1) }}%
                                        <div class="an-mbar"><span class="an-mbar-f {{ $pct >= 80 ? 'c' : ($pct >= 50 ? 'm' : 'w') }}" style="width: {{ max(2, ($pct / $maxCell) * 100) }}%"></span></div>
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr><td colspan="8" class="c an-empty-cell">No cohort data in the selected window.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Churn Detail</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Cohort</th>
                            <th class="r">Customers</th>
                            <th class="r">Churned</th>
                            <th class="r">Churn %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($churn as $ym => $row)
                            @php $pct = (float) ($row['customers'] ?? 0) > 0 ? ((float) ($row['churned'] ?? 0) / (float) ($row['customers'] ?? 0)) * 100 : 0; @endphp
                            <tr>
                                <td>{{ $ym }}</td>
                                <td class="r">{{ number_format((float) ($row['customers'] ?? 0), 0) }}</td>
                                <td class="r dn">{{ number_format((float) ($row['churned'] ?? 0), 0) }}</td>
                                <td class="r">{{ format_number($pct, 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="c an-empty-cell">No churn data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <p class="an-note">Churn = customers whose last invoice fell before the look-back cutoff. Colour: green = 80%+ retained, amber = 50-80%, slate = under 50%.</p>
    </div>
</x-app-layout>