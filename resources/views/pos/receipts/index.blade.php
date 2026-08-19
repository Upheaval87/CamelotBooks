<x-app-layout>
    <div class="pos">
        <div class="wrap">
            {{-- Page Head --}}
            <div class="pos-page-head">
                <div>
                    <h1>Sales Receipts</h1>
                    <div class="pos-sub">Completed sales · reprint · refund · void</div>
                </div>
                <div class="pos-actions">
                    <div class="pos-search" style="max-width:340px">
                        <svg class="pos-mag" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <form method="GET" action="{{ route('pos.receipts.index') }}">
                            <input type="hidden" name="method" value="{{ $method ?? '' }}">
                            <input type="hidden" name="status" value="{{ $status ?? '' }}">
                            <input class="pos-in" name="q" placeholder="Search receipt # / customer…" value="{{ $q ?? '' }}">
                        </form>
                    </div>
                </div>
            </div>

            {{-- KPIs --}}
            <div class="pos-kpis">
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Total Sales</div>
                    <div class="pos-kpi-v">{{ format_money($totalSales) }}</div>
                    <div class="pos-kpi-n">{{ $totalCount }} transactions</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Today's Sales</div>
                    <div class="pos-kpi-v">{{ format_money($todaySales) }}</div>
                    <div class="pos-kpi-n">{{ $todayCount }} today</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Completed</div>
                    <div class="pos-kpi-v">{{ $stats['posted'] ?? 0 }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Draft</div>
                    <div class="pos-kpi-v">{{ $stats['draft'] ?? 0 }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Voided</div>
                    <div class="pos-kpi-v">{{ $stats['voided'] ?? 0 }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Avg Sale</div>
                    <div class="pos-kpi-v">{{ format_money($avgSale) }}</div>
                    <div class="pos-kpi-n">per transaction</div>
                </div>
            </div>

            {{-- Payment Method Chips --}}
            <div class="pos-pmchips" style="margin-bottom:16px">
                <a href="{{ route('pos.receipts.index', array_merge(request()->query(), ['method' => ''])) }}" class="pos-pm {{ empty($method) ? 'pos-pm-on' : '' }}">All Methods</a>
                @foreach($paymentMethods as $pm)
                    <a href="{{ route('pos.receipts.index', array_merge(request()->query(), ['method' => $pm->id])) }}" class="pos-pm {{ $method == $pm->id ? 'pos-pm-on' : '' }}">
                        {{ $pm->name }}
                    </a>
                @endforeach
            </div>

            {{-- Main Content + Rail --}}
            <div class="pos-shell">
                {{-- Sales Table --}}
                <div class="pos-card">
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th class="num">Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr>
                                        <td class="pos-mono pos-em">{{ $sale->sale_number }}</td>
                                        <td class="pos-em">{{ $sale->created_at?->format('d M H:i') ?? '—' }}</td>
                                        <td class="pos-em">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                        <td class="pos-em">{{ $sale->terminal?->identifier ?? '—' }}</td>
                                        <td class="num pos-bold">{{ format_money($sale->total) }}</td>
                                        <td>
                                            @php $primaryMethod = $sale->payments->first()?->paymentMethod?->name ?? '—'; @endphp
                                            <span class="pos-tchip pos-tchip-pay">{{ $primaryMethod }}</span>
                                        </td>
                                        <td>
                                            @if($sale->status === 'posted')
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Completed</span>
                                            @elseif($sale->status === 'draft')
                                                <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Draft</span>
                                            @else
                                                <span class="pos-badge pos-badge-rev"><span class="pos-bdot"></span>Voided</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <a href="{{ route('pos.sales.receipt', $sale) }}" class="pos-ibtn" title="View">👁</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="pos-empty">
                                            <h3>No receipts found</h3>
                                            <p>Sales receipts will appear here once transactions are completed.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-pag">
                        <span>Showing {{ $sales->firstItem() }}–{{ $sales->lastItem() }} of {{ $sales->total() }} receipts</span>
                        {{ $sales->withQueryString()->links() }}
                    </div>
                </div>

                {{-- Rail --}}
                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.sales.checkout') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Checkout
                        </a>
                        <a href="{{ route('pos.reports.x-report') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            X-Report
                        </a>
                    </div>
                    <div class="pos-rail-card">
                        <h3>Views</h3>
                        <a href="{{ route('pos.receipts.index') }}" class="pos-rail-view on">All Sales</a>
                        <a href="{{ route('pos.receipts.index', ['status' => 'posted']) }}" class="pos-rail-view">Completed</a>
                        <a href="{{ route('pos.receipts.index', ['status' => 'draft']) }}" class="pos-rail-view">Draft</a>
                        <a href="{{ route('pos.receipts.index', ['status' => 'voided']) }}" class="pos-rail-view">Voided</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
