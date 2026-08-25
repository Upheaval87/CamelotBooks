<x-app-layout>
    @php
        $companyId = (int) session('current_company_id');
        $branchId = $branchId ?? (int) request()->query('branch_id', 0);
        $branches = $branches ?? \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $todayStart = now()->startOfDay();

        $todaySales = $todaySales ?? \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('created_at', '>=', $todayStart)
            ->count();
        $todayRevenue = $todayRevenue ?? \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('created_at', '>=', $todayStart)
            ->sum('total');
        $todayRevenue = (float) $todayRevenue;
        $averageSale = $averageSale ?? ($todaySales > 0 ? $todayRevenue / $todaySales : 0.0);
        $todayReturns = $todayReturns ?? \App\Models\PosReturn::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereDate('date', today())
            ->sum('total');
        $itemsSold = $itemsSold ?? \App\Models\PosSaleLine::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)
                    ->posted()
                    ->when($branchId, fn ($w) => $w->where('branch_id', $branchId))
                    ->where('created_at', '>=', $todayStart);
            })
            ->sum('quantity');

        $recentSales = $recentSales ?? \App\Models\PosSale::forCompany($companyId)
            ->with(['customer:id,name', 'terminal:id,identifier'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->limit(10)
            ->get(['id', 'sale_number', 'customer_id', 'terminal_id', 'total', 'status', 'created_at']);

        $terminals = $terminals ?? \App\Models\PosTerminal::forCompany($companyId)
            ->active()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('identifier')
            ->get();

        $openSessions = \App\Models\PosCashierSession::forCompany($companyId)
            ->open()
            ->whereIn('terminal_id', $terminals->pluck('id'))
            ->with('user:id,name')
            ->get()
            ->keyBy('terminal_id');

        $hasOpenShiftHere = false;
        $sessionTerminalId = session('pos_terminal_id');
        if ($sessionTerminalId && $openSessions->has($sessionTerminalId)) {
            $hasOpenShiftHere = true;
        }

        $lowStockProducts = $lowStockProducts ?? \App\Models\Product::query()
            ->selectRaw('products.*, COALESCE((SELECT SUM(s.quantity_on_hand) FROM inventory_stock s WHERE s.product_id = products.id), 0) AS pos_stock_on_hand')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('low_stock_alerts', true)
            ->orderBy('name')
            ->get()
            ->filter(fn ($p) => $p->pos_stock_on_hand <= (float) ($p->effective_reorder_point ?? 0))
            ->take(8)
            ->values();

        $lowStockCount = is_countable($lowStockProducts) ? count($lowStockProducts) : 0;

        $sessionTerminal = $terminals->firstWhere('id', (int) $sessionTerminalId);

        $yesterdaySales = \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])
            ->sum('total');
        $yesterdayRevenue = (float) $yesterdaySales;
        $pctChange = $yesterdayRevenue > 0 ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100) : 0;

        $completedSales = \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('created_at', '>=', $todayStart)
            ->count();
        $voidedSales = $todaySales - $completedSales;

        $cashCollected = (float) \App\Models\PosPayment::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)->posted()->when($branchId, fn ($w) => $w->where('branch_id', $branchId))->where('created_at', '>=', $todayStart);
            })->where('method', 'Cash')->sum('amount');

        $cardCollected = (float) \App\Models\PosPayment::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)->posted()->when($branchId, fn ($w) => $w->where('branch_id', $branchId))->where('created_at', '>=', $todayStart);
            })->where('method', 'Card')->sum('amount');
        $mobileCollected = (float) \App\Models\PosPayment::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)->posted()->when($branchId, fn ($w) => $w->where('branch_id', $branchId))->where('created_at', '>=', $todayStart);
            })->where('method', 'Mobile Money')->sum('amount');
        $cardMobileTotal = $cardCollected + $mobileCollected;

        $creditCollected = (float) \App\Models\PosPayment::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)->posted()->when($branchId, fn ($w) => $w->where('branch_id', $branchId))->where('created_at', '>=', $todayStart);
            })->where('method', 'Credit')->sum('amount');

        $topSellingItems = \App\Models\PosSaleLine::whereHas('sale', function ($q) use ($companyId, $branchId, $todayStart) {
                $q->forCompany($companyId)->posted()->when($branchId, fn ($w) => $w->where('branch_id', $branchId))->where('created_at', '>=', $todayStart);
            })->selectRaw('product_id, SUM(quantity) as qty, SUM(amount) as total_amount')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->with('product:id,name')
            ->get();

        $salesByCashier = \App\Models\PosSale::forCompany($companyId)
            ->posted()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('created_at', '>=', $todayStart)
            ->whereNotNull('created_by')
            ->selectRaw('created_by, SUM(total) as total_amount')
            ->groupBy('created_by')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->with('creator:id,name')
            ->get();
    @endphp

    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Dashboard</h1>
                <p class="pos-sub">Today's sales performance · {{ now()->format('D, d M Y') }}</p>
            </div>
            <div class="pos-actions">
                @if($branches->count() > 1)
                    <form method="GET" action="{{ route('pos.dashboard') }}" class="pos-f">
                        <select name="branch_id" onchange="this.form.submit()" class="pos-in" style="width:auto;height:38px;font-size:12.5px">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('pos.sales.checkout') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    New Sale
                </a>
                <a href="{{ route('pos.terminals.index') }}" class="pos-btn pos-btn-sec">Open Register</a>
                <a href="{{ route('pos.reports.overview') }}" class="pos-btn pos-btn-ghost">View Reports</a>
                <a href="{{ route('pos.products.index') }}" class="pos-btn pos-btn-ghost">Manage Products</a>
                <a href="{{ route('pos.receipts.index') }}" class="pos-btn pos-btn-ghost">Transactions</a>
            </div>
        </div>

        @if(!$hasOpenShiftHere && $sessionTerminal)
            <x-feedback.alert variant="warning" class="mb-4">
                No till session is open on <strong>{{ $sessionTerminal->identifier }}</strong>. Open a till session before making sales.
            </x-feedback.alert>
        @endif

        <div class="pos-kpis">
            <div class="pos-kpi pos-kpi-hero">
                <div class="pos-kpi-l">Today's Sales</div>
                <div class="pos-kpi-v">{{ format_money($todayRevenue) }}</div>
                <div class="pos-kpi-n">
                    @if($pctChange > 0)
                        <span style="color:#7FD1C0">+{{ $pctChange }}%</span> vs yesterday
                    @elseif($pctChange < 0)
                        <span style="color:#ffb4b4">{{ $pctChange }}%</span> vs yesterday
                    @else
                        No change vs yesterday
                    @endif
                </div>
            </div>
            <div class="pos-kpi">
                <div class="pos-kpi-l">Transactions</div>
                <div class="pos-kpi-v">{{ format_number($todaySales, 0) }}</div>
                <div class="pos-kpi-n">{{ $completedSales }} completed · {{ $voidedSales }} void</div>
            </div>
            <div class="pos-kpi">
                <div class="pos-kpi-l">Avg Sale Value</div>
                <div class="pos-kpi-v">{{ format_money($averageSale) }}</div>
                <div class="pos-kpi-n">per transaction</div>
            </div>
            <div class="pos-kpi">
                <div class="pos-kpi-l">Cash Collected</div>
                <div class="pos-kpi-v">{{ format_money($cashCollected) }}</div>
                <div class="pos-kpi-n">cash drawer</div>
            </div>
            <div class="pos-kpi">
                <div class="pos-kpi-l">Card / Mobile</div>
                <div class="pos-kpi-v">{{ format_money($cardMobileTotal) }}</div>
                <div class="pos-kpi-n">card {{ format_money($cardCollected) }} · mobile {{ format_money($mobileCollected) }}</div>
            </div>
            <div class="pos-kpi">
                <div class="pos-kpi-l">Outstanding Credit</div>
                <div class="pos-kpi-v">{{ format_money($creditCollected) }}</div>
                <div class="pos-kpi-n">credit sales</div>
            </div>
        </div>

        <div class="pos-shell" style="margin-top:16px">
            <div>
                <div class="pos-grid2" style="margin-bottom:16px">
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <h2>Top Selling Items</h2>
                        </div>
                        <div class="pos-li-wrap">
                            @if($topSellingItems->isNotEmpty())
                                <table class="pos-tbl">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="num">Qty Sold</th>
                                            <th class="num">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topSellingItems as $item)
                                            <tr>
                                                <td>{{ $item->product?->name ?? '—' }}</td>
                                                <td class="num">{{ format_number($item->qty, 0) }}</td>
                                                <td class="num pos-bold">{{ format_money($item->total_amount) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="pos-empty">
                                    <h3>No sales today</h3>
                                    <p>Top selling items will appear here once sales are posted.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pos-card">
                        <div class="pos-card-h">
                            <h2>Sales by Cashier</h2>
                        </div>
                        <div>
                            @if($salesByCashier->isNotEmpty())
                                @foreach($salesByCashier as $cashier)
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--pos-line)">
                                        <span style="font-size:13px;font-weight:600;color:var(--pos-ink)">{{ $cashier->creator?->name ?? '—' }}</span>
                                        <span style="font-size:13px;font-weight:800;color:var(--pos-sec);font-variant-numeric:tabular-nums">{{ format_money($cashier->total_amount) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="pos-empty">
                                    <h3>No sales today</h3>
                                    <p>Cashier sales will appear here once transactions are posted.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <h2>Recent Transactions</h2>
                        <div class="pos-right">
                            <a href="{{ route('pos.receipts.index') }}" style="font-size:12px;font-weight:700;color:var(--pos-sec);text-decoration:none">View all →</a>
                        </div>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Time</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th class="num">Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $sale)
                                    <tr>
                                        <td class="pos-mono">{{ $sale->sale_number }}</td>
                                        <td>{{ $sale->created_at?->format('H:i') ?? '—' }}</td>
                                        <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                        <td class="pos-em">{{ $sale->terminal?->identifier ?? '—' }}</td>
                                        <td class="num pos-bold">{{ format_money($sale->total) }}</td>
                                        <td><span class="pos-tchip">{{ $sale->payments->first()?->method ?? '—' }}</span></td>
                                        <td>
                                            @if($sale->status === 'posted')
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Completed</span>
                                            @elseif($sale->status === 'draft')
                                                <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Draft</span>
                                            @else
                                                <span class="pos-badge pos-badge-rev"><span class="pos-bdot"></span>Voided</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="pos-dash" style="text-align:center;padding:32px 12px">No transactions yet today.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="pos-grid2">
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <h2>Low Stock Alerts</h2>
                            @if($lowStockCount > 0)
                                <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>{{ $lowStockCount }} item{{ $lowStockCount === 1 ? '' : 's' }} below reorder</span>
                            @endif
                        </div>
                        <div class="pos-li-wrap">
                            @if($lowStockCount > 0)
                                <table class="pos-tbl">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th class="num">On Hand</th>
                                            <th class="num">Reorder Point</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($lowStockProducts as $product)
                                            <tr>
                                                <td>{{ $product->name }}</td>
                                                <td class="pos-mono">{{ $product->sku ?? '—' }}</td>
                                                <td class="num" style="color:var(--pos-red);font-weight:700">{{ format_number($product->pos_stock_on_hand ?? $product->stock_qty ?? 0) }}</td>
                                                <td class="num">{{ format_number($product->effective_reorder_point ?? 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="pos-empty">
                                    <h3>Stock OK</h3>
                                    <p>All stocked items are above their reorder points.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pos-card">
                        <div class="pos-card-h">
                            <h2>Live Registers</h2>
                            <div class="pos-right">
                                <a href="{{ route('pos.terminals.index') }}" style="font-size:12px;font-weight:700;color:var(--pos-sec);text-decoration:none">Manage →</a>
                            </div>
                        </div>
                        <div>
                            @forelse($terminals as $terminal)
                                @php $session = $openSessions->get($terminal->id); @endphp
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 16px;border-bottom:1px solid var(--pos-line)">
                                    <div>
                                        <div style="font-size:13px;font-weight:700;color:var(--pos-ink)">{{ $terminal->identifier }}</div>
                                        <div style="font-size:11.5px;color:var(--pos-muted)">
                                            @if($session)
                                                Cashier: {{ $session->user?->name ?? '—' }} · {{ format_money($session->expected_cash ?? $session->opening_float) }}
                                            @else
                                                No open shift
                                            @endif
                                        </div>
                                    </div>
                                    @if($session)
                                        <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Open</span>
                                    @else
                                        <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Idle</span>
                                    @endif
                                </div>
                            @empty
                                <div class="pos-empty">
                                    <h3>No terminals</h3>
                                    <p>No terminals configured.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.sales.checkout') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        New Sale
                    </a>
                    <a href="{{ route('pos.terminals.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        Registers
                    </a>
                    <a href="{{ route('pos.reports.overview') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 010-4h14v4"/><path d="M3 5v14a2 2 0 002 2h16v-5"/><path d="M18 12a2 2 0 000 4h4v-4h-4z"/></svg>
                        Reports
                    </a>
                    <a href="{{ route('pos.receipts.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                        Receipts
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
