<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('regional', 'currency_symbol', session('current_company_id'), 'KES'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Customer Credit Balance</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">CN #</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Applied ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unapplied ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['cn_number'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['customer'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['applied']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['unapplied']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No credit balances found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900">Summary</h2>
        <p class="mt-2 text-sm text-gray-700">Total Unapplied: <span class="font-bold">{{ $cs }} {{ format_number($total_unapplied) }}</span></p>
    </div>
</div>
</x-app-layout>