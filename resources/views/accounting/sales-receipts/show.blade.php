<x-app-layout>
    <x-list-header title="{{ __('Sales Receipt') }} {{ $salesReceipt->receipt_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($salesReceipt->status === 'draft')
                        @can('sales-receipts.post')
                            <form method="POST" action="{{ route('accounting.sales-receipts.post', $salesReceipt) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Post Receipt') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if($salesReceipt->status === 'posted' && $salesReceipt->customer && $salesReceipt->customer->email)
                        <form method="POST" action="{{ route('accounting.sales-receipts.email', $salesReceipt) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-item">{{ __('Email Receipt') }}</button>
                        </form>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <a href="{{ route('accounting.sales-receipts.print', $salesReceipt) }}" target="_blank" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </a>
                </div>

                <div class="tr-spacer"></div>

                @if($salesReceipt->status === 'posted')
                    @can('sales-receipts.void')
                        <form method="POST" action="{{ route('accounting.sales-receipts.void', $salesReceipt) }}" class="inline" onsubmit="return prompt('{{ __('Enter void reason') }}:')">
                            @csrf
                            <input type="hidden" name="void_reason" value="Voided via UI" />
                            <button type="submit" class="tr-archive">{{ __('Void Receipt') }}</button>
                        </form>
                    @endcan
                @endif

                <a href="{{ route('accounting.sales-receipts.index') }}" class="tr-item">{{ __('Back to Sales Receipts') }}</a>
            </x-record-toolbar>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="detail-grid">
                            <x-detail-field :label="__('Status')" noBorder>
                                @switch($salesReceipt->status)
                                    @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                                    @case('posted') <span class="status-pill positive">{{ __('Posted') }}</span> @break
                                    @case('voided') <span class="status-pill negative">{{ __('Voided') }}</span> @break
                                @endswitch
                            </x-detail-field>
                            <x-detail-field :label="__('Customer')" :value="$salesReceipt->customer->name ?? __('Walk-in')" />
                            <x-detail-field :label="__('Date')" :value="$salesReceipt->receipt_date?->format('M d, Y') ?? '—'" />
                            <x-detail-field :label="__('Reference')" :value="$salesReceipt->reference ?? '—'" />
                            <x-detail-field :label="__('Branch')" :value="$salesReceipt->branch->name ?? '—'" />
                            @if($salesReceipt->journal_entry_id)
                                <x-detail-field :label="__('Journal Entry')">
                                    <a href="{{ route('accounting.journal-entries.show', $salesReceipt->journal_entry_id) }}" class="text-ink hover:text-gold">JE-{{ str_pad($salesReceipt->journal_entry_id, 4, '0', STR_PAD_LEFT) }}</a>
                                </x-detail-field>
                            @endif
                            <x-detail-field :label="__('Description')" :value="$salesReceipt->memo ?? '—'" />
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
                                        <th class="text-right">{{ __('Discount') }}</th>
                                        <th class="text-right">{{ __('Tax') }}</th>
                                        <th class="text-right">{{ __('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salesReceipt->lines as $line)
                                        <tr>
                                            <td>{{ $line->product->name ?? '—' }}</td>
                                            <td>{{ $line->description }}</td>
                                            <td class="numeric">{{ number_format($line->quantity, 2) }}</td>
                                            <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                            <td class="numeric">{{ number_format($line->discount, 2) }}</td>
                                            <td class="numeric">{{ format_money($line->tax_amount) }}</td>
                                            <td class="numeric">{{ format_money($line->line_total) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end mt-4">
                            <div class="balance-grid">
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Subtotal') }}:</span>
                                    <span class="balance-value">{{ format_money($salesReceipt->subtotal) }}</span>
                                </div>
                                <div class="balance-row">
                                    <span class="balance-label">{{ __('Tax') }}:</span>
                                    <span class="balance-value">{{ format_money($salesReceipt->tax_total) }}</span>
                                </div>
                                <div class="balance-total-row">
                                    <span class="balance-label">{{ __('Total') }}:</span>
                                    <span class="balance-value">{{ format_money($salesReceipt->total) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Payments') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Method') }}</th>
                                        <th class="text-right">{{ __('Amount') }}</th>
                                        <th class="text-right">{{ __('Cash Tendered') }}</th>
                                        <th class="text-right">{{ __('Change') }}</th>
                                        <th>{{ __('Reference') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($salesReceipt->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->paymentMethod->name ?? '—' }}</td>
                                            <td class="numeric">{{ format_money($payment->amount) }}</td>
                                            <td class="numeric">{{ $payment->cash_tendered ? format_money($payment->cash_tendered) : '—' }}</td>
                                            <td class="numeric">{{ $payment->change_given ? format_money($payment->change_given) : '—' }}</td>
                                            <td>{{ $payment->reference_number ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.sales-receipts.print', $salesReceipt), 'icon' => 'print', 'title' => __('Print')],
                        ['route' => route('accounting.sales-receipts.email', $salesReceipt), 'icon' => 'email', 'title' => __('Email Receipt')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.sales-receipts.index'), 'icon' => 'back', 'title' => __('Back to Sales Receipts')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
