<x-app-layout>
    <x-slot name="header">{{ __('Manage UOM Conversions:') }} {{ $product->name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.uom-conversions.update', $product) }}">
                            @csrf
                            @method('PUT')

                            <x-form.section number="01" :title="__('UOM Conversions')" />
                            <p class="text-sm text-gray-600 mb-4">SKU: <span class="font-medium">{{ $product->sku }}</span> &middot; Type: <span class="font-medium">{{ ucfirst($product->type) }}</span></p>

                            <div x-data="{
                                uoms: {{ Js::from($product->uomConversions->map(fn($u) => [
                                    'uom_name' => $u->uom_name,
                                    'conversion_factor' => $u->conversion_factor,
                                    'purchase_price' => $u->purchase_price,
                                    'sales_price' => $u->sales_price,
                                    'is_base' => (bool)$u->is_base,
                                ])->values()->values()) }},
                                addUom() { this.uoms.push({ uom_name: '', conversion_factor: 1, purchase_price: null, sales_price: null, is_base: false }); },
                                removeUom(i) { if (this.uoms.length > 1) this.uoms.splice(i, 1); }
                            }">
                                <template x-for="(uom, index) in uoms" :key="index">
                                    <div class="border rounded-lg p-4 mb-4 bg-gray-50">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-sm font-medium text-gray-700" x-text="'UOM #' + (index + 1)"></span>
                                            <button type="button" @click="removeUom(index)" x-show="uoms.length > 1" class="text-red-500 hover:text-red-700" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        </div>
                                        <input type="hidden" :name="'uoms['+index+'][is_base]'" value="0">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 uppercase">UOM Name</label>
                                                <input :name="'uoms['+index+'][uom_name]'" x-model="uom.uom_name" type="text" required
                                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="e.g. Carton, Box">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 uppercase">Conversion Factor</label>
                                                <input :name="'uoms['+index+'][conversion_factor]'" x-model.number="uom.conversion_factor" type="number" step="0.01" min="0.01" required
                                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 uppercase">Purchase Price</label>
                                                <input :name="'uoms['+index+'][purchase_price]'" x-model.number="uom.purchase_price" type="number" step="0.01" min="0"
                                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 uppercase">Sales Price</label>
                                                <input :name="'uoms['+index+'][sales_price]'" x-model.number="uom.sales_price" type="number" step="0.01" min="0"
                                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                            </div>
                                            <div class="col-span-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" :name="'uoms['+index+'][is_base]'" value="1" x-model="uom.is_base" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                                    <span class="ml-2 text-sm text-gray-600">Base Unit of Measure</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <button type="button" @click="addUom()" class="mb-6 inline-flex items-center px-3 py-1.5 border border-dashed border-gray-400 rounded-md text-sm text-gray-600 hover:border-indigo-500 hover:text-indigo-600">+ Add UOM</button>

                                <div class="border-t pt-4 flex justify-end mt-8 gap-3">
                                    <x-button variant="ghost" href="{{ route('accounting.uom-conversions.index') }}">{{ __('Cancel') }}</x-button>
                                    <x-primary-button type="submit">{{ __('Save UOM Conversions') }}</x-primary-button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
