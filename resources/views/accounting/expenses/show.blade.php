<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Expense') }} {{ $expense->expense_number }}</x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button href="{{ route('accounting.expenses.index') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                New
            </x-toolbar-button>
            @if($expense->status === 'draft')
                <x-toolbar-button variant="commit" href="{{ route('accounting.expenses.edit', $expense) }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Save
                </x-toolbar-button>
                <form method="POST" action="{{ route('accounting.expenses.post', $expense) }}" class="inline" onsubmit="return confirm('Post this expense? This will create a journal entry.')">
                    @csrf
                    <x-toolbar-button variant="commit" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Save & Submit
                    </x-toolbar-button>
                </form>
            @endif

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Lookup Employee
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Cost Center
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Claim History
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button class="relative">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                Attach Receipts
                <span class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-atlas-danger rounded-full">{{ $expense->receipts_count ?? 0 }}</span>
            </x-toolbar-button>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <x-dropdown align="left" width="48">
                <x-slot name="trigger">
                    <x-toolbar-button>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </x-toolbar-button>
                </x-slot>
                <x-slot name="content">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Duplicate
                        </button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Export
                        </button>
                        <a href="{{ route('accounting.expenses.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Expenses
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            <x-slot name="right">
                @if($expense->status !== 'void' && $expense->status !== 'draft')
                    <form method="POST" action="{{ route('accounting.expenses.void', $expense) }}" class="inline" id="void-form">
                        @csrf
                        <input type="hidden" name="reason" id="void-reason" value="" />
                        <x-toolbar-button variant="danger" type="button" onclick="askVoidReason()">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Withdraw Claim
                        </x-toolbar-button>
                    </form>
                @endif
            </x-slot>
        </x-toolbar>
    </div>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">Expense #</p>
                    <p class="text-sm font-medium text-gray-900">{{ $expense->expense_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="mt-1">
                        @if($expense->status === 'draft')
                            <span class="status-pill neutral">Draft</span>
                        @elseif($expense->status === 'posted')
                            <span class="status-pill positive">Posted</span>
                        @elseif($expense->status === 'void')
                            <span class="status-pill negative">Void</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($expense->status) }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Employee</p>
                    <p class="text-sm font-medium text-gray-900">{{ $expense->employee->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date</p>
                    <p class="text-sm font-medium text-gray-900">{{ $expense->expense_date?->format('M d, Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-sm font-bold text-gray-900">{{ format_money($expense->total_amount ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Branch</p>
                    <p class="text-sm font-medium text-gray-900">{{ $expense->branch->name ?? '—' }}</p>
                </div>
                @if($expense->description)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if(isset($expense->lines) && $expense->lines->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Expense Lines') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
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

    <script>
        function askVoidReason() {
            var reason = prompt('Please enter the reason for withdrawing this claim:');
            if (reason && reason.trim() !== '') {
                document.getElementById('void-reason').value = reason;
                document.getElementById('void-form').submit();
            }
        }
    </script>
</x-app-layout>
