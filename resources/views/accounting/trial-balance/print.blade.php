@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Trial Balance</p>
    <p class="report-range">As of {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</p>
</div>

<div class="report-toolbar">
    <label class="zero-toggle">
        <input type="checkbox" id="reportZeroToggle" checked onchange="toggleZeroRows()">
        Show zero-balance accounts
    </label>
    <button class="btn-outline" onclick="window.print()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print
    </button>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Account Code</th>
            <th>Account Name</th>
            <th class="report-col-amt">Dr ({{ $cs }})</th>
            <th class="report-col-amt">Cr ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trialBalance as $row)
            @php $zero = $row['debit_balance'] == 0 && $row['credit_balance'] == 0; @endphp
            <tr class="@if($zero) zero @endif">
                <td><span class="report-cell-code">{{ $row['account']->code }}</span></td>
                <td>{{ $row['account']->name }}</td>
                <td class="report-cell-amt">{{ $row['debit_balance'] > 0 ? format_number($row['debit_balance']) : '' }}</td>
                <td class="report-cell-amt">{{ $row['credit_balance'] > 0 ? format_number($row['credit_balance']) : '' }}</td>
            </tr>
        @endforeach
        <tr class="report-subtotal-row">
            <td colspan="2" style="text-align:right">Totals</td>
            <td class="report-cell-amt">{{ format_number($totalDebit) }}</td>
            <td class="report-cell-amt">{{ format_number($totalCredit) }}</td>
        </tr>
        <tr class="report-total-row">
            <td colspan="3" style="text-align:right">Difference</td>
            <td class="report-cell-amt report-total-val">{{ format_number($difference) }}</td>
        </tr>
    </tbody>
</table>
