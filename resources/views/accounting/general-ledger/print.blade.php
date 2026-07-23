<div class="header">
    <div class="company">{{ $company->name }}</div>
    <div class="report-title">Account Statement</div>
    <div class="period">Account: {{ $account->code }} - {{ $account->name }} | Type: {{ ucfirst($account->type) }}</div>
    <div class="period">Opening Balance: {{ number_format($openingBalance, 2) }}</div>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Journal #</th>
            <th>Branch</th>
            <th>Memo</th>
            <th class="text-right">Debit</th>
            <th class="text-right">Credit</th>
            <th class="text-right">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $txn)
            @php $line = $txn['line']; @endphp
            <tr>
                <td>{{ $line->journalEntry->date->format('Y-m-d') }}</td>
                <td>{{ $line->journalEntry->journal_number }}</td>
                <td>{{ $line->journalEntry->branch->name ?? '-' }}</td>
                <td>{{ mb_substr($line->memo ?? $line->journalEntry->memo ?? '', 0, 30) }}</td>
                <td class="text-right">{{ (float) $line->debit > 0 ? number_format((float) $line->debit, 2) : '' }}</td>
                <td class="text-right">{{ (float) $line->credit > 0 ? number_format((float) $line->credit, 2) : '' }}</td>
                <td class="text-right">{{ number_format($txn['running_balance'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="row-grand">
            <td colspan="6" class="text-right">Closing Balance</td>
            <td class="text-right">{{ number_format($closingBalance, 2) }}</td>
        </tr>
    </tbody>
</table>
