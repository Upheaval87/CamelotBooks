<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Sales by Cashier</h1>
                <div class="pos-sub">Individual cashier performance metrics</div>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-pad">
                        <form method="GET" action="{{ route('pos.reports.sales-by-cashier') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap">
                            <div>
                                <label class="pos-lbl">From</label>
                                <input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}" class="pos-in">
                            </div>
                            <div>
                                <label class="pos-lbl">To</label>
                                <input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}" class="pos-in">
                            </div>
                            <button type="submit" class="pos-btn pos-btn-sec">Filter</button>
                        </form>
                    </div>
                </div>

                <div class="pos-card">
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Cashier</th>
                                    <th class="num">Sessions</th>
                                    <th class="num">Sales Count</th>
                                    <th class="num">Gross Sales</th>
                                    <th class="num">Returns</th>
                                    <th class="num">Net Sales</th>
                                    <th class="num">Avg Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['cashiers'] as $row)
                                    <tr>
                                        <td class="pos-bold">{{ $row['user']?->name ?? '—' }}</td>
                                        <td class="num">{{ $row['sessions_count'] }}</td>
                                        <td class="num">{{ $row['sales_count'] }}</td>
                                        <td class="num">{{ format_money($row['sales_total']) }}</td>
                                        <td class="num" style="color:var(--pos-red)">{{ format_money($row['returns_total']) }}</td>
                                        <td class="num pos-bold">{{ format_money($row['net_sales']) }}</td>
                                        <td class="num pos-em">{{ format_money($row['average_sale']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="pos-em">No cashier sessions found for this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(count($data['cashiers']) > 0)
                            <tfoot>
                                <tr>
                                    <td class="pos-em">Grand Total</td>
                                    <td class="num pos-em">—</td>
                                    <td class="num pos-bold">{{ $data['grand_count'] }}</td>
                                    <td class="num pos-bold">{{ format_money($data['grand_total_sales']) }}</td>
                                    <td class="num pos-bold" style="color:var(--pos-red)">{{ format_money($data['grand_total_returns']) }}</td>
                                    <td class="num pos-bold pos-numr">{{ format_money($data['grand_net_sales']) }}</td>
                                    <td class="num pos-em">{{ format_money($data['grand_count'] > 0 ? $data['grand_total_sales'] / $data['grand_count'] : 0) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.reports.overview') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                        Reports Overview
                    </a>
                    <a href="{{ route('pos.reports.x-report') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        X Report
                    </a>
                    <a href="{{ route('pos.reports.z-report') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Z Report
                    </a>
                    <a href="{{ route('pos.reports.sales-by-terminal') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                        Sales by Terminal
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
