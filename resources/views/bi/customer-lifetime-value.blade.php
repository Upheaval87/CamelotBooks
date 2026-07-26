<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customer Lifetime Value</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('bi._staleness')

            <x-report-filters mode="as_of" :showBranch="true" :showCostCenter="false" :action="route('bi.customer-lifetime-value')" />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Customer Summary</h3>
                    <div class="text-sm text-gray-500">
                        {{ $total_customers }} customers &middot; Total net revenue: <span class="font-semibold text-gray-800">@money($total_revenue)</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoices</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credits</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Net Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Months Active</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Avg Monthly</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">First Invoice</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($customers as $customer)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $customer->customer_name ?? 'Walk-in' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $customer->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $customer->invoice_count }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($customer->total_revenue)</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($customer->total_credits)</td>
                                    <td class="px-4 py-3 text-sm text-right font-medium {{ $customer->net_revenue >= 0 ? 'text-gray-900' : 'text-red-600' }}">@money($customer->net_revenue)</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ $customer->months_active }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">@money($customer->avg_monthly_revenue)</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->first_invoice_date ? \Carbon\Carbon::parse($customer->first_invoice_date)->format('M d, Y') : '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $customer->last_invoice_date ? \Carbon\Carbon::parse($customer->last_invoice_date)->format('M d, Y') : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-3 text-sm text-gray-500 text-center">No customer sales data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
