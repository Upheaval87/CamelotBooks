<x-app-layout>
    <x-list-header title="{{ __('New Stock Transfer') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-feedback.alert>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.stock-transfers.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Stock Transfer Details')" />

                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="product_id" value="{{ __('Product') }}" />
                                    <x-scoped-search-field
                                        name="product_id"
                                        entity="product"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'product']) }}"
                                        :value="old('product_id')"
                                        placeholder="Search products..."
                                        :required="true"
                                    />
                                    <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="from_branch_id" value="{{ __('From Branch') }}" />
                                        <x-scoped-search-field
                                            name="from_branch_id"
                                            entity="branch"
                                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                            :value="old('from_branch_id')"
                                            :label="old('from_branch_id') ? ($branches->firstWhere('id', (int) old('from_branch_id'))?->name ?? '') : ''"
                                            placeholder="{{ __('Select Source') }}"
                                            required
                                        />
                                        <x-input-error :messages="$errors->get('from_branch_id')" class="mt-2" />
                                    </div>
                                    <div>
                                        <x-input-label for="to_branch_id" value="{{ __('To Branch') }}" />
                                        <x-scoped-search-field
                                            name="to_branch_id"
                                            entity="branch"
                                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                            :value="old('to_branch_id')"
                                            :label="old('to_branch_id') ? ($branches->firstWhere('id', (int) old('to_branch_id'))?->name ?? '') : ''"
                                            placeholder="{{ __('Select Destination') }}"
                                            required
                                        />
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
                                    <x-input-label for="memo" value="{{ __('Description (optional)') }}" />
                                    <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Brief explanation" />
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.stock-transfers.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Complete Transfer') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
