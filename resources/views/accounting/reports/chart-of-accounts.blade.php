<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Chart of Accounts</h1>
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Account Type</label>
                <select name="type" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach(['asset','liability','equity','income','expense'] as $t)
                        <option value="{{ $t }}" {{ ($selected_type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button type="submit">Filter</x-primary-button>
            <span class="text-sm text-gray-500">{{ $total_accounts }} accounts</span>
        </form>
    </div>
    @foreach($grouped as $group)
    <div class="mb-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">{{ ucfirst($group['type']) }} Accounts</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sub Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Opening ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Current ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($group['accounts'] as $a)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-mono">{{ $a['code'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $a['name'] }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $a['sub_type'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $a['description'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($a['opening_balance']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($a['current_balance']) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endforeach
</div>
</x-app-layout>
