<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Asset Disposal Report</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset Code</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disposal Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acquisition ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Proceeds ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Gain/Loss ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Memo</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($disposals as $d)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono">{{ $d['asset_code'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $d['asset_name'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $d['disposal_date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst(str_replace('_', ' ', $d['disposal_method'])) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($d['acquisition_cost']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($d['proceeds_amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium {{ $d['gain_loss_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($d['gain_loss_amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $d['memo'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No disposals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
