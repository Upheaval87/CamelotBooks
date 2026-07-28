<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Pending Approvals Aging</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested By</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Days Pending</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aging</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $i)
                @php
                    $days = $i['days_pending'];
                    $color = $days <= 3 ? 'bg-green-100 text-green-800' : ($days <= 7 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">{{ ucfirst($i['type']) }}</span></td>
                    <td class="px-4 py-2 text-sm font-mono">{{ $i['reference'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-600">{{ $i['description'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($i['amount']) }}</td>
                    <td class="px-4 py-2 text-sm">{{ $i['requested_at'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $i['requested_by'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ $days }}</td>
                    <td class="px-4 py-2 text-sm"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">{{ $days }}d</span></td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No pending approvals.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
