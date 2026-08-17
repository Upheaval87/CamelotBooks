@php
  $isEdit = $isEdit ?? false;
@endphp

<form method="POST" action="{{ $isEdit ? route('accounting.budgets.update', $budget) : route('accounting.budgets.store') }}" id="budget-form" enctype="multipart/form-data">
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif

  <!-- Breadcrumbs -->
  <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
    <div class="bu-crumbs"><a href="{{ route('accounting.budgets.index') }}">Budgets</a> › <span class="here">{{ $isEdit ? 'Edit Budget' : 'Create Budget' }}</span></div>

    <!-- Page head -->
    <div class="bu-page-head" style="padding-top:6px"><div><h1>{{ $isEdit ? 'Edit Budget' : 'Create Budget' }}</h1><div class="sub">{{ $isEdit ? 'Update budget details and line items.' : 'Create a new budget with line items.' }}</div></div></div>

    <!-- Budget Information card -->
    <div class="bu-card" style="margin-bottom:16px">
      <div class="bu-card-h"><h2>Budget Information</h2></div>
      <div class="bu-pad">
        <div class="bu-g3">
          <div class="bu-f"><label>Budget Name *</label><input class="in" name="name" value="{{ old('name', $budget->name ?? '') }}" placeholder="e.g. FY2026 Operating Budget" required></div>
          <div class="bu-f"><label>Type *</label><select class="in" name="type" required>
            <option value="operating" {{ old('type', $budget->type ?? '') === 'operating' ? 'selected' : '' }}>Operating Budget</option>
            <option value="capital" {{ old('type', $budget->type ?? '') === 'capital' ? 'selected' : '' }}>Capital Budget</option>
            <option value="department" {{ old('type', $budget->type ?? '') === 'department' ? 'selected' : '' }}>Departmental</option>
            <option value="project" {{ old('type', $budget->type ?? '') === 'project' ? 'selected' : '' }}>Project</option>
          </select></div>
          <div class="bu-f"><label>Fiscal Year *</label><select class="in" name="fiscal_year_id" required>
            <option value="">Select…</option>
            @foreach($fiscalYears as $fy)<option value="{{ $fy->id }}" {{ old('fiscal_year_id', $budget->fiscal_year_id ?? '') == $fy->id ? 'selected' : '' }}>{{ $fy->name }}</option>@endforeach
          </select></div>
          <div class="bu-f"><label>Period *</label><select class="in" name="period" required>
            <option value="annual" {{ old('period', $budget->period ?? '') === 'annual' ? 'selected' : '' }}>Annual</option>
            <option value="quarterly" {{ old('period', $budget->period ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
            <option value="monthly" {{ old('period', $budget->period ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
          </select></div>
          <div class="bu-f"><label>Currency</label><select class="in" name="currency">
            @foreach($currencies as $cur)<option value="{{ $cur->code }}" {{ old('currency', $budget->currency ?? '') === $cur->code ? 'selected' : '' }}>{{ $cur->code }} — {{ $cur->name }}</option>@endforeach
          </select></div>
          <div class="bu-f"><label>Branch</label><select class="in" name="branch_id">
            <option value="">— None —</option>
            @foreach($branches as $br)<option value="{{ $br->id }}" {{ old('branch_id', $budget->branch_id ?? '') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>@endforeach
          </select></div>
        </div>
      </div>
    </div>

    <!-- Budget Lines card -->
    <div class="bu-card lines" style="margin-bottom:16px">
      <div class="bu-card-h"><h2>Budget Lines</h2><div style="margin-left:auto"><button type="button" class="bu-btn bu-btn-ghost bu-btn-sm" id="add-line">＋ Add Line</button></div></div>
      <div class="bu-lines">
        <table>
          <thead><tr><th style="width:14%">Type</th><th style="width:34%">Account</th><th style="width:18%" class="num">Annual Amount</th><th style="width:16%">Distribution</th><th style="width:6%"></th></tr></thead>
          <tbody id="lines-body">
            @php
              $lines = old('lines', $isEdit ? $budget->lines->map(fn($l) => [
                'id' => $l->id,
                'type' => $l->line_type,
                'account_id' => $l->account_id,
                'annual_amount' => $l->annual_amount,
                'distribution' => $l->distribution,
              ])->toArray() : [['type' => 'expense', 'account_id' => '', 'annual_amount' => '', 'distribution' => 'even']]);
            @endphp
            @foreach($lines as $i => $line)
            <tr class="line-row">
              <td><select class="in" name="lines[{{ $i }}][type]"><option value="income" {{ ($line['type'] ?? '') === 'income' ? 'selected' : '' }}>Income</option><option value="expense" {{ ($line['type'] ?? '') === 'expense' ? 'selected' : '' }}>Expense</option></select></td>
              <td><select class="in" name="lines[{{ $i }}][account_id]" required><option value="">Select account…</option>
                @foreach($accounts as $acc)<option value="{{ $acc->id }}" {{ ($line['account_id'] ?? '') == $acc->id ? 'selected' : '' }}>{{ $acc->code }} · {{ $acc->name }}</option>@endforeach
              </select></td>
              <td><input class="in" name="lines[{{ $i }}][annual_amount]" type="text" value="{{ number_format((float) ($line['annual_amount'] ?? 0), 2, '.', '') }}" style="text-align:right" required></td>
              <td><select class="in" name="lines[{{ $i }}][distribution]"><option value="even" {{ ($line['distribution'] ?? '') === 'even' ? 'selected' : '' }}>Even</option><option value="weighted" {{ ($line['distribution'] ?? '') === 'weighted' ? 'selected' : '' }}>Weighted</option><option value="seasonal" {{ ($line['distribution'] ?? '') === 'seasonal' ? 'selected' : '' }}>Seasonal</option></select></td>
              <td><button type="button" class="rm" onclick="this.closest('tr').remove();recalcTotals()">✕</button></td>
            </tr>
            @if($isEdit)
              <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line['id'] ?? '' }}">
            @endif
            @endforeach
          </tbody>
          <tfoot>
            <tr><td colspan="2">Total Income: <b id="total-income">0</b></td><td class="num">Total Expenses: <b id="total-expenses">0</b></td><td colspan="2">Net: <b id="total-net" style="color:var(--green)">0</b></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Sticky action bar -->
    <div class="bu-card bu-actionbar">
      <a href="{{ route('accounting.budgets.index') }}" class="bu-btn bu-btn-ghost">Cancel</a>
      @if(!$isEdit)
        <button type="submit" name="action" value="save_and_new" class="bu-btn bu-btn-sec">Save & New</button>
      @endif
      <button type="submit" name="action" value="save_draft" class="bu-btn bu-btn-sec">Save Draft</button>
      <button type="submit" name="action" value="submit_for_approval" class="bu-btn bu-btn-cta">Submit for Approval</button>
    </div>
  </div>
</form>

<script>
let lineIndex = {{ count($lines) }};
function recalcTotals() {
  let income = 0, expenses = 0;
  document.querySelectorAll('#lines-body .line-row').forEach(row => {
    const type = row.querySelector('select[name*="[type]"]')?.value;
    const amount = parseFloat(row.querySelector('input[name*="[annual_amount]"]')?.value) || 0;
    if (type === 'income') income += amount;
    else expenses += amount;
  });
  document.getElementById('total-income').textContent = income.toFixed(2);
  document.getElementById('total-expenses').textContent = expenses.toFixed(2);
  document.getElementById('total-net').textContent = (income - expenses).toFixed(2);
}
document.getElementById('add-line').addEventListener('click', function() {
  const tbody = document.getElementById('lines-body');
  const templateRow = tbody.querySelector('.line-row');
  const clone = templateRow.cloneNode(true);
  clone.querySelectorAll('select, input').forEach(el => {
    el.name = el.name.replace(/\[\d+\]/, '[' + lineIndex + ']');
    if (el.type !== 'hidden') { el.value = el.tagName === 'SELECT' ? el.options[0].value : ''; }
    if (el.name.includes('[id]')) { el.value = ''; }
  });
  tbody.appendChild(clone);
  lineIndex++;
  recalcTotals();
});
recalcTotals();
</script>
