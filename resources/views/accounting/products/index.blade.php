<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Create Product') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.products.create') }}">
                    {{ __('Create Product') }}
                </x-button>
            </div>
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.products.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="search" value="{{ __('Search') }}" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="Name or Stock Keeping Unit (SKU)..." />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="type" value="{{ __('Type') }}" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Types</option>
                            <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                            <option value="inventory" {{ request('type') === 'inventory' ? 'selected' : '' }}>Inventory</option>
                            <option value="non_inventory" {{ request('type') === 'non_inventory' ? 'selected' : '' }}>Non-Inventory</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <x-input-label for="status" value="{{ __('Status') }}" />
                        <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                        @if(request('search') || request('type') || request('status'))
                            <a href="{{ route('accounting.products.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Stock Keeping Unit (SKU)</th>
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
                                <tr class="{{ $product->is_active ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td>
                                        <a href="{{ route('accounting.products.show', $product) }}" class="text-ink hover:text-gold">
                                            {{ $product->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $product->sku ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ str_replace('_', ' ', ucfirst($product->type)) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($product->sales_price ?? 0) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_number($product->purchase_price ?? 0) }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $product->incomeAccount?->name ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($product->is_active)
                                            <span class="status-pill positive">Active</span>
                                        @else
                                            <span class="status-pill neutral">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.products.edit', $product) }}" class="text-ink hover:text-gold">Edit</a>
                                        <form method="POST" action="{{ route('accounting.products.toggle', $product) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-{{ $product->is_active ? 'red' : 'green' }}-600 hover:text-{{ $product->is_active ? 'red' : 'green' }}-900">
                                                {{ $product->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-ink-soft">
                                        No products found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($products->hasPages())
                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
