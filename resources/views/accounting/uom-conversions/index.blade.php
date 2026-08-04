<x-app-layout>
    <x-list-header title="{{ __('Unit of Measure Conversions') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Stock Keeping Unit (SKU)</th>
                                <th>Base UOM</th>
                                <th class="text-center">UOM Conversions</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="text-ink-soft">{{ $product->sku }}</td>
                                    <td class="text-ink-soft">{{ $product->unit_of_measure ?? 'Each' }}</td>
                                    <td class="text-center">
                                        @if($product->uomConversions->count() > 1)
                                            <span class="status-pill neutral">{{ $product->uomConversions->count() }} UOMs</span>
                                        @elseif($product->uomConversions->count() === 1)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-600">Base only</span>
                                        @else
                                            <span class="text-gray-400 text-xs">None</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <a href="{{ route('accounting.uom-conversions.edit', $product) }}" class="text-ink hover:text-gold">Manage UOMs</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">No inventory products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
