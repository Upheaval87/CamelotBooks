<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Purchase Register</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tax ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($bills as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['type'] === 'Bill' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }}">{{ $row['type'] }}</span></td>
                    <td class="px-4 py-2 text-sm font-mono">{{ $row['reference'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['date'] }}</td>
                    <td class="px-4 py-2 text-sm">{{ $row['vendor_name'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['amount']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['tax_total']) }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst($row['status']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No purchases found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-sm text-right">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_amount) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_tax) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>
