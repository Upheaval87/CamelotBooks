<x-app-layout>
@php
$cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
$statusTotals = [
    'total' => $summary['total'],
    'accepted' => $summary['accepted'],
    'pending' => $summary['draft'] + $summary['sent'],
    'rejected' => $summary['declined'],
];
@endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Quotation Status" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="flex gap-4 mb-6">
        <div class="bg-white shadow-sm sm:rounded-lg p-4 flex-1 text-center">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ format_number($statusTotals['total'] ?? 0) }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4 flex-1 text-center">
            <p class="text-sm text-gray-500">Accepted</p>
            <p class="text-2xl font-bold text-green-600">{{ format_number($statusTotals['accepted'] ?? 0) }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4 flex-1 text-center">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ format_number($statusTotals['pending'] ?? 0) }}</p>
        </div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4 flex-1 text-center">
            <p class="text-sm text-gray-500">Rejected</p>
            <p class="text-2xl font-bold text-red-600">{{ format_number($statusTotals['rejected'] ?? 0) }}</p>
        </div>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Quote #</th>
                <th>Date</th>
                <th>Customer</th>
                <th class="text-right">Total ({{ $cs }})</th>
                <th>Valid Until</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($quotations as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['quotation_number'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td class="numeric">{{ format_number($row['total']) }}</td>
                    <td>{{ $row['valid_until'] }}</td>
                    <td>
                        @if($row['status'] === 'accepted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Accepted</span>
                        @elseif($row['status'] === 'pending')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                        @elseif($row['status'] === 'rejected')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ ucfirst($row['status']) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No quotations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>