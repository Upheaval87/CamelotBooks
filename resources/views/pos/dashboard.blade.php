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
    @endphp

    <div class="pos-wrap">
        <div class="pos-head">
            <div>
                <h1>POS Centre</h1>
                <p class="pos-sub">Point of sale overview · {{ now()->format('D, d M Y') }}</p>
            </div>
            <div class="pos-grow"></div>

            @if($branches->count() > 1)
                <form method="GET" action="{{ route('pos.dashboard') }}">
                    <select name="branch_id"
                        onchange="this.form.submit()"
                        class="h-[38px] rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[12.5px] font-bold text-[color:var(--ink)] focus:border-[color:var(--sec)] focus:outline-none">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId === (int) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if($sessionTerminal)
                @if($hasOpenShiftHere)
                    <span class="pos-badge pos-badge-active"><span class="pos-badge-dot green"></span>{{ $sessionTerminal->identifier }} · Shift Open</span>
                @else
                    <span class="pos-badge pos-badge-pending"><span class="pos-badge-dot amber"></span>{{ $sessionTerminal->identifier }} · No Open Shift</span>
                @endif
            @elseif($terminals->isNotEmpty())
                <span class="pos-badge pos-badge-muted"><span class="pos-badge-dot"></span>{{ $terminals->count() }} {{ Str::plural('terminal', $terminals->count()) }}</span>
            @endif

            <div class="pos-actions">
                <a href="{{ route('pos.sales.checkout') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    New Sale
                </a>
                <a href="{{ route('pos.receipts.index') }}" class="pos-btn pos-btn-sec">View Receipts</a>
                <form method="POST" action="{{ route('pos.cashier.logout') }}"
                    onsubmit="return fbConfirmSubmit(event, 'End your cashier session?', { type: 'danger' })">
                    @csrf
                    <button type="submit" class="pos-btn pos-btn-danger">End Shift</button>
                </form>
            </div>
        </div>

        @if(!$hasOpenShiftHere && $sessionTerminal)
            <x-feedback.alert variant="warning" class="mb-5">
                No till session is open on <strong>{{ $sessionTerminal->identifier }}</strong>. Open a till session before making sales.
            </x-feedback.alert>
        @endif

        <div class="pos-stats">
            <div class="pos-stat pos-kpi-card">
                <div class="lbl">Today's Revenue</div>
                <div class="val">{{ format_money($todayRevenue) }}</div>
                <div class="sub">posted sales</div>
            </div>
            <div class="pos-stat pos-kpi-card">
                <div class="lbl">Transactions</div>
                <div class="val">{{ format_number($todaySales, 0) }}</div>
                <div class="sub">today</div>
            </div>
            <div class="pos-stat pos-kpi-card">
                <div class="lbl">Average Sale</div>
                <div class="val">{{ format_money($averageSale) }}</div>
                <div class="sub">per transaction</div>
            </div>
            <div class="pos-stat pos-kpi-card">
                <div class="lbl">Returns</div>
                <div class="val neg" style="color:var(--red-2)">-{{ format_money($todayReturns) }}</div>
                <div class="sub">refunded today</div>
            </div>
            <div class="pos-stat pos-kpi-card">
                <div class="lbl">Items Sold</div>
                <div class="val">{{ format_number($itemsSold, 0) }}</div>
                <div class="sub">units today</div>
            </div>
        </div>

        <div class="pos-quick">
            <a href="{{ route('pos.sales.checkout') }}">
                <span class="ic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#128F8E" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </span>
                New Sale
            </a>
            <a href="{{ route('pos.receipts.index') }}">
                <span class="ic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#128F8E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                </span>
                View Receipts
            </a>
            <form method="POST" action="{{ route('pos.cashier.logout') }}"
                onsubmit="return fbConfirmSubmit(event, 'End your cashier session?', { type: 'danger' })">
                @csrf
                <button type="submit" class="flex w-full items-center gap-[10px] rounded-xl border border-[color:var(--border)] bg-white px-5 py-4 text-left text-[13px] font-bold text-[color:var(--ink)] transition hover:border-[rgba(185,28,28,.45)] hover:text-[#b91c1c]">
                    <span class="ic flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-[10px]" style="background:rgba(185,28,28,.08)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </span>
                    End Shift
                </button>
            </form>
        </div>

        <div class="pos-grid" style="margin-top:20px">
            <div class="pos-card">
                <div class="pos-card-h">
                    <h2>Recent Transactions</h2>
                    <a href="{{ route('pos.receipts.index') }}">View all</a>
                </div>
                <div class="pos-tbl-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th class="r">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td class="mono">{{ $sale->sale_number }}</td>
                                    <td>{{ $sale->created_at?->format('H:i') ?? '—' }}</td>
                                    <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                                    <td class="r">{{ format_money($sale->total) }}</td>
                                    <td>
                                        @if($sale->status === 'posted')
                                            <span class="pos-badge pos-badge-active"><span class="pos-badge-dot green"></span>Completed</span>
                                        @elseif($sale->status === 'draft')
                                            <span class="pos-badge pos-badge-pending"><span class="pos-badge-dot amber"></span>Draft</span>
                                        @else
                                            <span class="pos-badge pos-badge-danger"><span class="pos-badge-dot red"></span>Voided</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="pos-tbl-empty">No transactions yet today.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pos-card">
                <div class="pos-card-h">
                    <h2>Live Registers</h2>
                    <a href="{{ route('pos.terminals.index') }}">Manage terminals</a>
                </div>
                <div>
                    @forelse($terminals as $terminal)
                        @php $session = $openSessions->get($terminal->id); @endphp
                        <div class="pos-register-card" style="border-bottom:1px solid var(--line)">
                            <div class="till-id">{{ $terminal->identifier }}</div>
                            <div class="till-info">
                                @if($session)
                                    Cashier: <strong style="color:var(--ink)">{{ $session->user?->name ?? '—' }}</strong>
                                    · Till balance: <strong style="color:var(--ink)">{{ format_money($session->expected_cash ?? $session->opening_float) }}</strong>
                                @else
                                    No open shift
                                @endif
                            </div>
                            @if($session)
                                <span class="pos-badge pos-badge-active"><span class="pos-badge-dot green"></span>Open</span>
                            @else
                                <span class="pos-badge pos-badge-muted"><span class="pos-badge-dot"></span>Idle</span>
                            @endif
                        </div>
                    @empty
                        <div class="pos-tbl-empty">No terminals configured.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="pos-card">
            <div class="pos-card-h">
                <h2>Low Stock Alerts</h2>
                @if($lowStockCount > 0)
                    <span class="pos-low-stock">{{ $lowStockCount }} item{{ $lowStockCount === 1 ? '' : 's' }} below reorder point</span>
                @endif
            </div>
            @if($lowStockCount > 0)
                <div class="pos-tbl-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="r">On Hand</th>
                                <th class="r">Reorder Point</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="mono">{{ $product->sku ?? '—' }}</td>
                                    <td class="r pos-low-stock">{{ format_number($product->pos_stock_on_hand ?? $product->stock_qty ?? 0) }}</td>
                                    <td class="r">{{ format_number($product->effective_reorder_point ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="pos-tbl-empty">All stocked items are above their reorder points.</div>
            @endif
        </div>
    </div>
</x-app-layout>
