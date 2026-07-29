<x-app-layout>
    <x-slot name="header">{{ __('Inventory Valuation Report') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.by-category') }}">{{ __('By Category') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-csv') }}">{{ __('Export CSV') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.inventory-valuation.export-pdf') }}" target="_blank">{{ __('Print / PDF') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">All Products (FIFO Valuation)</h3>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 uppercase">Total Inventory Value</div>
                        <div class="text-xl font-bold text-gray-900">@money($totalValue)</div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Product</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Avg Unit Cost</th>
                                <th class="text-right">Total Value</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($valuation as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="text-ink-soft">{{ $row['sku'] ?? '—' }}</td>
                                    <td>{{ $row['product_name'] }}</td>
                                    <td class="numeric">{{ format_money($row['total_quantity']) }}</td>
                                    <td class="numeric">{{ format_money((float)$row['avg_cost'], null, 4) }}</td>
                                    <td class="numeric">@money((float)$row['total_value'])</td>
                                    <td class="text-ink-soft text-right">
                                        {{ $totalValue > 0 ? number_format(((float)$row['total_value'] / $totalValue) * 100, 1) . '%' : '0.0%' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No inventory items found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($valuation) > 0)
                            <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-sm font-bold text-gray-900 text-right">Total</td>
                                    <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">@money($totalValue)</td>
                                    <td class="px-6 py-3 text-sm font-bold text-gray-900 text-right">100.0%</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
