<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }} #{{ $invoice->invoice_number }}</x-slot>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-toolbar>
                <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
                <x-toolbar-button variant="ghost" href="{{ route('accounting.invoices.create') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New
                </x-toolbar-button>
                @if($invoice->status === 'draft')
                    <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                        @csrf
                        <x-toolbar-button variant="commit" type="submit">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Save
                        </x-toolbar-button>
                    </form>
                    <x-toolbar-button variant="commit" href="{{ route('accounting.invoices.edit', $invoice) }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Save & Send
                    </x-toolbar-button>
                @endif

                <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

                <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
                <x-toolbar-button variant="ghost" href="{{ route('accounting.customers.show', $invoice->customer) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Lookup Customer
                </x-toolbar-button>
                <x-toolbar-button variant="ghost" href="{{ route('accounting.quotations.index', ['customer_id' => $invoice->customer_id]) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Copy from Quote
                </x-toolbar-button>

                <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

                <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
                <x-toolbar-button variant="ghost" onclick="window.print()">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </x-toolbar-button>
                <x-toolbar-button variant="ghost">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Attach File
                </x-toolbar-button>
                @if($invoice->customer && $invoice->customer->email)
                    <x-toolbar-button variant="ghost" href="mailto:{{ $invoice->customer->email }}?subject=Invoice {{ $invoice->invoice_number }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email Invoice
                    </x-toolbar-button>
                @else
                    <x-toolbar-button variant="ghost" disabled title="No email on file for this customer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email Invoice
                    </x-toolbar-button>
                @endif

                <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <x-toolbar-button variant="ghost" class="!p-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </x-toolbar-button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Duplicate
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                Copy Link
                            </button>
                            @if($invoice->journalEntry)
                                <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    View Journal Entry
                                </a>
                            @endif
                            <a href="{{ route('accounting.invoices.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back to Invoices
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>

                <x-slot name="right">
                    @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                        <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <x-toolbar-button variant="danger" type="submit" onclick="return confirm('Are you sure you want to void this invoice?')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Void Invoice
                            </x-toolbar-button>
                        </form>
                    @endif
                </x-slot>
            </x-toolbar>

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
                        <dt class="text-sm font-medium text-gray-500">{{ __('Invoice Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @switch($invoice->status)
                                @case('draft')
                                    <span class="status-pill neutral">Draft</span>
                                    @break
                                @case('sent')
                                    <span class="status-pill neutral">Sent</span>
                                    @break
                                @case('paid')
                                    <span class="status-pill positive">Paid</span>
                                    @break
                                @case('overdue')
                                    <span class="status-pill negative">Overdue</span>
                                    @break
                                @case('void')
                                    <span class="status-pill neutral">Void</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Customer') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->customer->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Invoice Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Due Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->due_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->reference ?? '—' }}</dd>
                    </div>
                    @if($invoice->memo)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $invoice->memo }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Line Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Discount</th>
                                <th>Tax</th>
                                <th>Cost Center</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                    <td class="numeric">{{ $line->discount }}%</td>
                                    <td class="numeric">{{ $line->tax_rate }}%</td>
                                    <td>{{ $line->costCenter->name ?? '—' }}</td>
                                    <td class="numeric">{{ format_money($line->total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal:</span>
                            <span class="text-gray-900">{{ format_money($invoice->subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax:</span>
                            <span class="text-gray-900">{{ format_money($invoice->tax_total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total:</span>
                            <span class="text-gray-900">{{ format_money($invoice->total) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Paid:</span>
                            <span class="text-gray-900">{{ format_money($invoice->amount_paid) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t pt-2">
                            <span class="text-gray-800">Balance Due:</span>
                            <span class="text-gray-900">{{ format_money($invoice->balance_due) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Entry') }}</h3>
                    <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $invoice->journalEntry->reference }} — View Journal Entry
                    </a>
                </div>
            @endif

            @if($invoice->payments->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Payment History') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th class="text-right">Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $payment->reference ?? '—' }}</td>
                                        <td class="numeric">{{ format_money($payment->pivot->amount) }}</td>
                                        <td class="text-ink-soft">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
