<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('New Bill of Materials') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.assemblies.boms') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.assemblies.store-bom') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="assembly_product_id" value="{{ __('Assembly Product') }}" />
                            <x-scoped-search-field
                                name="assembly_product_id"
                                entity="product"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'product']) }}"
                                :value="old('assembly_product_id')"
                                placeholder="Search assembly products..."
                                :required="true"
                            />
                            <x-input-error :messages="$errors->get('assembly_product_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="bom_number" value="{{ __('BOM Number') }}" />
                                <x-text-input id="bom_number" name="bom_number" type="text" class="mt-1 block w-full" :value="old('bom_number')" required placeholder="e.g. BOM-001" />
                                <x-input-error :messages="$errors->get('bom_number')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="name" value="{{ __('Name (optional)') }}" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="e.g. Desktop Computer Assembly" />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <x-input-label value="{{ __('Component Lines') }}" />
                                <button type="button" onclick="addComponentLine()" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Component</button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="datasheet">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>UOM</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="components-body" class="bg-white divide-y divide-gray-200">
                                    </tbody>
                                </table>
                            </div>

                            <div id="no-lines-msg" class="text-sm text-gray-500 text-center py-4 border-2 border-dashed border-gray-200 rounded mt-2">
                                No components added. Click "+ Add Component" to begin.
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.assemblies.boms') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Create BOM') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $assemblyProductsJson = $assemblyProducts->map(function($p) {
            return [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode,
                'type' => $p->type, 'description' => $p->description,
            ];
        })->values();
        $componentProductsJson = $componentProducts->map(function($p) {
            return [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'barcode' => $p->barcode,
                'type' => $p->type, 'description' => $p->description,
            ];
        })->values();
    @endphp

    <script>
        const assemblyProducts = @json($assemblyProductsJson);
        const componentProducts = @json($componentProductsJson);
        const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
        let lineIndex = 0;

        function buildComponentSearchable(idx, selectedId, selectedName) {
            return scopedSearchFieldHtml({
                name: 'lines[' + idx + '][component_product_id]',
                entity: 'product',
                searchUrl: PRODUCT_SEARCH_URL,
                value: selectedId || '',
                label: selectedName || '',
                placeholder: 'Search components...',
            });
        }

        function addComponentLine(data) {
            var tbody = document.getElementById('components-body');
            var idx = lineIndex++;
            var selectedId = data ? String(data.component_product_id || data.product_id || '') : '';
            var selectedName = '';
            if (selectedId) {
                var allProducts = assemblyProducts.concat(componentProducts);
                var found = allProducts.find(function(p) { return p.id == selectedId; });
                if (found) selectedName = found.name;
            }
            var picker = buildComponentSearchable(idx, selectedId, selectedName);
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="px-4 py-2" style="min-width: 220px;">' + picker + '</td>' +
                '<td class="px-4 py-2"><input type="number" name="lines[' + idx + '][quantity]" value="' + (data ? data.quantity : 1) + '" step="0.0001" min="0.0001" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required /></td>' +
                '<td class="px-4 py-2"><input type="text" name="lines[' + idx + '][unit_of_measure]" value="' + (data ? (data.unit_of_measure || data.uom || 'Each') : 'Each') + '" class="block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Each" /></td>' +
                '<td class="px-4 py-2 text-center"><button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></td>';
            tbody.appendChild(tr);
            updateNoLinesMsg();
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
            updateNoLinesMsg();
        }

        function updateNoLinesMsg() {
            var tbody = document.getElementById('components-body');
            var msg = document.getElementById('no-lines-msg');
            if (tbody.children.length === 0) {
                msg.style.display = '';
            } else {
                msg.style.display = 'none';
            }
        }

        addComponentLine();
    </script>
</x-app-layout>
