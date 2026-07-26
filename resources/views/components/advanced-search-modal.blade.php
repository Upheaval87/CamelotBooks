@props([
    'name',
    'items' => [],
    'valueKey' => 'id',
    'labelKey' => 'name',
    'showFields' => [],
    'categories' => [],
    'types' => [],
])

<x-modal name="advanced-search-{{ $name }}" maxWidth="2xl">
    <div
        x-data="{
            query: '',
            categoryFilter: '',
            typeFilter: '',
            priceMin: '',
            priceMax: '',
            allItems: {{ $items->values()->toJson() }},
            get filteredItems() {
                return this.allItems.filter(item => {
                    if (this.query) {
                        const q = this.query.toLowerCase();
                        const matchesSearch = Object.values(item).some(v =>
                            v && String(v).toLowerCase().includes(q)
                        );
                        if (!matchesSearch) return false;
                    }
                    if (this.categoryFilter && item.category_id != this.categoryFilter) return false;
                    if (this.typeFilter && item.type !== this.typeFilter) return false;
                    if (this.priceMin && (parseFloat(item.sales_price) || 0) < parseFloat(this.priceMin)) return false;
                    if (this.priceMax && (parseFloat(item.sales_price) || 0) > parseFloat(this.priceMax)) return false;
                    return true;
                });
            },
            selectItem(item) {
                window.dispatchEvent(new CustomEvent('advanced-search-selected', {
                    detail: { targetName: '{{ $name }}', item: item }
                }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'advanced-search-{{ $name }}' });
            }
        }"
        class="p-6"
    >
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Advanced Search</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" x-model="query" placeholder="Type to filter..." class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
            @if(count($categories) > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                    <select x-model="categoryFilter" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">All</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(count($types) > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                    <select x-model="typeFilter" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="">All</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Min Price</label>
                <input type="number" x-model="priceMin" min="0" step="0.01" placeholder="0.00" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Max Price</label>
                <input type="number" x-model="priceMax" min="0" step="0.01" placeholder="999999" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
        </div>

        <div class="text-xs text-gray-500 mb-2" x-text="filteredItems.length + ' item(s) found'"></div>

        <div class="max-h-80 overflow-auto border border-gray-200 rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="item in filteredItems" :key="item.{{ $valueKey }}">
                        <tr class="hover:bg-indigo-50 cursor-pointer" @click="selectItem(item)">
                            <td class="px-3 py-2 text-sm font-medium text-gray-900" x-text="item.{{ $labelKey }}"></td>
                            <td class="px-3 py-2 text-sm text-gray-500" x-text="item.sku || '—'"></td>
                            <td class="px-3 py-2 text-sm text-gray-900 text-right" x-text="item.sales_price ? formatMoney(parseFloat(item.sales_price)) : '—'"></td>
                            <td class="px-3 py-2 text-sm text-gray-900 text-right" x-text="item.stock_qty !== null && item.stock_qty !== undefined ? item.stock_qty : '—'"></td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium" @click.stop="selectItem(item)">Select</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredItems.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No items match your filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" @click="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'advanced-search-{{ $name }}' }))"
                class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Close
            </button>
        </div>
    </div>
</x-modal>
