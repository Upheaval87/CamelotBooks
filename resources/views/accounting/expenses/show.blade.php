<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Expense') }} {{ $expense->expense_number }}
            </h2>
            <div class="flex gap-2">
                @if($expense->status === 'draft')
                    <form method="POST" action="{{ route('accounting.expenses.post', $expense) }}" class="inline" onsubmit="return confirm('Post this expense? This will create a journal entry.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Post') }}
                        </button>
                    </form>
                @endif
                @if($expense->status !== 'void' && $expense->status !== 'draft')
                    <form method="POST" action="{{ route('accounting.expenses.void', $expense) }}" class="inline" id="void-form">
                        @csrf
                        <input type="hidden" name="reason" id="void-reason" value="" />
                        <button type="button" onclick="askVoidReason()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Void') }}
                        </button>
                    </form>
                @endif
                <a href="{{ route('accounting.expenses.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Expense #</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->expense_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        @switch($expense->status)
                            @case('draft')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Draft</span>
                                @break
                            @case('posted')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Posted</span>
                                @break
                            @case('void')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-500">Void</span>
                                @break
                        @endswitch
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Expense Date</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->expense_date?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Vendor</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->vendor->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Paid From</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->bankAccount?->code ? $expense->bankAccount->code . ' - ' . $expense->bankAccount->name : 'Default Cash (1000)' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Reference</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->reference ?? '—' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Memo</p>
                        <p class="text-sm font-medium text-gray-900">{{ $expense->memo ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Line Items</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price ({{ $cs }})</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax ({{ $cs }})</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($expense->lines as $line)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $line->expenseAccount?->code ?? '' }} - {{ $line->expenseAccount?->name ?? '' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $line->description }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ $line->quantity }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ format_number($line->unit_price) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ format_number($line->tax_amount) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right font-medium">{{ format_number($line->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-2 text-sm font-semibold text-gray-800 text-right">Total:</td>
                                <td class="px-4 py-2 text-sm font-bold text-gray-900 text-right">{{ format_number($expense->amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($expense->journal_entry_id)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Journal Entry</h3>
                    <a href="{{ route('accounting.journal-entries.show', $expense->journal_entry_id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                        JE #{{ $expense->journalEntry?->entry_number ?? $expense->journal_entry_id }}
                    </a>
                </div>
            @endif

            @if($expense->void_reason)
                <div class="bg-red-50 overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-red-800 mb-2">Void Reason</h3>
                    <p class="text-sm text-red-700">{{ $expense->void_reason }}</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function askVoidReason() {
            const reason = prompt('Enter reason for voiding this expense:');
            if (reason !== null && reason.trim() !== '') {
                document.getElementById('void-reason').value = reason;
                document.getElementById('void-form').submit();
            }
        }
    </script>
</x-app-layout>
