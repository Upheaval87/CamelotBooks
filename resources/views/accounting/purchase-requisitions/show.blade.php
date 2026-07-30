<x-app-layout>
    <x-slot name="header">{{ __('Requisition') }} #{{ $requisition->requisition_number }}</x-slot>

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
                    @if($requisition->status === 'submitted')
                        @can('purchase-requisitions.approve')
                            <form method="POST" action="{{ route('accounting.purchase-requisitions.approve', $requisition) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Approve') }}</button>
                            </form>
                        @endcan
                        @can('purchase-requisitions.reject')
                            <form method="POST" action="{{ route('accounting.purchase-requisitions.reject', $requisition) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-archive">{{ __('Reject') }}</button>
                            </form>
                        @endcan
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

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Requisition Number')" :value="$requisition->requisition_number" />
                    <x-detail-field :label="__('Status')" noBorder>
                        @switch($requisition->status)
                            @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                            @case('submitted') <span class="status-pill neutral">{{ __('Submitted') }}</span> @break
                            @case('approved') <span class="status-pill positive">{{ __('Approved') }}</span> @break
                            @case('rejected') <span class="status-pill negative">{{ __('Rejected') }}</span> @break
                        @endswitch
                    </x-detail-field>
                    <x-detail-field :label="__('Date')" :value="$requisition->date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Created By')" :value="$requisition->createdBy->name ?? '—'" />
                    <x-detail-field :label="__('Branch')" :value="$requisition->branch->name ?? '—'" />
                    <x-detail-field :label="__('Cost Center')" :value="$requisition->costCenter->name ?? '—'" />
                    @if($requisition->approvedBy)
                        <x-detail-field :label="__('Approved By')" :value="$requisition->approvedBy->name" />
                        <x-detail-field :label="__('Approved At')" :value="$requisition->approved_at?->format('M d, Y g:i A') ?? '—'" />
                    @endif
                    @if($requisition->memo)
                        <x-detail-field :label="__('Description')" :value="$requisition->memo" class="col-span-3" />
                    @endif
                </div>
            </div>

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
