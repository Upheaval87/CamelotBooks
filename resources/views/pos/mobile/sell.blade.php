@extends('layouts.pos-mobile', ['title' => 'POS Sell'])

@section('content')
<div class="pos-m-page pos-m-sell-page" x-data="posMobileSell({
    products: {!! Js::from($products) !!},
    categories: {!! Js::from($categories) !!},
    paymentMethods: {!! Js::from($paymentMethods) !!},
    customers: {!! Js::from($customers) !!},
    bankAccounts: {!! Js::from($bankAccounts) !!},
    mobileProviders: {!! Js::from($mobileProviders) !!},
    walkInCustomerId: {{ $walkInCustomer?->id ?? 'null' }},
    terminalId: {{ session('pos_terminal_id') ?? 0 }},
    storeUrl: '{{ route("pos.sales.store") }}',
    receiptUrl: '{{ route("pos.m.receipt", "__ID__") }}',
})" x-cloak>

    {{-- §7.1 — Search + Barcode Scan --}}
    <div class="pos-m-search-bar">
        <div class="pos-m-search-field">
            <svg class="pos-m-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" x-model="searchQuery" @input.debounce.300ms="filterProducts()" placeholder="Search or scan barcode..."
                class="pos-m-search-input" inputmode="search" autocomplete="off">
            <button x-show="searchQuery.length > 0" @click="searchQuery = ''; filterProducts()" class="pos-m-search-clear">&times;</button>
        </div>
        <button @click="startScan()" class="pos-m-scan-btn" title="Scan barcode">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        </button>
    </div>

    {{-- §7.2 — Category Tabs --}}
    <div class="pos-m-cat-tabs">
        <button class="pos-m-cat-tab" :class="activeCategory === '' ? 'pos-m-cat-tab--active' : ''"
            @click="activeCategory = ''; filterProducts()">All</button>
        @foreach($categories as $cat)
            <button class="pos-m-cat-tab" :class="activeCategory === {{ $cat->id }} ? 'pos-m-cat-tab--active' : ''"
                @click="activeCategory = {{ $cat->id }}; filterProducts()">{{ $cat->name }}</button>
        @endforeach
    </div>

    {{-- §7.3 — Swipeable pages: Cart (page 0) / Payment (page 1) --}}
    <div class="pos-m-swipe-wrap" :style="'transform: translateX(' + (page === 0 ? '0' : '-100') + '%)'">

        {{-- PAGE 0: CART --}}
        <div class="pos-m-swipe-page">
            {{-- Product Grid --}}
            <div class="pos-m-prod-grid">
                <template x-for="p in displayedProducts" :key="p.id">
                    <button class="pos-m-prod-card" @click="addToCart(p)"
                        :class="p.tracked_as_inventory && p.current_stock <= 0 ? 'pos-m-prod-card--oos' : ''">
                        <div class="pos-m-prod-name" x-text="p.name"></div>
                        <div class="pos-m-prod-sku" x-text="p.sku"></div>
                        <div class="pos-m-prod-price" x-text="'K ' + formatNum(p.sales_price)"></div>
                        <div class="pos-m-prod-stock" x-show="p.tracked_as_inventory"
                            x-text="p.current_stock > 0 ? 'Stock: ' + p.current_stock : 'Out of stock'"></div>
                    </button>
                </template>
                <div x-show="displayedProducts.length === 0" class="pos-m-empty-inline">No products found.</div>
            </div>

            {{-- §7.4 — Cart Bar (sticky bottom, above nav) --}}
            <div class="pos-m-cart-bar" x-show="cart.length > 0" x-transition>
                <div class="pos-m-cart-summary">
                    <span class="pos-m-cart-count" x-text="cartCount + ' item' + (cartCount !== 1 ? 's' : '')"></span>
                    <span class="pos-m-cart-total" x-text="'K ' + formatNum(cartTotal)"></span>
                </div>
                <button class="pos-m-cart-proceed" @click="page = 1" :disabled="cart.length === 0">
                    Continue to Payment
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            {{-- Cart Items Drawer --}}
            <div class="pos-m-cart-drawer" x-show="showCartDrawer" x-transition.opacity @click.self="showCartDrawer = false">
                <div class="pos-m-cart-drawer-panel" @click.stop>
                    <div class="pos-m-cart-drawer-header">
                        <span class="pos-m-cart-drawer-title">Cart</span>
                        <button @click="showCartDrawer = false" class="pos-m-cart-drawer-close">&times;</button>
                    </div>
                    <div class="pos-m-cart-drawer-items">
                        <template x-for="(item, idx) in cart" :key="item.product_id + '-' + idx">
                            <div class="pos-m-cart-item">
                                <div class="pos-m-cart-item-info">
                                    <div class="pos-m-cart-item-name" x-text="item.product_name"></div>
                                    <div class="pos-m-cart-item-price" x-text="'K ' + formatNum(item.unit_price)"></div>
                                </div>
                                <div class="pos-m-cart-item-qty">
                                    <button @click="decrementCart(idx)" class="pos-m-qty-btn">−</button>
                                    <span x-text="item.quantity"></span>
                                    <button @click="incrementCart(idx)" class="pos-m-qty-btn">+</button>
                                </div>
                                <div class="pos-m-cart-item-line" x-text="'K ' + formatNum(item.line_total)"></div>
                                <button @click="removeFromCart(idx)" class="pos-m-cart-item-del">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- PAGE 1: PAYMENT --}}
        <div class="pos-m-swipe-page">
            <div class="pos-m-payment-wrap">

                {{-- Back button --}}
                <button class="pos-m-back-btn" @click="page = 0">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back to Cart
                </button>

                {{-- Customer selector --}}
                <div class="pos-m-field">
                    <label class="pos-m-label">Customer</label>
                    <select x-model="customerId" class="pos-m-select">
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ ($walkInCustomer?->id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Totals --}}
                <div class="pos-m-totals">
                    <div class="pos-m-totals-row">
                        <span>Subtotal</span>
                        <span x-text="'K ' + formatNum(getSubtotal())"></span>
                    </div>
                    <div class="pos-m-totals-row" x-show="getDiscount() > 0">
                        <span>Discount</span>
                        <span class="pos-m-totals-neg" x-text="'−K ' + formatNum(getDiscount())"></span>
                    </div>
                    <div class="pos-m-totals-row">
                        <span>Tax</span>
                        <span x-text="'K ' + formatNum(getTax())"></span>
                    </div>
                    <div class="pos-m-totals-row pos-m-totals-row--grand">
                        <span>Total</span>
                        <span x-text="'K ' + formatNum(getTotal())"></span>
                    </div>
                </div>

                {{-- §8.2 — Payment Method Selector --}}
                <div class="pos-m-section-title">Payment Method</div>
                <div class="pos-m-methods">
                    @foreach($paymentMethods as $pm)
                        <button class="pos-m-method-btn" :class="selectedMethod === '{{ $pm->type }}' ? 'pos-m-method-btn--active pos-m-method-btn--{{ $pm->type }}' : ''"
                            @click="selectedMethod = '{{ $pm->type }}'; selectedMethodId = '{{ $pm->id }}'; selectedMethodName = '{{ $pm->name }}'; paymentRef = ''; paymentAcctName = ''; paymentInstitution = '';"
                            :disabled="cart.length === 0">
                            @if($pm->type === 'cash')
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            @elseif($pm->type === 'card')
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            @elseif($pm->type === 'mobile_money')
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            @else
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            @endif
                            <span>{{ $pm->name }}</span>
                        </button>
                    @endforeach
                    {{-- Split Payment --}}
                    <button class="pos-m-method-btn" :class="selectedMethod === 'split' ? 'pos-m-method-btn--active pos-m-method-btn--split' : ''"
                        @click="selectedMethod = 'split'; openSplitModal()">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        Split
                    </button>
                </div>

                {{-- Cash input --}}
                <div x-show="selectedMethod === 'cash' && cart.length > 0" x-transition class="pos-m-cash-input">
                    <label class="pos-m-label">Cash Tendered</label>
                    <input type="number" x-model.number="cashTendered" min="0" step="0.01"
                        class="pos-m-input pos-m-input--large" placeholder="0.00" inputmode="decimal">
                    <div class="pos-m-change" x-show="cashTendered > 0">
                        <span>Change</span>
                        <span :class="getChange() >= 0 ? 'pos-m-change-ok' : 'pos-m-change-short'"
                            x-text="getChange() >= 0 ? 'K ' + formatNum(getChange()) : 'Short K ' + formatNum(Math.abs(getChange()))"></span>
                    </div>
                </div>

                {{-- Card / Mobile ref input --}}
                <div x-show="(selectedMethod === 'card' || selectedMethod === 'mobile_money') && cart.length > 0" x-transition class="pos-m-ref-input">
                    <label class="pos-m-label">Reference / Transaction No.</label>
                    <input type="text" x-model="paymentRef" class="pos-m-input" placeholder="e.g. TXN12345">
                    <label class="pos-m-label" style="margin-top:0.75rem">Account Name</label>
                    <input type="text" x-model="paymentAcctName" class="pos-m-input" placeholder="Account holder name">
                    <label class="pos-m-label" style="margin-top:0.75rem">Institution</label>
                    <select x-model="paymentInstitution" class="pos-m-select">
                        <option value="">Select...</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->name }}">{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Balance Due + Complete Button --}}
                <div class="pos-m-balance-footer" x-show="cart.length > 0">
                    <div class="pos-m-balance-due">
                        <span>Balance Due</span>
                        <span class="pos-m-balance-amount" x-text="'K ' + formatNum(getBalanceDue())"></span>
                    </div>
                    <button class="pos-m-complete-btn" @click="completeSale()"
                        :disabled="submitting || !canCompleteSale()">
                        <span x-show="!submitting">Complete Sale</span>
                        <span x-show="submitting">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Error display --}}
    <div x-show="errorMessage" x-cloak class="pos-m-error" x-text="errorMessage"></div>

    {{-- Split Payment Modal --}}
    <div x-show="showSplitModal" x-cloak class="pos-m-modal-overlay" @click.self="showSplitModal = false">
        <div class="pos-m-modal">
            <div class="pos-m-modal-header">
                <span>Split Payment</span>
                <button @click="showSplitModal = false" class="pos-m-modal-close">&times;</button>
            </div>
            <div class="pos-m-modal-body">
                <div class="pos-m-split-due">
                    <span>Total Due</span>
                    <span x-text="'K ' + formatNum(getTotal())"></span>
                </div>
                {{-- Cash portion --}}
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle">
                        <input type="checkbox" x-model="splitCashEnabled"> Cash
                    </label>
                    <div x-show="splitCashEnabled" class="pos-m-split-fields">
                        <input type="number" x-model.number="splitCashAlloc" min="0" step="0.01" class="pos-m-input" placeholder="Amount">
                        <input type="number" x-model.number="splitCashTendered" min="0" step="0.01" class="pos-m-input" placeholder="Tendered">
                    </div>
                </div>
                {{-- Card portion --}}
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle">
                        <input type="checkbox" x-model="splitCardEnabled"> Card
                    </label>
                    <div x-show="splitCardEnabled" class="pos-m-split-fields">
                        <input type="number" x-model.number="splitCardAmount" min="0" step="0.01" class="pos-m-input" placeholder="Amount">
                        <input type="text" x-model="splitCardRef" class="pos-m-input" placeholder="Reference">
                    </div>
                </div>
                {{-- Mobile portion --}}
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle">
                        <input type="checkbox" x-model="splitMobileEnabled"> Mobile Money
                    </label>
                    <div x-show="splitMobileEnabled" class="pos-m-split-fields">
                        <input type="number" x-model.number="splitMobileAmount" min="0" step="0.01" class="pos-m-input" placeholder="Amount">
                        <input type="text" x-model="splitMobileRef" class="pos-m-input" placeholder="Reference">
                    </div>
                </div>
                <div class="pos-m-split-remaining">
                    <span>Remaining</span>
                    <span :class="getSplitRemaining() === 0 ? 'pos-m-change-ok' : 'pos-m-change-short'"
                        x-text="'K ' + formatNum(getSplitRemaining())"></span>
                </div>
            </div>
            <div class="pos-m-modal-footer">
                <button class="pos-m-btn pos-m-btn--ghost" @click="showSplitModal = false">Cancel</button>
                <button class="pos-m-btn pos-m-btn--solid" @click="confirmSplit()" :disabled="getSplitRemaining() !== 0">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Barcode scanner hidden input --}}
    <input type="text" id="pos-m-barcode-input" class="pos-m-scan-hidden" @input="onBarcodeScan($event)">

</div>
@endsection
