<x-app-layout>
    <x-slot name="header">{{ __('Customer Payment') }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            @if($payment->invoices->isNotEmpty())
                <x-toolbar-button href="{{ route('accounting.invoices.show', $payment->invoices->first()) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Lookup Invoice
                </x-toolbar-button>
            @else
                <x-toolbar-button disabled>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Lookup Invoice
                </x-toolbar-button>
            @endif
            <x-toolbar-button href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Apply to Invoice
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                Attach File
            </x-toolbar-button>
            @if($payment->customer && $payment->customer->email)
                <x-toolbar-button href="mailto:{{ $payment->customer->email }}?subject=Payment Receipt - {{ $payment->reference ?? $payment->id }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email Receipt
                </x-toolbar-button>
            @else
                <x-toolbar-button disabled title="No email on file for this customer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email Receipt
                </x-toolbar-button>
            @endif

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <x-dropdown align="left" width="56">
                <x-slot name="trigger">
                    <x-toolbar-button>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </x-toolbar-button>
                </x-slot>
                <x-slot name="content">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Duplicate Payment
                        </button>
                        <a href="{{ route('accounting.customers.show', $payment->customer) }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Customer
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            <x-slot name="right">
                @if($payment->status !== 'void')
                    <form method="POST" action="{{ route('accounting.customer-payments.void', $payment) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <x-toolbar-button variant="danger" type="submit" onclick="return confirm('Are you sure you want to void this payment?')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Void Payment
                        </x-toolbar-button>
                    </form>
                @endif
            </x-slot>
        </x-toolbar>
    </div>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Customer') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $payment->customer->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        @if($payment->status === 'void')
                            <span class="status-pill neutral">Void</span>
                        @else
                            <span class="status-pill positive">Completed</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Payment Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Amount') }}</dt>
                    <dd class="mt-1 text-2xl font-bold text-gray-900">{{ format_money($payment->amount) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Payment Method') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Bank Account') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $payment->bankAccount->name ?? '—' }}</dd>
                </div>
                @if($payment->reference)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->reference }}</dd>
                    </div>
                @endif
                @if($payment->memo)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $payment->memo }}</dd>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Invoice Allocations') }}</h3>
            <div class="overflow-x-auto">
                <table class="datasheet">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th class="text-right">Invoice Total</th>
                            <th class="text-right">Amount Allocated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payment->invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('accounting.invoices.show', $invoice) }}" class="text-ink hover:text-gold">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>
                                <td class="text-ink-soft">
                                    {{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="numeric">
                                    {{ format_money($invoice->total) }}
                                </td>
                                <td class="numeric">
                                    {{ format_money($invoice->pivot->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-ink-soft">
                                    No allocations found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
