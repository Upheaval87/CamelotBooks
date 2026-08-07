<x-app-layout>
    @php
        $quotationStatusBadge = match ($quotation->status) {
            'accepted', 'converted' => 'approved',
            'declined' => 'rejected',
            'sent' => 'pending',
            default => 'neutral',
        };
        $isDecisionState = in_array($quotation->status, ['sent'], true);
    @endphp

    <x-review.head
        :title="__('Quotation') . ' ' . $quotation->quotation_number"
        :back-url="route('accounting.quotations.index')"
        back-label="{{ __('Back to Quotations') }}"
    >
        <x-slot name="badge">
            <x-review.badge :variant="$quotationStatusBadge" :dot="in_array($quotation->status, ['sent', 'accepted', 'declined'], true)">
                @switch($quotation->status)
                    @case('draft') {{ __('Draft') }} @break
                    @case('sent') {{ __('Sent') }} @break
                    @case('accepted') {{ __('Accepted') }} @break
                    @case('declined') {{ __('Declined') }} @break
                    @case('converted') {{ __('Converted') }} @break
                    @case('void') {{ __('Void') }} @break
                @endswitch
            </x-review.badge>
        </x-slot>
    </x-review.head>

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
                        @can('quotations.send')
                            <form method="POST" action="{{ route('accounting.quotations.send', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-save">{{ __('Mark as Sent') }}</button></form>
                        @endcan
                    @endif
                    @if(in_array($quotation->status, ['sent', 'accepted']))
                        @can('quotations.convert')
                            <form method="POST" action="{{ route('accounting.quotations.convert-to-invoice', $quotation) }}" class="inline">@csrf<button type="submit" class="tr-save">{{ __('Convert to Invoice') }}</button></form>
                        @endcan
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
                    @can('quotations.void')
                        <form method="POST" action="{{ route('accounting.quotations.void', $quotation) }}" class="inline" onsubmit="return fbPromptForm(event, '{{ __('Enter void reason') }}:')">
                            @csrf<input type="hidden" name="void_reason" value="" />
                            <button type="submit" class="tr-archive">{{ __('Void') }}</button>
                        </form>
                    @endcan
                @endif

                <a href="{{ route('accounting.quotations.index') }}" class="tr-item">{{ __('Back to Quotations') }}</a>
            </x-record-toolbar>

            
            

            <div class="detail-page">
                <div class="detail-page-main">
                    <x-review.card title="{{ __('Quotation Details') }}" icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/>">
                        <div class="mt-[22px] grid grid-cols-1 gap-x-8 gap-y-[22px] md:grid-cols-2 lg:grid-cols-3">
                            <x-review.field label="{{ __('Quotation Number') }}" mono>{{ $quotation->quotation_number }}</x-review.field>
                            <x-review.field label="{{ __('Customer') }}">{{ $quotation->customer->name ?? '—' }}</x-review.field>
                            <x-review.field label="{{ __('Date') }}">{{ $quotation->quotation_date?->format('M d, Y') ?? '—' }}</x-review.field>
                            <x-review.field label="{{ __('Valid Until') }}">{{ $quotation->valid_until?->format('M d, Y') ?? '—' }}</x-review.field>
                            <x-review.field label="{{ __('Reference') }}" mono>{{ $quotation->reference ?? '—' }}</x-review.field>
                            <x-review.field label="{{ __('Created By') }}">{{ $quotation->createdByUser->name ?? '—' }}</x-review.field>
                            @if($quotation->memo)
                                <x-review.field label="{{ __('Description') }}" class="lg:col-span-3">{{ $quotation->memo ?? '—' }}</x-review.field>
                            @endif
                        </div>
                    </x-review.card>

                    @if($isDecisionState)
                        <x-review.decision title="{{ __('Review & Decide') }}" hint="{{ __('Accept to proceed with this quotation, or decline it.') }}">
                            <x-slot name="actions">
                                <form id="quotation-decline-form" method="POST" action="{{ route('accounting.quotations.decline', $quotation) }}" class="inline">@csrf</form>
                                <form id="quotation-accept-form" method="POST" action="{{ route('accounting.quotations.accept', $quotation) }}" class="inline">@csrf</form>
                                <x-review.btn variant="reject" type="submit" form="quotation-decline-form">{{ __('Decline') }}</x-review.btn>
                                <x-review.btn variant="primary" size="lg" type="submit" form="quotation-accept-form">{{ __('Accept') }}</x-review.btn>
                            </x-slot>
                        </x-review.decision>
                    @elseif(in_array($quotation->status, ['accepted', 'declined', 'converted'], true))
                        @php
                            $quotationOutcome = match ($quotation->status) {
                                'accepted' => ['chip' => 'ACCEPTED', 'tone' => 'approved', 'title' => __('Quotation accepted')],
                                'converted' => ['chip' => 'CONVERTED', 'tone' => 'approved', 'title' => __('Quotation converted to invoice')],
                                default => ['chip' => 'DECLINED', 'tone' => 'rejected', 'title' => __('Quotation declined')],
                            };
                        @endphp
                        <x-review.outcome
                            :title="$quotationOutcome['title']"
                            :description="__('This quotation is no longer open for decision.')"
                            :chip="$quotationOutcome['chip']"
                            :tone="$quotationOutcome['tone']"
                        />
                    @endif

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
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
                                            <td>{{ $line->description }}</td>
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

                    @if($quotation->attachments->isNotEmpty())
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('Attachments') }}</p>
                            <ul class="divide-y divide-line border border-shell rounded-[14px] bg-[#fbfcfe] overflow-hidden">
                                @foreach($quotation->attachments as $attachment)
                                    <li class="flex items-center gap-3 px-4 py-2.5 text-[13px]">
                                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" class="flex-1 min-w-0 truncate text-gold-700 hover:text-gold-800 font-semibold">{{ $attachment->name }}</a>
                                        <span class="shrink-0 text-[11px] text-slate-400">{{ format_bytes($attachment->file_size) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.quotations.print', $quotation), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.quotations.index'), 'icon' => 'back', 'title' => __('Back to Quotations')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
