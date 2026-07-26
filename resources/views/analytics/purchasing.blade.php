<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Purchasing Analytics</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-report-filters mode="period" :showBranch="true" :showCostCenter="false" :action="route('analytics.purchasing')" />

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Purchases</div>
                    <div class="text-2xl font-bold text-indigo-600">@money(array_sum(array_column($data['monthly_summary'], 'total')))</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Total Bills</div>
                    <div class="text-2xl font-bold text-gray-800">{{ array_sum(array_column($data['monthly_summary'], 'count')) }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Purchase Price Variance</div>
                    <div class="text-2xl font-bold {{ $data['ppv_total'] >= 0 ? 'text-red-600' : 'text-green-600' }}">@money(abs($data['ppv_total']))</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-xs text-gray-500 uppercase">Avg Lead Time</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $data['lead_times']['avg_days'] !== null ? number_format($data['lead_times']['avg_days'], 0) . ' days' : 'N/A' }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Purchase Trend</h3>
                <x-chart type="bar" :id="'purchase-trend'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                    ['label' => 'Purchase Amount', 'data' => $data['spend_data'], 'backgroundColor' => '#6366f1'],
                ])" height="300" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Vendors</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bills</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Spend</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($data['top_vendors'] as $vendor)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $vendor['vendor_name'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $vendor['bill_count'] }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">@money($vendor['total_spend'])</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No data</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Purchase Price Variance Trend</h3>
                    @if(count($data['ppv_trend']) > 0)
                        <x-chart type="line" :id="'ppv-trend'" :labels="json_encode(array_column($data['ppv_trend'], 'month'))" :datasets="json_encode([
                            ['label' => 'PPV', 'data' => array_map(fn($v) => (float)$v['net_amount'], $data['ppv_trend']), 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239,68,68,0.1)', 'fill' => true],
                        ])" height="250" />
                    @else
                        <p class="text-sm text-gray-500 text-center py-8">No PPV data</p>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Vendor Lead Times</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Average</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $data['lead_times']['avg_days'] !== null ? number_format($data['lead_times']['avg_days'], 0) . ' days' : 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Minimum</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $data['lead_times']['min_days'] !== null ? number_format($data['lead_times']['min_days'], 0) . ' days' : 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Maximum</div>
                        <div class="text-lg font-semibold text-gray-800">{{ $data['lead_times']['max_days'] !== null ? number_format($data['lead_times']['max_days'], 0) . ' days' : 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
