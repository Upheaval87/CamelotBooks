<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>New POS Return</h1>
                <div class="pos-sub">Select a sale and return items for a refund</div>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.returns.index') }}" class="pos-btn pos-btn-ghost">Cancel</a>
                <button type="submit" form="return-form" class="pos-btn pos-btn-cta" x-bind:disabled="refundTotal <= 0 || submitting" x-on:click="submitting = true">
                    <span x-show="!submitting">Post Return</span>
                    <span x-show="submitting" x-cloak>Processing…</span>
                </button>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                @if($errors->any())
                    <div class="pos-card" style="margin-bottom:16px;border:1px solid var(--pos-red)">
                        <div class="pos-pad">
                            <ul style="margin:0;padding-left:16px;color:var(--pos-red);font-size:13px">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form id="return-form" method="POST" action="{{ route('pos.returns.store') }}" x-data="returnForm()">
                    @csrf

                    {{-- Section: Return Details --}}
                    <div class="pos-card" style="margin-bottom:16px">
                        <div class="pos-card-h">
                            <span class="pos-step">01 · Return Details</span>
                        </div>
                        <div class="pos-pad">
                            <div class="pos-g2">
                                <div class="pos-f">
                                    <label>Original Sale <span style="color:var(--pos-red)">*</span></label>
                                    @php
                                        $saleItems = collect($sales)->map(fn($s) => [
                                            'id' => $s->id,
                                            'label' => $s->sale_number,
                                            'subtitle' => ($s->created_at?->format('M d, Y') ?? '—'),
                                        ]);
                                        $selectedSale = old('pos_sale_id', request('sale_id')) ? $sales->firstWhere('id', (int) old('pos_sale_id', request('sale_id'))) : null;
                                    @endphp
                                    <x-scoped-search-field
                                        name="pos_sale_id"
                                        mode="client"
                                        :items="$saleItems"
                                        :value="old('pos_sale_id', request('sale_id'))"
                                        :label="$selectedSale ? ($selectedSale->sale_number . ' – ' . ($selectedSale->created_at?->format('M d, Y') ?? '—')) : ''"
                                        placeholder="{{ __('-- Select Sale --') }}"
                                        on-select="posReturnSaleSelected"
                                        required
                                    />
                                </div>
                                <div class="pos-f">
                                    <label>Return Date <span style="color:var(--pos-red)">*</span></label>
                                    <input type="date" name="date" class="pos-in" value="{{ old('date', now()->toDateString()) }}" required>
                                </div>
                            </div>
                            <div class="pos-f" style="margin-top:12px">
                                <label>Reason</label>
                                <input type="text" name="reason" class="pos-in" value="{{ old('reason') }}" placeholder="e.g. Customer changed mind, defective item">
                            </div>
                        </div>
                    </div>

                    {{-- Section: Items to Return --}}
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">02 · Items to Return</span>
                        </div>
                        <div class="pos-pad">
                            <p class="pos-sub" x-show="!saleLoaded">Select a sale above to load its items.</p>

                            <div x-show="saleLoaded" x-cloak>
                                <div class="pos-li-wrap">
                                    <table class="pos-tbl">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="num">Qty Sold</th>
                                                <th class="num">Unit Price</th>
                                                <th class="num">Tax Rate</th>
                                                <th class="num">Qty Returning</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(line, idx) in saleLines" :key="line.id">
                                                <tr>
                                                    <td x-text="line.product?.name"></td>
                                                    <td class="num" x-text="parseFloat(line.quantity).toFixed(4)"></td>
                                                    <td class="num">{{ $cs }}<span x-text="parseFloat(line.unit_price).toFixed(2)"></span></td>
                                                    <td class="num" x-text="parseFloat(line.tax_rate).toFixed(2) + '%'"></td>
                                                    <td class="num">
                                                        <input type="hidden" :name="'lines[' + idx + '][pos_sale_line_id]'" :value="line.id">
                                                        <input type="number" :name="'lines[' + idx + '][quantity_returned]'" x-model.number="line.qtyReturn"
                                                            :max="line.quantity" min="0" step="0.0001"
                                                            class="pos-in" style="width:100px;text-align:right">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="num">Refund Total:</td>
                                                <td class="num pos-bold" style="color:var(--pos-red)">-{{ $cs }}<span x-text="refundTotal.toFixed(2)"></span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.dashboard') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        POS Dashboard
                    </a>
                    <a href="{{ route('pos.returns.index') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                        Returns List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.posReturnSaleSelected = function(id, item) {
            window.dispatchEvent(new CustomEvent('pos-return-sale-selected', { detail: { id: id } }));
        };

        function returnForm() {
            return {
                selectedSaleId: '{{ old("pos_sale_id", request("sale_id")) }}',
                saleLines: [],
                saleLoaded: false,
                submitting: false,

                get refundTotal() {
                    return this.saleLines.reduce((sum, line) => {
                        const qty = parseFloat(line.qtyReturn) || 0;
                        const unitPrice = parseFloat(line.unit_price);
                        const taxRate = parseFloat(line.tax_rate) / 100;
                        const lineSubtotal = qty * unitPrice;
                        return sum + lineSubtotal + (lineSubtotal * taxRate);
                    }, 0);
                },

                async loadSale() {
                    if (!this.selectedSaleId) {
                        this.saleLines = [];
                        this.saleLoaded = false;
                        return;
                    }
                    try {
                        const resp = await fetch(`/accounting/pos/sales/${this.selectedSaleId}/lines-json`);
                        const data = await resp.json();
                        this.saleLines = data.lines.map(l => ({
                            ...l,
                            qtyReturn: 0
                        }));
                        this.saleLoaded = true;
                    } catch (e) {
                        this.saleLines = [];
                        this.saleLoaded = false;
                    }
                },

                init() {
                    window.addEventListener('pos-return-sale-selected', (e) => {
                        this.selectedSaleId = e.detail.id;
                        this.loadSale();
                    });
                    if (this.selectedSaleId) {
                        this.loadSale();
                    }
                }
            }
        }
    </script>
</x-app-layout>
