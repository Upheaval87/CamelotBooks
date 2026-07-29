<x-app-layout>
    <x-slot name="header">{{ __('Create Product') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.products.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-2 gap-4">
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="income_account_id" value="{{ __('Income Account') }}" />
                                <select id="income_account_id" name="income_account_id" class="input mt-1">
                                    <option value="">None</option>
                                    @foreach($incomeAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('income_account_id') == $account->id ? 'selected' : '' }}>
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
                                        <option value="{{ $account->id }}" {{ old('expense_account_id') == $account->id ? 'selected' : '' }}>
                                            {{ $account->code }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('expense_account_id')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
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
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.products.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Product') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
