<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Employee Cost by Branch</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>

    <h2 class="text-lg font-semibold text-gray-900 mb-3">By Branch</h2>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-8">
        <table class="datasheet">
            <thead><tr>
                <th>Branch</th>
                <th class="text-right">Total Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($byBranch as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['branch'] }}</td>
                    <td class="numeric">{{ format_number($row['total_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="text-lg font-semibold text-gray-900 mb-3">By Cost Center</h2>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Cost Center</th>
                <th class="text-right">Total Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($byCostCenter as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['cost_center'] }}</td>
                    <td class="numeric">{{ format_number($row['total_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Employee Cost by Branch</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>

    <h2 class="text-lg font-semibold text-gray-900 mb-3">By Branch</h2>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-8">
        <table class="datasheet">
            <thead><tr>
                <th>Branch</th>
                <th class="text-right">Total Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($byBranch as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['branch'] }}</td>
                    <td class="numeric">{{ format_number($row['total_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 class="text-lg font-semibold text-gray-900 mb-3">By Cost Center</h2>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Cost Center</th>
                <th class="text-right">Total Cost ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($byCostCenter as $row)
                <tr class="hover:bg-gray-50">
                    <td>{{ $row['cost_center'] }}</td>
                    <td class="numeric">{{ format_number($row['total_cost']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>