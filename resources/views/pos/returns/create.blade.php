<x-app-layout>
    <x-list-header title="{{ __('New POS Return') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-feedback.alert>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('pos.returns.store') }}" x-data="returnForm()">
                        @csrf

                        <div class="card p-6 mb-6">
                            <x-form.section number="01" :title="__('Return Details')" />
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="sale_id" value="{{ __('Original Sale') }}" />
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
                                    <x-input-error :messages="$errors->get('pos_sale_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="date" value="{{ __('Return Date') }}" />
                                    <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->toDateString())" required />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="reason" value="{{ __('Reason') }}" />
                                    <x-text-input id="reason" name="reason" type="text" class="mt-1 block w-full" :value="old('reason')" placeholder="e.g. Customer changed mind, defective item" />
                                </div>
                            </div>
                        </div>

                        <div class="card p-6 mb-6">
                            <x-form.section number="02" :title="__('Items to Return')" />
                            <p class="text-sm text-ink-soft mb-4" x-show="!saleLoaded">Select a sale above to load its items.</p>

                            <div x-show="saleLoaded" x-cloak>
                                <div class="overflow-x-auto">
                                    <table class="datasheet">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-right">Qty Sold</th>
                                                <th class="text-right">Unit Price</th>
                                                <th class="text-right">Tax Rate</th>
                                                <th class="text-right">Qty Returning</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(line, idx) in saleLines" :key="line.id">
                                                <tr>
                                                    <td x-text="line.product?.name"></td>
                                                    <td class="numeric" x-text="parseFloat(line.quantity).toFixed(4)"></td>
                                                    <td class="numeric">$<span x-text="parseFloat(line.unit_price).toFixed(2)"></span></td>
                                                    <td class="numeric" x-text="parseFloat(line.tax_rate).toFixed(2) + '%'"></td>
                                                    <td class="text-right">
                                                        <input type="hidden" :name="'lines[' + idx + '][pos_sale_line_id]'" :value="line.id">
                                                        <input type="number" :name="'lines[' + idx + '][quantity_returned]'" x-model.number="line.qtyReturn"
                                                            :max="line.quantity" min="0" step="0.0001"
                                                            class="input w-28 text-right">
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-right font-semibold">Refund Total:</td>
                                                <td class="numeric font-bold text-red-600">-$<span x-text="refundTotal.toFixed(2)"></span></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('pos.returns.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit" x-bind:disabled="refundTotal <= 0 || submitting" x-on:click="submitting = true">
                                    <span x-show="!submitting">{{ __('Post Return') }}</span>
                                    <span x-show="submitting" x-cloak>{{ __('Processing...') }}</span>
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('POS Dashboard'), 'route' => route('pos.dashboard'), 'icon' => 'grid'],
                        ['title' => __('Returns List'), 'route' => route('pos.returns.index'), 'icon' => 'table-list'],
                    ]],
                ]" />
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
