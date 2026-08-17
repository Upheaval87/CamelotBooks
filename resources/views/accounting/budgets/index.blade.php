<x-app-layout>
    <div class="bu-wrap">
        <div class="bu-page-head">
            <div>
                <h1>Budgets & Monitoring</h1>
                <div class="sub">Track spending against budget in real time.</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.budgets.vsactual') }}" class="bu-btn bu-btn-ghost">Budget vs Actual</a>
                <a href="{{ route('accounting.budgets.create') }}" class="bu-btn bu-btn-cta">+ New Budget</a>
            </div>
        </div>

        <div class="bu-card" style="margin-bottom:16px">
            <div class="bu-pad">
                <div class="bu-statcards">
                    <div class="bu-sc on" onclick="window.location='{{ route('accounting.budgets.index') }}'"><span class="ic bu-i-all">📊</span><span class="t">All</span><span class="c">{{ $stats->sum('count') }}</span></div>
                    <div class="bu-sc {{ request('status') === 'draft' ? 'on' : '' }}" onclick="window.location='{{ route('accounting.budgets.index', ['status' => 'draft']) }}'"><span class="ic bu-i-draft">✏️</span><span class="t">Draft</span><span class="c">{{ $stats->get('draft', (object)['count' => 0])->count }}</span></div>
                    <div class="bu-sc {{ request('status') === 'pending_approval' ? 'on' : '' }}" onclick="window.location='{{ route('accounting.budgets.index', ['status' => 'pending_approval']) }}'"><span class="ic bu-i-pend">⏳</span><span class="t">Pending</span><span class="c">{{ $stats->get('pending_approval', (object)['count' => 0])->count }}</span></div>
                    <div class="bu-sc {{ request('status') === 'approved' ? 'on' : '' }}" onclick="window.location='{{ route('accounting.budgets.index', ['status' => 'approved']) }}'"><span class="ic bu-i-app">✅</span><span class="t">Approved</span><span class="c">{{ $stats->get('approved', (object)['count' => 0])->count }}</span></div>
                    <div class="bu-sc {{ request('status') === 'locked' ? 'on' : '' }}" onclick="window.location='{{ route('accounting.budgets.index', ['status' => 'locked']) }}'"><span class="ic bu-i-lock">🔒</span><span class="t">Locked</span><span class="c">{{ $stats->get('locked', (object)['count' => 0])->count }}</span></div>
                </div>
                <div class="bu-toolbar">
                    <form method="GET" action="{{ route('accounting.budgets.index') }}" style="display:flex;gap:10px;align-items:center;width:100%">
                        <select class="in" name="fiscal_year_id" style="flex:none;width:200px">
                            <option value="">All Fiscal Years</option>
                            @foreach($fiscalYears as $fy)
                                <option value="{{ $fy->id }}" {{ request('fiscal_year_id') == $fy->id ? 'selected' : '' }}>{{ $fy->label ?? $fy->name }}</option>
                            @endforeach
                        </select>
                        <input class="in grow" name="search" placeholder="Search budgets…" value="{{ request('search') }}">
                        <button type="submit" class="bu-btn bu-btn-sec">Search</button>
                        @if(request('search') || request('fiscal_year_id') || request('status'))
                            <a href="{{ route('accounting.budgets.index') }}" class="bu-btn bu-btn-ghost">Clear</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="bu-card">
            <div class="bu-li-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Budget</th>
                            <th>Fiscal Year</th>
                            <th class="num">Income</th>
                            <th class="num">Expenses</th>
                            <th style="width:16%">Utilization</th>
                            <th>Status</th>
                            <th>Prepared By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budgets as $budget)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('accounting.budgets.show', $budget) }}'">
                                <td style="font-weight:700;color:var(--ink)">
                                    {{ $budget->name }}
                                    <span style="font-size:11px;color:var(--faint);margin-left:4px">{{ $budget->code }}</span>
                                </td>
                                <td class="bu-em">{{ $budget->fiscalYear?->label ?? $budget->fiscalYear?->name ?? '—' }}</td>
                                <td class="num">{{ number_format($budget->total_income ?? 0, 2) }}</td>
                                <td class="num">{{ number_format($budget->total_expenses ?? 0, 2) }}</td>
                                <td>
                                    @php
                                        $budgeted = (float) ($budget->total_expenses ?? 0);
                                        $util = $budgeted > 0 ? min(round(($budget->total_spent ?? 0) / max($budgeted, 1) * 100), 200) : 0;
                                        $uc = $util >= 100 ? 'bu-u-crit' : ($util >= 75 ? 'bu-u-warn' : 'bu-u-ok');
                                    @endphp
                                    <div class="bu-util">
                                        <div class="bu-ubar"><i class="{{ $uc }}" style="width:{{ min($util, 100) }}%"></i></div>
                                        <span class="p">{{ $util }}%</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusMap = [
                                            'draft'            => ['bu-b-draft', 'Draft'],
                                            'pending_approval' => ['bu-b-pend', 'Pending Approval'],
                                            'approved'         => ['bu-b-app', 'Approved'],
                                            'rejected'         => ['bu-b-lock', 'Rejected'],
                                            'locked'           => ['bu-b-lock', 'Locked'],
                                        ];
                                        $sm = $statusMap[$budget->status] ?? ['bu-b-draft', $budget->statusLabel()];
                                    @endphp
                                    <span class="bu-badge {{ $sm[0] }}"><span class="bu-bdot"></span>{{ $sm[1] }}</span>
                                </td>
                                <td class="bu-em">{{ $budget->preparedByUser?->name ?? '—' }}</td>
                                <td>
                                    <div class="bu-row-act">
                                        <button class="bu-ibtn" title="View" onclick="event.stopPropagation();window.location='{{ route('accounting.budgets.show', $budget) }}'">👁</button>
                                        @if($budget->isEditable())
                                            <button class="bu-ibtn" title="Edit" onclick="event.stopPropagation();window.location='{{ route('accounting.budgets.edit', $budget) }}'">✎</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="bu-empty">No budgets found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($budgets, 'links'))
                <div class="bu-pagi">
                    <span class="t">Showing {{ $budgets->firstItem() }}–{{ $budgets->lastItem() }} of {{ $budgets->total() }}</span>
                    {!! $budgets->links() !!}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
