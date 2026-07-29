<x-app-layout>
    <x-slot name="header">{{ __('New Assembly Build') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.assemblies.index') }}">
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

            <div class="card p-6">
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                    Build consumes component inventory (FIFO) and creates assembled product stock. Journal: DR Assembly Inventory Asset, CR Component Inventory Assets.
                </div>

                <form method="POST" action="{{ route('accounting.assemblies.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="assembly_product_id" value="{{ __('Assembly Product') }}" />
                            <x-searchable-select
                                name="assembly_product_id"
                                :items="$products"
                                valueKey="id"
                                labelKey="name"
                                :searchKeys="['name', 'sku', 'barcode']"
                                :showFields="['sku']"
                                :preload="old('assembly_product_id')"
                                placeholder="Search assembly products..."
                                :enableAdvancedSearch="true"
                                advancedSearchName="product"
                                :required="true"
                            />
                            <x-input-error :messages="$errors->get('assembly_product_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bom_id" value="{{ __('Bill of Materials (optional)') }}" />
                            <p class="text-xs text-gray-500 mb-1">If omitted, the first active BOM for the selected product will be used.</p>
                            <select id="bom_id" name="bom_id" class="input mt-1">
                                <option value="">Auto-select first active BOM</option>
                                @foreach($boms as $bom)
                                    <option value="{{ $bom->id }}" {{ old('bom_id') == $bom->id ? 'selected' : '' }}>
                                        {{ $bom->bom_number }} - {{ $bom->assemblyProduct->name ?? '' }} ({{ $bom->lines_count }} lines)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="date" value="{{ __('Date') }}" />
                                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                            </div>
                            <div>
                                <x-input-label for="quantity" value="{{ __('Quantity to Build') }}" />
                                <x-text-input id="quantity" name="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" :value="old('quantity')" required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="memo" value="{{ __('Memo (optional)') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional note" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <x-button variant="ghost" href="{{ route('accounting.assemblies.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Build Assembly') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-advanced-search-modal name="product" :items="$products" labelKey="name" :showFields="['sku']" :types="['inventory']" />
</x-app-layout>
