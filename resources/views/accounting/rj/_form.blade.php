@php
    $formAction = $isEdit ? route('accounting.rj.update', $template) : route('accounting.rj.store');
    $cancelRoute = $isEdit ? route('accounting.rj.show', $template) : route('accounting.rj.index');
    $title = $isEdit ? 'Edit Recurring Journal' : 'Create Recurring Journal';
@endphp

<div>
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub">{{ $isEdit ? 'Update template schedule, lines and settings.' : 'Define a repeating journal template with line items and a generation schedule.' }}</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="{{ $cancelRoute }}" class="btn btn-ghost btn-sm">Cancel</a>
            <button type="submit" name="action" value="save_draft" form="rj-form" class="btn btn-ghost btn-sm">Save Draft</button>
            <button type="submit" name="action" value="activate" form="rj-form" class="btn btn-cta btn-sm">⚡ Activate Schedule</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="rj-form" enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid rgba(220,38,38,.3);border-radius:12px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-size:13px">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic">📋</span>
                    <h2>Basic Information</h2>
                </div>
                <div class="g4">
                    <div class="field sp2">
                        <label>Journal Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="input" value="{{ old('name', $template?->name ?? '') }}" required placeholder="e.g. Monthly Rent Accrual">
                    </div>
                    <div class="field">
                        <label>Reference</label>
                        <input type="text" class="input" value="{{ $template?->reference ?? 'Auto-assigned on save' }}" disabled>
                    </div>
                    <div class="field">
                        <label>Journal Type <span style="color:var(--red)">*</span></label>
                        <select name="journal_type" class="input" required>
                            <option value="standard" @selected(old('journal_type', $template?->journal_type) === 'standard')>Standard</option>
                            <option value="accrual" @selected(old('journal_type', $template?->journal_type) === 'accrual')>Accrual</option>
                            <option value="depreciation" @selected(old('journal_type', $template?->journal_type) === 'depreciation')>Depreciation</option>
                            <option value="prepayment" @selected(old('journal_type', $template?->journal_type) === 'prepayment')>Prepayment</option>
                            <option value="adjustment" @selected(old('journal_type', $template?->journal_type) === 'adjustment')>Adjustment</option>
                        </select>
                    </div>
                    <div class="field sp2">
                        <label>Description</label>
                        <textarea name="description" class="input" rows="2" placeholder="Optional description for this template">{{ old('description', $template?->description ?? '') }}</textarea>
                    </div>
                    <div class="field">
                        <label>Start Date <span style="color:var(--red)">*</span></label>
                        <input type="date" name="start_date" class="input" value="{{ old('start_date', $template?->start_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="field">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="input" value="{{ old('end_date', $template?->end_date?->format('Y-m-d') ?? '') }}">
                    </div>
                    <div class="field">
                        <label>Currency</label>
                        <select name="currency" class="input">
                            @forelse($currencies as $curOption)
                                <option value="{{ $curOption->code }}" @selected(old('currency', $template?->currency ?? 'USD') === $curOption->code)>{{ $curOption->code }} - {{ $curOption->name }}</option>
                            @empty
                                <option value="USD" @selected(old('currency', $template?->currency ?? 'USD') === 'USD')>USD - US Dollar</option>
                            @endforelse
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic">📝</span>
                    <h2>Journal Lines</h2>
                </div>
                <div class="li-wrap" style="margin-top:12px">
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="num">Debit</th>
                                <th class="num">Credit</th>
                                <th>Department</th>
                                <th>Cost Centre</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="rj-lines-body">
                            @php
                                $linesData = [];
                                if (old('lines')) {
                                    foreach (array_values(old('lines')) as $l) {
                                        $linesData[] = [
                                            'account_id' => $l['account_id'] ?? '',
                                            'memo' => $l['memo'] ?? '',
                                            'debit' => $l['debit'] ?? '',
                                            'credit' => $l['credit'] ?? '',
                                            'branch_id' => $l['branch_id'] ?? '',
                                            'cost_center_id' => $l['cost_center_id'] ?? '',
                                        ];
                                    }
                                } elseif ($isEdit) {
                                    foreach ($template->templateLines as $line) {
                                        $linesData[] = [
                                            'account_id' => $line->account_id ?? '',
                                            'memo' => $line->memo ?? '',
                                            'debit' => $line->debit > 0 ? $line->debit : '',
                                            'credit' => $line->credit > 0 ? $line->credit : '',
                                            'branch_id' => $line->branch_id ?? '',
                                            'cost_center_id' => $line->cost_center_id ?? '',
                                        ];
                                    }
                                }
                                if (empty($linesData)) {
                                    $linesData = [
                                        ['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => '', 'branch_id' => '', 'cost_center_id' => ''],
                                        ['account_id' => '', 'memo' => '', 'debit' => '', 'credit' => '', 'branch_id' => '', 'cost_center_id' => ''],
                                    ];
                                }
                            @endphp
                            @foreach($linesData as $idx => $ld)
                                <tr class="rj-line-row">
                                    <td>
                                        <select name="lines[{{ $idx }}][account_id]" class="input" required onchange="updateTotals()">
                                            <option value="">Select account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" @selected($ld['account_id'] == $acc->id)>{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="lines[{{ $idx }}][memo]" class="input" value="{{ $ld['memo'] }}" placeholder="Description">
                                    </td>
                                    <td>
                                        <input type="number" name="lines[{{ $idx }}][debit]" class="input" value="{{ $ld['debit'] }}" step="0.01" min="0" placeholder="0.00" style="text-align:right" onchange="updateTotals()">
                                    </td>
                                    <td>
                                        <input type="number" name="lines[{{ $idx }}][credit]" class="input" value="{{ $ld['credit'] }}" step="0.01" min="0" placeholder="0.00" style="text-align:right" onchange="updateTotals()">
                                    </td>
                                    <td>
                                        <select name="lines[{{ $idx }}][branch_id]" class="input">
                                            <option value="">None</option>
                                            @foreach($branches as $br)
                                                <option value="{{ $br->id }}" @selected($ld['branch_id'] == $br->id)>{{ $br->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="lines[{{ $idx }}][cost_center_id]" class="input">
                                            <option value="">None</option>
                                            @foreach($costCenters as $cc)
                                                <option value="{{ $cc->id }}" @selected($ld['cost_center_id'] == $cc->id)>{{ $cc->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger-o btn-xs" onclick="removeLine(this)" title="Remove line">✕</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Totals</td>
                                <td class="numr" id="rj-total-debit" style="font-weight:800">0.00</td>
                                <td class="numr" id="rj-total-credit" style="font-weight:800">0.00</td>
                                <td colspan="2" id="rj-balance-chip"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div style="margin-top:10px">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="addLine()">＋ Add Line</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-sec">
                <div class="sec-head">
                    <span class="sec-ic">📅</span>
                    <h2>Scheduling</h2>
                </div>
                <div class="g4">
                    <div class="field">
                        <label>Frequency <span style="color:var(--red)">*</span></label>
                        <select name="frequency" class="input" required>
                            <option value="daily" @selected(old('frequency', $template?->frequency) === 'daily')>Daily</option>
                            <option value="weekly" @selected(old('frequency', $template?->frequency) === 'weekly')>Weekly</option>
                            <option value="biweekly" @selected(old('frequency', $template?->frequency) === 'biweekly')>Biweekly</option>
                            <option value="monthly" @selected(old('frequency', $template?->frequency) === 'monthly')>Monthly</option>
                            <option value="quarterly" @selected(old('frequency', $template?->frequency) === 'quarterly')>Quarterly</option>
                            <option value="semi_annually" @selected(old('frequency', $template?->frequency) === 'semi_annually')>Semi-Annually</option>
                            <option value="yearly" @selected(old('frequency', $template?->frequency) === 'yearly')>Yearly</option>
                            <option value="custom" @selected(old('frequency', $template?->frequency) === 'custom')>Custom</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Day of Month</label>
                        <input type="number" name="day_of_month" class="input" value="{{ old('day_of_month', $template?->day_of_month ?? '') }}" min="1" max="31" placeholder="1–31">
                    </div>
                    <div class="field">
                        <label>Occurrences</label>
                        <input type="number" name="occurrences" class="input" value="{{ old('occurrences', $template?->occurrences ?? '') }}" min="1" placeholder="Unlimited if empty">
                    </div>
                    <div class="field">
                        <label>Next Execution</label>
                        <input type="text" class="input" value="{{ $template?->next_run_date?->format('d M Y') ?? 'Computed on activation' }}" disabled>
                    </div>
                    <div class="field">
                        <label>Generation Mode <span style="color:var(--red)">*</span></label>
                        <select name="generation_mode" class="input" required>
                            <option value="auto_post" @selected(old('generation_mode', $template?->generation_mode) === 'auto_post')>Auto Post</option>
                            <option value="approval_first" @selected(old('generation_mode', $template?->generation_mode) === 'approval_first')>Requires Approval</option>
                            <option value="draft_only" @selected(old('generation_mode', $template?->generation_mode) === 'draft_only')>Save as Draft Only</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Email Notification</label>
                        <select name="email_notification" class="input">
                            <option value="before_posting" @selected(old('email_notification', $template?->email_notification) === 'before_posting')>Before Posting</option>
                            <option value="after_posting" @selected(old('email_notification', $template?->email_notification) === 'after_posting')>After Posting</option>
                            <option value="none" @selected(old('email_notification', $template?->email_notification ?? 'none') === 'none')>None</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let lineIndex = document.querySelectorAll('#rj-lines-body .rj-line-row').length;

    function addLine() {
        const tbody = document.getElementById('rj-lines-body');
        const accountsOptions = document.querySelector('#rj-lines-body select[name*="[account_id]"]');
        const branchOptions = document.querySelector('#rj-lines-body select[name*="[branch_id]"]');
        const ccOptions = document.querySelector('#rj-lines-body select[name*="[cost_center_id]"]');

        const clone = document.querySelector('#rj-lines-body .rj-line-row:last-child').cloneNode(true);
        lineIndex++;

        clone.querySelectorAll('select, input').forEach(function(el) {
            el.name = el.name.replace(/\[\d+\]/, '[' + lineIndex + ']');
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });

        tbody.appendChild(clone);
        updateTotals();
    }

    function removeLine(btn) {
        const tbody = document.getElementById('rj-lines-body');
        const rows = tbody.querySelectorAll('.rj-line-row');
        if (rows.length <= 2) return;
        btn.closest('tr').remove();
        updateTotals();
    }

    function updateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('#rj-lines-body .rj-line-row').forEach(function(row) {
            const debitEl = row.querySelector('input[name*="[debit]"]');
            const creditEl = row.querySelector('input[name*="[credit]"]');
            totalDebit += parseFloat(debitEl?.value) || 0;
            totalCredit += parseFloat(creditEl?.value) || 0;
        });

        document.getElementById('rj-total-debit').textContent = totalDebit.toFixed(2);
        document.getElementById('rj-total-credit').textContent = totalCredit.toFixed(2);

        const chip = document.getElementById('rj-balance-chip');
        const diff = Math.abs(totalDebit - totalCredit);
        if (totalDebit === 0 && totalCredit === 0) {
            chip.innerHTML = '<span class="okchip bad">No amounts entered</span>';
        } else if (diff < 0.01) {
            chip.innerHTML = '<span class="okchip ok">✓ Balanced</span>';
        } else {
            chip.innerHTML = '<span class="okchip bad">Out ' + diff.toFixed(2) + '</span>';
        }
    }

    document.getElementById('rj-lines-body').addEventListener('change', function(e) {
        if (e.target.tagName === 'INPUT') {
            updateTotals();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        updateTotals();
    });
</script>
