@extends('layouts.pos-mobile', ['title' => 'Products'])

@section('content')
<div class="pos-m-page" style="padding-bottom:5.5rem">

    {{-- Header --}}
    <div class="pos-m-greeting">
        <div class="pos-m-greeting-name">Products</div>
        <div class="pos-m-greeting-sub">{{ $totalCount }} items · {{ $lowStockCount }} low stock</div>
    </div>

    {{-- Search --}}
    <div class="pos-m-search-field" style="margin-bottom:.75rem">
        <form method="GET" action="{{ route('pos.m.products') }}" class="pos-m-filter-form" style="width:100%">
            <div class="pos-m-search" style="position:relative">
                <span class="pos-m-search-ic">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Search by name, SKU or barcode…">
                @if($q)
                    <a href="{{ route('pos.m.products', ['category' => $category]) }}" class="pos-m-search-clear">&times;</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Category chips --}}
    <div class="pos-m-chips">
        <a href="{{ route('pos.m.products', array_filter(['q' => $q])) }}"
           class="pos-m-chip {{ !$category ? 'pos-m-chip--on' : '' }}">All</a>
        @foreach($categories as $cat)
            <a href="{{ route('pos.m.products', array_filter(['q' => $q, 'category' => $cat->id])) }}"
               class="pos-m-chip {{ $category == $cat->id ? 'pos-m-chip--on' : '' }}">{{ $cat->name }}</a>
        @endforeach
    </div>

    {{-- Product list --}}
    <div class="pos-m-product-list">
        @forelse($products as $product)
            <div class="pos-m-product-row">
                <div style="min-width:0">
                    <div class="pos-m-product-name">{{ $product->name }}</div>
                    <div class="pos-m-product-meta">{{ $product->sku }} · K {{ number_format($product->sales_price, 2) }}</div>
                </div>
                <div class="pos-m-product-stock">
                    @if($product->current_stock !== null)
                        @if($product->current_stock <= 0)
                            <span class="pos-m-stock-label pos-m-stock-label--out">Out of Stock</span>
                        @elseif($product->current_stock <= 10)
                            <span class="pos-m-stock-label pos-m-stock-label--low">{{ $product->current_stock }} left</span>
                        @else
                            <span class="pos-m-stock-label">{{ $product->current_stock }} in stock</span>
                        @endif
                    @else
                        <span class="pos-m-stock-label" style="color:#9AAEAE">Non-stock</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="pos-m-empty">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <p>No products found.</p>
            </div>
        @endforelse
    </div>

    @include('pos.mobile._bottom-nav', ['active' => 'products'])
</div>
@endsection
