<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $customer->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-toolbar class="mb-6">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-1">Record</span>
                <a href="{{ route('accounting.invoices.create', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    New Invoice
                </a>
                <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    New Payment
                </a>
                <a href="{{ route('accounting.customers.edit', $customer) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-atlas-navy/20 text-atlas-navy text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Save
                </a>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-1">Activity</span>
                <a href="{{ route('accounting.invoices.index', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Transaction History
                </a>
                <a href="{{ route('accounting.reports.customer-statement', ['customer_id' => $customer->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    View Statement
                </a>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md {{ $customer->open_balance > 0 ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700' }}">
                    {{ __('Open Balance:') }} {{ format_money($customer->open_balance ?? 0) }}
                </span>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider mr-1">Document</span>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Statement
                </button>
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Attach File
                </button>
                @if($customer->email)
                    <a href="mailto:{{ $customer->email }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email Statement
                    </a>
                @else
                    <button type="button" disabled title="No email on file" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/30 text-sm font-medium rounded-md cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email Statement
                    </button>
                @endif

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Duplicate
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Merge
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                Add Note
                            </button>
                            <a href="{{ route('accounting.customers.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back to Customers
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>

                <x-slot name="right">
                    <x-toolbar-button variant="danger" onclick="if(confirm('Are you sure you want to archive this customer?')) { window.location='{{ route('accounting.customers.index') }}' }">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        Archive
                    </x-toolbar-button>
                </x-slot>
            </x-toolbar>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Display Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->display_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Payment Terms') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ str_replace('_', ' ', ucfirst($customer->payment_terms ?? 'due_on_receipt')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Currency') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->currency ?? 'USD' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Credit Limit') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ format_money($customer->credit_limit ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($customer->is_active)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill neutral">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    @if($customer->billing_address)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Billing Address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $customer->billing_address }}</dd>
                        </div>
                    @endif
                    @if($customer->shipping_address)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Shipping Address') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $customer->shipping_address }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Balance') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ format_money($customer->opening_balance ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Opening Balance Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $customer->opening_balance_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div class="col-span-2 border-t pt-4">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Open Balance') }}</dt>
                        <dd class="mt-1 text-2xl font-bold {{ $balanceDue > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ format_money($balanceDue) }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Transaction History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Balance</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $txn)
                                <tr>
                                    <td>
                                        @if($txn['type'] === 'Invoice')
                                            <span class="text-indigo-600">{{ $txn['type'] }}</span>
                                        @else
                                            <span class="text-green-600">{{ $txn['type'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $txn['date']?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td>
                                        {{ $txn['reference'] }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $txn['description'] }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money(abs($txn['amount'])) }}
                                    </td>
                                    <td class="text-ink-soft text-right">
                                        {{ format_money($txn['paid']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $txn['balance'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ format_money(abs($txn['balance'])) }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $statusColors = [
                                                'draft' => 'gray',
                                                'sent' => 'blue',
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
                                    <td colspan="8" class="text-center text-ink-soft">
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
