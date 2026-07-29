<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Asset Impairment</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Asset</th>
                <th>Code</th>
                <th>Category</th>
                <th class="text-right">Carrying Amount ({{ $cs }})</th>
                <th class="text-right">Recoverable Amount ({{ $cs }})</th>
                <th class="text-right">Amount ({{ $cs }})</th>
                <th>Type</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['asset'] }}</td>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="numeric">{{ format_number($row['carrying_amount']) }}</td>
                    <td class="numeric">{{ format_number($row['recoverable_amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['amount']) }}</td>
                    <td>{{ ucfirst($row['type']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No impairment data found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-sm text-right">Total Impairment / Total Reversal</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_impairment) }} / {{ format_number($total_reversal) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Asset Impairment</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Asset</th>
                <th>Code</th>
                <th>Category</th>
                <th class="text-right">Carrying Amount ({{ $cs }})</th>
                <th class="text-right">Recoverable Amount ({{ $cs }})</th>
                <th class="text-right">Amount ({{ $cs }})</th>
                <th>Type</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['asset'] }}</td>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="numeric">{{ format_number($row['carrying_amount']) }}</td>
                    <td class="numeric">{{ format_number($row['recoverable_amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['amount']) }}</td>
                    <td>{{ ucfirst($row['type']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No impairment data found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="6" class="px-4 py-3 text-sm text-right">Total Impairment / Total Reversal</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_impairment) }} / {{ format_number($total_reversal) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>