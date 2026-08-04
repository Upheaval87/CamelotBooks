<x-app-layout>
    <x-list-header title="{{ __('Stock Transfer') }} {{ $transfer->transfer_number }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.stock-transfers.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-base font-semibold text-ink">{{ $transfer->transfer_number }}</p>
                                <span class="status-pill positive">{{ __('Completed') }}</span>
                            </div>
                            <div class="text-right text-sm text-ink-soft">
                                {{ $transfer->date->format('M d, Y') }}
                            </div>
                        </div>

                        <div class="detail-grid">
                            <x-detail-field :label="__('Product')" :value="$transfer->product->name ?? '—'" />
                            <x-detail-field :label="__('SKU')" :value="$transfer->product->sku ?? '—'" />
                            <x-detail-field :label="__('From Branch')" :value="$transfer->fromBranch->name ?? '—'" />
                            <x-detail-field :label="__('To Branch')" :value="$transfer->toBranch->name ?? '—'" />
                            <x-detail-field :label="__('Quantity Transferred')" :value="number_format($transfer->quantity, 4)" />
                            <x-detail-field :label="__('Created By')" :value="$transfer->creator->name ?? '—'" />
                            @if($transfer->memo)
                                <x-detail-field :label="__('Description')" :value="$transfer->memo" class="col-span-3" />
                            @endif
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.stock-transfers.print', $transfer), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.stock-transfers.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
