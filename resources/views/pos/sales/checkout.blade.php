<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>POS Checkout</h1>
                <div class="pos-sub">{{ session('pos_terminal_identifier') ?? '' }}</div>
            </div>
        </div>

        <div x-data="posCheckout()" x-effect="reactiveFilter()">

            {{-- Offline Indicator --}}
            <div x-show="!isOnline" x-cloak class="pos-alert pos-alert-warn" style="margin-bottom:16px">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="pos-bold">Offline Mode</span> – Sales will be queued and synced when connection is restored.
                </div>
                <span x-show="offlineQueueCount > 0" class="pos-sub" x-text="offlineQueueCount + ' sale(s) queued'"></span>
            </div>

            {{-- Sync Notification --}}
            <div x-show="syncResult" x-cloak x-transition class="pos-alert" style="margin-bottom:16px"
                :class="syncResult?.failed > 0 ? 'pos-alert-warn' : 'pos-alert-ok'">
                <span x-text="syncResult?.message"></span>
                <button @click="syncResult = null" class="pos-btn-close">&times;</button>
            </div>

            <div id="pos-error" class="pos-alert pos-alert-error" style="margin-bottom:16px;display:none">
                <span id="pos-error-text"></span>
            </div>

            <div class="pos-g-checkout">
                {{-- Product Selection & Lines --}}
                <div class="pos-g-checkout-main">
                    <div class="pos-card">
                        <div class="pos-card-h">1 · Add Items</div>
                        <div class="pos-pad">

                            <div style="margin-bottom:16px">
                                <label class="pos-lbl">Product</label>
                                <div style="position:relative">
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
                                    <div x-show="dropdownOpen && filteredProducts.length > 0" x-cloak class="pos-dropdown">
                                        <template x-for="(p, pi) in filteredProducts" :key="p.id">
                                            <div class="pos-dropdown-row"
                                                :class="parseInt(pi) === highlightIndex ? 'pos-dropdown-row--hl' : (p.tracked_as_inventory && p.current_stock <= 0 ? 'pos-dropdown-row--oos' : '')"
                                                @click="selectProduct(p)"
                                                @mouseenter="highlightIndex = parseInt(pi)">
                                                <div>
                                                    <span class="pos-bold" x-text="p.sku + ' – ' + p.name"></span>
                                                    <span x-show="p.tracked_as_inventory" class="pos-sub" style="margin-left:8px"
                                                        x-text="p.current_stock > 0 ? 'Stock: ' + p.current_stock : 'Out of stock'"></span>
                                                </div>
                                                <span class="pos-bold" x-text="formatMoney(parseFloat(p.sales_price))"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;gap:12px;margin-bottom:16px;align-items:flex-end">
                                <div style="width:160px" x-show="selectedProductName">
                                    <label class="pos-lbl">Unit</label>
                                    <select x-model="addUom" @change="onUomChange()" class="pos-in pos-in-sm">
                                        <option value="">Each (base)</option>
                                        <template x-for="u in (productUoms.uoms || []).filter(u => !u.is_base)" :key="u.uom_name">
                                            <option :value="u.uom_name" x-text="u.uom_name + ' (' + u.conversion_factor + 'x)'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div style="width:90px">
                                    <label class="pos-lbl">Qty</label>
                                    <input type="number" x-model="addQty" min="1" step="1" value="1"
                                        @keydown.enter.prevent="addLine()"
                                        class="pos-in pos-in-sm" style="text-align:center" />
                                </div>
                                <button type="button" @click="addLine()" class="pos-btn pos-btn-cta">Add</button>
                                <div class="pos-sub" x-show="selectedProductName">
                                    Selected: <span class="pos-bold" x-text="selectedProductName"></span>
                                </div>
                            </div>

                            <div class="pos-li-wrap">
                                <table class="pos-tbl">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="center">UOM</th>
                                            <th class="num">Qty</th>
                                            <th class="num">Price</th>
                                            <th class="num">Discount</th>
                                            <th class="num">Tax</th>
                                            <th class="num">Total</th>
                                            <th class="center"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(line, index) in lines" :key="index">
                                            <tr>
                                                <td x-text="line.product_name"></td>
                                                <td class="center">
                                                    <template x-if="line.transaction_uom">
                                                        <select x-model="line.transaction_uom" @change="onLineUomChange(index)" class="pos-in pos-in-sm" style="width:112px">
                                                            <option value="">Each</option>
                                                            <template x-for="u in (uomConversions[line.product_id] || [])" :key="u.uom_name">
                                                                <option :value="u.uom_name" x-text="u.uom_name"></option>
                                                            </template>
                                                        </select>
                                                    </template>
                                                    <template x-if="!line.transaction_uom">
                                                        <span class="pos-sub">Each</span>
                                                    </template>
                                                </td>
                                                <td class="num">
                                                    <input type="number" x-model.number="line.transaction_qty" min="0.01" step="1"
                                                        class="pos-in pos-in-sm" style="width:80px;text-align:right"
                                                        @input="onLineQtyChange(index)" />
                                                </td>
                                                <td class="num">
                                                    <input type="number" x-model.number="line.unit_price" min="0" step="0.01"
                                                        class="pos-in pos-in-sm" style="width:96px;text-align:right" @input="recalcLine(index)" />
                                                </td>
                                                <td class="num">
                                                    <input type="number" x-model.number="line.discount_amount" min="0" step="0.01"
                                                        class="pos-in pos-in-sm" style="width:80px;text-align:right" @input="recalcLine(index)" />
                                                </td>
                                                <td class="num pos-em" x-text="formatMoney(line.tax_amount)"></td>
                                                <td class="num pos-bold" x-text="formatMoney(line.line_total)"></td>
                                                <td class="center">
                                                    <button type="button" @click="removeLine(index)" class="pos-btn-del" title="Remove">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="lines.length === 0">
                                            <td colspan="8" class="pos-em" style="text-align:center;padding:24px">No items added yet. Search and add products above.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment Panel (sticky) --}}
                <div class="pos-g-checkout-side">
                    <div class="pos-card pos-sticky-top">
                        <div class="pos-card-h">2 · Payment</div>
                        <div class="pos-pad">

                            <div style="margin-bottom:12px">
                                <label class="pos-lbl">Customer</label>
                                <div class="pos-customer-picker">
                                    <x-scoped-search-field
                                        name="customer_id"
                                        entity="customer"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                        value="{{ $walkInCustomer?->id ?? '' }}"
                                        label="{{ $walkInCustomer?->name ?? '' }}"
                                        on-select="posCustomerSelected"
                                        placeholder="{{ __('Search customers...') }}"
                                    />
                                </div>
                                <button type="button" @click="setWalkInCustomer()" class="pos-link" style="margin-top:4px">Walk-in Customer</button>
                                <div x-show="bottleCreditAvailable > 0" x-cloak class="pos-card-accent" style="margin-top:8px;padding:10px">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <span class="pos-sub pos-bold">Bottle Credit Available</span>
                                        <span class="pos-bold" x-text="formatMoney(bottleCreditAvailable)"></span>
                                    </div>
                                    <button type="button" @click="bottleCreditApplied = Math.min(bottleCreditAvailable, getTotals().total); bottleReturnableIds = ['auto'];"
                                        x-show="bottleCreditApplied === 0"
                                        class="pos-btn pos-btn-sec pos-btn-sm" style="width:100%;margin-top:6px">Apply to This Sale</button>
                                    <button type="button" @click="bottleCreditApplied = 0; bottleReturnableIds = [];"
                                        x-show="bottleCreditApplied > 0"
                                        class="pos-btn pos-btn-ghost pos-btn-sm" style="width:100%;margin-top:6px">Remove Credit</button>
                                </div>
                            </div>

                            <div style="margin-bottom:16px">
                                <label class="pos-lbl">Reference</label>
                                <input type="text" x-model="reference" class="pos-in" placeholder="Optional" />
                            </div>

                            {{-- Totals --}}
                            <div class="pos-totals">
                                <div class="pos-total-row">
                                    <span class="pos-sub">Subtotal</span>
                                    <span x-text="formatMoney(getTotals().subtotal)"></span>
                                </div>
                                <div class="pos-total-row">
                                    <span class="pos-sub">Discount</span>
                                    <span x-text="'-' + formatMoney(getTotals().discount)"></span>
                                </div>
                                <div class="pos-total-row">
                                    <span class="pos-sub">Tax</span>
                                    <span x-text="formatMoney(getTotals().tax)"></span>
                                </div>
                                <div class="pos-total-row" x-show="bottleCreditApplied > 0">
                                    <span class="pos-bold" style="color:var(--pos-sec)">Bottle Credit</span>
                                    <span class="pos-bold" style="color:var(--pos-sec)" x-text="'-' + formatMoney(bottleCreditApplied)"></span>
                                </div>
                                <div class="pos-total-row pos-total-grand">
                                    <span class="pos-bold">Total Due</span>
                                    <span class="pos-numr pos-bold" x-text="formatMoney(getTotals().total - bottleCreditApplied)"></span>
                                </div>
                            </div>

                            {{-- Confirmed Payments --}}
                            <div x-show="payments.length > 0" style="margin-bottom:16px">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                                    <span class="pos-bold">Payments</span>
                                    <span class="pos-sub pos-bold" :class="getRemaining() > 0 ? 'pos-neg' : 'pos-pos'"
                                        x-text="'Remaining: ' + formatMoney(getRemaining())"></span>
                                </div>
                                <template x-for="(pay, pi) in payments" :key="pi">
                                    <div class="pos-payment-row">
                                        <div>
                                            <span class="pos-bold" x-text="pay.method_name"></span>
                                            <span class="pos-sub" x-show="pay.type === 'cash'" x-text="'(Tendered: ' + formatMoney(parseFloat(pay.cash_tendered)) + ')'"></span>
                                            <span class="pos-sub" x-show="pay.reference_number" x-text="'Ref: ' + pay.reference_number"></span>
                                            <span class="pos-sub" x-show="pay.account_name" x-text="pay.account_name"></span>
                                            <span class="pos-sub" x-show="pay.institution" x-text="pay.institution"></span>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:8px">
                                            <span class="pos-bold" x-text="formatMoney(parseFloat(pay.amount))"></span>
                                            <span x-show="pay.type === 'cash' && parseFloat(pay.change) > 0" class="pos-pos pos-sub" x-text="'Change: ' + formatMoney(parseFloat(pay.change))"></span>
                                            <button type="button" @click="removePayment(pi)" class="pos-btn-close" style="color:var(--pos-red)">&times;</button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Add Payment Buttons --}}
                            <div x-show="getRemaining() > 0" style="margin-bottom:16px">
                                <div class="pos-g2" style="gap:8px">
                                    <button type="button" @click="openPaymentModal('cash')" class="pos-btn pos-btn-pay-cash">Cash</button>
                                    <button type="button" @click="openPaymentModal('card')" class="pos-btn pos-btn-pay-card">Card</button>
                                    <button type="button" @click="openPaymentModal('mobile_money')" class="pos-btn pos-btn-pay-mobile">Mobile Money</button>
                                    <button type="button" @click="openSplitModal()" class="pos-btn pos-btn-pay-split">Split Payment</button>
                                </div>
                            </div>

                            {{-- COMPLETE SALE BUTTON --}}
                            <div class="pos-total-row pos-total-grand" style="border-top:none;padding-top:12px">
                                <button type="button" @click="submitSale()"
                                    :disabled="lines.length === 0 || submitting || getRemaining() > 0"
                                    class="pos-btn pos-btn-submit"
                                    :class="(lines.length > 0 && getRemaining() <= 0 && !submitting) ? 'pos-btn-submit--ready' : 'pos-btn-submit--disabled'">
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
        </div>

        {{-- ===== PAYMENT MODAL (Cash) ===== --}}
        <div x-show="showModal && modalType === 'cash'" x-cloak class="pos-overlay" @keydown.escape.window="closeModal()">
            <div class="pos-modal" @click.stop>
                <div class="pos-modal-h">Cash Payment</div>
                <div class="pos-modal-sub">Enter the cash amount received from the customer.</div>

                <div class="pos-modal-due">
                    <div class="pos-sub">Total Due</div>
                    <div class="pos-modal-amount pos-numr" x-text="formatMoney(modalDue)"></div>
                </div>

                <div style="margin-bottom:16px">
                    <label class="pos-lbl">Cash Tendered</label>
                    <input type="number" x-model.number="modalCashTendered" x-ref="cashTenderedInput" min="0" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="pos-in pos-in-lg" style="text-align:right" placeholder="0.00" />
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px">
                    <button type="button" @click="modalCashTendered = modalDue" class="pos-btn pos-btn-ghost pos-btn-sm">Exact</button>
                    <template x-for="denom in [10, 20, 50, 100]" :key="denom">
                        <button type="button" @click="modalCashTendered = denom" class="pos-btn pos-btn-ghost pos-btn-sm"
                            x-text="formatMoney(denom)"></button>
                    </template>
                </div>

                <div class="pos-modal-status"
                    :class="modalCashTendered >= modalDue ? 'pos-modal-status--ok' : 'pos-modal-status--err'"
                    x-show="modalCashTendered > 0">
                    <template x-if="modalCashTendered >= modalDue">
                        <div>
                            <div class="pos-sub pos-bold">Change to Give</div>
                            <div class="pos-modal-amount pos-pos" x-text="formatMoney(modalCashTendered - modalDue)"></div>
                        </div>
                    </template>
                    <template x-if="modalCashTendered > 0 && modalCashTendered < modalDue">
                        <div>
                            <div class="pos-sub pos-bold pos-neg">Insufficient — Remaining</div>
                            <div class="pos-modal-amount pos-neg" x-text="formatMoney(modalDue - modalCashTendered)"></div>
                        </div>
                    </template>
                </div>

                <div class="pos-modal-actions">
                    <button type="button" @click="closeModal()" class="pos-btn pos-btn-ghost pos-modal-cancel">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalCashTendered || modalCashTendered < modalDue"
                        class="pos-btn pos-btn-cash pos-modal-proceed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Card) ===== --}}
        <div x-show="showModal && modalType === 'card'" x-cloak class="pos-overlay" @keydown.escape.window="closeModal()">
            <div class="pos-modal" @click.stop>
                <div class="pos-modal-h">Card Payment</div>
                <div class="pos-modal-sub">Enter card payment details.</div>

                <div class="pos-modal-due">
                    <div class="pos-sub">Balance Due</div>
                    <div class="pos-modal-amount pos-numr" x-text="formatMoney(modalDue)"></div>
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="cardAmountInput" min="0.01" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="pos-in pos-in-lg" style="text-align:right" placeholder="0.00" />
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="pos-in" placeholder="e.g. TXN12345" />
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Account Name</label>
                    <input type="text" x-model="modalAccountName" class="pos-in" placeholder="Cardholder name" />
                </div>

                <div style="margin-bottom:16px">
                    <label class="pos-lbl">Financial Institution</label>
                    <select x-model="modalInstitution" class="pos-in">
                        <option value="">Select bank...</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->name }}">{{ $bank->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pos-modal-actions">
                    <button type="button" @click="closeModal()" class="pos-btn pos-btn-ghost pos-modal-cancel">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0 || Math.abs(parseFloat(modalAmount) - modalDue) > 0.01"
                        class="pos-btn pos-btn-card pos-modal-proceed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== PAYMENT MODAL (Mobile Money) ===== --}}
        <div x-show="showModal && modalType === 'mobile_money'" x-cloak class="pos-overlay" @keydown.escape.window="closeModal()">
            <div class="pos-modal" @click.stop>
                <div class="pos-modal-h">Mobile Money Payment</div>
                <div class="pos-modal-sub">Enter mobile money payment details.</div>

                <div class="pos-modal-due">
                    <div class="pos-sub">Balance Due</div>
                    <div class="pos-modal-amount pos-numr" x-text="formatMoney(modalDue)"></div>
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Amount</label>
                    <input type="number" x-model.number="modalAmount" x-ref="mobileAmountInput" min="0.01" step="0.01"
                        @focus="$el.select()"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="pos-in pos-in-lg" style="text-align:right" placeholder="0.00" />
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Reference / Transaction No.</label>
                    <input type="text" x-model="modalReference"
                        @keydown.enter.prevent="confirmPaymentModal()"
                        class="pos-in" placeholder="e.g. MP123456" />
                </div>

                <div style="margin-bottom:12px">
                    <label class="pos-lbl">Account Name</label>
                    <input type="text" x-model="modalAccountName" class="pos-in" placeholder="Account holder name" />
                </div>

                <div style="margin-bottom:16px">
                    <label class="pos-lbl">Provider / Institution</label>
                    <select x-model="modalInstitution" class="pos-in">
                        <option value="">Select provider...</option>
                        @foreach($mobileProviders as $provider)
                            <option value="{{ $provider->name }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pos-modal-actions">
                    <button type="button" @click="closeModal()" class="pos-btn pos-btn-ghost pos-modal-cancel">Cancel</button>
                    <button type="button" @click="confirmPaymentModal()"
                        :disabled="!modalAmount || modalAmount <= 0 || Math.abs(parseFloat(modalAmount) - modalDue) > 0.01"
                        class="pos-btn pos-btn-mobile pos-modal-proceed">Proceed</button>
                </div>
            </div>
        </div>

        {{-- ===== SPLIT PAYMENT MODAL ===== --}}
        <div x-show="showModal && modalType === 'split'" x-cloak class="pos-overlay" @keydown.escape.window="closeModal()">
            <div class="pos-modal pos-modal--wide" @click.stop>
                <div class="pos-modal-h">Split Payment</div>
                <div class="pos-modal-sub">Allocate the total due across multiple payment methods.</div>

                <div class="pos-modal-due">
                    <div class="pos-sub">Total Due</div>
                    <div class="pos-modal-amount pos-numr" x-text="formatMoney(modalDue)"></div>
                </div>

                {{-- Cash Row --}}
                <div class="pos-split-row" :class="splitCashEnabled ? 'pos-split-row--active' : ''">
                    <label class="pos-split-label">
                        <input type="checkbox" x-model="splitCashEnabled" class="pos-checkbox pos-checkbox--cash">
                        <div class="pos-split-dot" style="background:var(--pos-green)"></div>
                        <span class="pos-bold">Cash</span>
                    </label>
                    <div x-show="splitCashEnabled" x-transition style="margin-top:12px">
                        <div class="pos-g2">
                            <div>
                                <label class="pos-lbl-sm">Allocate to Cash</label>
                                <input type="number" x-model.number="splitCashAlloc" min="0" step="0.01"
                                    @focus="$el.select()" class="pos-in pos-in-sm" style="text-align:right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="pos-lbl-sm">Cash Tendered</label>
                                <input type="number" x-model.number="splitCashTendered" min="0" step="0.01"
                                    @focus="$el.select()" class="pos-in pos-in-sm" style="text-align:right" placeholder="0.00" />
                            </div>
                        </div>
                        <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center" x-show="splitCashTendered > 0 && splitCashAlloc > 0">
                            <span class="pos-sub">Change</span>
                            <span class="pos-bold"
                                :class="splitCashTendered >= splitCashAlloc ? 'pos-pos' : 'pos-neg'"
                                x-text="splitCashTendered >= splitCashAlloc ? formatMoney(splitCashTendered - splitCashAlloc) : 'Short by ' + formatMoney(splitCashAlloc - splitCashTendered)"></span>
                        </div>
                    </div>
                </div>

                {{-- Card Row --}}
                <div class="pos-split-row" :class="splitCardEnabled ? 'pos-split-row--active' : ''">
                    <label class="pos-split-label">
                        <input type="checkbox" x-model="splitCardEnabled" class="pos-checkbox pos-checkbox--card">
                        <div class="pos-split-dot" style="background:var(--pos-amber)"></div>
                        <span class="pos-bold">Card</span>
                    </label>
                    <div x-show="splitCardEnabled" x-transition style="margin-top:12px">
                        <div class="pos-g2">
                            <div>
                                <label class="pos-lbl-sm">Amount</label>
                                <input type="number" x-model.number="splitCardAmount" min="0" step="0.01"
                                    @focus="$el.select()" class="pos-in pos-in-sm" style="text-align:right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="pos-lbl-sm">Reference / Transaction No.</label>
                                <input type="text" x-model="splitCardRef" class="pos-in pos-in-sm" placeholder="e.g. TXN12345" />
                            </div>
                        </div>
                        <div class="pos-g2" style="margin-top:8px">
                            <div>
                                <label class="pos-lbl-sm">Account Name</label>
                                <input type="text" x-model="splitCardAccountName" class="pos-in pos-in-sm" placeholder="Cardholder name" />
                            </div>
                            <div>
                                <label class="pos-lbl-sm">Financial Institution</label>
                                <select x-model="splitCardInstitution" class="pos-in pos-in-sm">
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
                <div class="pos-split-row" :class="splitMobileEnabled ? 'pos-split-row--active' : ''">
                    <label class="pos-split-label">
                        <input type="checkbox" x-model="splitMobileEnabled" class="pos-checkbox pos-checkbox--mobile">
                        <div class="pos-split-dot" style="background:var(--pos-sec)"></div>
                        <span class="pos-bold">Mobile Money</span>
                    </label>
                    <div x-show="splitMobileEnabled" x-transition style="margin-top:12px">
                        <div class="pos-g2">
                            <div>
                                <label class="pos-lbl-sm">Amount</label>
                                <input type="number" x-model.number="splitMmobileAmount" min="0" step="0.01"
                                    @focus="$el.select()" class="pos-in pos-in-sm" style="text-align:right" placeholder="0.00" />
                            </div>
                            <div>
                                <label class="pos-lbl-sm">Reference / Transaction No.</label>
                                <input type="text" x-model="splitMobileRef" class="pos-in pos-in-sm" placeholder="e.g. MP123456" />
                            </div>
                        </div>
                        <div class="pos-g2" style="margin-top:8px">
                            <div>
                                <label class="pos-lbl-sm">Account Name</label>
                                <input type="text" x-model="splitMobileAccountName" class="pos-in pos-in-sm" placeholder="Account holder name" />
                            </div>
                            <div>
                                <label class="pos-lbl-sm">Provider / Institution</label>
                                <select x-model="splitMobileInstitution" class="pos-in pos-in-sm">
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
                <div class="pos-modal-status"
                    :class="getSplitRemaining() === 0 ? 'pos-modal-status--ok' : getSplitRemaining() > 0 ? 'pos-modal-status--warn' : 'pos-modal-status--err'">
                    <div class="pos-sub pos-bold"
                        :class="getSplitRemaining() === 0 ? 'pos-pos' : getSplitRemaining() > 0 ? 'pos-warn' : 'pos-neg'"
                        x-text="getSplitRemaining() === 0 ? 'Balanced' : getSplitRemaining() > 0 ? 'Remaining Balance' : 'Over-allocated'"></div>
                    <div class="pos-modal-amount"
                        :class="getSplitRemaining() === 0 ? 'pos-pos' : getSplitRemaining() > 0 ? 'pos-warn' : 'pos-neg'"
                        x-text="formatMoney(Math.abs(getSplitRemaining()))"></div>
                </div>

                <div class="pos-modal-actions">
                    <button type="button" @click="closeModal()" class="pos-btn pos-btn-ghost pos-modal-cancel">Cancel</button>
                    <button type="button" @click="confirmSplitPayment()"
                        :disabled="getSplitRemaining() !== 0 || !splitPaymentValid()"
                        class="pos-btn pos-btn-split pos-modal-proceed">Proceed</button>
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

        window.setWalkInCustomer = function() {
            const walkInId = '{{ $walkInCustomer?->id ?? '' }}';
            const walkInName = '{{ $walkInCustomer?->name ?? '' }}';
            window.dispatchEvent(new CustomEvent('pos-customer-selected', {
                detail: { id: walkInId, label: walkInName },
            }));
            const host = document.querySelector('.pos-customer-picker [data-scoped-search-field]');
            if (host && window.Alpine) {
                const comp = Alpine.$data(host);
                if (comp && typeof comp.setValue === 'function') {
                    comp.setValue(walkInId, walkInName);
                } else if (comp && typeof comp.clear === 'function') {
                    comp.clear();
                }
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
                customerId: '{{ $walkInCustomer?->id ?? '' }}',
                walkInCustomerId: '{{ $walkInCustomer?->id ?? '' }}',
                walkInCustomerName: '{{ $walkInCustomer?->name ?? '' }}',
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
                bottleCreditAvailable: 0,
                bottleCreditApplied: 0,
                bottleReturnableIds: [],

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
                        this.fetchBottleCredit();
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

                setWalkInCustomer() {
                    this.customerId = this.walkInCustomerId;
                    this.bottleCreditAvailable = 0;
                    this.bottleCreditApplied = 0;
                    this.bottleReturnableIds = [];
                    const host = document.querySelector('.pos-customer-picker [data-scoped-search-field]');
                    if (host && window.Alpine) {
                        const comp = Alpine.$data(host);
                        if (comp) {
                            comp.selectedId = this.walkInCustomerId;
                            comp.selectedLabel = this.walkInCustomerName;
                            comp.query = this.walkInCustomerName;
                            comp.results = [];
                            comp.open = false;
                        }
                    }
                    this.fetchBottleCredit();
                },

                async fetchBottleCredit() {
                    this.bottleCreditAvailable = 0;
                    this.bottleCreditApplied = 0;
                    this.bottleReturnableIds = [];
                    if (!this.customerId) return;
                    try {
                        const url = '{{ route("pos.returnables.credit-check") }}' + '?customer_id=' + this.customerId;
                        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await resp.json();
                        this.bottleCreditAvailable = parseFloat(data.available_credit) || 0;
                    } catch (e) { /* silent */ }
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
                    const creditApplied = this.bottleCreditApplied || 0;
                    const paid = this.payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0) + creditApplied;
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
                    document.getElementById('pos-error').style.display = 'none';

                    const payload = {
                        terminal_id: {{ session('pos_terminal_id') ?? 0 }},
                        cashier_session_id: null,
                        customer_id: this.customerId || null,
                        reference: this.reference || null,
                        bottle_credit_applied: this.bottleCreditApplied || 0,
                        bottle_returnable_ids: this.bottleReturnableIds || [],
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
                        errDiv.style.display = 'block';
                        errDiv.className = 'pos-alert pos-alert-warn';
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
                            errDiv.style.display = 'block';
                            errDiv.className = 'pos-alert pos-alert-error';
                        }
                    } catch (e) {
                        const errDiv = document.getElementById('pos-error');
                        document.getElementById('pos-error-text').textContent = 'An unexpected error occurred.';
                        errDiv.style.display = 'block';
                        errDiv.className = 'pos-alert pos-alert-error';
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>

    <script src="/js/pos-offline-queue.js"></script>
    <script>
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
