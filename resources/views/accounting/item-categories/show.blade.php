<x-app-layout>
    <x-slot name="header">{{ $category->name }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" href="{{ route('accounting.item-categories.edit', $category) }}">{{ __('Edit') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.item-categories.index') }}">{{ __('Back') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
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
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Stock Keeping Unit (SKU)</th>
                                    <th>Name</th>
                                    <th class="text-center">Tracked</th>
                                    <th class="text-right">Sales Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->products as $product)
                                    <tr>
                                        <td class="text-ink-soft">{{ $product->sku ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <a href="{{ route('accounting.products.show', $product) }}" class="text-ink hover:text-gold">{{ $product->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                                            @if($product->tracked_as_inventory)
                                                <span class="text-green-600">&#10003;</span>
                                            @else
                                                <span class="text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="numeric">@money($product->sales_price)</td>
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
