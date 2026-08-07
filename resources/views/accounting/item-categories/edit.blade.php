<x-app-layout>
    <x-list-header title="{{ __('Edit Item Category') }}" />

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
                        <form method="POST" action="{{ route('accounting.item-categories.update', $category) }}">
                            @csrf
                            @method('PUT')

                            <x-form.section number="01" :title="__('Category Details')" />

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
                                    <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description', $category->description) }}</textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="default_income_account_id" value="{{ __('Income Account') }}" />
                                            <x-scoped-search-field
                                                name="default_income_account_id"
                                                entity="account"
                                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                                :value="old('default_income_account_id', $category->default_income_account_id)"
                                                :label="old('default_income_account_id', $category->default_income_account_id) ? (($accounts->firstWhere('id', (int) old('default_income_account_id', $category->default_income_account_id))) ? $accounts->firstWhere('id', (int) old('default_income_account_id', $category->default_income_account_id))->code . ' - ' . $accounts->firstWhere('id', (int) old('default_income_account_id', $category->default_income_account_id))->name : '') : ''"
                                                placeholder="{{ __('None') }}"
                                            />
                                    </div>
                                    <div>
                                        <x-input-label for="default_cogs_account_id" value="{{ __('COGS Account') }}" />
                                            <x-scoped-search-field
                                                name="default_cogs_account_id"
                                                entity="account"
                                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                                :value="old('default_cogs_account_id', $category->default_cogs_account_id)"
                                                :label="old('default_cogs_account_id', $category->default_cogs_account_id) ? (($accounts->firstWhere('id', (int) old('default_cogs_account_id', $category->default_cogs_account_id))) ? $accounts->firstWhere('id', (int) old('default_cogs_account_id', $category->default_cogs_account_id))->code . ' - ' . $accounts->firstWhere('id', (int) old('default_cogs_account_id', $category->default_cogs_account_id))->name : '') : ''"
                                                placeholder="{{ __('None') }}"
                                            />
                                    </div>
                                    <div>
                                        <x-input-label for="default_inventory_asset_account_id" value="{{ __('Inventory Asset Account') }}" />
                                            <x-scoped-search-field
                                                name="default_inventory_asset_account_id"
                                                entity="account"
                                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                                :value="old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id)"
                                                :label="old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id) ? (($accounts->firstWhere('id', (int) old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id))) ? $accounts->firstWhere('id', (int) old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id))->code . ' - ' . $accounts->firstWhere('id', (int) old('default_inventory_asset_account_id', $category->default_inventory_asset_account_id))->name : '') : ''"
                                                placeholder="{{ __('None') }}"
                                            />
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
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-gold-700 shadow-sm focus:ring-gold-500">
                                    <x-input-label value="{{ __('Active') }}" class="ml-2" />
                                </div>
                            </div>

                            <div class="flex justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.item-categories.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button type="submit">{{ __('Update Category') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Product'), 'route' => route('accounting.products.create'), 'icon' => 'tag'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Item Categories List'), 'route' => route('accounting.item-categories.index'), 'icon' => 'table-list'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
