<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'workcap';
            $title = 'Working Capital';
            $subtitle = 'Liquidity, receivables, inventory, payables and the cash conversion cycle as at ' . ($data['as_of'] ?? $period['as_of']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $ccc = $data['cash_conversion_cycle'] ?? null;
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Cash</div><div class="v">@money($data['cash'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Receivables</div><div class="v">@money($data['receivables'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Inventory</div><div class="v">@money($data['inventory'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Payables</div><div class="v dn">@money($data['payables'] ?? 0)</div></div>
        </div>
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Working Capital</div><div class="v {{ ($data['working_capital'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['working_capital'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Current Ratio</div><div class="v">{{ $data['current_ratio'] !== null ? format_number($data['current_ratio'], 2) : '—' }}</div></div>
            <div class="an-kpi"><div class="l">Cash Conversion Cycle (d)</div><div class="v {{ $ccc !== null && $ccc <= 0 ? 'up' : 'nt' }}">{{ $ccc !== null ? format_number($ccc, 1) : '—' }}</div></div>
            <div class="an-kpi"><div class="l">DSO / DIO / DPO</div><div class="v" style="font-size:0.95rem;">{{ format_number($data['dso'] ?? 0, 0) }} / {{ format_number($data['dio'] ?? 0, 0) }} / {{ format_number($data['dpo'] ?? 0, 0) }}</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Cash & Bank Accounts</h2>
                    <p class="an-card-s">Accounts that make up the cash position</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead><tr><th>Account</th><th>Code</th><th class="c">Type</th><th class="r">Balance</th></tr></thead>
                    <tbody>
                        @forelse (($data['cash_accounts'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['name'] ?? '' }}</td>
                                <td>{{ $row['code'] ?? '' }}</td>
                                <td class="c"><span class="an-chip {{ ($row['bank'] ?? false) ? 'c-ct' : 'c-nt' }}">{{ ($row['bank'] ?? false) ? 'Bank' : 'Cash' }}</span></td>
                                <td class="r">@money($row['balance'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="c an-empty-cell">No cash or bank accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Receivables Aging</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead><tr><th>Bucket</th><th class="r">Amount</th></tr></thead>
                        <tbody>
                            @foreach (['current' => 'Current', 'days_1_30' => '1-30 days', 'days_31_60' => '31-60 days', 'days_61_90' => '61-90 days', 'days_90_plus' => '90+ days'] as $key => $label)
                                <tr><td>{{ $label }}</td><td class="r">@money(($data['ar_aging'][$key] ?? 0))</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th>Total</th><th class="r">@money(($data['ar_aging']['total'] ?? 0))</th></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="an-card">
                <h2 class="an-card-t">Payables Aging</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead><tr><th>Bucket</th><th class="r">Amount</th></tr></thead>
                        <tbody>
                            @foreach (['current' => 'Current', 'days_1_30' => '1-30 days', 'days_31_60' => '31-60 days', 'days_61_90' => '61-90 days', 'days_90_plus' => '90+ days'] as $key => $label)
                                <tr><td>{{ $label }}</td><td class="r">@money(($data['ap_aging'][$key] ?? 0))</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th>Total</th><th class="r">@money(($data['ap_aging']['total'] ?? 0))</th></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <p class="an-note">Days are calculated against trailing revenue and cost of goods. A negative cash conversion cycle means the business is paid before it must pay suppliers.</p>
    </div>
</x-app-layout>