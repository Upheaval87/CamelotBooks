<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Product') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.products.store') }}">
                            @csrf

                            <x-form.section number="01" :title="__('Product Details')" />

                            <div>
                                <x-input-label for="name" value="{{ __('Name') }}" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="description" value="{{ __('Description') }}" />
                                <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="sku" value="{{ __('Stock Keeping Unit (SKU)') }}" />
                                    <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku')" />
                                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="barcode" value="{{ __('Barcode') }}" />
                                    <x-text-input id="barcode" name="barcode" type="text" class="mt-1 block w-full" :value="old('barcode')" />
                                    <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="type" value="{{ __('Type') }}" />
                                    <select id="type" name="type" class="input mt-1" required>
                                        <option value="">Select Type</option>
                                        <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>Service</option>
                                        <option value="inventory" {{ old('type') === 'inventory' ? 'selected' : '' }}>Inventory</option>
                                        <option value="non_inventory" {{ old('type') === 'non_inventory' ? 'selected' : '' }}>Non-Inventory</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="sales_price" value="{{ __('Sales Price') }}" />
                                    <x-text-input id="sales_price" name="sales_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('sales_price')" min="0" />
                                    <x-input-error :messages="$errors->get('sales_price')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="purchase_price" value="{{ __('Purchase Price') }}" />
                                    <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('purchase_price')" min="0" />
                                    <x-input-error :messages="$errors->get('purchase_price')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="income_account_id" value="{{ __('Income Account') }}" />
                                    <x-scoped-search-field
                                        name="income_account_id"
                                        entity="account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                        :value="old('income_account_id')"
                                        :label="old('income_account_id') ? (($incomeAccounts->firstWhere('id', (int) old('income_account_id'))) ? $incomeAccounts->firstWhere('id', (int) old('income_account_id'))->code . ' - ' . $incomeAccounts->firstWhere('id', (int) old('income_account_id'))->name : '') : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                    <x-input-error :messages="$errors->get('income_account_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="expense_account_id" value="{{ __('Expense Account') }}" />
                                    <x-scoped-search-field
                                        name="expense_account_id"
                                        entity="account"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                        :value="old('expense_account_id')"
                                        :label="old('expense_account_id') ? (($expenseAccounts->firstWhere('id', (int) old('expense_account_id'))) ? $expenseAccounts->firstWhere('id', (int) old('expense_account_id'))->code . ' - ' . $expenseAccounts->firstWhere('id', (int) old('expense_account_id'))->name : '') : ''"
                                        placeholder="{{ __('None') }}"
                                    />
                                    <x-input-error :messages="$errors->get('expense_account_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="tax_rate" value="{{ __('Tax Rate (%)') }}" />
                                    <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('tax_rate')" min="0" max="100" />
                                    <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
                                </div>

                                <div class="flex items-end pb-1">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_taxable" value="1" {{ old('is_taxable') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span class="ms-2 text-sm text-gray-600">{{ __('Taxable') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.products.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button>{{ __('Create Product') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Purchase Order'), 'route' => route('accounting.purchase-orders.create'), 'icon' => 'document'],
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => 'invoice'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Product List'), 'route' => route('accounting.products.index'), 'icon' => 'person'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
