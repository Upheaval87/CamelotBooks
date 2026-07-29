<x-app-layout>
    <x-slot name="header">{{ __('Invoice') }} #{{ $invoice->invoice_number }}</x-slot>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.invoices.create') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($invoice->status === 'draft')
                        <form method="POST" action="{{ route('accounting.invoices.post', $invoice) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Save') }}</button>
                        </form>
                        <a href="{{ route('accounting.invoices.edit', $invoice) }}" class="tr-save">{{ __('Save & Send') }}</a>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <a href="{{ route('accounting.customers.show', $invoice->customer) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        {{ __('Lookup Customer') }}
                    </a>
                    <a href="{{ route('accounting.quotations.index', ['customer_id' => $invoice->customer_id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        {{ __('Copy from Quote') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('Attach File') }}
                    </button>
                    @if($invoice->customer && $invoice->customer->email)
                        <a href="mailto:{{ $invoice->customer->email }}?subject=Invoice {{ $invoice->invoice_number }}" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Invoice') }}
                        </a>
                    @else
                        <button type="button" disabled title="{{ __('No email on file') }}" class="tr-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Invoice') }}
                        </button>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                @if(in_array($invoice->status, ['sent', 'paid', 'overdue']))
                    <form method="POST" action="{{ route('accounting.invoices.void', $invoice) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tr-archive" onclick="return confirm('{{ __('Are you sure you want to void this invoice?') }}')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            {{ __('Void Invoice') }}
                        </button>
                    </form>
                @endif

                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="tr-more" aria-label="{{ __('More actions') }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                {{ __('Duplicate') }}
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                {{ __('Copy Link') }}
                            </button>
                            @if($invoice->journalEntry)
                                <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ __('View Journal Entry') }}
                                </a>
                            @endif
                            <a href="{{ route('accounting.invoices.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Invoices') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

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

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Invoice Number')" :value="$invoice->invoice_number" />
                    <x-detail-field :label="__('Status')">
                        @switch($invoice->status)
                            @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                            @case('sent') <span class="status-pill neutral">{{ __('Sent') }}</span> @break
                            @case('paid') <span class="status-pill positive">{{ __('Paid') }}</span> @break
                            @case('overdue') <span class="status-pill negative">{{ __('Overdue') }}</span> @break
                            @case('void') <span class="status-pill neutral">{{ __('Void') }}</span> @break
                        @endswitch
                    </x-detail-field>
                    <x-detail-field :label="__('Customer')" :value="$invoice->customer->name ?? '—'" />
                    <x-detail-field :label="__('Invoice Date')" :value="$invoice->invoice_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Due Date')" :value="$invoice->due_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Reference')" :value="$invoice->reference ?? '—'" />
                    @if($invoice->memo)
                        <x-detail-field :label="__('Memo')" :value="$invoice->memo" class="col-span-3" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th class="text-right">{{ __('Qty') }}</th>
                                <th class="text-right">{{ __('Unit Price') }}</th>
                                <th class="text-right">{{ __('Discount') }}</th>
                                <th>{{ __('Tax') }}</th>
                                <th>{{ __('Cost Center') }}</th>
                                <th class="text-right">{{ __('Total') }}</th>
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
                    <div class="balance-grid">
                        <div class="balance-row">
                            <span class="balance-label">{{ __('Subtotal') }}:</span>
                            <span class="balance-value">{{ format_money($invoice->subtotal) }}</span>
                        </div>
                        <div class="balance-row">
                            <span class="balance-label">{{ __('Tax') }}:</span>
                            <span class="balance-value">{{ format_money($invoice->tax_total) }}</span>
                        </div>
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Total') }}:</span>
                            <span class="balance-value">{{ format_money($invoice->total) }}</span>
                        </div>
                        <div class="balance-row">
                            <span class="balance-label">{{ __('Paid') }}:</span>
                            <span class="balance-value">{{ format_money($invoice->amount_paid) }}</span>
                        </div>
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Balance Due') }}:</span>
                            <span class="balance-value">{{ format_money($invoice->balance_due) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->journalEntry)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Entry') }}</p>
                    <a href="{{ route('accounting.journal-entries.show', $invoice->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $invoice->journalEntry->reference }} — {{ __('View Journal Entry') }}
                    </a>
                </div>
            @endif

            @if($invoice->payments->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Payment History') }}</p>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th class="text-right">{{ __('Amount') }}</th>
                                    <th>{{ __('Method') }}</th>
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
