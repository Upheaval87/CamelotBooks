<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New POS Return') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Return Details') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="sale_id" value="{{ __('Original Sale') }}" />
                            <select id="sale_id" name="pos_sale_id" x-model="selectedSaleId" @change="loadSale()"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">-- Select Sale --</option>
                                @foreach($sales as $s)
                                    <option value="{{ $s->id }}" {{ old('pos_sale_id', request('sale_id')) == $s->id ? 'selected' : '' }}>
                                        {{ $s->sale_number }} – ${{ number_format($s->total, 2) }} – {{ optional($s->created_at)->format('M d, Y') ?? '—' }}
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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Items to Return') }}</h3>
                    <p class="text-sm text-gray-600 mb-4" x-show="!saleLoaded">Select a sale above to load its items.</p>

                    <div x-show="saleLoaded" x-cloak>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Tax Rate</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty Returning</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(line, idx) in saleLines" :key="line.id">
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900" x-text="line.product?.name"></td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900" x-text="parseFloat(line.quantity).toFixed(4)"></td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900">$<span x-text="parseFloat(line.unit_price).toFixed(2)"></span></td>
                                            <td class="px-4 py-2 text-sm text-right text-gray-900" x-text="parseFloat(line.tax_rate).toFixed(2) + '%'"></td>
                                            <td class="px-4 py-2 text-sm text-right">
                                                <input type="hidden" :name="'lines[' + idx + '][pos_sale_line_id]'" :value="line.id">
                                                <input type="number" :name="'lines[' + idx + '][quantity_returned]'" x-model.number="line.qtyReturn"
                                                    :max="line.quantity" min="0" step="0.0001"
                                                    class="w-28 text-right border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-sm font-semibold text-gray-900 text-right">Refund Total:</td>
                                        <td class="px-4 py-2 text-sm font-bold text-red-600 text-right">-$<span x-text="refundTotal.toFixed(2)"></span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('pos.returns.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">{{ __('Cancel') }}</a>
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
