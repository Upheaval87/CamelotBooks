<x-app-layout>
    <x-list-header title="{{ __('Vendor Detail') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.bills.create', ['vendor_id' => $vendor->id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('New Bill') }}
                    </a>
                    <a href="{{ route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        {{ __('New Payment') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reports') }}</span>
                    <a href="{{ route('accounting.bills.index', ['vendor_id' => $vendor->id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        {{ __('Transaction History') }}
                    </a>
                    <a href="{{ route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        {{ __('View Statement') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print Statement') }}
                    </button>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('Attach File') }}
                    </button>
                    @if($vendor->email)
                        <a href="mailto:{{ $vendor->email }}" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Statement') }}
                        </a>
                    @else
                        <button type="button" disabled title="{{ __('No email on file') }}" class="tr-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Statement') }}
                        </button>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                <span class="status-pill positive">{{ __('Open Balance:') }} {{ format_money($balanceDue) }}</span>

                <div class="tr-divider"></div>

                <a href="{{ route('accounting.vendors.edit', $vendor) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Save') }}
                </a>

                @can('vendors.void')
                    <form method="POST" action="{{ route('accounting.vendors.toggle', $vendor) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to') }} {{ $vendor->is_active ? __('deactivate') : __('activate') }} {{ __('this vendor?') }}')">
                        @csrf
                        <button type="submit" class="tr-archive">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            {{ $vendor->is_active ? __('Archive') : __('Activate') }}
                        </button>
                    </form>
                @endcan

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
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                {{ __('Add Note') }}
                            </button>
                            <a href="{{ route('accounting.vendors.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Vendors') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field label="{{ __('Name') }}" strong>{{ $vendor->name }}</x-detail-field>
                            <x-detail-field label="{{ __('Display Name') }}" strong>{{ $vendor->display_name ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Email') }}">{{ $vendor->email ?? '—' }}</x-detail-field>

                            <x-detail-field label="{{ __('Phone') }}">{{ $vendor->phone ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Payment Terms') }}">{{ str_replace('_', ' ', ucfirst($vendor->payment_terms ?? 'due_on_receipt')) }}</x-detail-field>
                            <x-detail-field label="{{ __('Currency') }}">{{ $vendor->currency ?? 'USD' }}</x-detail-field>

                            <x-detail-field label="{{ __('Status') }}" noBorder>
                                @if($vendor->is_active)
                                    <span class="status-pill positive">{{ __('Active') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('Inactive') }}</span>
                                @endif
                            </x-detail-field>
                            <x-detail-field label="{{ __('Billing Address') }}">{{ $vendor->billing_address ?? '—' }}</x-detail-field>
                            <x-detail-field label="{{ __('Remit To Address') }}">{{ $vendor->remit_to_address ?? '—' }}</x-detail-field>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Balance') }}</p>
                        <div class="balance-grid">
                            <x-detail-field label="{{ __('Opening Balance') }}">{{ format_money($vendor->opening_balance ?? 0) }}</x-detail-field>
                            <x-detail-field label="{{ __('Opening Balance Date') }}">{{ $vendor->opening_balance_date?->format('M d, Y') ?? '—' }}</x-detail-field>
                        </div>
                        <div class="balance-total-row">
                            <p class="detail-lbl">{{ __('Open Balance') }}</p>
                            <span class="balance-amount {{ $balanceDue > 0 ? 'text-brick' : '' }}">{{ format_money($balanceDue) }}</span>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Transaction History') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Reference') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-right">{{ __('Amount') }}</th>
                                        <th class="text-right">{{ __('Paid') }}</th>
                                        <th class="text-right">{{ __('Balance') }}</th>
                                        <th class="text-center">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $txn)
                                        <tr>
                                            <td>
                                                @if($txn['type'] === 'Bill')
                                                    <span class="text-gold-700">{{ $txn['type'] }}</span>
                                                @else
                                                    <span class="text-green-600">{{ $txn['type'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $txn['date']?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td>
                                                {{ $txn['reference'] }}
                                            </td>
                                            <td>
                                                {{ $txn['description'] }}
                                            </td>
                                            <td class="numeric">
                                                {{ format_money(abs($txn['amount'])) }}
                                            </td>
                                            <td class="text-right">
                                                {{ format_money($txn['paid']) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $txn['balance'] > 0 ? 'text-brick' : 'text-ink' }}">
                                                {{ format_money(abs($txn['balance'])) }}
                                            </td>
                                            <td class="text-center">
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
                                        <tr class="empty-row">
                                            <td colspan="8" class="text-center text-ink-soft py-7">
                                                {{ __('No transactions found.') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.bills.index', ['vendor_id' => $vendor->id]), 'icon' => 'invoice', 'title' => __('View Bills')],
                        ['route' => route('accounting.vendor-payments.create', ['vendor_id' => $vendor->id]), 'icon' => 'payment', 'title' => __('Record Payment')],
                        ['route' => route('accounting.reports.vendor-statement', ['vendor_id' => $vendor->id]), 'icon' => 'statement', 'title' => __('View Statement')],
                        ['route' => route('accounting.vendors.show', $vendor), 'icon' => 'print', 'title' => __('Print Statement')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.vendors.index'), 'icon' => 'back', 'title' => __('Back to Vendors')],
                    ]],
                ]" />
            </div>

        </div>
    </div>
</x-app-layout>
