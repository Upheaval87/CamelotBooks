<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revenue & Expense Trends</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-report-filters mode="period" :showBranch="true" :showCostCenter="true" :showDimension="true" :dimensions="['none' => 'Consolidated', 'branch' => 'By Branch', 'cost_center' => 'By Cost Center']" :action="route('analytics.revenue-expense-trends')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Trend</h3>
                <x-chart type="line" :id="'revenue-expense-trend'" :labels="json_encode($data['labels'])" :datasets="json_encode([
                    ['label' => 'Revenue', 'data' => $data['revenue_data'], 'borderColor' => '#6366f1', 'backgroundColor' => 'rgba(99,102,241,0.1)', 'fill' => true],
                    ['label' => 'Expenses', 'data' => $data['expense_data'], 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239,68,68,0.1)', 'fill' => true],
                    ['label' => 'Net Income', 'data' => $data['net_income_data'], 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,0.1)', 'fill' => false, 'borderDash' => [5,5]],
                ])" height="350" />
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Period Details</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Income</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($data['results'] as $result)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $result['period'] }}</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">@money($result['revenue'] ?? array_sum(array_column($result['dimensions'] ?? [], 'revenue')))</td>
                                    <td class="px-6 py-4 text-sm text-right text-gray-900">@money($result['expense'] ?? array_sum(array_column($result['dimensions'] ?? [], 'expense')))</td>
                                    <td class="px-6 py-4 text-sm text-right font-medium {{ ($result['net_income'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        @money($result['net_income'] ?? (array_sum(array_column($result['dimensions'] ?? [], 'revenue')) - array_sum(array_column($result['dimensions'] ?? [], 'expense'))))
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr class="font-semibold">
                                <td class="px-6 py-4 text-sm">Total</td>
                                <td class="px-6 py-4 text-sm text-right">@money($data['total_revenue'])</td>
                                <td class="px-6 py-4 text-sm text-right">@money($data['total_expense'])</td>
                                <td class="px-6 py-4 text-sm text-right">@money($data['total_revenue'] - $data['total_expense'])</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
