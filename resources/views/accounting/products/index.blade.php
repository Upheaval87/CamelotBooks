@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('Products') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <x-list-header title="Products" createRoute="{{ route('accounting.products.create') }}" createLabel="Create Product" />

            <div class="list-layout">
                <div class="list-layout-content">
                    <x-list-filter-bar searchRoute="{{ route('accounting.products.index') }}" searchPlaceholder="Name or SKU..." entity="product">
                        <select name="type" class="list-filter-select">
                            <option value="">All Types</option>
                            <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                            <option value="inventory" {{ request('type') === 'inventory' ? 'selected' : '' }}>Inventory</option>
                            <option value="non_inventory" {{ request('type') === 'non_inventory' ? 'selected' : '' }}>Non-Inventory</option>
                        </select>
                        <select name="status" class="list-filter-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </x-list-filter-bar>

                    @if(session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">{{ session('error') }}</div>
                    @endif

                    <div class="list-table-wrap">
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>SKU</th>
                                    <th>Type</th>
                                    <th class="text-right">Sales Price ({{ $cs }})</th>
                                    <th class="text-right">Purchase Price ({{ $cs }})</th>
                                    <th>Income Account</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                <tr>
                                    <td><a href="{{ route('accounting.products.show', $product) }}">{{ $product->name }}</a></td>
                                    <td><span class="text-ink-soft">{{ $product->sku ?? '—' }}</span></td>
                                    <td><span class="text-ink-soft">{{ str_replace('_', ' ', ucfirst($product->type)) }}</span></td>
                                    <td class="list-numeric">{{ format_number($product->sales_price ?? 0) }}</td>
                                    <td class="list-numeric">{{ format_number($product->purchase_price ?? 0) }}</td>
                                    <td><span class="text-ink-soft">{{ $product->incomeAccount?->name ?? '—' }}</span></td>
                                    <td class="text-center">@if($product->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</td>
                                    <td>
                                        <div class="action-group">
                                            <a href="{{ route('accounting.products.show', $product) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg><span class="icon-btn-tooltip">View</span></a>
                                            <a href="{{ route('accounting.products.edit', $product) }}" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg><span class="icon-btn-tooltip">Edit</span></a>
                                            <form method="POST" action="{{ route('accounting.products.toggle', $product) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="icon-btn"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M{{ $product->is_active ? '19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' : '18 4l-4 4m0 0l-4-4m4 4V2m-4 6l4 4m-4-4H2' }}"/></svg><span class="icon-btn-tooltip">{{ $product->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="8" class="text-center text-ink-soft py-8">No products found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($products->hasPages())
                        <div class="px-6 py-3 border-t border-gray-200">{{ $products->links() }}</div>
                        @endif
                    </div>

                    <div class="list-mobile-cards">
                        @forelse($products as $product)
                        <div class="list-mobile-card">
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Name</span><span class="list-mobile-card-value"><a href="{{ route('accounting.products.show', $product) }}">{{ $product->name }}</a></span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">SKU</span><span class="list-mobile-card-value">{{ $product->sku ?? '—' }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Type</span><span class="list-mobile-card-value">{{ str_replace('_', ' ', ucfirst($product->type)) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Sales Price</span><span class="list-mobile-card-value">{{ format_number($product->sales_price ?? 0) }}</span></div>
                            <div class="list-mobile-card-row"><span class="list-mobile-card-label">Status</span><span class="list-mobile-card-value">@if($product->is_active)<span class="status-pill positive">Active</span>@else<span class="status-pill neutral">Inactive</span>@endif</span></div>
                        </div>
                        @empty
                        <div class="text-center text-ink-soft py-8">No products found.</div>
                        @endforelse
                        @if($products->hasPages())
                        <div class="px-2 py-3">{{ $products->links() }}</div>
                        @endif
                    </div>
                </div>

                <div class="list-layout-sidebar">
                    <x-list-quick-links title="Products" :groups="[
                        [
                            ['route' => route('accounting.products.index'), 'title' => 'All Products', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                            ['route' => route('accounting.products.index', ['type' => 'service']), 'title' => 'Services', 'icon' => 'M5 13l4 4L19 7'],
                            ['route' => route('accounting.products.index', ['type' => 'inventory']), 'title' => 'Inventory', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                            ['route' => route('accounting.products.create'), 'title' => 'Create Product', 'icon' => 'M12 4v16m8-8H4', 'subtitle' => 'Add new item'],
                        ],
                        [
                            ['route' => '#', 'title' => 'Inventory Valuation', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                            ['route' => '#', 'title' => 'Stock Report', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ],
                    ]" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
