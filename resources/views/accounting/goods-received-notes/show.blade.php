<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('GRN') }} #{{ $grn->grn_number }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($grn->status === 'draft')
                    <form method="POST" action="{{ route('accounting.goods-received-notes.post', $grn) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Post this GRN? This will create inventory cost layers and a journal entry.')">
                            {{ __('Post') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('accounting.goods-received-notes.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>
                            @elseif($grn->status === 'draft')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">{{ ucfirst($grn->status) }}</span>
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
                                <a href="{{ route('accounting.purchase-orders.show', $grn->purchaseOrder) }}" class="text-indigo-600 hover:text-indigo-900">{{ $grn->purchaseOrder->po_number }}</a>
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
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Ordered</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Received</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Cost ({{ $cs }})</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Cost ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($grn->lines as $line)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $line->product->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $line->description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ $line->quantity_ordered ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">{{ $line->quantity_received }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ format_number($line->unit_cost) }}</td>
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
                    <a href="{{ route('accounting.journal-entries.show', $grn->journalEntry) }}" class="text-indigo-600 hover:text-indigo-900">
                        {{ $grn->journalEntry->reference }} — View Journal Entry
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
