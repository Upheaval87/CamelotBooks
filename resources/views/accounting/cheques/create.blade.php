<x-app-layout>
    <x-slot name="header">{{ __('Write Cheque') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.cheques.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.cheques.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Bank Account') }}" />
                            <select id="bank_account_id" name="bank_account_id" class="input mt-1" required>
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} - {{ $account->name }} (Balance: {{ format_money($account->current_balance) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                        </div>

                        <div>
                            <x-input-label for="payee" value="{{ __('Payee') }}" />
                            <x-text-input id="payee" name="payee" type="text" class="mt-1 block w-full" :value="old('payee')" placeholder="Who is this cheque payable to?" required />
                        </div>

                        <div>
                            <x-input-label for="amount" value="{{ __('Amount') }}" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                        </div>

                        <div>
                            <x-input-label for="debit_account_id" value="{{ __('Expense/Asset Account') }}" />
                            <p class="text-xs text-gray-500 mb-1">The account to debit (expense or asset).</p>
                            <select id="debit_account_id" name="debit_account_id" class="input mt-1" required>
                                <option value="">Select Account</option>
                                @foreach($expenseAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('debit_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('debit_account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <x-text-input id="memo" name="memo" type="text" class="mt-1 block w-full" :value="old('memo')" placeholder="Optional memo" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <x-button variant="ghost" href="{{ route('accounting.cheques.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Write Cheque') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
