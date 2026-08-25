<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Z-Report</h1>
                <div class="pos-sub">End-of-day consolidated sales report</div>
            </div>
        </div>

        @if(!$data)
            <div class="pos-card">
                <div class="pos-empty">
                    <h3>No closed till sessions found</h3>
                    <p>Z-Reports are generated from closed till sessions.</p>
                </div>
            </div>
        @else
            <div class="pos-shell">
                <div>
                    <div class="pos-card" style="margin-bottom:16px">
                        <div class="pos-card-h">Session Info</div>
                        <div class="pos-pad">
                            <div class="pos-g4">
                                <div>
                                    <div class="pos-kpi-l">Terminal</div>
                                    <div class="pos-kpi-v">{{ $data['session']->terminal?->identifier ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Cashier</div>
                                    <div class="pos-kpi-v">{{ $data['session']->user?->name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Opened</div>
                                    <div class="pos-kpi-v">{{ $data['session']->opened_at?->format('M d, H:i') ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Closed</div>
                                    <div class="pos-kpi-v">{{ $data['session']->closed_at?->format('M d, H:i') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pos-card" style="margin-bottom:16px">
                        <div class="pos-card-h">1 · Sales Summary</div>
                        <div class="pos-pad">
                            <div class="pos-g4">
                                <div class="pos-sbox">
                                    <div class="pos-ic"><span class="pos-bdot green"></span></div>
                                    <div class="pos-n">{{ $data['sales_count'] }}</div>
                                    <div class="pos-l">Gross Sales</div>
                                </div>
                                <div class="pos-sbox">
                                    <div class="pos-ic"><span class="pos-bdot red"></span></div>
                                    <div class="pos-n" style="color:var(--pos-red)">{{ $data['returns_count'] }}</div>
                                    <div class="pos-l">Returns</div>
                                </div>
                                <div class="pos-sbox">
                                    <div class="pos-ic"><span class="pos-bdot green"></span></div>
                                    <div class="pos-n">{{ format_money($data['sales_total']) }}</div>
                                    <div class="pos-l">Gross Amount</div>
                                </div>
                                <div class="pos-sbox">
                                    <div class="pos-ic"><span class="pos-bdot amber"></span></div>
                                    <div class="pos-n pos-numr">{{ format_money($data['net_sales']) }}</div>
                                    <div class="pos-l">Net Sales</div>
                                </div>
                            </div>
                            <div class="pos-g3" style="margin-top:16px">
                                <div>
                                    <div class="pos-kpi-l">Subtotal</div>
                                    <div class="pos-kpi-v">{{ format_money($data['sales_subtotal']) }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Tax</div>
                                    <div class="pos-kpi-v">{{ format_money($data['sales_tax']) }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Returns</div>
                                    <div class="pos-kpi-v" style="color:var(--pos-red)">-{{ format_money($data['returns_total']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pos-card" style="margin-bottom:16px">
                        <div class="pos-card-h">2 · Payments by Method</div>
                        <div class="pos-li-wrap">
                            <table class="pos-tbl">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th class="num">Sales</th>
                                        <th class="num">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data['payments_by_method'] as $pm)
                                        <tr>
                                            <td class="pos-bold">{{ $pm->method_name }}</td>
                                            <td class="num">{{ $pm->sale_count }}</td>
                                            <td class="num pos-bold">{{ format_money($pm->total_amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="pos-em">No payments recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pos-card pos-card-accent" style="margin-bottom:16px">
                        <div class="pos-card-h">3 · Cash Drawer Reconciliation</div>
                        <div class="pos-pad">
                            <div class="pos-g3">
                                <div>
                                    <div class="pos-kpi-l">Opening Float</div>
                                    <div class="pos-kpi-v">{{ format_money($data['opening_float']) }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">+ Cash Payments</div>
                                    <div class="pos-kpi-v">{{ format_money($data['cash_payments']) }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">− Returns (Cash)</div>
                                    <div class="pos-kpi-v">{{ format_money($data['returns_total']) }}</div>
                                </div>
                            </div>
                            <div class="pos-g2" style="margin-top:16px">
                                <div>
                                    <div class="pos-kpi-l">= Expected Cash</div>
                                    <div class="pos-kpi-v pos-numr">{{ format_money($data['expected_cash']) }}</div>
                                </div>
                                <div>
                                    <div class="pos-kpi-l">Actual Cash Count</div>
                                    <div class="pos-kpi-v pos-numr">{{ $data['actual_cash_count'] !== null ? format_money($data['actual_cash_count']) : '—' }}</div>
                                </div>
                            </div>
                            <div style="margin-top:12px;padding:10px;border-radius:8px;background:{{ ($data['variance'] ?? 0) < 0 ? 'rgba(185,28,28,.08)' : 'rgba(18,143,142,.08)' }}">
                                <div class="pos-kpi-l">Variance</div>
                                <div class="pos-kpi-v pos-numr pos-bold" style="color:{{ ($data['variance'] ?? 0) > 0 ? 'var(--pos-green)' : (($data['variance'] ?? 0) < 0 ? 'var(--pos-red)' : 'var(--pos-ink)') }}">
                                    {{ $data['variance'] !== null ? ($data['variance'] >= 0 ? '+' : '') . format_money($data['variance']) : '—' }}
                                </div>
                            </div>
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
                        <a href="{{ route('pos.reports.sales-by-terminal') }}" class="pos-rail-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                            Sales by Terminal
                        </a>
                        <a href="{{ route('pos.reports.sales-by-cashier') }}" class="pos-rail-link">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Sales by Cashier
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
