@php $currencySymbol = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

<div class="report-head">
    <p class="company">{{ $company->name }}</p>
    <p class="report-title">Balance Sheet</p>
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

<div class="report-col-bar"><span>Description</span><span>Amount ({{ $currencySymbol }})</span></div>

<div class="report-section-bar">Assets</div>
@foreach($groups['asset'] as $subType => $items)
    @if(!empty($items))
        @foreach($items as $item)
            @php $zero = abs($item['balance']) <= 0; @endphp
            <div class="report-line @if($zero) zero @endif">
                <span><span class="code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</span>
                <span class="amt">{{ format_number($item['balance']) }}</span>
            </div>
        @endforeach
    @endif
@endforeach
<div class="report-subtotal"><span>Total Assets</span><span>{{ format_number($total_assets) }}</span></div>

<div class="report-section-bar">Liabilities</div>
@foreach($groups['liability'] as $subType => $items)
    @if(!empty($items))
        @foreach($items as $item)
            @php $zero = abs($item['balance']) <= 0; @endphp
            <div class="report-line @if($zero) zero @endif">
                <span><span class="code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</span>
                <span class="amt">{{ format_number($item['balance']) }}</span>
            </div>
        @endforeach
    @endif
@endforeach
<div class="report-subtotal"><span>Total Liabilities</span><span>{{ format_number($total_liabilities) }}</span></div>

<div class="report-section-bar">Equity</div>
@foreach($groups['equity'] as $subType => $items)
    @foreach($items as $item)
        @php $zero = abs($item['balance']) <= 0; @endphp
        <div class="report-line @if($zero) zero @endif">
            <span><span class="code">{{ $item['account']->code }}</span>{{ $item['account']->name }}</span>
            <span class="amt">{{ format_number($item['balance']) }}</span>
        </div>
    @endforeach
@endforeach
<div class="report-line">
    <span>Current Year Earnings</span>
    <span class="amt">{{ format_number($current_year_earnings) }}</span>
</div>
<div class="report-subtotal"><span>Total Equity</span><span>{{ format_number($total_equity) }}</span></div>

<div class="report-total">
    <span class="lbl">Total Liabilities &amp; Equity</span>
    <span class="val">{{ format_number($total_liabilities + $total_equity) }}</span>
</div>
