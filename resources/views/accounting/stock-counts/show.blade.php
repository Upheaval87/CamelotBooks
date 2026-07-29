<x-app-layout>
    <x-slot name="header">{{ __('Stock Count') }} {{ $count->count_number }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.stock-counts.index') }}">{{ __('Back') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $count->count_number }}</h3>
                        @if($count->status === 'posted')
                            <span class="status-pill positive">Posted</span>
                        @else
                            <span class="status-pill neutral">In Progress</span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500">{{ $count->date->format('M d, Y') }}</div>
                </div>
                <dl class="grid grid-cols-3 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">Branch</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $count->branch->name ?? 'All Locations' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Created By</dt>
                        <dd class="text-sm text-gray-900 font-medium mt-1">{{ $count->creator->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">Total Variance</dt>
                        <dd class="text-sm font-bold text-gray-900 mt-1">@money($count->variance_total)</dd>
                    </div>
                    @if($count->journalEntry)
                        <div>
                            <dt class="text-sm text-gray-500">Journal Entry</dt>
                            <dd class="text-sm text-indigo-600 font-medium mt-1">
                                <a href="{{ route('accounting.journal-entries.show', $count->journalEntry) }}" class="hover:text-indigo-900">{{ $count->journalEntry->entry_number }}</a>
                            </dd>
                        </div>
                    @endif
                    @if($count->notes)
                        <div class="col-span-3">
                            <dt class="text-sm text-gray-500">Notes</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $count->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Expected</th>
                                <th class="text-right">Counted</th>
                                <th class="text-right">Variance</th>
                                <th class="text-right">Unit Cost</th>
                                <th class="text-right">Variance $</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($count->lines as $line)
                                @if($line->counted_quantity !== null)
                                    <tr class="{{ $line->variance_quantity != 0 ? 'bg-yellow-50' : '' }}">
                                        <td>{{ $line->product->sku ?? '' }} {{ $line->product->name ?? '—' }}</td>
                                        <td class="numeric">{{ format_money($line->expected_quantity) }}</td>
                                        <td class="numeric">{{ format_money($line->counted_quantity) }}</td>
                                        <td class="px-4 py-3 text-sm text-right {{ $line->variance_quantity > 0 ? 'text-green-600 font-semibold' : ($line->variance_quantity < 0 ? 'text-red-600 font-semibold' : 'text-gray-500') }}">
                                            {{ $line->variance_quantity >= 0 ? '+' : '' }}{{ format_money($line->variance_quantity) }}
                                        </td>
                                        <td class="numeric">{{ format_money($line->unit_cost, null, 4) }}</td>
                                        <td class="numeric">@money($line->variance_cost)</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                            <tr>
                                <td colspan="5" class="text-right">Total Variance</td>
                                <td class="text-right">@money($count->variance_total)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
