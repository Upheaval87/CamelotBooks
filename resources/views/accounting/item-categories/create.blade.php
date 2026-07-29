<x-app-layout>
    <x-slot name="header">{{ __('New Item Category') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.item-categories.index') }}">
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
                <form method="POST" action="{{ route('accounting.item-categories.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="code" value="{{ __('Code') }}" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required placeholder="e.g. ELEC, FURN" />
                            </div>
                            <div>
                                <x-input-label for="name" value="{{ __('Name') }}" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="e.g. Electronics" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description (optional)') }}" />
                            <textarea id="description" name="description" rows="2" class="input mt-1">{{ old('description') }}</textarea>
                        </div>

                        <div>
                            <x-input-label value="{{ __('Default Accounts (Inherited by Products)') }}" />
                            <p class="text-xs text-gray-500 mb-3">Products in this category will use these accounts unless overridden at the product level.</p>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="default_income_account_id" value="{{ __('Income Account') }}" />
                                    <select id="default_income_account_id" name="default_income_account_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($accounts->where('type', 'income') as $account)
                                            <option value="{{ $account->id }}" {{ old('default_income_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="default_cogs_account_id" value="{{ __('COGS Account') }}" />
                                    <select id="default_cogs_account_id" name="default_cogs_account_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($accounts->where('type', 'expense') as $account)
                                            <option value="{{ $account->id }}" {{ old('default_cogs_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="default_inventory_asset_account_id" value="{{ __('Inventory Asset Account') }}" />
                                    <select id="default_inventory_asset_account_id" name="default_inventory_asset_account_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($accounts->where('type', 'asset') as $account)
                                            <option value="{{ $account->id }}" {{ old('default_inventory_asset_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="default_base_uom" value="{{ __('Default Base UOM') }}" />
                                    <x-text-input id="default_base_uom" name="default_base_uom" type="text" class="mt-1 block w-full" :value="old('default_base_uom')" placeholder="e.g. Each, Box, Kg" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="default_reorder_point" value="{{ __('Default Reorder Point (optional)') }}" />
                            <x-text-input id="default_reorder_point" name="default_reorder_point" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('default_reorder_point')" />
                        </div>

                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label class="ml-2 text-sm text-gray-700">{{ __('Active') }}</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <x-button variant="ghost" href="{{ route('accounting.item-categories.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Create Category') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
