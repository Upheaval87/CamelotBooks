<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>Account Tree</h1><div class="sub">Hierarchical structure with validation rules.</div></div>
            <div style="display:flex;gap:10px">
                <button class="coa-btn coa-btn-ghost coa-btn-sm" onclick="document.querySelectorAll('.tree li').forEach(li=>li.classList.remove('closed'))">Expand All</button>
                <a href="{{ route('accounting.coa.create') }}" class="coa-btn coa-btn-cta coa-btn-sm">Add Account</a>
            </div>
        </div>

        <div class="grid2">
            <div class="coa-card">
                <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Chart of Accounts — Tree</h2>
                </div>
                <div class="coa-pad">
                    <ul class="tree" role="tree">
                        @foreach($tree as $account)
                        @include('accounting.coa._tree-node', ['account' => $account, 'accounts' => $accounts, 'balances' => $balances])
                        @endforeach
                    </ul>
                </div>
                @if(!empty($issues))
                <div class="coa-pad" style="border-top:1px solid var(--line);display:flex;flex-direction:column;gap:10px">
                    @foreach($issues as $issue)
                    <div class="errcard">
                        ❌ <span><b>{{ $issue['message'] }}</b> — {{ $issue['account']->display_code }} {{ $issue['account']->name }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="coa-card">
                <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Account Types</h2>
                </div>
                <div class="coa-li-wrap">
                    <table class="coa-table" style="min-width:0">
                        <thead><tr><th>Type</th><th>Class</th><th>Statement</th><th>Normal</th></tr></thead>
                        <tbody>
                            <tr><td style="font-weight:700;color:var(--ink)">Asset</td><td class="coa-mono">1</td><td class="coa-em">Balance Sheet</td><td><span class="tchip dr">Debit</span></td></tr>
                            <tr><td style="font-weight:700;color:var(--ink)">Liability</td><td class="coa-mono">2</td><td class="coa-em">Balance Sheet</td><td><span class="tchip cr">Credit</span></td></tr>
                            <tr><td style="font-weight:700;color:var(--ink)">Equity</td><td class="coa-mono">3</td><td class="coa-em">Balance Sheet</td><td><span class="tchip cr">Credit</span></td></tr>
                            <tr><td style="font-weight:700;color:var(--ink)">Income</td><td class="coa-mono">4</td><td class="coa-em">Income Stmt</td><td><span class="tchip cr">Credit</span></td></tr>
                            <tr><td style="font-weight:700;color:var(--ink)">Expense</td><td class="coa-mono">5</td><td class="coa-em">Income Stmt</td><td><span class="tchip dr">Debit</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
