<x-app-layout>
    <x-slot name="header">{{ __('New Deposit') }}</x-slot>

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
                            <select id="bank_account_id" name="bank_account_id" class="input mt-1" required>
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
                        ['title' => __('New Customer'), 'route' => route('accounting.customers.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z\"/></svg>'],
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Deposits List'), 'route' => route('accounting.deposits.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
