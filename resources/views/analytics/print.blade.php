<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $pageLabel }} — CamelotBooks</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, 'Segoe UI', Roboto, Arial, sans-serif; margin: 0; padding: 32px; color: #111827; font-size: 12px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0B2A2D; padding-bottom: 14px; margin-bottom: 20px; }
        .brand { font-size: 18px; font-weight: 800; letter-spacing: -0.02em; color: #0B2A2D; }
        .brand small { display: block; font-size: 10px; font-weight: 500; letter-spacing: 0.06em; text-transform: uppercase; color: #5F7476; margin-top: 2px; }
        .doc { text-align: right; }
        .doc h1 { margin: 0; font-size: 16px; font-weight: 800; letter-spacing: -0.02em; color: #128F8E; }
        .doc p { margin: 4px 0 0; color: #5F7476; font-size: 11px; }
        .meta { display: flex; gap: 28px; margin-bottom: 22px; font-size: 11px; color: #5F7476; }
        .meta b { display: block; font-size: 12px; color: #111827; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 22px; }
        thead th { background: #F4F8F8; border-bottom: 2px solid #0B2A2D; text-align: left; padding: 8px 8px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #0B2A2D; }
        td { padding: 6px 8px; border-bottom: 1px solid #E2ECEC; }
        th.r, td.r { text-align: right; }
        th.c, td.c { text-align: center; }
        tfoot th { background: #F4F8F8; border-top: 2px solid #0B2A2D; padding: 8px; text-align: left; }
        .empty { text-align: center; color: #8AA5A7; padding: 20px !important; font-style: italic; }
        .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .kpi { border: 1px solid #E2ECEC; border-radius: 8px; padding: 10px 12px; }
        .kpi .l { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #5F7476; }
        .kpi .v { font-size: 14px; font-weight: 700; margin-top: 4px; color: #111827; }
        .kpi .v.up { color: #067647; }
        .kpi .v.dn { color: #B91C1C; }
        h2 { font-size: 13px; font-weight: 800; letter-spacing: -0.01em; color: #0B2A2D; margin: 26px 0 10px; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #E2ECEC; font-size: 10px; color: #8AA5A7; }
        .foot b { color: #5F7476; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body onload="window.print()">
    <div class="head">
        <div class="brand">{{ config('app.name', 'CamelotBooks') }}<small>{{ $cur['base'] ?? '' }} · Financial Analytics</small></div>
        <div class="doc">
            <h1>{{ $pageLabel }}</h1>
            <p>{{ $period['label'] }} · {{ $period['from'] }} to {{ $period['to'] }} · Printed {{ \Carbon\Carbon::now()->format('M j, Y H:i') }}</p>
        </div>
    </div>

    @php
        $page = $page ?? 'overview';
        $d = $data ?? [];
    @endphp

    @if ($page === 'overview')
        @php $k = $d['kpis'] ?? []; @endphp
        <div class="kpis">
            <div class="kpi"><div class="l">Total Revenue</div><div class="v">{{ isset($k['total_revenue']) ? format_money($k['total_revenue']['value']) : '—' }}</div></div>
            <div class="kpi"><div class="l">Total Expenses</div><div class="v">{{ isset($k['total_expenses']) ? format_money($k['total_expenses']['value']) : '—' }}</div></div>
            <div class="kpi"><div class="l">Net Income</div><div class="v up">{{ isset($k['net_income']) ? format_money($k['net_income']['value']) : '—' }}</div></div>
            <div class="kpi"><div class="l">Cash Balance</div><div class="v">{{ isset($k['cash_balance']) ? format_money($k['cash_balance']['value']) : '—' }}</div></div>
        </div>
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th><th class="r">% of Revenue</th></tr></thead>
            <tbody>
                @forelse (($d['breakdown'] ?? []) as $label => $row)
                    <tr><td>{{ is_array($row) ? ($row['label'] ?? $label) : $label }}</td>
                        <td class="r">@money(is_array($row) ? ($row['value'] ?? 0) : $row)</td>
                        <td class="r">{{ is_array($row) && isset($row['pct']) ? number_format($row['pct'], 1) . '%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No summary data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'financial-ratios')
        @php $k = $d['kpis'] ?? []; @endphp
        <table>
            <thead><tr><th>Ratio</th><th class="r">Value</th></tr></thead>
            <tbody>
                @forelse ($k as $label => $row)
                    <tr><td>{{ $row['label'] ?? $label }}</td>
                        <td class="r">{{ is_numeric($row['value'] ?? null) ? number_format((float) $row['value'], 2) : '—' }}</td></tr>
                @empty
                    <tr><td colspan="2" class="empty">No ratio data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'revenue-expense-trends')
        <table>
            <thead><tr><th>Period</th><th class="r">Revenue</th><th class="r">Expenses</th><th class="r">Net Income</th></tr></thead>
            <tbody>
                @forelse (($d['results'] ?? []) as $row)
                    <tr><td>{{ $row['period'] ?? '' }}</td>
                        <td class="r">@money($row['revenue'] ?? 0)</td>
                        <td class="r">@money($row['expense'] ?? 0)</td>
                        <td class="r">{{ (($row['net_income'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($row['net_income'] ?? 0)) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">No trend data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'sales')
        @php $m = $d['monthly_summary'] ?? []; @endphp
        <table>
            <thead><tr><th>Month</th><th class="r">Invoices</th><th class="r">Total</th></tr></thead>
            <tbody>
                @forelse ($m as $row)
                    <tr><td>{{ $row['month'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['count'] ?? 0), 0) }}</td>
                        <td class="r">@money($row['total'] ?? 0)</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'purchasing')
        @php $m = $d['monthly_summary'] ?? []; @endphp
        <table>
            <thead><tr><th>Month</th><th class="r">Bills</th><th class="r">Total</th></tr></thead>
            <tbody>
                @forelse ($m as $row)
                    <tr><td>{{ $row['month'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['count'] ?? 0), 0) }}</td>
                        <td class="r">@money($row['total'] ?? 0)</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No purchases in this period.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'inventory')
        @php $cv = $d['current_value'] ?? []; @endphp
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>Inventory Value ({{ $period['as_of'] }})</td><td class="r">@money($cv['total_value'] ?? 0)</td></tr>
                <tr><td>Units on Hand</td><td class="r">{{ number_format((float) ($cv['total_quantity'] ?? 0), 0) }}</td></tr>
                <tr><td>Product Count</td><td class="r">{{ number_format((float) ($cv['item_count'] ?? 0), 0) }}</td></tr>
                <tr><td>Stockout Events (period)</td><td class="r">{{ number_format((float) array_sum(array_column($d['stockouts'] ?? [], 'adjustment_count')), 0) }}</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'profitability')
        @php $is = $d['income_statement'] ?? []; @endphp
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>Total Income</td><td class="r">@money($is['total_income'] ?? 0)</td></tr>
                <tr><td>Total Expenses</td><td class="r">@money($is['total_expenses'] ?? 0)</td></tr>
                <tr><td>Net Income</td><td class="r">{{ (($is['net_income'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($is['net_income'] ?? 0)) }}</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'cash-flow-trend')
        @php
            $labels = $d['labels'] ?? [];
            $hc = (int) ($d['historical_count'] ?? 0);
        @endphp
        <table>
            <thead><tr><th>Month</th><th class="r">Net</th><th class="c">Type</th></tr></thead>
            <tbody>
                @forelse ($labels as $i => $label)
                    @php
                        $isProj = $i >= $hc;
                        $net = $isProj ? ($d['projection_net'][$i - $hc] ?? 0) : ($d['net'][$i] ?? 0);
                    @endphp
                    <tr><td>{{ $label }}</td>
                        <td class="r {{ $net >= 0 ? '' : '' }}">{{ (($net ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($net ?? 0)) }}</td>
                        <td class="c">{{ $isProj ? 'Projected' : 'Actual' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No cash-flow trend data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'expenses')
        @php $k = $d['kpis'] ?? []; @endphp
        <table>
            <thead><tr><th>Account</th><th class="r">Total</th></tr></thead>
            <tbody>
                @forelse (($d['top_accounts'] ?? []) as $row)
                    <tr><td>{{ $row['name'] ?? '' }}</td><td class="r">@money($row['total'] ?? 0)</td></tr>
                @empty
                    <tr><td colspan="2" class="empty">No expense data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'customers')
        <table>
            <thead><tr><th>#</th><th>Customer</th><th class="r">Orders</th><th class="r">Revenue</th></tr></thead>
            <tbody>
                @forelse (($d['top_customers'] ?? []) as $row)
                    <tr><td>{{ $row['rank'] ?? '-' }}</td>
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['orders'] ?? 0), 0) }}</td>
                        <td class="r">@money($row['revenue'] ?? 0)</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">No customer data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'working-capital')
        @php $k = $d['kpis'] ?? []; @endphp
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>Net Position</td><td class="r">@money($k['net_position']['value'] ?? 0)</td></tr>
                <tr><td>DSO</td><td class="r">{{ number_format((float) ($k['dso']['value'] ?? 0), 0) }} days</td></tr>
                <tr><td>DIO</td><td class="r">{{ number_format((float) ($k['dio']['value'] ?? 0), 0) }} days</td></tr>
                <tr><td>DPO</td><td class="r">{{ number_format((float) ($k['dpo']['value'] ?? 0), 0) }} days</td></tr>
                <tr><td>Cash Conversion Cycle</td><td class="r">{{ number_format((float) ($k['ccc']['value'] ?? 0), 0) }} days</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'budget-vs-actual')
        <table>
            <thead><tr><th>Account</th><th class="r">Budgeted</th><th class="r">Actual</th><th class="r">Variance</th></tr></thead>
            <tbody>
                @forelse (($d['lines'] ?? []) as $row)
                    <tr><td>{{ $row['account'] ?? '' }}</td>
                        <td class="r">@money($row['budgeted'] ?? 0)</td>
                        <td class="r">@money($row['actual'] ?? 0)</td>
                        <td class="r">{{ (($row['variance'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($row['variance'] ?? 0)) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">{{ ($d['has_budget'] ?? false) ? 'No budget lines.' : 'No budget has been set for this period.' }}</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'tax')
        @php $k = $d['kpis'] ?? []; @endphp
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>VAT Payable</td><td class="r">@money($k['vat_payable']['value'] ?? 0)</td></tr>
                <tr><td>VAT Receivable</td><td class="r">@money($k['vat_receivable']['value'] ?? 0)</td></tr>
                <tr><td>Net Due</td><td class="r">{{ (($k['net_due']['value'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($k['net_due']['value'] ?? 0)) }}</td></tr>
                <tr><td>Output VAT (YTD)</td><td class="r">@money($k['output_ytd']['value'] ?? 0)</td></tr>
                <tr><td>Input VAT (YTD)</td><td class="r">@money($k['input_ytd']['value'] ?? 0)</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'forecasts')
        @php
            $labels = $d['labels'] ?? [];
            $actual = $d['actual'] ?? [];
            $forecast = $d['forecast'] ?? [];
        @endphp
        <table>
            <thead><tr><th>Week</th><th class="r">Net Cash Flow</th><th class="c">Type</th></tr></thead>
            <tbody>
                @forelse ($labels as $i => $label)
                    @php
                        $val = ($actual[$i] ?? null) !== null ? $actual[$i] : ($forecast[$i] ?? null);
                        $isProj = ($actual[$i] ?? null) === null;
                    @endphp
                    <tr><td>{{ $label }}</td>
                        <td class="r">{{ ($val ?? 0) >= 0 ? '' : '-' }}{{ format_money(abs($val ?? 0)) }}</td>
                        <td class="c">{{ $isProj ? 'Projected' : 'Actual' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No cash-flow forecast data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'branches')
        <table>
            <thead><tr><th>Branch</th><th class="r">Revenue</th><th class="r">Profit</th><th class="r">Margin</th></tr></thead>
            <tbody>
                @forelse (($d['rows'] ?? []) as $row)
                    <tr><td>{{ $row['name'] ?? '' }}</td>
                        <td class="r">@money($row['revenue'] ?? 0)</td>
                        <td class="r">{{ (($row['profit'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($row['profit'] ?? 0)) }}</td>
                        <td class="r">{{ ($row['margin'] ?? null) !== null ? number_format((float) $row['margin'], 1) . '%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">No branch data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @else
        <p class="empty">No printable content.</p>
    @endif

    <div class="foot">
        <b>{{ config('app.name', 'CamelotBooks') }}</b> · Generated <b>{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</b> · Period <b>{{ $period['label'] }} ({{ $period['from'] }} – {{ $period['to'] }})</b> · Amounts in <b>{{ $cur['base'] ?? '' }}</b>
    </div>
</body>
</html>