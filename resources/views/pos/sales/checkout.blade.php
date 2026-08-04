<x-app-layout>
    <x-slot name="header">{{ __('POS Checkout') }} – {{ session('pos_terminal_identifier') ?? '' }}</x-slot>

    <div class="pb-12" x-data="posCheckout()" x-effect="reactiveFilter()">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- Offline Indicator --}}
            <div x-show="!isOnline" x-cloak
                class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded relative flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="font-semibold">Offline Mode</span> – Sales will be queued and synced when connection is restored.
                </div>
                <span x-show="offlineQueueCount > 0" class="text-sm font-medium"
                    x-text="offlineQueueCount + ' sale(s) queued'"></span>
            </div>

            {{-- Sync Notification --}}
            <div x-show="syncResult" x-cloak x-transition
                class="mb-4 px-4 py-3 rounded relative flex items-center justify-between"
                :class="syncResult?.failed > 0 ? 'bg-yellow-100 border border-yellow-400 text-yellow-800' : 'bg-green-100 border border-green-400 text-green-700'">
                <span x-text="syncResult?.message"></span>
                <button @click="syncResult = null" class="ml-2 font-bold">&times;</button>
            </div>
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
                <div class="lg:col-span-2 card p-6">
                    <div class="form-section-label">1 · Add Items</div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <div class="relative">
                            <div class="scoped-search-field">
                                <svg class="scoped-search-filter" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                <input type="text" x-model="searchQuery"
                                    @focus="dropdownOpen = searchQuery.length > 0"
                                    @keydown.down.prevent="moveHighlight(1)"
                                    @keydown.up.prevent="moveHighlight(-1)"
                                    @keydown.enter.prevent="confirmHighlight()"
                                    @keydown.escape="dropdownOpen = false"
                                    placeholder="Type to search products... (Up/Down to navigate, Enter to select)" autocomplete="off" />
                                <span class="scoped-search-divider" aria-hidden="true"></span>
                                <button type="button" class="scoped-search-open" title="Search across all records" @click="openGlobalSearch()">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </button>
                            </div>
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
                                        <span class="text-sm font-semibold" x-text="formatMoney(parseFloat(p.sales_price))"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 mb-4 items-end">
                        <div class="w-[160px]" x-show="selectedProductName">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                            <select x-model="addUom" @change="onUomChange()"
                                class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option value="">Each (base)</option>
                                <template x-for="u in (productUoms.uoms || []).filter(u => !u.is_base)" :key="u.uom_name">
                                    <option :value="u.uom_name" x-text="u.uom_name + ' (' + u.conversion_factor + 'x)'"></option>
                                </template>
                            </select>
                        </div>
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
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">UOM</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Discount</th>
                                    <th class="text-right">Tax</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr>
                                        <td x-text="line.product_name"></td>
                                        <td class="text-center">
                                            <template x-if="line.transaction_uom">
                                                <select x-model="line.transaction_uom" @change="onLineUomChange(index)"
                                                    class="w-28 input">
                                                    <option value="">Each</option>
                                                    <template x-for="u in (uomConversions[line.product_id] || [])" :key="u.uom_name">
                                                        <option :value="u.uom_name" x-text="u.uom_name"></option>
                                                    </template>
                                                </select>
                                            </template>
                                            <template x-if="!line.transaction_uom">
                                                <span class="text-gray-400 text-xs">Each</span>
                                            </template>
                                        </td>
                                        <td class="text-right">
                                            <input type="number" x-model.number="line.transaction_qty" min="0.01" step="1"
                                                class="input w-20 text-right"
                                                @input="onLineQtyChange(index)" />
                                        </td>
                                        <td class="text-right">
                                            <input type="number" x-model.number="line.unit_price" min="0" step="0.01"
                                                class="input w-24 text-right" @input="recalcLine(index)" />
                                        </td>
                                        <td class="text-right">
                                            <input type="number" x-model.number="line.discount_amount" min="0" step="0.01"
                                                class="input w-20 text-right" @input="recalcLine(index)" />
                                        </td>
                                        <td class="numeric text-ink-soft" x-text="formatMoney(line.tax_amount)"></td>
                                        <td class="numeric font-semibold" x-text="formatMoney(line.line_total)"></td>
                                        <td class="text-center">
                                            <button type="button" @click="removeLine(index)" class="text-red-600 hover:text-red-900 text-sm font-medium">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="lines.length === 0">
                                    <td colspan="8" class="text-ink-soft text-center py-6">No items added yet. Search and add products above.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Payment Panel (sticky) --}}
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <div class="card p-6">
                        <div class="form-section-label">2 · Payment</div>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700">Customer</label>
                            <div class="pos-customer-picker">
                                <x-scoped-search-field
                                    name="customer_id"
                                    entity="customer"
                                    search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                    value=""
                                    label=""
                                    on-select="posCustomerSelected"
                                    placeholder="{{ __('Search customers...') }}"
                                />
                            </div>
                            <button type="button" @click="clearPosCustomer()" class="mt-1 text-xs text-gray-500 hover:text-indigo-600">Walk-in Customer</button>
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
                                <span x-text="formatMoney(getTotals().subtotal)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Discount</span>
                                <span x-text="'-' + formatMoney(getTotals().discount)"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tax</span>
                                <span x-text="formatMoney(getTotals().tax)"></span>
                            </div>
                            <div class="flex justify-between text-xl font-bold border-t pt-2">
                                <span>Total Due</span>
                                <span class="text-indigo-600" x-text="formatMoney(getTotals().total)"></span>
                            </div>
                        </div>

                        {{-- Confirmed Payments --}}
                        <div class="mb-4" x-show="payments.length > 0">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700">Payments</span>
                                <span class="text-sm font-medium" :class="getRemaining() > 0 ? 'text-red-600' : 'text-green-600'"
                                    x-text="'Remaining: ' + formatMoney(getRemaining())"></span>
                            </div>
                            <template x-for="(pay, pi) in payments" :key="pi">
                                <div class="flex justify-between items-center bg-gray-50 rounded-md px-3 py-2 mb-1 text-sm">
                                    <div>
                                        <span class="font-medium" x-text="pay.method_name"></span>
                                        <span class="text-gray-500 ml-1" x-show="pay.type === 'cash'" x-text="'(Tendered: ' + formatMoney(parseFloat(pay.cash_tendered)) + ')'"></span>
                                        <span class="text-gray-500 ml-1" x-show="pay.reference_number" x-text="'Ref: ' + pay.reference_number"></span>
                                        <span class="text-gray-400 ml-1 text-xs" x-show="pay.account_name" x-text="pay.account_name"></span>
                                        <span class="text-gray-400 ml-1 text-xs" x-show="pay.institution" x-text="pay.institution"></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold" x-text="formatMoney(parseFloat(pay.amount))"></span>
                                        <span x-show="pay.type === 'cash' && parseFloat(pay.change) > 0" class="text-green-600 text-xs" x-text="'Change: ' + formatMoney(parseFloat(pay.change))"></span>
                                        <button type="button" @click="removePayment(pi)" class="text-red-500 hover:text-red-700 font-bold text-xs">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add Payment Button --}}
                        <div class="mb-4" x-show="getRemaining() > 0">
                            <div class="grid grid-cols-2 gap-2 mb-2">
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
                                <button type="button" @click="openSplitModal()"
                                    class="py-3 px-4 bg-amber-50 border-2 border-amber-300 text-amber-700 rounded-lg font-semibold text-sm hover:bg-amber-100 transition">
                                    Split Payment
                                </button>
                            </div>
                        </div>

                        {{-- COMPLETE SALE BUTTON --}}
                        <div class="mt-4 pt-4 border-t">
                            <button type="button" @click="submitSale()"
                                :disabled="lines.length === 0 || submitting || getRemaining() > 0"
                                class="w-full py-4 px-6 rounded-lg font-bold text-lg uppercase tracking-wider transition duration-200"
                                :class="(lines.length > 0 && getRemaining() <= 0 && !submitting) ? 'bg-green-600 hover:bg-green-500 text-white shadow-lg cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed'">
                                <span x-show="!submitting && lines.length > 0 && getRemaining() > 0" x-text="'Balance Due: ' + formatMoney(getRemaining())"></span>
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
            class="modal-overlay"
            @keydown.escape.window="closeModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Cash Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter the cash amount received from the customer.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-500 mb-1">Total Due</div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="formatMoney(modalDue)"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cash Tendered</label>
                    <input type="number" x-model.number="modalCashTendered" x-ref="cashTenderedInput" min="0" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                <div class="flex flex-wrap gap-2 mb-4">
                    <button type="button" @click="modalCashTendered = modalDue"
                        class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-sm font-medium">Exact</button>
                    <template x-for="denom in [10, 20, 50, 100]" :key="denom">
                        <button type="button" @click="modalCashTendered = denom"
                            class="px-3 py-1.5 bg-gray-200 hover:bg-gray-300 rounded text-sm font-medium"
                            x-text="formatMoney(denom)"></button>
                    </template>
                </div>

                <div class="rounded-lg p-3 mb-4 text-center"
                    :class="modalCashTendered >= modalDue ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'"
                    x-show="modalCashTendered > 0">
                    <template x-if="modalCashTendered >= modalDue">
                        <div>
                            <div class="text-xs text-green-600 font-medium uppercase">Change to Give</div>
                            <div class="text-3xl font-bold text-green-600" x-text="formatMoney(modalCashTendered - modalDue)"></div>
                        </div>
                    </template>
                    <template x-if="modalCashTendered > 0 && modalCashTendered < modalDue">
                        <div>
                            <div class="text-xs text-red-600 font-medium uppercase">Insufficient — Remaining</div>
                            <div class="text-2xl font-bold text-red-600" x-text="formatMoney(modalDue - modalCashTendered)"></div>
                        </div>
                    </template>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold text-lg hover:bg-gray-300">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalCashTendered || modalCashTendered < modalDue"
                        class="flex-1 py-3 px-4 bg-emerald-600 text-white rounded-lg font-bold text-lg hover:bg-emerald-500 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Card) ===== --}}
        <div x-show="showModal && modalType === 'card'" x-cloak
            class="modal-overlay"
            @keydown.escape.window="closeModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Card Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter card payment details.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-500 mb-1">Balance Due</div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="formatMoney(modalDue)"></div>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="cardAmountInput" min="0.01" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                        placeholder="e.g. TXN12345" />
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" x-model="modalAccountName"
                        class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                        placeholder="Cardholder name" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Financial Institution</label>
                    <select x-model="modalInstitution"
                        class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                        <option value="">Select bank...</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->name }}">{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold text-lg hover:bg-gray-300">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0 || Math.abs(parseFloat(modalAmount) - modalDue) > 0.01"
                        class="flex-1 py-3 px-4 bg-blue-600 text-white rounded-lg font-bold text-lg hover:bg-blue-500 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Mobile Money) ===== --}}
        <div x-show="showModal && modalType === 'mobile_money'" x-cloak
            class="modal-overlay"
            @keydown.escape.window="closeModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Mobile Money Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Enter mobile money payment details.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-500 mb-1">Balance Due</div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="formatMoney(modalDue)"></div>
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="mobileAmountInput" min="0.01" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full text-2xl font-semibold border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm text-right"
                        placeholder="0.00" />
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                        placeholder="e.g. MP123456" />
                </div>

                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                    <input type="text" x-model="modalAccountName"
                        class="block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm"
                        placeholder="Account holder name" />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Provider / Institution</label>
                    <select x-model="modalInstitution"
                        class="block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm">
                        <option value="">Select provider...</option>
                        @foreach($mobileProviders as $provider)
                            <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold text-lg hover:bg-gray-300">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0 || Math.abs(parseFloat(modalAmount) - modalDue) > 0.01"
                        class="flex-1 py-3 px-4 bg-purple-600 text-white rounded-lg font-bold text-lg hover:bg-purple-500 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== SPLIT PAYMENT MODAL ===== --}}
        <div x-show="showModal && modalType === 'split'" x-cloak
            class="modal-overlay"
            @keydown.escape.window="closeModal()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" @click.stop>
                <h3 class="text-xl font-bold text-gray-800 mb-1">Split Payment</h3>
                <div class="text-sm text-gray-500 mb-4">Allocate the total due across multiple payment methods.</div>

                <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm text-gray-500">Total Due</div>
                    <div class="text-3xl font-bold text-indigo-600" x-text="formatMoney(modalDue)"></div>
                </div>

                {{-- Cash Row --}}
                <div class="border rounded-lg p-4 mb-3" :class="splitCashEnabled ? 'border-emerald-300 bg-emerald-50/30' : ''">
                    <label class="flex items-center gap-2 mb-0 cursor-pointer">
                        <input type="checkbox" x-model="splitCashEnabled" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                        <span class="font-semibold text-gray-800">Cash</span>
                    </label>
                    <div x-show="splitCashEnabled" x-transition class="mt-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Allocate to Cash</label>
                                <input type="number" x-model.number="splitCashAlloc" min="0" step="0.01"
                                    @focus="$el.select()"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Cash Tendered</label>
                                <input type="number" x-model.number="splitCashTendered" min="0" step="0.01"
                                    @focus="$el.select()"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" placeholder="0.00" />
                            </div>
                        </div>
                        <div class="mt-2 flex justify-between items-center text-sm" x-show="splitCashTendered > 0 && splitCashAlloc > 0">
                            <span class="text-gray-500">Change</span>
                            <span class="font-semibold"
                                :class="splitCashTendered >= splitCashAlloc ? 'text-green-600' : 'text-red-600'"
                                x-text="splitCashTendered >= splitCashAlloc ? formatMoney(splitCashTendered - splitCashAlloc) : 'Short by ' + formatMoney(splitCashAlloc - splitCashTendered)"></span>
                        </div>
                    </div>
                </div>

                {{-- Card Row --}}
                <div class="border rounded-lg p-4 mb-3" :class="splitCardEnabled ? 'border-blue-300 bg-blue-50/30' : ''">
                    <label class="flex items-center gap-2 mb-0 cursor-pointer">
                        <input type="checkbox" x-model="splitCardEnabled" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="font-semibold text-gray-800">Card</span>
                    </label>
                    <div x-show="splitCardEnabled" x-transition class="mt-3">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                <input type="number" x-model.number="splitCardAmount" min="0" step="0.01"
                                    @focus="$el.select()"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Reference / Transaction No.</label>
                                <input type="text" x-model="splitCardRef"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="e.g. TXN12345" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Account Name</label>
                                <input type="text" x-model="splitCardAccountName"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Cardholder name" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Financial Institution</label>
                                <select x-model="splitCardInstitution"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">Select bank...</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->name }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile Money Row --}}
                <div class="border rounded-lg p-4 mb-4" :class="splitMobileEnabled ? 'border-purple-300 bg-purple-50/30' : ''">
                    <label class="flex items-center gap-2 mb-0 cursor-pointer">
                        <input type="checkbox" x-model="splitMobileEnabled" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                        <span class="font-semibold text-gray-800">Mobile Money</span>
                    </label>
                    <div x-show="splitMobileEnabled" x-transition class="mt-3">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Amount</label>
                                <input type="number" x-model.number="splitMmobileAmount" min="0" step="0.01"
                                    @focus="$el.select()"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm text-right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Reference / Transaction No.</label>
                                <input type="text" x-model="splitMobileRef"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="e.g. MP123456" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Account Name</label>
                                <input type="text" x-model="splitMobileAccountName"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Account holder name" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Provider / Institution</label>
                                <select x-model="splitMobileInstitution"
                                    class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    <option value="">Select provider...</option>
                                    @foreach($mobileProviders as $provider)
                                        <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Running Balance --}}
                <div class="rounded-lg p-3 mb-4 text-center"
                    :class="getSplitRemaining() === 0 ? 'bg-green-50 border border-green-200' : getSplitRemaining() > 0 ? 'bg-amber-50 border border-amber-200' : 'bg-red-50 border border-red-200'">
                    <div class="text-xs font-medium uppercase"
                        :class="getSplitRemaining() === 0 ? 'text-green-600' : getSplitRemaining() > 0 ? 'text-amber-600' : 'text-red-600'"
                        x-text="getSplitRemaining() === 0 ? 'Balanced' : getSplitRemaining() > 0 ? 'Remaining Balance' : 'Over-allocated'"></div>
                    <div class="text-2xl font-bold"
                        :class="getSplitRemaining() === 0 ? 'text-green-600' : getSplitRemaining() > 0 ? 'text-amber-600' : 'text-red-600'"
                        x-text="formatMoney(Math.abs(getSplitRemaining()))"></div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="closeModal()"
                        class="flex-1 py-3 px-4 bg-gray-200 text-gray-700 rounded-lg font-semibold text-lg hover:bg-gray-300">Cancel</button>
                    <button type="button" @click="confirmSplitPayment()"
                        :disabled="getSplitRemaining() !== 0 || !splitPaymentValid()"
                        class="flex-1 py-3 px-4 bg-amber-600 text-white rounded-lg font-bold text-lg hover:bg-amber-500 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">Proceed</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.posCustomerSelected = function(id, item) {
            window.dispatchEvent(new CustomEvent('pos-customer-selected', {
                detail: { id: id, label: item ? item.label : '' },
            }));
        };

        window.clearPosCustomer = function() {
            window.dispatchEvent(new CustomEvent('pos-customer-selected', {
                detail: { id: '', label: '' },
            }));
            const host = document.querySelector('.pos-customer-picker [data-scoped-search-field]');
            if (host && window.Alpine) {
                const comp = Alpine.$data(host);
                if (comp && typeof comp.clear === 'function') comp.clear();
            }
        };

        function posCheckout() {
            return {
                products: @json($products),
                filteredProducts: [],
                searchQuery: '',
                selectedProductId: '',
                selectedProductName: '',
                addQty: 1,
                addUom: '',
                addConversionFactor: 1,
                uomConversions: @json($uomConversions),
                productUoms: {},
                lines: [],
                customerId: '',
                reference: '',
                payments: [],
                submitting: false,
                dropdownOpen: false,
                highlightIndex: -1,
                _lastQuery: '',
                fieldId: null,
                paymentTypes: @json($paymentMethods->pluck('type', 'id')),
                paymentNames: @json($paymentMethods->pluck('name', 'id')),
                showModal: false,
                modalType: '',
                modalDue: 0,
                modalCashTendered: 0,
                modalAmount: 0,
                modalReference: '',
                modalAccountName: '',
                modalInstitution: '',
                bankAccounts: @json($bankAccounts),
                splitCashEnabled: false,
                splitCashAlloc: 0,
                splitCashTendered: 0,
                splitCardEnabled: false,
                splitCardAmount: 0,
                splitCardRef: '',
                splitCardAccountName: '',
                splitCardInstitution: '',
                splitMobileEnabled: false,
                splitMmobileAmount: 0,
                splitMobileRef: '',
                splitMobileAccountName: '',
                splitMobileInstitution: '',
                isOnline: navigator.onLine,
                offlineQueueCount: PosOfflineQueue.getCount(),
                syncResult: null,

                init() {
                    this.filteredProducts = [];
                    this.fieldId = 'pos-' + Math.random().toString(36).slice(2, 10) + '-' + Date.now().toString(36);
                    if (this.$el) {
                        this.$el.setAttribute('data-scoped-search-field', this.fieldId);
                    }
                    document.addEventListener('click', (e) => {
                        if (!this.$el.contains(e.target)) {
                            this.dropdownOpen = false;
                        }
                    });
                    window.addEventListener('global-search-selected', (e) => {
                        const detail = (e && e.detail) || {};
                        if (!detail.entity || detail.entity !== 'product') return;
                        const idMatches = detail.fieldId != null && detail.fieldId === this.fieldId;
                        const elMatches = detail.field != null && detail.field === this.$el;
                        if (!idMatches && !elMatches) return;
                        if (detail.item && detail.item.id) {
                            const product = this.products.find((p) => String(p.id) === String(detail.item.id));
                            if (product) this.selectProduct(product);
                        }
                    });
                    window.addEventListener('pos-customer-selected', (e) => {
                        this.customerId = (e.detail && e.detail.id) || '';
                    });
                    window.addEventListener('online', () => {
                        this.isOnline = true;
                        this.attemptSync();
                    });
                    window.addEventListener('offline', () => {
                        this.isOnline = false;
                    });
                    this.offlineQueueCount = PosOfflineQueue.getCount();
                },

                async attemptSync() {
                    const count = PosOfflineQueue.getCount();
                    if (count === 0) return;
                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const result = await PosOfflineQueue.syncAll(csrf);
                    this.offlineQueueCount = PosOfflineQueue.getCount();
                    if (result.synced > 0 && result.failed === 0) {
                        this.syncResult = { message: 'Synced ' + result.synced + ' queued sale(s) successfully.', failed: 0 };
                    } else if (result.synced > 0 && result.failed > 0) {
                        this.syncResult = { message: 'Synced ' + result.synced + ', failed ' + result.failed + ' sale(s). Retrying...', failed: result.failed };
                    } else if (result.failed > 0) {
                        this.syncResult = { message: 'Failed to sync ' + result.failed + ' sale(s). Will retry.', failed: result.failed };
                    }
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
                        p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)) || (p.barcode && String(p.barcode).toLowerCase().includes(q))
                    );
                    if (this.filteredProducts.length === 1) {
                        const p = this.filteredProducts[0];
                        const barcode = p.barcode ? String(p.barcode).toLowerCase() : '';
                        const sku = p.sku ? String(p.sku).toLowerCase() : '';
                        if (q === barcode || q === sku) {
                            this.selectProduct(p);
                            return;
                        }
                    }
                    this.dropdownOpen = true;
                },

                openGlobalSearch() {
                    window.dispatchEvent(new CustomEvent('open-global-search', {
                        detail: { query: this.searchQuery, entity: 'product', field: this.$el, fieldId: this.fieldId },
                    }));
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

                    // Load UOMs for this product
                    const uoms = this.uomConversions[p.id] || [];
                    this.productUoms = { uoms: uoms, basePrice: parseFloat(p.sales_price) || 0 };
                    if (uoms.length > 0) {
                        const base = uoms.find(u => u.is_base);
                        this.addUom = base ? base.uom_name : uoms[0].uom_name;
                        this.addConversionFactor = base ? base.conversion_factor : uoms[0].conversion_factor;
                    } else {
                        this.addUom = '';
                        this.addConversionFactor = 1;
                    }

                    this.$nextTick(() => {
                        const qtyInput = this.$el.querySelector('input[x-model="addQty"]');
                        if (qtyInput) qtyInput.focus();
                    });
                },

                onUomChange() {
                    const uoms = this.productUoms.uoms || [];
                    const selected = uoms.find(u => u.uom_name === this.addUom);
                    if (selected) {
                        this.addConversionFactor = selected.conversion_factor;
                    } else {
                        this.addConversionFactor = 1;
                    }
                },

                addLine() {
                    if (!this.selectedProductId) return;
                    const product = this.products.find(p => p.id == this.selectedProductId);
                    if (!product) return;

                    const qty = parseInt(this.addQty) || 1;
                    const uom = this.addUom || '';
                    const convFactor = parseFloat(this.addConversionFactor) || 1;
                    const basePrice = parseFloat(product.sales_price) || 0;
                    const uoms = this.productUoms.uoms || [];
                    const uomDef = uoms.find(u => u.uom_name === uom);
                    const uomPrice = (uomDef && uomDef.sales_price > 0) ? uomDef.sales_price : basePrice;

                    const existing = this.lines.find(l => l.product_id == product.id && l.transaction_uom === uom);
                    if (existing) {
                        existing.quantity += qty;
                        this.recalcLine(this.lines.indexOf(existing));
                    } else {
                        const line = {
                            product_id: product.id,
                            product_name: product.sku + ' – ' + product.name,
                            quantity: qty,
                            unit_price: uomPrice,
                            discount_amount: 0,
                            discount_type: null,
                            tax_rate: parseFloat(product.tax_rate) || 0,
                            is_taxable: product.is_taxable,
                            tax_amount: 0,
                            line_total: 0,
                            transaction_uom: uom,
                            transaction_qty: qty,
                            conversion_factor: convFactor,
                        };
                        this.lines.push(line);
                        this.recalcLine(this.lines.length - 1);
                    }

                    this.selectedProductId = '';
                    this.selectedProductName = '';
                    this.searchQuery = '';
                    this.addQty = 1;
                    this.addUom = '';
                    this.addConversionFactor = 1;
                    this.productUoms = {};
                    this.filteredProducts = [];
                    this.dropdownOpen = false;
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                },

                onLineQtyChange(index) {
                    const line = this.lines[index];
                    if (line.transaction_uom && line.conversion_factor > 0) {
                        line.quantity = parseFloat((line.transaction_qty * line.conversion_factor).toFixed(4));
                    } else {
                        line.quantity = line.transaction_qty || 1;
                    }
                    this.recalcLine(index);
                },

                onLineUomChange(index) {
                    const line = this.lines[index];
                    const product = this.products.find(p => p.id == line.product_id);
                    if (!product) return;
                    const uoms = this.uomConversions[line.product_id] || [];
                    const uomDef = uoms.find(u => u.uom_name === line.transaction_uom);
                    if (uomDef) {
                        line.conversion_factor = uomDef.conversion_factor;
                        if (uomDef.sales_price > 0) {
                            line.unit_price = uomDef.sales_price;
                        }
                    } else {
                        line.conversion_factor = 1;
                        line.unit_price = parseFloat(product.sales_price) || 0;
                    }
                    line.transaction_qty = line.transaction_qty || 1;
                    this.onLineQtyChange(index);
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
                    this.modalAccountName = '';
                    this.modalInstitution = '';
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
                    this.modalAccountName = '';
                    this.modalInstitution = '';
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
                            account_name: this.modalAccountName.trim(),
                            institution: this.modalInstitution,
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
                            account_name: this.modalAccountName.trim(),
                            institution: this.modalInstitution,
                        });
                    }
                    this.closeModal();
                },

                removePayment(pi) {
                    this.payments.splice(pi, 1);
                },

                openSplitModal() {
                    this.modalType = 'split';
                    this.modalDue = this.getRemaining();
                    this.splitCashEnabled = false;
                    this.splitCashAlloc = 0;
                    this.splitCashTendered = 0;
                    this.splitCardEnabled = false;
                    this.splitCardAmount = 0;
                    this.splitCardRef = '';
                    this.splitCardAccountName = '';
                    this.splitCardInstitution = '';
                    this.splitMobileEnabled = false;
                    this.splitMmobileAmount = 0;
                    this.splitMobileRef = '';
                    this.splitMobileAccountName = '';
                    this.splitMobileInstitution = '';
                    this.showModal = true;
                },

                getSplitRemaining() {
                    const alloc = (this.splitCashEnabled ? (parseFloat(this.splitCashAlloc) || 0) : 0)
                                + (this.splitCardEnabled ? (parseFloat(this.splitCardAmount) || 0) : 0)
                                + (this.splitMobileEnabled ? (parseFloat(this.splitMmobileAmount) || 0) : 0);
                    return parseFloat((this.modalDue - alloc).toFixed(2));
                },

                splitPaymentValid() {
                    const hasEnabled = this.splitCashEnabled || this.splitCardEnabled || this.splitMobileEnabled;
                    if (!hasEnabled) return false;

                    if (this.splitCashEnabled) {
                        const cashAlloc = parseFloat(this.splitCashAlloc) || 0;
                        const cashTendered = parseFloat(this.splitCashTendered) || 0;
                        if (cashAlloc <= 0) return false;
                        if (cashTendered <= 0) return false;
                        if (cashTendered < cashAlloc) return false;
                    }

                    if (this.splitCardEnabled) {
                        const cardAmount = parseFloat(this.splitCardAmount) || 0;
                        if (cardAmount <= 0) return false;
                        if (!this.splitCardRef.trim()) return false;
                    }

                    if (this.splitMobileEnabled) {
                        const mobileAmount = parseFloat(this.splitMmobileAmount) || 0;
                        if (mobileAmount <= 0) return false;
                        if (!this.splitMobileRef.trim()) return false;
                    }

                    return true;
                },

                confirmSplitPayment() {
                    if (this.getSplitRemaining() !== 0 || !this.splitPaymentValid()) return;

                    if (this.splitCashEnabled) {
                        const cashAlloc = parseFloat(this.splitCashAlloc) || 0;
                        const cashTendered = parseFloat(this.splitCashTendered) || 0;
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'cash') || '';
                        this.payments.push({
                            type: 'cash',
                            method_name: 'Cash',
                            payment_method_id: pmId,
                            amount: cashAlloc,
                            cash_tendered: cashTendered,
                            change: parseFloat((cashTendered - cashAlloc).toFixed(2)),
                            reference_number: '',
                        });
                    }

                    if (this.splitCardEnabled) {
                        const cardAmount = parseFloat(this.splitCardAmount) || 0;
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'card') || '';
                        this.payments.push({
                            type: 'card',
                            method_name: 'Card',
                            payment_method_id: pmId,
                            amount: cardAmount,
                            cash_tendered: 0,
                            change: 0,
                            reference_number: this.splitCardRef.trim(),
                        });
                    }

                    if (this.splitMobileEnabled) {
                        const mobileAmount = parseFloat(this.splitMmobileAmount) || 0;
                        const pmId = Object.keys(this.paymentTypes).find(id => this.paymentTypes[id] === 'mobile_money') || '';
                        this.payments.push({
                            type: 'mobile_money',
                            method_name: 'Mobile Money',
                            payment_method_id: pmId,
                            amount: mobileAmount,
                            cash_tendered: 0,
                            change: 0,
                            reference_number: this.splitMobileRef.trim(),
                        });
                    }

                    this.closeModal();
                },

                async submitSale() {
                    if (this.lines.length === 0 || this.getRemaining() > 0) return;

                    this.submitting = true;
                    document.getElementById('pos-error').classList.add('hidden');

                    const payload = {
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
                            transaction_uom: l.transaction_uom || null,
                            transaction_qty: l.transaction_qty || null,
                            conversion_factor: l.conversion_factor || null,
                        })),
                        payments: this.payments.map(p => ({
                            payment_method_id: p.payment_method_id,
                            amount: parseFloat(p.amount),
                            cash_tendered: parseFloat(p.cash_tendered) || null,
                            change_given: parseFloat(p.change) || null,
                            reference_number: p.reference_number || null,
                            account_name: p.account_name || null,
                            institution: p.institution || null,
                        })),
                    };

                    if (!navigator.onLine) {
                        const offlineId = PosOfflineQueue.enqueue(payload);
                        this.offlineQueueCount = PosOfflineQueue.getCount();
                        this.submitting = false;
                        this.lines = [];
                        this.payments = [];
                        this.customerId = '';
                        this.reference = '';
                        const errDiv = document.getElementById('pos-error');
                        document.getElementById('pos-error-text').textContent = 'Sale queued for offline sync. ID: ' + offlineId;
                        errDiv.classList.remove('hidden');
                        errDiv.className = 'mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative';
                        return;
                    }

                    try {
                        const resp = await fetch('{{ route("pos.sales.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await resp.json();
                        if (data.success) {
                            window.location.href = '{{ route("pos.sales.receipt", "__ID__") }}'.replace('__ID__', data.sale_id);
                        } else {
                            const errDiv = document.getElementById('pos-error');
                            document.getElementById('pos-error-text').textContent = data.message;
                            errDiv.classList.remove('hidden');
                            errDiv.className = 'mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
                        }
                    } catch (e) {
                        const errDiv = document.getElementById('pos-error');
                        document.getElementById('pos-error-text').textContent = 'An unexpected error occurred.';
                        errDiv.classList.remove('hidden');
                        errDiv.className = 'mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative';
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>

    <script src="/js/pos-offline-queue.js"></script>
    <script>
        // Background sync when back online
        window.addEventListener('online', async () => {
            const count = PosOfflineQueue.getCount();
            if (count > 0 && typeof Alpine !== 'undefined') {
                document.querySelectorAll('[x-data]').forEach((el) => {
                    if (el.__x && el.__x.$data.isOnline !== undefined) {
                        el.__x.$data.isOnline = true;
                        el.__x.$data.offlineQueueCount = PosOfflineQueue.getCount();
                        el.__x.$data.syncResult = { message: 'Syncing ' + count + ' queued sale(s)...', failed: 0 };
                    }
                });
            }
        });
    </script>
</x-app-layout>
