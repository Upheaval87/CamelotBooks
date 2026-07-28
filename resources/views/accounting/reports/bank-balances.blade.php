<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('regional', 'currency_symbol', session('current_company_id'), 'KES'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Bank Balances</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($accounts as $account)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $account->code }}</td>
                    <td class="px-4 py-2 text-sm">{{ $account->name }}</td>
                    <td class="px-4 py-2 text-sm">{{ ucfirst($account->bank_type ?? 'N/A') }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($account->balance) }}</td>
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