<x-app-layout>
    <x-slot name="header">{{ __('Inventory Valuation by Category') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.index') }}">{{ __('Back to Valuation') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">Valuation by Category (FIFO)</h3>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 uppercase">Grand Total</div>
                        <div class="text-xl font-bold text-gray-900">@money($grandTotal)</div>
                    </div>
                </div>
                <div class="p-6 space-y-6">
                    @forelse($categoryData as $category)
                        <div class="border border-gray-200 rounded-lg">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                                <div>
                                    <span class="font-semibold text-gray-900">{{ $category['code'] }}</span>
                                    <span class="text-gray-500 mx-2">&mdash;</span>
                                    <span class="text-gray-900">{{ $category['name'] }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-gray-500">{{ count($category['products']) }} products</span>
                                    <span class="ml-4 font-bold text-gray-900">@money($category['total_value'])</span>
                                </div>
                            </div>
                            @if(count($category['products']) > 0)
                                <table class="datasheet">
                                    <thead>
                                        <tr>
                                            <th>Stock Keeping Unit (SKU)</th>
                                            <th>Product</th>
                                            <th class="text-right">Quantity</th>
                                            <th class="text-right">Value</th>
                                            <th class="text-right">% of Category</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($category['products'] as $product)
                                            <tr class="hover:bg-gray-50">
                                                <td class="text-ink-soft">{{ $product['sku'] ?? '—' }}</td>
                                                <td>{{ $product['name'] }}</td>
                                                <td class="numeric">{{ format_money($product['quantity']) }}</td>
                                                <td class="numeric">@money($product['value'])</td>
                                                <td class="text-ink-soft text-right">
                                                    {{ $category['total_value'] > 0 ? number_format(($product['value'] / $category['total_value']) * 100, 1) . '%' : '0.0%' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="px-4 py-3 text-sm text-gray-500">No products with stock in this category.</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-sm text-gray-500 py-8">No item categories found.</div>
                    @endforelse

                    @if(count($uncategorizedData) > 0)
                        <div class="border border-orange-200 rounded-lg">
                            <div class="px-4 py-3 bg-orange-50 border-b border-orange-200 flex justify-between items-center">
                                <div>
                                    <span class="font-semibold text-orange-800">Uncategorized Products</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm text-orange-600">{{ count($uncategorizedData) }} products</span>
                                    <span class="ml-4 font-bold text-orange-800">@money(array_sum(array_column($uncategorizedData, 'value')))</span>
                                </div>
                            </div>
                            <table class="datasheet">
                                <thead>
                                    <tr>
                                        <th>Stock Keeping Unit (SKU)</th>
                                        <th>Product</th>
                                        <th class="text-right">Quantity</th>
                                        <th class="text-right">Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($uncategorizedData as $product)
                                        <tr class="hover:bg-gray-50">
                                            <td class="text-ink-soft">{{ $product['sku'] ?? '—' }}</td>
                                            <td>{{ $product['name'] }}</td>
                                            <td class="numeric">{{ format_money($product['quantity']) }}</td>
                                            <td class="numeric">@money($product['value'])</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
