<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $product->name }}</h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.products.edit', $product) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Edit Product') }}
                </a>
                <a href="{{ route('accounting.inventory-items.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase">Total On Hand</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ format_money($totalOnHand) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase">Total Value (FIFO)</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">@money($totalValue)</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase">Reorder Point</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ $product->reorder_point ? format_money($product->reorder_point) : '—' }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase">Avg Unit Cost</div>
                    <div class="text-2xl font-bold text-gray-900 mt-1">{{ $totalOnHand > 0 ? format_money($totalValue / $totalOnHand, null, 4) : format_money(0) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Product Details --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Details</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Stock Keeping Unit (SKU)</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->sku ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Type</dt>
                            <dd class="text-sm text-gray-900 font-medium capitalize">{{ $product->type }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Base UOM</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->getBaseUomName() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Sales Price</dt>
                            <dd class="text-sm text-gray-900 font-medium">@money($product->sales_price)</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Purchase Price</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->purchase_price ? format_money($product->purchase_price) : '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Income Account</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->incomeAccount->code ?? '' }} {{ $product->incomeAccount->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">COGS Account</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->expenseAccount->code ?? '' }} {{ $product->expenseAccount->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Inventory Asset Account</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $product->inventoryAssetAccount->code ?? '' }} {{ $product->inventoryAssetAccount->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Stock by Branch --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Stock by Location</h3>
                    @if($product->stock->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($product->stock as $stock)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-gray-900">{{ $stock->branch->name ?? 'Main' }}</td>
                                        <td class="px-3 py-2 text-sm text-gray-900 text-right font-medium">{{ format_money($stock->quantity_on_hand) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-sm text-gray-500">No stock records found.</p>
                    @endif
                </div>
            </div>

            {{-- FIFO Cost Layers --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">FIFO Cost Layers</h3>
                @if($product->costLayers->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Remaining</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Value</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($product->costLayers as $layer)
                                    <tr class="{{ $layer->quantity_remaining <= 0 ? 'text-gray-400' : '' }}">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $layer->date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $layer->source_type ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ format_money($layer->quantity_remaining) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ format_money($layer->unit_cost, null, 4) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">@money($layer->quantity_remaining * $layer->unit_cost)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No cost layers found.</p>
                @endif
            </div>

            {{-- UOM Conversions --}}
            @if($product->uomConversions->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">UOM Conversions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">UOM</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Conversion Factor</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Purchase Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Sales Price</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Base</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($product->uomConversions->where('is_active', true) as $uom)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $uom->uom_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($uom->conversion_factor, 4) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $uom->purchase_price > 0 ? format_money($uom->purchase_price) : '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $uom->sales_price > 0 ? format_money($uom->sales_price) : '—' }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-900">
                                            @if($uom->is_base)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Base</span>
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
    </div>
</x-app-layout>
