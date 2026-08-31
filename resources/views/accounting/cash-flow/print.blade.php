@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $dp = (int) \App\Models\SystemSetting::getValue('currency', 'decimal_places', session('current_company_id'), '2');
    $n = function ($v) use ($dp) {
        $v = (float) $v;
        return $v < 0 ? '('.number_format(abs($v), $dp, '.', ',').')' : number_format($v, $dp, '.', ',');
    };
@endphp

<table class="fs-table">
    <thead>
        <tr>
            <th class="fs-lbl">Code</th>
            <th class="fs-lbl">Account</th>
            <th>{{ __('Amount').' ('.$cs.')' }}</th>
        </tr>
    </thead>
    <tbody>
        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Cash Flows from Operating Activities</td>
        </tr>
        <tr>
            <td class="fs-amt"><span class="fs-code"></span></td>
            <td>Net Income</td>
            <td class="fs-amt {{ $net_income < 0 ? 'fs-neg' : '' }}">{{ $n($net_income) }}</td>
        </tr>
        @foreach($non_cash_expenses['items'] as $nci)
            <tr class="fs-zero">
                <td class="fs-amt"><span class="fs-code"></span></td>
                <td>Add: {{ $nci['account']->name }}</td>
                <td class="fs-amt {{ $nci['amount'] < 0 ? 'fs-neg' : '' }}">{{ $n($nci['amount']) }}</td>
            </tr>
        @endforeach
        @foreach($sections['operating'] as $item)
            @php $zero = abs($item['cash_effect']) <= 0; @endphp
            <tr class="@if($zero) fs-zero @endif">
                <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                <td class="fs-name">{{ $item['account']->name }}</td>
                <td class="fs-amt {{ $item['cash_effect'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['cash_effect']) }}</td>
            </tr>
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Net Cash from Operating Activities</td>
            <td class="fs-amt {{ $operating_total < 0 ? 'fs-neg' : '' }}">{{ $n($operating_total) }}</td>
        </tr>

        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Cash Flows from Investing Activities</td>
        </tr>
        @foreach($sections['investing'] as $item)
            @php $zero = abs($item['cash_effect']) <= 0; @endphp
            <tr class="@if($zero) fs-zero @endif">
                <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                <td class="fs-name">{{ $item['account']->name }}</td>
                <td class="fs-amt {{ $item['cash_effect'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['cash_effect']) }}</td>
            </tr>
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Net Cash from Investing Activities</td>
            <td class="fs-amt {{ $investing_total < 0 ? 'fs-neg' : '' }}">{{ $n($investing_total) }}</td>
        </tr>

        <tr class="fs-section">
            <td class="fs-stub" colspan="3">Cash Flows from Financing Activities</td>
        </tr>
        @foreach($sections['financing'] as $item)
            @php $zero = abs($item['cash_effect']) <= 0; @endphp
            <tr class="@if($zero) fs-zero @endif">
                <td class="fs-amt"><span class="fs-code">{{ $item['account']->code }}</span></td>
                <td class="fs-name">{{ $item['account']->name }}</td>
                <td class="fs-amt {{ $item['cash_effect'] < 0 ? 'fs-neg' : '' }}">{{ $n($item['cash_effect']) }}</td>
            </tr>
        @endforeach
        <tr class="fs-subtotal">
            <td class="fs-stub" colspan="2">Net Cash from Financing Activities</td>
            <td class="fs-amt {{ $financing_total < 0 ? 'fs-neg' : '' }}">{{ $n($financing_total) }}</td>
        </tr>

        <tr class="fs-total">
            <td class="fs-stub" colspan="2">Net Change in Cash</td>
            <td class="fs-amt {{ $net_change < 0 ? 'fs-neg' : '' }}">{{ $n($net_change) }}</td>
        </tr>
        <tr>
            <td class="fs-amt"><span class="fs-code"></span></td>
            <td>Beginning Cash Balance</td>
            <td class="fs-amt {{ $beginning_cash < 0 ? 'fs-neg' : '' }}">{{ $n($beginning_cash) }}</td>
        </tr>
        <tr class="fs-total">
            <td class="fs-amt"><span class="fs-code"></span></td>
            <td>Ending Cash Balance</td>
            <td class="fs-amt {{ $ending_cash < 0 ? 'fs-neg' : '' }}">{{ $n($ending_cash) }}</td>
        </tr>
    </tbody>
</table>
