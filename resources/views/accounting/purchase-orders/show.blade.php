<x-app-layout>
    <x-list-header title="{{ __('Purchase Order') }} #{{ $order->po_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    @if($order->status === 'draft')
                        @can('purchase-orders.confirm')
                            <form method="POST" action="{{ route('accounting.purchase-orders.confirm', $order) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Confirm & Send') }}</button>
                            </form>
                        @endcan
                        <a href="{{ route('accounting.purchase-orders.edit', $order) }}" class="tr-save">{{ __('Edit') }}</a>
                    @endif
                    @if(in_array($order->status, ['sent', 'partially_received']))
                        <a href="{{ route('accounting.goods-received-notes.create', ['purchase_order_id' => $order->id]) }}" class="tr-save">{{ __('Create GRN') }}</a>
                    @endif
                </div>

                <div class="tr-spacer"></div>

                @if(in_array($order->status, ['draft', 'sent']))
                    @can('purchase-orders.cancel')
                        <form method="POST" action="{{ route('accounting.purchase-orders.cancel', $order) }}" class="inline">
                            @csrf
                            <button type="submit" class="tr-archive" onclick="return fbConfirmButton(event, '{{ __('Are you sure?') }}', { type: 'danger' })">{{ __('Cancel') }}</button>
                        </form>
                    @endcan
                @endif

                <a href="{{ route('accounting.purchase-orders.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

            

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('PO Number')" :value="$order->po_number" />
                    <x-detail-field :label="__('Status')" noBorder>
                        @switch($order->status)
                            @case('draft') <span class="status-pill neutral">{{ __('Draft') }}</span> @break
                            @case('sent') <span class="status-pill neutral">{{ __('Sent') }}</span> @break
                            @case('partially_received') <span class="status-pill neutral">{{ __('Partially Received') }}</span> @break
                            @case('fully_received') <span class="status-pill positive">{{ __('Fully Received') }}</span> @break
                            @case('cancelled') <span class="status-pill negative">{{ __('Cancelled') }}</span> @break
                        @endswitch
                    </x-detail-field>
                    <x-detail-field :label="__('Vendor')" :value="$order->vendor->name ?? '—'" />
                    <x-detail-field :label="__('Date')" :value="$order->date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Expected Delivery')" :value="$order->expected_delivery_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Requisition')" :value="$order->requisition->requisition_number ?? '—'" />
                    @if($order->memo)
                        <x-detail-field :label="__('Description')" :value="$order->memo" class="col-span-3" />
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
                                <th class="text-right">{{ __('Amount') }}</th>
                                <th class="text-right">{{ __('Qty Received') }}</th>
                                <th class="text-right">{{ __('Qty Billed') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td>{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity }}</td>
                                    <td class="numeric">{{ format_money($line->unit_price) }}</td>
                                    <td class="numeric font-semibold">{{ format_money($line->amount) }}</td>
                                    <td class="numeric">{{ $line->quantity_received }}</td>
                                    <td class="numeric">{{ $line->quantity_billed }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="balance-grid">
                        <div class="balance-total-row">
                            <span class="balance-label">{{ __('Total') }}:</span>
                            <span class="balance-value">{{ format_money($totalAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($order->grns->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Goods Received Notes') }}</p>
                    <table class="record-datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('GRN #') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->grns as $grn)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.goods-received-notes.show', $grn) }}" class="text-ink hover:text-gold">{{ $grn->grn_number }}</a>
                                    </td>
                                    <td>{{ $grn->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($grn->status === 'posted')
                                            <span class="status-pill positive">{{ __('Posted') }}</span>
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
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Entry') }}</p>
                    <a href="{{ route('accounting.journal-entries.show', $order->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $order->journalEntry->reference }} — {{ __('View Journal Entry') }}
                    </a>
                </div>
            @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.purchase-orders.index'), 'icon' => 'back', 'title' => __('Back to Purchase Orders')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
