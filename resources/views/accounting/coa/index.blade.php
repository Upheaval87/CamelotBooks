<x-app-layout>
    <div class="coa-wrap coa-rebuild">
        <div class="page-head">
            <div><h1>Accounts</h1><div class="sub">Stored dash-less · displayed dashed · groups non-posting.</div></div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.coa.export-csv') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Export</a>
                <a href="{{ route('accounting.coa.create') }}" class="coa-btn coa-btn-cta coa-btn-sm">New Account</a>
            </div>
        </div>

        <div class="coa-card">
            <div class="coa-pad" style="padding-bottom:0">
                <div class="statgrid">
                    <a href="{{ route('accounting.coa.index') }}" class="fbox {{ !request('type') ? 'on' : '' }}">
                        <span class="t t-ink" style="width:2rem;height:2rem;border-radius:.625rem;display:grid;place-items:center;color:#fff">⬡</span>
                        <span><span class="l">All</span><span class="v" style="display:block">{{ $stats['total'] }}</span></span>
                    </a>
                    @php $typeMeta = ['asset'=>['1','t-teal'],'liability'=>['2','t-steel'],'equity'=>['3','t-mint'],'income'=>['4','t-mint'],'expense'=>['5','t-amber']]; @endphp
                    @foreach(['asset','liability','equity','income','expense'] as $t)
                    <a href="{{ route('accounting.coa.index', ['type' => $t]) }}" class="fbox {{ request('type') === $t ? 'on' : '' }}">
                        <span class="t {{ $typeMeta[$t][1] }}">{{ $typeMeta[$t][0] }}</span>
                        <span><span class="l">{{ ucfirst($t) }}s</span><span class="v" style="display:block">{{ $typeCounts->get($t, 0) }}</span></span>
                    </a>
                    @endforeach
                </div>

                <div class="controls" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:16px">
                    <form method="GET" action="{{ route('accounting.coa.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;width:100%">
                        <div class="search" style="position:relative;flex:1;min-width:220px;max-width:420px">
                            <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint)" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input class="coa-in" name="search" placeholder="Name or code (1-01-001)…" value="{{ request('search') }}" style="width:100%;height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 12px 0 36px;font-size:13px;color:var(--ink);font-family:inherit">
                        </div>
                        <select class="coa-in" name="type" style="height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 30px 0 12px;font-size:13px;color:var(--ink);font-family:inherit;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2210%22 height=%226%22%3E%3Cpath d=%22M1 1l4 4 4-4%22 stroke=%22%235f7476%22 stroke-width=%221.6%22 fill=%22none%22 stroke-linecap=%22round%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 11px center">
                            <option value="">All Types</option>
                            @foreach(['asset','liability','equity','income','expense'] as $t)
                            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}s</option>
                            @endforeach
                        </select>
                        <select class="coa-in" name="status" style="height:40px;border-radius:8px;border:1px solid var(--border);background:#fff;padding:0 30px 0 12px;font-size:13px;color:var(--ink);font-family:inherit;appearance:none;background-image:url('data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2210%22 height=%226%22%3E%3Cpath d=%22M1 1l4 4 4-4%22 stroke=%22%235f7476%22 stroke-width=%221.6%22 fill=%22none%22 stroke-linecap=%22round%22/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 11px center">
                            <option value="">Active + Inactive</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        <button type="submit" class="coa-btn coa-btn-ghost coa-btn-sm">Filter</button>
                        @if(request('search') || request('type') || request('status'))
                        <a href="{{ route('accounting.coa.index') }}" class="coa-btn coa-btn-ghost coa-btn-sm">Clear</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="coa-li-wrap">
                <table class="coa-table">
                    <thead>
                        <tr><th>Code</th><th>Account Name</th><th>Type</th><th>Level</th><th>Posting</th><th>Behaviour</th><th class="num">Balance</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($topLevel as $account)
                        @include('accounting.coa._account-row', ['account' => $account, 'depth' => 0, 'balances' => $balances])
                        @empty
                        <tr><td colspan="9" class="coa-empty">No accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
