<x-app-layout>
    <x-list-header title="{{ __('Bill') }} #{{ $bill->bill_number }}" />

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.bills.create') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($bill->status === 'draft')
                        <a href="{{ route('accounting.bills.edit', $bill) }}" class="tr-save">{{ __('Save') }}</a>
                        <form method="POST" action="{{ route('accounting.bills.submit', $bill) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Submit for Approval') }}</button>
                        </form>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <a href="{{ route('accounting.vendors.show', $bill->vendor) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        {{ __('Lookup Vendor') }}
                    </a>
                    <a href="{{ route('accounting.purchase-orders.index', ['vendor_id' => $bill->vendor_id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        {{ __('Copy from PO') }}
                    </a>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        {{ __('Payment History') }}
                    </button>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                    <button type="button" class="tr-item relative">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('Attach Bill/Receipt') }}
                        @if(($bill->attachments_count ?? 0) > 0)
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-atlas-danger rounded-full">{{ $bill->attachments_count }}</span>
                        @endif
                    </button>
                    @if($bill->vendor && $bill->vendor->email)
                        <a href="mailto:{{ $bill->vendor->email }}" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Vendor') }}
                        </a>
                    @else
                        <button type="button" disabled title="{{ __('No email on file') }}" class="tr-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Vendor') }}
                        </button>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                @if(!in_array($bill->status, ['void', 'paid', 'draft']))
                    @can('bills.void')
                        <form method="POST" action="{{ route('accounting.bills.void', $bill) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tr-archive" onclick="return confirm('{{ __('Are you sure you want to cancel this bill?') }}')">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                {{ __('Cancel Bill') }}
                            </button>
                        </form>
                    @endcan
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
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ __('Export') }}
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ __('Convert to Recurring') }}
                            </button>
                            <a href="{{ route('accounting.bills.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Bills') }}
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

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Bill Number')" :value="$bill->bill_number" />
                            <x-detail-field :label="__('Status')" noBorder>
                                @switch($bill->status)
                                    @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                                    @case('pending') <span class="status-pill neutral">{{ __('Pending Approval') }}</span> @break
                                    @case('approved') <span class="status-pill neutral">{{ __('Approved') }}</span> @break
                                    @case('paid') <span class="status-pill positive">{{ __('Paid') }}</span> @break
                                    @case('overdue') <span class="status-pill negative">{{ __('Overdue') }}</span> @break
                                    @case('void') <span class="status-pill neutral">{{ __('Void') }}</span> @break
                                @endswitch
                            </x-detail-field>
                            <x-detail-field :label="__('Vendor')" :value="$bill->vendor->name ?? '—'" />
                            <x-detail-field :label="__('Bill Date')" :value="$bill->bill_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Due Date')" :value="$bill->due_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Reference')" :value="$bill->reference ?? '—'" />
                            @if($bill->memo)
                                <x-detail-field :label="__('Description')" :value="$bill->memo" class="col-span-3" />
                            @endif
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-right">{{ __('Qty') }}</th>
                                        <th class="text-right">{{ __('Unit Price') }}</th>
                                        <th class="text-right">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bill->lines as $line)
                                        <tr>
                                            <td>{{ $line->product->name ?? '—' }}</td>
                                            <td>{{ $line->description }}</td>
                                            <td class="numeric">{{ $line->quantity }}</td>
                                            <td class="numeric">{{ format_money($line->unit_price) }}</td>
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
                                    <span class="balance-value">{{ format_money($bill->subtotal) }}</span>
                                </div>
                                @if($bill->tax_total > 0)
                                    <div class="balance-row">
                                        <span class="balance-label">{{ __('Tax') }}:</span>
                                        <span class="balance-value">{{ format_money($bill->tax_total) }}</span>
                                    </div>
                                @endif
                                <div class="balance-total-row">
                                    <span class="balance-label">{{ __('Total') }}:</span>
                                    <span class="balance-value">{{ format_money($bill->total) }}</span>
                                </div>
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Paid') }}:</span>
                                    <span class="balance-value">{{ format_money($bill->amount_paid) }}</span>
                                </div>
                                <div class="balance-total-row">
                                    <span class="balance-label">{{ __('Balance Due') }}:</span>
                                    <span class="balance-value">{{ format_money($bill->balance_due) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($bill->journalEntry)
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Entry') }}</p>
                            <a href="{{ route('accounting.journal-entries.show', $bill->journalEntry) }}" class="text-ink hover:text-gold">
                                {{ $bill->journalEntry->reference }} — {{ __('View Journal Entry') }}
                            </a>
                        </div>
                    @endif
                </div>
                @php
                    $billInsightLinks = [];
                    if ($bill->vendor && $bill->vendor->email) {
                        $billInsightLinks[] = ['route' => 'mailto:' . $bill->vendor->email, 'icon' => 'email', 'title' => __('Email Vendor')];
                    }
                @endphp
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => $billInsightLinks],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.bills.index'), 'icon' => 'back', 'title' => __('Back to Bills')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
