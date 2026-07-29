<x-app-layout>
    <x-slot name="header">{{ __('Assembly Build History') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.assemblies.index') }}">{{ __('Builds') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" action="{{ route('accounting.assemblies.history') }}" class="flex items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Assembly Product</label>
                        <select name="assembly_product_id" class="mt-1 block w-56 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('assembly_product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->sku ? $product->sku . ' - ' : '' }}{{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Type</label>
                        <select name="type" class="mt-1 block w-36 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All Types</option>
                            <option value="build" {{ request('type') === 'build' ? 'selected' : '' }}>Build</option>
                            <option value="unbuild" {{ request('type') === 'unbuild' ? 'selected' : '' }}>Unbuild</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                        Filter
                    </button>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Build #</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th class="text-center">Type</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Unit Cost</th>
                                <th class="text-right">Total Cost</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($builds as $build)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.assemblies.show', $build) }}" class="text-ink hover:text-gold">{{ $build->build_number }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $build->date->format('M d, Y') }}</td>
                                    <td>{{ $build->assemblyProduct->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($build->type === 'build')
                                            <span class="status-pill neutral">Build</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Unbuild</span>
                                        @endif
                                    </td>
                                    <td class="numeric">{{ format_money($build->quantity) }}</td>
                                    <td class="numeric">{{ format_money($build->unit_cost, null, 4) }}</td>
                                    <td class="numeric">@money($build->total_component_cost)</td>
                                    <td class="text-ink-soft">{{ $build->creator->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-ink-soft">No assembly builds found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-200">{{ $builds->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
