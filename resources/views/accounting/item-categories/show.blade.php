<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $category->name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.item-categories.edit', $category) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.item-categories.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Code</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($category->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($category->description)
                        <div class="col-span-2">
                            <dt class="text-sm text-gray-500">Description</dt>
                            <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->description }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-sm text-gray-500">Income Account</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->defaultIncomeAccount->code ?? '' }} {{ $category->defaultIncomeAccount->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">COGS Account</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->defaultCogsAccount->code ?? '' }} {{ $category->defaultCogsAccount->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Inventory Asset Account</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->defaultInventoryAssetAccount->code ?? '' }} {{ $category->defaultInventoryAssetAccount->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Default Reorder Point</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->default_reorder_point ? format_money($category->default_reorder_point) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Default Base UOM</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $category->default_base_uom ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Products in this Category ({{ $category->products->count() }})</h3>
                @if($category->products->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tracked</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales Price</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($category->products as $product)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $product->sku ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <a href="{{ route('accounting.products.show', $product) }}" class="text-indigo-600 hover:text-indigo-900">{{ $product->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                                            @if($product->tracked_as_inventory)
                                                <span class="text-green-600">&#10003;</span>
                                            @else
                                                <span class="text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">@money($product->sales_price)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No products in this category.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
