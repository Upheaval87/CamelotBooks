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
    <div class="p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Advanced Search</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <input type="text" id="as-{{ $name }}-q" placeholder="Type to filter..."
                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
            @if(count($categories) > 0)
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
                    <select id="as-{{ $name }}-cat" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                    <select id="as-{{ $name }}-type" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
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
                <input type="number" id="as-{{ $name }}-pmin" min="0" step="0.01" placeholder="0.00"
                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Max Price</label>
                <input type="number" id="as-{{ $name }}-pmax" min="0" step="0.01" placeholder="999999"
                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            </div>
        </div>

        <div class="flex items-center gap-3 mb-4">
            <button type="button" onclick="advSearch_{{ $name }}()"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                Search
            </button>
            <button type="button" onclick="advReset_{{ $name }}()"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Reset Filters
            </button>
            <span class="text-xs text-gray-500 ml-auto" id="as-count-{{ $name }}"></span>
        </div>

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
                <tbody id="as-body-{{ $name }}" class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" onclick="window.dispatchEvent(new CustomEvent('close-modal', { detail: 'advanced-search-{{ $name }}' }))"
                class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Close
            </button>
        </div>
    </div>

    <script>
    (function() {
        var name = '{{ $name }}';
        var valueKey = '{{ $valueKey }}';
        var labelKey = '{{ $labelKey }}';
        var allItems = {!! $items->values()->toJson() !!};

        function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

        function render(items) {
            var tbody = document.getElementById('as-body-' + name);
            var count = document.getElementById('as-count-' + name);
            if (!tbody) return;
            count.textContent = items.length + ' item(s) found';
            var h = '';
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var id = item[valueKey];
                var label = esc(item[labelKey] || '');
                var sku = item.sku ? esc(item.sku) : '&mdash;';
                var price = item.sales_price ? (typeof formatMoney === 'function' ? formatMoney(parseFloat(item.sales_price)) : parseFloat(item.sales_price).toFixed(2)) : '&mdash;';
                var stock = (item.stock_qty !== null && item.stock_qty !== undefined) ? item.stock_qty : '&mdash;';
                h += '<tr class="hover:bg-indigo-50 cursor-pointer" data-adv-id="' + id + '">' +
                    '<td class="px-3 py-2 text-sm font-medium text-gray-900">' + label + '</td>' +
                    '<td class="px-3 py-2 text-sm text-gray-500">' + sku + '</td>' +
                    '<td class="px-3 py-2 text-sm text-gray-900 text-right">' + price + '</td>' +
                    '<td class="px-3 py-2 text-sm text-gray-900 text-right">' + stock + '</td>' +
                    '<td class="px-3 py-2 text-center"><button type="button" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium" data-adv-select="' + i + '">Select</button></td>' +
                '</tr>';
            }
            if (items.length === 0) {
                h = '<tr><td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500">No items match your filters.</td></tr>';
            }
            tbody.innerHTML = h;
        }

        function getItems() { return allItems; }

        function doFilter() {
            var q = (document.getElementById('as-' + name + '-q').value || '').toLowerCase().trim();
            var cat = document.getElementById('as-' + name + '-cat') ? document.getElementById('as-' + name + '-cat').value : '';
            var typ = document.getElementById('as-' + name + '-type') ? document.getElementById('as-' + name + '-type').value : '';
            var pmin = parseFloat(document.getElementById('as-' + name + '-pmin').value) || 0;
            var pmax = parseFloat(document.getElementById('as-' + name + '-pmax').value);
            if (isNaN(pmax)) pmax = Infinity;

            var result = [];
            for (var i = 0; i < allItems.length; i++) {
                var item = allItems[i];
                if (q) {
                    var found = false;
                    var keys = Object.keys(item);
                    for (var k = 0; k < keys.length; k++) {
                        var v = item[keys[k]];
                        if (v && String(v).toLowerCase().indexOf(q) !== -1) { found = true; break; }
                    }
                    if (!found) continue;
                }
                if (cat && String(item.category_id) !== cat) continue;
                if (typ && item.type !== typ) continue;
                var price = parseFloat(item.sales_price) || 0;
                if (price < pmin) continue;
                if (price > pmax) continue;
                result.push(item);
            }
            render(result);
        }

        window['advSearch_' + name] = function() { doFilter(); };
        window['advReset_' + name] = function() {
            document.getElementById('as-' + name + '-q').value = '';
            var catEl = document.getElementById('as-' + name + '-cat');
            if (catEl) catEl.value = '';
            var typEl = document.getElementById('as-' + name + '-type');
            if (typEl) typEl.value = '';
            document.getElementById('as-' + name + '-pmin').value = '';
            document.getElementById('as-' + name + '-pmax').value = '';
            render(allItems);
        };

        var tbody = document.getElementById('as-body-' + name);
        if (tbody) {
            tbody.addEventListener('click', function(e) {
                var row = e.target.closest('tr[data-adv-id]');
                if (!row) return;
                var id = row.getAttribute('data-adv-id');
                var item = null;
                for (var i = 0; i < allItems.length; i++) {
                    if (String(allItems[i][valueKey]) === id) { item = allItems[i]; break; }
                }
                if (!item) return;
                window.dispatchEvent(new CustomEvent('advanced-search-selected', {
                    detail: { targetName: name, item: item }
                }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'advanced-search-' + name }));
            });
        }

        render(allItems);
    })();
    </script>
</x-modal>
