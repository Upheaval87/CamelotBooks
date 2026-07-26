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
            <th class="text-right">Debit Balance</th>
            <th class="text-right">Credit Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trialBalance as $row)
            <tr>
                <td>{{ $row['account']->code }}</td>
                <td>{{ $row['account']->name }}</td>
                <td class="text-right">{{ $row['debit_balance'] > 0 ? format_money($row['debit_balance']) : '' }}</td>
                <td class="text-right">{{ $row['credit_balance'] > 0 ? format_money($row['credit_balance']) : '' }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-right">{{ format_money($totalDebit) }}</td>
            <td class="text-right">{{ format_money($totalCredit) }}</td>
        </tr>
        <tr class="row-grand">
            <td colspan="3" class="text-right">Difference</td>
            <td class="text-right">{{ format_money($difference) }}</td>
        </tr>
    </tbody>
</table>
