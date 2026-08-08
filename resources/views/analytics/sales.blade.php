<x-app-layout>
    <x-list-header title="Sales Analytics" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-report-filters mode="period" :showBranch="true" :showCostCenter="false" :action="route('analytics.sales')" />

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Revenue</div>
                    <div class="text-2xl font-bold text-gold-700">@money($data['revenue']['total_income'])</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Invoices</div>
                    <div class="text-2xl font-bold text-gray-800">{{ array_sum(array_column($data['monthly_summary'], 'count')) }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Avg Invoice Value</div>
                    <div class="text-2xl font-bold text-gray-800">@money(array_sum(array_column($data['monthly_summary'], 'total')) / max(1, array_sum(array_column($data['monthly_summary'], 'count'))))</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Conversion Rate</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $data['conversion']['rate'] !== null ? number_format($data['conversion']['rate'], 1) . '%' : 'N/A' }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Invoice Trend</h3>
                    <x-chart type="bar" :id="'invoice-trend'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                        ['label' => 'Invoice Amount', 'data' => $data['invoice_value_data'], 'backgroundColor' => '#128F8E'],
                        ['label' => 'Invoice Count', 'data' => $data['invoice_count_data'], 'backgroundColor' => '#a78bfa', 'yAxisID' => 'y1'],
                    ])" :options="json_encode([
                        'scales' => ['y' => ['position' => 'left'], 'y1' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false]]]
                    ])" height="300" />
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quotation Conversion</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Quotations Created</span>
                            <span class="font-semibold text-gray-800">{{ $data['conversion']['quotations'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Invoices Generated</span>
                            <span class="font-semibold text-gray-800">{{ $data['conversion']['invoices'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Conversion Rate</span>
                            <span class="font-semibold text-gold-700">{{ $data['conversion']['rate'] !== null ? number_format($data['conversion']['rate'], 1) . '%' : 'No quotations' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Customers</h3>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-right">Invoices</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['top_customers'] as $customer)
                                    <tr>
                                        <td>{{ $customer['customer_name'] }}</td>
                                        <td class="numeric">{{ $customer['invoice_count'] }}</td>
                                        <td class="numeric">@money($customer['total_revenue'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-ink-soft text-center">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Products</h3>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Qty Sold</th>
                                    <th class="text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['top_products'] as $product)
                                    <tr>
                                        <td>{{ $product['product_name'] }} <span class="text-ink-soft">({{ $product['sku'] }})</span></td>
                                        <td class="numeric">{{ number_format($product['total_quantity'], 0) }}</td>
                                        <td class="numeric">@money($product['total_revenue'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-ink-soft text-center">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
