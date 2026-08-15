<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Unbilled Deliveries" />
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <x-primary-button type="submit">Filter</x-primary-button>
        </form>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Quotation #</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Product</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price ({{ $cs }})</th>
                <th class="text-right">Total ({{ $cs }})</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($lines as $l)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-sans">{{ $l['quotation_number'] }}</td>
                    <td>{{ $l['date'] }}</td>
                    <td>{{ $l['customer'] }}</td>
                    <td>{{ $l['product'] }}</td>
                    <td class="numeric">{{ format_number($l['quantity']) }}</td>
                    <td class="numeric">{{ format_number($l['unit_price']) }}</td>
                    <td class="figure px-4 py-2 text-sm text-right font-medium">{{ format_number($l['line_total']) }}</td>
                    <td>{{ $l['status'] }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No unbilled deliveries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Total Undelivered: <span class="text-lg font-bold">{{ $cs }} {{ format_number($total_undelivered) }}</span></p>
    </div>
</div>
</x-app-layout>
