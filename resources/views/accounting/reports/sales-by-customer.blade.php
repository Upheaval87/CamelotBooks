<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Sales by Customer</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoice Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoices</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Receipt Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">POS Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Grand Total ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['customer'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['invoice_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['invoices']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['receipt_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pos_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['grand_total']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-4 py-3 text-sm text-right">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_invoice) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_invoices) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_receipt) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_pos) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_grand) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Sales by Customer</h1>
    <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex gap-4 items-end">
        @csrf
        <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
    </form>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoice Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoices</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Receipt Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">POS Total ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Grand Total ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $row['customer'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['invoice_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['invoices']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['receipt_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($row['pos_total']) }}</td>
                    <td class="px-4 py-2 text-sm text-right font-semibold">{{ format_number($row['grand_total']) }}</td>
                </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No data found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td class="px-4 py-3 text-sm text-right">Totals</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_invoice) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_invoices) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_receipt) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_pos) }}</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_grand) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>