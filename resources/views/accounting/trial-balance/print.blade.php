@php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="header">
    <div class="company">{{ $company->name }}</div>
    <div class="report-title">Trial Balance</div>
    <div class="period">As of: {{ $asOfDate }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Account Code</th>
            <th>Account Name</th>
            <th class="text-right">Dr ({{ $cs }})</th>
            <th class="text-right">Cr ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trialBalance as $row)
            <tr>
                <td>{{ $row['account']->code }}</td>
                <td>{{ $row['account']->name }}</td>
                <td class="text-right">{{ $row['debit_balance'] > 0 ? format_number($row['debit_balance']) : '' }}</td>
                <td class="text-right">{{ $row['credit_balance'] > 0 ? format_number($row['credit_balance']) : '' }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-right">{{ format_number($totalDebit) }}</td>
            <td class="text-right">{{ format_number($totalCredit) }}</td>
        </tr>
        <tr class="row-grand">
            <td colspan="3" class="text-right">Difference</td>
            <td class="text-right">{{ format_number($difference) }}</td>
        </tr>
    </tbody>
</table>
