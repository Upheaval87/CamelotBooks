<x-app-layout>
    <div class="bu-wrap max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <nav class="bu-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('accounting.budgets.index') }}">Budgets</a>
            <span>›</span>
            <span class="here">{{ $budget->name }}</span>
        </nav>

        <div class="page-head" style="margin-top:12px">
            <div>
                <h1 style="font-size:21px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">{{ $budget->name }}</h1>
                <div class="sub">{{ $budget->code }} · {{ $budget->typeLabel() }} · {{ $budget->fiscalYear?->label ?? $budget->fiscalYear?->name }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <span class="bu-badge bu-b-{{ $budget->statusColor() }}">{{ $budget->statusLabel() }}</span>
                @if($budget->isEditable())
                    <a href="{{ route('accounting.budgets.edit', $budget) }}" class="bu-btn">Edit</a>
                    @if($budget->status === 'draft')
                        <form method="POST" action="{{ route('accounting.budgets.submit', $budget) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="bu-btn bu-btn-cta">Submit for Approval</button>
                        </form>
                    @endif
                    @if($budget->status === 'pending_approval')
                        <form method="POST" action="{{ route('accounting.budgets.approve', $budget) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="bu-btn bu-btn-cta">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('accounting.budgets.reject', $budget) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="bu-btn bu-btn-danger-o">Reject</button>
                        </form>
                    @endif
                    @if($budget->status === 'approved')
                        <form method="POST" action="{{ route('accounting.budgets.lock', $budget) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="bu-btn bu-btn-ghost">Lock Budget</button>
                        </form>
                    @endif
                    @if($budget->status === 'locked')
                        <form method="POST" action="{{ route('accounting.budgets.unlock', $budget) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="bu-btn bu-btn-ghost">Unlock</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- Tab navigation --}}
        <div class="bu-tabs" style="margin-top:16px">
            <a href="#" class="bu-tab on" onclick="showTab('overview');return false">Overview</a>
            <a href="#" class="bu-tab" onclick="showTab('lines');return false">Lines</a>
            <a href="#" class="bu-tab" onclick="showTab('actuals');return false">Actuals</a>
            <a href="#" class="bu-tab" onclick="showTab('breakdown');return false">Monthly Breakdown</a>
            <a href="#" class="bu-tab" onclick="showTab('audit');return false">Audit Trail</a>
        </div>

        {{-- Overview tab --}}
        <div id="tab-overview" class="bu-pane on">
            <div class="bu-g3" style="margin-top:16px">
                <div class="bu-card">
                    <div class="bu-card-h">Summary</div>
                    <div class="bu-pad">
                        <div class="bu-g3">
                            <div class="bu-f"><label>Total Income</label><div class="bu-num" style="font-size:16px;font-weight:700;color:var(--mint-fg,#22c55e)">{{ number_format($budget->total_income, 2) }}</div></div>
                            <div class="bu-f"><label>Total Expenses</label><div class="bu-num" style="font-size:16px;font-weight:700;color:var(--red-2,#b91c1c)">{{ number_format($budget->total_expenses, 2) }}</div></div>
                            <div class="bu-f"><label>Net</label><div class="bu-num" style="font-size:16px;font-weight:700;color:var(--ink)">{{ number_format($budget->net_amount, 2) }}</div></div>
                            <div class="bu-f"><label>Lines</label><div style="font-size:16px;font-weight:700;color:var(--ink)">{{ $budget->lines->count() }}</div></div>
                        </div>
                    </div>
                </div>
                <div class="bu-card">
                    <div class="bu-card-h">Details</div>
                    <div class="bu-pad">
                        <div class="bu-g3">
                            <div class="bu-f"><label>Prepared By</label><div style="font-size:13px;color:var(--ink)">{{ $budget->preparedByUser?->name ?? '—' }}</div></div>
                            <div class="bu-f"><label>Period</label><div style="font-size:13px;color:var(--ink)">{{ $budget->periodLabel() }}</div></div>
                            <div class="bu-f"><label>Currency</label><div style="font-size:13px;color:var(--ink)">{{ $budget->currency }}</div></div>
                            <div class="bu-f"><label>Department</label><div style="font-size:13px;color:var(--ink)">{{ $budget->department ?? '—' }}</div></div>
                            <div class="bu-f"><label>Branch</label><div style="font-size:13px;color:var(--ink)">{{ $budget->branch?->name ?? '—' }}</div></div>
                            <div class="bu-f"><label>Project</label><div style="font-size:13px;color:var(--ink)">{{ $budget->project ?? '—' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lines tab --}}
        <div id="tab-lines" class="bu-pane" style="margin-top:16px">
            <div class="bu-card">
                <div class="bu-pad">
                    <div class="bu-li-wrap">
                        <table>
                            <thead><tr><th>Type</th><th>Account</th><th class="num">Annual</th><th class="num">Monthly</th><th>Utilization</th></tr></thead>
                            <tbody>
                                @forelse($budget->lines as $line)
                                    <tr>
                                        <td><span class="bu-badge bu-b-{{ $line->line_type === 'income' ? 'app' : 'pend' }}">{{ $line->line_type }}</span></td>
                                        <td>{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                                        <td class="num">{{ number_format($line->annual_amount, 2) }}</td>
                                        <td class="num">{{ number_format($line->annual_amount / 12, 2) }}</td>
                                        <td>
                                            <div class="bu-util"><div class="bu-ubar"><i class="bu-u-ok" style="width:{{ min(100, $line->utilization_pct ?? 0) }}%"></i></div></div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="bu-empty">No lines added yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actuals tab --}}
        <div id="tab-actuals" class="bu-pane" style="margin-top:16px">
            <div class="bu-card">
                <div class="bu-pad">
                    <div class="bu-li-wrap">
                        <table>
                            <thead><tr><th>Account</th><th class="num">Budget</th><th class="num">Actual</th><th class="num">Variance</th><th>Variance %</th></tr></thead>
                            <tbody>
                                @forelse($budget->lines as $line)
                                    @php
                                        $actual = $actuals[$line->account_id] ?? 0;
                                        $variance = $line->annual_amount - $actual;
                                        $varPct = $line->annual_amount > 0 ? round(abs($variance) / $line->annual_amount * 100, 1) : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $line->account?->code }} — {{ $line->account?->name }}</td>
                                        <td class="num">{{ number_format($line->annual_amount, 2) }}</td>
                                        <td class="num">{{ number_format($actual, 2) }}</td>
                                        <td class="num"><span class="bu-vch {{ $variance >= 0 ? 'bu-vch-ok' : 'bu-vch-crit' }}">{{ number_format($variance, 2) }}</span></td>
                                        <td><span class="bu-vch {{ $varPct <= 10 ? 'bu-vch-ok' : ($varPct <= 25 ? 'bu-vch-warn' : 'bu-vch-crit') }}">{{ $varPct }}%</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="bu-empty">No lines to compare.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Breakdown tab --}}
        <div id="tab-breakdown" class="bu-pane" style="margin-top:16px">
            <div class="bu-card">
                <div class="bu-pad">
                    <div class="bu-li-wrap">
                        <table>
                            <thead><tr><th>Account</th><th class="num">Jan</th><th class="num">Feb</th><th class="num">Mar</th><th class="num">Apr</th><th class="num">May</th><th class="num">Jun</th><th class="num">Jul</th><th class="num">Aug</th><th class="num">Sep</th><th class="num">Oct</th><th class="num">Nov</th><th class="num">Dec</th></tr></thead>
                            <tbody>
                                @forelse($budget->lines as $line)
                                    @php $breakdown = $line->monthlyBreakdown(); @endphp
                                    <tr>
                                        <td>{{ $line->account?->code }}</td>
                                        @for($m = 1; $m <= 12; $m++)
                                            <td class="num">{{ number_format($breakdown[$m] ?? 0, 0) }}</td>
                                        @endfor
                                    </tr>
                                @empty
                                    <tr><td colspan="13" class="bu-empty">No lines to break down.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Audit Trail tab --}}
        <div id="tab-audit" class="bu-pane" style="margin-top:16px">
            <div class="bu-card">
                <div class="bu-pad">
                    <div class="bu-li-wrap">
                        <table>
                            <thead><tr><th>Date</th><th>Action</th><th>User</th><th>Details</th></tr></thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                        <td><span class="bu-badge bu-b-app">{{ $log->action }}</span></td>
                                        <td>{{ $log->user?->name ?? '—' }}</td>
                                        <td style="font-size:12px;color:var(--muted);max-width:300px">{{ $log->description ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="bu-empty">No audit entries yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTab(name) {
        document.querySelectorAll('.bu-pane').forEach(p => p.classList.remove('on'));
        document.querySelectorAll('.bu-tab').forEach(t => t.classList.remove('on'));
        document.getElementById('tab-' + name).classList.add('on');
        event.target.classList.add('on');
    }
    </script>
</x-app-layout>
