<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('New Recurring Journal Template') }}
            </h2>
            <a href="{{ route('accounting.recurring-journals.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Templates') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <form id="templateForm" method="POST" action="{{ route('accounting.recurring-journals.store') }}">
                @csrf

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Template Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <x-input-label for="name" value="{{ __('Template Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="frequency" value="{{ __('Frequency') }}" />
                            <select id="frequency" name="frequency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="biweekly" {{ old('frequency') === 'biweekly' ? 'selected' : '' }}>Biweekly</option>
                                <option value="monthly" {{ old('frequency') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ old('frequency') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="yearly" {{ old('frequency') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                            <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                            <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">No Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="start_date" value="{{ __('Start Date') }}" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="{{ __('End Date (optional)') }}" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="day_of_month" value="{{ __('Day of Month') }}" />
                            <x-text-input id="day_of_month" name="day_of_month" type="number" class="mt-1 block w-full" :value="old('day_of_month')" min="1" max="31" />
                            <x-input-error :messages="$errors->get('day_of_month')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="day_of_week" value="{{ __('Day of Week') }}" />
                            <select id="day_of_week" name="day_of_week" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Not set</option>
                                <option value="0" {{ old('day_of_week') == '0' ? 'selected' : '' }}>Sunday</option>
                                <option value="1" {{ old('day_of_week') == '1' ? 'selected' : '' }}>Monday</option>
                                <option value="2" {{ old('day_of_week') == '2' ? 'selected' : '' }}>Tuesday</option>
                                <option value="3" {{ old('day_of_week') == '3' ? 'selected' : '' }}>Wednesday</option>
                                <option value="4" {{ old('day_of_week') == '4' ? 'selected' : '' }}>Thursday</option>
                                <option value="5" {{ old('day_of_week') == '5' ? 'selected' : '' }}>Friday</option>
                                <option value="6" {{ old('day_of_week') == '6' ? 'selected' : '' }}>Saturday</option>
                            </select>
                            <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
                        </div>
                        <div class="flex items-end pb-1">
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_post" value="1" {{ old('auto_post') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <span class="ml-2 text-sm text-gray-600">{{ __('Auto Post') }}</span>
                            </label>
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="memo" value="{{ __('Memo') }}" />
                            <textarea id="memo" name="memo" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('memo') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Template Lines') }}</h3>
                        <button type="button" id="addLineBtn" class="inline-flex items-center px-3 py-1 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            + {{ __('Add Line') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="linesTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Dr ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Cr ({{ $cs }})</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
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

                <div class="flex items-center justify-end space-x-3">
                    <a href="{{ route('accounting.recurring-journals.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Create Template') }}
                    </button>
                </div>
            </form>
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
                '<td class="px-4 py-2 text-sm text-gray-500">' + (tbody.rows.length + 1) + '</td>' +
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
            const diff = Math.abs(totalDebit - totalCredit);
            if (totalDebit === 0 && totalCredit === 0) {
                indicator.innerHTML = '';
            } else if (diff < 0.005) {
                indicator.innerHTML = '<span class="text-green-600 font-semibold">Balanced</span>';
            } else {
                indicator.innerHTML = '<span class="text-red-600 font-semibold">Out of balance: ' + diff.toFixed(2) + '</span>';
            }
        }

        function attachInputListeners() {
            document.querySelectorAll('.debit-input, .credit-input').forEach(function(input) {
                input.removeEventListener('input', updateTotals);
                input.addEventListener('input', updateTotals);
            });
        }

        document.getElementById('addLineBtn').addEventListener('click', addLine);

        document.getElementById('templateForm').addEventListener('submit', function(e) {
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
