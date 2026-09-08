<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'working-capital';
            $title = 'Working Capital';
            $subtitle = 'Cash-conversion cycle and current asset/liability coverage as of ' . $period['as_of'] . '.';
            $k = $data['kpis'] ?? [];
            $ar = $data['ar'] ?? [];
            $ap = $data['ap'] ?? [];
            $arBuckets = ['current', 'days_1_30', 'days_31_60', 'over_60'];
            $bucketLabels = ['current' => 'Current', 'days_1_30' => '1-30 days', 'days_31_60' => '31-60 days', 'over_60' => 'Over 60 days'];
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.working-capital') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                        <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.working-capital') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Net Position</div><div class="v {{ ($k['net_position']['value'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($k['net_position']['value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Days Sales Outstanding</div><div class="v">{{ $k['dso']['value'] ?? 0 }} days</div></div>
            <div class="an-kpi"><div class="l">Days Inventory Outstanding</div><div class="v">{{ $k['dio']['value'] ?? 0 }} days</div></div>
            <div class="an-kpi"><div class="l">Days Payable Outstanding</div><div class="v">{{ $k['dpo']['value'] ?? 0 }} days</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Cash Conversion Cycle</h2>
                    <p class="an-card-s">DSO + DIO − DPO — how long capital is tied up from purchase to collection.</p>
                </div>
                <div class="an-bigstat">{{ $k['ccc']['value'] ?? 0 }} <span>days</span></div>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Working Capital Position</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="r">Receivables (AR)</th>
                            <th class="r">Payables (AP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($arBuckets as $bk)
                            <tr>
                                <td>{{ $bucketLabels[$bk] }}</td>
                                <td class="r">@money($ar[$bk] ?? 0)</td>
                                <td class="r">@money($ap[$bk] ?? 0)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="an-tfoot-row">
                            <th>Total</th>
                            <th class="r">@money($ar['total'] ?? 0)</th>
                            <th class="r">@money($ap['total'] ?? 0)</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>