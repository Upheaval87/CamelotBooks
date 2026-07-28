<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('regional', 'currency_symbol', session('current_company_id'), 'KES'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Unbilled Receipts</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">GRN #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">PO #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['grn_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['vendor'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['po_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['product'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['qty']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['unit_cost']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['total_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No unbilled receipts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total Unbilled</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($total_unbilled) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Accrued Purchases Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($accrued_balance) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Variance</p>
                @if($variance != 0)
                    <p class="text-lg font-bold text-red-600">{{ $cs }} {{ format_number($variance) }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">{{ $cs }} {{ format_number($variance) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>