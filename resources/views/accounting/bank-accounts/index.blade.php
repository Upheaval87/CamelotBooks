<x-app-layout>
    <x-list-header title="{{ __('Create Account') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.accounts.create') }}">
                    {{ __('Create Account') }}
                </x-button>
            </div>
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

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account Name</th>
                                <th>Account Number</th>
                                <th>Bank</th>
                                <th class="text-right">Book Balance</th>
                                <th class="text-center">Last Reconciled</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bankAccounts as $bankAccount)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="text-ink hover:text-gold">
                                            {{ $bankAccount->name }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $bankAccount->account_number ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $bankAccount->bank_name ?? '—' }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($bankAccount->current_balance) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        {{ $bankAccount->last_reconciled_date?->format('M d, Y') ?? 'Never' }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.bank-accounts.register', $bankAccount) }}" class="text-ink hover:text-gold">Register</a>
                                        <a href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}" class="text-ink hover:text-gold">Reconcile</a>
                                        <a href="{{ route('accounting.bank-accounts.manual-form', $bankAccount->id) }}" class="text-ink hover:text-gold">Manual Transaction</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-ink-soft">
                                        No bank accounts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
