<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Asset Disposal Report</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Asset Code</th>
                <th>Asset Name</th>
                <th>Disposal Date</th>
                <th>Method</th>
                <th class="text-right">Acquisition ({{ $cs }})</th>
                <th class="text-right">Proceeds ({{ $cs }})</th>
                <th class="text-right">Gain/Loss ({{ $cs }})</th>
                <th>Memo</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($disposals as $d)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono">{{ $d['asset_code'] }}</td>
                    <td>{{ $d['asset_name'] }}</td>
                    <td>{{ $d['disposal_date'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $d['disposal_method'])) }}</td>
                    <td class="numeric">{{ format_number($d['acquisition_cost']) }}</td>
                    <td class="numeric">{{ format_number($d['proceeds_amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium {{ $d['gain_loss_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($d['gain_loss_amount']) }}</td>
                    <td class="text-ink-soft">{{ $d['memo'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No disposals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
