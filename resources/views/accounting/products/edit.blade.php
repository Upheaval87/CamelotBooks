<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Edit Product') }}: {{ $product->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.products.update', $product) }}">
                            @csrf
                            @method('PUT')

                            <x-form.section number="01" :title="__('Product Details')" />

                            <div>
                                <x-input-label for="name" value="{{ __('Name') }}" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name)" required autofocus />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="description" value="{{ __('Description') }}" />
                                <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description', $product->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="sku" value="{{ __('Stock Keeping Unit (SKU)') }}" />
                                    <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku', $product->sku)" />
                                    <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="barcode" value="{{ __('Barcode') }}" />
                                    <x-text-input id="barcode" name="barcode" type="text" class="mt-1 block w-full" :value="old('barcode', $product->barcode)" />
                                    <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="type" value="{{ __('Type') }}" />
                                    <select id="type" name="type" class="input mt-1" required>
                                        <option value="">Select Type</option>
                                        <option value="service" {{ old('type', $product->type) === 'service' ? 'selected' : '' }}>Service</option>
                                        <option value="inventory" {{ old('type', $product->type) === 'inventory' ? 'selected' : '' }}>Inventory</option>
                                        <option value="non_inventory" {{ old('type', $product->type) === 'non_inventory' ? 'selected' : '' }}>Non-Inventory</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="sales_price" value="{{ __('Sales Price') }}" />
                                    <x-text-input id="sales_price" name="sales_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('sales_price', $product->sales_price)" min="0" />
                                    <x-input-error :messages="$errors->get('sales_price')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="purchase_price" value="{{ __('Purchase Price') }}" />
                                    <x-text-input id="purchase_price" name="purchase_price" type="number" step="0.01" class="mt-1 block w-full" :value="old('purchase_price', $product->purchase_price)" min="0" />
                                    <x-input-error :messages="$errors->get('purchase_price')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="income_account_id" value="{{ __('Income Account') }}" />
                                    <select id="income_account_id" name="income_account_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($incomeAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('income_account_id', $product->income_account_id) == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('income_account_id')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="expense_account_id" value="{{ __('Expense Account') }}" />
                                    <select id="expense_account_id" name="expense_account_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($expenseAccounts as $account)
                                            <option value="{{ $account->id }}" {{ old('expense_account_id', $product->expense_account_id) == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('expense_account_id')" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="tax_rate" value="{{ __('Tax Rate (%)') }}" />
                                    <x-text-input id="tax_rate" name="tax_rate" type="number" step="0.01" class="mt-1 block w-full" :value="old('tax_rate', $product->tax_rate)" min="0" max="100" />
                                    <x-input-error :messages="$errors->get('tax_rate')" class="mt-2" />
                                </div>

                                <div class="flex items-end pb-1">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_taxable" value="1" {{ old('is_taxable', $product->is_taxable) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span class="ms-2 text-sm text-gray-600">{{ __('Taxable') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.products.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button>{{ __('Update Product') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Purchase Order'), 'route' => route('accounting.purchase-orders.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Product List'), 'route' => route('accounting.products.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
