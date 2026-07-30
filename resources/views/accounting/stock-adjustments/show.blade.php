<x-app-layout>
    <x-slot name="header">{{ __('Stock Adjustment') }} {{ $adjustment->adjustment_number }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.stock-adjustments.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-base font-semibold text-ink">{{ $adjustment->adjustment_number }}</p>
                                <span class="status-pill positive">{{ __('Posted') }}</span>
                            </div>
                            <div class="text-right text-sm text-ink-soft">
                                {{ $adjustment->date->format('M d, Y') }}
                            </div>
                        </div>

                        <div class="detail-grid">
                            <x-detail-field :label="__('Product')" :value="$adjustment->product->name ?? '—'" />
                            <x-detail-field :label="__('SKU')" :value="$adjustment->product->sku ?? '—'" />
                            <x-detail-field :label="__('Type')">
                                @if($adjustment->type === 'increase')
                                    <span class="status-pill positive">{{ __('Increase') }}</span>
                                @else
                                    <span class="status-pill negative">{{ __('Decrease') }}</span>
                                @endif
                            </x-detail-field>
                            <x-detail-field :label="__('Quantity')" :value="number_format($adjustment->quantity, 4)" />
                            <x-detail-field :label="__('Unit Cost')" :value="format_money($adjustment->unit_cost, null, 4)" />
                            <x-detail-field :label="__('Total Cost')" value-class="font-bold">
                                @money($adjustment->total_cost)
                            </x-detail-field>
                            <x-detail-field :label="__('Reason')" :value="str_replace('_', ' ', $adjustment->reason_code)" />
                            <x-detail-field :label="__('Branch')" :value="$adjustment->branch->name ?? __('All Locations')" />
                            @if($adjustment->memo)
                                <x-detail-field :label="__('Description')" :value="$adjustment->memo" class="col-span-3" />
                            @endif
                            @if($adjustment->journalEntry)
                                <x-detail-field :label="__('Journal Entry')">
                                    <a href="{{ route('accounting.journal-entries.show', $adjustment->journalEntry) }}" class="text-ink hover:text-gold">
                                        {{ $adjustment->journalEntry->entry_number }}
                                    </a>
                                </x-detail-field>
                            @endif
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.stock-adjustments.print', $adjustment), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.stock-adjustments.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
