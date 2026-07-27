<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Sales Register</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.sales-register.index') }}" class="flex items-end gap-4">
                    <div>
                        <x-input-label for="date_from" value="{{ __('From') }}" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block" :value="$dateFrom" />
                    </div>
                    <div>
                        <x-input-label for="date_to" value="{{ __('To') }}" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block" :value="$dateTo" />
                    </div>
                    <div>
                        <x-input-label for="type" value="{{ __('Type') }}" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                            <option value="invoice" {{ $type === 'invoice' ? 'selected' : '' }}>Invoices</option>
                            <option value="pos_sale" {{ $type === 'pos_sale' ? 'selected' : '' }}>POS Sales</option>
                            <option value="sales_receipt" {{ $type === 'sales_receipt' ? 'selected' : '' }}>Sales Receipts</option>
                        </select>
                    </div>
                    <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                </form>
            </div>

            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-4 gap-6 text-center">
                    <div>
                        <div class="text-2xl font-bold text-gray-800">{{ $summary['count'] }}</div>
                        <div class="text-sm text-gray-500">Total Transactions</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">{{ format_money($summary['total_amount']) }}</div>
                        <div class="text-sm text-gray-500">Subtotal</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800">{{ format_money($summary['total_tax']) }}</div>
                        <div class="text-sm text-gray-500">Tax</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-indigo-600">{{ format_money($summary['total_total']) }}</div>
                        <div class="text-sm text-gray-500">Grand Total</div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($allSales as $sale)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($sale['date'])->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $sale['document_number'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $sale['type'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $sale['customer'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($sale['amount']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ format_money($sale['tax']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">{{ format_money($sale['total']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @switch($sale['status'])
                                            @case('posted') <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>@break
                                            @case('paid') <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Paid</span>@break
                                            @case('partially_paid') <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Partial</span>@break
                                            @default <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($sale['status']) }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">No sales found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
