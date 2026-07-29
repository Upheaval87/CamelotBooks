<x-app-layout>
    <x-slot name="header">{{ __('Bank Reconciliation') }} — {{ $bankAccount->name ?? '' }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.bank-reconciliation.import-form', $bankAccount->id ?? '') }}">{{ __('Import Statement') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.bank-accounts.index') }}">{{ __('Back to Accounts') }}</x-button>
    </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            @if(!isset($bankAccount) || !$bankAccount)
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form method="GET" onsubmit="event.preventDefault(); var v = document.getElementById('bank_account_id').value; if(v) window.location.href = '{{ url('accounting/bank-reconciliation') }}/' + v;" class="flex items-end gap-4">
                        <div class="flex-1">
                            <x-input-label for="bank_account_id" value="{{ __('Bank Account') }}" />
                            <select id="bank_account_id" name="bank_account_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">Select Bank Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}" {{ request('bank_account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <x-primary-button type="submit">{{ __('Select') }}</x-primary-button>
                    </form>
                </div>
            @endif

            @if(isset($bankAccount) && $bankAccount)
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ __('Start New Reconciliation') }}</h3>
                            <p class="text-sm text-gray-500 mt-1">Book balance: {{ format_money($bankAccount->current_balance) }}</p>
                        </div>
                        <a href="{{ route('accounting.bank-reconciliation.import-form', $bankAccount->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Start Reconciliation') }}
                        </a>
                    </div>
                </div>

                <div class="datasheet-wrap">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Reconciliation History') }}</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Statement Date</th>
                                    <th class="text-right">Statement Balance</th>
                                    <th class="text-right">Cleared Balance</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reconciliations as $reconciliation)
                                    <tr>
                                        <td>
                                            {{ $reconciliation->start_date?->format('M d, Y') }} — {{ $reconciliation->end_date?->format('M d, Y') }}
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $reconciliation->statement_date?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="numeric">
                                            {{ format_money($reconciliation->statement_balance) }}
                                        </td>
                                        <td class="numeric">
                                            {{ format_money($reconciliation->cleared_balance) }}
                                        </td>
                                        <td class="text-center">
                                            @if($reconciliation->status === 'completed')
                                                <span class="status-pill positive">Completed</span>
                                            @elseif($reconciliation->status === 'in_progress')
                                                <span class="status-pill neutral">In Progress</span>
                                            @else
                                                <span class="status-pill neutral">{{ ucfirst($reconciliation->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation) }}" class="text-ink hover:text-gold">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-ink-soft">
                                            No reconciliations found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
