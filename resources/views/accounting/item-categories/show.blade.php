<x-app-layout>
    <x-slot name="header">{{ $category->name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.item-categories.edit', $category) }}" class="tr-save">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.item-categories.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </a>
            </x-record-toolbar>

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Code') }}" strong>{{ $category->code }}</x-detail-field>
                    <x-detail-field label="{{ __('Status') }}">
                        @if($category->is_active)
                            <span class="status-pill positive">{{ __('Active') }}</span>
                        @else
                            <span class="status-pill neutral">{{ __('Inactive') }}</span>
                        @endif
                    </x-detail-field>
                    @if($category->description)
                        <x-detail-field label="{{ __('Description') }}">{{ $category->description }}</x-detail-field>
                    @endif
                    <x-detail-field label="{{ __('Income Account') }}">{{ $category->defaultIncomeAccount->code ?? '' }} {{ $category->defaultIncomeAccount->name ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('COGS Account') }}">{{ $category->defaultCogsAccount->code ?? '' }} {{ $category->defaultCogsAccount->name ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Inventory Asset Account') }}">{{ $category->defaultInventoryAssetAccount->code ?? '' }} {{ $category->defaultInventoryAssetAccount->name ?? '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Default Reorder Point') }}">{{ $category->default_reorder_point ? format_money($category->default_reorder_point) : '—' }}</x-detail-field>
                    <x-detail-field label="{{ __('Default Base UOM') }}">{{ $category->default_base_uom ?? '—' }}</x-detail-field>
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Products in this Category') }} ({{ $category->products->count() }})</p>
                @if($category->products->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Stock Keeping Unit (SKU)</th>
                                    <th>Name</th>
                                    <th class="text-center">Tracked</th>
                                    <th class="text-right">Sales Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->products as $product)
                                    <tr>
                                        <td class="text-ink-soft">{{ $product->sku ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                            <a href="{{ route('accounting.products.show', $product) }}" class="text-ink hover:text-gold">{{ $product->name }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-500">
                                            @if($product->tracked_as_inventory)
                                                <span class="text-green-600">&#10003;</span>
                                            @else
                                                <span class="text-gray-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="numeric">@money($product->sales_price)</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-ink-soft">{{ __('No products in this category.') }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
