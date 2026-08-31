@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $dp = (int) \App\Models\SystemSetting::getValue('currency', 'decimal_places', session('current_company_id'), '2');
    $n = function ($v) use ($dp) {
        $v = (float) $v;
        return $v < 0 ? '('.number_format(abs($v), $dp, '.', ',').')' : number_format($v, $dp, '.', ',');
    };
    $sectionLabels = [
        'asset'     => ['current_asset' => 'Current Assets', 'non_current_asset' => 'Non-Current Assets', 'other_asset' => 'Other Assets'],
        'liability' => ['current_liability' => 'Current Liabilities', 'non_current_liability' => 'Non-Current Liabilities', 'other_liability' => 'Other Liabilities'],
        'equity'    => ['equity' => 'Equity'],
    ];
    $secSub = ['asset' => 0, 'liability' => 0, 'equity' => 0];
@endphp

<table class="fs-table">
    <thead>
        <tr>
            <th class="fs-lbl">Code</th>
            <th class="fs-lbl">Account</th>
            <th>{{ __('Balance').' ('.$cs.')' }}</th>
        </tr>
    </thead>
    <tbody>
        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Assets</td>
        </tr>
        @foreach($sectionLabels['asset'] as $subKey => $secLabel)
            @if(!empty($groups['asset'][$subKey]))
                <tr class="fs-subsection">
                    <td class="fs-stub" colspan="3">{{ $secLabel }}</td>
                </tr>
                @foreach($groups['asset'][$subKey] as $item)
                    <tr class="fs-zero">
                        <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                        <td class="fs-name">{{ $item['account']->name }}</td>
                        <td class="fs-amt">{{ $n($item['balance']) }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Total Assets</td>
            <td class="fs-amt">{{ $n($total_assets) }}</td>
        </tr>

        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Liabilities</td>
        </tr>
        @foreach($sectionLabels['liability'] as $subKey => $secLabel)
            @if(!empty($groups['liability'][$subKey]))
                <tr class="fs-subsection">
                    <td class="fs-stub" colspan="3">{{ $secLabel }}</td>
                </tr>
                @foreach($groups['liability'][$subKey] as $item)
                    <tr class="fs-zero">
                        <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                        <td class="fs-name">{{ $item['account']->name }}</td>
                        <td class="fs-amt">{{ $n($item['balance']) }}</td>
                    </tr>
                @endforeach
            @endif
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Total Liabilities</td>
            <td class="fs-amt">{{ $n($total_liabilities) }}</td>
        </tr>

        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Equity</td>
        </tr>
        @foreach($sectionLabels['equity'] as $subKey => $secLabel)
            @foreach($groups['equity'][$subKey] ?? [] as $item)
                <tr class="fs-zero">
                    <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                    <td class="fs-name">{{ $item['account']->name }}</td>
                    <td class="fs-amt">{{ $n($item['balance']) }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Current Year Earnings</td>
            <td class="fs-amt">{{ $n($current_year_earnings) }}</td>
        </tr>
        <tr class="fs-total">
            <td class="fs-stub" colspan="2">Total Equity</td>
            <td class="fs-amt">{{ $n($total_equity) }}</td>
        </tr>

        <tr class="fs-total">
            <td class="fs-stub" colspan="2">Total Liabilities &amp; Equity</td>
            <td class="fs-amt">{{ $n($total_liabilities + $total_equity) }}</td>
        </tr>
    </tbody>
</table>
