<x-app-layout>
    @php
        $cs = $cs ?? '$';
        $seg = request('view', 'aging');
        $bucketLabels = ['current' => __('Current'), 'days_1_30' => __('1–30 Days'), 'days_31_60' => __('31–60 Days'), 'days_61_90' => __('61–90 Days'), 'days_90_plus' => __('90+ Days')];
    @endphp

    <div class="suite ex-suite stage pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- crumbs --}}
            <nav class="crumbs">
                <a href="{{ route('accounting.vendors.dashboard') }}">Vendor Centre</a>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                <span>Reports</span>
            </nav>

            {{-- page head --}}
            <div class="page-head">
                <div>
                    <h1>{{ __('Vendor Reports') }}</h1>
                    <p class="sub">{{ __('AP aging, purchasing and supplier statements at a glance.') }}</p>
                </div>
                <div class="cluster">
                    <a href="{{ route('accounting.aging.ap-summary') }}" class="btn ghost sm">{{ __('AP Aging Detail') }}</a>
                    <a href="{{ route('accounting.vendors.dashboard') }}" class="btn cta sm">{{ __('Back to Dashboard') }}</a>
                </div>
            </div>

            {{-- seg filterbar --}}
            <form method="GET" action="{{ route('accounting.vendors.reports') }}" class="toolbar">
                <div class="seg">
                    <button type="submit" name="view" value="aging" class="segbtn @if($seg === 'aging') on @endif">{{ __('AP Aging') }}</button>
                    <button type="submit" name="view" value="reports" class="segbtn @if($seg === 'reports') on @endif">{{ __('All Reports') }}</button>
                </div>
                <span class="chip-t" style="margin-left:auto">{{ count($agingVendors) }} {{ __('vendors') }} · {{ format_number($agingTotals['total']) }} {{ $cs }}</span>
            </form>

            @if($seg === 'aging')
                {{-- aging summary table --}}
                <div class="card" style="margin-top:16px">
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:20%">{{ __('Vendor') }}</th>
                                    <th style="width:13%" class="num">{{ __('Current') }} ({{ $cs }})</th>
                                    <th style="width:13%" class="num">{{ __('1–30 Days') }} ({{ $cs }})</th>
                                    <th style="width:13%" class="num">{{ __('31–60 Days') }} ({{ $cs }})</th>
                                    <th style="width:13%" class="num">{{ __('61–90 Days') }} ({{ $cs }})</th>
                                    <th style="width:13%" class="num">{{ __('90+ Days') }} ({{ $cs }})</th>
                                    <th style="width:15%" class="num">{{ __('Total') }} ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($agingVendors as $vendor)
                                    <tr>
                                        <td style="font-weight:600;color:var(--deep-3,#0A2E32)">
                                            <a href="{{ route('accounting.vendors.show', $vendor['vendor_id']) }}" class="link">{{ $vendor['vendor_name'] }}</a>
                                        </td>
                                        <td class="numr">{{ format_number($vendor['current']) }}</td>
                                        <td class="numr">{{ format_number($vendor['days_1_30']) }}</td>
                                        <td class="numr">{{ format_number($vendor['days_31_60']) }}</td>
                                        <td class="numr">{{ format_number($vendor['days_61_90']) }}</td>
                                        <td class="numr">{{ format_number($vendor['days_90_plus']) }}</td>
                                        <td class="numr" style="font-weight:700">{{ format_number($vendor['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="empty">{{ __('No open vendor balances.') }}</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="total">{{ __('Total') }}</td>
                                    <td class="numr total">{{ format_number($agingTotals['current']) }}</td>
                                    <td class="numr total">{{ format_number($agingTotals['days_1_30']) }}</td>
                                    <td class="numr total">{{ format_number($agingTotals['days_31_60']) }}</td>
                                    <td class="numr total">{{ format_number($agingTotals['days_61_90']) }}</td>
                                    <td class="numr total">{{ format_number($agingTotals['days_90_plus']) }}</td>
                                    <td class="numr total" style="font-weight:800">{{ format_number($agingTotals['total']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- quick links --}}
                <div class="rep-grid" style="margin-top:16px">
                    <a href="{{ route('accounting.aging.ap-summary') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h3>AP Aging Summary</h3>
                        <p>Open vendor balances bucketed by how overdue they are.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.aging.ap-detail') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18v16H3z"/><path d="M3 10h18"/></svg></span>
                        <h3>AP Aging Detail</h3>
                        <p>Every open bill with its days overdue and outstanding amount.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.purchases-by-vendor') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                        <h3>Purchases by Vendor</h3>
                        <p>Invoice and purchase totals grouped by supplier over a period.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.purchases-by-item') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.5 5.5L20 8l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/></svg></span>
                        <h3>Purchases by Item</h3>
                        <p>What you bought and how much — summarised per product.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.vendor-statement') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
                        <h3>Vendor Statement</h3>
                        <p>A full statement of activity for a single supplier.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.vendor-credit-balance') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8m0 4h4"/></svg></span>
                        <h3>Vendor Credit Balance</h3>
                        <p>Outstanding credit notes that can be applied to bills.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.unbilled-receipts') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></span>
                        <h3>Unbilled Receipts</h3>
                        <p>Goods received but not yet matched to a supplier bill.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.unbilled-deliveries') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4v10l8 4 8-4z"/><path d="M12 3v10m0 0l-8-4m8 4l8-4"/></svg></span>
                        <h3>Unbilled Deliveries</h3>
                        <p>PO deliveries awaiting a matching supplier bill.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                </div>
            @else
                {{-- all reports grid --}}
                <div class="rep-grid" style="margin-top:16px">
                    <a href="{{ route('accounting.aging.ap-summary') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                        <h3>AP Aging Summary</h3>
                        <p>Open vendor balances bucketed by how overdue they are.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.aging.ap-detail') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h18v16H3z"/><path d="M3 10h18"/></svg></span>
                        <h3>AP Aging Detail</h3>
                        <p>Every open bill with its days overdue and outstanding amount.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.purchases-by-vendor') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                        <h3>Purchases by Vendor</h3>
                        <p>Invoice and purchase totals grouped by supplier over a period.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.purchases-by-item') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.5 5.5L20 8l-4 4 1 6-5-3-5 3 1-6-4-4 5.5-.5z"/></svg></span>
                        <h3>Purchases by Item</h3>
                        <p>What you bought and how much — summarised per product.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.vendor-statement') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></span>
                        <h3>Vendor Statement</h3>
                        <p>A full statement of activity for a single supplier.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.vendor-credit-balance') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8m0 4h4"/></svg></span>
                        <h3>Vendor Credit Balance</h3>
                        <p>Outstanding credit notes that can be applied to bills.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.unbilled-receipts') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></span>
                        <h3>Unbilled Receipts</h3>
                        <p>Goods received but not yet matched to a supplier bill.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                    <a href="{{ route('accounting.reports.unbilled-deliveries') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4v10l8 4 8-4z"/><path d="M12 3v10m0 0l-8-4m8 4l8-4"/></svg></span>
                        <h3>Unbilled Deliveries</h3>
                        <p>PO deliveries awaiting a matching supplier bill.</p>
                        <span class="rep-go">Open report →</span>
                    </a>
                </div>
            @endif

            {{-- communication centre --}}
            <div class="card" style="margin-top:16px">
                <div class="sec-head">
                    <span class="sec-ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <h2>{{ __('Communication Centre') }}</h2>
                </div>
                <div class="rep-grid">
                    <a href="{{ route('accounting.reports.vendor-statement') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>
                        <h3>Email Vendor Statement</h3>
                        <p>Send a statement of activity to a supplier directly.</p>
                        <span class="rep-go">Open →</span>
                    </a>
                    <a href="{{ route('accounting.vendors.index') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span>
                        <h3>Contact Vendors</h3>
                        <p>Browse the vendor directory with full contact details.</p>
                        <span class="rep-go">Open →</span>
                    </a>
                    <a href="{{ route('accounting.vendors.settings') }}" class="card rep-card">
                        <span class="rep-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></span>
                        <h3>Vendor Settings</h3>
                        <p>Default payment terms, currency and due-soon window.</p>
                        <span class="rep-go">Open →</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
