@php
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $typeFilter = request('type');
    $statusFilter = request('status');
@endphp
<x-app-layout>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="suite">

                {{-- page-head --}}
                <div class="page-head">
                    <div>
                        <h1>Products</h1>
                        <div class="sub">Manage your product catalogue and inventory.</div>
                    </div>
                    <div class="tbtns">
                        <a href="{{ route('accounting.products.create') }}" class="btn cta">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"/></svg>
                            Create Product
                        </a>
                    </div>
                </div>

                <div class="shell">
                    <div>

                        {{-- Portfolio --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                                <h2>Portfolio</h2>
                                <span class="rule"></span>
                            </div>

                            <div class="sgrid">
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                                    <div>
                                        <div class="l">Products</div>
                                        <div class="v">{{ number_format($stats['total']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Active</div>
                                        <div class="v">{{ number_format($stats['active']) }}</div>
                                    </div>
                                </div>
                                <div class="sbox ic">
                                    <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                    <div>
                                        <div class="l">Low Stock</div>
                                        <div class="v {{ $stats['low_stock'] > 0 ? 'red' : 'mint' }}">{{ number_format($stats['low_stock']) }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- controls: search + filters --}}
                            <form method="GET" action="{{ route('accounting.products.index') }}" id="prod-list-form">
                                <div class="controls">
                                    <div class="search">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                                        <input type="text" name="search" class="input" placeholder="Name or SKU..." value="{{ request('search') }}" />
                                    </div>
                                    <select name="type" class="input" style="width:11rem">
                                        <option value="">All Types</option>
                                        <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                                        <option value="inventory" {{ request('type') === 'inventory' ? 'selected' : '' }}>Inventory</option>
                                        <option value="non_inventory" {{ request('type') === 'non_inventory' ? 'selected' : '' }}>Non-Inventory</option>
                                    </select>
                                    <select name="status" class="input" style="width:9rem">
                                        <option value="">All Statuses</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    <button type="submit" class="btn ghost">Filter</button>
                                    @if(request()->hasAny('search', 'type', 'status'))
                                        <a href="{{ route('accounting.products.index') }}" class="btn ghost">Clear</a>
                                    @endif
                                    <span class="chip-t">{{ $products->total() }} products</span>
                                </div>
                            </form>
                        </section>

                        {{-- product list --}}
                        <section class="card" style="padding:20px 24px; margin-top:16px">
                            <div class="li-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:24%">Name</th>
                                            <th style="width:12%">SKU</th>
                                            <th style="width:12%">Type</th>
                                            <th class="num" style="width:13%">Sales Price ({{ $cs }})</th>
                                            <th class="num" style="width:13%">Purchase Price ({{ $cs }})</th>
                                            <th style="width:16%">Income Account</th>
                                            <th style="width:10%">Status</th>
                                            <th style="width:8%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($products as $product)
                                        <tr>
                                            <td>
                                                <a href="{{ route('accounting.products.show', $product) }}" style="font-weight:600;color:var(--deep-3,#0A2E32);text-decoration:none">{{ $product->name }}</a>
                                            </td>
                                            <td class="mono">{{ $product->sku ?? '—' }}</td>
                                            <td><span class="tchip">{{ str_replace('_', ' ', ucfirst($product->type)) }}</span></td>
                                            <td class="numr">{{ format_number($product->sales_price ?? 0) }}</td>
                                            <td class="numr">{{ format_number($product->purchase_price ?? 0) }}</td>
                                            <td class="em">{{ $product->incomeAccount?->name ?? '—' }}</td>
                                            <td>
                                                @if($product->is_active)
                                                    <span class="badge b-act"><span class="bdot"></span>Active</span>
                                                @else
                                                    <span class="badge b-inact"><span class="bdot"></span>Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="row-act">
                                                    <a href="{{ route('accounting.products.show', $product) }}" class="ibtn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                                    <a href="{{ route('accounting.products.edit', $product) }}" class="ibtn" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>
                                                    <form method="POST" action="{{ route('accounting.products.toggle', $product) }}" class="inline" @if($product->is_active) onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to deactivate this product?') }}', { type: 'danger' })" @endif>
                                                        @csrf @method('PATCH')
                                                        <button type="submit" class="ibtn {{ $product->is_active ? 'del' : '' }}" title="{{ $product->is_active ? 'Deactivate' : 'Activate' }}">
                                                            @if($product->is_active)
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16m-10 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0l-1 12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L6 7"/></svg>
                                                            @else
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                                                            @endif
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8"><div class="empty">No products found.</div></td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                @if($products->hasPages())
                                    @php
                                        $paginator = $products->appends(request()->query());
                                        $last = $paginator->lastPage();
                                        $cur = $paginator->currentPage();
                                        $winStart = max(1, $cur - 2);
                                        $winEnd = min($last, $cur + 2);
                                        $firstItem = $paginator->firstItem() ?: 0;
                                        $lastItem = $paginator->lastItem() ?: 0;
                                    @endphp
                                    <div class="pagi">
                                        <span class="t">Showing {{ $firstItem }}–{{ $lastItem }} of {{ $paginator->total() }} products</span>
                                        <span class="pg">
                                            @if($paginator->onFirstPage())
                                                <span class="pgbtn" aria-disabled="true" aria-label="Previous">‹</span>
                                            @else
                                                <a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous">‹</a>
                                            @endif

                                            @if($winStart > 1)
                                                <a href="{{ $paginator->url(1) }}">1</a>
                                                @if($winStart > 2)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                            @endif

                                            @for($page = $winStart; $page <= $winEnd; $page++)
                                                @if($page === $cur)
                                                    <span class="pgbtn cur" aria-current="page">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
                                                @endif
                                            @endfor

                                            @if($winEnd < $last)
                                                @if($winEnd < $last - 1)<span class="pgbtn dots" aria-hidden="true">…</span>@endif
                                                <a href="{{ $paginator->url($last) }}">{{ $last }}</a>
                                            @endif

                                            @if($paginator->hasMorePages())
                                                <a href="{{ $paginator->nextPageUrl() }}" aria-label="Next">›</a>
                                            @else
                                                <span class="pgbtn" aria-disabled="true" aria-label="Next">›</span>
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </section>
                    </div>

                    {{-- right rail --}}
                    <aside class="railsum">
                        <div class="card">
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                    <h2>Views</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.products.index') }}" class="vitem {{ !$typeFilter && !$statusFilter ? 'on' : '' }}" {{ !$typeFilter && !$statusFilter ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
                                        All Products
                                    </a>
                                    <a href="{{ route('accounting.products.index', ['type' => 'service']) }}" class="vitem {{ $typeFilter === 'service' ? 'on' : '' }}" {{ $typeFilter === 'service' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg></span>
                                        Services
                                    </a>
                                    <a href="{{ route('accounting.products.index', ['type' => 'inventory']) }}" class="vitem {{ $typeFilter === 'inventory' ? 'on' : '' }}" {{ $typeFilter === 'inventory' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg></span>
                                        Inventory
                                    </a>
                                    <a href="{{ route('accounting.products.index', ['type' => 'non_inventory']) }}" class="vitem {{ $typeFilter === 'non_inventory' ? 'on' : '' }}" {{ $typeFilter === 'non_inventory' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-2 0-4 .8-4 2s2 2 4 2 4-.8 4-2-2-2-4-2zm0 0V4m-4 6c0 1.2 1.8 2 4 2s4-.8 4-2m-8 0v6c0 1.2 1.8 2 4 2s4-.8 4-2v-6"/></svg></span>
                                        Non-Inventory
                                    </a>
                                    <a href="{{ route('accounting.products.index', ['status' => 'active']) }}" class="vitem {{ $statusFilter === 'active' ? 'on' : '' }}" {{ $statusFilter === 'active' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Active
                                    </a>
                                    <a href="{{ route('accounting.products.index', ['status' => 'inactive']) }}" class="vitem {{ $statusFilter === 'inactive' ? 'on' : '' }}" {{ $statusFilter === 'inactive' ? 'aria-current="page"' : '' }}>
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Inactive
                                    </a>
                                </div>
                            </div>
                            <div class="rail-sec">
                                <div class="sec-head">
                                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                    <h2>Reports</h2>
                                    <span class="rule"></span>
                                </div>
                                <div class="vlist">
                                    <a href="{{ route('accounting.inventory-valuation.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                                        Inventory Valuation
                                    </a>
                                    <a href="{{ route('accounting.low-stock.index') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg></span>
                                        Low Stock Report
                                    </a>
                                    <a href="{{ route('accounting.reports.stock-movement') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg></span>
                                        Stock Movement
                                    </a>
                                    <a href="{{ route('accounting.reports.item-profitability') }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 3.055A9.001 9.001 0 1 0 20.945 13H11V3.055zM20.488 9H15V3.512A9.025 9.025 0 0 1 20.488 9z"/></svg></span>
                                        Item Profitability
                                    </a>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
