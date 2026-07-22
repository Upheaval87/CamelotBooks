<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Product Detail') }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.products.edit', $product) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Products') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('SKU') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($product->type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($product->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($product->description)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $product->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Pricing') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Sales Price') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->sales_price ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Purchase Price') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->purchase_price ?? 0, 2) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Accounts & Tax') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Income Account') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->incomeAccount?->name ? "{$product->incomeAccount->code} - {$product->incomeAccount->name}" : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expense Account') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->expenseAccount?->name ? "{$product->expenseAccount->code} - {$product->expenseAccount->name}" : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Tax Rate') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($product->tax_rate ?? 0, 2) }}%</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Taxable') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $product->is_taxable ? 'Yes' : 'No' }}</dd>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
