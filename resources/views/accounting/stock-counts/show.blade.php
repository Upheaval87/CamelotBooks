<x-app-layout>
    <x-list-header title="{{ __('Stock Count') }} {{ $count->count_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.stock-counts.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <p class="text-base font-semibold text-ink">{{ $count->count_number }}</p>
                                @if($count->status === 'posted')
                                    <span class="status-pill positive">{{ __('Posted') }}</span>
                                @else
                                    <span class="status-pill neutral">{{ __('In Progress') }}</span>
                                @endif
                            </div>
                            <div class="text-sm text-ink-soft">{{ $count->date->format('M d, Y') }}</div>
                        </div>

                        <div class="detail-grid">
                            <x-detail-field :label="__('Branch')" :value="$count->branch->name ?? __('All Locations')" />
                            <x-detail-field :label="__('Created By')" :value="$count->creator->name ?? '—'" />
                            <x-detail-field :label="__('Total Variance')" value-class="font-bold">
                                @money($count->variance_total)
                            </x-detail-field>
                            @if($count->journalEntry)
                                <x-detail-field :label="__('Journal Entry')">
                                    <a href="{{ route('accounting.journal-entries.show', $count->journalEntry) }}" class="text-ink hover:text-gold">{{ $count->journalEntry->entry_number }}</a>
                                </x-detail-field>
                            @endif
                            @if($count->notes)
                                <x-detail-field :label="__('Notes')" :value="$count->notes" class="col-span-3" />
                            @endif
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Count Lines') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th class="text-right">{{ __('Expected') }}</th>
                                        <th class="text-right">{{ __('Counted') }}</th>
                                        <th class="text-right">{{ __('Variance') }}</th>
                                        <th class="text-right">{{ __('Unit Cost') }}</th>
                                        <th class="text-right">{{ __('Variance $') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($count->lines as $line)
                                        @if($line->counted_quantity !== null)
                                            <tr class="{{ $line->variance_quantity != 0 ? 'bg-yellow-50' : '' }}">
                                                <td>{{ $line->product->sku ?? '' }} {{ $line->product->name ?? '—' }}</td>
                                                <td class="numeric">{{ format_money($line->expected_quantity) }}</td>
                                                <td class="numeric">{{ format_money($line->counted_quantity) }}</td>
                                                <td class="figure px-4 py-3 text-sm text-right {{ $line->variance_quantity > 0 ? 'text-green-600 font-semibold' : ($line->variance_quantity < 0 ? 'text-red-600 font-semibold' : 'text-gray-500') }}">
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
                                        <td colspan="5" class="text-right font-semibold">{{ __('Total Variance') }}</td>
                                        <td class="text-right font-bold">@money($count->variance_total)</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.stock-counts.print', $count), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.stock-counts.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
