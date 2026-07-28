<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Consolidated Balance Sheet</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">As Of Date</label><input type="date" name="as_of_date" value="{{ $asOfDate }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>

    @forelse($companies as $c)
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-indigo-50 border-b border-indigo-200"><h2 class="text-lg font-semibold text-indigo-800">{{ $c['company_name'] }}</h2></div>

        @php
            $cAssets = collect($c['assets']);
            $cLiabilities = collect($c['liabilities']);
            $cEquity = collect($c['equity']);
            $cAssetsTotal = $cAssets->sum('balance');
            $cLiabTotal = $cLiabilities->sum('balance');
            $cEqTotal = $cEquity->sum('balance');
        @endphp

        <div class="px-4 py-3 bg-blue-50 border-b border-blue-200"><h3 class="text-sm font-semibold text-blue-800">Assets</h3></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance ({{ $cs }})</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($cAssets as $a)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $a['name'] }} <span class="text-gray-400">({{ $a['code'] }})</span></td><td class="px-4 py-2 text-sm text-right">{{ format_number($a['balance']) }}</td></tr>
                @empty
                <tr><td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No asset accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Assets</td><td class="px-4 py-2 text-sm text-right">{{ format_number($cAssetsTotal) }}</td></tr>
            </tbody>
        </table>

        <div class="px-4 py-3 bg-red-50 border-b border-red-200"><h3 class="text-sm font-semibold text-red-800">Liabilities</h3></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance ({{ $cs }})</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($cLiabilities as $l)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $l['name'] }} <span class="text-gray-400">({{ $l['code'] }})</span></td><td class="px-4 py-2 text-sm text-right">{{ format_number($l['balance']) }}</td></tr>
                @empty
                <tr><td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No liability accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Liabilities</td><td class="px-4 py-2 text-sm text-right">{{ format_number($cLiabTotal) }}</td></tr>
            </tbody>
        </table>

        <div class="px-4 py-3 bg-purple-50 border-b border-purple-200"><h3 class="text-sm font-semibold text-purple-800">Equity</h3></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance ({{ $cs }})</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($cEquity as $e)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $e['name'] }} <span class="text-gray-400">({{ $e['code'] }})</span></td><td class="px-4 py-2 text-sm text-right">{{ format_number($e['balance']) }}</td></tr>
                @empty
                <tr><td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">No equity accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Equity</td><td class="px-4 py-2 text-sm text-right">{{ format_number($cEqTotal) }}</td></tr>
            </tbody>
        </table>

        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
            <p class="text-sm font-medium text-gray-700">Liabilities + Equity: <span class="font-bold">{{ $cs }} {{ format_number($cLiabTotal + $cEqTotal) }}</span> — Assets: <span class="font-bold">{{ $cs }} {{ format_number($cAssetsTotal) }}</span></p>
        </div>
    </div>
    @empty
    <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-sm text-gray-500">No companies selected.</div>
    @endforelse

    @if(count($companies) > 1)
    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Consolidated Total Assets: <span class="font-bold">{{ $cs }} {{ format_number($totals['assets']) }}</span> — Total Liabilities + Equity: <span class="font-bold">{{ $cs }} {{ format_number($totals['liabilities'] + $totals['equity']) }}</span></p>
    </div>
    @endif
</div>
</x-app-layout>
