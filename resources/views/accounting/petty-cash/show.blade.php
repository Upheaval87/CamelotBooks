<x-app-layout>
    <x-slot name="header">{{ $fund->name }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
            @endif

            <div class="space-y-6">

            <x-record-toolbar>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.petty-cash.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">
                    <div class="grid grid-cols-3 gap-6 mb-6">
                        <div class="card p-6">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Float Amount') }}</p>
                            <p class="mt-1 text-2xl font-bold text-indigo-600">{{ format_money($fund->petty_cash_float ?? 0) }}</p>
                        </div>
                        <div class="card p-6">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Current Balance') }}</p>
                            <p class="mt-1 text-2xl font-bold text-green-600">{{ format_money($fund->current_balance) }}</p>
                        </div>
                        <div class="card p-6">
                            <p class="text-xs font-medium text-ink-soft uppercase">{{ __('Spent') }}</p>
                            <p class="mt-1 text-2xl font-bold text-red-600">{{ format_money(($fund->petty_cash_float ?? 0) - $fund->current_balance) }}</p>
                        </div>
                    </div>

                    @if(($fund->petty_cash_float ?? 0) == 0)
                        <div class="card p-6 mb-6" x-data="{ show: false }">
                            <p class="text-base font-semibold text-ink mb-4">{{ __('Establish Fund') }}</p>
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
                        <div class="card p-6" x-data="{ show: false }">
                            <p class="text-base font-semibold text-ink mb-4">{{ __('Record Expense') }}</p>
                            <form method="POST" action="{{ route('accounting.petty-cash.expense') }}">
                                @csrf
                                <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="exp_account" value="{{ __('Expense Account') }}" />
                                        <select id="exp_account" name="debit_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                            <option value="">{{ __('Select Account') }}</option>
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

                        <div class="card p-6">
                            <p class="text-base font-semibold text-ink mb-4">{{ __('Replenish Fund') }}</p>
                            <form method="POST" action="{{ route('accounting.petty-cash.replenish') }}">
                                @csrf
                                <input type="hidden" name="petty_cash_account_id" value="{{ $fund->id }}" />
                                <div class="space-y-4">
                                    <div>
                                        <x-input-label for="rep_bank" value="{{ __('From Bank Account') }}" />
                                        <select id="rep_bank" name="bank_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                            <option value="">{{ __('Select Bank Account') }}</option>
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
                                        <x-text-input id="rep_desc" name="description" type="text" class="mt-1 block w-full" placeholder="{{ __('Optional') }}" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <x-primary-button type="submit">{{ __('Replenish Fund') }}</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card p-6">
                        <p class="text-base font-semibold text-ink mb-5">{{ __('Expense History') }}</p>
                        <div class="overflow-x-auto">
                            <table class="record-datasheet">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th class="text-right">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expenses as $expense)
                                        <tr>
                                            <td>{{ $expense->journalEntry?->date?->format('M d, Y') ?? '—' }}</td>
                                            <td>{{ $expense->memo ?? $expense->journalEntry?->memo ?? '—' }}</td>
                                            <td class="numeric">{{ format_money($expense->credit) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-ink-soft">{{ __('No expenses recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => route('accounting.petty-cash.print', $fund), 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.petty-cash.index'), 'icon' => 'back', 'title' => __('Back')],
                    ]],
                ]" />
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
