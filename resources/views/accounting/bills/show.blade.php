<x-app-layout>
    <x-slot name="header">{{ __('Bill') }} #{{ $bill->bill_number }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button href="{{ route('accounting.bills.index') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                New
            </x-toolbar-button>
            @if($bill->status === 'draft')
                <x-toolbar-button variant="commit" href="{{ route('accounting.bills.edit', $bill) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Save
                </x-toolbar-button>
                <form method="POST" action="{{ route('accounting.bills.submit', $bill) }}" class="inline">
                    @csrf
                    <x-toolbar-button variant="commit" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Submit for Approval
                    </x-toolbar-button>
                </form>
            @endif

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Lookup Vendor
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Copy from PO
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Payment History
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>
            <x-toolbar-button class="relative">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                Attach Bill/Receipt
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-atlas-danger rounded-full">{{ $bill->attachments_count ?? 0 }}</span>
            </x-toolbar-button>
            @if($bill->vendor && $bill->vendor->email)
                <x-toolbar-button href="mailto:{{ $bill->vendor->email }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email Vendor
                </x-toolbar-button>
            @else
                <x-toolbar-button disabled title="No email on file">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Email Vendor
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
                            Duplicate
                        </button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Convert to Recurring
                        </button>
                        <a href="{{ route('accounting.bills.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Bills
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            <x-slot name="right">
                @if(!in_array($bill->status, ['void', 'paid', 'draft']))
                    <form method="POST" action="{{ route('accounting.bills.void', $bill) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <x-toolbar-button variant="danger" type="submit" onclick="return confirm('Are you sure you want to cancel this bill?')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Cancel Bill
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
                    <dt class="text-sm font-medium text-gray-500">{{ __('Bill Number') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $bill->bill_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        @switch($bill->status)
                            @case('draft')
                                <span class="status-pill neutral">Draft</span>
                                @break
                            @case('pending')
                                <span class="status-pill neutral">Pending Approval</span>
                                @break
                            @case('approved')
                                <span class="status-pill neutral">Approved</span>
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
                    <dt class="text-sm font-medium text-gray-500">{{ __('Vendor') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $bill->vendor->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Bill Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $bill->bill_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Due Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $bill->due_date?->format('M d, Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $bill->reference ?? '—' }}</dd>
                </div>
                @if($bill->memo)
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $bill->memo }}</dd>
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
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bill->lines as $line)
                            <tr>
                                <td>{{ $line->product->name ?? '—' }}</td>
                                <td class="text-ink-soft">{{ $line->description }}</td>
                                <td class="numeric">{{ $line->quantity }}</td>
                                <td class="numeric">{{ format_money($line->unit_price) }}</td>
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
                        <span class="text-gray-900">{{ format_money($bill->subtotal) }}</span>
                    </div>
                    @if($bill->tax_total > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tax:</span>
                            <span class="text-gray-900">{{ format_money($bill->tax_total) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm font-semibold border-t pt-2">
                        <span class="text-gray-800">Total:</span>
                        <span class="text-gray-900">{{ format_money($bill->total) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Paid:</span>
                        <span class="text-gray-900">{{ format_money($bill->amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold border-t pt-2">
                        <span class="text-gray-800">Balance Due:</span>
                        <span class="text-gray-900">{{ format_money($bill->balance_due) }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($bill->journalEntry)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Entry') }}</h3>
                <a href="{{ route('accounting.journal-entries.show', $bill->journalEntry) }}" class="text-ink hover:text-gold">
                    {{ $bill->journalEntry->reference }} — View Journal Entry
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
