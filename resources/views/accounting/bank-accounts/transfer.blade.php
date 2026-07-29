<x-app-layout>
    <x-slot name="header">{{ __('Transfer Between Accounts') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.bank-accounts.index') }}">{{ __('Back to Accounts') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.bank-accounts.transfer') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="from_account_id" value="{{ __('Transfer From') }}" />
                            <select id="from_account_id" name="from_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Source Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} ({{ format_money($account->current_balance) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('from_account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="to_account_id" value="{{ __('Transfer To') }}" />
                            <select id="to_account_id" name="to_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Destination Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} ({{ format_money($account->current_balance) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('to_account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="amount" value="{{ __('Amount') }}" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="transfer_date" value="{{ __('Date') }}" />
                            <x-text-input id="transfer_date" name="transfer_date" type="date" class="mt-1 block w-full" :value="old('transfer_date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('transfer_date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="Transfer description" />
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.bank-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Transfer') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
