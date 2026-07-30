<x-app-layout>
    <x-slot name="header">{{ __('New Item Category') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.item-categories.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Category Details')" />

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
                                    <x-input-label value="{{ __('Active') }}" class="ml-2" />
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.item-categories.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Create Category') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Product'), 'route' => route('accounting.products.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Item Categories List'), 'route' => route('accounting.item-categories.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
