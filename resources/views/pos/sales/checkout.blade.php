<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('POS Checkout') }} – {{ session('pos_terminal_identifier') ?? '' }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="posCheckout()" x-effect="reactiveFilter()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div id="pos-error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative hidden">
                <span id="pos-error-text"></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Product Selection & Lines --}}
                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Add Items') }}</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <div class="relative">
                            <input type="text" x-model="searchQuery"
                                @focus="dropdownOpen = searchQuery.length > 0"
                                @keydown.down.prevent="moveHighlight(1)"
                                @keydown.up.prevent="moveHighlight(-1)"
                                @keydown.enter.prevent="confirmHighlight()"
                                @keydown.escape="dropdownOpen = false"
                                placeholder="Type to search products... (Up/Down to navigate, Enter to select)" autocomplete="off"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            <div x-show="dropdownOpen && filteredProducts.length > 0" x-cloak
                                class="absolute z-30 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                <template x-for="(p, pi) in filteredProducts" :key="p.id">
                                    <div class="px-3 py-2 cursor-pointer flex justify-between items-center"
                                        :style="parseInt(pi) === highlightIndex ? 'background-color: #4f46e5; color: white !important;' : ''"
                                        :class="p.tracked_as_inventory && p.current_stock <= 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                        @click="selectProduct(p)"
                                        @mouseenter="highlightIndex = parseInt(pi)">
                                        <div :style="parseInt(pi) === highlightIndex ? 'color: white !important;' : ''">
                                            <span class="text-sm" x-text="p.sku + ' – ' + p.name"></span>
                                            <span x-show="p.tracked_as_inventory" class="ml-2 text-xs"
                                                x-text="p.current_stock > 0 ? 'Stock: ' + p.current_stock : 'Out of stock'"></span>
                                        </div>
                                        <span class="text-sm font-semibold" x-text="'$' + parseFloat(p.sales_price).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mb-4 items-end">
                        <div class="w-[90px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                            <input type="number" x-model="addQty" min="1" step="1" value="1"
                                @keydown.enter.prevent="addLine()"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-center" />
                        </div>
                        <button type="button" @click="addLine()"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md font-semibold text-sm hover:bg-indigo-500 shadow-sm whitespace-nowrap">
                            {{ __('Add') }}
                        </button>
                        <div class="text-sm text-gray-500" x-show="selectedProductName">
                            Selected: <span class="font-semibold text-gray-800" x-text="selectedProductName"></span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Discount</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tax</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900" x-text="line.product_name"></td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            <input type="number" x-model.number="line.quantity" min="0.01" step="1"
                                                class="w-20 text-right border-gray-300 rounded-md shadow-sm text-sm" @input="recalcLine(index)" />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            <input type="number" x-model.number="line.unit_price" min="0" step="0.01"
                                                class="w-24 text-right border-gray-300 rounded-md shadow-sm text-sm" @input="recalcLine(index)" />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right">
                                            <input type="number" x-model.number="line.discount_amount" min="0" step="0.01"
                                                class="w-20 text-right border-gray-300 rounded-md shadow-sm text-sm" @input="recalcLine(index)" />
                                        </td>
                                        <td class="px-4 py-2 text-sm text-right text-gray-500" x-text="'$' + line.tax_amount.toFixed(2)"></td>
                                        <td class="px-4 py-2 text-sm text-right font-semibold" x-text="'$' + line.line_total.toFixed(2)"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="removeLine(index)" class="text-red-600 hover:text-red-900 text-sm font-medium">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="lines.length === 0">
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">No items added yet. Search and add products above.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Payment Panel (sticky) --}}
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment') }}</h3>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Customer</label>
                            <select x-model="customerId" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Walk-in Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Reference</label>
                            <input type="text" x-model="reference"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Optional" />
                        </div>

                        {{-- Totals --}}
                        <div class="border-t pt-3 mb-3 space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Subtotal</span>
                                <span x-text="'$' + getTotals().subtotal.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Discount</span>
                                <span x-text="'-$' + getTotals().discount.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax</span>
                                <span x-text="'$' + getTotals().tax.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t pt-2">
                                <span>Total Due</span>
                                <span class="text-indigo-600" x-text="'$' + getTotals().total.toFixed(2)"></span>
                            </div>
                        </div>

                        {{-- Confirmed Payments --}}
                        <div class="mb-4" x-show="payments.length > 0">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Payments</span>
                                <span class="text-sm font-medium" :class="getRemaining() > 0 ? 'text-red-600' : 'text-green-600'"
                                    x-text="'Remaining: $' + getRemaining().toFixed(2)"></span>
                            </div>
                            <template x-for="(pay, pi) in payments" :key="pi">
                                <div class="flex justify-between items-center bg-gray-50 rounded-md px-3 py-2 mb-1 text-sm">
                                    <div>
                                        <span class="font-medium" x-text="pay.method_name"></span>
                                        <span class="text-gray-500 ml-1" x-show="pay.type === 'cash'" x-text="'(Tendered: $' + parseFloat(pay.cash_tendered).toFixed(2) + ')'"></span>
                                        <span class="text-gray-500 ml-1" x-show="pay.reference_number" x-text="'Ref: ' + pay.reference_number"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold" x-text="'$' + parseFloat(pay.amount).toFixed(2)"></span>
                                        <span x-show="pay.type === 'cash' && parseFloat(pay.change) > 0" class="text-green-600 text-xs" x-text="'Change: $' + parseFloat(pay.change).toFixed(2)"></span>
                                        <button type="button" @click="removePayment(pi)" class="text-red-500 hover:text-red-700 font-bold text-xs">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add Payment Button --}}
                        <div class="mb-4" x-show="getRemaining() > 0">
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="openPaymentModal('cash')"
                                    class="py-3 px-4 bg-emerald-50 border-2 border-emerald-300 text-emerald-700 rounded-lg font-semibold text-sm hover:bg-emerald-100 transition">
                                    Cash
                                </button>
                                <button type="button" @click="openPaymentModal('card')"
                                    class="py-3 px-4 bg-blue-50 border-2 border-blue-300 text-blue-700 rounded-lg font-semibold text-sm hover:bg-blue-100 transition">
                                    Card
                                </button>
                                <button type="button" @click="openPaymentModal('mobile_money')"
                                    class="py-3 px-4 bg-purple-50 border-2 border-purple-300 text-purple-700 rounded-lg font-semibold text-sm hover:bg-purple-100 transition">
                                    Mobile Money
                                </button>
                            </div>
                        </div>

                        {{-- COMPLETE SALE BUTTON --}}
                        <div class="mt-4 pt-4 border-t">
                            <button type="button" @click="submitSale()"
                                :disabled="lines.length === 0 || submitting || getRemaining() > 0"
                                class="w-full py-4 px-6 rounded-lg font-bold text-lg uppercase tracking-wider transition duration-200"
                                :class="(lines.length > 0 && getRemaining() <= 0 && !submitting) ? 'bg-green-600 hover:bg-green-500 text-white shadow-lg cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                                <span x-show="!submitting && lines.length > 0 && getRemaining() > 0" x-text="'Balance Due: $' + getRemaining().toFixed(2)"></span>
                                <span x-show="!submitting && lines.length > 0 && getRemaining() <= 0">Complete Sale</span>
                                <span x-show="submitting">Processing...</span>
                                <span x-show="!submitting && lines.length === 0">Add items first</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Cash) ===== --}}
        <div x-show="showModal && modalType === 'cash'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
            @keydown.escape.window="closeModal()">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 z-10" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Cash Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter the cash amount received from the customer.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>Total Due</span>
                    </div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="'$' + modalDue.toFixed(2)"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cash Tendered</label>
                    <input type="number" x-model.number="modalCashTendered" x-ref="cashTenderedInput" min="0" step="0.01"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                {{-- Quick-tender buttons --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    <button type="button" @click="modalCashTendered = modalDue"
                        class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-sm font-medium">Exact</button>
                    <template x-for="denom in [10, 20, 50, 100]" :key="denom">
                        <button type="button" @click="modalCashTendered = denom"
                            class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-sm font-medium"
                            x-text="'$' + denom"></button>
                    </template>
                </div>

                {{-- Change display --}}
                <div class="rounded-lg p-3 mb-6 text-center"
                    :class="modalCashTendered >= modalDue ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'"
                    x-show="modalCashTendered > 0">
                    <template x-if="modalCashTendered >= modalDue">
                        <div>
                            <div class="text-xs text-green-600 font-medium uppercase">Change to Give</div>
                            <div class="text-3xl font-bold text-green-600" x-text="'$' + (modalCashTendered - modalDue).toFixed(2)"></div>
                        </div>
                    </template>
                    <template x-if="modalCashTendered > 0 && modalCashTendered < modalDue">
                        <div>
                            <div class="text-xs text-red-600 font-medium uppercase">Insufficient — Remaining</div>
                            <div class="text-2xl font-bold text-red-600" x-text="'$' + (modalDue - modalCashTendered).toFixed(2)"></div>
                        </div>
                    </template>
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalCashTendered || modalCashTendered <= 0"
                        class="flex-1 py-3 px-4 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-500 transition disabled:opacity-40 disabled:cursor-not-allowed">OK</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Card) ===== --}}
        <div x-show="showModal && modalType === 'card'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
            @keydown.escape.window="closeModal()">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 z-10" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Card Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter card payment details.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>Balance Due</span>
                    </div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="'$' + modalDue.toFixed(2)"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="cardAmountInput" min="0.01" step="0.01"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                        placeholder="e.g. TXN12345" />
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0"
                        class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-500 transition disabled:opacity-40 disabled:cursor-not-allowed">OK</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Mobile Money) ===== --}}
        <div x-show="showModal && modalType === 'mobile_money'" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center"
            @keydown.escape.window="closeModal()">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6 z-10" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Mobile Money Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter mobile money payment details.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                        <span>Balance Due</span>
                    </div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="'$' + modalDue.toFixed(2)"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="mobileAmountInput" min="0.01" step="0.01"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                        placeholder="e.g. MP123456" />
                </div>

                <div class="flex gap-3">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0"
                        class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-500 transition disabled:opacity-40 disabled:cursor-not-allowed">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function posCheckout() {
            return {
                products: @json($products),
                filteredProducts: [],
                searchQuery: '',
                selectedProductId: '',
                selectedProductName: '',
                addQty: 1,
                lines: [],
                customerId: '',
                reference: '',
                payments: [],
                submitting: false,
                dropdownOpen: false,
                highlightIndex: -1,
                _lastQuery: '',
                paymentTypes: @json($paymentMethods->pluck('type', 'id')),
                paymentNames: @json($paymentMethods->pluck('name', 'id')),
                showModal: false,
                modalType: '',
                modalDue: 0,
                modalCashTendered: 0,
                modalAmount: 0,
                modalReference: '',

                init() {
                    this.filteredProducts = [];
                    document.addEventListener('click', (e) => {
                        if (!this.$el.contains(e.target)) {
                            this.dropdownOpen = false;
                        }
                    });
                },

                reactiveFilter() {
                    const q = this.searchQuery.toLowerCase().trim();
                    if (q === this._lastQuery) return;
                    this._lastQuery = q;
                    this.highlightIndex = -1;
                    if (q === '') {
                        this.filteredProducts = [];
                        this.dropdownOpen = false;
                        return;
                    }
                    this.filteredProducts = this.products.filter(p =>
                        p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))
                    );
                    this.dropdownOpen = true;
                },

                moveHighlight(dir) {
                    if (!this.dropdownOpen || this.filteredProducts.length === 0) return;
                    this.highlightIndex += dir;
                    if (this.highlightIndex < 0) this.highlightIndex = this.filteredProducts.length - 1;
                    if (this.highlightIndex >= this.filteredProducts.length) this.highlightIndex = 0;
                },

                confirmHighlight() {
                    if (this.highlightIndex >= 0 && this.highlightIndex < this.filteredProducts.length) {
                        this.selectProduct(this.filteredProducts[this.highlightIndex]);
                    }
                },

                selectProduct(p) {
                    if (p.tracked_as_inventory && p.current_stock <= 0) return;
                    this.selectedProductId = p.id;
                    this.selectedProductName = p.sku + ' – ' + p.name;
                    this.searchQuery = p.sku + ' – ' + p.name;
                    this.dropdownOpen = false;
                    this.highlightIndex = -1;
                    this.$nextTick(() => {
                        const qtyInput = this.$el.querySelector('input[x-model="addQty"]');
                        if (qtyInput) qtyInput.focus();
                    });
                },

                addLine() {
                    if (!this.selectedProductId) return;
                    const product = this.products.find(p => p.id == this.selectedProductId);
                    if (!product) return;

                    const qty = parseInt(this.addQty) || 1;
                    const existing = this.lines.find(l => l.product_id == product.id);
                    if (existing) {
                        existing.quantity += qty;
                        this.recalcLine(this.lines.indexOf(existing));
                    } else {
                        const line = {
                            product_id: product.id,
                            product_name: product.sku + ' – ' + product.name,
                            quantity: qty,
                            unit_price: parseFloat(product.sales_price) || 0,
                            discount_amount: 0,
                            discount_type: null,
                            tax_rate: parseFloat(product.tax_rate) || 0,
                            is_taxable: product.is_taxable,
                            tax_amount: 0,
                            line_total: 0,
                        };
                        this.lines.push(line);
                        this.recalcLine(this.lines.length - 1);
                    }

                    this.selectedProductId = '';
                    this.selectedProductName = '';
                    this.searchQuery = '';
                    this.addQty = 1;
                    this.filteredProducts = [];
                    this.dropdownOpen = false;
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                },

                recalcLine(index) {
                    const line = this.lines[index];
                    const subtotal = line.quantity * line.unit_price;
                    const afterDiscount = subtotal - (line.discount_amount || 0);
                    line.tax_amount = line.is_taxable ? parseFloat((afterDiscount * (line.tax_rate / 100)).toFixed(2)) : 0;
                    line.line_total = parseFloat((afterDiscount + line.tax_amount).toFixed(2));
                },

                getTotals() {
                    return {
                        subtotal: parseFloat(this.lines.reduce((s, l) => s + (l.quantity * l.unit_price), 0).toFixed(2)),
                        discount: parseFloat(this.lines.reduce((s, l) => s + (l.discount_amount || 0), 0).toFixed(2)),
                        tax: parseFloat(this.lines.reduce((s, l) => s + l.tax_amount, 0).toFixed(2)),
                        total: parseFloat(this.lines.reduce((s, l) => s + l.line_total, 0).toFixed(2)),
                    };
                },

                getRemaining() {
                    const total = this.getTotals().total;
                    const paid = this.payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
                    return Math.max(0, parseFloat((total - paid).toFixed(2)));
                },

                openPaymentModal(type) {
                    this.modalType = type;
                    this.modalDue = this.getRemaining();
                    this.modalCashTendered = this.modalDue;
                    this.modalAmount = this.modalDue;
                    this.modalReference = '';
                    this.showModal = true;
                    this.$nextTick(() => {
                        if (type === 'cash' && this.$refs.cashTenderedInput) {
                            this.$refs.cashTenderedInput.focus();
                            this.$refs.cashTenderedInput.select();
                        } else if (type === 'card' && this.$refs.cardAmountInput) {
                            this.$refs.cardAmountInput.focus();
                            this.$refs.cardAmountInput.select();
                        } else if (type === 'mobile_money' && this.$refs.mobileAmountInput) {
                            this.$refs.mobileAmountInput.focus();
                            this.$refs.mobileAmountInput.select();
                        }
                    });
                },

                closeModal() {
                    this.showModal = false;
                    this.modalType = '';
                    this.modalReference = '';
                },

                confirmPaymentModal() {
                    if (this.modalType === 'cash') {
                        const tendered = parseFloat(this.modalCashTendered) || 0;
                        if (tendered <= 0) return;
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'cash') || '';
                        this.payments.push({
                            type: 'cash',
                            method_name: 'Cash',
                            payment_method_id: pmId,
                            amount: this.modalDue,
                            cash_tendered: tendered,
                            change: parseFloat((tendered - this.modalDue).toFixed(2)),
                            reference_number: '',
                        });
                    } else if (this.modalType === 'card') {
                        const amount = parseFloat(this.modalAmount) || 0;
                        if (amount <= 0) return;
                        const ref = this.modalReference.trim();
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'card') || '';
                        this.payments.push({
                            type: 'card',
                            method_name: 'Card',
                            payment_method_id: pmId,
                            amount: amount,
                            cash_tendered: 0,
                            change: 0,
                            reference_number: ref,
                        });
                    } else if (this.modalType === 'mobile_money') {
                        const amount = parseFloat(this.modalAmount) || 0;
                        if (amount <= 0) return;
                        const ref = this.modalReference.trim();
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'mobile_money') || '';
                        this.payments.push({
                            type: 'mobile_money',
                            method_name: 'Mobile Money',
                            payment_method_id: pmId,
                            amount: amount,
                            cash_tendered: 0,
                            change: 0,
                            reference_number: ref,
                        });
                    }
                    this.closeModal();
                },

                removePayment(pi) {
                    this.payments.splice(pi, 1);
                },

                async submitSale() {
                    if (this.lines.length === 0 || this.getRemaining() > 0) return;

                    this.submitting = true;
                    document.getElementById('pos-error').classList.add('hidden');

                    try {
                        const resp = await fetch('{{ route("pos.sales.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                terminal_id: {{ session('pos_terminal_id') ?? 0 }},
                                cashier_session_id: null,
                                customer_id: this.customerId || null,
                                reference: this.reference || null,
                                lines: this.lines.map(l => ({
                                    product_id: l.product_id,
                                    quantity: l.quantity,
                                    unit_price: l.unit_price,
                                    discount_amount: l.discount_amount,
                                    discount_type: l.discount_type,
                                    tax_rate: l.tax_rate,
                                })),
                                payments: this.payments.map(p => ({
                                    payment_method_id: p.payment_method_id,
                                    amount: parseFloat(p.amount),
                                    cash_tendered: parseFloat(p.cash_tendered) || null,
                                    change_given: parseFloat(p.change) || null,
                                    reference_number: p.reference_number || null,
                                })),
                            }),
                        });

                        const data = await resp.json();
                        if (data.success) {
                            window.location.href = '{{ route("pos.sales.receipt", "__ID__") }}'.replace('__ID__', data.sale_id);
                        } else {
                            const errDiv = document.getElementById('pos-error');
                            document.getElementById('pos-error-text').textContent = data.message;
                            errDiv.classList.remove('hidden');
                        }
                    } catch (e) {
                        const errDiv = document.getElementById('pos-error');
                        document.getElementById('pos-error-text').textContent = 'An unexpected error occurred.';
                        errDiv.classList.remove('hidden');
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
