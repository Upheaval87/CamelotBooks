<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Vendor Centre') }} — {{ $vendor->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('accounting.vendor-centre.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Open Balance</p>
                    <p class="text-xl font-bold text-red-600">{{ format_number($stats['open_balance']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Bills</p>
                    <p class="text-xl font-bold text-gray-900">{{ format_number($stats['total_bills']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Paid</p>
                    <p class="text-xl font-bold text-green-600">{{ format_number($stats['total_paid']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Credit Balance</p>
                    <p class="text-xl font-bold {{ $stats['credit_balance'] > 0 ? 'text-green-600' : 'text-gray-900' }}">{{ format_number($stats['credit_balance']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Expenses</p>
                    <p class="text-xl font-bold text-gray-900">{{ format_number($stats['total_expenses']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Bill Count</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['bill_count'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">PO Count</p>
                    <p class="text-xl font-bold text-gray-900">{{ $stats['po_count'] }}</p>
                </div>
            </div>

            {{-- Vendor Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Vendor Details</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="text-sm text-gray-900">{{ $vendor->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Phone</p>
                        <p class="text-sm text-gray-900">{{ $vendor->phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Currency</p>
                        <p class="text-sm text-gray-900">{{ $vendor->currency ?? 'USD' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Payment Terms</p>
                        <p class="text-sm text-gray-900">{{ $vendor->payment_terms ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('accounting.bills.create') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Create Bill') }}
                    </a>
                    <a href="{{ route('accounting.vendor-payments.create') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Record Payment') }}
                    </a>
                    <a href="{{ route('accounting.vendor-credits.create') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Vendor Credit') }}
                    </a>
                    <a href="{{ route('accounting.expenses.create') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Record Expense') }}
                    </a>
                    <a href="{{ route('accounting.purchase-orders.create') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Create PO') }}
                    </a>
                    <a href="{{ route('accounting.aging.ap-detail') }}?vendor_id={{ $vendor->id }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('A/P Aging Detail') }}
                    </a>
                </div>
            </div>

            {{-- Transaction Timeline --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Timeline</h3>
                @if($timeline->isEmpty())
                    <p class="text-sm text-gray-500">No transactions found for this vendor.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($timeline as $item)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            @switch($item['type'])
                                                @case('bill')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Bill</span>
                                                    @break
                                                @case('payment')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Payment</span>
                                                    @break
                                                @case('credit')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Credit</span>
                                                    @break
                                                @case('po')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">PO</span>
                                                    @break
                                                @case('expense')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Expense</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            {{ $item['reference'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $item['date'] instanceof \Carbon\Carbon ? $item['date']->format('M d, Y') : $item['date'] }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                            {{ format_number($item['amount']) }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                                            <span class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', $item['status'])) }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm">
                                            <a href="{{ $item['url'] }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
