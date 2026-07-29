<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Bank Balances</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th class="text-right">Balance ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($accounts as $account)
                <tr class="hover:bg-gray-50">
                    <td>{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td>{{ ucfirst($account->bank_type ?? 'N/A') }}</td>
                    <td class="numeric">{{ format_number($account->balance) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No bank accounts found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-sm text-right">Total Balance</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_balance) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Bank Balances</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th class="text-right">Balance ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($accounts as $account)
                <tr class="hover:bg-gray-50">
                    <td>{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td>{{ ucfirst($account->bank_type ?? 'N/A') }}</td>
                    <td class="numeric">{{ format_number($account->balance) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No bank accounts found.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-sm text-right">Total Balance</td>
                    <td class="px-4 py-3 text-sm text-right">{{ format_number($total_balance) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>