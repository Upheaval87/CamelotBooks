<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('regional', 'currency_symbol', session('current_company_id'), 'KES'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Quotation Status</h1>
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
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quote #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Until</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['quote_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['customer'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['total']) }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['valid_until'] }}</td>
                    <td class="px-4 py-2 text-sm">
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