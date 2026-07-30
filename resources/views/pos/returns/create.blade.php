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

            <div class="form-page">
                <div class="form-page-main">
                    <form method="POST" action="{{ route('pos.returns.store') }}" x-data="returnForm()">
                        @csrf

                        <div class="card p-6 mb-6">
                            <x-form.section number="01" :title="__('Return Details')" />
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
                        ['title' => __('POS Dashboard'), 'route' => route('pos.dashboard'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z\"/></svg>'],
                        ['title' => __('Returns List'), 'route' => route('pos.returns.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
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
