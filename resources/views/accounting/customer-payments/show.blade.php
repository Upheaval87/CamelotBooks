<x-app-layout>
    <x-list-header title="{{ __('Customer Payment') }}" />

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('New') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    @if($payment->invoices->isNotEmpty())
                        <a href="{{ route('accounting.invoices.show', $payment->invoices->first()) }}" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            {{ __('Lookup Invoice') }}
                        </a>
                    @else
                        <button type="button" disabled class="tr-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            {{ __('Lookup Invoice') }}
                        </button>
                    @endif
                    <a href="{{ route('accounting.customer-payments.create', ['customer_id' => $payment->customer_id]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        {{ __('Apply to Invoice') }}
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
                    @if($payment->customer && $payment->customer->email)
                        <a href="mailto:{{ $payment->customer->email }}?subject=Payment Receipt - {{ $payment->reference ?? $payment->id }}" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Receipt') }}
                        </a>
                    @else
                        <button type="button" disabled title="{{ __('No email on file') }}" class="tr-item opacity-40 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('Email Receipt') }}
                        </button>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                @if($payment->status !== 'void')
                    <form method="POST" action="{{ route('accounting.customer-payments.void', $payment) }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tr-archive" onclick="return confirm('{{ __('Are you sure you want to void this payment?') }}')">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            {{ __('Void Payment') }}
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
                                {{ __('Duplicate Payment') }}
                            </button>
                            <a href="{{ route('accounting.customers.show', $payment->customer) }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ __('View Customer') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

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
                    <x-detail-field :label="__('Customer')" :value="$payment->customer->name ?? '—'" />
                    <x-detail-field :label="__('Status')" noBorder>
                        @if($payment->status === 'void')
                            <span class="status-pill neutral">{{ __('Void') }}</span>
                        @else
                            <span class="status-pill positive">{{ __('Completed') }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Payment Date')" :value="$payment->payment_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Amount')" value-class="text-2xl font-bold text-ink">
                        {{ format_money($payment->amount) }}
                    </x-detail-field>
                    <x-detail-field :label="__('Payment Method')" :value="ucfirst(str_replace('_', ' ', $payment->payment_method))" />
                    <x-detail-field :label="__('Bank Account')" :value="$payment->bankAccount->name ?? '—'" />
                    @if($payment->reference)
                        <x-detail-field :label="__('Reference')" :value="$payment->reference" />
                    @endif
                    @if($payment->memo)
                        <x-detail-field :label="__('Description')" :value="$payment->memo" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Invoice Allocations') }}</p>
                <div class="overflow-x-auto">
                    <table class="record-datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice #') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-right">{{ __('Invoice Total') }}</th>
                                <th class="text-right">{{ __('Amount Allocated') }}</th>
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
                                    <td>
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
                                    <td colspan="4" class="text-center">
                                        {{ __('No allocations found.') }}
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
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                        ['route' => $payment->customer && $payment->customer->email ? 'mailto:'.$payment->customer->email.'?subject=Payment Receipt - '.($payment->reference ?? $payment->id) : '#', 'icon' => 'email', 'title' => __('Email Receipt')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => $payment->customer ? route('accounting.customers.show', $payment->customer) : route('accounting.customers.index'), 'icon' => 'back', 'title' => __('Back to Customer')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
