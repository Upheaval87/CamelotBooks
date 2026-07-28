<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Consolidated Balance Sheet</h1>
    @if($companies->count() > 1)
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-700 mb-2">Included Companies</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($companies as $c)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ $c->name }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Assets --}}
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-blue-50 border-b border-blue-200"><h2 class="text-lg font-semibold text-blue-800">Assets</h2></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Previous ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($assets as $a)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $a['name'] }}</td><td class="px-4 py-2 text-sm text-right">{{ format_number($a['current']) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($a['previous']) }}</td><td class="px-4 py-2 text-sm text-right font-medium {{ $a['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($a['change']) }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No asset accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Assets</td><td class="px-4 py-2 text-sm text-right">{{ format_number($total_assets_current) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($total_assets_previous) }}</td><td class="px-4 py-2 text-sm text-right {{ $total_assets_change >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($total_assets_change) }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Liabilities --}}
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-red-50 border-b border-red-200"><h2 class="text-lg font-semibold text-red-800">Liabilities</h2></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Previous ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($liabilities as $l)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $l['name'] }}</td><td class="px-4 py-2 text-sm text-right">{{ format_number($l['current']) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($l['previous']) }}</td><td class="px-4 py-2 text-sm text-right font-medium {{ $l['change'] >= 0 ? 'text-red-600' : 'text-green-600' }}">{{ format_number($l['change']) }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No liability accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Liabilities</td><td class="px-4 py-2 text-sm text-right">{{ format_number($total_liabilities_current) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($total_liabilities_previous) }}</td><td class="px-4 py-2 text-sm text-right {{ $total_liabilities_change >= 0 ? 'text-red-600' : 'text-green-600' }}">{{ format_number($total_liabilities_change) }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Equity --}}
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-purple-50 border-b border-purple-200"><h2 class="text-lg font-semibold text-purple-800">Equity</h2></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Previous ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($equity as $e)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $e['name'] }}</td><td class="px-4 py-2 text-sm text-right">{{ format_number($e['current']) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($e['previous']) }}</td><td class="px-4 py-2 text-sm text-right font-medium {{ $e['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($e['change']) }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No equity accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Equity</td><td class="px-4 py-2 text-sm text-right">{{ format_number($total_equity_current) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($total_equity_previous) }}</td><td class="px-4 py-2 text-sm text-right {{ $total_equity_change >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($total_equity_change) }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Balance check --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Liabilities + Equity: <span class="font-bold">{{ $cs }} {{ format_number($total_liabilities_current + $total_equity_current) }}</span> — Assets: <span class="font-bold">{{ $cs }} {{ format_number($total_assets_current) }}</span> — Difference: <span class="font-bold {{ abs($total_assets_current - $total_liabilities_current - $total_equity_current) > 0.01 ? 'text-red-600' : 'text-green-600' }}">{{ $cs }} {{ format_number(abs($total_assets_current - $total_liabilities_current - $total_equity_current)) }}</span></p>
    </div>
</div>
</x-app-layout>
