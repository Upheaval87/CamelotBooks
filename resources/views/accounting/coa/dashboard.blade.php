<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>Chart of Accounts</h1><div class="sub">Hierarchical X-XX-XXX · the classification engine every module posts through.</div></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="{{ route('accounting.coa.index') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Manage Groups</a>
                <a href="{{ route('accounting.coa.reports') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Import</a>
                <a href="{{ route('accounting.coa.export-csv') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Export COA</a>
                <a href="{{ route('accounting.coa.reports') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Reports</a>
                <a href="{{ route('accounting.coa.create') }}" class="coa-btn coa-btn-cta coa-btn-sm">Add Account</a>
            </div>
        </div>

        <div class="coa-kpis" style="margin-bottom:12px">
            <div class="coa-kpi" style="border:none;background:linear-gradient(135deg,var(--sec-2),var(--sec) 60%,#0c7a79)">
                <div class="l" style="color:#dff7f6">Total Accounts</div>
                <div class="v" style="color:#fff">{{ $stats['total'] }}</div>
                <div class="n" style="color:#dff7f6">{{ $stats['active'] }} active · {{ $stats['inactive'] }} inactive</div>
            </div>
            <div class="coa-kpi">
                <div class="l">Assets</div>
                <div class="v">{{ $typeCounts->get('asset', 0) }}</div>
                <div class="n">1xxx · debit</div>
            </div>
            <div class="coa-kpi">
                <div class="l">Liabilities</div>
                <div class="v">{{ $typeCounts->get('liability', 0) }}</div>
                <div class="n">2xxx · credit</div>
            </div>
        </div>
        <div class="coa-kpis" style="margin-bottom:16px">
            <div class="coa-kpi">
                <div class="l">Equity</div>
                <div class="v">{{ $typeCounts->get('equity', 0) }}</div>
                <div class="n">3xxx · credit</div>
            </div>
            <div class="coa-kpi">
                <div class="l">Income</div>
                <div class="v">{{ $typeCounts->get('income', 0) }}</div>
                <div class="n">4xxx · credit</div>
            </div>
            <div class="coa-kpi warn">
                <div class="l">Expenses</div>
                <div class="v">{{ $typeCounts->get('expense', 0) }}</div>
                <div class="n">5xxx · debit</div>
            </div>
        </div>
        <div class="coa-kpis" style="margin-bottom:16px">
            <div class="coa-kpi warn">
                <div class="l">Without Mapping</div>
                <div class="v">{{ $unmappedCount }}</div>
                <div class="n"><a class="open-l" href="{{ route('accounting.coa.mapping') }}">Fix mappings →</a></div>
            </div>
            <div class="coa-kpi">
                <div class="l">Groups (non-posting)</div>
                <div class="v">{{ $stats['groups'] }}</div>
                <div class="n">Level 1 & 2</div>
            </div>
            <div class="coa-kpi">
                <div class="l">Posting Accounts</div>
                <div class="v">{{ $stats['posting'] }}</div>
                <div class="n">Level 3</div>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Recent Account Changes</h2>
                <a class="open-l" href="{{ route('accounting.coa.reports') }}#audit" style="margin-left:auto;font-size:11px;font-weight:800;color:var(--sec);text-decoration:none">Audit trail →</a>
            </div>
            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead>
                        <tr><th>Code</th><th>Account Name</th><th>Type</th><th>Level</th><th>Status</th><th>Balance</th></tr>
                    </thead>
                    <tbody>
                        @forelse($accounts->take(10) as $account)
                        <tr>
                            <td class="coa-mono">{{ $account->display_code }}</td>
                            <td style="font-weight:700;color:var(--ink)">{{ $account->name }}</td>
                            <td>
                                @php
                                    $typeClasses = ['asset'=>'mix','liability'=>'cr','equity'=>'cr','income'=>'post','expense'=>'dr'];
                                @endphp
                                <span class="tchip {{ $typeClasses[$account->type] ?? 'mix' }}">{{ ucfirst($account->type) }}</span>
                            </td>
                            <td><span class="tchip lv">L{{ $account->level }}</span></td>
                            <td><span class="coa-badge {{ $account->is_active ? 'coa-b-ok' : 'coa-b-off' }}"><span class="bdot"></span>{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="num" style="text-align:right;font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink)">{{ number_format($balances[$account->id] ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="coa-empty">No accounts yet. <a href="{{ route('accounting.coa.create') }}">Add the first account.</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
