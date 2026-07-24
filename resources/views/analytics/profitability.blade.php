<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profitability Analytics</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-report-filters mode="period" :showBranch="true" :showCostCenter="true" :action="route('analytics.profitability')" />

            @if(count($data['by_branch']) > 0)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Profitability by Branch</h3>
                <x-chart type="bar" :id="'profit-by-branch'" :labels="json_encode(array_column($data['by_branch'], 'branch_name'))" :datasets="json_encode([
                    ['label' => 'Revenue', 'data' => array_column($data['by_branch'], 'revenue'), 'backgroundColor' => '#6366f1'],
                    ['label' => 'Expenses', 'data' => array_column($data['by_branch'], 'expenses'), 'backgroundColor' => '#ef4444'],
                ])" height="300" />
            </div>
            @endif

            @if(count($data['by_cost_center']) > 0)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Profitability by Cost Center</h3>
                <x-chart type="bar" :id="'profit-by-cc'" :labels="json_encode(array_column($data['by_cost_center'], 'cost_center_name'))" :datasets="json_encode([
                    ['label' => 'Revenue', 'data' => array_column($data['by_cost_center'], 'revenue'), 'backgroundColor' => '#6366f1'],
                    ['label' => 'Expenses', 'data' => array_column($data['by_cost_center'], 'expenses'), 'backgroundColor' => '#ef4444'],
                ])" height="300" />
            </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Profitability by Product</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($data['by_product'] as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $product['product_name'] }} <span class="text-xs text-gray-400">({{ $product['sku'] }})</span></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($product['quantity_sold'], 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($product['avg_price'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${{ number_format($product['revenue'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-3 text-sm text-gray-500 text-center">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Income Statement Summary</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Total Revenue</div>
                        <div class="text-lg font-semibold text-gray-800">${{ number_format($data['income_statement']['total_income'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Total Expenses</div>
                        <div class="text-lg font-semibold text-gray-800">${{ number_format($data['income_statement']['total_expenses'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Net Income</div>
                        <div class="text-lg font-semibold {{ $data['income_statement']['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">${{ number_format($data['income_statement']['net_income'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
