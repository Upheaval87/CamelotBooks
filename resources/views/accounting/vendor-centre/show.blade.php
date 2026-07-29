<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Vendor Centre') }} — {{ $vendor->name }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.vendor-centre.index') }}">{{ __('Back') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Open Balance ({{ $cs }})</p>
                    <p class="text-xl font-bold text-red-600">{{ format_number($stats['open_balance']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Bills ({{ $cs }})</p>
                    <p class="text-xl font-bold text-gray-900">{{ format_number($stats['total_bills']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Paid ({{ $cs }})</p>
                    <p class="text-xl font-bold text-green-600">{{ format_number($stats['total_paid']) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Credit Balance ({{ $cs }})</p>
                    <p class="text-xl font-bold {{ $stats['credit_balance'] > 0 ? 'text-green-600' : 'text-gray-900' }}">{{ format_number($stats['credit_balance']) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase">Total Expenses ({{ $cs }})</p>
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
                    <x-button variant="ghost" href="{{ route('accounting.bills.create') }}?vendor_id={{ $vendor->id }}">{{ __('Create Bill') }}</x-button>
                    <x-button variant="ghost" href="{{ route('accounting.vendor-payments.create') }}?vendor_id={{ $vendor->id }}">{{ __('Record Payment') }}</x-button>
                    <x-button variant="ghost" href="{{ route('accounting.vendor-credits.create') }}?vendor_id={{ $vendor->id }}">{{ __('Vendor Credit') }}</x-button>
                    <x-button variant="ghost" href="{{ route('accounting.expenses.create') }}?vendor_id={{ $vendor->id }}">{{ __('Record Expense') }}</x-button>
                    <x-button variant="ghost" href="{{ route('accounting.purchase-orders.create') }}?vendor_id={{ $vendor->id }}">{{ __('Create PO') }}</x-button>
                    <x-button variant="ghost" href="{{ route('accounting.aging.ap-detail') }}?vendor_id={{ $vendor->id }}">{{ __('A/P Aging Detail') }}</x-button>
                </div>
            </div>

            {{-- Transaction Timeline --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Timeline</h3>
                @if($timeline->isEmpty())
                    <p class="text-sm text-gray-500">No transactions found for this vendor.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Reference</th>
                                    <th>Date</th>
                                    <th class="text-right">Amount ({{ $cs }})</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($timeline as $item)
                                    <tr>
                                        <td>
                                            @switch($item['type'])
                                                @case('bill')
                                                    <span class="status-pill neutral">Bill</span>
                                                    @break
                                                @case('payment')
                                                    <span class="status-pill positive">Payment</span>
                                                    @break
                                                @case('credit')
                                                    <span class="status-pill neutral">Credit</span>
                                                    @break
                                                @case('po')
                                                    <span class="status-pill neutral">PO</span>
                                                    @break
                                                @case('expense')
                                                    <span class="status-pill neutral">Expense</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            {{ $item['reference'] }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $item['date'] instanceof \Carbon\Carbon ? $item['date']->format('M d, Y') : $item['date'] }}
                                        </td>
                                        <td class="numeric">
                                            {{ format_number($item['amount']) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="text-xs text-gray-600">{{ ucfirst(str_replace('_', ' ', $item['status'])) }}</span>
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ $item['url'] }}" class="text-ink hover:text-gold">View</a>
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
