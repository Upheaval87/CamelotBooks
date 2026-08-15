<x-app-layout>
    <x-list-header title="{{ $product->name }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <a href="{{ route('accounting.products.edit', $product) }}" class="tr-save">{{ __('Edit Product') }}</a>
                </div>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.inventory-items.index') }}" class="tr-item">{{ __('Back') }}</a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="card p-4">
                            <div class="text-xs font-medium text-ink-soft uppercase">{{ __('Total On Hand') }}</div>
                            <div class="text-2xl font-bold text-ink mt-1">{{ format_money($totalOnHand) }}</div>
                        </div>
                        <div class="card p-4">
                            <div class="text-xs font-medium text-ink-soft uppercase">{{ __('Total Value') }} (FIFO)</div>
                            <div class="text-2xl font-bold text-ink mt-1">@money($totalValue)</div>
                        </div>
                        <div class="card p-4">
                            <div class="text-xs font-medium text-ink-soft uppercase">{{ __('Reorder Point') }}</div>
                            <div class="text-2xl font-bold text-ink mt-1">{{ $product->reorder_point ? format_money($product->reorder_point) : '—' }}</div>
                        </div>
                        <div class="card p-4">
                            <div class="text-xs font-medium text-ink-soft uppercase">{{ __('Avg Unit Cost') }}</div>
                            <div class="text-2xl font-bold text-ink mt-1">{{ $totalOnHand > 0 ? format_money($totalValue / $totalOnHand, null, 4) : format_money(0) }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-4">{{ __('Product Details') }}</p>
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('SKU') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->sku ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Type') }}</dt>
                                    <dd class="text-sm text-ink font-medium capitalize">{{ $product->type }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Base UOM') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->getBaseUomName() }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Sales Price') }}</dt>
                                    <dd class="text-sm text-ink font-medium">@money($product->sales_price)</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Purchase Price') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->purchase_price ? format_money($product->purchase_price) : '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Income Account') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->incomeAccount->code ?? '' }} {{ $product->incomeAccount->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('COGS Account') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->expenseAccount->code ?? '' }} {{ $product->expenseAccount->name ?? '—' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-ink-soft">{{ __('Inventory Asset Account') }}</dt>
                                    <dd class="text-sm text-ink font-medium">{{ $product->inventoryAssetAccount->code ?? '' }} {{ $product->inventoryAssetAccount->name ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-4">{{ __('Stock by Location') }}</p>
                            @if($product->stock->isNotEmpty())
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-ink-soft uppercase">{{ __('Branch') }}</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-ink-soft uppercase">{{ __('Quantity') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->stock as $stock)
                                            <tr>
                                                <td class="px-3 py-2 text-sm text-ink">{{ $stock->branch->name ?? 'Main' }}</td>
                                                <td class="figure px-3 py-2 text-sm text-ink text-right font-medium">{{ format_money($stock->quantity_on_hand) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-sm text-ink-soft">{{ __('No stock records found.') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('FIFO Cost Layers') }}</p>
                        @if($product->costLayers->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Source') }}</th>
                                            <th class="text-right">{{ __('Qty Remaining') }}</th>
                                            <th class="text-right">{{ __('Unit Cost') }}</th>
                                            <th class="text-right">{{ __('Total Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->costLayers as $layer)
                                            <tr class="{{ $layer->quantity_remaining <= 0 ? 'text-gray-400' : '' }}">
                                                <td>{{ $layer->date->format('M d, Y') }}</td>
                                                <td class="text-ink-soft">{{ $layer->source_type ?? '—' }}</td>
                                                <td class="figure px-4 py-3 text-sm text-ink text-right font-medium">{{ format_money($layer->quantity_remaining) }}</td>
                                                <td class="numeric">{{ format_money($layer->unit_cost, null, 4) }}</td>
                                                <td class="numeric">@money($layer->quantity_remaining * $layer->unit_cost)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-ink-soft">{{ __('No cost layers found.') }}</p>
                        @endif
                    </div>

                    @if($product->uomConversions->isNotEmpty())
                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-5">{{ __('UOM Conversions') }}</p>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>{{ __('UOM') }}</th>
                                            <th class="text-right">{{ __('Conversion Factor') }}</th>
                                            <th class="text-right">{{ __('Purchase Price') }}</th>
                                            <th class="text-right">{{ __('Sales Price') }}</th>
                                            <th class="text-center">{{ __('Base') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product->uomConversions->where('is_active', true) as $uom)
                                            <tr>
                                                <td class="px-4 py-3 text-sm text-ink font-medium">{{ $uom->uom_name }}</td>
                                                <td class="numeric">{{ number_format($uom->conversion_factor, 4) }}</td>
                                                <td class="numeric">{{ $uom->purchase_price > 0 ? format_money($uom->purchase_price) : '—' }}</td>
                                                <td class="numeric">{{ $uom->sales_price > 0 ? format_money($uom->sales_price) : '—' }}</td>
                                                <td class="px-4 py-3 text-center text-sm text-ink">
                                                    @if($uom->is_base)
                                                        <span class="status-pill neutral">{{ __('Base') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.inventory-items.print', $product), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.inventory-items.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
