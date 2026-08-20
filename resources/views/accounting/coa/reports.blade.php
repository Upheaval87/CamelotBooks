<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>COA Reports & Settings</h1><div class="sub">Import/Export, audit trail, reports, and system settings.</div></div>
        </div>

        <div class="grid2" style="margin-bottom:16px">
            <div class="coa-card">
                <div class="coa-card coa-pad" style="border-bottom:1px solid var(--line)">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Import / Export</h2>
                </div>
                <div class="coa-pad" style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="coa-btn coa-btn-ghost coa-btn-sm">Import COA</button>
                    <button class="coa-btn coa-btn-ghost coa-btn-sm">Import Balances</button>
                    <button class="coa-btn coa-btn-ghost coa-btn-sm">Import Mappings</button>
                    <a href="{{ route('accounting.coa.export-csv') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Export COA</a>
                    <button class="coa-btn coa-btn-ghost coa-btn-sm">Export Balances</button>
                </div>
            </div>

            <div class="coa-card" id="audit">
                <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Audit Trail</h2>
                </div>
                <div class="coa-pad" style="font-size:12px;color:var(--muted);display:flex;flex-direction:column;gap:8px">
                    <div><b style="color:var(--ink)">System initialized</b> — Chart of accounts created with {{ $stats['total'] }} accounts ({{ $stats['active'] }} active).</div>
                    @forelse($accounts->take(5) as $account)
                    <div><b style="color:var(--ink)">{{ $account->created_at?->format('d M Y') ?? '—' }}</b> — {{ $account->is_active ? 'Created' : 'Deactivated' }} {{ $account->display_code }} {{ $account->name }}</div>
                    @empty
                    <div>No recent changes.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="repcards" style="margin-bottom:16px">
            <div class="repcard">
                <span class="t">Chart of Accounts</span>
                <span class="d">Full COA with codes, levels, parents, balances.</span>
                <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="{{ route('accounting.reports.chart-of-accounts') }}">Open →</a></div>
            </div>
            <div class="repcard">
                <span class="t">Trial Balance</span>
                <span class="d">Dr/Cr balances by account for a period.</span>
                <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="{{ route('accounting.reports.trial-balance') }}">Open →</a></div>
            </div>
            <div class="repcard">
                <span class="t">General Ledger</span>
                <span class="d">Full history per account with running balance.</span>
                <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="{{ route('accounting.general-ledger.index') }}">Open →</a></div>
            </div>
            <div class="repcard">
                <span class="t">Account Balance</span>
                <span class="d">Closing balances by account and currency.</span>
                <div class="foot"><span class="fmt">PDF</span><span class="fmt">Excel</span><a class="open-l" href="{{ route('accounting.reports.trial-balance') }}">Open →</a></div>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-card coa-pad" style="display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line)">
                <h2 style="font-size:14px;font-weight:800;color:var(--ink)">COA Settings</h2>
                <span class="fmt" style="margin-left:auto;font-size:9.5px;font-weight:800;letter-spacing:.06em;color:var(--deep-1);background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);border-radius:999px;padding:2px 8px">admin</span>
            </div>
            <div class="coa-pad">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px 20px">
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Numbering</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">X-XX-XXX hierarchical · stored dash-less</div></div>
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Method</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">Inherited from company ({{ ucfirst(request()->user()?->currentCompany?->accounting_method ?? 'accrual') }})</div></div>
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Depth</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">3 default · unlimited via parent_id</div></div>
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Manual codes</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">Admin only</div></div>
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Total accounts</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">{{ $stats['total'] }} ({{ $stats['active'] }} active)</div></div>
                    <div class="fld"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--faint)">Posting accounts</div><div class="v" style="margin-top:4px;font-size:13px;font-weight:600;color:var(--ink)">{{ $stats['total'] - $stats['inactive'] }}</div></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
