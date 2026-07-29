<x-app-layout>
    <x-slot name="header">{{ __('Low Stock Report') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.low-stock.export-csv') }}">{{ __('Export CSV') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Items Below Reorder Point</h3>
                    <p class="text-sm text-gray-500 mt-1">Items where quantity on hand is at or below the reorder point.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Product</th>
                                <th class="text-right">Qty On Hand</th>
                                <th class="text-right">Reorder Point</th>
                                <th class="text-right">Shortage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="text-ink-soft">{{ $item['sku'] ?? '—' }}</td>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td class="numeric">
                                        <span class="text-red-600 font-semibold">{{ format_money($item['quantity_on_hand']) }}</span>
                                    </td>
                                    <td class="numeric">{{ format_money($item['reorder_point']) }}</td>
                                    <td class="numeric">
                                        <span class="text-red-600 font-bold">{{ format_money($item['shortage']) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">
                                        All items are adequately stocked.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
