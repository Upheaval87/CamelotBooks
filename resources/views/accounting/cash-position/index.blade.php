<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Cash Position') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Total Cash Position') }}</h3>
                <p class="text-3xl font-bold text-indigo-600">{{ number_format($totalCashPosition, 2) }}</p>
                <div class="mt-2 flex gap-6 text-sm text-gray-500">
                    <span>Bank: <strong class="text-gray-900">{{ number_format($totalBankBalance, 2) }}</strong></span>
                    <span>Petty Cash: <strong class="text-gray-900">{{ number_format($totalPettyCash, 2) }}</strong></span>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Bank Accounts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Book Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reconciled Balance</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Last Reconciled</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($bankAccounts as $account)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <a href="{{ route('accounting.bank-accounts.register', $account->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $account->code }} - {{ $account->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ number_format($account->current_balance, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($account->reconciled_balance, 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $account->last_reconciled_date?->format('M d, Y') ?? 'Never' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="{{ route('accounting.bank-accounts.register', $account->id) }}" class="text-indigo-600 hover:text-indigo-900">Register</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No bank accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($pettyCashSummary))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Petty Cash Funds</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fund</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Float</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Spent</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pettyCashSummary as $fund)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $fund['code'] }} - {{ $fund['name'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($fund['float'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ number_format($fund['current_balance'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ number_format($fund['spent'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-indigo-600 hover:text-indigo-900">Manage</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
