<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Consolidated Income Statement</h1>

    {{-- Income --}}
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-green-50 border-b border-green-200"><h2 class="text-lg font-semibold text-green-800">Income</h2></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Previous ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($income as $i)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $i['name'] }}</td><td class="px-4 py-2 text-sm text-right">{{ format_number($i['current']) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($i['previous']) }}</td><td class="px-4 py-2 text-sm text-right font-medium {{ $i['change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($i['change']) }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No income accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Income</td><td class="px-4 py-2 text-sm text-right">{{ format_number($total_income_current) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($total_income_previous) }}</td><td class="px-4 py-2 text-sm text-right {{ $total_income_change >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ format_number($total_income_change) }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Expenses --}}
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-orange-50 border-b border-orange-200"><h2 class="text-lg font-semibold text-orange-800">Expenses</h2></div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Previous ({{ $cs }})</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Change</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($expenses as $e)
                <tr class="hover:bg-gray-50"><td class="px-4 py-2 text-sm pl-8">{{ $e['name'] }}</td><td class="px-4 py-2 text-sm text-right">{{ format_number($e['current']) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($e['previous']) }}</td><td class="px-4 py-2 text-sm text-right font-medium {{ $e['change'] >= 0 ? 'text-red-600' : 'text-green-600' }}">{{ format_number($e['change']) }}</td></tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">No expense accounts.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold"><td class="px-4 py-2 text-sm">Total Expenses</td><td class="px-4 py-2 text-sm text-right">{{ format_number($total_expenses_current) }}</td><td class="px-4 py-2 text-sm text-right text-gray-500">{{ format_number($total_expenses_previous) }}</td><td class="px-4 py-2 text-sm text-right {{ $total_expenses_change >= 0 ? 'text-red-600' : 'text-green-600' }}">{{ format_number($total_expenses_change) }}</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Net Income --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-4">
        <p class="text-sm font-medium text-gray-700">Net Income: <span class="text-lg font-bold {{ $net_income >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ $cs }} {{ format_number($net_income) }}</span></p>
    </div>
</div>
</x-app-layout>
