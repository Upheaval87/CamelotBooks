<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Tax Depreciation Schedule" />
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Asset Code</th>
                <th>Name</th>
                <th>Method</th>
                <th class="text-right">Cost ({{ $cs }})</th>
                <th class="text-right">Rate %</th>
                <th class="text-right">Accum Depr ({{ $cs }})</th>
                <th class="text-right">NBV ({{ $cs }})</th>
                <th>Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assets as $a)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-sans">{{ $a['asset_code'] }}</td>
                    <td>{{ $a['asset_name'] }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $a['depreciation_method'])) }}</td>
                    <td class="numeric">{{ format_number($a['acquisition_cost']) }}</td>
                    <td class="numeric">{{ number_format($a['depreciation_rate'], 1) }}%</td>
                    <td class="numeric">{{ format_number($a['accumulated_depreciation']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($a['net_book_value']) }}</td>
                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $a['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">{{ ucfirst($a['status']) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No assets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
