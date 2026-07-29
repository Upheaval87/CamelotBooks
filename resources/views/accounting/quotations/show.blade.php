<x-app-layout>
    <x-slot name="header">{{ __('Quotation') }} {{ $quotation->quotation_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($quotation->status === 'draft')
                        <a href="{{ route('accounting.quotations.edit', $quotation) }}" class="tr-save">{{ __('Edit') }}</a>
                        @if($quotation->customer && $quotation->customer->email)
                            <form method="POST" action="{{ route('accounting.quotations.email', $quotation) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-item">{{ __('Email to Customer') }}</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('accounting.quotations.send', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-save">{{ __('Mark as Sent') }}</button></form>
                    @endif
                    @if($quotation->status === 'sent')
                        <form method="POST" action="{{ route('accounting.quotations.accept', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-save">{{ __('Accept') }}</button></form>
                        <form method="POST" action="{{ route('accounting.quotations.decline', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-archive">{{ __('Decline') }}</button></form>
                    @endif
                    @if(in_array($quotation->status, ['sent', 'accepted']))
                        <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-save">{{ __('Convert to Invoice') }}</button></form>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <a href="{{ route('accounting.quotations.print', $quotation) }}" target="_blank" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </a>
                </div>

                <div class="tr-spacer"></div>

                @if(in_array($quotation->status, ['draft', 'sent', 'accepted']))
                    <form method="POST" action="{{ route('accounting.quotations.void', $quotation) }}" class="inline" onsubmit="var r=prompt('{{ __('Enter void reason') }}:');if(!r)return false;this.void_reason.value=r;">
                        @csrf<input type="hidden" name="void_reason" value="" />
                        <button type="submit" class="tr-archive">{{ __('Void') }}</button>
                    </form>
                @endif

                <a href="{{ route('accounting.quotations.index') }}" class="tr-item">{{ __('Back to Quotations') }}</a>
            </x-record-toolbar>

            @if(session('success'))<div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>@endif

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Status')">
                        @switch($quotation->status)
                            @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span>@break
                            @case('sent') <span class="status-pill neutral">{{ __('Sent') }}</span>@break
                            @case('accepted') <span class="status-pill positive">{{ __('Accepted') }}</span>@break
                            @case('declined') <span class="status-pill negative">{{ __('Declined') }}</span>@break
                            @case('converted') <span class="status-pill positive">{{ __('Converted') }}</span>@break
                            @case('void') <span class="status-pill neutral">{{ __('Void') }}</span>@break
                        @endswitch
                    </x-detail-field>
                    <x-detail-field :label="__('Customer')" :value="$quotation->customer->name ?? '—'" />
                    <x-detail-field :label="__('Date')" :value="$quotation->quotation_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Valid Until')" :value="$quotation->valid_until?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Reference')" :value="$quotation->reference ?? '—'" />
                    <x-detail-field :label="__('Created By')" :value="$quotation->createdByUser->name ?? '—'" />
                    <x-detail-field :label="__('Description')" :value="$quotation->memo ?? '—'" />
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead><tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th class="text-right">{{ __('Qty') }}</th>
                            <th class="text-right">{{ __('Unit Price') }}</th>
                            <th class="text-right">{{ __('Tax') }}</th>
                            <th class="text-right">{{ __('Total') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach($quotation->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ number_format($line->quantity, 2) }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
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
                            <span class="balance-value">{{ format_money($quotation->amount) }}</span>
                        </div>
                        <div class="balance-row">
                            <span class="balance-label">{{ __('Tax') }}:</span>
                            <span class="balance-value">{{ format_money($quotation->tax_total) }}</span>
                        </div>
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Total') }}:</span>
                            <span class="balance-value">{{ format_money($quotation->total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
