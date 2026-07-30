<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('New Journal Entry') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="form-page">
                <div class="form-page-main">
            <form id="journalForm" method="POST" action="{{ route('accounting.journal-entries.store') }}">
                @csrf

                <input type="hidden" name="action" id="actionInput" value="">

                <x-toolbar class="mb-6">
                    <button type="submit" onclick="document.getElementById('actionInput').value='save_draft'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-atlas-navy/20 text-atlas-navy text-sm font-medium rounded-md hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        Save as Draft
                    </button>
                    <button type="submit" onclick="document.getElementById('actionInput').value='post'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-atlas-amber text-atlas-navy text-sm font-medium rounded-md hover:brightness-110 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Post
                    </button>

                    <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                    <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        Attach File
                    </button>

                    <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                    <span id="balanceStatusPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md">
                    </span>

                    <x-slot name="right">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="py-1">
                                    <button type="button" onclick="clearAllLines()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Clear All Lines
                                    </button>
                                    <button type="button" onclick="resetForm()" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Reset Form
                                    </button>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </x-slot>
                </x-toolbar>

                <div class="card p-6 mb-6">
                    <x-form.section number="01" :title="__('Entry Details')" />
                    <div class="grid grid-cols-4 gap-6">
                        <div>
                            <x-input-label for="date" value="{{ __('Date') }}" />
                            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="reference" value="{{ __('Reference') }}" />
                            <x-text-input id="reference" name="reference" type="text" class="mt-1 block w-full" :value="old('reference')" placeholder="e.g. Invoice #, Receipt #" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                            <select id="branch_id" name="branch_id" class="input mt-1">
                                <option value="">No Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_adjusting_entry" value="1" {{ old('is_adjusting_entry') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="ml-2 text-sm text-gray-600">{{ __('Adjusting Entry') }}</span>
                            </label>
                        </div>
                        <div class="col-span-4">
                            <x-input-label for="memo" value="{{ __('Description') }}" />
                            <textarea id="memo" name="memo" rows="2" class="input mt-1">{{ old('memo') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <x-form.section number="02" :title="__('Lines')" />
                        <button type="button" id="addLineBtn" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + {{ __('Add Line') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="linesTable">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">#</th>
                                    <th>Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Dr ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Cr ({{ $cs }})</th>
                                    <th>Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Branch</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="linesBody" class="bg-white divide-y divide-gray-200">
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Totals</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900" id="totalDebit">0.00</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold text-gray-900" id="totalCredit">0.00</td>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm" id="balanceIndicator"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>
            </form>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Chart of Accounts'), 'route' => route('accounting.accounts.index'), 'icon' => 'user'],
                        ['title' => __('Journal Entries List'), 'route' => route('accounting.journal-entries.index'), 'icon' => 'list'],
                    ]],
                ]" />
            </div>
        </div>
    </div>

    <script>
        const accounts = @json($accounts);
        const branches = @json($branches);
        let lineIndex = 0;

        function buildAccountOptions() {
            let html = '<option value="">Select Account</option>';
            accounts.forEach(function(account) {
                html += '<option value="' + account.id + '">' + account.code + ' - ' + account.name + '</option>';
            });
            return html;
        }

        function buildBranchOptions() {
            let html = '<option value="">Use Header Branch</option>';
            branches.forEach(function(branch) {
                html += '<option value="' + branch.id + '">' + branch.name + '</option>';
            });
            return html;
        }

        function addLine() {
            const tbody = document.getElementById('linesBody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-index', lineIndex);
            tr.innerHTML =
                '<td class="text-ink-soft">' + (tbody.rows.length + 1) + '</td>' +
                '<td class="px-4 py-2">' +
                    '<select name="lines[' + lineIndex + '][account_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>' +
                        buildAccountOptions() +
                    '</select>' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="number" name="lines[' + lineIndex + '][debit]" step="0.01" min="0" value="0" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right debit-input" />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="number" name="lines[' + lineIndex + '][credit]" step="0.01" min="0" value="0" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right credit-input" />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="text" name="lines[' + lineIndex + '][memo]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<select name="lines[' + lineIndex + '][branch_id]" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">' +
                        buildBranchOptions() +
                    '</select>' +
                '</td>' +
                '<td class="px-4 py-2 text-center">' +
                    '<button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900 text-sm">&times;</button>' +
                '</td>';
            tbody.appendChild(tr);
            lineIndex++;
            attachInputListeners();
            updateTotals();
        }

        function removeLine(btn) {
            const tbody = document.getElementById('linesBody');
            if (tbody.rows.length <= 2) {
                alert('At least two lines are required.');
                return;
            }
            btn.closest('tr').remove();
            renumberLines();
            updateTotals();
        }

        function renumberLines() {
            const tbody = document.getElementById('linesBody');
            Array.from(tbody.rows).forEach(function(row, i) {
                row.querySelector('td').textContent = i + 1;
            });
        }

        function updateTotals() {
            let totalDebit = 0;
            let totalCredit = 0;

            document.querySelectorAll('.debit-input').forEach(function(input) {
                totalDebit += parseFloat(input.value) || 0;
            });

            document.querySelectorAll('.credit-input').forEach(function(input) {
                totalCredit += parseFloat(input.value) || 0;
            });

            document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
            document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

            const indicator = document.getElementById('balanceIndicator');
            const pill = document.getElementById('balanceStatusPill');
            const diff = Math.abs(totalDebit - totalCredit);
            if (totalDebit === 0 && totalCredit === 0) {
                indicator.innerHTML = '';
                pill.innerHTML = '';
                pill.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md';
            } else if (diff < 0.005) {
                indicator.innerHTML = '<span class="text-green-600 font-semibold">Balanced</span>';
                pill.innerHTML = '<svg class="w-4 h-4 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Balanced';
                pill.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-green-50 text-green-700';
            } else {
                indicator.innerHTML = '<span class="text-red-600 font-semibold">Out of balance: ' + diff.toFixed(2) + '</span>';
                pill.innerHTML = '<svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Out of Balance: ' + window.currencySymbol + diff.toFixed(2);
                pill.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-red-50 text-red-700';
            }
        }

        function attachInputListeners() {
            document.querySelectorAll('.debit-input, .credit-input').forEach(function(input) {
                input.removeEventListener('input', updateTotals);
                input.addEventListener('input', updateTotals);
            });
        }

        function clearAllLines() {
            if (!confirm('Clear all lines?')) return;
            const tbody = document.getElementById('linesBody');
            tbody.innerHTML = '';
            addLine();
            addLine();
            updateTotals();
        }

        function resetForm() {
            if (!confirm('Reset the entire form?')) return;
            document.getElementById('journalForm').reset();
            document.getElementById('memo').value = '';
            const tbody = document.getElementById('linesBody');
            tbody.innerHTML = '';
            lineIndex = 0;
            addLine();
            addLine();
            updateTotals();
        }

        document.getElementById('addLineBtn').addEventListener('click', addLine);

        document.getElementById('journalForm').addEventListener('submit', function(e) {
            updateTotals();
            const totalDebit = parseFloat(document.getElementById('totalDebit').textContent) || 0;
            const totalCredit = parseFloat(document.getElementById('totalCredit').textContent) || 0;

            if (Math.abs(totalDebit - totalCredit) >= 0.005) {
                e.preventDefault();
                alert('Debits and credits must be equal before submitting.');
                return;
            }

            if (totalDebit === 0 && totalCredit === 0) {
                e.preventDefault();
                alert('At least one line must have a debit or credit amount.');
                return;
            }
        });

        addLine();
        addLine();
    </script>
</x-app-layout>
