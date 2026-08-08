<x-app-layout>
    <x-list-header title="{{ __('Count') }} {{ $count->count_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <x-feedback.alert variant="error" class="mb-4">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-feedback.alert>
            @endif

            

            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $count->count_number }}</h3>
                                @if($count->status === 'posted')
                                    <span class="status-pill positive">Posted</span>
                                @else
                                    <span class="status-pill neutral">In Progress</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">{{ $count->date->format('M d, Y') }} | {{ $count->branch->name ?? 'All Locations' }}</div>
                        </div>
                    </div>

                    @if($count->status === 'in_progress')
                        <form method="POST" action="{{ route('accounting.stock-counts.update', $count) }}">
                            @csrf
                            @method('PUT')

                            <div class="datasheet-wrap">
                                <div class="overflow-x-auto">
                                    <table class="datasheet">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-right">Expected</th>
                                                <th class="text-center">Physical Count</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($count->lines as $line)
                                                <tr class="hover:bg-gray-50">
                                                    <td>{{ $line->product->sku ?? '' }} {{ $line->product->name ?? '—' }}</td>
                                                    <td class="numeric">{{ format_money($line->expected_quantity) }}</td>
                                                    <td class="text-center">
                                                        <input type="number" name="counts[{{ $line->id }}]" step="0.0001" min="0"
                                                            value="{{ old('counts.' . $line->id, $line->counted_quantity ?? $line->expected_quantity) }}"
                                                            class="input mt-1 w-32 text-right mx-auto" />
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="flex justify-end gap-3 p-4 border-t border-gray-200">
                                    <x-button variant="ghost" href="{{ route('accounting.stock-counts.index') }}">{{ __('Cancel') }}</x-button>
                                    <button type="submit" name="post_count" value="1" onclick="return fbConfirmButton(event, 'Post this count? Variances will be posted to the general ledger.', { type: 'action' })"
                                        class="inline-flex items-center px-4 py-2 bg-gold-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gold-800">
                                        {{ __('Save & Post Count') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
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
                            @if($count->journalEntry)
                                <div class="p-4 border-t border-gray-200">
                                    <span class="text-sm text-gray-500">Journal Entry: </span>
                                    <a href="{{ route('accounting.journal-entries.show', $count->journalEntry) }}" class="text-sm text-gold-700 hover:text-gold-800 font-medium">{{ $count->journalEntry->entry_number }}</a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
