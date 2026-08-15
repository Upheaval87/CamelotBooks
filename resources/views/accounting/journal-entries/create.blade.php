<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            {{-- sticky head --}}
            <div class="sticky-head">
                <div>
                    <h1>{{ __('New Journal Entry') }}</h1>
                    <div class="sub">{{ __('Post a manual journal entry to the general ledger.') }}</div>
                </div>
                <div class="tbtns">
                    <span id="balanceStatusPill" class="badge"></span>
                    <a href="{{ route('accounting.journal-entries.index') }}" class="btn ghost">{{ __('Cancel') }}</a>
                    <button type="submit" form="journalForm" onclick="document.getElementById('actionInput').value='save_draft'" class="btn ghost">{{ __('Save as Draft') }}</button>
                    <button type="submit" form="journalForm" onclick="document.getElementById('actionInput').value='post'" class="btn cta">{{ __('Post') }}</button>
                </div>
            </div>

            <div class="shell">
                <div style="display:flex;flex-direction:column;gap:20px;min-width:0">

                    <form id="journalForm" method="POST" action="{{ route('accounting.journal-entries.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="action" id="actionInput" value="">

                        <x-input-error :messages="$errors->get('error')" class="mb-4" />

                        {{-- entry details --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/></svg></span>
                                <h2>{{ __('Entry Details') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="g4">
                                <div class="field">
                                    <label for="date">{{ __('Date') }} <span class="req">*</span></label>
                                    <input type="date" id="date" name="date" class="input" value="{{ old('date', date('Y-m-d')) }}" required />
                                    <x-input-error :messages="$errors->get('date')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="reference">{{ __('Reference') }}</label>
                                    <input type="text" id="reference" name="reference" class="input" value="{{ old('reference') }}" placeholder="e.g. Invoice #, Receipt #" />
                                    <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                                </div>
                                <div class="field">
                                    <label for="branch_id">{{ __('Branch') }}</label>
                                    <x-scoped-search-field
                                        name="branch_id"
                                        entity="branch"
                                        search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                                        :value="old('branch_id')"
                                        :label="old('branch_id') ? ($branches->firstWhere('id', (int) old('branch_id'))?->name ?? '') : ''"
                                        placeholder="{{ __('Search branches...') }}"
                                    />
                                    <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                                </div>
                                <div class="field" style="display:flex;align-items:flex-end">
                                    <label class="flex items-center" style="margin-bottom:10px;gap:8px;text-transform:none;letter-spacing:0">
                                        <input type="checkbox" name="is_adjusting_entry" value="1" {{ old('is_adjusting_entry') ? 'checked' : '' }} style="width:15px;height:15px;accent-color:var(--sec,#128F8E)" />
                                        <span style="font-size:0.929rem;font-weight:600;color:var(--ink,#0B2A2D)">{{ __('Adjusting Entry') }}</span>
                                    </label>
                                </div>
                                <div class="field sp3">
                                    <label for="memo">{{ __('Description') }}</label>
                                    <textarea id="memo" name="memo" rows="2" class="input" style="min-height:80px">{{ old('memo') }}</textarea>
                                    <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                                </div>
                            </div>
                        </section>

                        {{-- lines --}}
                        <section class="card card-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/></svg></span>
                                <h2>{{ __('Lines') }}</h2>
                                <span class="rule"></span>
                                <button type="button" onclick="clearAllLines()" class="btn ghost btn-sm">{{ __('Clear All Lines') }}</button>
                                <button type="button" onclick="resetForm()" class="btn ghost btn-sm">{{ __('Reset Form') }}</button>
                                <button type="button" id="addLineBtn" class="btn btn-sec btn-sm">+ {{ __('Add Line') }}</button>
                            </div>

                            <div class="li-wrap" style="margin-top:16px">
                                <table id="linesTable">
                                    <thead>
                                        <tr>
                                            <th style="width:4%">#</th>
                                            <th style="width:24%">{{ __('Account') }}</th>
                                            <th class="num" style="width:13%">{{ __('Dr') }} ({{ $cs }})</th>
                                            <th class="num" style="width:13%">{{ __('Cr') }} ({{ $cs }})</th>
                                            <th style="width:20%">{{ __('Description') }}</th>
                                            <th style="width:18%">{{ __('Branch') }}</th>
                                            <th style="width:8%"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="linesBody">
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2" style="text-align:right;font-weight:700;color:var(--muted,#5F7476)">{{ __('Totals') }}</td>
                                            <td class="numr" style="font-weight:800" id="totalDebit">0.00</td>
                                            <td class="numr" style="font-weight:800" id="totalCredit">0.00</td>
                                            <td colspan="3" style="text-align:right" id="balanceIndicator"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                        </section>
                    </form>

                </div>

                {{-- rail --}}
                <aside class="railsum">
                    <div class="card">
                        <div class="rail-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg></span>
                                <h2>{{ __('Quick Nav') }}</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="vlist">
                                <a href="{{ route('accounting.accounts.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg></span>
                                    {{ __('Chart of Accounts') }}
                                </a>
                                <a href="{{ route('accounting.journal-entries.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg></span>
                                    {{ __('Journal Entries List') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <script>
        const ACCOUNT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'account']));
        const BRANCH_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'branch']));
        const CURRENCY_SYMBOL = @json($cs);
        let lineIndex = 0;

        function addLine() {
            const tbody = document.getElementById('linesBody');
            const tr = document.createElement('tr');
            tr.setAttribute('data-index', lineIndex);
            tr.innerHTML =
                '<td style="font-weight:600;color:var(--muted,#5F7476)">' + (tbody.rows.length + 1) + '</td>' +
                '<td style="padding:10px 6px">' +
                    scopedSearchFieldHtml({
                        name: 'lines[' + lineIndex + '][account_id]',
                        entity: 'account',
                        searchUrl: ACCOUNT_SEARCH_URL,
                        value: '',
                        label: '',
                        placeholder: 'Search accounts...',
                        required: true,
                    }) +
                '</td>' +
                '<td style="padding:10px 6px"><input type="number" name="lines[' + lineIndex + '][debit]" step="0.01" min="0" value="0" class="input text-right debit-input" /></td>' +
                '<td style="padding:10px 6px"><input type="number" name="lines[' + lineIndex + '][credit]" step="0.01" min="0" value="0" class="input text-right credit-input" /></td>' +
                '<td style="padding:10px 6px"><input type="text" name="lines[' + lineIndex + '][memo]" class="input" /></td>' +
                '<td style="padding:10px 6px">' +
                    scopedSearchFieldHtml({
                        name: 'lines[' + lineIndex + '][branch_id]',
                        entity: 'branch',
                        searchUrl: BRANCH_SEARCH_URL,
                        value: '',
                        label: '',
                        placeholder: 'Use header branch',
                    }) +
                '</td>' +
                '<td style="padding:10px 6px;text-align:center"><button type="button" onclick="removeLine(this)" class="ibtn del" title="Remove line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button></td>';
            tbody.appendChild(tr);
            lineIndex++;
            attachInputListeners();
            updateTotals();
        }

        function removeLine(btn) {
            const tbody = document.getElementById('linesBody');
            if (tbody.rows.length <= 2) {
                CB.toast('error', 'At least two lines are required.');
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
                pill.className = 'badge';
            } else if (diff < 0.005) {
                indicator.innerHTML = '<span class="badge b-post" style="display:inline-flex;align-items:center;gap:6px"><span class="bdot"></span>Balanced</span>';
                pill.innerHTML = '<span class="bdot"></span>Balanced';
                pill.className = 'badge b-post';
            } else {
                indicator.innerHTML = '<span class="badge b-red" style="display:inline-flex;align-items:center;gap:6px"><span class="bdot"></span>Out of balance: ' + CURRENCY_SYMBOL + diff.toFixed(2) + '</span>';
                pill.innerHTML = '<span class="bdot"></span>Out of balance: ' + CURRENCY_SYMBOL + diff.toFixed(2);
                pill.className = 'badge b-red';
            }
        }

        function attachInputListeners() {
            document.querySelectorAll('.debit-input, .credit-input').forEach(function(input) {
                input.removeEventListener('input', updateTotals);
                input.addEventListener('input', updateTotals);
            });
        }

        function clearAllLines() {
            CB.confirm({ type: 'action', title: 'Clear all lines?' }).then(function (ok) {
                if (!ok) return;
                const tbody = document.getElementById('linesBody');
                tbody.innerHTML = '';
                addLine();
                addLine();
                updateTotals();
            });
        }

        function resetForm() {
            CB.confirm({ type: 'action', title: 'Reset the entire form?' }).then(function (ok) {
                if (!ok) return;
                document.getElementById('journalForm').reset();
                document.getElementById('memo').value = '';
                const tbody = document.getElementById('linesBody');
                tbody.innerHTML = '';
                lineIndex = 0;
                addLine();
                addLine();
                updateTotals();
            });
        }

        document.getElementById('addLineBtn').addEventListener('click', addLine);

        document.getElementById('journalForm').addEventListener('submit', function(e) {
            updateTotals();
            const totalDebit = parseFloat(document.getElementById('totalDebit').textContent) || 0;
            const totalCredit = parseFloat(document.getElementById('totalCredit').textContent) || 0;

            if (Math.abs(totalDebit - totalCredit) >= 0.005) {
                e.preventDefault();
                CB.toast('error', 'Debits and credits must be equal before submitting.');
                return;
            }

            if (totalDebit === 0 && totalCredit === 0) {
                e.preventDefault();
                CB.toast('error', 'At least one line must have a debit or credit amount.');
                return;
            }
        });

        addLine();
        addLine();
    </script>
</x-app-layout>
