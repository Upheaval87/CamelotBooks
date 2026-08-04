<x-app-layout>
    <x-list-header title="Sales Register" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Document #</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Tax</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allSales as $sale)
                                <tr>
                                    <td class="text-ink-soft">{{ \Carbon\Carbon::parse($sale['date'])->format('M d, Y') }}</td>
                                    <td>{{ $sale['document_number'] }}</td>
                                    <td>{{ $sale['type'] }}</td>
                                    <td>{{ $sale['customer'] }}</td>
                                    <td class="numeric">{{ format_money($sale['amount']) }}</td>
                                    <td class="numeric">{{ format_money($sale['tax']) }}</td>
                                    <td class="numeric">{{ format_money($sale['total']) }}</td>
                                    <td class="text-center">
                                        @switch($sale['status'])
                                            @case('posted') <span class="status-pill positive">Posted</span>@break
                                            @case('paid') <span class="status-pill positive">Paid</span>@break
                                            @case('partially_paid') <span class="status-pill neutral">Partial</span>@break
                                            @default <span class="status-pill neutral">{{ ucfirst($sale['status']) }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-ink-soft">No sales found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
