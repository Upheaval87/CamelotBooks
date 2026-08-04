<x-app-layout>
    <x-list-header title="Profitability Analytics" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Qty Sold</th>
                                <th class="text-right">Avg Price</th>
                                <th class="text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['by_product'] as $product)
                                <tr>
                                    <td>{{ $product['product_name'] }} <span class="text-ink-soft">({{ $product['sku'] }})</span></td>
                                    <td class="numeric">{{ number_format($product['quantity_sold'], 0) }}</td>
                                    <td class="numeric">@money($product['avg_price'])</td>
                                    <td class="numeric">@money($product['revenue'])</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-ink-soft text-center">No data</td></tr>
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
                        <div class="text-lg font-semibold text-gray-800">@money($data['income_statement']['total_income'])</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Total Expenses</div>
                        <div class="text-lg font-semibold text-gray-800">@money($data['income_statement']['total_expenses'])</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Net Income</div>
                        <div class="text-lg font-semibold {{ $data['income_statement']['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">@money($data['income_statement']['net_income'])</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
