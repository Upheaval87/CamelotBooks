<x-app-layout>
@php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '; @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Deposits in Transit" />
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Memo</th>
                <th class="text-right">Amount ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($deposits as $deposit)
                <tr class="hover:bg-gray-50">
                    <td>{{ $deposit->date }}</td>
                    <td>{{ $deposit->reference }}</td>
                    <td>{{ $deposit->memo }}</td>
                    <td class="numeric">{{ format_number($deposit->amount) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No deposits in transit found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total in Transit</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($total_in_transit) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Account Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($account_balance) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Variance</p>
                @if($variance != 0)
                    <p class="text-lg font-bold text-red-600">{{ $cs }} {{ format_number($variance) }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">{{ $cs }} {{ format_number($variance) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>); @endphp
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <x-list-header title="Deposits in Transit" />
    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
        <table class="datasheet">
            <thead><tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Memo</th>
                <th class="text-right">Amount ({{ $cs }})</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($deposits as $deposit)
                <tr class="hover:bg-gray-50">
                    <td>{{ $deposit->date }}</td>
                    <td>{{ $deposit->reference }}</td>
                    <td>{{ $deposit->memo }}</td>
                    <td class="numeric">{{ format_number($deposit->amount) }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No deposits in transit found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total in Transit</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($total_in_transit) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Account Balance</p>
                <p class="text-lg font-bold text-gray-900">{{ $cs }} {{ format_number($account_balance) }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Variance</p>
                @if($variance != 0)
                    <p class="text-lg font-bold text-red-600">{{ $cs }} {{ format_number($variance) }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">{{ $cs }} {{ format_number($variance) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>