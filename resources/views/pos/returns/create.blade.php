<x-app-layout>
    <x-slot name="header">{{ __('New POS Return') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pos.returns.store') }}" x-data="returnForm()">
                @csrf

                <div class="card p-6 mb-6">
                    <div class="form-section-label">1 · Return Details</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="sale_id" value="{{ __('Original Sale') }}" />
                            <select id="sale_id" name="pos_sale_id" x-model="selectedSaleId" @change="loadSale()"
                                class="input mt-1" required>
                                <option value="">-- Select Sale --</option>
                                @foreach($sales as $s)
                                    <option value="{{ $s->id }}" {{ old('pos_sale_id', request('sale_id')) == $s->id ? 'selected' : '' }}>
                                        {{ $s->sale_number }} – @money($s->total) – {{ optional($s->created_at)->format('M d, Y') ?? '—' }}
                                    </option>
                                @endforeach
                            </select>
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
                    <div class="form-section-label">2 · Items to Return</div>
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
                </div>

                <div class="flex items-center justify-end gap-3">
                    <x-button variant="ghost" href="{{ route('pos.returns.index') }}">{{ __('Cancel') }}</x-button>
                    <x-primary-button type="submit" x-bind:disabled="refundTotal <= 0 || submitting" x-on:click="submitting = true">
                        <span x-show="!submitting">{{ __('Post Return') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Processing...') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
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
                    if (this.selectedSaleId) {
                        this.loadSale();
                    }
                }
            }
        }
    </script>
</x-app-layout>
