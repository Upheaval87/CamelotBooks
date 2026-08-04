<x-app-layout>
    <x-list-header title="{{ __('New Deposit') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <x-button variant="ghost" href="{{ route('accounting.deposits.index') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </x-button>
            </div>
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.deposits.store') }}" x-data="{ selectedTotal: 0, selectedIds: [] }">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="bank_account_id" value="{{ __('Deposit To') }}" />
                            <x-scoped-search-field
                                name="bank_account_id"
                                entity="bank-account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'bank-account']) }}"
                                :value="old('bank_account_id')"
                                :label="old('bank_account_name', ($bankAccounts->firstWhere('id', (int) old('bank_account_id'))?->name ?? ''))"
                                placeholder="{{ __('Search bank accounts...') }}"
                                required
                            />
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
                                    <table class="datasheet">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">
                                                    <input type="checkbox" @change="let checked = $event.target.checked; document.querySelectorAll('.deposit-item').forEach(cb => { cb.checked = checked; cb.dispatchEvent(new Event('change')) })" class="rounded border-gray-300" />
                                                </th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th class="text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($undepositedLines as $line)
                                                <tr>
                                                    <td class="px-4 py-2">
                                                        <input type="checkbox" name="journal_entry_ids[]" value="{{ $line->journal_entry_id }}"
                                                            class="deposit-item rounded border-gray-300"
                                                            @change="if($event.target.checked){selectedTotal += {{ $line->debit }}; selectedIds.push({{ $line->journal_entry_id }})} else {selectedTotal -= {{ $line->debit }}; selectedIds = selectedIds.filter(id => id !== {{ $line->journal_entry_id }})}"
                                                        />
                                                    </td>
                                                    <td class="text-ink-soft">{{ $line->journalEntry->date->format('M d, Y') }}</td>
                                                    <td class="text-ink-soft">{{ $line->memo ?? $line->journalEntry->memo ?? '—' }}</td>
                                                    <td class="numeric">{{ format_money($line->debit) }}</td>
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

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.deposits.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button type="submit">{{ __('Record Deposit') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>
                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Customer'), 'route' => route('accounting.customers.create'), 'icon' => 'person'],
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => 'document'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Deposits List'), 'route' => route('accounting.deposits.index'), 'icon' => 'document'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
