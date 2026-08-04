<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Vendor Credit Balance" />
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>CN #</th>
                <th>Date</th>
                <th>Vendor</th>
                <th class="text-right">Amount ({{ $cs }})</th>
                <th class="text-right">Applied ({{ $cs }})</th>
                <th class="text-right">Refunded ({{ $cs }})</th>
                <th class="text-right">Unapplied ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['cn_number'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['vendor'] }}</td>
                    <td class="numeric">{{ format_number($row['amount']) }}</td>
                    <td class="numeric">{{ format_number($row['applied']) }}</td>
                    <td class="numeric">{{ format_number($row['refunded']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['unapplied']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No credit balances found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900">Summary</h2>
        <p class="mt-2 text-sm text-gray-700">Total Unapplied: <span class="font-bold">{{ $cs }} {{ format_number($total_unapplied) }}</span></p>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Vendor Credit Balance" />
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>CN #</th>
                <th>Date</th>
                <th>Vendor</th>
                <th class="text-right">Amount ({{ $cs }})</th>
                <th class="text-right">Applied ({{ $cs }})</th>
                <th class="text-right">Refunded ({{ $cs }})</th>
                <th class="text-right">Unapplied ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['cn_number'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['vendor'] }}</td>
                    <td class="numeric">{{ format_number($row['amount']) }}</td>
                    <td class="numeric">{{ format_number($row['applied']) }}</td>
                    <td class="numeric">{{ format_number($row['refunded']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['unapplied']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No credit balances found.</td></tr>
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