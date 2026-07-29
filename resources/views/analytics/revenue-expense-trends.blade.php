<x-app-layout>
    <x-slot name="header">Revenue & Expense Trends</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-right">Revenue</th>
                                <th class="text-right">Expenses</th>
                                <th class="text-right">Net Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['results'] as $result)
                                <tr>
                                    <td>{{ $result['period'] }}</td>
                                    <td class="numeric">@money($result['revenue'] ?? array_sum(array_column($result['dimensions'] ?? [], 'revenue')))</td>
                                    <td class="numeric">@money($result['expense'] ?? array_sum(array_column($result['dimensions'] ?? [], 'expense')))</td>
                                    <td class="numeric font-medium {{ ($result['net_income'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        @money($result['net_income'] ?? (array_sum(array_column($result['dimensions'] ?? [], 'revenue')) - array_sum(array_column($result['dimensions'] ?? [], 'expense'))))
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-semibold">
                                <td>Total</td>
                                <td class="numeric">@money($data['total_revenue'])</td>
                                <td class="numeric">@money($data['total_expense'])</td>
                                <td class="numeric">@money($data['total_revenue'] - $data['total_expense'])</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
