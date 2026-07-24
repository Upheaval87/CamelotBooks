<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('POS Checkout') }} – {{ session('pos_terminal_identifier') ?? '' }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="posCheckout()" x-init="init()">
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
                {{-- Product Selection --}}
                <div class="lg:col-span-2 bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Add Items') }}</h3>

                    <div class="flex gap-4 mb-4">
                        <div class="flex-1">
                            <input type="text" x-model="searchQuery" @input="filterProducts()" placeholder="Search by name or SKU..."
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                        </div>
                        <div class="w-[200px]">
                            <select x-model="selectedProductId" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Select Product --</option>
                                <template x-for="p in filteredProducts" :key="p.id">
                                    <option :value="p.id" x-text="p.sku + ' – ' + p.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="w-[100px]">
                            <input type="number" x-model="addQty" min="1" step="1" value="1"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                        </div>
                        <x-primary-button type="button" @click="addLine()">{{ __('Add') }}</x-primary-button>
                    </div>

                    {{-- Sale Lines Table --}}
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
                                            <button type="button" @click="removeLine(index)" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="lines.length === 0">
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-400">No items added yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Payment Panel --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment') }}</h3>

                    <div class="mb-4">
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
                    <div class="border-t pt-4 mb-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span x-text="'$' + totals.subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Discount</span>
                            <span x-text="'-$' + totals.discount.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax</span>
                            <span x-text="'$' + totals.tax.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>Total</span>
                            <span x-text="'$' + totals.total.toFixed(2)"></span>
                        </div>
                    </div>

                    {{-- Payments --}}
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-semibold text-gray-700">Payments</span>
                            <span class="text-sm text-gray-500" x-text="'Remaining: $' + remaining.toFixed(2)"></span>
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
                                <button type="button" @click="payments.splice(pi, 1)" class="text-red-600 hover:text-red-900 text-sm">X</button>
                            </div>
                        </template>
                        <button type="button" @click="addPayment()" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Payment</button>
                    </div>

                    <button type="button" @click="submitSale()" :disabled="lines.length === 0 || submitting"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center text-lg py-3 disabled:opacity-50">
                        <span x-show="!submitting">Complete Sale</span>
                        <span x-show="submitting">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function posCheckout() {
            return {
                products: @json($products),
                filteredProducts: [],
                searchQuery: '',
                selectedProductId: '',
                addQty: 1,
                lines: [],
                customerId: '',
                reference: '',
                payments: [{ payment_method_id: '', amount: 0 }],
                submitting: false,

                init() {
                    this.filteredProducts = this.products;
                },

                filterProducts() {
                    const q = this.searchQuery.toLowerCase();
                    this.filteredProducts = this.products.filter(p =>
                        p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))
                    );
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
                        this.lines.push({
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
                        });
                        this.recalcLine(this.lines.length - 1);
                    }

                    this.selectedProductId = '';
                    this.addQty = 1;
                    this.recalcTotals();
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                    this.recalcTotals();
                },

                recalcLine(index) {
                    const line = this.lines[index];
                    const subtotal = line.quantity * line.unit_price;
                    const afterDiscount = subtotal - (line.discount_amount || 0);
                    line.tax_amount = line.is_taxable ? parseFloat((afterDiscount * (line.tax_rate / 100)).toFixed(2)) : 0;
                    line.line_total = parseFloat((afterDiscount + line.tax_amount).toFixed(2));
                    this.recalcTotals();
                },

                recalcTotals() {
                    this.totals = {
                        subtotal: this.lines.reduce((s, l) => s + (l.quantity * l.unit_price), 0),
                        discount: this.lines.reduce((s, l) => s + (l.discount_amount || 0), 0),
                        tax: this.lines.reduce((s, l) => s + l.tax_amount, 0),
                        total: this.lines.reduce((s, l) => s + l.line_total, 0),
                    };
                    this.totals = {
                        subtotal: parseFloat(this.totals.subtotal.toFixed(2)),
                        discount: parseFloat(this.totals.discount.toFixed(2)),
                        tax: parseFloat(this.totals.tax.toFixed(2)),
                        total: parseFloat(this.totals.total.toFixed(2)),
                    };
                },

                get totals() {
                    const subtotal = this.lines.reduce((s, l) => s + (l.quantity * l.unit_price), 0);
                    const discount = this.lines.reduce((s, l) => s + (l.discount_amount || 0), 0);
                    const tax = this.lines.reduce((s, l) => s + l.tax_amount, 0);
                    const total = this.lines.reduce((s, l) => s + l.line_total, 0);
                    return { subtotal, discount, tax, total };
                },

                get remaining() {
                    const paid = this.payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);
                    return Math.max(0, this.totals.total - paid);
                },

                addPayment() {
                    this.payments.push({ payment_method_id: '', amount: this.remaining > 0 ? this.remaining.toFixed(2) : 0 });
                },

                async submitSale() {
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
    @endpush
</x-app-layout>
