<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Stock Count Variance" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Stock Count ID</label><input type="text" name="stock_count_id" value="{{ request('stock_count_id') }}" placeholder="Stock Count ID" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Count #</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-right">Expected</th>
                <th class="text-right">Counted</th>
                <th class="text-right">Variance Qty</th>
                <th class="text-right">Unit Cost ({{ $cs }})</th>
                <th class="text-right">Variance Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($lines as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['count_number'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td>{{ $row['product_name'] }}</td>
                    <td>{{ $row['sku'] }}</td>
                    <td class="numeric">{{ format_number($row['expected']) }}</td>
                    <td class="numeric">{{ format_number($row['counted']) }}</td>
                    <td class="numeric">{{ format_number($row['variance_qty']) }}</td>
                    <td class="numeric">{{ format_number($row['unit_cost']) }}</td>
                    <td class="numeric">{{ format_number($row['variance_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="9" class="px-4 py-3 text-sm text-right">Total Variance Cost</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_variance_cost) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>