<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Customer Statement</h1>
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer</label>
                <select name="customer_id" required class="mt-1 block border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @foreach(\App\Models\Customer::where('company_id', session('current_company_id'))->where('is_active', true)->orderBy('name')->get() as $c)
                        <option value="{{ $c->id }}" {{ ($customerId ?? request('customer_id')) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">From</label><input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">To</label><input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="mt-1 block border-gray-300 rounded-md shadow-sm sm:text-sm"></div>
            <x-primary-button type="submit">Generate</x-primary-button>
        </form>
    </div>
    @if(isset($customer))
    <div class="mb-4 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="font-semibold text-gray-800">{{ $customer->name }}</h2>
        <p class="text-sm text-gray-500">{{ $customer->email ?? '' }} {{ $customer->phone ?? '' }}</p>
    </div>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th class="text-right">Debit ({{ $cs }})</th>
                <th class="text-right">Credit ({{ $cs }})</th>
                <th class="text-right">Balance ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                <tr class="bg-gray-50 font-medium">
                    <td colspan="3" class="px-4 py-2 text-sm">Opening Balance</td>
                    <td colspan="2" class="px-4 py-2 text-sm text-right">{{ format_number($opening_balance) }}</td>
                    <td class="numeric">{{ format_number($opening_balance) }}</td>
                </tr>
                @forelse($transactions as $t)
                <tr class="hover:bg-gray-50">
                    <td>{{ $t['date'] }}</td>
                    <td>{{ $t['type'] }}</td>
                    <td class="px-4 py-2 text-sm font-mono">{{ $t['reference'] }}</td>
                    <td class="numeric">{{ $t['debit'] > 0 ? format_number($t['debit']) : '' }}</td>
                    <td class="numeric">{{ $t['credit'] > 0 ? format_number($t['credit']) : '' }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium">{{ format_number($t['balance']) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No transactions found.</td></tr>
                @endforelse
                <tr class="bg-gray-50 font-bold">
                    <td colspan="3" class="px-4 py-2 text-sm">Closing Balance</td>
                    <td class="numeric">{{ format_number($total_debit) }}</td>
                    <td class="numeric">{{ format_number($total_credit) }}</td>
                    <td class="numeric">{{ format_number($closing_balance) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
</div>
</x-app-layout>
