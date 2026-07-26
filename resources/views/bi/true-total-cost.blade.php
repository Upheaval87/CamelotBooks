<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">True Total Cost per Branch</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('bi._staleness')

            <x-report-filters mode="period" :showBranch="true" :showCostCenter="false" :action="route('bi.true-total-cost')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Total Cost Breakdown by Branch</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Payroll</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">OpEx</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Depreciation</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($branches as $branch)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $branch['branch_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['payroll'] ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['opex'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">${{ number_format($branch['depreciation'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">${{ number_format($branch['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-3 text-sm text-gray-500 text-center">No cost data for this period</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($branches) > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900">Grand Total</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format(array_sum(array_column($branches, 'payroll')), 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format(array_sum(array_column($branches, 'opex')), 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format(array_sum(array_column($branches, 'depreciation')), 2) }}</td>
                                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format($grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
