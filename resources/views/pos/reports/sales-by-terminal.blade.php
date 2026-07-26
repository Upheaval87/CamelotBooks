<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sales by Terminal') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <form method="GET" action="{{ route('pos.reports.sales-by-terminal') }}" class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}"
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500 text-sm font-medium">Filter</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terminal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sales Count</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Sales</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Returns</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Net Sales</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Sale</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($data['terminals'] as $row)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $row['terminal']->identifier ?? '—' }} – {{ $row['terminal']->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">{{ $row['sales_count'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">${{ number_format($row['sales_total'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">${{ number_format($row['returns_total'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900">${{ number_format($row['net_sales'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">${{ number_format($row['average_sale'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No terminals found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($data['terminals']) > 0)
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">Grand Total</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ $data['grand_count'] }}</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-900">${{ number_format($data['grand_total_sales'], 2) }}</td>
                                <td class="px-6 py-3 text-sm text-right text-red-600">${{ number_format($data['grand_total_returns'], 2) }}</td>
                                <td class="px-6 py-3 text-sm text-right text-indigo-700">${{ number_format($data['grand_net_sales'], 2) }}</td>
                                <td class="px-6 py-3 text-sm text-right text-gray-500">
                                    ${{ $data['grand_count'] > 0 ? number_format($data['grand_total_sales'] / $data['grand_count'], 2) : '0.00' }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
