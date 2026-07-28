<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Bank Reconciliation History</h1>
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Account</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statement Date</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Statement Bal ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cleared Bal ({{ $cs }})</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Difference ({{ $cs }})</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed By</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($reconciliations as $r)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm">{{ $r['bank_account'] }} <span class="text-gray-400">({{ $r['bank_account_code'] }})</span></td>
                    <td class="px-4 py-2 text-sm">{{ $r['statement_date'] }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($r['statement_balance']) }}</td>
                    <td class="px-4 py-2 text-sm text-right">{{ format_number($r['cleared_balance']) }}</td>
                    <td class="px-4 py-2 text-sm text-right {{ $r['difference'] != 0 ? 'text-red-600 font-medium' : 'text-green-600' }}">{{ format_number($r['difference']) }}</td>
                    <td class="px-4 py-2 text-sm"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $r['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">{{ $r['status'] }}</span></td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $r['completed_by'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No reconciliations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</x-app-layout>
