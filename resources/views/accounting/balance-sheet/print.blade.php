<div class="header">
    <div class="company">{{ $company->name }}</div>
    <div class="report-title">Balance Sheet</div>
    <div class="period">As of: {{ $asOfDate }}</div>
</div>

<table>
    <thead>
        <tr><th>Description</th><th class="text-right">Amount</th></tr>
    </thead>
    <tbody>
        <tr class="section-header"><td colspan="2">Assets</td></tr>
        @foreach($groups['asset'] as $subType => $items)
            @if(!empty($items))
                @foreach($items as $item)
                    <tr>
                        <td class="indent">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                        <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Total Assets</td>
            <td class="text-right">{{ number_format($total_assets, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="section-header"><td colspan="2">Liabilities</td></tr>
        @foreach($groups['liability'] as $subType => $items)
            @if(!empty($items))
                @foreach($items as $item)
                    <tr>
                        <td class="indent">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                        <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
        <tr class="row-subtotal">
            <td class="text-right">Total Liabilities</td>
            <td class="text-right">{{ number_format($total_liabilities, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="section-header"><td colspan="2">Equity</td></tr>
        @foreach($groups['equity'] as $subType => $items)
            @foreach($items as $item)
                <tr>
                    <td class="indent">{{ $item['account']->code }} - {{ $item['account']->name }}</td>
                    <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr>
            <td class="indent">Current Year Earnings</td>
            <td class="text-right">{{ number_format($current_year_earnings, 2) }}</td>
        </tr>
        <tr class="row-subtotal">
            <td class="text-right">Total Equity</td>
            <td class="text-right">{{ number_format($total_equity, 2) }}</td>
        </tr>
        <tr><td colspan="2"></td></tr>
        <tr class="row-grand">
            <td class="text-right">Total Liabilities &amp; Equity</td>
            <td class="text-right">{{ number_format($total_liabilities + $total_equity, 2) }}</td>
        </tr>
    </tbody>
</table>
