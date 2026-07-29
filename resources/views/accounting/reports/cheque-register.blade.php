<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Cheque Register</h1>
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <x-primary-button type="submit">Filter</x-primary-button>
        </form>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Cheque #</th>
                <th>Payee</th>
                <th>Bank Account</th>
                <th class="text-right">Amount ({{ $cs }})</th>
                <th>Type</th>
                <th>Reconciled</th>
                <th>Entered By</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($cheques as $ch)
                <tr class="hover:bg-gray-50">
                    <td>{{ $ch['date'] }}</td>
                    <td class="px-4 py-2 text-sm font-mono">{{ $ch['cheque_number'] }}</td>
                    <td>{{ $ch['payee'] }}</td>
                    <td>{{ $ch['bank_account'] }}</td>
                    <td class="px-4 py-2 text-sm text-right {{ $ch['type'] === 'payment' ? 'text-red-600' : 'text-green-600' }}">{{ format_number($ch['amount']) }}</td>
                    <td>{{ ucfirst($ch['type']) }}</td>
                    <td>{{ $ch['is_reconciled'] ? 'Yes' : 'No' }}</td>
                    <td class="text-ink-soft">{{ $ch['created_by'] }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">No cheques found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 grid grid-cols-2 gap-4 max-w-md">
        <div class="bg-white shadow-sm sm:rounded-lg p-4"><p class="text-sm text-gray-500">Total Payments</p><p class="text-lg font-bold text-red-600">{{ $cs }} {{ format_number($total_payments) }}</p></div>
        <div class="bg-white shadow-sm sm:rounded-lg p-4"><p class="text-sm text-gray-500">Total Receipts</p><p class="text-lg font-bold text-green-600">{{ $cs }} {{ format_number($total_receipts) }}</p></div>
    </div>
</div>
</x-app-layout>
