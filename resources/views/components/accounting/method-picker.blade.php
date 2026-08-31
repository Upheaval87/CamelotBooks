@php
    // Shared Accounting Method picker (spec §3). Reused by BOTH company-creation
    // surfaces (self-serve modal + Super Admin page). Exposes a real radio group
    // so the chosen method is submitted with the form without JS; Alpine keeps
    // the `.sel` card highlight + `aria-checked` in sync.
    $name = $name ?? 'accounting_method';
    $repName = $repName ?? 'reporting_preference';
    $method = old($name, $method ?? 'accrual');
    $reporting = old($repName, $reporting ?? 'accrual_view');
@endphp
<div class="am-suite" x-data="{ method: @js($method), reporting: @js($reporting) }">
    <div class="optcards" role="radiogroup" aria-label="Accounting method">
        <label class="optcard" :class="{ sel: method === 'accrual' }" role="radio" :aria-checked="method === 'accrual' ? 'true' : 'false'">
            <input type="radio" name="{{ $name }}" value="accrual" class="am-radio"
                x-model="method" {{ $method === 'accrual' ? 'checked' : '' }}>
            <span class="rd"></span>
            <div class="t">Accrual (Recommended)</div>
            <div class="d">Record income when earned &amp; expenses when incurred. Tracks receivables, payables, inventory, accruals &amp; prepayments. Full Balance Sheet + Income Statement. Best for credit, inventory, loans, external reporting.</div>
        </label>
        <label class="optcard" :class="{ sel: method === 'cash' }" role="radio" :aria-checked="method === 'cash' ? 'true' : 'false'">
            <input type="radio" name="{{ $name }}" value="cash" class="am-radio"
                x-model="method" {{ $method === 'cash' ? 'checked' : '' }}>
            <span class="rd"></span>
            <div class="t">Cash</div>
            <div class="d">Record only when cash moves. Simpler, smaller chart. Best for tiny cash-only businesses. You can switch to accrual later via a controlled conversion.</div>
        </label>
    </div>

    <div class="grid2" style="margin-top:14px">
        <div class="warn">
            <span><b>What changes in your books:</b> accrual adds AR / AP / inventory / accrual / prepayment accounts and invoice &amp; bill modules; cash omits them and reports cash in/out. The method is stored per company and inherited by the Chart of Accounts.</span>
        </div>
        <div class="okchip" style="align-self:start">
            <span>Reporting preference: <b x-text="reporting === 'cash_view' ? 'Cash view' : 'Accrual view'">Accrual view</b> <span x-show="reporting === 'cash_view'">&middot; cash-basis view active</span><span x-show="reporting !== 'cash_view'">&middot; cash-basis view available as a report toggle</span></span>
            <input type="hidden" name="{{ $repName }}" :value="reporting" value="accrual_view">
        </div>
    </div>
</div>
