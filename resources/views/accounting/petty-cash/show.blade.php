<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $fund->name }}</h2>
            <a href="{{ route('accounting.petty-cash.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase">Float Amount</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-600">{{ number_format($fund->petty_cash_float ?? 0, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase">Current Balance</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">{{ number_format($fund->current_balance, 2) }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-medium text-gray-500 uppercase">Spent</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">{{ number_format(($fund->petty_cash_float ?? 0) - $fund->current_balance, 2) }}</p>
                </div>
            </div>

            @if(($fund->petty_cash_float ?? 0) == 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6" x-data="{ show: false }">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Establish Fund</h3>
                    <p class="text-sm text-gray-500 mb-4">Transfer money from a bank account to establish this petty cash fund.</p>
                    <form method="POST" action="{{ route('accounting.petty-cash.establish') }}">
                        @csrf
                        <input type="hidden" name="fund_id" value="{{ $fund->id }}" />
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="bank_account_id" value="{{ __('From Bank Account') }}" />
                                <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="est_amount" value="{{ __('Amount') }}" />
                                <x-text-input id="est_amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="est_date" value="{{ __('Date') }}" />
                                <x-text-input id="est_date" name="date" type="date" class="mt-1 block w-full" :value="now()->format('Y-m-d')" required />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-primary-button type="submit">{{ __('Establish Fund') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ show: false }">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Record Expense</h3>
                    <form method="POST" action="{{ route('accounting.petty-cash.expense') }}">
                        @csrf
                        <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="exp_account" value="{{ __('Expense Account') }}" />
                                <select id="exp_account" name="debit_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="">Select Account</option>
                                    @foreach($expenseAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="exp_amount" value="{{ __('Amount') }}" />
                                <x-text-input id="exp_amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="exp_date" value="{{ __('Date') }}" />
                                <x-text-input id="exp_date" name="date" type="date" class="mt-1 block w-full" :value="now()->format('Y-m-d')" required />
                            </div>
                            <div>
                                <x-input-label for="exp_desc" value="{{ __('Description') }}" />
                                <x-text-input id="exp_desc" name="description" type="text" class="mt-1 block w-full" placeholder="e.g. Office supplies" required />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-primary-button type="submit">{{ __('Record Expense') }}</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Replenish Fund</h3>
                    <form method="POST" action="{{ route('accounting.petty-cash.replenish') }}">
                        @csrf
                        <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="rep_bank" value="{{ __('From Bank Account') }}" />
                                <select id="rep_bank" name="bank_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="rep_amount" value="{{ __('Amount') }}" />
                                <x-text-input id="rep_amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
                            </div>
                            <div>
                                <x-input-label for="rep_date" value="{{ __('Date') }}" />
                                <x-text-input id="rep_date" name="date" type="date" class="mt-1 block w-full" :value="now()->format('Y-m-d')" required />
                            </div>
                            <div>
                                <x-input-label for="rep_desc" value="{{ __('Description') }}" />
                                <x-text-input id="rep_desc" name="description" type="text" class="mt-1 block w-full" placeholder="Optional" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <x-primary-button type="submit">{{ __('Replenish Fund') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Expense History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($expenses as $expense)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $expense->journalEntry?->date?->format('M d, Y') ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $expense->memo ?? $expense->journalEntry?->memo ?? '—' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ number_format($expense->credit, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No expenses recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
