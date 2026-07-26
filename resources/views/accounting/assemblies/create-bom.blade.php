<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Bill of Materials') }}</h2>
            <a href="{{ route('accounting.assemblies.boms') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
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
                <form method="POST" action="{{ route('accounting.assemblies.store-bom') }}" x-data="bomForm()">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="assembly_product_id" value="{{ __('Assembly Product') }}" />
                            <select id="assembly_product_id" name="assembly_product_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Select Assembly Product</option>
                                @foreach($assemblyProducts as $product)
                                    <option value="{{ $product->id }}" {{ old('assembly_product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
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
                                <button type="button" @click="addLine()" class="text-sm text-indigo-600 hover:text-indigo-900">+ Add Component</button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(line, index) in lines" :key="index">
                                    <div class="flex items-end gap-3 p-3 bg-gray-50 rounded border border-gray-200">
                                        <div class="flex-1">
                                            <label class="block text-xs font-medium text-gray-500 uppercase">Component</label>
                                            <select :name="'lines[' + index + '][component_product_id]'" x-model="line.product_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                                <option value="">Select Component</option>
                                                @foreach($componentProducts as $product)
                                                    <option value="{{ $product->id }}">{{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="w-32">
                                            <label class="block text-xs font-medium text-gray-500 uppercase">Quantity</label>
                                            <input :name="'lines[' + index + '][quantity]'" x-model="line.quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                        </div>
                                        <div class="w-28">
                                            <label class="block text-xs font-medium text-gray-500 uppercase">UOM</label>
                                            <input :name="'lines[' + index + '][unit_of_measure]'" x-model="line.uom" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Each">
                                        </div>
                                        <button type="button" @click="removeLine(index)" class="mb-1 text-red-600 hover:text-red-900 text-sm">Remove</button>
                                    </div>
                                </template>
                            </div>

                            <div x-show="lines.length === 0" class="text-sm text-gray-500 text-center py-4 border-2 border-dashed border-gray-200 rounded">
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

    <script>
    function bomForm() {
        return {
            lines: [{ product_id: '', quantity: '1', uom: 'Each' }],
            addLine() {
                this.lines.push({ product_id: '', quantity: '1', uom: 'Each' });
            },
            removeLine(index) {
                this.lines.splice(index, 1);
            }
        };
    }
    </script>
</x-app-layout>
