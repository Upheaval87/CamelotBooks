<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Stock Movement" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end flex-wrap">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Product</label>
            <x-scoped-search-field
                name="product_id"
                entity="product"
                search-url="{{ route('accounting.search.entity', ['entity' => 'product']) }}"
                :value="request('product_id')"
                :label="request('product_id') ? (\App\Models\Product::find(request('product_id'))?->name ?? '') : ''"
                placeholder="Search products..."
            /></div>
        <div><label class="block text-sm font-medium text-gray-700">Branch</label>
            <x-scoped-search-field
                name="branch_id"
                entity="branch"
                search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                :value="request('branch_id')"
                :label="request('branch_id') ? (\App\Models\Branch::find(request('branch_id'))?->name ?? '') : ''"
                placeholder="Search branches..."
            /></div>
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Type</th>
                <th>Reference</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Cost ({{ $cs }})</th>
                <th class="text-right">Running Qty</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($movements as $movement)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $movement['date'] }}</td>
                    <td>{{ $movement['product_name'] }}</td>
                    <td>{{ $movement['sku'] }}</td>
                    <td>{{ $movement['type'] }}</td>
                    <td>{{ $movement['reference'] }}</td>
                    <td class="numeric">{{ format_number($movement['quantity']) }}</td>
                    <td class="numeric">{{ format_number($movement['unit_cost']) }}</td>
                    <td class="numeric">{{ format_number($movement['running_qty']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No stock movements found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(!empty($reconciliation))
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <h2 class="text-lg font-semibold text-gray-900 mb-2 px-4 pt-4">Reconciliation</h2>
        <table class="datasheet">
            <thead><tr>
                <th>Product</th>
                <th>Branch</th>
                <th class="text-right">Running Qty</th>
                <th class="text-right">On Hand</th>
                <th class="text-right">Variance</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($reconciliation as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['product_name'] }}</td>
                    <td>{{ $row['branch_id'] ?? '—' }}</td>
                    <td class="numeric">{{ format_number($row['running_qty']) }}</td>
                    <td class="numeric">{{ format_number($row['on_hand']) }}</td>
                    <td class="numeric {{ (float) $row['variance'] !== 0.0 ? 'text-red-600' : '' }}">{{ format_number($row['variance']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
</x-app-layout>
