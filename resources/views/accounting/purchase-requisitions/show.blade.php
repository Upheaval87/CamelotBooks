<x-app-layout>
    @php
        $reqStatusBadge = match ($requisition->status) {
            'approved' => 'approved',
            'rejected' => 'rejected',
            'submitted' => 'pending',
            default => 'neutral',
        };
    @endphp

    <x-review.head
        :title="__('Requisition') . ' #' . $requisition->requisition_number"
        :back-url="route('accounting.purchase-requisitions.index')"
        back-label="{{ __('Back to Requisitions') }}"
    >
        <x-slot name="badge">
            <x-review.badge :variant="$reqStatusBadge" :dot="in_array($requisition->status, ['submitted', 'approved', 'rejected'], true)">
                @switch($requisition->status)
                    @case('draft') {{ __('Draft') }} @break
                    @case('submitted') {{ __('Submitted') }} @break
                    @case('approved') {{ __('Approved') }} @break
                    @case('rejected') {{ __('Rejected') }} @break
                @endswitch
            </x-review.badge>
        </x-slot>
    </x-review.head>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($requisition->status === 'draft')
                        @can('purchase-requisitions.submit')
                            <form method="POST" action="{{ route('accounting.purchase-requisitions.submit', $requisition) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Submit for Approval') }}</button>
                            </form>
                        @endcan
                        <a href="{{ route('accounting.purchase-requisitions.edit', $requisition) }}" class="tr-save">{{ __('Edit') }}</a>
                    @endif
                    @if($requisition->status === 'approved')
                        <a href="{{ route('accounting.purchase-orders.create', ['requisition_id' => $requisition->id]) }}" class="tr-save">{{ __('Create Purchase Order') }}</a>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                <a href="{{ route('accounting.purchase-requisitions.index') }}" class="tr-item">{{ __('Back to Requisitions') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

            

            <x-review.card title="{{ __('Requisition Details') }}" icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'/>">
                <div class="mt-[22px] grid grid-cols-1 gap-x-8 gap-y-[22px] md:grid-cols-2 lg:grid-cols-3">
                    <x-review.field label="{{ __('Requisition Number') }}" mono>{{ $requisition->requisition_number }}</x-review.field>
                    <x-review.field label="{{ __('Date') }}">{{ $requisition->date?->format('M d, Y') ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Created By') }}">{{ $requisition->createdBy->name ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Branch') }}">{{ $requisition->branch->name ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Cost Center') }}">{{ $requisition->costCenter->name ?? '—' }}</x-review.field>
                    @if($requisition->approvedBy)
                        <x-review.field label="{{ __('Approved By') }}">{{ $requisition->approvedBy->name }}</x-review.field>
                        <x-review.field label="{{ __('Approved At') }}">{{ $requisition->approved_at?->format('M d, Y g:i A') ?? '—' }}</x-review.field>
                    @endif
                    @if($requisition->memo)
                        <x-review.field label="{{ __('Description') }}" class="lg:col-span-3">{{ $requisition->memo }}</x-review.field>
                    @endif
                </div>
            </x-review.card>

            @if($requisition->status === 'submitted')
                <x-review.decision title="{{ __('Review & Decide') }}" hint="{{ __('Approve to create a purchase order, or reject this requisition.') }}">
                    <x-slot name="actions">
                        <form id="req-reject-form" method="POST" action="{{ route('accounting.purchase-requisitions.reject', $requisition) }}" class="inline">
                            @csrf
                        </form>
                        <form id="req-approve-form" method="POST" action="{{ route('accounting.purchase-requisitions.approve', $requisition) }}" class="inline">
                            @csrf
                        </form>
                        <x-review.btn variant="reject" type="submit" form="req-reject-form">{{ __('Reject') }}</x-review.btn>
                        <x-review.btn variant="primary" size="lg" type="submit" form="req-approve-form">{{ __('Approve') }}</x-review.btn>
                    </x-slot>
                </x-review.decision>
            @elseif($requisition->status === 'approved')
                <x-review.outcome
                    title="{{ __('Requisition approved') }}"
                    :description="__('Approved by') . ' ' . ($requisition->approvedBy->name ?? '—') . ($requisition->approved_at ? ' — ' . $requisition->approved_at->format('M d, Y g:i A') : '')"
                    chip="APPROVED"
                />
            @elseif($requisition->status === 'rejected')
                <x-review.outcome
                    title="{{ __('Requisition rejected') }}"
                    :description="__('This requisition was not approved.')"
                    chip="REJECTED"
                    tone="rejected"
                />
            @endif

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Line Items') }}</p>
                <div class="overflow-x-auto">
                    <table class="record-datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th class="text-right">{{ __('Qty') }}</th>
                                <th class="text-right">{{ __('Est. Unit Cost') }}</th>
                                <th>{{ __('Cost Center') }}</th>
                                <th class="text-right">{{ __('Est. Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requisition->lines as $line)
                                <tr>
                                    <td>{{ $line->description }}</td>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="numeric">{{ $line->quantity }}</td>
                                    <td class="numeric">{{ $line->estimated_unit_cost ? format_money($line->estimated_unit_cost) : '—' }}</td>
                                    <td>{{ $line->costCenter->name ?? '—' }}</td>
                                    <td class="numeric">{{ $line->estimated_total ? format_money($line->estimated_total) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="balance-grid">
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Total Estimated') }}:</span>
                            <span class="balance-value">{{ format_money($requisition->lines->sum('estimated_total')) }}</span>
                        </div>
                    </div>
                </div>
            </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.purchase-requisitions.index'), 'icon' => 'back', 'title' => __('Back to Requisitions')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
