<div class="header">
    <div class="company">{{ $company->name }}</div>
    <div class="report-title">Income Statement</div>
    <div class="period">{{ $dateFrom }} to {{ $dateTo }}</div>
</div>

<table>
    <thead>
        <tr><th>Description</th><th class="text-right">Amount</th></tr>
    </thead>
    <tbody>
        <tr class="section-header"><td colspan="2">Income</td></tr>
        @foreach($groups['income'] as $subType => $items)
            @foreach($items as $item)
                <tr>
                    <td class="indent">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                    <td class="text-right">{{ format_money(max(0, $item['net'])) }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Total Income</td>
            <td class="text-right">{{ format_money($total_income) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="section-header"><td colspan="2">Expenses</td></tr>
        @foreach($groups['expense'] as $subType => $items)
            @foreach($items as $item)
                <tr>
                    <td class="indent">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                    <td class="text-right">{{ format_money(max(0, $item['net'])) }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Total Expenses</td>
            <td class="text-right">{{ format_money($total_expenses) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="row-grand">
            <td class="text-right">{{ $net_income >= 0 ? 'Net Income' : 'Net Loss' }}</td>
            <td class="text-right {{ $net_income < 0 ? 'negative' : '' }}">{{ format_money(abs($net_income)) }}</td>
        </tr>
    </tbody>
</table>
