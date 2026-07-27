<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Stock Adjustment') }} {{ $adjustment->adjustment_number }}</h2>
            <a href="{{ route('accounting.stock-adjustments.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $adjustment->adjustment_number }}</h3>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>
                    </div>
                    <div class="text-right text-sm text-gray-500">
                        {{ $adjustment->date->format('M d, Y') }}
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Product</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $adjustment->product->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Stock Keeping Unit (SKU)</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $adjustment->product->sku ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Type</dt>
                        <dd class="mt-1">
                            @if($adjustment->type === 'increase')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Increase</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Decrease</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Quantity</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ number_format($adjustment->quantity, 4) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Unit Cost</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ format_money($adjustment->unit_cost, null, 4) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Total Cost</dt>
                        <dd class="text-sm text-gray-900 font-bold mt-1">@money($adjustment->total_cost)</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Reason</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1 capitalize">{{ str_replace('_', ' ', $adjustment->reason_code) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Branch</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $adjustment->branch->name ?? 'All Locations' }}</dd>
                    </div>
                    @if($adjustment->memo)
                        <div class="col-span-2">
                            <dt class="text-sm text-gray-500">Memo</dt>
                            <dd class="text-sm text-gray-900 font-medium mt-1">{{ $adjustment->memo }}</dd>
                        </div>
                    @endif
                    @if($adjustment->journalEntry)
                        <div class="col-span-2">
                            <dt class="text-sm text-gray-500">Journal Entry</dt>
                            <dd class="text-sm text-indigo-600 font-medium mt-1">
                                <a href="{{ route('accounting.journal-entries.show', $adjustment->journalEntry) }}" class="hover:text-indigo-900">
                                    {{ $adjustment->journalEntry->entry_number }}
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
