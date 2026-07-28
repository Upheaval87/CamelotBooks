@extends('layouts.app')
@section('content')
@php $cs = \App\Models\SystemSetting::getValue('regional', 'currency_symbol', session('current_company_id'), 'KES'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Stock Count Variance</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">Stock Count ID</label><input type="text" name="stock_count_id" value="{{ request('stock_count_id') }}" placeholder="Stock Count ID" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Branch</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expected</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Counted</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance Qty</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Variance Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['count_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['branch'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['product'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['sku'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['expected']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['counted']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['variance_qty']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['unit_cost']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['variance_cost']) }}</td>
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
@endsection
