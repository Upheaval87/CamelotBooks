<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Consolidated Income Statement" />
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>

    @forelse($companies as $c)
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gold-soft border-b border-gold-line"><h2 class="text-lg font-semibold text-gold-800">{{ $c['company_name'] }}</h2></div>

        <div class="px-4 py-3 bg-green-50 border-b border-green-200"><h3 class="text-sm font-semibold text-green-800">Income</h3></div>
        <table class="datasheet">
            <thead><tr><th>Account</th><th class="text-right">Balance ({{ $cs }})</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($c['income'] as $i)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $i['name'] }} <span class="text-gray-400">({{ $i['code'] }})</span></td><td class="numeric">{{ format_number($i['balance']) }}</td></tr>
                @empty
                <tr><td colspan="2" class="text-center text-ink-soft">No income accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td>Total Income</td><td class="numeric">{{ format_number(collect($c['income'])->sum('balance')) }}</td></tr>
            </tbody>
        </table>

        <div class="px-4 py-3 bg-orange-50 border-b border-orange-200"><h3 class="text-sm font-semibold text-orange-800">Expenses</h3></div>
        <table class="datasheet">
            <thead><tr><th>Account</th><th class="text-right">Balance ({{ $cs }})</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($c['expense'] as $e)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $e['name'] }} <span class="text-gray-400">({{ $e['code'] }})</span></td><td class="numeric">{{ format_number($e['balance']) }}</td></tr>
                @empty
                <tr><td colspan="2" class="text-center text-ink-soft">No expense accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td>Total Expenses</td><td class="numeric">{{ format_number(collect($c['expense'])->sum('balance')) }}</td></tr>
            </tbody>
        </table>

        <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
            <p class="text-sm font-medium text-gray-700">Net Income: <span class="font-bold {{ $c['net_income'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ $cs }} {{ format_number($c['net_income']) }}</span></p>
        </div>
    </div>
    @empty
    <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-sm text-gray-500">No companies selected.</div>
    @endforelse

    @if(count($companies) > 1)
    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Consolidated Net Income: <span class="font-bold {{ $total_net_income >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ $cs }} {{ format_number($total_net_income) }}</span></p>
    </div>
    @endif
</div>
</x-app-layout>
