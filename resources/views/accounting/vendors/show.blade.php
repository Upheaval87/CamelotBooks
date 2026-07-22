<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Vendor Detail') }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.vendors.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Vendors') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Display Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->display_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Payment Terms') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Currency') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->currency ?? 'USD' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($vendor->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($vendor->billing_address)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Billing Address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $vendor->billing_address }}</dd>
                        </div>
                    @endif
                    @if($vendor->remit_to_address)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Remit To Address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $vendor->remit_to_address }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Balance') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ number_format($vendor->opening_balance ?? 0, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $vendor->opening_balance_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2 border-t pt-4">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Open Balance') }}</dt>
                        <dd class="mt-1 text-2xl font-bold {{ $balanceDue > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($balanceDue, 2) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Transaction History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($transactions as $txn)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        @if($txn['type'] === 'Bill')
                                            <span class="text-indigo-600">{{ $txn['type'] }}</span>
                                        @else
                                            <span class="text-green-600">{{ $txn['type'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $txn['date']?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $txn['reference'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $txn['description'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format(abs($txn['amount']), 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        {{ number_format($txn['paid'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $txn['balance'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ number_format(abs($txn['balance']), 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'gray',
                                                'pending_approval' => 'yellow',
                                                'approved' => 'blue',
                                                'partially_paid' => 'yellow',
                                                'paid' => 'green',
                                                'overdue' => 'red',
                                                'void' => 'gray',
                                            ];
                                            $color = $statusColors[$txn['status']] ?? 'gray';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                            {{ str_replace('_', ' ', ucfirst($txn['status'])) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                        No transactions found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
