<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Pension Remittance Report" />
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
                <th>Period</th>
                <th>Run #</th>
                <th>Pay Date</th>
                <th class="text-right">EE Contrib ({{ $cs }})</th>
                <th class="text-right">ER Contrib ({{ $cs }})</th>
                <th class="text-right">Total ({{ $cs }})</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($runs as $r)
                <tr class="hover:bg-gray-50">
                    <td>{{ $r['period_label'] }}</td>
                    <td class="px-4 py-2 text-sm font-sans">{{ $r['run_number'] }}</td>
                    <td>{{ $r['pay_date'] }}</td>
                    <td class="numeric">{{ format_number($r['total_ee']) }}</td>
                    <td class="numeric">{{ format_number($r['total_er']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($r['total_ee'] + $r['total_er']) }}</td>
                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $r['status'] === 'posted' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ ucfirst($r['status']) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No pension remittances found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 grid grid-cols-2 gap-4 max-w-lg">
        <div class="bg-white shadow-sm sm:rounded-lg p-4"><p class="text-sm text-gray-500">Total EE Contributions</p><p class="text-lg font-bold">{{ $cs }} {{ format_number($total_ee) }}</p></div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4"><p class="text-sm text-gray-500">Total ER Contributions</p><p class="text-lg font-bold">{{ $cs }} {{ format_number($total_er) }}</p></div>
    </div>
</div>
</x-app-layout>
