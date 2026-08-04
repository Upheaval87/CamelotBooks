<x-app-layout>
    <x-list-header title="{{ __('Cash Position') }}" />

    <div class="py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Total Cash Position') }}</h3>
                <p class="text-3xl font-bold text-indigo-600">{{ format_money($totalCashPosition) }}</p>
                <div class="mt-2 flex gap-6 text-sm text-gray-500">
                    <span>Bank: <strong class="text-gray-900">{{ format_money($totalBankBalance) }}</strong></span>
                    <span>Petty Cash: <strong class="text-gray-900">{{ format_money($totalPettyCash) }}</strong></span>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Bank Accounts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Book Balance</th>
                                <th class="text-right">Reconciled Balance</th>
                                <th class="text-center">Last Reconciled</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bankAccounts as $account)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.bank-accounts.register', $account->id) }}" class="text-ink hover:text-gold">
                                            {{ $account->code }} - {{ $account->name }}
                                        </a>
                                    </td>
                                    <td class="numeric">{{ format_money($account->current_balance) }}</td>
                                    <td class="text-ink-soft text-right">{{ format_money($account->reconciled_balance) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $account->last_reconciled_date?->format('M d, Y') ?? 'Never' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="{{ route('accounting.bank-accounts.register', $account->id) }}" class="text-ink hover:text-gold">Register</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">No bank accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($pettyCashSummary))
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Petty Cash Funds</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Fund</th>
                                <th class="text-right">Float</th>
                                <th class="text-right">Balance</th>
                                <th class="text-right">Spent</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pettyCashSummary as $fund)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-ink hover:text-gold">
                                            {{ $fund['code'] }} - {{ $fund['name'] }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft text-right">{{ format_money($fund['float']) }}</td>
                                    <td class="numeric">{{ format_money($fund['current_balance']) }}</td>
                                    <td class="text-ink-soft text-right">{{ format_money($fund['spent']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <a href="{{ route('accounting.petty-cash.show', $fund['id']) }}" class="text-ink hover:text-gold">Manage</a>
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
