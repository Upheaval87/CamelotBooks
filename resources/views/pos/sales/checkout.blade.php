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
                                @keydown.arrow-down.prevent="moveHighlight(1)"
                                @keydown.arrow-up.prevent="moveHighlight(-1)"
                                @keydown.enter.prevent="confirmHighlight()"
                                @keydown.escape="dropdownOpen = false"
                                placeholder="Type to search products... (Up/Down to navigate, Enter to select)" autocomplete="off"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            <div x-show="dropdownOpen && filteredProducts.length > 0" x-cloak
                                class="absolute z-30 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                <template x-for="(p, pi) in filteredProducts" :key="p.id">
                                    <div class="px-3 py-2 cursor-pointer flex justify-between items-center"
                                        :class="{
                                            'bg-indigo-100': pi === highlightIndex,
                                            'hover:bg-indigo-50': pi !== highlightIndex,
                                            'opacity-50 cursor-not-allowed': p.tracked_as_inventory && p.current_stock <= 0
                                        }"
                                        @click="selectProduct(p)"
                                        @mouseenter="highlightIndex = pi">
                                        <div>
                                            <span class="text-sm text-gray-900" x-text="p.sku + ' – ' + p.name"></span>
                                            <span x-show="p.tracked_as_inventory" class="ml-2 text-xs"
                                                :class="p.current_stock > 0 ? 'text-green-600' : 'text-red-600'"
                                                x-text="p.current_stock > 0 ? 'Stock: ' + p.current_stock : 'Out of stock'"></span>
                                        </div>
                                        <span class="text-sm font-semibold text-indigo-600" x-text="'$' + parseFloat(p.sales_price).toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mb-4 items-end">
                        <div class="w-[90px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                            <input type="number" x-model="addQty" min="1" step="1" value="1"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-center" />
                        </div>
                        <button type="button" @click="addLine()"
                            class="px-5 py-2 bg-indigo-600 text-white rounded-md font-semibold text-sm hover:bg-indigo-500 shadow-sm">
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
                                <span class="text-gray-500">Tax (16%)</span>
                                <span x-text="'$' + getTotals().tax.toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t pt-2">
                                <span>Total</span>
                                <span class="text-indigo-600" x-text="'$' + getTotals().total.toFixed(2)"></span>
                            </div>
                        </div>

                        {{-- Payments --}}
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Payments</span>
                                <span class="text-sm font-medium" :class="getRemaining() > 0 ? 'text-red-600' : 'text-green-600'"
                                    x-text="'Remaining: $' + getRemaining().toFixed(2)"></span>
                            </div>
                            <template x-for="(pay, pi) in payments" :key="pi">
                                <div class="flex gap-2 mb-2 items-end">
                                    <select x-model="pay.payment_method_id" class="flex-1 border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="">Method</option>
                                        @foreach($paymentMethods as $pm)
                                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" x-model.number="pay.amount" min="0.01" step="0.01"
                                        class="w-24 border-gray-300 rounded-md shadow-sm text-sm text-right" />
                                    <button type="button" @click="removePayment(pi)"
                                        class="text-red-600 hover:text-red-900 text-sm font-bold px-2" x-show="payments.length > 1">&times;</button>
                                </div>
                            </template>
                            <button type="button" @click="addPayment()" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">+ Add Payment</button>
                        </div>

                        {{-- COMPLETE SALE BUTTON --}}
                        <button type="button" @click="submitSale()"
                            :disabled="lines.length === 0 || submitting"
                            class="w-full py-4 px-6 rounded-lg font-bold text-white text-lg uppercase tracking-wider transition-all duration-200 shadow-lg disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="(lines.length > 0 && !submitting) ? 'bg-green-600 hover:bg-green-500 active:bg-green-700 shadow-green-200' : 'bg-gray-400'">
                            <span x-show="!submitting && lines.length > 0" class="flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Complete Sale
                            </span>
                            <span x-show="submitting" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Processing...
                            </span>
                            <span x-show="!submitting && lines.length === 0" class="flex items-center justify-center">
                                Add items to begin
                            </span>
                        </button>
                    </div>
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
                payments: [{ payment_method_id: '', amount: 0 }],
                submitting: false,
                dropdownOpen: false,
                highlightIndex: -1,
                _lastQuery: '',

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

                filterProducts() {
                    const q = this.searchQuery.toLowerCase().trim();
                    if (q === '') {
                        this.filteredProducts = this.products.slice(0, 20);
                    } else {
                        this.filteredProducts = this.products.filter(p =>
                            p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))
                        );
                    }
                    this.dropdownOpen = true;
                },

                selectProduct(p) {
                    if (p.tracked_as_inventory && p.current_stock <= 0) return;
                    this.selectedProductId = p.id;
                    this.selectedProductName = p.sku + ' – ' + p.name;
                    this.searchQuery = p.sku + ' – ' + p.name;
                    this.dropdownOpen = false;
                    this.highlightIndex = -1;
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

                addPayment() {
                    this.payments.push({ payment_method_id: '', amount: this.getRemaining() > 0 ? this.getRemaining().toFixed(2) : '0.00' });
                },

                removePayment(pi) {
                    this.payments.splice(pi, 1);
                },

                async submitSale() {
                    if (this.lines.length === 0) return;

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
                                payments: this.payments.filter(p => p.payment_method_id && p.amount > 0).map(p => ({
                                    payment_method_id: p.payment_method_id,
                                    amount: parseFloat(p.amount),
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
