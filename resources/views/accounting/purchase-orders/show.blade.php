<x-app-layout>
    <x-slot name="header">{{ __('Purchase Order') }} #{{ $order->po_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        @if($order->status === 'draft')
            <form method="POST" action="{{ route('accounting.purchase-orders.confirm', $order) }}" class="inline">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Confirm & Send') }}</x-button>
            </form>
            <x-button variant="primary" href="{{ route('accounting.purchase-orders.edit', $order) }}">{{ __('Edit') }}</x-button>
        @endif
        @if(in_array($order->status, ['draft', 'sent']))
            <form method="POST" action="{{ route('accounting.purchase-orders.cancel', $order) }}" class="inline">
                @csrf
                <x-button variant="ghost" type="submit" onclick="return confirm('Are you sure?')">{{ __('Cancel') }}</x-button>
            </form>
        @endif
        @if(in_array($order->status, ['sent', 'partially_received']))
            <x-button variant="primary" href="{{ route('accounting.goods-received-notes.create', ['purchase_order_id' => $order->id]) }}">{{ __('Create GRN') }}</x-button>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.purchase-orders.index') }}">{{ __('Back') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('PO Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->po_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @switch($order->status)
                                @case('draft')
                                    <span class="status-pill neutral">Draft</span>
                                    @break
                                @case('sent')
                                    <span class="status-pill neutral">Sent</span>
                                    @break
                                @case('partially_received')
                                    <span class="status-pill neutral">Partially Received</span>
                                    @break
                                @case('fully_received')
                                    <span class="status-pill positive">Fully Received</span>
                                    @break
                                @case('cancelled')
                                    <span class="status-pill negative">Cancelled</span>
                                    @break
                            @endswitch
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Vendor') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->vendor->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expected Delivery') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Requisition') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->requisition->requisition_number ?? '—' }}</dd>
                    </div>
                    @if($order->memo)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $order->memo }}</dd>
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
                                <th class="text-right">Amount</th>
                                <th class="text-right">Qty Received</th>
                                <th class="text-right">Qty Billed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">{{ format_money($line->amount) }}</td>
                                    <td class="numeric">{{ $line->quantity_received }}</td>
                                    <td class="numeric">{{ $line->quantity_billed }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-48 space-y-2">
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total:</span>
                            <span class="text-gray-900">{{ format_money($totalAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->grns->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Goods Received Notes') }}</h3>
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>GRN #</th>
                                <th>Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->grns as $grn)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.goods-received-notes.show', $grn) }}" class="text-ink hover:text-gold">{{ $grn->grn_number }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $grn->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($grn->status === 'posted')
                                            <span class="status-pill positive">Posted</span>
                                        @else
                                            <span class="status-pill neutral">{{ ucfirst($grn->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($order->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Entry') }}</h3>
                    <a href="{{ route('accounting.journal-entries.show', $order->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $order->journalEntry->reference }} — View Journal Entry
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
