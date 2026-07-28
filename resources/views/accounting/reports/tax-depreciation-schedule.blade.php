<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Tax Depreciation Schedule</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asset Code</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cost ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Rate %</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Accum Depr ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">NBV ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assets as $a)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono">{{ $a['asset_code'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $a['asset_name'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst(str_replace('_', ' ', $a['depreciation_method'])) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($a['acquisition_cost']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ number_format($a['depreciation_rate'], 1) }}%</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($a['accumulated_depreciation']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($a['net_book_value']) }}</td>
                    <td class="px-4 py-2 text-sm"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $a['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($a['status']) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No assets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
