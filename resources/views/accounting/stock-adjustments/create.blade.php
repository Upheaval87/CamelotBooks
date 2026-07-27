<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Stock Adjustment') }}</h2>
            <a href="{{ route('accounting.stock-adjustments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
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
                <form method="POST" action="{{ route('accounting.stock-adjustments.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div x-data="{ adjustmentType: '{{ old('type', 'increase') }}' }">
                            <div>
                                <x-input-label for="product_id" value="{{ __('Product') }}" />
                                <x-searchable-select
                                    name="product_id"
                                    :items="$products"
                                    valueKey="id"
                                    labelKey="name"
                                    :searchKeys="['name', 'sku', 'barcode']"
                                    :showFields="['sku']"
                                    :preload="old('product_id')"
                                    placeholder="Search products..."
                                    :enableAdvancedSearch="true"
                                    advancedSearchName="product"
                                    :required="true"
                                />
                                <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="branch_id" value="{{ __('Branch (optional)') }}" />
                                <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">All Locations</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="date" value="{{ __('Date') }}" />
                                <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                            </div>

                            <div class="mt-4">
                                <x-input-label value="{{ __('Adjustment Type') }}" />
                                <div class="mt-2 flex gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="type" value="increase" x-model="adjustmentType" {{ old('type', 'increase') === 'increase' ? 'checked' : '' }} class="border-gray-300 text-indigo-600 focus:ring-indigo-500" required>
                                        <span class="ml-2 text-sm text-gray-700">Increase (found/gained)</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="type" value="decrease" x-model="adjustmentType" {{ old('type') === 'decrease' ? 'checked' : '' }} class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">Decrease (damaged/lost)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="quantity" value="{{ __('Quantity') }}" />
                                <x-text-input id="quantity" name="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" :value="old('quantity')" required />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="unit_cost" value="{{ __('Unit Cost (optional for decreases)') }}" />
                                <p class="text-xs text-gray-500 mb-1" x-show="adjustmentType === 'increase'">Required for increases. Used as the FIFO cost.</p>
                                <p class="text-xs text-gray-500 mb-1" x-show="adjustmentType === 'decrease'">Leave blank to use FIFO cost from existing layers.</p>
                                <x-text-input id="unit_cost" name="unit_cost" type="number" step="0.0001" min="0" class="mt-1 block w-full" :value="old('unit_cost')" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="reason_code" value="{{ __('Reason') }}" />
                                <select id="reason_code" name="reason_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="">Select Reason</option>
                                    <option value="found_in_count" {{ old('reason_code') === 'found_in_count' ? 'selected' : '' }}>Found in Count</option>
                                    <option value="damage" {{ old('reason_code') === 'damage' ? 'selected' : '' }}>Damage</option>
                                    <option value="shrinkage" {{ old('reason_code') === 'shrinkage' ? 'selected' : '' }}>Shrinkage</option>
                                    <option value="correction" {{ old('reason_code') === 'correction' ? 'selected' : '' }}>Correction</option>
                                    <option value="other" {{ old('reason_code') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('reason_code')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="memo" value="{{ __('Memo (optional)') }}" />
                                <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Brief explanation" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.stock-adjustments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Post Adjustment') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-advanced-search-modal name="product" :items="$products" labelKey="name" :showFields="['sku']" :types="['inventory']" />
</x-app-layout>
