@php $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Income Statement</p>
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

<div class="report-col-bar"><span>Description</span><span>Amount ({{ $currencySymbol }})</span></div>

<div class="report-section-bar">Income</div>
@foreach($groups['income'] as $subType => $items)
    @foreach($items as $item)
        @php $zero = max(0, $item['net']) <= 0; @endphp
        <div class="report-line @if($zero) zero @endif">
            <span><span class="code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</span>
            <span class="amt">{{ format_number(max(0, $item['net'])) }}</span>
        </div>
    @endforeach
@endforeach
<div class="report-subtotal"><span>Total Income</span><span>{{ format_number($total_income) }}</span></div>

<div class="report-section-bar">Expenses</div>
@foreach($groups['expense'] as $subType => $items)
    @foreach($items as $item)
        @php $zero = max(0, $item['net']) <= 0; @endphp
        <div class="report-line @if($zero) zero @endif">
            <span><span class="code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</span>
            <span class="amt">{{ format_number(max(0, $item['net'])) }}</span>
        </div>
    @endforeach
@endforeach
<div class="report-subtotal"><span>Total Expenses</span><span>{{ format_number($total_expenses) }}</span></div>

<div class="report-total">
    <span class="lbl">{{ $net_income >= 0 ? 'Net Income' : 'Net Loss' }}</span>
    <span class="val">{{ format_number(abs($net_income)) }}</span>
</div>
