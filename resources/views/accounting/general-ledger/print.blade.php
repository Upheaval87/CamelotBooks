@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Account Statement</p>
    <p class="report-range">Account: {{ $account->code }} — {{ $account->name }} | Type: {{ ucfirst($account->type) }}</p>
    <p class="report-range">Opening Balance: {{ format_number($openingBalance) }}</p>
</div>

<div class="report-toolbar">
    <button class="btn-outline" onclick="window.print()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print
    </button>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Journal #</th>
            <th>Branch</th>
            <th>Description</th>
            <th class="report-col-amt">Debit ({{ $cs }})</th>
            <th class="report-col-amt">Credit ({{ $cs }})</th>
            <th class="report-col-amt">Balance</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $txn)
            @php $line = $txn['line']; @endphp
            <tr class="@if((float)$line->debit == 0 && (float)$line->credit == 0) zero @endif">
                <td>{{ $line->journalEntry->date->format('Y-m-d') }}</td>
                <td>{{ $line->journalEntry->journal_number }}</td>
                <td>{{ $line->journalEntry->branch->name ?? '-' }}</td>
                <td>{{ mb_substr($line->memo ?? $line->journalEntry->memo ?? '', 0, 30) }}</td>
                <td class="report-cell-amt">{{ (float) $line->debit > 0 ? format_number((float) $line->debit) : '' }}</td>
                <td class="report-cell-amt">{{ (float) $line->credit > 0 ? format_number((float) $line->credit) : '' }}</td>
                <td class="report-cell-amt">{{ format_number($txn['running_balance']) }}</td>
            </tr>
        @endforeach
        <tr class="report-total-row">
            <td colspan="6" style="text-align:right">Closing Balance</td>
            <td class="report-cell-amt report-total-val">{{ format_number($closingBalance) }}</td>
        </tr>
    </tbody>
</table>
