<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('GRN') }} #{{ $grn->grn_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        @if($grn->status === 'draft')
            <form method="POST" action="{{ route('accounting.goods-received-notes.post', $grn) }}" class="inline">
                @csrf
                <x-button variant="primary" type="submit" onclick="return confirm('Post this GRN? This will create inventory cost layers and a journal entry.')">{{ __('Post') }}</x-button>
            </form>
        @endif
        <x-button variant="ghost" href="{{ route('accounting.goods-received-notes.index') }}">{{ __('Back') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('GRN Number') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $grn->grn_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($grn->status === 'posted')
                                <span class="status-pill positive">Posted</span>
                            @elseif($grn->status === 'draft')
                                <span class="status-pill neutral">Draft</span>
                            @else
                                <span class="status-pill negative">{{ ucfirst($grn->status) }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $grn->date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Vendor') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $grn->vendor->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Purchase Order') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($grn->purchaseOrder)
                                <a href="{{ route('accounting.purchase-orders.show', $grn->purchaseOrder) }}" class="text-ink hover:text-gold">{{ $grn->purchaseOrder->po_number }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @if($grn->memo)
                        <div class="col-span-2">
                            <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $grn->memo }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Received Items') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th class="text-right">Qty Ordered</th>
                                <th class="text-right">Qty Received</th>
                                <th class="text-right">Unit Cost ({{ $cs }})</th>
                                <th class="text-right">Total Cost ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($grn->lines as $line)
                                <tr>
                                    <td>{{ $line->product->name ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $line->description }}</td>
                                    <td class="numeric">{{ $line->quantity_ordered ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">{{ $line->quantity_received }}</td>
                                    <td class="numeric">{{ format_number($line->unit_cost) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">{{ format_number($line->total_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-48 space-y-2">
                        <div class="flex justify-between text-sm font-semibold border-t pt-2">
                            <span class="text-gray-800">Total Received:</span>
                            <span class="text-gray-900">{{ format_number($grn->lines->sum('total_cost')) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($grn->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Entry') }}</h3>
                    <a href="{{ route('accounting.journal-entries.show', $grn->journalEntry) }}" class="text-ink hover:text-gold">
                        {{ $grn->journalEntry->reference }} — View Journal Entry
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
