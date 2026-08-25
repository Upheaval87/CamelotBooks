<x-app-layout>
    <div class="pos">
        <div class="wrap">
            {{-- Page Head --}}
            <div class="pos-page-head">
                <div>
                    <h1>Products / Inventory Items</h1>
                    <div class="pos-sub">SKU · barcode · pricing · stock levels</div>
                </div>
                <div class="pos-actions">
                    <div class="pos-search" style="max-width:340px">
                        <svg class="pos-mag" width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <form method="GET" action="{{ route('pos.products.index') }}">
                            <input class="pos-in" name="q" placeholder="Search name / SKU / barcode…" value="{{ $q ?? '' }}">
                        </form>
                    </div>
                    <a href="{{ route('pos.products.index') }}" class="pos-btn pos-btn-ghost">Adjust Stock</a>
                                                <a href="{{ route('accounting.inventory.items.create') }}" class="pos-btn pos-btn-sec">Add Product</a>
                </div>
            </div>

            {{-- KPIs --}}
            <div class="pos-kpis" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Total Products</div>
                    <div class="pos-kpi-v">{{ $stats['total_products'] }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Active</div>
                    <div class="pos-kpi-v">{{ $stats['active_products'] }}</div>
                </div>
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Low Stock</div>
                    <div class="pos-kpi-v">{{ $stats['low_stock'] }}</div>
                    <div class="pos-kpi-n" style="color:#dff7f6">below reorder point</div>
                </div>
            </div>

            {{-- Products Table + Rail --}}
            <div class="pos-shell">
                <div class="pos-card">
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="num">Cost</th>
                                    <th class="num">Price</th>
                                    <th class="num">Stock</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td class="pos-mono pos-em">{{ $product->sku ?? '—' }}</td>
                                        <td class="pos-bold">{{ $product->name }}</td>
                                        <td class="pos-em">{{ $product->category?->name ?? '—' }}</td>
                                        <td class="num">{{ format_money($product->purchase_price) }}</td>
                                        <td class="num pos-bold">{{ format_money($product->sales_price) }}</td>
                                        <td class="num @if($product->current_stock !== null && $product->current_stock <= ($product->reorder_point ?? 0) && $product->reorder_point > 0) style="color:var(--pos-red)" @endif">
                                            @if($product->tracked_as_inventory)
                                                {{ $product->current_stock ?? '—' }}
                                            @else
                                                <span class="pos-dash">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->is_active)
                                                <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                            @else
                                                <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                            @endif
                                        </td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <a href="{{ route('accounting.inventory.items.edit', $product) }}" class="pos-ibtn" title="Edit">✎</a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="pos-empty">
                                                <h3>No products found</h3>
                                                <p>Add products to get started with POS sales.</p>
                    <a href="{{ route('accounting.inventory.items.create') }}" class="pos-btn pos-btn-sec">Add Product</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-pag">
                        <span>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products</span>
                        {{ $products->withQueryString()->links() }}
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('accounting.inventory.items.create') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Product
                        </a>
                        <a href="{{ route('accounting.invsetup.adjustments') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                            Adjust Stock
                        </a>
                        <a href="{{ route('pos.receipts.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Receipts
                        </a>
                    </div>
                    <div class="pos-rail-card">
                        <h3>Stock Summary</h3>
                        <div style="font-size:12.5px;color:var(--pos-muted);line-height:1.5">
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">{{ $stats['total_products'] }}</strong> total products</p>
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">{{ $stats['active_products'] }}</strong> active</p>
                            <p><strong style="color:var(--pos-red)">{{ $stats['low_stock'] }}</strong> low stock</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
