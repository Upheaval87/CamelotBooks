@props(['journalEntry' => null, 'accounts', 'branches', 'costCenters' => null, 'isEdit' => false])

@php
    $action = $isEdit ? route('accounting.journal-entries.update', $journalEntry) : route('accounting.journal-entries.store');
    $method = $isEdit ? 'PATCH' : 'POST';
    $jsLines = $isEdit ? $journalEntry->lines->map(fn($l) => [
        'account_id' => (string) $l->account_id,
        'memo' => $l->memo ?? '',
        'debit' => (float) $l->debit,
        'credit' => (float) $l->credit,
        'cost_center_id' => $l->cost_center_id ? (string) $l->cost_center_id : '',
    ])->values()->toJson() : '[]';
@endphp

<form id="je-form" method="POST" action="{{ $action }}" x-data="jeForm({{ $jsLines }})">
    @csrf
    @if($isEdit) @method('PATCH') @endif

    <div class="je-card" style="margin-bottom:16px">
        <div class="je-card-h"><h2>Journal Information</h2></div>
        <div class="je-pad">
            <div class="je-g4">
                <div class="je-f">
                    <label>Journal No</label>
                    <input class="in" value="{{ $isEdit ? $journalEntry->journal_number : 'Auto-generated' }}" disabled>
                </div>
                <div class="je-f">
                    <label>Transaction Date</label>
                    <input class="in" type="date" name="date" value="{{ old('date', $isEdit ? $journalEntry->date->format('Y-m-d') : date('Y-m-d')) }}" required>
                </div>
                <div class="je-f">
                    <label>Journal Type</label>
                    <select class="in" name="is_adjusting_entry">
                        <option value="0" {{ ($isEdit ? !$journalEntry->is_adjusting_entry : true) ? 'selected' : '' }}>General Journal</option>
                        <option value="1" {{ $isEdit && $journalEntry->is_adjusting_entry ? 'selected' : '' }}>Adjusting</option>
                    </select>
                </div>
                <div class="je-f">
                    <label>Reference</label>
                    <input class="in" name="reference" value="{{ old('reference', $isEdit ? $journalEntry->reference : '') }}" placeholder="Ref">
                </div>
                <div class="je-f je-g-span3">
                    <label>Description</label>
                    <input class="in" name="memo" value="{{ old('memo', $isEdit ? $journalEntry->memo : '') }}" placeholder="Description">
                </div>
                <div class="je-f">
                    <label>Branch</label>
                    <select class="in" name="branch_id">
                        <option value="">None</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $isEdit ? $journalEntry->branch_id : '') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="je-card je-lines">
        <div class="je-card-h">
            <h2>Journal Lines</h2>
            <div class="right">
                <button type="button" class="je-btn je-btn-ghost je-btn-sm" @click="addLine()">＋ Add Line</button>
            </div>
        </div>
        <div class="je-li-wrap">
            <table class="je-table">
                <thead>
                    <tr>
                        <th style="width:26%">Account</th>
                        <th style="width:26%">Description</th>
                        <th class="num" style="width:12%">Debit</th>
                        <th class="num" style="width:12%">Credit</th>
                        <th style="width:18%">Cost Centre</th>
                        <th style="width:6%"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, idx) in lines" :key="idx">
                        <tr>
                            <td>
                                <select class="in" :name="'lines['+idx+'][account_id]'" x-model="line.account_id" required>
                                    <option value="">Select account…</option>
                                    @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->code }} · {{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input class="in" :name="'lines['+idx+'][memo]'" x-model="line.memo" placeholder="Line description"></td>
                            <td><input class="in" type="number" step="0.01" min="0" :name="'lines['+idx+'][debit]'" x-model.number="line.debit" @input="updateTotals()" style="text-align:right"></td>
                            <td><input class="in" type="number" step="0.01" min="0" :name="'lines['+idx+'][credit]'" x-model.number="line.credit" @input="updateTotals()" style="text-align:right"></td>
                            <td>
                                <select class="in" :name="'lines['+idx+'][cost_center_id]'">
                                    <option value="">None</option>
                                    @if($costCenters)
                                    @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}">{{ $cc->code }} · {{ $cc->name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </td>
                            <td>
                                <button type="button" class="rm" @click="removeLine(idx)">🗑</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="font-weight:800">Totals</td>
                        <td class="num" x-text="fmt(totalDebit)"></td>
                        <td class="num" x-text="fmt(totalCredit)"></td>
                        <td colspan="2">
                            <span class="je-okchip" :class="{'bad': !balanced}" x-show="lines.length > 0">
                                <template x-if="balanced"><span>✓ Balanced</span></template>
                                <template x-if="!balanced"><span x-text="'✗ Out ' + fmt(Math.abs(totalDebit - totalCredit))"></span></template>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="je-pad" style="display:flex;gap:10px;border-top:1px solid var(--je-line)">
            <button type="button" class="je-btn je-btn-ghost je-btn-sm" @click="addLine()">＋ Add Line</button>
            <button type="button" class="je-btn je-btn-ghost je-btn-sm" @click="validateBalance()">Validate Balance</button>
        </div>
    </div>

    <div class="je-actionbar">
        @if($isEdit)
        <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}" class="je-btn je-btn-ghost">Cancel</a>
        <button type="submit" name="action" value="save_draft" class="je-btn je-btn-sec">Save Changes</button>
        @else
        <a href="{{ route('accounting.journal-entries.index') }}" class="je-btn je-btn-ghost">Cancel</a>
        <button type="submit" name="action" value="save_draft" class="je-btn je-btn-sec">Save Draft</button>
        <button type="submit" name="action" value="post" class="je-btn je-btn-cta">Submit Approval</button>
        @endif
    </div>
</form>

@push('scripts')
<script>
function jeForm(existingLines) {
    return {
        lines: (existingLines && existingLines.length) ? existingLines : [
            { account_id: '', memo: '', debit: 0, credit: 0, cost_center_id: '' },
            { account_id: '', memo: '', debit: 0, credit: 0, cost_center_id: '' },
        ],
        totalDebit: 0,
        totalCredit: 0,
        balanced: true,
        init() { this.updateTotals(); },
        addLine() { this.lines.push({ account_id: '', memo: '', debit: 0, credit: 0, cost_center_id: '' }); },
        removeLine(idx) { if (this.lines.length > 2) this.lines.splice(idx, 1); this.updateTotals(); },
        updateTotals() {
            this.totalDebit = this.lines.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
            this.totalCredit = this.lines.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
            this.balanced = Math.abs(this.totalDebit - this.totalCredit) < 0.01;
        },
        validateBalance() {
            this.updateTotals();
            const errors = [];
            this.lines.forEach((l, i) => {
                if (!l.account_id) errors.push('Line ' + (i+1) + ': missing account');
                if ((parseFloat(l.debit) || 0) > 0 && (parseFloat(l.credit) || 0) > 0) errors.push('Line ' + (i+1) + ': both debit and credit set');
                if ((parseFloat(l.debit) || 0) === 0 && (parseFloat(l.credit) || 0) === 0) errors.push('Line ' + (i+1) + ': no amount');
            });
            if (!this.balanced) errors.push('Journal is unbalanced by ' + this.fmt(Math.abs(this.totalDebit - this.totalCredit)));
            if (this.lines.length < 2) errors.push('At least 2 lines required');
            if (errors.length) { window.CB && CB.toast('error', 'Validation Errors', errors.join('\n• ')); } else { window.CB && CB.toast('success', 'Valid', 'Journal is valid and balanced.'); }
        },
        fmt(n) { return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0); },
    };
}
</script>
@endpush
