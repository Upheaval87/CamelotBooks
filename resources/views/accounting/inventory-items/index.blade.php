<x-app-layout>
    <x-list-header title="{{ __('Add Product') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.products.create') }}">
                    {{ __('Add Product') }}
                </x-button>
            </div>
            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Product Name</th>
                                <th class="text-right">Qty On Hand</th>
                                <th class="text-right">Reorder Point</th>
                                <th class="text-right">Sales Price</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr class="hover:bg-gray-50">
                                    <td class="text-ink-soft">{{ $product->sku ?? '—' }}</td>
                                    <td>
                                        <a href="{{ route('accounting.inventory-items.show', $product) }}" class="text-ink hover:text-gold">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td class="numeric">
                                        @php $onHand = $product->stock->sum('quantity_on_hand'); @endphp
                                        <span class="{{ $onHand <= ($product->reorder_point ?? 0) && $product->reorder_point ? 'text-red-600' : '' }}">
                                            {{ format_money($onHand) }}
                                        </span>
                                    </td>
                                    <td class="text-ink-soft text-right">
                                        {{ $product->reorder_point ? format_money($product->reorder_point) : '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($product->sales_price) }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('accounting.inventory-items.show', $product) }}" class="text-ink hover:text-gold">View</a>
                                        <a href="{{ route('accounting.products.edit', $product) }}" class="text-ink hover:text-gold">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No inventory items found. Add a tracked product to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
