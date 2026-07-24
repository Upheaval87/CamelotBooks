<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventory Analytics</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('analytics.inventory') }}" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="as_of_date" value="As of Date" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" :value="$asOfDate" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_from" value="From" />
                        <x-text-input id="date_from" name="date_from" type="date" :value="$dateFrom" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_to" value="To" />
                        <x-text-input id="date_to" name="date_to" type="date" :value="$dateTo" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="slow_moving_days" value="Slow Moving Days" />
                        <x-text-input id="slow_moving_days" name="slow_moving_days" type="number" :value="$slowMovingDays" class="mt-1 block w-full" />
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <x-primary-button>Apply</x-primary-button>
                </div>
            </form>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Stock Value</div>
                    <div class="text-2xl font-bold text-indigo-600">${{ number_format($data['current_value']['total_value'], 2) }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Quantity</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($data['current_value']['total_quantity'], 0) }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Tracked Items</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $data['current_value']['item_count'] }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Stock Value Trend</h3>
                <x-chart type="line" :id="'stock-value-trend'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                    ['label' => 'Stock Value', 'data' => $data['value_data'], 'borderColor' => '#6366f1', 'backgroundColor' => 'rgba(99,102,241,0.1)', 'fill' => true],
                ])" height="300" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Slow-Moving Stock (>{{ $slowMovingDays }} days)</h3>
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($data['slow_moving'] as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item['product_name'] }} <span class="text-xs text-gray-400">({{ $item['sku'] }})</span></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($item['old_quantity'], 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($item['old_value'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No slow-moving items</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Low Stock Items</h3>
                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">On Hand</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reorder Point</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Shortage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($data['low_stock'] as $item)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $item['product_name'] }} <span class="text-xs text-gray-400">({{ $item['sku'] }})</span></td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($item['quantity_on_hand'], 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($item['reorder_point'], 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-red-600">{{ number_format($item['shortage'], 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-3 text-sm text-gray-500 text-center">All stock levels OK</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Turnover by Product</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Turnover</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Days on Hand</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($data['turnover'] as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item['product_name'] }} <span class="text-xs text-gray-400">({{ $item['sku'] }})</span></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($item['total_value'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($item['avg_cost'], 4) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $item['turnover'] !== null ? number_format($item['turnover'], 1) : 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $item['days_on_hand'] !== null ? number_format($item['days_on_hand'], 0) : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
