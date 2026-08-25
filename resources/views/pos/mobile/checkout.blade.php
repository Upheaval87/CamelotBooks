@extends('layouts.pos-mobile', ['title' => 'Checkout'])

@section('content')
<div class="pos-m-page" style="display:flex;flex-direction:column;height:100dvh" x-data="posMobileSell({
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

    {{-- Checkout header --}}
    <div class="pos-m-chead">
        <button type="button" class="pos-m-xbtn" @click="page === 0 ? window.location.href = '{{ route('pos.m.sell') }}' : page = 0">✕</button>
        <span class="pos-m-chead-t">Checkout</span>
        <span class="pos-m-pdots">
            <i :class="page === 0 ? 'on' : ''"></i>
            <i :class="page === 1 ? 'on' : ''"></i>
        </span>
    </div>

    {{-- Swipeable pager --}}
    <div class="pos-m-pagerwrap">
        <div class="pos-m-pager" :style="'transform: translateX(-' + (page * 50) + '%)'">

            {{-- Page 0: Cart review --}}
            <div class="pos-m-ppage">
                {{-- Customer card --}}
                <div class="pos-m-card pos-m-cust">
                    <span class="pos-m-cust-av">🚶</span>
                    <div>
                        <div class="pos-m-cust-n">Walk-in Customer</div>
                        <div class="pos-m-cust-s">No account · pay on the spot</div>
                    </div>
                    <span class="pos-m-cust-chg">Change</span>
                </div>

                {{-- Returnables toggle --}}
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 1rem;font-size:.78125rem;font-weight:600;margin:.75rem 0">
                    <span>Customer has a Returnables Receipt</span>
                    <button type="button" @click="$el.querySelector('.pos-m-sw')?.classList.toggle('on')" style="background:none;border:none;padding:0;cursor:pointer">
                        <span class="pos-m-sw" :class="showReturnables ? 'on' : ''"
                              @click="showReturnables = !showReturnables"></span>
                    </button>
                </div>

                {{-- BRR area --}}
                <div x-show="showReturnables" x-cloak>
                    <div style="display:flex;gap:.5rem;margin:0 1rem .75rem">
                        <input type="text" class="pos-m-input" style="flex:1" value="" placeholder="Enter / scan BRR №">
                        <button type="button" class="pos-m-btn pos-m-btn--solid" style="width:84px;padding:.5rem .75rem;font-size:.8125rem;border-radius:12px">Apply</button>
                    </div>
                </div>

                {{-- Cart items --}}
                <div class="pos-m-card" style="padding:.125rem 1rem .5rem">
                    <template x-for="(line, idx) in cart" :key="line.product_id">
                        <div style="display:flex;align-items:center;gap:.625rem;padding:.8125rem 0;border-bottom:1px solid #EEF3F1">
                            <div style="flex:1;min-width:0">
                                <div style="font-size:.8125rem;font-weight:600" x-text="line.product_name"></div>
                                <div style="font-size:.625rem;color:#9AAEAE" x-text="'K ' + formatNum(line.unit_price) + (line.bottle_deposit ? ' · +K' + formatNum(line.bottle_deposit) + ' bottle' : '')"></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:.375rem">
                                <button type="button" @click="decrementCart(idx)" style="width:27px;height:27px;border-radius:8px;border:1px solid #E4EAE8;background:#fff;font-weight:700;color:#0B3437;display:grid;place-items:center;cursor:pointer">−</button>
                                <span style="font-size:.78125rem;font-weight:700;min-width:16px;text-align:center" x-text="line.quantity"></span>
                                <button type="button" @click="incrementCart(idx)" style="width:27px;height:27px;border-radius:8px;border:1px solid #E4EAE8;background:#fff;font-weight:700;color:#0B3437;display:grid;place-items:center;cursor:pointer">+</button>
                            </div>
                            <div style="font-size:.8125rem;font-weight:700;width:70px;text-align:right;font-variant-numeric:tabular-nums" x-text="'K ' + formatNum(line.line_total)"></div>
                        </div>
                    </template>
                </div>

                {{-- Totals --}}
                <div class="pos-m-card pos-m-totbox">
                    <div class="pos-m-tot">
                        <span>Subtotal</span>
                        <span x-text="'K ' + formatNum(getSubtotal())"></span>
                    </div>
                    <div class="pos-m-tot" x-show="getDiscount() > 0">
                        <span>Discount</span>
                        <span style="color:#C2453F" x-text="'−K ' + formatNum(getDiscount())"></span>
                    </div>
                    <div class="pos-m-tot">
                        <span>Tax</span>
                        <span x-text="'K ' + formatNum(getTax())"></span>
                    </div>
                    <div class="pos-m-tot big">
                        <span>Total</span>
                        <span class="v" x-text="'K ' + formatNum(getTotal())"></span>
                    </div>
                </div>

                <button type="button" class="pos-m-btn pos-m-btn--solid" style="width:100%;margin-top:.875rem;height:52px;border-radius:14px;font-size:.90625rem"
                        @click="page = 1">Continue to Payment</button>
                <button type="button" class="pos-m-addmore" id="addMore"
                        style="width:100%;height:48px;border-radius:13px;border:1.5px dashed #0E6E67;background:rgba(14,110,103,.05);font-size:.8125rem;font-weight:700;color:#0E6E67;font-family:inherit;margin-top:.625rem;cursor:pointer"
                        @click="window.location.href = '{{ route('pos.m.sell') }}'">+ Add more items · return to Sell</button>
            </div>

            {{-- Page 1: Payment --}}
            <div class="pos-m-ppage">
                {{-- Payment methods --}}
                <div class="pos-m-card pos-m-paycard" style="padding:.75rem .875rem">
                    <div style="font-size:.625rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:#5F7476;padding:.125rem .125rem .625rem">Payment method</div>
                    <div class="pos-m-payopt sel" @click="selectedMethod = 'cash'"
                         :class="selectedMethod === 'cash' ? 'sel' : ''">
                        <span class="pos-m-payopt-ic">💵</span>
                        <div><div class="pos-m-payopt-n">Cash</div><div class="pos-m-payopt-s">Drawer 1 · gives change</div></div>
                        <span class="pos-m-payopt-rd"></span>
                    </div>
                    <div class="pos-m-payopt" @click="selectedMethod = 'card'"
                         :class="selectedMethod === 'card' ? 'sel' : ''">
                        <span class="pos-m-payopt-ic">💳</span>
                        <div><div class="pos-m-payopt-n">Card</div><div class="pos-m-payopt-s">2.5% fee · ref required</div></div>
                        <span class="pos-m-payopt-rd"></span>
                    </div>
                    <div class="pos-m-payopt" @click="selectedMethod = 'mobile_money'"
                         :class="selectedMethod === 'mobile_money' ? 'sel' : ''">
                        <span class="pos-m-payopt-ic">📱</span>
                        <div><div class="pos-m-payopt-n">Mobile Money</div><div class="pos-m-payopt-s">1% fee · ref required</div></div>
                        <span class="pos-m-payopt-rd"></span>
                    </div>
                    <div class="pos-m-payopt" @click="selectedMethod = 'split'; openSplitModal()"
                         :class="selectedMethod === 'split' ? 'sel' : ''">
                        <span class="pos-m-payopt-ic">🏦</span>
                        <div><div class="pos-m-payopt-n">Credit / Split</div><div class="pos-m-payopt-s">Posts to AR · approval may apply</div></div>
                        <span class="pos-m-payopt-rd"></span>
                    </div>
                </div>

                {{-- Total due --}}
                <div class="pos-m-card pos-m-totbox" style="margin-top:.75rem">
                    <div class="pos-m-tot big" style="border-top:none;margin-top:0;padding-top:0">
                        <span>Total Due</span>
                        <span class="v" x-text="'K ' + formatNum(getTotal())"></span>
                    </div>
                </div>

                <button type="button" class="pos-m-btn pos-m-btn--solid" style="width:100%;margin-top:.75rem;height:52px;border-radius:14px"
                        :disabled="!canCompleteSale()" @click="completeSale()">Complete Sale</button>
                <button type="button" class="pos-m-btn pos-m-btn--ghost" style="width:100%;margin-top:.625rem"
                        @click="page = 0">Back to Cart</button>
            </div>
        </div>
    </div>

    {{-- Abandon sale overlay --}}
    <div class="pos-m-sheet-ov" id="confirm">
        <div class="pos-m-sheet">
            <div class="pos-m-sheet-h">Abandon this sale?</div>
            <div class="pos-m-sheet-p">The <span x-text="cartCount"></span> items in the cart will be cleared and a new sale started.</div>
            <div class="pos-m-sheet-btns">
                <button type="button" class="pos-m-sheet-keep" onclick="document.getElementById('confirm').classList.remove('show')">Keep selling</button>
                <button type="button" class="pos-m-sheet-aband" onclick="document.getElementById('confirm').classList.remove('show'); window.location.href = '{{ route('pos.m.sell') }}'">Abandon sale</button>
            </div>
        </div>
    </div>

    {{-- Split payment modal --}}
    <div class="pos-m-modal-overlay" x-show="showSplitModal" x-cloak @click.self="showSplitModal = false">
        <div class="pos-m-modal">
            <div class="pos-m-modal-header">
                <span>Split Payment</span>
                <button type="button" class="pos-m-modal-close" @click="showSplitModal = false">&times;</button>
            </div>
            <div class="pos-m-modal-body">
                <div class="pos-m-split-due">
                    <span>Total Due</span>
                    <span x-text="'K ' + formatNum(getTotal())"></span>
                </div>
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle"><input type="checkbox" x-model="splitCashEnabled"> Cash</label>
                    <div class="pos-m-split-fields" x-show="splitCashEnabled">
                        <input type="number" class="pos-m-input" placeholder="Amount" x-model.number="splitCashAlloc" min="0">
                        <input type="number" class="pos-m-input" placeholder="Tendered" x-model.number="splitCashTendered" min="0">
                    </div>
                </div>
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle"><input type="checkbox" x-model="splitCardEnabled"> Card</label>
                    <div class="pos-m-split-fields" x-show="splitCardEnabled">
                        <input type="number" class="pos-m-input" placeholder="Amount" x-model.number="splitCardAmount" min="0">
                        <input type="text" class="pos-m-input" placeholder="Reference" x-model="splitCardRef">
                    </div>
                </div>
                <div class="pos-m-split-row">
                    <label class="pos-m-split-toggle"><input type="checkbox" x-model="splitMobileEnabled"> Mobile Money</label>
                    <div class="pos-m-split-fields" x-show="splitMobileEnabled">
                        <input type="number" class="pos-m-input" placeholder="Amount" x-model.number="splitMobileAmount" min="0">
                        <input type="text" class="pos-m-input" placeholder="Reference" x-model="splitMobileRef">
                    </div>
                </div>
                <div class="pos-m-split-remaining">
                    <span>Remaining</span>
                    <span :class="getSplitRemaining() === 0 ? 'pos-m-summary-pos' : ''" x-text="'K ' + formatNum(Math.abs(getSplitRemaining()))"></span>
                </div>
            </div>
            <div class="pos-m-modal-footer">
                <button type="button" class="pos-m-btn pos-m-btn--ghost" style="flex:1" @click="showSplitModal = false">Cancel</button>
                <button type="button" class="pos-m-btn pos-m-btn--solid" style="flex:1" :disabled="getSplitRemaining() !== 0" @click="confirmSplit()">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Error toast --}}
    <div class="pos-m-toast pos-m-toast--error" x-show="errorMessage" x-text="errorMessage" x-cloak></div>

    @include('pos.mobile._bottom-nav', ['active' => 'sell'])
</div>
@endsection
