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
            <th>Debit ({{ $cs }})</th>
            <th>Credit ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trialBalance as $row)
            @php $zero = $row['debit_balance'] == 0 && $row['credit_balance'] == 0; @endphp
            <tr class="@if($zero) fs-zero @endif">
                <td class="fs-amt"><span class="fs-code">{{ $row['account']->code }}</span></td>
                <td class="fs-name">{{ $row['account']->name }}</td>
                <td class="fs-amt">{{ $row['debit_balance'] > 0 ? $n($row['debit_balance']) : '' }}</td>
                <td class="fs-amt">{{ $row['credit_balance'] > 0 ? $n($row['credit_balance']) : '' }}</td>
            </tr>
        @endforeach
        <tr class="fs-total">
            <td class="fs-stub" colspan="2">Total</td>
            <td class="fs-amt">{{ $n($totalDebit) }}</td>
            <td class="fs-amt">{{ $n($totalCredit) }}</td>
        </tr>
    </tbody>
</table>
