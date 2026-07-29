<x-app-layout>
    <x-slot name="header">{{ __('New Stock Transfer') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.stock-transfers.index') }}">
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
                <form method="POST" action="{{ route('accounting.stock-transfers.store') }}">
                    @csrf

                    <div class="space-y-6">
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="from_branch_id" value="{{ __('From Branch') }}" />
                                <select id="from_branch_id" name="from_branch_id" class="input mt-1" required>
                                    <option value="">Select Source</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('from_branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('from_branch_id')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="to_branch_id" value="{{ __('To Branch') }}" />
                                <select id="to_branch_id" name="to_branch_id" class="input mt-1" required>
                                    <option value="">Select Destination</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('to_branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('to_branch_id')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                        </div>

                        <div>
                            <x-input-label for="quantity" value="{{ __('Quantity') }}" />
                            <x-text-input id="quantity" name="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" :value="old('quantity')" required />
                            <p class="text-xs text-gray-500 mt-1">Quantity in the product's base UOM. FIFO layers will be consumed from the source and recreated at the destination.</p>
                        </div>

                        <div>
                            <x-input-label for="memo" value="{{ __('Memo (optional)') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Brief explanation" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <x-button variant="ghost" href="{{ route('accounting.stock-transfers.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Complete Transfer') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-advanced-search-modal name="product" :items="$products" labelKey="name" :showFields="['sku']" :types="['inventory']" />
</x-app-layout>
