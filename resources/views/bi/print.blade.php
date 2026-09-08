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
        <div class="brand">{{ config('app.name', 'CamelotBooks') }}<small>{{ $cur['base'] ?? '' }} · Business Intelligence</small></div>
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
            <div class="kpi"><div class="l">Revenue</div><div class="v">{{ isset($k['revenue']) ? format_money($k['revenue']['value'] ?? 0) : '—' }}</div></div>
            <div class="kpi"><div class="l">Gross Profit</div><div class="v">{{ isset($k['gross_profit']) ? format_money($k['gross_profit']['value'] ?? 0) : '—' }}</div></div>
            <div class="kpi"><div class="l">Net Income</div><div class="v up">{{ isset($k['net_income']) ? format_money($k['net_income']['value'] ?? 0) : '—' }}</div></div>
            <div class="kpi"><div class="l">Cash Balance</div><div class="v">{{ isset($k['cash_balance']) ? format_money($k['cash_balance']['value'] ?? 0) : '—' }}</div></div>
        </div>
        @php $st = $d['statement'] ?? []; @endphp
        <h2>Income Statement</h2>
        <table>
            <thead><tr><th>Metric</th><th class="r">Amount</th></tr></thead>
            <tbody>
                @foreach ([
                    'Revenue' => $st['revenue'] ?? 0,
                    'Cost of Goods Sold' => $st['cogs'] ?? 0,
                    'Gross Profit' => $st['gross_profit'] ?? 0,
                    'Operating Expenses' => $st['opex'] ?? 0,
                    'Total Expenses' => $st['total_expenses'] ?? 0,
                    'Net Income' => $st['net_income'] ?? 0,
                ] as $label => $val)
                    <tr><td>{{ $label }}</td><td class="r">{{ format_money((float) $val) }}</td></tr>
                @endforeach
            </tbody>
        </table>

    @elseif ($page === 'branch')
        <table>
            <thead><tr><th>Branch</th><th class="r">Revenue</th><th class="r">Gross Profit</th><th class="r">Expenses</th><th class="r">Net Income</th><th class="r">Margin</th></tr></thead>
            <tbody>
                @forelse (($d['branches'] ?? []) as $row)
                    <tr><td>{{ $row['branch_name'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['revenue'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['gross_profit'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['total_expenses'] ?? 0) }}</td>
                        <td class="r">{{ (($row['net_income'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($row['net_income'] ?? 0)) }}</td>
                        <td class="r">{{ isset($row['net_margin']) && $row['net_margin'] !== null ? number_format((float) $row['net_margin'], 1) . '%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No branch data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'clv')
        <table>
            <thead><tr><th>Customer</th><th class="r">Orders</th><th class="r">Revenue</th><th class="r">Avg Order</th><th class="r">Est. LTV</th><th>Segment</th></tr></thead>
            <tbody>
                @forelse (($d['customers'] ?? []) as $row)
                    <tr><td>{{ $row['customer_name'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['order_count'] ?? 0), 0) }}</td>
                        <td class="r">{{ format_money($row['total_revenue'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['avg_order_value'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['est_ltv'] ?? 0) }}</td>
                        <td>{{ $row['segment'] ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No customer data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'employee')
        <table>
            <thead><tr><th>Branch</th><th class="r">Headcount</th><th class="r">Payroll</th><th class="r">Revenue</th><th class="r">Cost / Employee</th><th class="r">Revenue / Employee</th></tr></thead>
            <tbody>
                @forelse (($d['branches'] ?? []) as $row)
                    <tr><td>{{ $row['branch_name'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['headcount'] ?? 0), 0) }}</td>
                        <td class="r">{{ format_money($row['total_payroll'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['revenue'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['cost_per_employee'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['revenue_per_employee'] ?? 0) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No employee productivity data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'truecost')
        <table>
            <thead><tr><th>Branch</th><th class="r">OPEX</th><th class="r">Payroll</th><th class="r">Depreciation</th><th class="r">GL Total</th><th class="r">True Total Cost</th></tr></thead>
            <tbody>
                @forelse (($d['branches'] ?? []) as $row)
                    <tr><td>{{ $row['branch_name'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['opex'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['payroll'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['depreciation'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['total_gl'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['total'] ?? 0) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No true total cost data.</td></tr>
                @endforelse
            </tbody>
            @if (($d['branches'] ?? []) && isset($d['grand_total']))
                <tfoot><tr><th>Grand Total</th><th class="r" colspan="4"></th><th class="r">{{ format_money($d['grand_total']) }}</th></tr></tfoot>
            @endif
        </table>

    @elseif ($page === 'product')
        <table>
            <thead><tr><th>Product</th><th>SKU</th><th class="r">Revenue</th><th class="r">Contribution</th><th class="r">Margin</th><th class="r">% of Revenue</th><th class="c">Class</th></tr></thead>
            <tbody>
                @forelse (($d['rows'] ?? []) as $row)
                    <tr><td>{{ $row['product_name'] ?? '' }}</td>
                        <td>{{ $row['sku'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['revenue'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['contribution'] ?? 0) }}</td>
                        <td class="r">{{ isset($row['margin_pct']) ? number_format((float) $row['margin_pct'], 1) . '%' : '—' }}</td>
                        <td class="r">{{ isset($row['pct_of_revenue']) ? number_format((float) $row['pct_of_revenue'], 1) . '%' : '—' }}</td>
                        <td class="c">{{ $row['class'] ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="7" class="empty">No product data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'supplier')
        <table>
            <thead><tr><th>Supplier</th><th class="r">Spend</th><th class="r">Bills</th><th class="r">On-time %</th><th class="r">Open</th></tr></thead>
            <tbody>
                @forelse (($d['rows'] ?? []) as $row)
                    <tr><td>{{ $row['vendor_name'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['spend'] ?? 0) }}</td>
                        <td class="r">{{ number_format((float) ($row['bill_count'] ?? 0), 0) }}</td>
                        <td class="r">{{ isset($row['on_time_pct']) && $row['on_time_pct'] !== null ? number_format((float) $row['on_time_pct'], 1) . '%' : '—' }}</td>
                        <td class="r">{{ format_money($row['open_amount'] ?? 0) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty">No supplier data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'workcap')
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>Cash</td><td class="r">{{ format_money($d['cash'] ?? 0) }}</td></tr>
                <tr><td>Receivables</td><td class="r">{{ format_money($d['receivables'] ?? 0) }}</td></tr>
                <tr><td>Inventory</td><td class="r">{{ format_money($d['inventory'] ?? 0) }}</td></tr>
                <tr><td>Payables</td><td class="r">{{ format_money($d['payables'] ?? 0) }}</td></tr>
                <tr><td>Working Capital</td><td class="r">{{ format_money($d['working_capital'] ?? 0) }}</td></tr>
                <tr><td>Current Ratio</td><td class="r">{{ $d['current_ratio'] !== null ? number_format((float) $d['current_ratio'], 2) : '—' }}</td></tr>
                <tr><td>DSO</td><td class="r">{{ number_format((float) ($d['dso'] ?? 0), 1) }} days</td></tr>
                <tr><td>DIO</td><td class="r">{{ number_format((float) ($d['dio'] ?? 0), 1) }} days</td></tr>
                <tr><td>DPO</td><td class="r">{{ number_format((float) ($d['dpo'] ?? 0), 1) }} days</td></tr>
                <tr><td>Cash Conversion Cycle</td><td class="r">{{ number_format((float) ($d['cash_conversion_cycle'] ?? 0), 1) }} days</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'scenarios')
        @foreach (($d['scenarios'] ?? []) as $scenario)
            <h2>{{ $scenario['label'] }} — final balance {{ format_money($scenario['final_balance'] ?? 0) }}</h2>
            <table>
                <thead><tr><th>Week</th><th class="r">Opening</th><th class="r">Inflow</th><th class="r">Outflow</th><th class="r">Net</th><th class="r">Closing</th></tr></thead>
                <tbody>
                    @forelse (($scenario['weeks'] ?? []) as $wk)
                        <tr><td>{{ $wk['week'] }} · {{ $wk['label'] }}</td>
                            <td class="r">{{ format_money($wk['opening']) }}</td>
                            <td class="r">{{ format_money($wk['inflow']) }}</td>
                            <td class="r">{{ format_money($wk['outflow']) }}</td>
                            <td class="r">{{ (($wk['net_cash_flow'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($wk['net_cash_flow'] ?? 0)) }}</td>
                            <td class="r">{{ format_money($wk['closing']) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="empty">No scenario data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach

    @elseif ($page === 'variance')
        <table>
            <thead><tr><th>Account</th><th class="r">Budgeted</th><th class="r">Actual</th><th class="r">Variance</th><th class="r">Var %</th></tr></thead>
            <tbody>
                @forelse (($d['rows'] ?? []) as $row)
                    <tr><td>{{ $row['account_name'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['budgeted'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['actual'] ?? 0) }}</td>
                        <td class="r">{{ (($row['variance'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs($row['variance'] ?? 0)) }}</td>
                        <td class="r">{{ isset($row['variance_pct']) && $row['variance_pct'] !== null ? number_format((float) $row['variance_pct'], 1) . '%' : '—' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty">No budget has been set for this period.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'pvm')
        <table>
            <thead><tr><th>Product</th><th class="r">Prev Revenue</th><th class="r">Cur Revenue</th><th class="r">Change</th><th class="r">Price</th><th class="r">Prev Price</th></tr></thead>
            <tbody>
                @forelse (($d['rows'] ?? []) as $row)
                    <tr><td>{{ $row['product_name'] ?? '' }}</td>
                        <td class="r">{{ format_money($row['prev_revenue'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['cur_revenue'] ?? 0) }}</td>
                        <td class="r">{{ (($row['cur_revenue'] ?? 0) - ($row['prev_revenue'] ?? 0) >= 0 ? '' : '-') }}{{ format_money(abs(($row['cur_revenue'] ?? 0) - ($row['prev_revenue'] ?? 0))) }}</td>
                        <td class="r">{{ format_money($row['price'] ?? 0) }}</td>
                        <td class="r">{{ format_money($row['prev_price'] ?? 0) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="empty">No price-volume-mix data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'breakeven')
        <table>
            <thead><tr><th>Metric</th><th class="r">Value</th></tr></thead>
            <tbody>
                <tr><td>Revenue</td><td class="r">{{ format_money($d['revenue'] ?? 0) }}</td></tr>
                <tr><td>Contribution</td><td class="r">{{ format_money($d['contribution'] ?? 0) }}</td></tr>
                <tr><td>Fixed Costs</td><td class="r">{{ format_money($d['fixed_cost'] ?? 0) }}</td></tr>
                <tr><td>Break-even Revenue</td><td class="r">{{ format_money($d['break_even_revenue'] ?? 0) }}</td></tr>
                <tr><td>Break-even Units</td><td class="r">{{ number_format((float) ($d['break_even_units'] ?? 0), 1) }}</td></tr>
            </tbody>
        </table>

    @elseif ($page === 'cohorts')
        <table>
            <thead><tr><th>Cohort</th><th class="r">Customers</th><th class="r">M0</th><th class="r">M1</th><th class="r">M2</th><th class="r">M3</th><th class="r">M4</th><th class="r">M5</th></tr></thead>
            <tbody>
                @forelse (($d['grid'] ?? []) as $row)
                    <tr><td>{{ $row['cohort'] ?? '' }}</td>
                        <td class="r">{{ number_format((float) ($row['customers'] ?? 0), 0) }}</td>
                        @for ($i = 0; $i <= 5; $i++)
                            <td class="r">{{ isset($row["m{$i}"]) ? number_format((float) $row["m{$i}"], 1) . '%' : '—' }}</td>
                        @endfor
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">No cohort data.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($page === 'board')
        @php $k = $d['kpis'] ?? []; @endphp
        <div class="kpis">
            <div class="kpi"><div class="l">Revenue</div><div class="v">{{ isset($k['revenue']) ? format_money($k['revenue']['value'] ?? 0) : '—' }}</div></div>
            <div class="kpi"><div class="l">Net Income</div><div class="v up">{{ isset($k['net_income']) ? format_money($k['net_income']['value'] ?? 0) : '—' }}</div></div>
            <div class="kpi"><div class="l">Working Capital</div><div class="v">{{ format_money($d['working_capital']['working_capital'] ?? 0) }}</div></div>
            <div class="kpi"><div class="l">Outstanding Actions</div><div class="v">{{ number_format((float) ($d['outstanding_actions'] ?? 0), 0) }}</div></div>
        </div>
        <h2>Tracked Actions</h2>
        <table>
            <thead><tr><th>Description</th><th>Owner</th><th>Due</th><th class="c">Status</th></tr></thead>
            <tbody>
                @forelse (($d['actions'] ?? []) as $row)
                    <tr><td>{{ $row['description'] ?? '' }}</td>
                        <td>{{ $row['owner'] ?? '—' }}</td>
                        <td>{{ $row['due_date'] ?? '—' }}</td>
                        <td class="c">{{ $row['status'] ?? 'open' }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty">No actions tracked.</td></tr>
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