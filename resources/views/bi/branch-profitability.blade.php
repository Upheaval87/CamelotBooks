<x-app-layout>
    <x-slot name="header">Fully-Loaded Branch Profitability</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('bi._staleness')

            <x-report-filters mode="period" :showBranch="true" :showCostCenter="false" :action="route('bi.branch-profitability')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Profitability by Branch (Fully Loaded)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">COGS</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">GM %</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payroll</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">OpEx</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Depreciation</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Expenses</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Income</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NM %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($branches as $branch)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $branch['branch_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['revenue'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['cogs'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['gross_profit'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format($branch['gross_margin'], 1) }}%</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['payroll'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['opex'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['depreciation'])</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($branch['total_expenses'])</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold {{ $branch['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">@money($branch['net_income'])</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $branch['net_margin'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($branch['net_margin'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="px-4 py-3 text-sm text-gray-500 text-center">No data for this period</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
