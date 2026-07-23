<div class="header">
    <div class="company">{{ $company->name }}</div>
    <div class="report-title">Cash Flow Statement</div>
    <div class="period">{{ $dateFrom }} to {{ $dateTo }}</div>
</div>

<table>
    <thead>
        <tr><th>Description</th><th class="text-right">Amount</th></tr>
    </thead>
    <tbody>
        <tr class="section-header"><td colspan="2">Operating Activities</td></tr>
        <tr>
            <td class="indent">Net Income</td>
            <td class="text-right {{ $net_income < 0 ? 'negative' : '' }}">{{ number_format($net_income, 2) }}</td>
        </tr>
        @foreach($non_cash_expenses['items'] as $item)
            <tr>
                <td class="indent">Add: {{ $item['account']->name }}</td>
                <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
        @endforeach
        @foreach($sections['operating'] as $item)
            <tr>
                <td class="indent">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                <td class="text-right {{ $item['cash_effect'] < 0 ? 'negative' : '' }}">{{ number_format($item['cash_effect'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Net Cash from Operating</td>
            <td class="text-right {{ $operating_total < 0 ? 'negative' : '' }}">{{ number_format($operating_total, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="section-header"><td colspan="2">Investing Activities</td></tr>
        @foreach($sections['investing'] as $item)
            <tr>
                <td class="indent">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                <td class="text-right {{ $item['cash_effect'] < 0 ? 'negative' : '' }}">{{ number_format($item['cash_effect'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Net Cash from Investing</td>
            <td class="text-right {{ $investing_total < 0 ? 'negative' : '' }}">{{ number_format($investing_total, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="section-header"><td colspan="2">Financing Activities</td></tr>
        @foreach($sections['financing'] as $item)
            <tr>
                <td class="indent">{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</td>
                <td class="text-right {{ $item['cash_effect'] < 0 ? 'negative' : '' }}">{{ number_format($item['cash_effect'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Net Cash from Financing</td>
            <td class="text-right {{ $financing_total < 0 ? 'negative' : '' }}">{{ number_format($financing_total, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="row-grand">
            <td class="text-right">Net Change in Cash</td>
            <td class="text-right {{ $net_change < 0 ? 'negative' : '' }}">{{ number_format($net_change, 2) }}</td>
        </tr>
        <tr>
            <td class="indent">Beginning Cash Balance</td>
            <td class="text-right">{{ number_format($beginning_cash, 2) }}</td>
        </tr>
        <tr>
            <td class="indent">Ending Cash Balance</td>
            <td class="text-right">{{ number_format($ending_cash, 2) }}</td>
        </tr>
    </tbody>
</table>
