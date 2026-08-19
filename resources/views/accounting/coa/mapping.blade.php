<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head" style="border:none;margin-bottom:12px">
            <div><h1>Account Mapping</h1><div class="sub">Validate before posting; built modules linked, not rebuilt.</div></div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.coa.index') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Validate Mapping</a>
                <button class="coa-btn coa-btn-cta coa-btn-sm">Map Account</button>
            </div>
        </div>

        <form method="POST" action="{{ route('accounting.coa.mapping.store') }}">
            @csrf
            <div class="grid2">
                @php
                    $groups = [
                        'Sales' => ['default_revenue', 'accounts_receivable', 'undeposited_funds'],
                        'Purchasing' => ['default_expense', 'accounts_payable', 'inventory_asset'],
                        'Payroll' => ['salary_expense', 'paye_payable', 'pension_payable', 'net_pay_payable'],
                        'Banking' => ['default_bank', 'cash_in_drawer', 'petty_cash'],
                        'Tax' => ['tax_payable', 'tax_receivable'],
                        'Inventory' => ['inventory_asset', 'inventory_adjustment', 'inventory_count_variance', 'purchase_price_variance'],
                    ];
                @endphp
                @foreach($groups as $moduleName => $keys)
                <div class="mapcard">
                    <div class="t">{{ $moduleName }} <span class="tchip" style="background:rgba(22,163,74,.10);border-color:rgba(22,163,74,.35);color:var(--green)">built</span></div>
                    @foreach($keys as $key)
                    <div class="maprow">
                        <span>{{ $availableKeys[$key] ?? $key }}</span>
                        <select name="mappings[{{ $key }}]" class="coa-in" style="height:32px;width:auto;min-width:140px;font-size:12px;border-radius:8px">
                            <option value="">— None —</option>
                            @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($mappings[$key]->account_id ?? '') == $acc->id ? 'selected' : '' }}>
                                {{ $acc->display_code }} {{ $acc->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
                @endforeach

                <div class="mapcard" style="border-color:rgba(18,143,142,.4)">
                    <div class="t">Budgeting <span class="tchip" style="background:rgba(18,143,142,.10);border-color:rgba(18,143,142,.35);color:var(--sec)">linkage</span></div>
                    <div class="maprow">
                        <span>Expense ↔ budget lines</span>
                        <a class="open-l" href="{{ route('accounting.budgets.dashboard') }}" style="margin-left:auto;font-size:11px;font-weight:800;color:var(--sec);text-decoration:none">Open Budgeting →</a>
                    </div>
                </div>

                <div class="mapcard" style="border-color:rgba(18,143,142,.4)">
                    <div class="t">Recurring Journals <span class="tchip" style="background:rgba(18,143,142,.10);border-color:rgba(18,143,142,.35);color:var(--sec)">linkage</span></div>
                    <div class="maprow">
                        <span>Template ↔ account</span>
                        <a class="open-l" href="{{ route('accounting.rj.index') }}" style="margin-left:auto;font-size:11px;font-weight:800;color:var(--sec);text-decoration:none">Open Recurring →</a>
                    </div>
                </div>
            </div>

            <div style="margin-top:16px;text-align:right">
                <button type="submit" class="coa-btn coa-btn-cta coa-btn-sm">Save Mappings</button>
            </div>
        </form>
    </div>
</x-app-layout>
