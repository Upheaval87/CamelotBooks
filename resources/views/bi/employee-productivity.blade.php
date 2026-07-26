<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Employee Productivity vs Revenue</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('bi._staleness')

            <x-report-filters mode="period" :showBranch="true" :showCostCenter="false" :action="route('bi.employee-productivity')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Productivity by Branch</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Headcount</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Payroll</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost / Employee</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Revenue / Employee</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payroll % of Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($branches as $branch)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $branch['branch_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $branch['headcount'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['total_payroll'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['revenue'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['cost_per_employee'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['revenue_per_employee'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $branch['ratio'] > 80 ? 'text-red-600' : 'text-gray-600' }}">{{ number_format($branch['ratio'], 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-3 text-sm text-gray-500 text-center">No payroll or revenue data for this period</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
