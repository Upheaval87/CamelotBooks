<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Budget') }}: {{ $budget->name }}
            </h2>
            <a href="{{ route('accounting.budgets.show', $budget) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Back to Budget') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.budgets.update', $budget) }}" id="budget-form">
                @csrf
                @method('PUT')

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Budget Details') }}</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="fiscal_year_id" value="{{ __('Fiscal Year') }}" />
                            <select id="fiscal_year_id" name="fiscal_year_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-gray-100" disabled>
                                @foreach($fiscalYears as $fy)
                                    <option value="{{ $fy->id }}" data-start="{{ $fy->start_date->format('Y-m-d') }}" data-end="{{ $fy->end_date->format('Y-m-d') }}" {{ $budget->fiscal_year_id == $fy->id ? 'selected' : '' }}>
                                        {{ $fy->label }} ({{ $fy->start_date->format('M Y') }} - {{ $fy->end_date->format('M Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="name" value="{{ __('Budget Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $budget->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $budget->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('Budget Lines') }}</h3>
                        <button type="button" id="add-line" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Add Line') }}
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="lines-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="lines-body">
                            </tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('accounting.budgets.show', $budget) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-primary-button type="submit">{{ __('Update Budget') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const accounts = @json($accounts);
        @php
            $existingLinesJson = $budget->lines->map(fn($l) => ['account_id' => $l->account_id, 'period_label' => $l->period_label, 'amount' => $l->amount, 'notes' => $l->notes])->values();
        @endphp
        const existingLines = @json($existingLinesJson);
        let lineIndex = 0;

        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        function getPeriodLabels() {
            const fySelect = document.getElementById('fiscal_year_id');
            const option = fySelect.options[fySelect.selectedIndex];
            if (!option || !option.value) return [];

            const start = new Date(option.dataset.start);
            const end = new Date(option.dataset.end);
            const labels = [];
            let d = new Date(start);

            while (d <= end) {
                labels.push(months[d.getMonth()] + ' ' + d.getFullYear());
                d.setMonth(d.getMonth() + 1);
            }

            return labels;
        }

        function buildAccountOptions(selectedId) {
            let html = '<option value="">Select Account</option>';
            accounts.forEach(function(account) {
                const sel = account.id == selectedId ? ' selected' : '';
                html += '<option value="' + account.id + '"' + sel + '>' + account.code + ' - ' + account.name + '</option>';
            });
            return html;
        }

        function buildPeriodOptions(selectedPeriod) {
            const periods = getPeriodLabels();
            let html = '<option value="">Select Period</option>';
            periods.forEach(function(p) {
                const sel = p === selectedPeriod ? ' selected' : '';
                html += '<option value="' + p + '"' + sel + '>' + p + '</option>';
            });
            return html;
        }

        function addLine(data) {
            const tbody = document.getElementById('lines-body');
            const tr = document.createElement('tr');
            const accountId = data ? data.account_id : '';
            const periodLabel = data ? data.period_label : '';
            const amount = data ? data.amount : '0';
            const notes = data ? (data.notes || '') : '';
            tr.innerHTML =
                '<td class="px-4 py-2">' +
                    '<select name="lines[' + lineIndex + '][account_id]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">' +
                        buildAccountOptions(accountId) +
                    '</select>' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<select name="lines[' + lineIndex + '][period_label]" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">' +
                        buildPeriodOptions(periodLabel) +
                    '</select>' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="number" name="lines[' + lineIndex + '][amount]" step="0.01" min="0" value="' + amount + '" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right" />' +
                '</td>' +
                '<td class="px-4 py-2">' +
                    '<input type="text" name="lines[' + lineIndex + '][notes]" value="' + notes + '" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />' +
                '</td>' +
                '<td class="px-4 py-2 text-center">' +
                    '<button type="button" onclick="removeLine(this)" class="text-red-600 hover:text-red-900" title="Remove"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>' +
                '</td>';
            tbody.appendChild(tr);
            lineIndex++;
        }

        function removeLine(btn) {
            btn.closest('tr').remove();
        }

        document.getElementById('add-line').addEventListener('click', function() { addLine(); });

        existingLines.forEach(function(line) { addLine(line); });

        if (existingLines.length === 0) {
            addLine();
        }
    </script>
</x-app-layout>
