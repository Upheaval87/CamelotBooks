@extends('layouts.pos-mobile', ['title' => 'Products'])

@section('content')
<div class="pos-m-page" style="padding-bottom: 5rem;">

    {{-- §12 — Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Products</div>
        <div class="pos-m-greeting-sub">
            {{ $totalCount }} items
            @if($lowStockCount > 0) · {{ $lowStockCount }} low stock @endif
            · {{ auth()->user()->name ?? 'Cashier' }}
        </div>
    </div>

    {{-- §12 — Search --}}
    <div class="pos-m-search-bar">
        <div class="pos-m-search-field">
            <svg class="pos-m-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <form method="GET" action="{{ route('pos.m.products') }}" class="pos-m-search-form" style="display:flex;flex:1;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Search products..."
                    class="pos-m-search-input" inputmode="search" autocomplete="off">
            </form>
        </div>
    </div>

    {{-- §12 — Category chips --}}
    <div class="pos-m-chip-row">
        <a href="{{ route('pos.m.products') }}" class="pos-m-chip {{ !$category ? 'pos-m-chip--active' : '' }}">All</a>
        <a href="{{ route('pos.m.products', ['q' => $q, 'category' => 'low']) }}" class="pos-m-chip">Low stock</a>
        @foreach($categories as $cat)
            <a href="{{ route('pos.m.products', ['q' => $q, 'category' => $cat->id]) }}"
                class="pos-m-chip {{ $category == $cat->id ? 'pos-m-chip--active' : '' }}">
                {{ $cat->name }}
            </a>
        @endforeach
    </div>

    {{-- §12 — Product rows --}}
    @if($products->isEmpty())
        <div class="pos-m-empty">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <p>No products found.</p>
        </div>
    @else
        <div class="pos-m-product-list">
            @foreach($products as $product)
                <div class="pos-m-product-row">
                    <div class="pos-m-product-info">
                        <div class="pos-m-product-name">{{ $product->name }}</div>
                        <div class="pos-m-product-meta">
                            K {{ number_format($product->sales_price, 2) }}
                            @if($product->sku) · {{ $product->sku }} @endif
                        </div>
                    </div>
                    <div class="pos-m-product-stock">
                        @if($product->current_stock === null)
                            <span class="pos-m-stock-label">—</span>
                        @elseif($product->current_stock <= 0)
                            <span class="pos-m-stock-label pos-m-stock-label--out">Out</span>
                        @elseif($product->current_stock <= 10)
                            <span class="pos-m-stock-label pos-m-stock-label--low">{{ $product->current_stock }} left</span>
                        @else
                            <span class="pos-m-stock-label">{{ $product->current_stock }} in stock</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

@include('pos.mobile._bottom-nav', ['active' => 'products'])
@endsection
