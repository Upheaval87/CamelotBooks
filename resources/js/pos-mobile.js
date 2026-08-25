/**
 * posMobileSell — Alpine data component for the mobile POS Sell/Checkout page.
 * Combines §7 (Sell) and §8 (Checkout) into a single swipeable experience.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('posMobileSell', (config) => ({
        // Data
        allProducts: config.products || [],
        categories: config.categories || [],
        paymentMethods: config.paymentMethods || [],
        customers: config.customers || [],
        bankAccounts: config.bankAccounts || [],
        mobileProviders: config.mobileProviders || [],
        walkInCustomerId: config.walkInCustomerId || '',
        terminalId: config.terminalId || 0,
        storeUrl: config.storeUrl || '',
        receiptUrl: config.receiptUrl || '',

        // Search & filter
        searchQuery: '',
        activeCategory: '',
        displayedProducts: [],

        // Cart
        cart: [],
        showCartDrawer: false,

        // Page (0=cart, 1=payment)
        page: 0,

        // Payment
        selectedMethod: '',
        selectedMethodId: '',
        selectedMethodName: '',
        cashTendered: 0,
        paymentRef: '',
        paymentAcctName: '',
        paymentInstitution: '',
        customerId: config.walkInCustomerId || '',

        // Split
        showSplitModal: false,
        splitCashEnabled: false,
        splitCashAlloc: 0,
        splitCashTendered: 0,
        splitCardEnabled: false,
        splitCardAmount: 0,
        splitCardRef: '',
        splitMobileEnabled: false,
        splitMobileAmount: 0,
        splitMobileRef: '',

        // State
        submitting: false,
        errorMessage: '',

        init() {
            this.filterProducts();
        },

        // ─── §7.2 Product search + filter ───
        filterProducts() {
            let list = [...this.allProducts];

            // Category filter
            if (this.activeCategory !== '') {
                list = list.filter(p => p.category_id == this.activeCategory);
            }

            // Search filter
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p =>
                    p.name.toLowerCase().includes(q) ||
                    p.sku.toLowerCase().includes(q) ||
                    (p.barcode && p.barcode.includes(q))
                );
            }

            this.displayedProducts = list;
        },

        // ─── §7.3 Add to cart ───
        addToCart(product) {
            if (product.tracked_as_inventory && product.current_stock <= 0) return;

            const existing = this.cart.find(l => l.product_id === product.id);
            if (existing) {
                existing.quantity += 1;
                this.recalcLine(existing);
            } else {
                const line = {
                    product_id: product.id,
                    product_name: product.name,
                    sku: product.sku,
                    quantity: 1,
                    unit_price: parseFloat(product.sales_price) || 0,
                    discount_amount: 0,
                    discount_type: null,
                    tax_rate: parseFloat(product.tax_rate) || 0,
                    is_taxable: product.is_taxable,
                    tax_amount: 0,
                    line_total: parseFloat(product.sales_price) || 0,
                };
                this.cart.push(line);
            }
            this.cashTendered = this.cartTotal;
        },

        incrementCart(idx) {
            this.cart[idx].quantity += 1;
            this.recalcLine(this.cart[idx]);
            this.cashTendered = this.cartTotal;
        },

        decrementCart(idx) {
            if (this.cart[idx].quantity > 1) {
                this.cart[idx].quantity -= 1;
                this.recalcLine(this.cart[idx]);
                this.cashTendered = this.cartTotal;
            }
        },

        removeFromCart(idx) {
            this.cart.splice(idx, 1);
            this.cashTendered = this.cartTotal;
        },

        recalcLine(line) {
            const subtotal = line.quantity * line.unit_price;
            const afterDiscount = subtotal - (line.discount_amount || 0);
            line.tax_amount = line.is_taxable ? parseFloat((afterDiscount * (line.tax_rate / 100)).toFixed(2)) : 0;
            line.line_total = parseFloat((afterDiscount + line.tax_amount).toFixed(2));
        },

        // ─── §8.1 Totals ───
        get cartCount() {
            return this.cart.reduce((s, l) => s + l.quantity, 0);
        },

        get cartTotal() {
            return parseFloat(this.cart.reduce((s, l) => s + l.line_total, 0).toFixed(2));
        },

        getSubtotal() {
            return parseFloat(this.cart.reduce((s, l) => s + (l.quantity * l.unit_price), 0).toFixed(2));
        },

        getDiscount() {
            return parseFloat(this.cart.reduce((s, l) => s + (l.discount_amount || 0), 0).toFixed(2));
        },

        getTax() {
            return parseFloat(this.cart.reduce((s, l) => s + (l.tax_amount || 0), 0).toFixed(2));
        },

        getTotal() {
            return parseFloat(this.cart.reduce((s, l) => s + l.line_total, 0).toFixed(2));
        },

        getPaymentTotal() {
            if (this.selectedMethod === 'cash') {
                return this.getTotal();
            } else if (this.selectedMethod === 'split') {
                return this.getSplitAllocated();
            }
            return parseFloat(this.paymentAmount) || 0;
        },

        getBalanceDue() {
            return this.getTotal();
        },

        getChange() {
            if (this.selectedMethod !== 'cash') return 0;
            return parseFloat(((parseFloat(this.cashTendered) || 0) - this.getTotal()).toFixed(2));
        },

        // ─── §8.2 Can complete ───
        canCompleteSale() {
            if (this.cart.length === 0) return false;
            if (!this.selectedMethod) return false;
            if (this.submitting) return false;

            if (this.selectedMethod === 'cash') {
                return (parseFloat(this.cashTendered) || 0) >= this.getTotal();
            }
            if (this.selectedMethod === 'card' || this.selectedMethod === 'mobile_money') {
                return this.paymentRef.trim().length > 0;
            }
            if (this.selectedMethod === 'split') {
                return this.getSplitRemaining() === 0 && this.getSplitAllocated() >= this.getTotal();
            }
            return false;
        },

        // ─── Split payment ───
        openSplitModal() {
            this.splitCashEnabled = false;
            this.splitCashAlloc = 0;
            this.splitCashTendered = 0;
            this.splitCardEnabled = false;
            this.splitCardAmount = 0;
            this.splitCardRef = '';
            this.splitMobileEnabled = false;
            this.splitMobileAmount = 0;
            this.splitMobileRef = '';
            this.showSplitModal = true;
        },

        getSplitRemaining() {
            const alloc = (this.splitCashEnabled ? (parseFloat(this.splitCashAlloc) || 0) : 0)
                        + (this.splitCardEnabled ? (parseFloat(this.splitCardAmount) || 0) : 0)
                        + (this.splitMobileEnabled ? (parseFloat(this.splitMobileAmount) || 0) : 0);
            return parseFloat((this.getTotal() - alloc).toFixed(2));
        },

        getSplitAllocated() {
            return (this.splitCashEnabled ? (parseFloat(this.splitCashAlloc) || 0) : 0)
                 + (this.splitCardEnabled ? (parseFloat(this.splitCardAmount) || 0) : 0)
                 + (this.splitMobileEnabled ? (parseFloat(this.splitMobileAmount) || 0) : 0);
        },

        confirmSplit() {
            if (this.getSplitRemaining() !== 0) return;
            this.showSplitModal = false;
        },

        // ─── Barcode scan ───
        startScan() {
            const input = document.getElementById('pos-m-barcode-input');
            if (input) {
                input.value = '';
                input.focus();
            }
        },

        onBarcodeScan(event) {
            const code = event.target.value.trim();
            if (!code) return;

            const product = this.allProducts.find(p => p.barcode === code || p.sku === code);
            if (product) {
                this.addToCart(product);
            } else {
                this.errorMessage = 'Product not found for barcode: ' + code;
                setTimeout(() => this.errorMessage = '', 3000);
            }
            event.target.value = '';
        },

        // ─── Complete sale ───
        async completeSale() {
            if (!this.canCompleteSale()) return;

            this.submitting = true;
            this.errorMessage = '';

            const payments = this.buildPayments();

            const payload = {
                terminal_id: this.terminalId,
                cashier_session_id: null,
                customer_id: this.customerId || null,
                reference: null,
                lines: this.cart.map(l => ({
                    product_id: l.product_id,
                    quantity: l.quantity,
                    unit_price: l.unit_price,
                    discount_amount: l.discount_amount || 0,
                    discount_type: l.discount_type || null,
                    tax_rate: l.tax_rate || 0,
                    transaction_uom: null,
                    transaction_qty: null,
                    conversion_factor: null,
                })),
                payments: payments,
            };

            try {
                const resp = await fetch(this.storeUrl, {
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
                    const url = this.receiptUrl.replace('__ID__', data.sale_id);
                    window.location.href = url;
                } else {
                    this.errorMessage = data.message || 'Sale failed. Please try again.';
                    this.submitting = false;
                }
            } catch (e) {
                this.errorMessage = 'Network error. Please try again.';
                this.submitting = false;
            }
        },

        buildPayments() {
            const payments = [];

            if (this.selectedMethod === 'cash') {
                payments.push({
                    payment_method_id: this.getMethodId('cash'),
                    amount: this.getTotal(),
                    cash_tendered: parseFloat(this.cashTendered) || 0,
                    change_given: parseFloat(this.getChange()) || 0,
                    reference_number: null,
                });
            } else if (this.selectedMethod === 'card') {
                payments.push({
                    payment_method_id: this.getMethodId('card'),
                    amount: this.getTotal(),
                    cash_tendered: 0,
                    change_given: 0,
                    reference_number: this.paymentRef.trim(),
                    account_name: this.paymentAcctName.trim(),
                    institution: this.paymentInstitution,
                });
            } else if (this.selectedMethod === 'mobile_money') {
                payments.push({
                    payment_method_id: this.getMethodId('mobile_money'),
                    amount: this.getTotal(),
                    cash_tendered: 0,
                    change_given: 0,
                    reference_number: this.paymentRef.trim(),
                    account_name: this.paymentAcctName.trim(),
                    institution: this.paymentInstitution,
                });
            } else if (this.selectedMethod === 'split') {
                if (this.splitCashEnabled && this.splitCashAlloc > 0) {
                    payments.push({
                        payment_method_id: this.getMethodId('cash'),
                        amount: parseFloat(this.splitCashAlloc),
                        cash_tendered: parseFloat(this.splitCashTendered) || 0,
                        change_given: parseFloat((this.splitCashTendered - this.splitCashAlloc).toFixed(2)),
                        reference_number: null,
                    });
                }
                if (this.splitCardEnabled && this.splitCardAmount > 0) {
                    payments.push({
                        payment_method_id: this.getMethodId('card'),
                        amount: parseFloat(this.splitCardAmount),
                        cash_tendered: 0,
                        change_given: 0,
                        reference_number: this.splitCardRef.trim(),
                    });
                }
                if (this.splitMobileEnabled && this.splitMobileAmount > 0) {
                    payments.push({
                        payment_method_id: this.getMethodId('mobile_money'),
                        amount: parseFloat(this.splitMobileAmount),
                        cash_tendered: 0,
                        change_given: 0,
                        reference_number: this.splitMobileRef.trim(),
                    });
                }
            }

            return payments;
        },

        getMethodId(type) {
            const pm = this.paymentMethods.find(m => m.type === type);
            return pm ? pm.id : '';
        },

        formatNum(val) {
            return parseFloat(val || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
    }));
});
