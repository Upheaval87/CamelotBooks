<x-app-layout>
    <x-slot name="header">{{ __('Requisition') }} #{{ $requisition->requisition_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        @if($requisition->status === 'draft')
            <form method="POST" action="{{ route('accounting.purchase-requisitions.submit', $requisition) }}" class="inline">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Submit for Approval') }}</x-button>
            </form>
            <x-button variant="primary" href="{{ route('accounting.purchase-requisitions.edit', $requisition) }}">{{ __('Edit') }}</x-button>
        @endif
        @if($requisition->status === 'submitted')
            <form method="POST" action="{{ route('accounting.purchase-requisitions.approve', $requisition) }}" class="inline">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Approve') }}</x-button>
            </form>
            <form method="POST" action="{{ route('accounting.purchase-requisitions.reject', $requisition) }}" class="inline">
                @csrf
                <x-button variant="ghost" type="submit">{{ __('Reject') }}</x-button>
            </form>
        @endif
        @if($requisition->status === 'approved')
            <x-button variant="primary" href="{{ route('accounting.purchase-orders.create', ['requisition_id' => $requisition->id]) }}">{{ __('Create Purchase Order') }}</x-button>
                    </a>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.purchase-requisitions.index') }}">{{ __('Back to Requisitions') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Requisition Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $requisition->requisition_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @switch($requisition->status)
                                @case('draft')
                                    <span class="status-pill neutral">Draft</span>
                                    @break
                                @case('submitted')
                                    <span class="status-pill neutral">Submitted</span>
                                    @break
                                @case('approved')
                                    <span class="status-pill positive">Approved</span>
                                    @break
                                @case('rejected')
                                    <span class="status-pill negative">Rejected</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $requisition->date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $requisition->createdBy->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Branch') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $requisition->branch->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Cost Center') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $requisition->costCenter->name ?? '—' }}</dd>
                    </div>
                    @if($requisition->memo)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $requisition->memo }}</dd>
                        </div>
                    @endif
                    @if($requisition->approvedBy)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Approved By') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $requisition->approvedBy->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Approved At') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $requisition->approved_at?->format('M d, Y g:i A') ?? '—' }}</dd>
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
                                <th>Description</th>
                                <th>Product</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Est. Unit Cost</th>
                                <th>Cost Center</th>
                                <th class="text-right">Est. Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requisition->lines as $line)
                                <tr>
                                    <td>{{ $line->description }}</td>
                                    <td class="text-ink-soft">{{ $line->product->name ?? '—' }}</td>
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
                    <div class="w-48 space-y-2">
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total Estimated:</span>
                            <span class="text-gray-900">{{ format_money($requisition->lines->sum('estimated_total')) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
