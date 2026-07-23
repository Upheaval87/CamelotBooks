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
                <td class="text-right">{{ $row['debit_balance'] > 0 ? number_format($row['debit_balance'], 2) : '' }}</td>
                <td class="text-right">{{ $row['credit_balance'] > 0 ? number_format($row['credit_balance'], 2) : '' }}</td>
            </tr>
        @endforeach
        <tr class="row-subtotal">
            <td colspan="2" class="text-right">Totals</td>
            <td class="text-right">{{ number_format($totalDebit, 2) }}</td>
            <td class="text-right">{{ number_format($totalCredit, 2) }}</td>
        </tr>
        <tr class="row-grand">
            <td colspan="3" class="text-right">Difference</td>
            <td class="text-right">{{ number_format($difference, 2) }}</td>
        </tr>
    </tbody>
</table>
