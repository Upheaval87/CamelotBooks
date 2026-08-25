@extends('layouts.pos-mobile', ['title' => 'Sell'])

@section('content')
<div class="pos-m-page" x-data="posMobileSell({
    products: {!! \Illuminate\Support\Js::from($products) !!},
    categories: {!! \Illuminate\Support\Js::from($categories) !!},
    paymentMethods: {!! \Illuminate\Support\Js::from($paymentMethods) !!},
    customers: {!! \Illuminate\Support\Js::from($customers) !!},
    bankAccounts: {!! \Illuminate\Support\Js::from($bankAccounts) !!},
    mobileProviders: {!! \Illuminate\Support\Js::from($mobileProviders) !!},
    walkInCustomerId: '{{ $walkInCustomer?->id ?? '' }}',
    terminalId: {{ session('pos_terminal_id') ?? 0 }},
    storeUrl: '{{ route('pos.sales.store') }}',
    receiptUrl: '{{ route('pos.m.receipt', '__ID__') }}'
})">

    {{-- Topbar --}}
    <div class="pos-m-topbar">
        <span class="pos-m-topbar-mark">C</span>
        <div class="pos-m-topbar-brand">
            <div class="pos-m-topbar-name">Sell</div>
            <div class="pos-m-topbar-sub">{{ $terminal->identifier ?? 'TILL-01' }} · Shift open</div>
        </div>
        <div class="pos-m-topbar-right">
            <span class="pos-m-topbar-av">{{ strtoupper(substr($cashierName ?? 'U', 0, 2)) }}</span>
        </div>
    </div>

    {{-- Scrollable area (products + cart) --}}
    <div style="flex:1;overflow-y:auto;padding:0 1.25rem 7rem;scrollbar-width:none;position:relative">

        {{-- Search row --}}
        <div class="pos-m-searchrow">
            <div class="pos-m-search">
                <span class="pos-m-search-ic">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/></svg>
                </span>
                <input type="text" placeholder="Search or scan product…" x-model="searchQuery" @input="filterProducts()">
            </div>
            <input type="file" accept="image/*" capture="environment" id="pos-m-barcode-input"
                   class="pos-m-scan-hidden" @change="onBarcodeScan($event)">
            <button type="button" class="pos-m-scanbtn" @click="startScan()">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </button>
        </div>

        {{-- Category tabs --}}
        <div class="pos-m-tabs">
            <button type="button" class="pos-m-tab {{ ($activeCategory ?? '') === '' ? 'pos-m-tab--on' : '' }}"
                    @click="activeCategory = ''; filterProducts()">All</button>
            @foreach($categories as $cat)
                <button type="button" class="pos-m-tab"
                        :class="activeCategory == '{{ $cat->id }}' ? 'pos-m-tab--on' : ''"
                        @click="activeCategory = '{{ $cat->id }}'; filterProducts()">{{ $cat->name }}</button>
            @endforeach
        </div>

        {{-- Product grid --}}
        <div class="pos-m-pgrid">
            <template x-for="product in displayedProducts" :key="product.id">
                <div class="pos-m-card pos-m-pcard" :class="product.tracked_as_inventory && product.current_stock <= 0 ? 'pos-m-pcard--oos' : ''">
                    <div class="pos-m-pcard-cat" x-text="product.category_name || ''"></div>
                    <div class="pos-m-pcard-name" x-text="product.name"></div>
                    <div class="pos-m-pcard-row">
                        <div>
                            <div class="pos-m-pcard-price" x-text="'K ' + formatNum(product.sales_price)"></div>
                            <div class="pos-m-pcard-stock" x-show="product.current_stock !== null"
                                 x-text="product.current_stock <= 0 ? 'Out of stock' : product.current_stock + ' in stock'"
                                 :style="product.current_stock <= 0 ? 'color:#C2453F' : ''"></div>
                        </div>
                        <button type="button" class="pos-m-addbtn" @click="addToCart(product)"
                                :disabled="product.tracked_as_inventory && product.current_stock <= 0">+</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="pos-m-empty" x-show="displayedProducts.length === 0" style="display:none" x-cloak>
            <p>No products found.</p>
        </div>

        {{-- Cart bar (floating) --}}
        <div class="pos-m-cartbar" x-show="cart.length > 0" x-cloak
             @click="showCartDrawer = true" style="cursor:pointer">
            <span x-text="cartCount + ' items'"></span>
            <span x-text="'K ' + formatNum(cartTotal) + ' →'"></span>
        </div>
    </div>

    {{-- Cart drawer --}}
    <div class="pos-m-sheet-ov" x-show="showCartDrawer" x-cloak @click.self="showCartDrawer = false">
        <div class="pos-m-sheet">
            <div class="pos-m-sheet-h">Cart (<span x-text="cartCount"></span>)</div>
            <template x-for="(line, idx) in cart" :key="line.product_id">
                <div style="display:flex;align-items:center;gap:.625rem;padding:.8125rem 0;border-bottom:1px solid #EEF3F1">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:.8125rem;font-weight:600" x-text="line.product_name"></div>
                        <div style="font-size:.625rem;color:#9AAEAE" x-text="'K ' + formatNum(line.unit_price) + ' each'"></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.375rem">
                        <button type="button" class="pos-m-qty-btn" @click="decrementCart(idx)" style="width:27px;height:27px;border-radius:8px;border:1px solid #E4EAE8;background:#fff;font-weight:700;color:#0B3437;display:grid;place-items:center;cursor:pointer">−</button>
                        <span style="font-size:.78125rem;font-weight:700;min-width:16px;text-align:center" x-text="line.quantity"></span>
                        <button type="button" class="pos-m-qty-btn" @click="incrementCart(idx)" style="width:27px;height:27px;border-radius:8px;border:1px solid #E4EAE8;background:#fff;font-weight:700;color:#0B3437;display:grid;place-items:center;cursor:pointer">+</button>
                    </div>
                    <div style="font-size:.8125rem;font-weight:700;width:70px;text-align:right;font-variant-numeric:tabular-nums" x-text="'K ' + formatNum(line.line_total)"></div>
                </div>
            </template>
            <div style="display:flex;gap:.625rem;margin-top:1rem">
                <button type="button" class="pos-m-btn pos-m-btn--ghost" style="flex:1" @click="showCartDrawer = false">Keep Selling</button>
                <button type="button" class="pos-m-btn pos-m-btn--solid" style="flex:1" @click="showCartDrawer = false; page = 1; window.location.href = '{{ route('pos.m.checkout') }}'">Checkout →</button>
            </div>
        </div>
    </div>

    {{-- Error toast --}}
    <div class="pos-m-toast pos-m-toast--error" x-show="errorMessage" x-text="errorMessage" x-cloak
         x-init="$watch('errorMessage', v => { if(v) setTimeout(() => errorMessage = '', 3000) })"></div>

    @include('pos.mobile._bottom-nav', ['active' => 'sell'])
</div>
@endsection
