@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $openStatuses = ['approved', 'partially_paid', 'overdue'];
    $agingBuckets = [
        'current' => ['label' => 'Current', 'class' => ''],
        'days_1_30' => ['label' => '1–30 days', 'class' => 'warn'],
        'days_31_60' => ['label' => '31–60 days', 'class' => 'warn'],
        'days_61_90' => ['label' => '61–90 days', 'class' => 'red'],
        'days_90_plus' => ['label' => '90+ days', 'class' => 'red'],
    ];
    $agingMax = max((float) $aging['totals']['current'], (float) $aging['totals']['days_1_30'], (float) $aging['totals']['days_31_60'], (float) $aging['totals']['days_61_90'], (float) $aging['totals']['days_90_plus'], 0.01);
    $exportParams = request()->except('page');
@endphp
<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite ex-suite stage">

                {{-- crumbs --}}
                <div class="crumbs">
                    <a href="{{ route('accounting.vendors.dashboard') }}">Vendor Centre</a>
                    <span>›</span>
                    <span class="here">Dashboard</span>
                </div>

                {{-- page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Vendor Centre</h1>
                        <div class="sub">A single workspace for your suppliers — balances, bills, payments and insights.</div>
                    </div>
                    <div class="cluster">
                        <a href="{{ route('accounting.vendors.create') }}" class="btn btn-ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            New Vendor
                        </a>
                        <a href="{{ route('accounting.bills.create') }}" class="btn btn-sec">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8m0 4h8"/></svg>
                            New Bill
                        </a>
                        <details class="more">
                            <summary class="icon-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
                            </summary>
                            <div class="more-menu">
                                <a href="{{ route('accounting.vendors.export', $exportParams) }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                    Export CSV
                                </a>
                                <a href="{{ route('accounting.vendors.reports') }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg>
                                    Vendor Reports
                                </a>
                                <a href="{{ route('accounting.vendors.settings') }}" class="more-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                    Settings
                                </a>
                            </div>
                        </details>
                    </div>
                </div>

                {{-- KPI row (§1) --}}
                <div class="kpis">
                    <div class="kpi hero">
                        <div class="l">Total Payables ({{ $cs }})</div>
                        <div class="v">{{ format_number($stats['open_balance']) }}</div>
                        <div class="n">{{ $stats['unpaid_bills'] }} open bills across {{ $stats['total_vendors'] }} vendors</div>
                    </div>
                    <div class="kpi warn">
                        <div class="l">Overdue ({{ $cs }})</div>
                        <div class="v">{{ format_number($stats['overdue']) }}</div>
                        <div class="n">Past due balance</div>
                    </div>
                    <div class="kpi">
                        <div class="l">Due This Month ({{ $cs }})</div>
                        <div class="v">{{ format_number($stats['bills_this_month']) }}</div>
                        <div class="n">Bills raised this month</div>
                    </div>
                    <div class="kpi">
                        <div class="l">Awaiting Approval</div>
                        <div class="v">{{ number_format($stats['pending_approval']) }}</div>
                        <div class="n">Bills in approval queue</div>
                    </div>
                </div>

                {{-- Overview / aging (§1) --}}
                <section class="card card-sec" style="margin-top:16px">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 12l4-4 4 4 4-4"/></svg></span>
                        <h2>Overview</h2>
                        <span class="rule"></span>
                    </div>
                    <h3 style="margin:0 0 2px;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint,#8aa5a7)">Payables Aging</h3>
                    <div class="vc-aging-grid" style="margin-top:8px">
                        @foreach($agingBuckets as $key => $bucket)
                            <div class="vc-aging-cell">
                                <div class="vc-aging-lbl">{{ $bucket['label'] }}</div>
                                <div class="vc-aging-val {{ $bucket['class'] }}">{{ format_number($aging['totals'][$key]) }}</div>
                                <div class="vc-aging-bar"><span style="width: {{ round(((float) $aging['totals'][$key] / $agingMax) * 100, 1) }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="dash-cols">
                    {{-- Top vendors --}}
                    <section class="card card-sec">
                        <div class="sec-head">
                            <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                        <h2>Top Vendors by Balance</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th class="num">Current ({{ $cs }})</th>
                                    <th class="num">Balance ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topVendors as $row)
                                    <tr>
                                        <td><a href="{{ route('accounting.vendors.show', $row['vendor_id']) }}" class="row-link">{{ $row['vendor_name'] }}</a></td>
                                        <td class="numr">{{ format_number($row['current']) }}</td>
                                        <td class="numr {{ $row['total'] > 0 ? 'red' : '' }}">{{ format_number($row['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3"><div class="empty">No vendor balances.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Due soon --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                        <h2>Due in Next 30 Days</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Vendor</th>
                                    <th>Bill</th>
                                    <th class="num">Due ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dueSoon as $bill)
                                    <tr>
                                        <td>{{ $bill->vendor->name ?? '—' }}</td>
                                        <td><a href="{{ route('accounting.bills.show', $bill) }}" class="mono">{{ $bill->bill_number }}</a></td>
                                        <td class="numr red">{{ format_number($bill->balance_due) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3"><div class="empty">Nothing due in the next 30 days.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
                </div>

                {{-- Recent activity --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                        <h2>Recent Activity</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Vendor</th>
                                    <th>Date</th>
                                    <th class="num">Amount ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $activity = collect(); @endphp
                                @foreach($recentBills as $bill)
                                    @php $activity->push(['type' => 'Bill', 'ref' => $bill->bill_number, 'route' => route('accounting.bills.show', $bill), 'vendor' => $bill->vendor->name ?? '—', 'date' => $bill->bill_date, 'amount' => (float) $bill->amount]); @endphp
                                @endforeach
                                @foreach($recentPayments as $payment)
                                    @php $activity->push(['type' => 'Payment', 'ref' => $payment->payment_number, 'route' => route('accounting.vendor-payments.show', $payment), 'vendor' => $payment->vendor->name ?? '—', 'date' => $payment->payment_date, 'amount' => (float) $payment->amount]); @endphp
                                @endforeach
                                @foreach($recentCredits as $credit)
                                    @php $activity->push(['type' => 'Credit', 'ref' => $credit->credit_number, 'route' => route('accounting.vendor-credits.show', $credit), 'vendor' => $credit->vendor->name ?? '—', 'date' => $credit->created_at?->toDateString(), 'amount' => -abs((float) $credit->amount)]); @endphp
                                @endforeach
                                @php $activity = $activity->sortByDesc('date')->take(10); @endphp
                                @forelse($activity as $item)
                                    <tr>
                                        <td>
                                            @if($item['type'] === 'Bill') <span class="badge b-teal">Bill</span>
                                            @elseif($item['type'] === 'Payment') <span class="badge b-mint">Payment</span>
                                            @else <span class="badge b-gray">Credit</span> @endif
                                        </td>
                                        <td><a href="{{ $item['route'] }}" class="mono">{{ $item['ref'] }}</a></td>
                                        <td>{{ $item['vendor'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($item['date'])->format('d M Y') }}</td>
                                        <td class="numr {{ $item['amount'] < 0 ? 'mint' : '' }}">{{ format_number(abs($item['amount'])) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="empty">No recent vendor activity.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            @include('accounting.vendors._slim-rail', ['active' => 'dashboard'])
        </div>
    </div>
</x-app-layout>
