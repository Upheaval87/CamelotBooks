<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('New Deposit') }}</h2>
            <a href="{{ route('accounting.deposits.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                {{ __('Back') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.deposits.store') }}" x-data="{ selectedTotal: 0, selectedIds: [] }">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Deposit To') }}" />
                            <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->code }} - {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('bank_account_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="date" value="{{ __('Deposit Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label value="{{ __('Select Items to Deposit') }}" />
                            <p class="text-xs text-gray-500 mb-2">Check the items included in this deposit.</p>
                            @if($undepositedLines->isEmpty())
                                <p class="text-sm text-gray-500">No undeposited items available.</p>
                            @else
                                <div class="border border-gray-200 rounded-md overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">
                                                    <input type="checkbox" @change="let checked = $event.target.checked; document.querySelectorAll('.deposit-item').forEach(cb => { cb.checked = checked; cb.dispatchEvent(new Event('change')) })" class="rounded border-gray-300" />
                                                </th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($undepositedLines as $line)
                                                <tr>
                                                    <td class="px-4 py-2">
                                                        <input type="checkbox" name="journal_entry_ids[]" value="{{ $line->journal_entry_id }}"
                                                            class="deposit-item rounded border-gray-300"
                                                            @change="if($event.target.checked){selectedTotal += {{ $line->debit }}; selectedIds.push({{ $line->journal_entry_id }})} else {selectedTotal -= {{ $line->debit }}; selectedIds = selectedIds.filter(id => id !== {{ $line->journal_entry_id }})}"
                                                        />
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $line->journalEntry->date->format('M d, Y') }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $line->memo ?? $line->journalEntry->memo ?? '—' }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($line->debit, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div>
                            <x-input-label for="amount" value="{{ __('Total Deposit Amount') }}" />
                            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('amount', '0')" required />
                            <p class="text-xs text-gray-500 mt-1">Selected total: <span x-text="selectedTotal.toFixed(2)"></span></p>
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description')" placeholder="e.g. Daily deposit" />
                        </div>

                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('accounting.deposits.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button type="submit">{{ __('Record Deposit') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
