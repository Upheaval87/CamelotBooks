@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Cash Flow Statement</p>
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

<div class="report-col-bar"><span>Description</span><span>Amount ({{ $cs }})</span></div>

<div class="report-section-bar">Operating Activities</div>
<div class="report-line">
    <span>Net Income</span>
    <span class="amt">{{ format_number($net_income) }}</span>
</div>
@foreach($non_cash_expenses['items'] as $item)
    @php $zero = abs($item['amount']) <= 0; @endphp
    <div class="report-line @if($zero) zero @endif">
        <span>Add: {{ $item['account']->name }}</span>
        <span class="amt">{{ format_number($item['amount']) }}</span>
    </div>
@endforeach
@foreach($sections['operating'] as $item)
    @php $zero = abs($item['cash_effect']) <= 0; @endphp
    <div class="report-line @if($zero) zero @endif">
        <span>{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</span>
        <span class="amt">{{ format_number($item['cash_effect']) }}</span>
    </div>
@endforeach
<div class="report-subtotal"><span>Net Cash from Operating</span><span>{{ format_number($operating_total) }}</span></div>

<div class="report-section-bar">Investing Activities</div>
@forelse($sections['investing'] as $item)
    @php $zero = abs($item['cash_effect']) <= 0; @endphp
    <div class="report-line @if($zero) zero @endif">
        <span>{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</span>
        <span class="amt">{{ format_number($item['cash_effect']) }}</span>
    </div>
@empty
    <div class="report-line zero">
        <span>No investing activities</span>
        <span class="amt">{{ format_number(0) }}</span>
    </div>
@endforelse
<div class="report-subtotal"><span>Net Cash from Investing</span><span>{{ format_number($investing_total) }}</span></div>

<div class="report-section-bar">Financing Activities</div>
@forelse($sections['financing'] as $item)
    @php $zero = abs($item['cash_effect']) <= 0; @endphp
    <div class="report-line @if($zero) zero @endif">
        <span>{{ $item['change'] > 0 ? 'Increase in' : 'Decrease in' }} {{ $item['account']->name }}</span>
        <span class="amt">{{ format_number($item['cash_effect']) }}</span>
    </div>
@empty
    <div class="report-line zero">
        <span>No financing activities</span>
        <span class="amt">{{ format_number(0) }}</span>
    </div>
@endforelse
<div class="report-subtotal"><span>Net Cash from Financing</span><span>{{ format_number($financing_total) }}</span></div>

<div class="report-total">
    <span class="lbl">Net Change in Cash</span>
    <span class="val">{{ format_number($net_change) }}</span>
</div>
<div class="report-line">
    <span>Beginning Cash Balance</span>
    <span class="amt">{{ format_number($beginning_cash) }}</span>
</div>
<div class="report-subtotal">
    <span>Ending Cash Balance</span>
    <span>{{ format_number($ending_cash) }}</span>
</div>

@if($mismatch)
    <div class="text-xs text-red-600 mt-4 p-3 bg-red-50 rounded" style="margin:16px -14px -24px;padding:10px 14px">
        Warning: Ending cash does not match actual bank balances. Difference: {{ format_number($mismatch) }}
    </div>
@endif
