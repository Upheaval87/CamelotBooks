<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Expense') }} {{ $expense->expense_number }}</x-slot>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.expenses.create') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($expense->status === 'draft')
                        <a href="{{ route('accounting.expenses.edit', $expense) }}" class="tr-save">{{ __('Save') }}</a>
                        <form method="POST" action="{{ route('accounting.expenses.post', $expense) }}" class="inline" onsubmit="return confirm('{{ __('Post this expense?') }}')">
                            @csrf
                            <button type="submit" class="tr-save">{{ __('Save & Submit') }}</button>
                        </form>
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <a href="{{ route('accounting.employees.show', $expense->employee) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ __('Lookup Employee') }}
                    </a>
                    <a href="{{ route('accounting.cost-centers.index') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ __('Cost Center') }}
                    </a>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        {{ __('Claim History') }}
                    </button>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button type="button" class="tr-item relative">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('Attach Receipts') }}
                        @if(($expense->receipts_count ?? 0) > 0)
                            <span class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-atlas-danger rounded-full">{{ $expense->receipts_count }}</span>
                        @endif
                    </button>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                </div>

                <div class="tr-spacer"></div>

                @if($expense->status !== 'void' && $expense->status !== 'draft')
                    <form method="POST" action="{{ route('accounting.expenses.void', $expense) }}" class="inline" id="void-form">
                        @csrf
                        <input type="hidden" name="reason" id="void-reason" value="" />
                        <button type="button" class="tr-archive" onclick="askVoidReason()">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ __('Withdraw Claim') }}
                        </button>
                    </form>
                @endif

                <x-dropdown align="left" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="tr-more" aria-label="{{ __('More actions') }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                {{ __('Duplicate') }}
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ __('Export') }}
                            </button>
                            <a href="{{ route('accounting.expenses.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Expenses') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field :label="__('Expense #')" :value="$expense->expense_number" />
                    <x-detail-field :label="__('Status')">
                        @if($expense->status === 'draft')
                            <span class="status-pill neutral">{{ __('Draft') }}</span>
                        @elseif($expense->status === 'posted')
                            <span class="status-pill positive">{{ __('Posted') }}</span>
                        @elseif($expense->status === 'void')
                            <span class="status-pill negative">{{ __('Void') }}</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($expense->status) }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Employee')" :value="$expense->employee->name ?? '—'" />
                    <x-detail-field :label="__('Date')" :value="$expense->expense_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Amount')" value-class="text-lg font-bold text-ink">
                        {{ format_money($expense->total_amount ?? 0) }}
                    </x-detail-field>
                    <x-detail-field :label="__('Branch')" :value="$expense->branch->name ?? '—'" />
                    @if($expense->description)
                        <x-detail-field :label="__('Description')" :value="$expense->description" class="col-span-4" />
                    @endif
                </div>
            </div>

            @if(isset($expense->lines) && $expense->lines->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Expense Lines') }}</p>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="text-right">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expense->lines as $line)
                                    <tr>
                                        <td>{{ $line->account->name ?? '—' }}</td>
                                        <td class="text-ink-soft">{{ $line->description }}</td>
                                        <td class="numeric">{{ format_money($line->amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function askVoidReason() {
            var reason = prompt('{{ __('Please enter the reason for withdrawing this claim') }}:');
            if (reason && reason.trim() !== '') {
                document.getElementById('void-reason').value = reason;
                document.getElementById('void-form').submit();
            }
        }
    </script>
</x-app-layout>
