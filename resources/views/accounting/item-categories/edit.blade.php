<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Item Category') }}</h2>
            <a href="{{ route('accounting.item-categories.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
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
                <form method="POST" action="{{ route('accounting.item-categories.update', $category) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="code" value="{{ __('Code') }}" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $category->code)" required />
                            </div>
                            <div>
                                <x-input-label for="name" value="{{ __('Name') }}" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description', $category->description) }}</textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="default_income_account_id" value="{{ __('Income Account') }}" />
                                <select id="default_income_account_id" name="default_income_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">None</option>
                                    @foreach($accounts->where('type', 'income') as $account)
                                        <option value="{{ $account->id }}" {{ old('default_income_account_id', $category->default_income_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="default_cogs_account_id" value="{{ __('COGS Account') }}" />
                                <select id="default_cogs_account_id" name="default_cogs_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">None</option>
                                    @foreach($accounts->where('type', 'expense') as $account)
                                        <option value="{{ $account->id }}" {{ old('default_cogs_account_id', $category->default_cogs_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="default_inventory_asset_account_id" value="{{ __('Inventory Asset Account') }}" />
                                <select id="default_inventory_asset_account_id" name="default_inventory_asset_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="">None</option>
                                    @foreach($accounts->where('type', 'asset') as $account)
                                        <option value="{{ $account->id }}" {{ old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="default_base_uom" value="{{ __('Default Base UOM') }}" />
                                <x-text-input id="default_base_uom" name="default_base_uom" type="text" class="mt-1 block w-full" :value="old('default_base_uom', $category->default_base_uom)" placeholder="e.g. Each, Box, Kg" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="default_reorder_point" value="{{ __('Default Reorder Point') }}" />
                            <x-text-input id="default_reorder_point" name="default_reorder_point" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('default_reorder_point', $category->default_reorder_point)" />
                        </div>

                        <div class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label class="ml-2 text-sm text-gray-700">{{ __('Active') }}</label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.item-categories.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Update Category') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
