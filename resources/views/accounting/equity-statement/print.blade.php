@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Statement of Changes in Equity</p>
    <p class="report-range">{{ \Carbon\Carbon::parse($dateFrom)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('M d, Y') }}</p>
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
            <th>Account</th>
            <th class="report-col-amt">Opening ({{ $cs }})</th>
            <th class="report-col-amt">Movement ({{ $cs }})</th>
            <th class="report-col-amt">Closing ({{ $cs }})</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movements as $item)
            @php $zero = abs($item['movement']) <= 0 && abs($item['opening']) <= 0 && abs($item['closing']) <= 0; @endphp
            <tr class="@if($zero) zero @endif">
                <td><span class="report-cell-code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</td>
                <td class="report-cell-amt">{{ format_number($item['opening']) }}</td>
                <td class="report-cell-amt">{{ $item['movement'] >= 0 ? '+' : '' }}{{ format_number($item['movement']) }}</td>
                <td class="report-cell-amt">{{ format_number($item['closing']) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-ink-soft" style="padding:20px 14px">No equity accounts found.</td>
            </tr>
        @endforelse
        <tr class="report-subtotal-row">
            <td>Net Income for Period</td>
            <td class="report-cell-amt"></td>
            <td class="report-cell-amt">{{ $net_income >= 0 ? '+' : '' }}{{ format_number($net_income) }}</td>
            <td class="report-cell-amt"></td>
        </tr>
        <tr class="report-total-row">
            <td>Total Equity</td>
            <td class="report-cell-amt report-total-val">{{ format_number($total_opening) }}</td>
            <td class="report-cell-amt report-total-val">{{ ($total_closing - $total_opening) >= 0 ? '+' : '' }}{{ format_number($total_closing - $total_opening) }}</td>
            <td class="report-cell-amt report-total-val">{{ format_number($total_closing) }}</td>
        </tr>
    </tbody>
</table>
